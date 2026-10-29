<script setup>
import { computed, nextTick, onBeforeUnmount, ref, watch } from 'vue';
import Hls from 'hls.js/dist/hls.light.min.js';
import { api } from '../services/api';
import { useSessionStore } from '../stores/session';

const props = defineProps({
    titleId: { type: [Number, String], required: true },
    option: { type: Object, default: null },
    episodeId: { type: [Number, String], default: null },
});

const session = useSessionStore();
const video = ref(null);
const error = ref('');
let hls;
let lastSavedAt = 0;
const kind = computed(() => String(props.option?.mode || props.option?.type || props.option?.status || props.option?.kind || '').toUpperCase());
const source = computed(() => props.option?.url || props.option?.src || props.option?.source_url || '');
const canPlayDirect = computed(() => ['DIRECT', 'HLS'].includes(kind.value) && source.value);

async function save(force = false) {
    if (!session.authenticated || !video.value?.duration) return;
    const now = Math.floor(video.value.currentTime);
    if (!force && now - lastSavedAt < 20) return;
    lastSavedAt = now;
    try {
        await api.saveProgress(props.titleId, {
            episode_id: props.episodeId ? Number(props.episodeId) : null,
            position_seconds: now,
            duration_seconds: Math.max(1, Math.floor(video.value.duration)),
        });
    } catch {
        // Playback must not be interrupted by a background progress request.
    }
}

async function restore() {
    if (!session.authenticated || !video.value) return;
    try {
        const response = await api.progress(props.titleId, props.episodeId);
        const progress = Array.isArray(response.data) ? response.data[0] : response.data;
        const position = Number(progress?.position_seconds || 0);
        if (position > 0 && position < Number(progress?.duration_seconds || Infinity) - 15) video.value.currentTime = position;
    } catch {
        // A missing progress record is normal.
    }
}

async function mountSource() {
    error.value = '';
    hls?.destroy();
    hls = null;
    await nextTick();
    if (!video.value || !canPlayDirect.value) return;
    const url = source.value;
    if (/\.m3u8($|\?)/i.test(url) && !video.value.canPlayType('application/vnd.apple.mpegurl')) {
        if (!Hls.isSupported()) {
            error.value = 'Этот браузер не поддерживает HLS-видео.';
            return;
        }
        hls = new Hls();
        hls.loadSource(url);
        hls.attachMedia(video.value);
        hls.on(Hls.Events.ERROR, (_, data) => {
            if (data.fatal) error.value = 'Не удалось воспроизвести поток. Выберите другой вариант.';
        });
    } else {
        video.value.src = url;
    }
}

function onVideoError() {
    if (canPlayDirect.value) error.value = 'Видео временно недоступно.';
}

watch(() => [props.option, props.episodeId], mountSource, { immediate: true, deep: true });
onBeforeUnmount(() => { hls?.destroy(); save(true); });
</script>

<template>
    <section class="player-shell" aria-label="Видеоплеер">
        <video v-if="canPlayDirect" ref="video" controls playsinline preload="metadata" :poster="option?.poster_url || ''"
            @loadedmetadata="restore" @timeupdate="save(false)" @pause="save(true)" @ended="save(true)" @error="onVideoError" />
        <iframe v-else-if="kind === 'EMBED' && source" :src="source" title="Встроенный видеоплеер" allow="autoplay; fullscreen; picture-in-picture" allowfullscreen></iframe>
        <div v-else class="player-message">
            <span class="player-icon" aria-hidden="true">▶</span>
            <h3>{{ kind === 'UNAVAILABLE' || !option ? 'Просмотр пока недоступен' : 'Доступен внешний источник' }}</h3>
            <p>{{ option?.message || 'Мы уже работаем над добавлением легального источника для вашей страны.' }}</p>
            <a v-if="source" class="button button-primary focusable" :href="source" target="_blank" rel="noopener">
                {{ kind === 'TRAILER' ? 'Смотреть трейлер' : 'Перейти к просмотру' }}
            </a>
        </div>
        <div v-if="error" class="player-error" role="alert"><strong>Ошибка воспроизведения</strong><span>{{ error }}</span></div>
    </section>
</template>
