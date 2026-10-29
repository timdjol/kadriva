<script setup>
import { computed, onMounted, ref, watch } from 'vue';
import { useRoute } from 'vue-router';
import { api, listFrom } from '../services/api';
import { backdropOf, durationLabel, posterOf, ratingOf, titleName, yearOf } from '../utils/media';
import { useSessionStore } from '../stores/session';
import StatePanel from '../components/StatePanel.vue';
import VideoPlayer from '../components/VideoPlayer.vue';

const route = useRoute();
const session = useSessionStore();
const title = ref(null);
const seasons = ref([]);
const watchOptions = ref([]);
const selectedSeason = ref(null);
const selectedEpisode = ref(null);
const selectedOption = ref(null);
const loading = ref(true);
const error = ref('');
const favoriteBusy = ref(false);
const titleId = computed(() => {
    if (route.params.id) return route.params.id;
    return String(route.params.slugId || '').match(/-(\d+)$/)?.[1] || route.params.slugId;
});

const genres = computed(() => (title.value?.genres || []).map((item) => typeof item === 'string' ? item : item.name).filter(Boolean));
const episodes = computed(() => selectedSeason.value?.episodes || []);
const isFavorite = computed(() => session.favoriteIds.has(Number(titleId.value)));
const statusLabel = computed(() => {
    const status = String(selectedOption.value?.mode || selectedOption.value?.type || selectedOption.value?.status || '').toUpperCase();
    return { DIRECT: 'Доступно', HLS: 'Доступно', EMBED: 'Встроенный плеер', EXTERNAL: 'Внешний сервис', TRAILER: 'Только трейлер', UNAVAILABLE: 'Недоступно' }[status] || 'Проверяем';
});

async function loadWatch() {
    try {
        const response = await api.watchOptions(titleId.value, { country: session.country, episode_id: selectedEpisode.value?.id || undefined });
        watchOptions.value = listFrom(response.data?.options || response.data);
        selectedOption.value = response.data?.current || watchOptions.value[0] || (response.data?.mode ? response.data : null);
    } catch (exception) {
        watchOptions.value = [];
        selectedOption.value = { type: 'UNAVAILABLE', message: exception.userMessage };
    }
}

async function load() {
    loading.value = true;
    error.value = '';
    try {
        const response = await api.title(titleId.value);
        title.value = response.data?.title || response.data;
        document.title = `${titleName(title.value)} — КиноДом`;
        if (title.value?.type === 'series') {
            const seasonsResponse = await api.seasons(titleId.value);
            seasons.value = listFrom(seasonsResponse.data?.seasons || seasonsResponse.data);
            selectedSeason.value = seasons.value[0] || null;
            selectedEpisode.value = selectedSeason.value?.episodes?.[0] || null;
        }
        if (session.authenticated) await session.loadFavorites();
        await loadWatch();
    } catch (exception) {
        error.value = exception.userMessage;
    } finally {
        loading.value = false;
    }
}

function chooseSeason(id) {
    selectedSeason.value = seasons.value.find((item) => String(item.id) === String(id)) || seasons.value[0];
    selectedEpisode.value = selectedSeason.value?.episodes?.[0] || null;
}
async function toggleFavorite() {
    favoriteBusy.value = true;
    try { await session.toggleFavorite(titleId.value); } finally { favoriteBusy.value = false; }
}
watch(selectedEpisode, loadWatch);
watch(() => session.country, loadWatch);
onMounted(load);
</script>

<template>
    <div>
        <StatePanel v-if="loading || error" :loading="loading" :error="error" @retry="load" />
        <template v-else-if="title">
            <section class="detail-hero" :style="backdropOf(title) ? { backgroundImage: `linear-gradient(90deg, #07080c 3%, rgba(7,8,12,.82) 52%, rgba(7,8,12,.25)), linear-gradient(0deg, #07080c, transparent 40%), url('${backdropOf(title)}')` } : {}">
                <div class="shell detail-layout">
                    <div class="detail-poster">
                        <img v-if="posterOf(title)" :src="posterOf(title)" :alt="`Постер: ${titleName(title)}`" />
                        <div v-else class="poster-placeholder"><span>КД</span></div>
                    </div>
                    <div class="detail-copy">
                        <span class="eyebrow">{{ title.type === 'series' ? 'Сериал' : 'Фильм' }}</span>
                        <h1>{{ titleName(title) }}</h1>
                        <p v-if="title.original_title || title.original_name" class="original-title">{{ title.original_title || title.original_name }}</p>
                        <div class="hero-meta"><span class="rating-inline">★ {{ ratingOf(title) }} {{ title.rating_source || 'TMDB' }}</span><span>{{ yearOf(title) }}</span><span v-if="durationLabel(title.runtime_minutes || title.duration || title.runtime)">{{ durationLabel(title.runtime_minutes || title.duration || title.runtime) }}</span><span v-if="title.country_code">{{ title.country_code }}</span><span v-if="title.age_rating">{{ title.age_rating }}</span></div>
                        <div class="chips"><span v-for="genre in genres" :key="genre">{{ genre }}</span></div>
                        <p class="overview">{{ title.overview || title.description || 'Описание скоро появится.' }}</p>
                        <p v-if="title.actors?.length" class="actors">В ролях: {{ title.actors.map((actor) => actor.name).join(', ') }}</p>
                        <div class="button-row">
                            <a class="button button-primary focusable" href="#player"><span aria-hidden="true">▶</span> Смотреть</a>
                            <button class="button button-secondary focusable" :disabled="favoriteBusy" @click="toggleFavorite">
                                <span aria-hidden="true">{{ isFavorite ? '♥' : '♡' }}</span> {{ isFavorite ? 'В избранном' : 'В избранное' }}
                            </button>
                        </div>
                    </div>
                </div>
            </section>
            <div class="shell detail-content">
                <section v-if="seasons.length" class="episode-picker">
                    <div class="section-heading"><h2>Сезоны и серии</h2></div>
                    <label>Сезон
                        <select class="focusable" :value="selectedSeason?.id" @change="chooseSeason($event.target.value)">
                            <option v-for="season in seasons" :key="season.id" :value="season.id">{{ season.name || `Сезон ${season.number || season.season_number}` }}</option>
                        </select>
                    </label>
                    <div class="episode-list">
                        <button v-for="episode in episodes" :key="episode.id" class="episode focusable" :class="{ active: selectedEpisode?.id === episode.id }" @click="selectedEpisode = episode">
                            <span class="episode-number">{{ episode.number || episode.episode_number }}</span>
                            <span><strong>{{ episode.name || episode.title || `Серия ${episode.number}` }}</strong><small>{{ [durationLabel(episode.runtime_minutes || episode.duration), episode.description].filter(Boolean).join(' · ') }}</small></span>
                        </button>
                    </div>
                </section>
                <section id="player" class="watch-section">
                    <div class="section-heading">
                        <div><h2>Смотреть</h2><p class="muted">{{ selectedEpisode?.name || selectedEpisode?.title || 'Полная версия' }}</p></div>
                        <span class="availability">{{ statusLabel }} · {{ session.country }}</span>
                    </div>
                    <div v-if="watchOptions.length > 1" class="option-tabs" role="tablist">
                        <button v-for="(option, index) in watchOptions" :key="option.id || index" class="focusable" :class="{ active: selectedOption === option }" @click="selectedOption = option">{{ option.label || option.provider || `Источник ${index + 1}` }}</button>
                    </div>
                    <VideoPlayer :title-id="titleId" :episode-id="selectedEpisode?.id" :option="selectedOption" />
                </section>
            </div>
        </template>
    </div>
</template>
