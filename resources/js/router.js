import { createRouter, createWebHistory } from 'vue-router';

const router = createRouter({
    history: createWebHistory(),
    scrollBehavior: () => ({ top: 0 }),
    routes: [
        { path: '/', name: 'home', component: () => import('./views/HomeView.vue') },
        { path: '/catalog', name: 'catalog', component: () => import('./views/CatalogView.vue') },
        { path: '/search', name: 'search', component: () => import('./views/SearchView.vue') },
        { path: '/titles/:id', name: 'title', component: () => import('./views/TitleView.vue') },
        { path: '/movies/:slugId', name: 'movie', component: () => import('./views/TitleView.vue') },
        { path: '/series/:slugId', name: 'series', component: () => import('./views/TitleView.vue') },
        { path: '/account', name: 'account', component: () => import('./views/AccountView.vue') },
        { path: '/reset-password/:token', name: 'reset-password', component: () => import('./views/ResetPasswordView.vue') },
        { path: '/:pathMatch(.*)*', redirect: '/' },
    ],
});

router.afterEach((to) => {
    const labels = { home: 'Главная', catalog: 'Каталог', search: 'Поиск', title: 'Просмотр', movie: 'Фильм', series: 'Сериал', account: 'Мой профиль', 'reset-password': 'Сброс пароля' };
    document.title = `${labels[to.name] || 'Кино'} — Kadriva`;
});

export default router;
