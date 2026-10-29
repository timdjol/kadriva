<script setup>
import { onMounted, onUnmounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import AppHeader from './components/AppHeader.vue';
import AuthModal from './components/AuthModal.vue';
import { useSessionStore } from './stores/session';

const session = useSessionStore();
const route = useRoute();
const router = useRouter();

function spatialKeydown(event) {
    if (event.key === 'Escape') {
        if (session.authOpen) session.authOpen = false;
        else if (route.name !== 'home') router.back();
        return;
    }
    if (!['ArrowLeft', 'ArrowRight', 'ArrowUp', 'ArrowDown'].includes(event.key)) return;
    const active = document.activeElement;
    if (!active || ['INPUT', 'SELECT', 'TEXTAREA', 'VIDEO'].includes(active.tagName)) return;
    const elements = [...document.querySelectorAll('.focusable:not([disabled])')].filter((node) => node.offsetParent !== null);
    const current = active.getBoundingClientRect();
    const direction = event.key.replace('Arrow', '').toLowerCase();
    const candidates = elements.filter((node) => {
        if (node === active) return false;
        const rect = node.getBoundingClientRect();
        if (direction === 'left') return rect.right <= current.left + 10;
        if (direction === 'right') return rect.left >= current.right - 10;
        if (direction === 'up') return rect.bottom <= current.top + 10;
        return rect.top >= current.bottom - 10;
    });
    const target = candidates.sort((a, b) => {
        const ar = a.getBoundingClientRect(), br = b.getBoundingClientRect();
        const distance = (rect) => Math.hypot(rect.left + rect.width / 2 - current.left - current.width / 2, rect.top + rect.height / 2 - current.top - current.height / 2);
        return distance(ar) - distance(br);
    })[0];
    if (target) {
        event.preventDefault();
        target.focus();
    }
}

onMounted(() => {
    session.loadCountries();
    session.hydrate();
    window.addEventListener('keydown', spatialKeydown);
});
onUnmounted(() => window.removeEventListener('keydown', spatialKeydown));
</script>

<template>
    <a class="skip-link" href="#main-content">К содержимому</a>
    <AppHeader />
    <main id="main-content">
        <RouterView />
    </main>
    <footer class="site-footer">
        <div class="shell"><span class="brand-small">КиноДом</span><span>Кино, которое хочется смотреть</span><span>© {{ new Date().getFullYear() }}</span></div>
    </footer>
    <div v-if="session.countryPrompt" class="notice-banner country-banner" role="dialog" aria-label="Подтверждение страны">
        <p>Похоже, вы в стране <strong>{{ session.countryPrompt.code }}</strong>. Подтвердите, чтобы показать доступные источники.</p>
        <div class="button-row">
            <button class="button button-primary focusable" @click="session.confirmCountry(session.countryPrompt.code)">Это верно</button>
            <select class="focusable" :value="session.country" aria-label="Выбрать другую страну" @change="session.confirmCountry($event.target.value)">
                <option v-for="item in session.countries" :key="item.code" :value="item.code">{{ item.name }}</option>
            </select>
        </div>
    </div>
    <div v-if="!session.cookieAccepted" class="notice-banner cookie-banner" role="dialog" aria-label="Уведомление о cookie">
        <p>Мы используем необходимые cookie для входа и сохранения страны просмотра. Рекламные трекеры не подключаются.</p>
        <button class="button button-primary focusable" @click="session.acceptCookies()">Понятно</button>
    </div>
    <AuthModal />
</template>
