<script setup>
import { reactive, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { api } from '../services/api';
import { useSessionStore } from '../stores/session';

const route = useRoute();
const router = useRouter();
const session = useSessionStore();
const busy = ref(false);
const error = ref('');
const form = reactive({
    token: route.params.token,
    email: route.query.email || '',
    password: '',
    password_confirmation: '',
});

async function submit() {
    busy.value = true;
    error.value = '';
    try {
        await api.resetPassword(form);
        session.requestAuth('login');
        router.push('/');
    } catch (exception) {
        error.value = exception.userMessage;
    } finally {
        busy.value = false;
    }
}
</script>

<template>
    <div class="shell page">
        <header class="page-heading">
            <div>
                <span class="eyebrow">Аккаунт</span>
                <h1>Новый пароль</h1>
                <p>После сохранения вы сможете войти с новым паролем.</p>
            </div>
        </header>
        <form class="auth-form reset-form" @submit.prevent="submit">
            <label>Электронная почта
                <input v-model="form.email" class="focusable" required type="email" autocomplete="email" />
            </label>
            <label>Новый пароль
                <input v-model="form.password" class="focusable" required type="password" minlength="8" autocomplete="new-password" />
            </label>
            <label>Повторите пароль
                <input v-model="form.password_confirmation" class="focusable" required type="password" minlength="8" autocomplete="new-password" />
            </label>
            <p v-if="error" class="form-error" role="alert">{{ error }}</p>
            <button class="button button-primary focusable" :disabled="busy">{{ busy ? 'Сохраняем…' : 'Обновить пароль' }}</button>
        </form>
    </div>
</template>
