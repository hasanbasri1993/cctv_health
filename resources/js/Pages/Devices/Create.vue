<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const form = useForm({
    name: '',
    ip_address: '',
    port: 80,
    username: '',
    password: '',
    model: '',
});

function submit() {
    form.post('/devices');
}
</script>

<template>
    <Head title="Add Device" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center gap-3">
                <Link href="/devices" class="rounded-md p-1.5 text-slate-500 transition-colors hover:bg-slate-800/60 hover:text-slate-200">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                </Link>
                <div>
                    <h2 class="text-[17px] font-semibold text-slate-100">Add Device</h2>
                    <p class="text-[13px] text-slate-500">Register a new Hikvision NVR or DVR</p>
                </div>
            </div>
        </template>

        <div class="mx-auto max-w-2xl">
            <div class="rounded-lg border border-white/[0.06] bg-slate-900/60 shadow-[0_1px_2px_rgba(0,0,0,0.4)]">
                <form @submit.prevent="submit" class="divide-y divide-white/[0.06]">
                    <div class="space-y-5 p-6">
                        <!-- Name -->
                        <div>
                            <label for="name" class="block text-[13px] font-medium text-slate-300">Device Name</label>
                            <input
                                id="name"
                                v-model="form.name"
                                type="text"
                                class="mt-1.5 block w-full rounded-md border border-white/[0.08] bg-slate-800/60 px-3 py-2 text-[13px] text-slate-100 placeholder-slate-500 focus:border-cyan-500/50 focus:outline-none focus:ring-1 focus:ring-cyan-500/30"
                                placeholder="e.g. Front Door NVR"
                                required
                                autofocus
                            />
                            <p v-if="form.errors.name" class="mt-1 text-[12px] text-red-400">{{ form.errors.name }}</p>
                        </div>

                        <!-- IP + Port -->
                        <div class="grid grid-cols-3 gap-3">
                            <div class="col-span-2">
                                <label for="ip_address" class="block text-[13px] font-medium text-slate-300">IP Address</label>
                                <input
                                    id="ip_address"
                                    v-model="form.ip_address"
                                    type="text"
                                    class="mt-1.5 block w-full rounded-md border border-white/[0.08] bg-slate-800/60 px-3 py-2 font-mono text-[13px] text-slate-100 placeholder-slate-500 focus:border-cyan-500/50 focus:outline-none focus:ring-1 focus:ring-cyan-500/30"
                                    placeholder="192.168.1.100"
                                    required
                                />
                                <p v-if="form.errors.ip_address" class="mt-1 text-[12px] text-red-400">{{ form.errors.ip_address }}</p>
                            </div>
                            <div>
                                <label for="port" class="block text-[13px] font-medium text-slate-300">Port</label>
                                <input
                                    id="port"
                                    v-model.number="form.port"
                                    type="number"
                                    min="1"
                                    max="65535"
                                    class="mt-1.5 block w-full rounded-md border border-white/[0.08] bg-slate-800/60 px-3 py-2 text-[13px] text-slate-100 placeholder-slate-500 focus:border-cyan-500/50 focus:outline-none focus:ring-1 focus:ring-cyan-500/30"
                                />
                                <p v-if="form.errors.port" class="mt-1 text-[12px] text-red-400">{{ form.errors.port }}</p>
                            </div>
                        </div>

                        <!-- Username -->
                        <div>
                            <label for="username" class="block text-[13px] font-medium text-slate-300">Username</label>
                            <input
                                id="username"
                                v-model="form.username"
                                type="text"
                                class="mt-1.5 block w-full rounded-md border border-white/[0.08] bg-slate-800/60 px-3 py-2 text-[13px] text-slate-100 placeholder-slate-500 focus:border-cyan-500/50 focus:outline-none focus:ring-1 focus:ring-cyan-500/30"
                                placeholder="admin"
                            />
                            <p v-if="form.errors.username" class="mt-1 text-[12px] text-red-400">{{ form.errors.username }}</p>
                        </div>

                        <!-- Password -->
                        <div>
                            <label for="password" class="block text-[13px] font-medium text-slate-300">Password</label>
                            <input
                                id="password"
                                v-model="form.password"
                                type="password"
                                autocomplete="new-password"
                                class="mt-1.5 block w-full rounded-md border border-white/[0.08] bg-slate-800/60 px-3 py-2 text-[13px] text-slate-100 placeholder-slate-500 focus:border-cyan-500/50 focus:outline-none focus:ring-1 focus:ring-cyan-500/30"
                            />
                            <p v-if="form.errors.password" class="mt-1 text-[12px] text-red-400">{{ form.errors.password }}</p>
                        </div>

                        <!-- Model -->
                        <div>
                            <label for="model" class="block text-[13px] font-medium text-slate-300">
                                Model <span class="text-slate-500">(optional)</span>
                            </label>
                            <input
                                id="model"
                                v-model="form.model"
                                type="text"
                                class="mt-1.5 block w-full rounded-md border border-white/[0.08] bg-slate-800/60 px-3 py-2 text-[13px] text-slate-100 placeholder-slate-500 focus:border-cyan-500/50 focus:outline-none focus:ring-1 focus:ring-cyan-500/30"
                                placeholder="e.g. Hikvision DS-7208HQHI-K2"
                            />
                            <p v-if="form.errors.model" class="mt-1 text-[12px] text-red-400">{{ form.errors.model }}</p>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="flex items-center justify-end gap-3 px-6 py-4">
                        <Link
                            href="/devices"
                            class="rounded-md px-3 py-2 text-[13px] font-medium text-slate-400 transition-colors hover:bg-slate-800/60 hover:text-slate-200"
                        >
                            Cancel
                        </Link>
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="inline-flex items-center gap-2 rounded-md bg-cyan-600 px-4 py-2 text-[13px] font-medium text-white transition-colors hover:bg-cyan-500 disabled:opacity-50"
                        >
                            <svg v-if="form.processing" class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                            </svg>
                            {{ form.processing ? 'Saving…' : 'Add Device' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
