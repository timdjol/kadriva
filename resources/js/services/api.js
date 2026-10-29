import axios from 'axios';

const http = axios.create({
    baseURL: '/api/v1',
    withCredentials: true,
    headers: { Accept: 'application/json' },
});

http.interceptors.request.use((config) => {
    const token = document.querySelector('meta[name="csrf-token"]')?.content;
    if (token) config.headers['X-CSRF-TOKEN'] = token;
    return config;
});

function messageFrom(error) {
    const errors = error.response?.data?.errors;
    if (Array.isArray(errors) && errors.length) return errors.map((item) => item.message).join(' ');
    return error.response?.data?.message || 'Не удалось выполнить запрос. Попробуйте ещё раз.';
}

async function request(method, url, options = {}) {
    try {
        const response = await http.request({ method, url, ...options });
        const envelope = response.data || {};
        return { data: envelope.data, meta: envelope.meta || {}, errors: envelope.errors || [] };
    } catch (error) {
        error.userMessage = messageFrom(error);
        error.fields = error.response?.data?.errors?.find((item) => item.fields)?.fields || {};
        throw error;
    }
}

export const api = {
    home: () => request('get', '/home'),
    titles: (params) => request('get', '/titles', { params }),
    title: (id) => request('get', `/titles/${id}`),
    seasons: (id) => request('get', `/titles/${id}/seasons`),
    watchOptions: (id, params) => request('get', `/titles/${id}/watch-options`, { params }),
    search: (q, page = 1, signal, limit) => request('get', '/search', { params: { q, page, limit }, signal }),
    countries: () => request('get', '/countries'),
    detectCountry: () => request('get', '/countries/detect'),
    genres: () => request('get', '/genres'),
    me: () => request('get', '/auth/me'),
    register: (payload) => request('post', '/auth/register', { data: payload }),
    login: (payload) => request('post', '/auth/login', { data: payload }),
    forgotPassword: (email) => request('post', '/auth/forgot-password', { data: { email } }),
    resetPassword: (payload) => request('post', '/auth/reset-password', { data: payload }),
    logout: () => request('post', '/auth/logout'),
    favorites: () => request('get', '/me/favorites'),
    addFavorite: (titleId) => request('post', '/me/favorites', { data: { title_id: titleId } }),
    removeFavorite: (titleId) => request('delete', '/me/favorites', { params: { title_id: titleId } }),
    progress: (titleId, episodeId) => request('get', `/me/progress/${titleId}`, { params: { episode_id: episodeId || undefined } }),
    saveProgress: (titleId, payload) => request('put', `/me/progress/${titleId}`, { data: payload }),
    history: () => request('get', '/me/history'),
    settings: (countryCode) => request('put', '/me/settings', { data: { country_code: countryCode } }),
};

export function listFrom(data) {
    if (Array.isArray(data)) return data;
    return data?.items || data?.titles || data?.results || data?.data || [];
}
