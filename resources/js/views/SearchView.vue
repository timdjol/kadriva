<script setup>
import { onBeforeUnmount, ref, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { api, listFrom } from '../services/api';
import MediaCard from '../components/MediaCard.vue';
import StatePanel from '../components/StatePanel.vue';

const route = useRoute();
const router = useRouter();
const query = ref(String(route.query.q || ''));
const items = ref([]);
const loading = ref(false);
const error = ref('');
let timer;
let controller;

async function search() {
    const q = query.value.trim();
    if (q.length < 2) {
        items.value = [];
        loading.value = false;
        return;
    }
    controller?.abort();
    controller = new AbortController();
    loading.value = true;
    error.value = '';
    router.replace({ query: { q } }).catch(() => {});
    try {
        items.value = listFrom((await api.search(q, 1, controller.signal)).data);
    } catch (exception) {
        if (exception.code !== 'ERR_CANCELED') error.value = exception.userMessage;
    } finally {
        loading.value = false;
    }
}

watch(query, () => {
    clearTimeout(timer);
    timer = setTimeout(search, 400);
}, { immediate: true });
watch(() => route.query.q, (value) => { if (String(value || '') !== query.value) query.value = String(value || ''); });
onBeforeUnmount(() => { clearTimeout(timer); controller?.abort(); });
</script>

<template>
    <div class="shell page search-page">
        <header class="page-heading"><div><span class="eyebrow">Поиск</span><h1>Что будем смотреть?</h1></div></header>
        <label class="search-large">
            <span aria-hidden="true">⌕</span>
            <input v-model="query" class="focusable" type="search" minlength="2" autofocus placeholder="Введите название фильма или сериала" aria-label="Поисковый запрос" />
        </label>
        <p v-if="query.length && query.length < 2" class="search-hint">Введите минимум 2 символа</p>
        <StatePanel :loading="loading" :error="error" :empty="query.length >= 2 && !loading && !error && !items.length" empty-text="По вашему запросу ничего не найдено" @retry="search" />
        <div v-if="!loading && items.length" class="search-results">
            <p class="muted">Результаты по запросу «{{ query }}»</p>
            <div class="media-grid"><MediaCard v-for="item in items" :key="item.id" :item="item" /></div>
        </div>
    </div>
</template>
