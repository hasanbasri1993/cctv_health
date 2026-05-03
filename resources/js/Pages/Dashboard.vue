<script setup>
import { computed, onMounted, onUnmounted } from 'vue';
import { usePage, router } from '@inertiajs/vue3';
import { Head, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import DeviceCard from '@/Components/DeviceCard.vue';

const props = defineProps({
    devices: {
        type: Array,
        default: () => [],
    },
    stats: {
        type: Object,
        default: () => ({}),
    },
});

const page = usePage();
const user = computed(() => page.props.auth.user);

const totalDevices = computed(() => props.stats.total ?? props.devices.length);
const onlineCount = computed(() => props.stats.online ?? props.devices.filter(d => d.status === 'online').length);
const offlineCount = computed(() => props.stats.offline ?? props.devices.filter(d => d.status === 'offline').length);
const activeAlerts = computed(() => props.stats.active_alerts ?? props.devices.reduce((sum, d) => sum + (d.active_alerts_count ?? 0), 0));

let refreshTimer = null;

onMounted(() => {
    refreshTimer = setInterval(() => {
        router.reload({ only: ['devices', 'stats'] });
    }, 30000);
});

onUnmounted(() => {
    if (refreshTimer) clearInterval(refreshTimer);
});
</script>

<template>
    <Head title="Dashboard" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">
                    CCTV Monitor Dashboard
                </h2>
                <span class="text-xs text-gray-400">Auto-refreshes every 30s</span>
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">

                <!-- Greeting -->
                <p class="mb-6 text-sm text-gray-600">
                    Welcome back, <span class="font-medium">{{ user.name }}</span>.
                </p>

                <!-- Summary Stats -->
                <div class="mb-8 grid grid-cols-2 gap-4 sm:grid-cols-4">
                    <div class="rounded-lg bg-white p-5 shadow-sm">
                        <p class="text-xs font-medium uppercase tracking-wide text-gray-500">Total Devices</p>
                        <p class="mt-2 text-3xl font-bold text-gray-900">{{ totalDevices }}</p>
                    </div>
                    <div class="rounded-lg bg-white p-5 shadow-sm">
                        <p class="text-xs font-medium uppercase tracking-wide text-gray-500">Online</p>
                        <p class="mt-2 text-3xl font-bold text-green-600">{{ onlineCount }}</p>
                    </div>
                    <div class="rounded-lg bg-white p-5 shadow-sm">
                        <p class="text-xs font-medium uppercase tracking-wide text-gray-500">Offline</p>
                        <p class="mt-2 text-3xl font-bold text-red-600">{{ offlineCount }}</p>
                    </div>
                    <div class="rounded-lg bg-white p-5 shadow-sm">
                        <p class="text-xs font-medium uppercase tracking-wide text-gray-500">Active Alerts</p>
                        <p class="mt-2 text-3xl font-bold" :class="activeAlerts > 0 ? 'text-yellow-600' : 'text-gray-900'">
                            {{ activeAlerts }}
                        </p>
                    </div>
                </div>

                <!-- Devices Section -->
                <div class="mb-4 flex items-center justify-between">
                    <h3 class="text-base font-semibold text-gray-800">Devices</h3>
                    <Link
                        href="/devices"
                        class="text-sm font-medium text-indigo-600 hover:text-indigo-500"
                    >
                        View all &rarr;
                    </Link>
                </div>

                <div v-if="devices.length === 0" class="rounded-lg border-2 border-dashed border-gray-200 p-12 text-center">
                    <svg class="mx-auto h-12 w-12 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                    </svg>
                    <p class="mt-3 text-sm text-gray-500">No devices found.</p>
                    <Link href="/devices/create" class="mt-3 inline-block text-sm font-medium text-indigo-600 hover:text-indigo-500">
                        Add your first device &rarr;
                    </Link>
                </div>

                <div v-else class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                    <DeviceCard
                        v-for="device in devices"
                        :key="device.id"
                        :device="device"
                    />
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
