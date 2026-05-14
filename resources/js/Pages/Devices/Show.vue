<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import StatusIndicator from '@/Components/StatusIndicator.vue';
import ChannelGrid from '@/Components/ChannelGrid.vue';
import StorageCard from '@/Components/StorageCard.vue';
import {
    Chart, LineController, LineElement, PointElement, LinearScale,
    TimeSeriesScale, CategoryScale, Tooltip, Filler,
} from 'chart.js';

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
const perPage = ref(new URLSearchParams(window.location.search).get('per_page') ?? '20');
let chartInstance = null;

function changePerPage(val) {
    router.get(route('devices.show', props.device.id), { per_page: val }, { preserveScroll: true, preserveState: true });
}

async function testConnection() {
    testingConnection.value = true;
    testResult.value = null;
    try {
        const res = await fetch(`/devices/${props.device.id}/test`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
                'Accept': 'application/json',
            },
        });
        const data = await res.json();
        testResult.value = {
            success: data.success,
            message: data.success
                ? `Connected in ${data.response_time_ms}ms${data.device_info ? ' — ' + data.device_info : ''}`
                : (data.error ?? 'Connection failed'),
        };
    } catch {
        testResult.value = { success: false, message: 'Request failed' };
    } finally {
        testingConnection.value = false;
    }
}

const gridColor = 'rgba(148,163,184,0.08)';
const tickColor = '#64748b';

