<script setup>
import { computed, ref } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import StatusIndicator from '@/Components/StatusIndicator.vue';

const props = defineProps({
    devices: { type: Array, default: () => [] },
    auth: { type: Object, default: () => ({}) },
});

const page = usePage();
const search = ref('');
const statusFilter = ref('');

const filteredDevices = computed(() => {
    let list = props.devices;
    if (search.value) {
        const q = search.value.toLowerCase();
        list = list.filter(d =>
            d.name.toLowerCase().includes(q) ||
            d.ip_address.includes(q) ||
            (d.model ?? '').toLowerCase().includes(q),
        );
    }
    if (statusFilter.value) {
        list = list.filter(d => d.status === statusFilter.value);
    }
    return list;
});

const stats = computed(() => ({
    total: props.devices.length,
    online: props.devices.filter(d => d.status === 'online').length,
    offline: props.devices.filter(d => d.status === 'offline').length,
    alerts: props.devices.reduce((s, d) => s + (d.alerts_count ?? 0), 0),
}));

const canManage = computed(() => {
    const role = page.props.auth?.user?.role ?? props.auth?.user?.role;
    return role === 'admin' || role === 'operator';
});

function formatLastSeen(dateStr) {
    if (!dateStr) return '—';
    return new Date(dateStr).toLocaleString();
}

function deleteDevice(device) {
    if (!confirm(`Delete "${device.name}"? This cannot be undone.`)) return;
    router.delete(`/devices/${device.id}`);
}
</script>

