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
        <div v-if="channels.length === 0" class="rounded-lg border border-dashed border-white/[0.08] p-8 text-center">
            <p class="text-[13px] text-slate-500">No channels configured for this device.</p>
        </div>
        <div v-else class="grid grid-cols-2 gap-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6">
            <div
                v-for="channel in channels"
                :key="channel.id"
                class="rounded-lg border border-white/[0.06] bg-slate-800/50 p-3"
            >
                <div class="mb-1.5 flex items-center justify-between">
                    <span class="text-[10px] font-bold uppercase tracking-wider text-slate-500">CH {{ channel.channel_number }}</span>
                </div>
                <p class="mb-2 truncate text-[13px] font-medium text-slate-200" :title="channel.name">
                    {{ channel.name || `Channel ${channel.channel_number}` }}
                </p>
                <StatusIndicator :status="channel.status" />
                <p
                    v-if="channel.last_status_change"
                    class="mt-1.5 text-[11px] tabular-nums text-slate-500"
                    :title="formatDate(channel.last_status_change)"
                >
                    {{ new Date(channel.last_status_change).toLocaleDateString() }}
                </p>
            </div>
        </div>
    </div>
</template>
