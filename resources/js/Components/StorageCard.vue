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
const freeGB = computed(() => bytesToGB(props.storage.capacity - props.storage.used_space));
</script>

<template>
    <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
        <div class="flex items-start justify-between gap-2">
            <h4 class="truncate text-sm font-semibold text-gray-900">{{ storage.name }}</h4>
            <StatusIndicator :status="storage.health_status" />
        </div>
        <div v-if="capacityGB !== null" class="mt-3 flex gap-4 text-sm text-gray-600">
            <span><span class="font-medium">{{ capacityGB }}</span> GB total</span>
            <span><span class="font-medium">{{ freeGB ?? '—' }}</span> GB free</span>
        </div>
        <div v-else class="mt-2 text-xs text-gray-400 italic">Capacity data unavailable</div>
    </div>
</template>
