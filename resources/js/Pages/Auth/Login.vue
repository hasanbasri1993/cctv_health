<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

defineProps({
    canResetPassword: Boolean,
    status: String,
});

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="Log in" />

        <h1 class="mb-1 text-[18px] font-semibold text-slate-100">Sign in</h1>
        <p class="mb-6 text-[13px] text-slate-500">Access your monitoring dashboard</p>

        <div v-if="status" class="mb-5 rounded-md bg-emerald-500/10 px-4 py-3 text-[13px] font-medium text-emerald-400 ring-1 ring-emerald-500/30">
            {{ status }}
        </div>

        <form @submit.prevent="submit" class="space-y-4">
            <div>
                <label for="email" class="block text-[13px] font-medium text-slate-300">Email</label>
                <input
                    id="email"
                    v-model="form.email"
                    type="email"
                    required
                    autofocus
                    autocomplete="username"
                    class="mt-1.5 block w-full rounded-md border border-white/[0.08] bg-slate-800/60 px-3 py-2 text-[13px] text-slate-100 placeholder-slate-500 focus:border-cyan-500/50 focus:outline-none focus:ring-1 focus:ring-cyan-500/30"
                />
                <p v-if="form.errors.email" class="mt-1 text-[12px] text-red-400">{{ form.errors.email }}</p>
            </div>

            <div>
                <div class="flex items-center justify-between">
                    <label for="password" class="block text-[13px] font-medium text-slate-300">Password</label>
                    <Link
                        v-if="canResetPassword"
                        :href="route('password.request')"
                        class="text-[12px] text-cyan-400 hover:text-cyan-300"
                    >Forgot password?</Link>
                </div>
                <input
                    id="password"
                    v-model="form.password"
                    type="password"
                    required
                    autocomplete="current-password"
                    class="mt-1.5 block w-full rounded-md border border-white/[0.08] bg-slate-800/60 px-3 py-2 text-[13px] text-slate-100 placeholder-slate-500 focus:border-cyan-500/50 focus:outline-none focus:ring-1 focus:ring-cyan-500/30"
                />
                <p v-if="form.errors.password" class="mt-1 text-[12px] text-red-400">{{ form.errors.password }}</p>
            </div>

            <label class="flex cursor-pointer items-center gap-2.5">
                <input
                    type="checkbox"
                    v-model="form.remember"
                    class="h-4 w-4 rounded border-white/[0.15] bg-slate-800 text-cyan-500 focus:ring-cyan-500/30 focus:ring-offset-slate-900"
                />
                <span class="text-[13px] text-slate-400">Remember me</span>
            </label>

            <button
                type="submit"
                :disabled="form.processing"
                class="mt-2 flex w-full items-center justify-center gap-2 rounded-md bg-cyan-600 py-2.5 text-[13px] font-semibold text-white transition-colors hover:bg-cyan-500 disabled:opacity-50"
            >
                <svg v-if="form.processing" class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                </svg>
                {{ form.processing ? 'Signing in…' : 'Sign in' }}
            </button>
        </form>
    </GuestLayout>
</template>
