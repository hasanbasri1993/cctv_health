<script setup>
import { ref } from 'vue';
import StatusIndicator from '@/Components/StatusIndicator.vue';

const props = defineProps({
    channels: { type: Array, required: true },
    device: { type: Object, default: null },
});

const copiedKey = ref(null);

function rtspUrl(channel, stream) {
    if (!props.device) return '';
    const ch = channel.channel_number * 100 + stream;
    return `rtsp://${props.device.username}:${props.device.password}@${props.device.ip_address}:554/Streaming/Channels/${ch}`;
}

async function copy(channel, stream) {
    const url = rtspUrl(channel, stream);
    const key = `${channel.id}-${stream}`;
    try {
        await navigator.clipboard.writeText(url);
    } catch {
        const ta = document.createElement('textarea');
        ta.value = url;
        document.body.appendChild(ta);
        ta.select();
        document.execCommand('copy');
        document.body.removeChild(ta);
    }
    copiedKey.value = key;
    setTimeout(() => { if (copiedKey.value === key) copiedKey.value = null; }, 2000);
}

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

                <!-- RTSP copy buttons -->
                <div v-if="device" class="mt-2.5 flex gap-1">
                    <button
                        type="button"
                        @click="copy(channel, 1)"
                        :title="rtspUrl(channel, 1)"
                        :class="copiedKey === `${channel.id}-1`
                            ? 'bg-emerald-500/20 text-emerald-400 border-emerald-500/30'
                            : 'bg-slate-700/60 text-slate-400 border-white/[0.06] hover:bg-slate-700 hover:text-slate-200'"
                        class="flex flex-1 items-center justify-center gap-1 rounded border px-1.5 py-1 text-[10px] font-medium transition-colors"
                    >
                        <svg class="h-3 w-3 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path v-if="copiedKey !== `${channel.id}-1`" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                            <path v-else stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                        Main
                    </button>
                    <button
                        type="button"
                        @click="copy(channel, 2)"
                        :title="rtspUrl(channel, 2)"
                        :class="copiedKey === `${channel.id}-2`
                            ? 'bg-emerald-500/20 text-emerald-400 border-emerald-500/30'
                            : 'bg-slate-700/60 text-slate-400 border-white/[0.06] hover:bg-slate-700 hover:text-slate-200'"
                        class="flex flex-1 items-center justify-center gap-1 rounded border px-1.5 py-1 text-[10px] font-medium transition-colors"
                    >
                        <svg class="h-3 w-3 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path v-if="copiedKey !== `${channel.id}-2`" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                            <path v-else stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                        Sub
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
