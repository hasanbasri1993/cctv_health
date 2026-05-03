<script setup>
import StatusIndicator from '@/Components/StatusIndicator.vue';

defineProps({
    channels: {
        type: Array,
        required: true,
    },
});

function formatDate(dateStr) {
    if (!dateStr) return null;
    return new Date(dateStr).toLocaleString();
}
</script>

<template>
    <div>
        <div v-if="channels.length === 0" class="rounded-lg border-2 border-dashed border-gray-200 p-8 text-center">
            <p class="text-sm text-gray-500">No channels configured for this device.</p>
        </div>
        <div v-else class="grid grid-cols-2 gap-3 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6">
            <div
                v-for="channel in channels"
                :key="channel.id"
                class="rounded-lg border border-gray-200 bg-white p-3 shadow-sm"
            >
                <div class="mb-2 flex items-center justify-between">
                    <span class="text-xs font-bold text-gray-400">CH {{ channel.channel_number }}</span>
                </div>
                <p class="mb-2 truncate text-sm font-medium text-gray-800" :title="channel.name">
                    {{ channel.name || `Channel ${channel.channel_number}` }}
                </p>
                <StatusIndicator :status="channel.status" />
                <p
                    v-if="channel.last_status_change"
                    class="mt-1.5 text-xs text-gray-400"
                    :title="formatDate(channel.last_status_change)"
                >
                    Changed {{ new Date(channel.last_status_change).toLocaleDateString() }}
                </p>
            </div>
        </div>
    </div>
</template>
