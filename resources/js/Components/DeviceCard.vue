<script setup>
import { Link } from '@inertiajs/vue3';
import StatusIndicator from '@/Components/StatusIndicator.vue';

defineProps({
    device: {
        type: Object,
        required: true,
    },
});

function formatLastSeen(dateStr) {
    if (!dateStr) return 'Never';
    const date = new Date(dateStr);
    const now = new Date();
    const diffMs = now - date;
    const diffMins = Math.floor(diffMs / 60000);
    if (diffMins < 1) return 'Just now';
    if (diffMins < 60) return `${diffMins}m ago`;
    const diffHours = Math.floor(diffMins / 60);
    if (diffHours < 24) return `${diffHours}h ago`;
    return `${Math.floor(diffHours / 24)}d ago`;
}
</script>

<template>
    <div class="overflow-hidden rounded-lg bg-white shadow hover:shadow-md transition-shadow duration-200">
        <div class="p-5">
            <!-- Header -->
            <div class="flex items-start justify-between gap-2">
                <div class="min-w-0">
                    <h3 class="truncate text-base font-semibold text-gray-900">
                        {{ device.name }}
                    </h3>
                    <p class="mt-0.5 text-sm text-gray-500 font-mono">{{ device.ip_address }}</p>
                </div>
                <StatusIndicator :status="device.status" />
            </div>

            <!-- Details -->
            <dl class="mt-4 space-y-2">
                <div v-if="device.model" class="flex justify-between text-sm">
                    <dt class="text-gray-500">Model</dt>
                    <dd class="font-medium text-gray-700 truncate max-w-[140px]">{{ device.model }}</dd>
                </div>
                <div class="flex justify-between text-sm">
                    <dt class="text-gray-500">Last Seen</dt>
                    <dd class="font-medium text-gray-700">{{ formatLastSeen(device.last_seen_at) }}</dd>
                </div>
                <div class="flex justify-between text-sm">
                    <dt class="text-gray-500">Channels</dt>
                    <dd class="font-medium text-gray-700">{{ device.channels_count ?? 0 }}</dd>
                </div>
            </dl>

            <!-- Alerts badge -->
            <div v-if="device.active_alerts_count > 0" class="mt-3">
                <span class="inline-flex items-center gap-1 rounded-full bg-red-100 px-2.5 py-0.5 text-xs font-medium text-red-700">
                    <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                    </svg>
                    {{ device.active_alerts_count }} active alert{{ device.active_alerts_count !== 1 ? 's' : '' }}
                </span>
            </div>
        </div>

        <!-- Footer link -->
        <div class="border-t border-gray-100 bg-gray-50 px-5 py-3">
            <Link
                :href="`/devices/${device.id}`"
                class="text-sm font-medium text-indigo-600 hover:text-indigo-500"
            >
                View details &rarr;
            </Link>
        </div>
    </div>
</template>
