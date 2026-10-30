<script setup>
import { ref } from 'vue';
import { posterOf, ratingOf, titleName, titlePath, yearOf } from '../utils/media';

defineProps({ item: { type: Object, required: true }, progress: { type: Number, default: 0 } });
const failed = ref(false);
</script>

<template>
    <RouterLink class="media-card focusable" :to="titlePath(item)" :aria-label="`${titleName(item)}, ${yearOf(item)}`">
        <div class="poster">
            <img v-if="posterOf(item) && !failed" :src="posterOf(item)" :alt="`Постер: ${titleName(item)}`" loading="lazy" @error="failed = true" />
            <div v-else class="poster-placeholder" aria-hidden="true"><span>K</span></div>
            <span v-if="ratingOf(item) !== '—'" class="rating">★ {{ ratingOf(item) }}</span>
            <div v-if="progress > 0" class="progress-track" aria-label="Прогресс просмотра">
                <span :style="{ width: `${Math.min(progress, 100)}%` }"></span>
            </div>
        </div>
        <h3>{{ titleName(item) }}</h3>
        <p>{{ yearOf(item) }} · {{ item.type === 'series' ? 'Сериал' : 'Фильм' }}</p>
    </RouterLink>
</template>
