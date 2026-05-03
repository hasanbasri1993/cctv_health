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
        list = list.filter(d => d.name.toLowerCase().includes(q) || d.ip_address.includes(q) || (d.model ?? '').toLowerCase().includes(q));
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
    if (!confirm(`Delete device "${device.name}"? This cannot be undone.`)) return;
    router.delete(`/devices/${device.id}`);
}
</script>

<template>
    <Head title="Devices" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">Devices</h2>
                <Link
                    v-if="canManage"
                    href="/devices/create"
                    class="inline-flex items-center gap-1.5 rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                >
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Add Device
                </Link>
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 space-y-4">

                <!-- Quick stats -->
                <div class="grid grid-cols-3 gap-4">
                    <div class="rounded-lg bg-white p-4 shadow-sm text-center">
                        <div class="text-2xl font-bold text-gray-900">{{ stats.total }}</div>
                        <div class="text-xs text-gray-500 mt-1">Total</div>
                    </div>
                    <div class="rounded-lg bg-white p-4 shadow-sm text-center">
                        <div class="text-2xl font-bold text-green-600">{{ stats.online }}</div>
                        <div class="text-xs text-gray-500 mt-1">Online</div>
                    </div>
                    <div class="rounded-lg bg-white p-4 shadow-sm text-center">
                        <div class="text-2xl font-bold text-red-600">{{ stats.offline }}</div>
                        <div class="text-xs text-gray-500 mt-1">Offline</div>
                    </div>
                </div>

                <!-- Search & filter -->
                <div class="flex gap-3">
                    <input v-model="search" type="text" placeholder="Search by name, IP, or model…"
                        class="flex-1 rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                    <select v-model="statusFilter" class="rounded-md border-gray-300 text-sm shadow-sm">
                        <option value="">All statuses</option>
                        <option value="online">Online</option>
                        <option value="offline">Offline</option>
                        <option value="unknown">Unknown</option>
                    </select>
                </div>

                <div class="overflow-hidden rounded-lg bg-white shadow">
                    <div v-if="filteredDevices.length === 0" class="p-12 text-center">
                        <svg class="mx-auto h-12 w-12 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                        </svg>
                        <p class="mt-3 text-sm text-gray-500">No devices have been added yet.</p>
                        <Link v-if="canManage" href="/devices/create" class="mt-3 inline-block text-sm font-medium text-indigo-600 hover:text-indigo-500">
                            Add your first device &rarr;
                        </Link>
                    </div>

                    <div v-else class="overflow-x-auto">

                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">Name</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">IP Address</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">Status</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">Model</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">Last Seen</th>
                                    <th class="relative px-6 py-3"><span class="sr-only">Actions</span></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 bg-white">
                                <tr v-for="device in filteredDevices" :key="device.id" class="hover:bg-gray-50">
                                    <td class="whitespace-nowrap px-6 py-4">
                                        <Link :href="`/devices/${device.id}`" class="font-medium text-gray-900 hover:text-indigo-600">
                                            {{ device.name }}
                                        </Link>
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-4 font-mono text-sm text-gray-600">
                                        {{ device.ip_address }}
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-4">
                                        <StatusIndicator :status="device.status" />
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-600">
                                        {{ device.model || '—' }}
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-600">
                                        {{ formatLastSeen(device.last_seen_at) }}
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-4 text-right text-sm">
                                        <div class="flex items-center justify-end gap-3">
                                            <Link
                                                :href="`/devices/${device.id}`"
                                                class="text-indigo-600 hover:text-indigo-500"
                                            >
                                                View
                                            </Link>
                                            <Link
                                                v-if="canManage"
                                                :href="`/devices/${device.id}/edit`"
                                                class="text-gray-600 hover:text-gray-900"
                                            >
                                                Edit
                                            </Link>
                                            <button
                                                v-if="canManage"
                                                type="button"
                                                @click="deleteDevice(device)"
                                                class="text-red-600 hover:text-red-500"
                                            >
                                                Delete
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

