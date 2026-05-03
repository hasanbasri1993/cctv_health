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

const usedPercent = computed(() => {
    if (!props.storage.capacity || props.storage.capacity === 0) return 0;
    return Math.round((props.storage.used_space / props.storage.capacity) * 100);
});

const barColor = computed(() => {
    if (usedPercent.value >= 90) return 'bg-red-500';
    if (usedPercent.value >= 75) return 'bg-yellow-400';
    return 'bg-green-500';
});

const capacityGB = computed(() => bytesToGB(props.storage.capacity));
const usedGB = computed(() => bytesToGB(props.storage.used_space));
</script>

<template>
    <div class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
        <!-- Header -->
        <div class="flex items-start justify-between gap-2">
            <div class="min-w-0">
                <h4 class="truncate text-sm font-semibold text-gray-900">{{ storage.name }}</h4>
                <p v-if="storage.type" class="text-xs text-gray-500 uppercase tracking-wide mt-0.5">{{ storage.type }}</p>
            </div>
            <StatusIndicator :status="storage.health_status" />
        </div>

        <!-- Capacity bar -->
        <div v-if="capacityGB !== null" class="mt-4">
            <div class="mb-1 flex justify-between text-xs text-gray-500">
                <span>Storage Usage</span>
                <span>{{ usedPercent }}%</span>
            </div>
            <div class="h-2 w-full overflow-hidden rounded-full bg-gray-200">
                <div
                    :class="[barColor, 'h-2 rounded-full transition-all duration-300']"
                    :style="{ width: `${usedPercent}%` }"
                ></div>
            </div>
            <div class="mt-1.5 flex justify-between text-xs text-gray-500">
                <span>{{ usedGB ?? '—' }} GB used</span>
                <span>{{ capacityGB }} GB total</span>
            </div>
        </div>
        <div v-else class="mt-3 text-xs text-gray-400 italic">Capacity data unavailable</div>
    </div>
</template>
