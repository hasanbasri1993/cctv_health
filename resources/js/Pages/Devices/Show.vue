<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import StatusIndicator from '@/Components/StatusIndicator.vue';
import ChannelGrid from '@/Components/ChannelGrid.vue';
import StorageCard from '@/Components/StorageCard.vue';
import { Chart, LineController, LineElement, PointElement, LinearScale, TimeSeriesScale, CategoryScale, Tooltip, Filler } from 'chart.js';

Chart.register(LineController, LineElement, PointElement, LinearScale, TimeSeriesScale, CategoryScale, Tooltip, Filler);

const props = defineProps({
    device: { type: Object, required: true },
    channels: { type: Array, default: () => [] },
    storages: { type: Array, default: () => [] },
    alerts: { type: Array, default: () => [] },
    healthLogs: { type: Object, default: () => ({ data: [], links: [], last_page: 1, from: null, to: null, total: 0 }) },
    tempStats: { type: Object, default: null },
});

const testingConnection = ref(false);
const testResult = ref(null);
const chartCanvas = ref(null);
let chartInstance = null;

async function testConnection() {
    testingConnection.value = true;
    testResult.value = null;
    try {
        const response = await fetch(`/devices/${props.device.id}/test`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
                'Accept': 'application/json',
            },
        });
        const data = await response.json();
        testResult.value = {
            success: data.success,
            message: data.success
                ? `Connected in ${data.response_time_ms}ms${data.device_info ? ' — ' + data.device_info : ''}`
                : (data.error ?? 'Connection failed'),
        };
    } catch (e) {
        testResult.value = { success: false, message: 'Request failed' };
    } finally {
        testingConnection.value = false;
    }
}

async function loadChart() {
    if (!chartCanvas.value) return;
    try {
        const res = await fetch(`/devices/${props.device.id}/health-history`, {
            headers: { 'Accept': 'application/json' },
        });
        const logs = await res.json();
        if (!logs.length) return;

        const labels = logs.map(l => new Date(l.created_at).toLocaleTimeString());
        const responseData = logs.map(l => l.response_time_ms ?? null);
        const tempData = logs.map(l => l.temperature ?? null);
        const hasTemp = tempData.some(v => v !== null);

        if (chartInstance) chartInstance.destroy();
        chartInstance = new Chart(chartCanvas.value, {
            type: 'line',
            data: {
                labels,
                datasets: [
                    {
                        label: 'Response Time (ms)',
                        data: responseData,
                        borderColor: '#6366f1',
                        backgroundColor: 'rgba(99,102,241,0.08)',
                        fill: true,
                        tension: 0.3,
                        pointRadius: 2,
                        spanGaps: false,
                        yAxisID: 'y',
                    },
                    ...(hasTemp ? [{
                        label: 'Temperature (°C)',
                        data: tempData,
                        borderColor: '#f97316',
                        backgroundColor: 'transparent',
                        fill: false,
                        tension: 0.3,
                        pointRadius: 2,
                        spanGaps: true,
                        yAxisID: 'yTemp',
                        borderDash: [4, 2],
                    }] : []),
                ],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { tooltip: { mode: 'index', intersect: false } },
                scales: {
                    y: { beginAtZero: true, position: 'left', title: { display: true, text: 'ms' } },
                    x: { ticks: { maxTicksLimit: 12, maxRotation: 0 } },
                    ...(hasTemp ? { yTemp: { position: 'right', title: { display: true, text: '°C' }, grid: { drawOnChartArea: false } } } : {}),
                },
            },
        });
    } catch (e) {
        // chart load failed silently
    }
}

onMounted(() => loadChart());
onUnmounted(() => { if (chartInstance) chartInstance.destroy(); });

function formatDateTime(dateStr) {
    if (!dateStr) return '—';
    return new Date(dateStr).toLocaleString();
}

const severityClasses = {
    critical: 'bg-red-100 text-red-700',
    high: 'bg-orange-100 text-orange-700',
    medium: 'bg-yellow-100 text-yellow-700',
    low: 'bg-blue-100 text-blue-700',
    info: 'bg-gray-100 text-gray-600',
};
</script>

