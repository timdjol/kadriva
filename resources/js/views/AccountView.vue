<script setup>
import { onMounted, ref, watch } from 'vue';
import { useRouter } from 'vue-router';
import { api, listFrom } from '../services/api';
import { useSessionStore } from '../stores/session';
import MediaCard from '../components/MediaCard.vue';
import StatePanel from '../components/StatePanel.vue';

const session = useSessionStore();
const router = useRouter();
const favorites = ref([]);
const continuing = ref([]);
const history = ref([]);
const loading = ref(false);
const error = ref('');

async function load() {
    if (!session.authenticated) return;
    loading.value = true;
    error.value = '';
    try {
        const [favoriteResponse, homeResponse, historyResponse] = await Promise.all([api.favorites(), api.home(), api.history()]);
        favorites.value = listFrom(favoriteResponse.data).map((item) => item.title || item);
        const home = homeResponse.data || {};
        continuing.value = listFrom(home.continue_watching || home.continue || []);
        history.value = listFrom(historyResponse.data);
        await session.loadFavorites();
    } catch (exception) {
        error.value = exception.userMessage;
    } finally {
        loading.value = false;
    }
}

async function logout() {
    await session.logout();
    router.push('/');
}
onMounted(load);
watch(() => session.authenticated, (authenticated) => {
    if (authenticated) load();
});
</script>

<template>
    <div class="shell page account-page">
        <template v-if="!session.authenticated">
            <section class="account-gate">
                <span class="account-gate-icon" aria-hidden="true">♡</span>
                <h1>Ваше кино — всегда рядом</h1>
                <p>Войдите, чтобы сохранять избранное и продолжать просмотр с нужного момента.</p>
                <div class="button-row">
                    <button class="button button-primary focusable" @click="session.requestAuth('login')">Войти</button>
                    <button class="button button-secondary focusable" @click="session.requestAuth('register')">Создать профиль</button>
                </div>
            </section>
        </template>
        <template v-else>
            <header class="account-header">
                <div class="profile-button profile-large">{{ session.user?.name?.slice(0, 1)?.toUpperCase() }}</div>
                <div><span class="eyebrow">Мой профиль</span><h1>{{ session.user?.name }}</h1><p class="muted">{{ session.user?.email }}</p></div>
                <button class="button button-ghost focusable" @click="logout">Выйти</button>
            </header>
            <StatePanel :loading="loading" :error="error" @retry="load" />
            <section v-if="!loading && continuing.length" class="account-section">
                <div class="section-heading"><h2>Продолжить просмотр</h2></div>
                <div class="media-grid">
                    <MediaCard v-for="item in continuing" :key="item.id || item.title?.id" :item="item.title || item" :progress="item.progress_percent || (item.position_seconds / item.duration_seconds * 100)" />
                </div>
            </section>
            <section v-if="!loading" class="account-section">
                <div class="section-heading"><h2>Избранное</h2><span class="muted">{{ favorites.length }}</span></div>
                <div v-if="favorites.length" class="media-grid"><MediaCard v-for="item in favorites" :key="item.id" :item="item" /></div>
                <StatePanel v-else empty empty-text="В избранном пока пусто" />
            </section>
            <section v-if="!loading && history.length" class="account-section">
                <div class="section-heading"><h2>История просмотров</h2></div>
                <div class="media-grid">
                    <MediaCard v-for="item in history" :key="item.title?.id || item.id" :item="item.title || item" :progress="Number(item.progress_percent || 0)" />
                </div>
            </section>
        </template>
    </div>
</template>
