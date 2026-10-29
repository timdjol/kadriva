<script setup>
import { onMounted, reactive, ref, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { api, listFrom } from '../services/api';
import MediaCard from '../components/MediaCard.vue';
import StatePanel from '../components/StatePanel.vue';

const route = useRoute();
const router = useRouter();
const items = ref([]);
const meta = ref({});
const genres = ref([]);
const loading = ref(false);
const error = ref('');
const filters = reactive({
    type: route.query.type || '',
    genre: route.query.genre || '',
    year: route.query.year || '',
    rating: route.query.rating || '',
    sort: route.query.sort || 'popular',
    page: Number(route.query.page || 1),
});

let timer;
watch(() => [filters.type, filters.genre, filters.year, filters.rating, filters.sort], () => {
    filters.page = 1;
    clearTimeout(timer);
    timer = setTimeout(load, 250);
});

async function load() {
    loading.value = true;
    error.value = '';
    const params = Object.fromEntries(Object.entries(filters).filter(([, value]) => value !== ''));
    router.replace({ query: params }).catch(() => {});
    try {
        const response = await api.titles(params);
        items.value = listFrom(response.data);
        meta.value = { ...(response.data?.meta || {}), ...response.meta };
    } catch (exception) {
        error.value = exception.userMessage;
    } finally {
        loading.value = false;
    }
}

function pageTo(page) {
    if (page < 1 || page > Number(meta.value.last_page || meta.value.total_pages || 1)) return;
    filters.page = page;
    load();
    window.scrollTo({ top: 0, behavior: 'smooth' });
}
function reset() {
    Object.assign(filters, { type: '', genre: '', year: '', rating: '', sort: 'popular', page: 1 });
}
onMounted(async () => {
    load();
    try {
        genres.value = listFrom((await api.genres()).data);
    } catch {
        genres.value = [];
    }
});
</script>

<template>
    <div class="shell page">
        <header class="page-heading"><div><span class="eyebrow">Библиотека</span><h1>Каталог</h1></div><p>Фильмы и сериалы на любой вечер</p></header>
        <section class="filters" aria-label="Фильтры каталога">
            <label>Тип<select v-model="filters.type" class="focusable"><option value="">Все</option><option value="movie">Фильмы</option><option value="series">Сериалы</option></select></label>
            <label>Жанр<select v-model="filters.genre" class="focusable"><option value="">Все</option><option v-for="genre in genres" :key="genre.id" :value="genre.id">{{ genre.name }}</option></select></label>
            <label>Год<input v-model="filters.year" class="focusable" type="number" min="1900" :max="new Date().getFullYear() + 1" placeholder="Любой" /></label>
            <label>Рейтинг<select v-model="filters.rating" class="focusable"><option value="">Любой</option><option value="7">От 7</option><option value="8">От 8</option><option value="9">От 9</option></select></label>
            <label>Сортировка<select v-model="filters.sort" class="focusable"><option value="popular">По популярности</option><option value="rating">По рейтингу</option><option value="release_date">По дате выхода</option></select></label>
            <button class="button button-ghost focusable filter-reset" @click="reset">Сбросить</button>
        </section>
        <StatePanel :loading="loading" :error="error" :empty="!loading && !error && !items.length" @retry="load" />
        <div v-if="!loading && items.length" class="media-grid">
            <MediaCard v-for="item in items" :key="item.id" :item="item" />
        </div>
        <nav v-if="Number(meta.last_page || meta.total_pages) > 1" class="pagination" aria-label="Страницы каталога">
            <button class="focusable" :disabled="filters.page <= 1" @click="pageTo(filters.page - 1)">←</button>
            <span>Страница {{ filters.page }} из {{ meta.last_page || meta.total_pages }}</span>
            <button class="focusable" :disabled="filters.page >= Number(meta.last_page || meta.total_pages)" @click="pageTo(filters.page + 1)">→</button>
        </nav>
    </div>
</template>
