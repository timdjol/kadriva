<script setup>
import { computed, onMounted, ref } from 'vue';
import { api, listFrom } from '../services/api';
import { backdropOf, ratingOf, titleName, titlePath, yearOf } from '../utils/media';
import MediaRow from '../components/MediaRow.vue';
import StatePanel from '../components/StatePanel.vue';

const payload = ref(null);
const loading = ref(true);
const error = ref('');

const sections = computed(() => {
    const data = payload.value || {};
    if (Array.isArray(data.sections) && data.sections.length) {
        return data.sections.map((section) => ({ title: section.title || section.name, items: listFrom(section.items || section) }));
    }
    const collections = (data.collections || []).map((collection) => ({
        title: collection.name,
        items: listFrom(collection.titles),
    }));
    return [
        { title: 'Продолжить просмотр', items: listFrom(data.continue_watching) },
        { title: 'Популярное', items: listFrom(data.featured) },
        { title: 'Новинки', items: listFrom(data.new_movies || data.movies) },
        { title: 'Сериалы', items: listFrom(data.series || data.new_series) },
        { title: 'Мультфильмы', items: listFrom(data.cartoons) },
        { title: 'Аниме', items: listFrom(data.anime) },
        ...collections,
    ].filter((section) => section.items.length);
});
const hero = computed(() => payload.value?.hero || listFrom(payload.value?.featured)[0] || sections.value[0]?.items?.[0]);

async function load() {
    loading.value = true;
    error.value = '';
    try {
        payload.value = (await api.home()).data || {};
    } catch (exception) {
        error.value = exception.userMessage;
    } finally {
        loading.value = false;
    }
}
onMounted(load);
</script>

<template>
    <div>
        <StatePanel v-if="loading || error" :loading="loading" :error="error" @retry="load" />
        <template v-else>
            <section v-if="hero" class="hero" :style="backdropOf(hero) ? { backgroundImage: `linear-gradient(90deg, rgba(20,17,17,.98) 5%, rgba(20,17,17,.72) 53%, rgba(20,17,17,.18)), linear-gradient(0deg, #141111, transparent 35%), url('${backdropOf(hero)}')` } : {}">
                <div class="shell hero-content">
                    <span class="eyebrow">Выбор редакции</span>
                    <h1>{{ titleName(hero) }}</h1>
                    <div class="hero-meta"><span class="rating-inline">★ {{ ratingOf(hero) }}</span><span>{{ yearOf(hero) }}</span><span>{{ hero.type === 'series' ? 'Сериал' : 'Фильм' }}</span><span v-if="hero.age_rating">{{ hero.age_rating }}+</span></div>
                    <p>{{ hero.overview || hero.description || 'Откройте новую историю — включайте и смотрите прямо сейчас.' }}</p>
                    <div class="button-row">
                        <RouterLink class="button button-primary focusable" :to="titlePath(hero)"><span aria-hidden="true">▶</span> Смотреть</RouterLink>
                        <RouterLink class="button button-secondary focusable" :to="titlePath(hero)">Подробнее</RouterLink>
                    </div>
                </div>
            </section>
            <div class="shell home-sections">
                <MediaRow v-for="section in sections" :key="section.title" :title="section.title" :items="section.items" />
                <StatePanel :empty="!sections.length" empty-text="Пока здесь нет подборок" />
            </div>
        </template>
    </div>
</template>
