<script setup>
import { computed, onMounted, onUnmounted, ref, watch, nextTick } from 'vue';
import { usePage, router, Head, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import {
    Chart,
    LineController,
    LineElement,
    PointElement,
    LinearScale,
    CategoryScale,
    Filler,
    Tooltip,
    Legend,
    DoughnutController,
    ArcElement,
    BarController,
    BarElement,
} from 'chart.js';

Chart.register(
    LineController, LineElement, PointElement, LinearScale, CategoryScale, Filler,
    Tooltip, Legend, DoughnutController, ArcElement, BarController, BarElement,
);

const props = defineProps({
    devices: { type: Array, default: () => [] },
    stats: { type: Object, default: () => ({}) },
});

const page = usePage();
const user = computed(() => page.props.auth.user);

const totalDevices = computed(() => props.stats.total_devices ?? props.devices.length);
const onlineCount = computed(() => props.stats.online_devices ?? props.devices.filter(d => d.status === 'online').length);
const offlineCount = computed(() => props.stats.offline_devices ?? props.devices.filter(d => d.status === 'offline').length);
const activeAlerts = computed(() => props.stats.active_alerts ?? 0);
const recordingCount = computed(() => onlineCount.value); // TODO: replace with actual recording status

const kpis = computed(() => [
    { label: 'Total Cameras', value: totalDevices.value, hint: 'All registered devices', tone: 'cyan' },
    { label: 'Online', value: onlineCount.value, hint: 'Reachable now', tone: 'emerald' },
    { label: 'Offline', value: offlineCount.value, hint: 'Unreachable', tone: 'red' },
    { label: 'Alerts Today', value: activeAlerts.value, hint: 'Active + acknowledged', tone: 'amber' },
    { label: 'Recording', value: recordingCount.value, hint: 'Streams capturing', tone: 'slate' },
]);

const toneClass = (tone) => ({
    cyan: 'text-cyan-400',
    emerald: 'text-emerald-400',
    red: 'text-red-400',
    amber: 'text-amber-400',
    slate: 'text-slate-200',
}[tone] ?? 'text-slate-200');

// ===== Chart refs =====
const activityCanvas = ref(null);
const statusCanvas = ref(null);
const zoneCanvas = ref(null);
let activityChart = null;
let statusChart = null;
let zoneChart = null;

// TODO: replace with API — last 24h camera activity
const activityData = {
    labels: ['00:00','02:00','04:00','06:00','08:00','10:00','12:00','14:00','16:00','18:00','20:00','22:00'],
    online: [22,22,21,21,23,24,24,23,22,24,24,23],
    motion: [3,2,1,0,4,8,12,15,11,9,6,4],
};

// TODO: replace with API — alerts per zone last 7 days
const zoneData = {
    labels: ['Lobby','Parking','Warehouse','Back Gate','Office','Roof'],
    counts: [12, 28, 7, 19, 4, 9],
};

const statusCategories = computed(() => [
    { label: 'Camera Offline',               count: props.stats.offline_devices     ?? 0, color: '#ef4444', dot: 'bg-red-500' },
    { label: 'Video Loss',                   count: props.stats.video_loss          ?? 0, color: '#f59e0b', dot: 'bg-amber-400' },
    { label: 'Communication Exception',      count: props.stats.comm_exception      ?? 0, color: '#f97316', dot: 'bg-orange-400' },
    { label: 'Recording Exception',          count: props.stats.recording_exception ?? 0, color: '#818cf8', dot: 'bg-indigo-400' },
    { label: 'No Recording Schedule Config', count: props.stats.storage_fault       ?? 0, color: '#64748b', dot: 'bg-slate-500' },
    { label: 'Arming Exception',             count: props.stats.active_alerts       ?? 0, color: '#06b6d4', dot: 'bg-cyan-400' },
]);

const statusTotal = computed(() => statusCategories.value.reduce((s, c) => s + c.count, 0));
const statusDominant = computed(() => {
    const exc = statusCategories.value.filter(c => c.count > 0);
    if (!exc.length) return { label: 'All Clear', count: totalDevices.value };
    return exc.reduce((a, b) => b.count > a.count ? b : a);
});

// TODO: replace with API — recent events feed
const recentEvents = [
    { time: '14:32', type: 'motion', zone: 'Parking', severity: 'info' },
    { time: '14:18', type: 'offline', zone: 'Back Gate', severity: 'critical' },
    { time: '13:55', type: 'storage', zone: 'NVR-01', severity: 'warning' },
    { time: '13:21', type: 'motion', zone: 'Lobby', severity: 'info' },
    { time: '12:47', type: 'tamper', zone: 'Roof', severity: 'critical' },
    { time: '12:10', type: 'reconnect', zone: 'Warehouse', severity: 'info' },
];

const severityClass = (s) => ({
    critical: 'bg-red-500/15 text-red-400 ring-1 ring-red-500/30',
    warning: 'bg-amber-500/15 text-amber-400 ring-1 ring-amber-500/30',
    info: 'bg-cyan-500/15 text-cyan-400 ring-1 ring-cyan-500/30',
}[s] ?? 'bg-slate-700 text-slate-300');

// TODO: replace with API — recent alert log
const recentLogs = computed(() => {
    const sample = [
        { id: 1, device: 'CAM-LOBBY-01', message: 'Motion detected', status: 'active', time: '2m ago' },
        { id: 2, device: 'NVR-MAIN', message: 'HDD temperature 52°C', status: 'warning', time: '14m ago' },
        { id: 3, device: 'CAM-GATE-03', message: 'Connection lost', status: 'critical', time: '46m ago' },
        { id: 4, device: 'CAM-ROOF-02', message: 'Tamper detected', status: 'critical', time: '1h ago' },
        { id: 5, device: 'CAM-OFFICE-01', message: 'Recording resumed', status: 'resolved', time: '2h ago' },
    ];
    return sample;
});

const statusBadge = (s) => ({
    active: 'bg-cyan-500/15 text-cyan-400',
    warning: 'bg-amber-500/15 text-amber-400',
    critical: 'bg-red-500/15 text-red-400',
    resolved: 'bg-emerald-500/15 text-emerald-400',
}[s] ?? 'bg-slate-700 text-slate-300');

// ===== Chart options =====
const gridColor = 'rgba(148, 163, 184, 0.1)';
const tickColor = '#94a3b8';

function buildActivityChart() {
    if (!activityCanvas.value) return;
    const ctx = activityCanvas.value.getContext('2d');
    const grad = ctx.createLinearGradient(0, 0, 0, 240);
    grad.addColorStop(0, 'rgba(6, 182, 212, 0.35)');
    grad.addColorStop(1, 'rgba(6, 182, 212, 0)');

    activityChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: activityData.labels,
            datasets: [
                {
                    label: 'Online cameras',
                    data: activityData.online,
                    borderColor: '#06b6d4',
                    backgroundColor: grad,
                    fill: true,
                    tension: 0.35,
                    borderWidth: 2,
                    pointRadius: 0,
                    pointHoverRadius: 4,
                },
                {
                    label: 'Motion events',
                    data: activityData.motion,
                    borderColor: '#f59e0b',
                    backgroundColor: 'transparent',
                    fill: false,
                    tension: 0.35,
                    borderWidth: 2,
                    pointRadius: 0,
                    pointHoverRadius: 4,
                    borderDash: [4, 4],
                },
            ],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: { mode: 'index', intersect: false },
            plugins: {
                legend: {
                    display: true,
                    position: 'top',
                    align: 'end',
                    labels: { color: tickColor, font: { size: 12 }, boxWidth: 10, boxHeight: 10, usePointStyle: true },
                },
                tooltip: {
                    backgroundColor: '#0f172a',
                    borderColor: 'rgba(255,255,255,0.08)',
                    borderWidth: 1,
                    titleColor: '#e2e8f0',
                    bodyColor: '#cbd5e1',
                    padding: 10,
                },
            },
            scales: {
                x: { grid: { color: gridColor, drawBorder: false }, ticks: { color: tickColor, font: { size: 11 } } },
                y: { grid: { color: gridColor, drawBorder: false }, ticks: { color: tickColor, font: { size: 11 } }, beginAtZero: true },
            },
        },
    });
}

