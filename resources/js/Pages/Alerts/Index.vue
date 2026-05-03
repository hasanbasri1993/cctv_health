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
    router.patch(`/alerts/${alert.id}/acknowledge`, {}, { preserveScroll: true });
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

const severityClasses = {
    critical: 'bg-red-100 text-red-700 ring-red-200',
    high: 'bg-orange-100 text-orange-700 ring-orange-200',
    medium: 'bg-yellow-100 text-yellow-700 ring-yellow-200',
    low: 'bg-blue-100 text-blue-700 ring-blue-200',
    info: 'bg-gray-100 text-gray-600 ring-gray-200',
};

const statusClasses = {
    active: 'bg-red-50 text-red-700',
    acknowledged: 'bg-yellow-50 text-yellow-700',
    resolved: 'bg-green-50 text-green-700',
};

const alertsList = computed(() =>
    Array.isArray(props.alerts) ? props.alerts : (props.alerts?.data ?? [])
);
const paginationLinks = computed(() =>
    Array.isArray(props.alerts) ? [] : (props.alerts?.links ?? [])
);
</script>

<template>
    <Head title="Alerts" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">Alert Center</h2>
                <button
                    type="button"
                    @click="exportCsv"
                    class="inline-flex items-center gap-1.5 rounded-md border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50"
                >
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                    </svg>
                    Export CSV
                </button>
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 space-y-6">

                <!-- Filters -->
                <div class="rounded-lg bg-white p-5 shadow-sm">
                    <h3 class="mb-4 text-sm font-semibold text-gray-700">Filters</h3>
                    <div class="flex flex-wrap items-end gap-4">
                        <!-- Status -->
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Status</label>
                            <select
                                v-model="filterForm.status"
                                class="rounded-md border border-gray-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
                            >
                                <option value="">All</option>
                                <option value="active">Active</option>
                                <option value="acknowledged">Acknowledged</option>
                                <option value="resolved">Resolved</option>
                            </select>
                        </div>

                        <!-- Severity -->
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Severity</label>
                            <select
                                v-model="filterForm.severity"
                                class="rounded-md border border-gray-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
                            >
                                <option value="">All</option>
                                <option value="critical">Critical</option>
                                <option value="high">High</option>
                                <option value="medium">Medium</option>
                                <option value="low">Low</option>
                                <option value="info">Info</option>
                            </select>
                        </div>

                        <!-- Actions -->
                        <div class="flex items-center gap-2">
                            <button
                                type="button"
                                @click="applyFilters"
                                class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-500"
                            >
                                Apply
                            </button>
                            <button
                                type="button"
                                @click="clearFilters"
                                class="rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
                            >
                                Clear
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Table -->
                <div class="overflow-hidden rounded-lg bg-white shadow">
                    <div v-if="alertsList.length === 0" class="p-12 text-center">
                        <svg class="mx-auto h-12 w-12 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                        </svg>
                        <p class="mt-3 text-sm text-gray-500">No alerts found.</p>
                    </div>

                    <div v-else class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">Title</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">Severity</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">Device</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">Status</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">Created</th>
                                    <th class="relative px-6 py-3"><span class="sr-only">Actions</span></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 bg-white">
                                <tr v-for="alert in alertsList" :key="alert.id" class="hover:bg-gray-50">
                                    <td class="px-6 py-4">
                                        <p class="text-sm font-medium text-gray-900">{{ alert.title }}</p>
                                        <p v-if="alert.message" class="mt-0.5 text-xs text-gray-500 line-clamp-1">{{ alert.message }}</p>
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-4">
                                        <span
                                            :class="severityClasses[alert.severity] ?? 'bg-gray-100 text-gray-600 ring-gray-200'"
                                            class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium capitalize ring-1 ring-inset"
                                        >
                                            {{ alert.severity }}
                                        </span>
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-600">
                                        <Link
                                            v-if="alert.device"
                                            :href="`/devices/${alert.device.id}`"
                                            class="hover:text-indigo-600"
                                        >
                                            {{ alert.device.name }}
                                        </Link>
                                        <span v-else>—</span>
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-4">
                                        <span
                                            :class="statusClasses[alert.status] ?? 'bg-gray-50 text-gray-600'"
                                            class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium capitalize"
                                        >
                                            {{ alert.status }}
                                        </span>
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-600">
                                        {{ formatDateTime(alert.created_at) }}
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-4 text-right text-sm">
                                        <button
                                            v-if="alert.status === 'active'"
                                            type="button"
                                            @click="acknowledgeAlert(alert)"
                                            class="text-indigo-600 hover:text-indigo-500 font-medium"
                                        >
                                            Acknowledge
                                        </button>
                                        <span v-else class="text-gray-400">—</span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div v-if="paginationLinks.length > 3" class="flex items-center justify-between border-t border-gray-200 px-6 py-4">
                        <p class="text-sm text-gray-600">
                            Showing {{ alerts?.meta?.from ?? '—' }}–{{ alerts?.meta?.to ?? '—' }} of {{ alerts?.meta?.total ?? '—' }}
                        </p>
                        <div class="flex gap-1">
                            <template v-for="link in paginationLinks" :key="link.label">
                                <Link
                                    v-if="link.url"
                                    :href="link.url"
                                    preserve-scroll
                                    :class="[
                                        link.active
                                            ? 'bg-indigo-600 text-white border-indigo-600'
                                            : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50',
                                        'inline-flex items-center rounded border px-3 py-1.5 text-sm font-medium'
                                    ]"
                                    v-html="link.label"
                                />
                                <span
                                    v-else
                                    class="inline-flex items-center rounded border border-gray-200 bg-gray-50 px-3 py-1.5 text-sm text-gray-400"
                                    v-html="link.label"
                                />
                            </template>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>