<template>
    <Head :title="device.name" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <Link href="/devices" class="text-gray-400 hover:text-gray-600">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                        </svg>
                    </Link>
                    <h2 class="text-xl font-semibold leading-tight text-gray-800">{{ device.name }}</h2>
                    <StatusIndicator :status="device.status" />
                </div>
                <div class="flex items-center gap-2">
                    <button
                        type="button"
                        @click="testConnection"
                        :disabled="testingConnection"
                        class="inline-flex items-center gap-1.5 rounded-md border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 disabled:opacity-50"
                    >
                        <svg class="h-4 w-4" :class="{ 'animate-spin': testingConnection }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                        {{ testingConnection ? 'Testing...' : 'Test Connection' }}
                    </button>
                    <Link
                        :href="`/devices/${device.id}/edit`"
                        class="inline-flex items-center gap-1.5 rounded-md bg-indigo-600 px-3 py-2 text-sm font-medium text-white shadow-sm hover:bg-indigo-500"
                    >
                        Edit
                    </Link>
                </div>
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 space-y-8">

                <!-- Test Connection Result -->
                <div
                    v-if="testResult"
                    :class="testResult.success ? 'bg-green-50 border-green-200 text-green-800' : 'bg-red-50 border-red-200 text-red-800'"
                    class="rounded-lg border px-4 py-3 text-sm font-medium flex items-center justify-between"
                >
                    <span>{{ testResult.message }}</span>
                    <button type="button" @click="testResult = null" class="ml-4 opacity-60 hover:opacity-100">&times;</button>
                </div>

                <!-- Device Info -->
                <div class="rounded-lg bg-white shadow overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-100">
                        <h3 class="text-base font-semibold text-gray-800">Device Information</h3>
                    </div>
                    <dl class="grid grid-cols-2 gap-x-6 gap-y-4 p-6 sm:grid-cols-3 lg:grid-cols-4">
                        <div>
                            <dt class="text-xs font-medium text-gray-500 uppercase tracking-wide">IP Address</dt>
                            <dd class="mt-1 font-mono text-sm text-gray-900">{{ device.ip_address }}</dd>
                        </div>
                        <div v-if="device.port">
                            <dt class="text-xs font-medium text-gray-500 uppercase tracking-wide">Port</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ device.port }}</dd>
                        </div>
                        <div v-if="device.model">
                            <dt class="text-xs font-medium text-gray-500 uppercase tracking-wide">Model</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ device.model }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-medium text-gray-500 uppercase tracking-wide">Last Seen</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ formatDateTime(device.last_seen_at) }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-medium text-gray-500 uppercase tracking-wide">Channels</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ channels.length }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-medium text-gray-500 uppercase tracking-wide">Added</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ formatDateTime(device.created_at) }}</dd>
                        </div>
                    </dl>
                </div>

                <!-- Channels -->
                <div class="rounded-lg bg-white shadow overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-100">
                        <h3 class="text-base font-semibold text-gray-800">
                            Channels
                            <span class="ml-2 text-sm font-normal text-gray-500">({{ channels.length }})</span>
                        </h3>
                    </div>
                    <div class="p-6">
                        <ChannelGrid :channels="channels" />
                    </div>
                </div>

                <!-- Storage -->
                <div v-if="storages.length > 0" class="rounded-lg bg-white shadow overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-100">
                        <h3 class="text-base font-semibold text-gray-800">Storage</h3>
                    </div>
                    <div class="grid grid-cols-1 gap-4 p-6 sm:grid-cols-2 lg:grid-cols-3">
                        <StorageCard
                            v-for="storage in storages"
                            :key="storage.id"
                            :storage="storage"
                        />
                    </div>
                </div>

                <!-- Active Alerts -->
                <div class="rounded-lg bg-white shadow overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                        <h3 class="text-base font-semibold text-gray-800">Recent Alerts</h3>
                        <Link href="/alerts" class="text-sm text-indigo-600 hover:text-indigo-500">View all</Link>
                    </div>
                    <div v-if="alerts.length === 0" class="px-6 py-8 text-center text-sm text-gray-500">
                        No active alerts for this device.
                    </div>
                    <ul v-else class="divide-y divide-gray-100">
                        <li v-for="alert in alerts" :key="alert.id" class="flex items-center justify-between px-6 py-4">
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-medium text-gray-900">{{ alert.title }}</p>
                                <p class="text-xs text-gray-500 mt-0.5">{{ formatDateTime(alert.created_at) }}</p>
                            </div>
                            <span
                                :class="severityClasses[alert.severity] ?? 'bg-gray-100 text-gray-600'"
                                class="ml-4 shrink-0 rounded-full px-2.5 py-0.5 text-xs font-medium capitalize"
                            >
                                {{ alert.severity }}
                            </span>
                        </li>
                    </ul>
                </div>

                <!-- Health Log -->
                <div class="rounded-lg bg-white shadow overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-100">
                        <h3 class="text-base font-semibold text-gray-800">Health History</h3>
                    </div>

                    <!-- Temp Stats Card -->
                    <div v-if="tempStats" class="grid grid-cols-4 divide-x divide-gray-100 border-b border-gray-100 bg-gray-50">
                        <div class="px-5 py-3 text-center">
                            <p class="text-xs text-gray-400 uppercase tracking-wide">Last</p>
                            <p class="mt-1 text-lg font-semibold" :class="tempStats.last >= 55 ? 'text-red-600' : tempStats.last >= 45 ? 'text-yellow-600' : 'text-gray-800'">
                                {{ tempStats.last }}°C
                            </p>
                        </div>
                        <div class="px-5 py-3 text-center">
                            <p class="text-xs text-gray-400 uppercase tracking-wide">Min</p>
                            <p class="mt-1 text-lg font-semibold text-blue-600">{{ tempStats.min }}°C</p>
                        </div>
                        <div class="px-5 py-3 text-center">
                            <p class="text-xs text-gray-400 uppercase tracking-wide">Max</p>
                            <p class="mt-1 text-lg font-semibold" :class="tempStats.max >= 55 ? 'text-red-600' : tempStats.max >= 45 ? 'text-yellow-600' : 'text-gray-800'">
                                {{ tempStats.max }}°C
                            </p>
                        </div>
                        <div class="px-5 py-3 text-center">
                            <p class="text-xs text-gray-400 uppercase tracking-wide">Avg</p>
                            <p class="mt-1 text-lg font-semibold text-gray-700">{{ tempStats.avg }}°C</p>
                        </div>
                    </div>

                    <div class="px-6 py-4" style="height:180px;">
                        <canvas ref="chartCanvas"></canvas>
                    </div>
                    <div v-if="healthLogs.data.length > 0" class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-100 text-sm">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">Time</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">Status</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">Response</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">Temp</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 bg-white">
                                <tr v-for="log in healthLogs.data" :key="log.id" class="hover:bg-gray-50">
                                    <td class="whitespace-nowrap px-6 py-3 text-gray-600">{{ formatDateTime(log.created_at) }}</td>
                                    <td class="whitespace-nowrap px-6 py-3">
                                        <StatusIndicator :status="log.status" />
                                    </td>
                                    <td class="px-6 py-3 text-gray-600">{{ log.response_time_ms ? log.response_time_ms + 'ms' : '—' }}</td>
                                    <td class="px-6 py-3 text-gray-600">{{ log.temperature !== null && log.temperature !== undefined ? log.temperature + '°C' : '—' }}</td>
                                </tr>
                            </tbody>
                        </table>
                        <!-- Pagination -->
                        <div v-if="healthLogs.last_page > 1" class="flex items-center justify-between border-t border-gray-100 px-6 py-3">
                            <p class="text-xs text-gray-500">
                                Showing {{ healthLogs.from }}–{{ healthLogs.to }} of {{ healthLogs.total }}
                            </p>
                            <div class="flex gap-1">
                                <Link
                                    v-for="link in healthLogs.links"
                                    :key="link.label"
                                    :href="link.url ?? '#'"
                                    :class="[
                                        'px-2.5 py-1 rounded text-xs border',
                                        link.active ? 'bg-indigo-600 text-white border-indigo-600' : 'text-gray-600 border-gray-200 hover:bg-gray-50',
                                        !link.url ? 'opacity-40 pointer-events-none' : '',
                                    ]"
                                    preserve-scroll
                                    v-html="link.label"
                                />
                            </div>
                        </div>
                    </div>
                    <div v-else class="px-6 py-4 text-sm text-gray-500">No health history yet.</div>
                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>
