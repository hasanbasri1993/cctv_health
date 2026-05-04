<script setup>
import { computed } from 'vue';
import StatusIndicator from '@/Components/StatusIndicator.vue';

const props = defineProps({
    storage: {
        type: Object,
        required: true,
    },
});

function bytesToGB(bytes) {
    if (!bytes && bytes !== 0) return null;
    return (bytes / 1024 / 1024 / 1024).toFixed(1);
}

const capacityGB = computed(() => bytesToGB(props.storage.capacity));
const usedGB = computed(() => bytesToGB(props.storage.used_space));
const freeGB = computed(() => bytesToGB(props.storage.capacity - props.storage.used_space));

const freePercent = computed(() => {
    if (!props.storage.capacity || props.storage.capacity === 0) return 100;
    return Math.round(((props.storage.capacity - props.storage.used_space) / props.storage.capacity) * 100);
});

const usedPercent = computed(() => 100 - freePercent.value);

const barColor = computed(() => {
    if (freePercent.value <= 10) return 'bg-red-500';
    if (freePercent.value <= 20) return 'bg-yellow-400';
    return 'bg-green-500';
});

const cardBorderClass = computed(() => {
    if (freePercent.value <= 10) return 'border-red-400';
    if (freePercent.value <= 20) return 'border-yellow-400';
    return 'border-gray-200';
});

const freeLabelClass = computed(() => {
    if (freePercent.value <= 10) return 'text-red-600 font-semibold';
    if (freePercent.value <= 20) return 'text-yellow-600 font-semibold';
    return 'text-gray-700';
});
</script>

<template>
    <div :class="['rounded-lg border-2 bg-white p-4 shadow-sm transition-colors', cardBorderClass]">
        <!-- Header -->
        <div class="flex items-start justify-between gap-2">
            <h4 class="truncate text-sm font-semibold text-gray-900">{{ storage.name }}</h4>
            <StatusIndicator :status="storage.health_status" />
        </div>

        <!-- Temperature -->
        <div v-if="storage.temperature !== null && storage.temperature !== undefined" class="mt-2 flex items-center gap-1 text-xs text-gray-500">
            <svg class="h-3.5 w-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19V6a3 3 0 016 0v13a5 5 0 11-6 0z" />
            </svg>
            <span :class="storage.temperature >= 55 ? 'text-red-500 font-semibold' : storage.temperature >= 45 ? 'text-yellow-500 font-semibold' : ''">
                {{ storage.temperature }}°C
            </span>
        </div>

        <!-- Usage bar -->
        <div v-if="capacityGB !== null" class="mt-3">
            <div class="h-1.5 w-full overflow-hidden rounded-full bg-gray-100">
                <div
                    :class="[barColor, 'h-1.5 rounded-full transition-all duration-300']"
                    :style="{ width: `${usedPercent}%` }"
                ></div>
            </div>
            <div class="mt-2 grid grid-cols-2 gap-x-2 text-xs">
                <div>
                    <span class="text-gray-400">Total</span>
                    <p class="font-medium text-gray-700">{{ capacityGB }} GB</p>
                </div>
                <div>
                    <span class="text-gray-400">Free</span>
                    <p :class="['font-medium', freeLabelClass]">{{ freeGB ?? '—' }} GB</p>
                </div>
            </div>
        </div>
        <div v-else class="mt-2 text-xs text-gray-400 italic">Capacity data unavailable</div>
    </div>
</template>
