<script setup>
import MediaCard from './MediaCard.vue';

defineProps({ title: { type: String, required: true }, items: { type: Array, default: () => [] }, allLink: { type: String, default: '/catalog' } });
</script>

<template>
    <section v-if="items.length" class="media-section">
        <div class="section-heading">
            <h2>{{ title }}</h2>
            <RouterLink class="text-link focusable" :to="allLink">Смотреть все <span aria-hidden="true">→</span></RouterLink>
        </div>
        <div class="media-row" tabindex="0" :aria-label="title">
            <MediaCard v-for="item in items" :key="item.id" :item="item" :progress="Number(item.progress_percent || 0)" />
        </div>
    </section>
</template>
