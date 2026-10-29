<script setup>
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import { useSessionStore } from '../stores/session';

const session = useSessionStore();
const router = useRouter();
const query = ref('');

function submitSearch() {
    const q = query.value.trim();
    if (q.length >= 2) router.push({ name: 'search', query: { q } });
}
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
            <form class="header-search" role="search" @submit.prevent="submitSearch">
                <label class="sr-only" for="global-search">Поиск фильмов и сериалов</label>
                <input id="global-search" v-model="query" class="focusable" type="search" minlength="2" placeholder="Название, актёр…" />
                <button class="icon-button focusable" aria-label="Найти" type="submit">⌕</button>
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
