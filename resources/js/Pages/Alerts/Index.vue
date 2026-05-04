<script setup>
import { reactive, computed } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const props = defineProps({
    alerts: {
        type: Object,
        default: () => ({ data: [], links: [], meta: {} }),
    },
    filters: {
        type: Object,
        default: () => ({}),
    },
});

const filterForm = reactive({
    status: props.filters.status ?? '',
    severity: props.filters.severity ?? '',
    device_id: props.filters.device_id ?? '',
});

function applyFilters() {
    const params = {};
    if (filterForm.status) params.status = filterForm.status;
    if (filterForm.severity) params.severity = filterForm.severity;
    if (filterForm.device_id) params.device_id = filterForm.device_id;
    router.get('/alerts', params, { preserveScroll: true, preserveState: true });
}

function clearFilters() {
    filterForm.status = '';
    filterForm.severity = '';
    filterForm.device_id = '';
    router.get('/alerts', {}, { preserveScroll: true });
}

function acknowledgeAlert(alert) {
    router.post(`/alerts/${alert.id}/acknowledge`, {}, { preserveScroll: true });
}

function exportCsv() {
    const params = new URLSearchParams();
    if (filterForm.status) params.set('status', filterForm.status);
    if (filterForm.severity) params.set('severity', filterForm.severity);
    if (filterForm.device_id) params.set('device_id', filterForm.device_id);
    params.set('export', 'csv');
    window.location.href = `/alerts?${params.toString()}`;
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
    info: 'bg-slate-700/60 text-slate-400',
};

const statusBadge = {
    active: 'bg-red-500/15 text-red-400',
    acknowledged: 'bg-amber-500/15 text-amber-400',
    resolved: 'bg-emerald-500/15 text-emerald-400',
};

const alertsList = computed(() =>
    Array.isArray(props.alerts) ? props.alerts : (props.alerts?.data ?? []),
);
const paginationLinks = computed(() =>
    Array.isArray(props.alerts) ? [] : (props.alerts?.links ?? []),
);
const paginationMeta = computed(() =>
    Array.isArray(props.alerts) ? {} : (props.alerts?.meta ?? props.alerts ?? {}),
);

const activeFiltersCount = computed(() =>
    [filterForm.status, filterForm.severity, filterForm.device_id].filter(Boolean).length,
);
</script>

