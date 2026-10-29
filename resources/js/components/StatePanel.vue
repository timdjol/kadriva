<script setup>
defineProps({ loading: Boolean, error: { type: String, default: '' }, empty: Boolean, emptyText: { type: String, default: 'Ничего не найдено' } });
defineEmits(['retry']);
</script>

<template>
    <div v-if="loading" class="state-panel" role="status">
        <span class="spinner" aria-hidden="true"></span><strong>Загружаем кино…</strong>
    </div>
    <div v-else-if="error" class="state-panel state-error" role="alert">
        <strong>Что-то пошло не так</strong><p>{{ error }}</p>
        <button class="button button-secondary focusable" @click="$emit('retry')">Попробовать снова</button>
    </div>
    <div v-else-if="empty" class="state-panel">
        <span class="state-icon" aria-hidden="true">⌕</span><strong>{{ emptyText }}</strong>
        <p class="muted">Измените запрос или сбросьте фильтры.</p>
    </div>
</template>