function buildStatusChart() {
    if (!statusCanvas.value) return;
    const ctx = statusCanvas.value.getContext('2d');
    statusChart = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: statusCategories.value.map(c => c.label),
            datasets: [{
                data: statusCategories.value.map(c => c.count || 0.01),
                backgroundColor: statusCategories.value.map(c => c.color),
                borderColor: '#0f172a',
                borderWidth: 2,
                hoverOffset: 4,
            }],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '72%',
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: '#0f172a',
                    borderColor: 'rgba(255,255,255,0.08)',
                    borderWidth: 1,
                    titleColor: '#e2e8f0',
                    bodyColor: '#cbd5e1',
                    padding: 10,
                    callbacks: {
                        label: (ctx) => {
                            const real = statusCategories.value[ctx.dataIndex].count;
                            return ` ${ctx.label}: ${real}`;
                        },
                    },
                },
            },
        },
    });
}

function buildZoneChart() {
    if (!zoneCanvas.value) return;
    const ctx = zoneCanvas.value.getContext('2d');
    zoneChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: zoneData.labels,
            datasets: [{
                label: 'Alerts',
                data: zoneData.counts,
                backgroundColor: 'rgba(6, 182, 212, 0.55)',
                hoverBackgroundColor: '#06b6d4',
                borderRadius: 4,
                borderSkipped: false,
                barThickness: 22,
            }],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: '#0f172a',
                    borderColor: 'rgba(255,255,255,0.08)',
                    borderWidth: 1,
                    titleColor: '#e2e8f0',
                    bodyColor: '#cbd5e1',
                    padding: 10,
                },
            },
            scales: {
                x: { grid: { display: false }, ticks: { color: tickColor, font: { size: 11 } } },
                y: { grid: { color: gridColor, drawBorder: false }, ticks: { color: tickColor, font: { size: 11 } }, beginAtZero: true },
            },
        },
    });
}

