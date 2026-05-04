<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';

const form = useForm({ password: '' });

const submit = () => {
    form.post(route('password.confirm'), {
        onFinish: () => form.reset(),
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="Confirm Password" />

        <h1 class="mb-1 text-[18px] font-semibold text-slate-100">Confirm access</h1>
        <p class="mb-6 text-[13px] text-slate-500">This area is protected. Enter your password to continue.</p>

        <form @submit.prevent="submit" class="space-y-4">
            <div>
                <label for="password" class="block text-[13px] font-medium text-slate-300">Password</label>
                <input
                    id="password"
                    v-model="form.password"
                    type="password"
                    required
                    autofocus
                    autocomplete="current-password"
                    class="mt-1.5 block w-full rounded-md border border-white/[0.08] bg-slate-800/60 px-3 py-2 text-[13px] text-slate-100 focus:border-cyan-500/50 focus:outline-none focus:ring-1 focus:ring-cyan-500/30"
                />
                <p v-if="form.errors.password" class="mt-1 text-[12px] text-red-400">{{ form.errors.password }}</p>
            </div>

            <button
                type="submit"
                :disabled="form.processing"
                class="flex w-full items-center justify-center gap-2 rounded-md bg-cyan-600 py-2.5 text-[13px] font-semibold text-white transition-colors hover:bg-cyan-500 disabled:opacity-50"
            >
                <svg v-if="form.processing" class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                </svg>
                {{ form.processing ? 'Confirming…' : 'Confirm' }}
            </button>
        </form>
    </GuestLayout>
</template>