async function loadChart() {
    if (!chartCanvas.value) return;
    try {
        const res = await fetch(`/devices/${props.device.id}/health-history`, { headers: { Accept: 'application/json' } });
        const logs = await res.json();
        if (!logs.length) return;

        const labels = logs.map(l => new Date(l.created_at).toLocaleTimeString());
        const responseData = logs.map(l => l.response_time_ms ?? null);
        const tempData = logs.map(l => l.temperature ?? null);
        const hasTemp = tempData.some(v => v !== null);

        if (chartInstance) chartInstance.destroy();

        const ctx = chartCanvas.value.getContext('2d');
        const grad = ctx.createLinearGradient(0, 0, 0, 160);
        grad.addColorStop(0, 'rgba(99,102,241,0.25)');
        grad.addColorStop(1, 'rgba(99,102,241,0)');

        chartInstance = new Chart(ctx, {
            type: 'line',
            data: {
                labels,
                datasets: [
                    {
                        label: 'Response Time (ms)',
                        data: responseData,
                        borderColor: '#818cf8',
                        backgroundColor: grad,
                        fill: true,
                        tension: 0.3,
                        pointRadius: 2,
                        spanGaps: false,
                        yAxisID: 'y',
                    },
                    ...(hasTemp ? [{
                        label: 'Temperature (°C)',
                        data: tempData,
                        borderColor: '#fb923c',
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
                plugins: {
                    tooltip: {
                        mode: 'index',
                        intersect: false,
                        backgroundColor: '#0f172a',
                        borderColor: 'rgba(255,255,255,0.08)',
                        borderWidth: 1,
                        titleColor: '#e2e8f0',
                        bodyColor: '#94a3b8',
                        padding: 10,
                    },
                    legend: {
                        labels: { color: tickColor, font: { size: 11 }, boxWidth: 8, usePointStyle: true },
                    },
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        position: 'left',
                        grid: { color: gridColor },
                        ticks: { color: tickColor, font: { size: 11 } },
                        title: { display: true, text: 'ms', color: tickColor, font: { size: 11 } },
                    },
                    x: {
                        grid: { color: gridColor },
                        ticks: { color: tickColor, font: { size: 11 }, maxTicksLimit: 12, maxRotation: 0 },
                    },
                    ...(hasTemp ? {
                        yTemp: {
                            position: 'right',
                            grid: { drawOnChartArea: false },
                            ticks: { color: tickColor, font: { size: 11 } },
                            title: { display: true, text: '°C', color: tickColor, font: { size: 11 } },
                        },
                    } : {}),
                },
            },
        });
    } catch {
        // chart load failed silently
    }
}

function formatDateTime(dateStr) {
    if (!dateStr) return '—';
    return new Date(dateStr).toLocaleString();
}

const severityBadge = {
    critical: 'bg-red-500/15 text-red-400 ring-1 ring-red-500/30',
    high: 'bg-orange-500/15 text-orange-400 ring-1 ring-orange-500/30',
    medium: 'bg-amber-500/15 text-amber-400 ring-1 ring-amber-500/30',
    low: 'bg-blue-500/15 text-blue-400 ring-1 ring-blue-500/30',
    info: 'bg-slate-700 text-slate-400',
};

const tempClass = (v) => v >= 55 ? 'text-red-400' : v >= 45 ? 'text-amber-400' : 'text-slate-100';

onMounted(() => loadChart());
onUnmounted(() => { if (chartInstance) chartInstance.destroy(); });
</script>

<template>
    <Head :title="device.name" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <Link href="/devices" class="rounded-md p-1.5 text-slate-500 transition-colors hover:bg-slate-800/60 hover:text-slate-200">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                        </svg>
                    </Link>
                    <div>
                        <div class="flex items-center gap-2">
                            <h2 class="text-[17px] font-semibold text-slate-100">{{ device.name }}</h2>
                            <StatusIndicator :status="device.status" />
                        </div>
                        <p class="text-[13px] text-slate-500 font-mono">{{ device.ip_address }}:{{ device.port }}</p>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <button
                        type="button"
                        @click="testConnection"
                        :disabled="testingConnection"
                        class="inline-flex items-center gap-1.5 rounded-md border border-white/[0.08] bg-slate-800/60 px-3 py-2 text-[13px] font-medium text-slate-300 transition-colors hover:bg-slate-700/60 disabled:opacity-50"
                    >
                        <svg class="h-4 w-4" :class="{ 'animate-spin': testingConnection }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                        {{ testingConnection ? 'Testing…' : 'Test Connection' }}
                    </button>
                    <Link
                        :href="`/devices/${device.id}/edit`"
                        class="inline-flex items-center gap-1.5 rounded-md bg-cyan-600 px-3 py-2 text-[13px] font-medium text-white transition-colors hover:bg-cyan-500"
                    >
                        Edit
                    </Link>
                </div>
            </div>
        </template>

        <div class="space-y-4">

            <!-- Test result banner -->
            <div
                v-if="testResult"
                :class="testResult.success
                    ? 'border-emerald-500/30 bg-emerald-500/10 text-emerald-300'
                    : 'border-red-500/30 bg-red-500/10 text-red-300'"
                class="flex items-center justify-between rounded-lg border px-4 py-3 text-[13px] font-medium"
            >
                <span>{{ testResult.message }}</span>
                <button type="button" @click="testResult = null" class="ml-4 opacity-60 hover:opacity-100 text-lg leading-none">&times;</button>
            </div>

            <!-- Device Info -->
            <div class="rounded-lg border border-white/[0.06] bg-slate-900/60 shadow-[0_1px_2px_rgba(0,0,0,0.4)]">
                <div class="border-b border-white/[0.06] px-5 py-3">
                    <h3 class="text-[15px] font-semibold text-slate-100">Device Information</h3>
                </div>
                <dl class="grid grid-cols-2 gap-x-6 gap-y-4 p-5 sm:grid-cols-3 lg:grid-cols-5">
                    <div>
                        <dt class="text-[11px] font-medium uppercase tracking-wider text-slate-500">IP Address</dt>
                        <dd class="mt-1 font-mono text-[13px]">
                            <a :href="`http://${device.ip_address}`" target="_blank" rel="noopener noreferrer" class="text-cyan-400 hover:text-cyan-300 hover:underline">{{ device.ip_address }}</a>
                        </dd>
                    </div>
                    <div v-if="device.port">
                        <dt class="text-[11px] font-medium uppercase tracking-wider text-slate-500">Port</dt>
                        <dd class="mt-1 text-[13px] text-slate-200">{{ device.port }}</dd>
                    </div>
                    <div v-if="device.model">
                        <dt class="text-[11px] font-medium uppercase tracking-wider text-slate-500">Model</dt>
                        <dd class="mt-1 text-[13px] text-slate-200">{{ device.model }}</dd>
                    </div>
                    <div>
                        <dt class="text-[11px] font-medium uppercase tracking-wider text-slate-500">Last Seen</dt>
                        <dd class="mt-1 text-[13px] text-slate-200">{{ formatDateTime(device.last_seen_at) }}</dd>
                    </div>
                    <div>
                        <dt class="text-[11px] font-medium uppercase tracking-wider text-slate-500">Channels</dt>
                        <dd class="mt-1 text-[13px] text-slate-200">{{ channels.length }}</dd>
                    </div>
                    <div>
                        <dt class="text-[11px] font-medium uppercase tracking-wider text-slate-500">Added</dt>
                        <dd class="mt-1 text-[13px] text-slate-200">{{ formatDateTime(device.created_at) }}</dd>
                    </div>
                </dl>
            </div>

            <!-- Channels + Storage side by side on larger screens -->
            <div class="grid grid-cols-1 gap-4 lg:grid-cols-3">
                <!-- Channels (wider) -->
                <div class="rounded-lg border border-white/[0.06] bg-slate-900/60 shadow-[0_1px_2px_rgba(0,0,0,0.4)] lg:col-span-2">
                    <div class="border-b border-white/[0.06] px-5 py-3">
                        <h3 class="text-[15px] font-semibold text-slate-100">
                            Channels
                            <span class="ml-1.5 text-[13px] font-normal text-slate-500">({{ channels.length }})</span>
                        </h3>
                    </div>
                    <div class="p-5">
                        <ChannelGrid :channels="channels" :device="device" />
                    </div>
                </div>

                <!-- Storage -->
                <div class="rounded-lg border border-white/[0.06] bg-slate-900/60 shadow-[0_1px_2px_rgba(0,0,0,0.4)]">
                    <div class="border-b border-white/[0.06] px-5 py-3">
                        <h3 class="text-[15px] font-semibold text-slate-100">Storage</h3>
                    </div>
                    <div class="space-y-3 p-5">
                        <template v-if="storages.length > 0">
                            <StorageCard v-for="s in storages" :key="s.id" :storage="s" />
                        </template>
                        <p v-else class="text-[13px] text-slate-500">No storage devices found.</p>
                    </div>
                </div>
            </div>

            <!-- Alerts -->
            <div class="rounded-lg border border-white/[0.06] bg-slate-900/60 shadow-[0_1px_2px_rgba(0,0,0,0.4)]">
                <div class="flex items-center justify-between border-b border-white/[0.06] px-5 py-3">
                    <h3 class="text-[15px] font-semibold text-slate-100">Recent Alerts</h3>
                    <Link href="/alerts" class="text-[12px] font-medium text-cyan-400 hover:text-cyan-300">View all →</Link>
                </div>
                <div v-if="alerts.length === 0" class="px-5 py-8 text-center text-[13px] text-slate-500">
                    No active alerts for this device.
                </div>
                <ul v-else class="divide-y divide-white/[0.04]">
                    <li v-for="alert in alerts" :key="alert.id" class="flex items-center justify-between px-5 py-3 hover:bg-slate-800/30">
                        <div class="min-w-0">
                            <p class="truncate text-[13px] font-medium text-slate-200">{{ alert.title }}</p>
                            <p class="text-[12px] tabular-nums text-slate-500">{{ formatDateTime(alert.created_at) }}</p>
                        </div>
                        <span
                            :class="severityBadge[alert.severity] ?? 'bg-slate-700 text-slate-400'"
                            class="ml-4 shrink-0 rounded px-1.5 py-0.5 text-[11px] font-medium capitalize"
                        >
                            {{ alert.severity }}
                        </span>
                    </li>
                </ul>
            </div>

            <!-- Health History -->
            <div class="rounded-lg border border-white/[0.06] bg-slate-900/60 shadow-[0_1px_2px_rgba(0,0,0,0.4)]">
                <div class="flex items-center justify-between border-b border-white/[0.06] px-5 py-3">
                    <h3 class="text-[15px] font-semibold text-slate-100">Health History</h3>
                    <div class="flex items-center gap-2">
                        <label for="per-page" class="text-[11px] text-slate-500">Rows</label>
                        <select
                            id="per-page"
                            v-model="perPage"
                            @change="changePerPage(perPage)"
                            class="rounded border border-white/[0.08] bg-slate-800/60 px-2 py-1 text-[12px] text-slate-300 focus:outline-none focus:ring-1 focus:ring-cyan-500/30"
                        >
                            <option value="5">5</option>
                            <option value="10">10</option>
                            <option value="15">15</option>
                            <option value="20">20</option>
                        </select>
                    </div>
                </div>

                <!-- Temp stats -->
                <div v-if="tempStats" class="grid grid-cols-4 divide-x divide-white/[0.06] border-b border-white/[0.06] bg-slate-800/30">
                    <div class="px-4 py-3 text-center">
                        <p class="text-[10px] font-medium uppercase tracking-wider text-slate-500">Last</p>
                        <p class="mt-1 text-[17px] font-semibold tabular-nums" :class="tempClass(tempStats.last)">{{ tempStats.last }}°C</p>
                    </div>
                    <div class="px-4 py-3 text-center">
                        <p class="text-[10px] font-medium uppercase tracking-wider text-slate-500">Min</p>
                        <p class="mt-1 text-[17px] font-semibold tabular-nums text-blue-400">{{ tempStats.min }}°C</p>
                    </div>
                    <div class="px-4 py-3 text-center">
                        <p class="text-[10px] font-medium uppercase tracking-wider text-slate-500">Max</p>
                        <p class="mt-1 text-[17px] font-semibold tabular-nums" :class="tempClass(tempStats.max)">{{ tempStats.max }}°C</p>
                    </div>
                    <div class="px-4 py-3 text-center">
                        <p class="text-[10px] font-medium uppercase tracking-wider text-slate-500">Avg</p>
                        <p class="mt-1 text-[17px] font-semibold tabular-nums text-slate-200">{{ tempStats.avg }}°C</p>
                    </div>
                </div>

                <!-- Chart -->
                <div class="px-5 py-4" style="height:180px;">
                    <canvas ref="chartCanvas"></canvas>
                </div>

                <!-- Table -->
                <div v-if="healthLogs.data.length > 0" class="overflow-x-auto border-t border-white/[0.06]">
                    <table class="min-w-full text-[13px]">
                        <thead>
                            <tr class="border-b border-white/[0.04] text-left text-[11px] uppercase tracking-wider text-slate-500">
                                <th class="px-5 py-2.5 font-medium">Time</th>
                                <th class="px-5 py-2.5 font-medium">Status</th>
                                <th class="px-5 py-2.5 font-medium">Response</th>
                                <th class="px-5 py-2.5 font-medium">Temp</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/[0.04]">
                            <tr v-for="log in healthLogs.data" :key="log.id" class="hover:bg-slate-800/30">
                                <td class="whitespace-nowrap px-5 py-2.5 tabular-nums text-slate-400">{{ formatDateTime(log.created_at) }}</td>
                                <td class="whitespace-nowrap px-5 py-2.5">
                                    <StatusIndicator :status="log.status" />
                                </td>
                                <td class="px-5 py-2.5 tabular-nums text-slate-400">{{ log.response_time_ms ? log.response_time_ms + 'ms' : '—' }}</td>
                                <td class="px-5 py-2.5 tabular-nums" :class="log.temperature !== null && log.temperature !== undefined ? tempClass(log.temperature) : 'text-slate-500'">
                                    {{ log.temperature !== null && log.temperature !== undefined ? log.temperature + '°C' : '—' }}
                                </td>
                            </tr>
                        </tbody>
                    </table>

                    <!-- Pagination -->
                    <div v-if="healthLogs.last_page > 1" class="flex items-center justify-between border-t border-white/[0.06] px-5 py-3">
                        <p class="text-[12px] text-slate-500">
                            Showing {{ healthLogs.from }}–{{ healthLogs.to }} of {{ healthLogs.total }}
                        </p>
                        <div class="flex gap-1">
                            <Link
                                v-for="link in healthLogs.links"
                                :key="link.label"
                                :href="link.url ?? '#'"
                                :class="[
                                    'rounded px-2.5 py-1 text-[12px] border transition-colors',
                                    link.active
                                        ? 'border-cyan-500/50 bg-cyan-600/20 text-cyan-300'
                                        : 'border-white/[0.08] text-slate-400 hover:bg-slate-800/60 hover:text-slate-200',
                                    !link.url ? 'pointer-events-none opacity-40' : '',
                                ]"
                                preserve-scroll
                                v-html="link.label"
                            />
                        </div>
                    </div>
                </div>
                <div v-else class="px-5 py-6 text-center text-[13px] text-slate-500">No health history yet.</div>
            </div>

        </div>
    </AuthenticatedLayout>
</template>
