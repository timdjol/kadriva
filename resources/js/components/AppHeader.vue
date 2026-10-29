<script setup>
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { api, listFrom } from '../services/api';
import { posterOf, ratingOf, titleName, titlePath, yearOf } from '../utils/media';
import { useSessionStore } from '../stores/session';

const session = useSessionStore();
const route = useRoute();
const router = useRouter();
const query = ref('');
const items = ref([]);
const loading = ref(false);
const error = ref('');
const open = ref(false);
const active = ref(-1);
const root = ref(null);
let timer;
let controller;

const showPanel = computed(() => open.value && query.value.trim().length >= 2);
const footerIndex = computed(() => items.value.length);

function close() {
    open.value = false;
    active.value = -1;
}

async function fetchResults() {
    const q = query.value.trim();
    if (q.length < 2) {
        items.value = [];
        loading.value = false;
        error.value = '';
        return;
    }
    controller?.abort();
    controller = new AbortController();
    loading.value = true;
    error.value = '';
    open.value = true;
    try {
        items.value = listFrom((await api.search(q, 1, controller.signal, 8)).data);
        active.value = items.value.length ? 0 : -1;
    } catch (exception) {
        if (exception.code !== 'ERR_CANCELED') {
            error.value = exception.userMessage;
            items.value = [];
        }
    } finally {
        loading.value = false;
    }
}

function onInput() {
    clearTimeout(timer);
    const q = query.value.trim();
    if (q.length < 2) {
        items.value = [];
        error.value = '';
        close();
        return;
    }
    open.value = true;
    loading.value = true;
    timer = setTimeout(fetchResults, 250);
}

function goSearch() {
    const q = query.value.trim();
    if (q.length < 2) return;
    close();
    router.push({ name: 'search', query: { q } });
}

function goTitle(item) {
    query.value = titleName(item);
    router.push(titlePath(item));
}

function onKeydown(event) {
    if (event.key === 'Escape') {
        if (showPanel.value) {
            event.preventDefault();
            event.stopPropagation();
            close();
        }
        return;
    }
    if (!showPanel.value) return;
    if (event.key === 'ArrowDown') {
        event.preventDefault();
        active.value = Math.min(active.value + 1, footerIndex.value);
        return;
    }
    if (event.key === 'ArrowUp') {
        event.preventDefault();
        active.value = Math.max(active.value - 1, 0);
        return;
    }
    if (event.key === 'Enter' && active.value >= 0 && active.value < items.value.length) {
        event.preventDefault();
        goTitle(items.value[active.value]);
    }
}

function onDocumentPointer(event) {
    if (!root.value?.contains(event.target)) close();
}

watch(() => route.fullPath, close);
onMounted(() => document.addEventListener('pointerdown', onDocumentPointer));
onBeforeUnmount(() => {
    document.removeEventListener('pointerdown', onDocumentPointer);
    clearTimeout(timer);
    controller?.abort();
});
</script>

<template>
    <header class="app-header">
        <div class="shell header-inner">
            <RouterLink class="brand focusable" to="/" aria-label="КиноДом — на главную">
                <span class="brand-mark" aria-hidden="true">▶</span><span>Кино<span>Дом</span></span>
            </RouterLink>
            <nav class="main-nav" aria-label="Основная навигация">
                <RouterLink class="focusable" to="/">Главная</RouterLink>
                <RouterLink class="focusable" to="/catalog">Каталог</RouterLink>
            </nav>
            <form ref="root" class="header-search" role="search" @submit.prevent="goSearch">
                <label class="sr-only" for="global-search">Поиск фильмов и сериалов</label>
                <input
                    id="global-search"
                    v-model="query"
                    class="focusable"
                    type="search"
                    minlength="2"
                    placeholder="Название, актёр…"
                    autocomplete="off"
                    aria-autocomplete="list"
                    aria-controls="search-suggest"
                    :aria-expanded="showPanel ? 'true' : 'false'"
                    @input="onInput"
                    @focus="query.trim().length >= 2 && (open = true)"
                    @keydown="onKeydown"
                />
                <button class="icon-button focusable" aria-label="Найти" type="submit">⌕</button>
                <div v-if="showPanel" id="search-suggest" class="search-suggest" role="listbox" aria-label="Подсказки поиска">
                    <p v-if="loading && !items.length" class="search-suggest-status">Ищем…</p>
                    <p v-else-if="error" class="search-suggest-status search-suggest-error" role="alert">{{ error }}</p>
                    <p v-else-if="!loading && !items.length" class="search-suggest-status">Ничего не найдено</p>
                    <RouterLink
                        v-for="(item, index) in items"
                        :id="`search-option-${item.id}`"
                        :key="item.id"
                        class="search-suggest-item focusable"
                        role="option"
                        :aria-selected="active === index"
                        :class="{ active: active === index }"
                        :to="titlePath(item)"
                        @mouseenter="active = index"
                        @click.prevent="goTitle(item)"
                    >
                        <span class="search-suggest-poster">
                            <img v-if="posterOf(item)" :src="posterOf(item)" :alt="`Постер: ${titleName(item)}`" />
                            <span v-else aria-hidden="true">КД</span>
                        </span>
                        <span class="search-suggest-copy">
                            <strong>{{ titleName(item) }}</strong>
                            <small>{{ yearOf(item) }} · {{ item.type === 'series' ? 'Сериал' : 'Фильм' }}</small>
                        </span>
                        <span v-if="ratingOf(item) !== '—'" class="search-suggest-rating">★ {{ ratingOf(item) }}</span>
                    </RouterLink>
                    <button
                        class="search-suggest-more focusable"
                        type="submit"
                        :class="{ active: active === footerIndex }"
                        @mouseenter="active = footerIndex"
                    >
                        Все результаты
                    </button>
                </div>
            </form>
            <label class="country-select">
                <span class="sr-only">Страна</span>
                <select class="focusable" :value="session.country" aria-label="Страна просмотра" @change="session.changeCountry($event.target.value)">
                    <option v-for="item in session.countries" :key="item.code || item.country_code" :value="item.code || item.country_code">
                        {{ item.name || item.title || item.code }}
                    </option>
                </select>
            </label>
            <RouterLink v-if="session.authenticated" class="profile-button focusable" to="/account" aria-label="Мой профиль">
                {{ session.user?.name?.slice(0, 1)?.toUpperCase() || 'Я' }}
            </RouterLink>
            <button v-else class="button button-ghost focusable header-login" @click="session.requestAuth()">Войти</button>
        </div>
    </header>
</template>
