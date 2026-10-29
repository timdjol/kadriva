import { computed, ref } from 'vue';
import { defineStore } from 'pinia';
import { api, listFrom } from '../services/api';

export const useSessionStore = defineStore('session', () => {
    const user = ref(null);
    const country = ref(localStorage.getItem('cinema-country') || 'KG');
    const countries = ref([]);
    const favoriteIds = ref(new Set());
    const authOpen = ref(false);
    const authMode = ref('login');
    const countryPrompt = ref(null);
    const cookieAccepted = ref(localStorage.getItem('cinema-cookie-consent') === '1');

    const authenticated = computed(() => Boolean(user.value));

    function setUser(value) {
        user.value = value?.user || value || null;
        const code = user.value?.country_code;
        if (code) setCountryLocal(code);
    }

    function setCountryLocal(code) {
        country.value = code;
        localStorage.setItem('cinema-country', code);
    }

    async function loadCountries() {
        try {
            const response = await api.countries();
            countries.value = listFrom(response.data);
        } catch {
            countries.value = [{ code: 'KG', name: 'Кыргызстан' }, { code: 'KZ', name: 'Казахстан' }, { code: 'RU', name: 'Россия' }];
        }
        if (!localStorage.getItem('cinema-country-confirmed')) {
            try {
                const detected = (await api.detectCountry()).data;
                countryPrompt.value = detected;
            } catch {
                countryPrompt.value = { code: country.value, source: 'default' };
            }
        }
    }

    function confirmCountry(code) {
        setCountryLocal(code);
        localStorage.setItem('cinema-country-confirmed', '1');
        countryPrompt.value = null;
        if (authenticated.value) api.settings(code);
    }

    function acceptCookies() {
        cookieAccepted.value = true;
        localStorage.setItem('cinema-cookie-consent', '1');
    }

    async function hydrate() {
        try {
            setUser((await api.me()).data);
            await loadFavorites();
        } catch {
            user.value = null;
        }
    }

    async function loadFavorites() {
        if (!authenticated.value) return;
        const response = await api.favorites();
        favoriteIds.value = new Set(listFrom(response.data).map((item) => Number(item.title_id || item.title?.id || item.id)));
    }

    async function login(payload) {
        const response = await api.login(payload);
        setUser(response.data);
        await loadFavorites();
        authOpen.value = false;
    }

    async function register(payload) {
        const response = await api.register(payload);
        setUser(response.data);
        await loadFavorites();
        authOpen.value = false;
    }

    async function logout() {
        await api.logout();
        user.value = null;
        favoriteIds.value = new Set();
    }

    function requestAuth(mode = 'login') {
        authMode.value = mode;
        authOpen.value = true;
    }

    async function toggleFavorite(titleId) {
        if (!authenticated.value) {
            requestAuth();
            return false;
        }
        const next = new Set(favoriteIds.value);
        if (next.has(Number(titleId))) {
            await api.removeFavorite(titleId);
            next.delete(Number(titleId));
        } else {
            await api.addFavorite(titleId);
            next.add(Number(titleId));
        }
        favoriteIds.value = next;
        return true;
    }

    async function changeCountry(code) {
        setCountryLocal(code);
        if (authenticated.value) await api.settings(code);
    }

    return { user, country, countries, favoriteIds, authOpen, authMode, countryPrompt, cookieAccepted, authenticated, setUser, hydrate, loadCountries, confirmCountry, acceptCookies, loadFavorites, login, register, logout, requestAuth, toggleFavorite, changeCountry };
});