<template>
    <Head title="Alerts" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-[17px] font-semibold text-slate-100">Alert Center</h2>
                    <p class="text-[13px] text-slate-500">
                        {{ alertsList.length }} alert{{ alertsList.length !== 1 ? 's' : '' }}
                        <template v-if="activeFiltersCount > 0">
                            · <span class="text-cyan-400">{{ activeFiltersCount }} filter{{ activeFiltersCount !== 1 ? 's' : '' }} active</span>
                        </template>
                    </p>
                </div>
                <button
                    type="button"
                    @click="exportCsv"
                    class="inline-flex items-center gap-1.5 rounded-md border border-white/[0.08] bg-slate-800/60 px-3 py-2 text-[13px] font-medium text-slate-300 transition-colors hover:bg-slate-700/60"
                >
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                    </svg>
                    Export CSV
                </button>
            </div>
        </template>

        <!-- Filters -->
        <div class="rounded-lg border border-white/[0.06] bg-slate-900/60 px-5 py-4 shadow-[0_1px_2px_rgba(0,0,0,0.4)]">
            <div class="flex flex-wrap items-end gap-3">
                <div>
                    <label class="mb-1 block text-[11px] font-medium uppercase tracking-wider text-slate-500">Status</label>
                    <select
                        v-model="filterForm.status"
                        class="rounded-md border border-white/[0.08] bg-slate-800/60 px-3 py-2 text-[13px] text-slate-300 focus:border-cyan-500/50 focus:outline-none focus:ring-1 focus:ring-cyan-500/30"
                    >
                        <option value="">All</option>
                        <option value="active">Active</option>
                        <option value="acknowledged">Acknowledged</option>
                        <option value="resolved">Resolved</option>
                    </select>
                </div>

                <div>
                    <label class="mb-1 block text-[11px] font-medium uppercase tracking-wider text-slate-500">Severity</label>
                    <select
                        v-model="filterForm.severity"
                        class="rounded-md border border-white/[0.08] bg-slate-800/60 px-3 py-2 text-[13px] text-slate-300 focus:border-cyan-500/50 focus:outline-none focus:ring-1 focus:ring-cyan-500/30"
                    >
                        <option value="">All</option>
                        <option value="critical">Critical</option>
                        <option value="high">High</option>
                        <option value="medium">Medium</option>
                        <option value="low">Low</option>
                        <option value="info">Info</option>
                    </select>
                </div>

                <div class="flex items-center gap-2 pb-0.5">
                    <button
                        type="button"
                        @click="applyFilters"
                        class="rounded-md bg-cyan-600 px-4 py-2 text-[13px] font-medium text-white transition-colors hover:bg-cyan-500"
                    >
                        Apply
                    </button>
                    <button
                        v-if="activeFiltersCount > 0"
                        type="button"
                        @click="clearFilters"
                        class="rounded-md border border-white/[0.08] px-4 py-2 text-[13px] font-medium text-slate-400 transition-colors hover:bg-slate-800/60 hover:text-slate-200"
                    >
                        Clear
                    </button>
                </div>
            </div>
        </div>

        <!-- Table -->
        <div class="mt-3 rounded-lg border border-white/[0.06] bg-slate-900/60 shadow-[0_1px_2px_rgba(0,0,0,0.4)]">
            <!-- Empty -->
            <div v-if="alertsList.length === 0" class="py-16 text-center">
                <svg class="mx-auto h-10 w-10 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                </svg>
                <p class="mt-3 text-[13px] text-slate-500">No alerts found.</p>
                <button v-if="activeFiltersCount > 0" type="button" @click="clearFilters" class="mt-2 text-[13px] text-cyan-400 hover:text-cyan-300">
                    Clear filters
                </button>
            </div>

            <div v-else class="overflow-x-auto">
                <table class="min-w-full text-[13px]">
                    <thead>
                        <tr class="border-b border-white/[0.06] text-left text-[11px] uppercase tracking-wider text-slate-500">
                            <th class="px-5 py-3 font-medium">Title</th>
                            <th class="px-5 py-3 font-medium">Severity</th>
                            <th class="px-5 py-3 font-medium">Device</th>
                            <th class="px-5 py-3 font-medium">Status</th>
                            <th class="px-5 py-3 font-medium">Created</th>
                            <th class="px-5 py-3 text-right font-medium">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/[0.04]">
                        <tr v-for="alert in alertsList" :key="alert.id" class="hover:bg-slate-800/40">
                            <td class="px-5 py-3">
                                <p class="font-medium text-slate-200">{{ alert.title }}</p>
                                <p v-if="alert.message" class="mt-0.5 line-clamp-1 text-[12px] text-slate-500">{{ alert.message }}</p>
                            </td>
                            <td class="whitespace-nowrap px-5 py-3">
                                <span
                                    :class="severityBadge[alert.severity] ?? 'bg-slate-700/60 text-slate-400'"
                                    class="inline-flex rounded px-1.5 py-0.5 text-[11px] font-medium capitalize"
                                >
                                    {{ alert.severity }}
                                </span>
                            </td>
                            <td class="whitespace-nowrap px-5 py-3">
                                <Link
                                    v-if="alert.device"
                                    :href="`/devices/${alert.device.id}`"
                                    class="text-slate-300 hover:text-cyan-400"
                                >
                                    {{ alert.device.name }}
                                </Link>
                                <span v-else class="text-slate-500">—</span>
                            </td>
                            <td class="whitespace-nowrap px-5 py-3">
                                <span
                                    :class="statusBadge[alert.status] ?? 'bg-slate-700/60 text-slate-400'"
                                    class="inline-flex rounded px-1.5 py-0.5 text-[11px] font-medium capitalize"
                                >
                                    {{ alert.status }}
                                </span>
                            </td>
                            <td class="whitespace-nowrap px-5 py-3 tabular-nums text-slate-500">{{ formatDateTime(alert.created_at) }}</td>
                            <td class="whitespace-nowrap px-5 py-3 text-right">
                                <button
                                    v-if="alert.status === 'active'"
                                    type="button"
                                    @click="acknowledgeAlert(alert)"
                                    class="text-[12px] font-medium text-cyan-400 hover:text-cyan-300"
                                >
                                    Acknowledge
                                </button>
                                <span v-else class="text-slate-600">—</span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div v-if="paginationLinks.length > 3" class="flex items-center justify-between border-t border-white/[0.06] px-5 py-3">
                <p class="text-[12px] text-slate-500">
                    Showing {{ paginationMeta.from ?? '—' }}–{{ paginationMeta.to ?? '—' }} of {{ paginationMeta.total ?? '—' }}
                </p>
                <div class="flex gap-1">
                    <template v-for="link in paginationLinks" :key="link.label">
                        <Link
                            v-if="link.url"
                            :href="link.url"
                            preserve-scroll
                            :class="[
                                'inline-flex items-center rounded border px-2.5 py-1 text-[12px] font-medium transition-colors',
                                link.active
                                    ? 'border-cyan-500/50 bg-cyan-600/20 text-cyan-300'
                                    : 'border-white/[0.08] text-slate-400 hover:bg-slate-800/60 hover:text-slate-200',
                            ]"
                            v-html="link.label"
                        />
                        <span
                            v-else
                            class="inline-flex items-center rounded border border-white/[0.06] bg-slate-800/30 px-2.5 py-1 text-[12px] text-slate-600"
                            v-html="link.label"
                        />
                    </template>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
