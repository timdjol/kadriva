<script setup>
import { computed, reactive, ref, watch } from 'vue';
import { api } from '../services/api';
import { useSessionStore } from '../stores/session';

const session = useSessionStore();
const busy = ref(false);
const error = ref('');
const fields = ref({});
const form = reactive({ name: '', email: '', password: '', password_confirmation: '', country_code: session.country });
const title = computed(() => ({
    login: 'С возвращением',
    register: 'Создать профиль',
    forgot: 'Сброс пароля',
}[session.authMode] || 'Вход'));

watch(() => session.authOpen, (open) => {
    if (open) {
        error.value = '';
        fields.value = {};
        setTimeout(() => document.querySelector('.auth-dialog input')?.focus(), 0);
    }
});

async function submit() {
    busy.value = true;
    error.value = '';
    fields.value = {};
    try {
        if (session.authMode === 'forgot') {
            await api.forgotPassword(form.email);
            error.value = '';
            session.authMode = 'login';
            fields.value = { email: ['Если аккаунт существует, мы отправили письмо.'] };
            return;
        }
        await (session.authMode === 'login' ? session.login(form) : session.register(form));
    } catch (exception) {
        error.value = exception.userMessage;
        fields.value = exception.fields || {};
    } finally {
        busy.value = false;
    }
}
</script>

<template>
    <Teleport to="body">
        <div v-if="session.authOpen" class="modal-backdrop" role="presentation">
            <section class="auth-dialog" role="dialog" aria-modal="true" :aria-label="title" @keydown.esc="session.authOpen = false">
                <button class="modal-close focusable" aria-label="Закрыть" @click="session.authOpen = false">×</button>
                <div class="eyebrow">КиноДом</div>
                <h2>{{ title }}</h2>
                <p class="muted">{{ session.authMode === 'login' ? 'Войдите, чтобы сохранять фильмы и продолжать просмотр.' : (session.authMode === 'forgot' ? 'Укажите почту — отправим ссылку для нового пароля.' : 'Избранное и история просмотра будут доступны на всех устройствах.') }}</p>
                <form class="auth-form" @submit.prevent="submit">
                    <label v-if="session.authMode === 'register'">Имя
                        <input v-model="form.name" class="focusable" required autocomplete="name" />
                        <small v-if="fields.name">{{ fields.name[0] }}</small>
                    </label>
                    <label>Электронная почта
                        <input v-model="form.email" class="focusable" required type="email" autocomplete="email" />
                        <small v-if="fields.email">{{ fields.email[0] }}</small>
                    </label>
                    <label v-if="session.authMode !== 'forgot'">Пароль
                        <input v-model="form.password" class="focusable" required type="password" minlength="8" :autocomplete="session.authMode === 'login' ? 'current-password' : 'new-password'" />
                        <small v-if="fields.password">{{ fields.password[0] }}</small>
                    </label>
                    <label v-if="session.authMode === 'register'">Повторите пароль
                        <input v-model="form.password_confirmation" class="focusable" required type="password" minlength="8" autocomplete="new-password" />
                    </label>
                    <p v-if="error" class="form-error" role="alert">{{ error }}</p>
                    <button class="button button-primary focusable" :disabled="busy">{{ busy ? 'Подождите…' : (session.authMode === 'login' ? 'Войти' : (session.authMode === 'forgot' ? 'Отправить ссылку' : 'Зарегистрироваться')) }}</button>
                </form>
                <button v-if="session.authMode === 'login'" class="auth-switch focusable" @click="session.authMode = 'forgot'">Забыли пароль?</button>
                <button class="auth-switch focusable" @click="session.authMode = session.authMode === 'register' ? 'login' : 'register'">
                    {{ session.authMode === 'register' ? 'Уже есть профиль? Войти' : 'Нет профиля? Зарегистрироваться' }}
                </button>
            </section>
        </div>
    </Teleport>
</template>