// Update donut when stats change
watch([onlineCount, offlineCount, activeAlerts], () => {
    if (statusChart) {
        statusChart.data.datasets[0].data = statusCategories.value.map(c => c.count || 0.01);
        statusChart.update();
    }
});

let refreshTimer = null;

onMounted(async () => {
    await nextTick();
    buildActivityChart();
    buildStatusChart();
    buildZoneChart();
    refreshTimer = setInterval(() => {
        router.reload({ only: ['devices', 'stats'] });
    }, 30000);
});

onUnmounted(() => {
    if (refreshTimer) clearInterval(refreshTimer);
    activityChart?.destroy();
    statusChart?.destroy();
    zoneChart?.destroy();
});
</script>

<template>
    <Head title="Dashboard" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-[17px] font-semibold text-slate-100">CCTV Early Warning Dashboard</h2>
                    <p class="text-[13px] text-slate-500">Welcome back, {{ user.name }} — live overview of your camera fleet.</p>
                </div>
                <span class="inline-flex items-center gap-2 rounded-md bg-slate-800/60 px-2.5 py-1 text-[12px] text-slate-400 ring-1 ring-white/[0.06]">
                    <span class="relative flex h-2 w-2">
                        <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-cyan-400 opacity-60"></span>
                        <span class="relative inline-flex h-2 w-2 rounded-full bg-cyan-500"></span>
                    </span>
                    Auto-refresh 30s
                </span>
            </div>
        </template>

        <!-- KPI Row -->
        <section class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-5">
            <div
                v-for="k in kpis"
                :key="k.label"
                class="rounded-lg border border-white/[0.06] bg-slate-900/60 p-4 shadow-[0_1px_2px_rgba(0,0,0,0.4)]"
            >
                <p class="text-[11px] font-medium uppercase tracking-wider text-slate-500">{{ k.label }}</p>
                <p :class="['mt-1.5 text-2xl font-semibold tabular-nums', toneClass(k.tone)]">{{ k.value }}</p>
                <p class="mt-1 text-[12px] text-slate-500">{{ k.hint }}</p>
            </div>
        </section>

        <!-- Main Charts Row -->
        <section class="mt-4 grid grid-cols-1 gap-3 lg:grid-cols-3">
            <div class="rounded-lg border border-white/[0.06] bg-slate-900/60 p-4 shadow-[0_1px_2px_rgba(0,0,0,0.4)] lg:col-span-2">
                <div class="mb-3 flex items-center justify-between">
                    <div>
                        <h3 class="text-[15px] font-semibold text-slate-100">Camera Activity</h3>
                        <p class="text-[12px] text-slate-500">Online count + motion events, last 24h</p>
                    </div>
                    <div class="flex items-center gap-1 rounded-md bg-slate-800/60 p-0.5 text-[11px] ring-1 ring-white/[0.06]">
                        <button class="rounded px-2 py-1 text-slate-400 hover:text-slate-100">24h</button>
                        <button class="rounded bg-slate-700/60 px-2 py-1 font-medium text-cyan-400">7d</button>
                        <button class="rounded px-2 py-1 text-slate-400 hover:text-slate-100">30d</button>
                    </div>
                </div>
                <div class="h-[240px]">
                    <canvas ref="activityCanvas"></canvas>
                </div>
            </div>

            <div class="rounded-lg border border-white/[0.06] bg-slate-900/60 p-4 shadow-[0_1px_2px_rgba(0,0,0,0.4)]">
                <!-- Header -->
                <div class="mb-3 flex items-center justify-between">
                    <h3 class="text-[15px] font-semibold text-slate-100">Status Breakdown</h3>
                    <span class="text-[12px] text-slate-500">Total <span class="tabular-nums font-medium text-slate-300">{{ totalDevices }}</span></span>
                </div>

                <!-- Donut + list side by side -->
                <div class="flex items-center gap-4">
                    <!-- Donut -->
                    <div class="relative h-[150px] w-[150px] shrink-0">
                        <canvas ref="statusCanvas"></canvas>
                        <div class="pointer-events-none absolute inset-0 flex flex-col items-center justify-center">
                            <span class="text-[10px] uppercase tracking-wider text-slate-500 leading-tight text-center px-2">{{ statusDominant.label }}</span>
                            <span class="text-[22px] font-bold tabular-nums text-slate-100 leading-tight">{{ statusDominant.count }}</span>
                        </div>
                    </div>

                    <!-- Category list -->
                    <ul class="min-w-0 flex-1 space-y-1.5">
                        <li
                            v-for="cat in statusCategories"
                            :key="cat.label"
                            class="flex items-center gap-2 rounded-md px-2 py-1.5 transition-colors hover:bg-slate-800/50"
                        >
                            <span :class="[cat.dot, 'h-2 w-2 shrink-0 rounded-full']"></span>
                            <span class="min-w-0 flex-1 truncate text-[12px] text-slate-400">{{ cat.label }}</span>
                            <span :class="['tabular-nums text-[13px] font-semibold shrink-0', cat.count > 0 ? 'text-slate-100' : 'text-slate-600']">{{ cat.count }}</span>
                            <svg class="h-3.5 w-3.5 shrink-0 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                        </li>
                    </ul>
                </div>
            </div>
        </section>
        <!-- Zone Alerts -->
        <section class="mt-4 rounded-lg border border-white/[0.06] bg-slate-900/60 p-4 shadow-[0_1px_2px_rgba(0,0,0,0.4)]">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-[15px] font-semibold text-slate-100">Zone Alerts</h3>
                    <p class="text-[12px] text-slate-500">Alerts per detection zone, last 24h</p>
                </div>
                <div class="text-[12px] text-slate-500">Total <span class="tabular-nums font-medium text-slate-300">{{ totalAlerts }}</span> alerts</div>
            </div>
            <div class="mt-3 h-[240px]">
                <canvas ref="zoneCanvas"></canvas>
            </div>
        </section>
        <div class="mt-4 text-center text-[11px] text-slate-600">
            <p>Early Warning Dashboard • Data refreshes automatically every 30 seconds</p>
        </div>
    </AuthenticatedLayout>
</template>