<template>
    <Head title="Devices" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-[17px] font-semibold text-slate-100">Devices</h2>
                    <p class="text-[13px] text-slate-500">{{ stats.total }} registered device{{ stats.total !== 1 ? 's' : '' }}</p>
                </div>
                <div class="flex items-center gap-2">
                    <Link
                        v-if="canManage"
                        href="/devices/export/frigate-config"
                        class="inline-flex items-center gap-1.5 rounded-md border border-white/[0.08] bg-slate-800/60 px-3 py-2 text-[13px] font-medium text-slate-300 shadow-sm transition-all hover:bg-slate-700/60 hover:text-slate-100"
                    >
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                        </svg>
                        Frigate Config
                    </Link>
                    <Link
                        v-if="canManage"
                        href="/devices/create"
                        class="inline-flex items-center gap-1.5 rounded-md bg-cyan-600 px-3 py-2 text-[13px] font-medium text-white shadow-sm transition-colors hover:bg-cyan-500"
                    >
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        Add Device
                    </Link>
                </div>
            </div>
        </template>

        <!-- KPI row -->
        <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
            <div class="rounded-lg border border-white/[0.06] bg-slate-900/60 p-4">
                <p class="text-[11px] font-medium uppercase tracking-wider text-slate-500">Total</p>
                <p class="mt-1 text-2xl font-semibold tabular-nums text-slate-100">{{ stats.total }}</p>
            </div>
            <div class="rounded-lg border border-white/[0.06] bg-slate-900/60 p-4">
                <p class="text-[11px] font-medium uppercase tracking-wider text-slate-500">Online</p>
                <p class="mt-1 text-2xl font-semibold tabular-nums text-emerald-400">{{ stats.online }}</p>
            </div>
            <div class="rounded-lg border border-white/[0.06] bg-slate-900/60 p-4">
                <p class="text-[11px] font-medium uppercase tracking-wider text-slate-500">Offline</p>
                <p class="mt-1 text-2xl font-semibold tabular-nums text-red-400">{{ stats.offline }}</p>
            </div>
            <div class="rounded-lg border border-white/[0.06] bg-slate-900/60 p-4">
                <p class="text-[11px] font-medium uppercase tracking-wider text-slate-500">Active Alerts</p>
                <p class="mt-1 text-2xl font-semibold tabular-nums" :class="stats.alerts > 0 ? 'text-amber-400' : 'text-slate-300'">{{ stats.alerts }}</p>
            </div>
        </div>

        <!-- Search & filter -->
        <div class="mt-3 flex gap-2">
            <div class="relative flex-1">
                <svg class="absolute left-2.5 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
                <input
                    v-model="search"
                    type="text"
                    placeholder="Search by name, IP, or model…"
                    class="w-full rounded-md border border-white/[0.08] bg-slate-800/60 py-2 pl-9 pr-3 text-[13px] text-slate-100 placeholder-slate-500 focus:border-cyan-500/50 focus:outline-none focus:ring-1 focus:ring-cyan-500/30"
                />
            </div>
            <select
                v-model="statusFilter"
                class="rounded-md border border-white/[0.08] bg-slate-800/60 px-3 py-2 text-[13px] text-slate-300 focus:border-cyan-500/50 focus:outline-none focus:ring-1 focus:ring-cyan-500/30"
            >
                <option value="">All statuses</option>
                <option value="online">Online</option>
                <option value="offline">Offline</option>
                <option value="unknown">Unknown</option>
            </select>
        </div>

        <!-- Table -->
        <div class="mt-3 rounded-lg border border-white/[0.06] bg-slate-900/60 shadow-[0_1px_2px_rgba(0,0,0,0.4)]">
            <!-- Empty state -->
            <div v-if="filteredDevices.length === 0" class="py-16 text-center">
                <svg class="mx-auto h-10 w-10 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                </svg>
                <p class="mt-3 text-[13px] text-slate-500">
                    {{ search || statusFilter ? 'No devices match your filters.' : 'No devices added yet.' }}
                </p>
                <Link v-if="canManage && !search && !statusFilter" href="/devices/create" class="mt-3 inline-block text-[13px] font-medium text-cyan-400 hover:text-cyan-300">
                    Add your first device →
                </Link>
            </div>

            <!-- Table (desktop) -->
            <div v-else class="hidden lg:block">
                <table class="w-full text-[13px]">
                    <thead>
                        <tr class="border-b border-white/[0.06] text-left text-[11px] uppercase tracking-wider text-slate-500">
                            <th class="px-5 py-3 font-medium">Name</th>
                            <th class="px-5 py-3 font-medium">IP Address</th>
                            <th class="px-5 py-3 font-medium">Status</th>
                            <th class="px-5 py-3 font-medium">Model</th>
                            <th class="px-5 py-3 font-medium">Last Seen</th>
                            <th class="px-5 py-3 text-right font-medium">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/[0.04]">
                        <tr
                            v-for="device in filteredDevices"
                            :key="device.id"
                            class="group hover:bg-slate-800/40"
                        >
                            <td class="whitespace-nowrap px-5 py-3">
                                <Link :href="`/devices/${device.id}`" class="font-medium text-slate-100 hover:text-cyan-400">
                                    {{ device.name }}
                                </Link>
                            </td>
                            <td class="whitespace-nowrap px-5 py-3 font-mono">
                                <a :href="`http://${device.ip_address}`" target="_blank" rel="noopener noreferrer" class="text-cyan-400 hover:text-cyan-300 hover:underline">{{ device.ip_address }}</a>
                            </td>
                            <td class="whitespace-nowrap px-5 py-3">
                                <StatusIndicator :status="device.status" />
                            </td>
                            <td class="whitespace-nowrap px-5 py-3 text-slate-400">{{ device.model || '—' }}</td>
                            <td class="whitespace-nowrap px-5 py-3 tabular-nums text-slate-500">{{ formatLastSeen(device.last_seen_at) }}</td>
                            <td class="whitespace-nowrap px-5 py-3 text-right">
                                <div class="flex items-center justify-end gap-3">
                                    <Link :href="`/devices/${device.id}`" class="text-cyan-400 hover:text-cyan-300">View</Link>
                                    <Link v-if="canManage" :href="`/devices/${device.id}/edit`" class="text-slate-400 hover:text-slate-200">Edit</Link>
                                    <button v-if="canManage" type="button" @click="deleteDevice(device)" class="text-red-500 hover:text-red-400">Delete</button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Cards (mobile) -->
            <div v-if="filteredDevices.length > 0" class="lg:hidden divide-y divide-white/[0.04]">
                <div
                    v-for="device in filteredDevices"
                    :key="device.id"
                    class="p-4"
                >
                    <div class="flex items-start justify-between gap-3 mb-2">
                        <div class="min-w-0">
                            <Link :href="`/devices/${device.id}`" class="block truncate font-medium text-slate-100 hover:text-cyan-400 text-[14px]">
                                {{ device.name }}
                            </Link>
                            <a :href="`http://${device.ip_address}`" target="_blank" rel="noopener noreferrer" class="font-mono text-[12px] text-cyan-400 hover:text-cyan-300 hover:underline mt-0.5 block">{{ device.ip_address }}</a>
                        </div>
                        <div class="shrink-0">
                            <StatusIndicator :status="device.status" />
                        </div>
                    </div>
                    <div class="flex flex-wrap items-center gap-x-4 gap-y-1 text-[12px] text-slate-400 mb-3">
                        <span v-if="device.model">{{ device.model }}</span>
                        <span class="text-slate-600">Last seen: <span class="text-slate-500 tabular-nums">{{ formatLastSeen(device.last_seen_at) }}</span></span>
                    </div>
                    <div class="flex items-center gap-4">
                        <Link :href="`/devices/${device.id}`" class="text-[13px] text-cyan-400 hover:text-cyan-300">View</Link>
                        <Link v-if="canManage" :href="`/devices/${device.id}/edit`" class="text-[13px] text-slate-400 hover:text-slate-200">Edit</Link>
                        <button v-if="canManage" type="button" @click="deleteDevice(device)" class="text-[13px] text-red-500 hover:text-red-400">Delete</button>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
