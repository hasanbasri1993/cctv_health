<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

defineProps({ status: String });

const form = useForm({ email: '' });

const submit = () => { form.post(route('password.email')); };
</script>

<template>
    <GuestLayout>
        <Head title="Forgot Password" />

        <h1 class="mb-1 text-[18px] font-semibold text-slate-100">Reset password</h1>
        <p class="mb-6 text-[13px] text-slate-500">Enter your email and we'll send a reset link.</p>

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

            <button
                type="submit"
                :disabled="form.processing"
                class="flex w-full items-center justify-center gap-2 rounded-md bg-cyan-600 py-2.5 text-[13px] font-semibold text-white transition-colors hover:bg-cyan-500 disabled:opacity-50"
            >
                <svg v-if="form.processing" class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                </svg>
                {{ form.processing ? 'Sending…' : 'Send Reset Link' }}
            </button>

            <p class="text-center text-[13px] text-slate-500">
                <Link :href="route('login')" class="text-cyan-400 hover:text-cyan-300">← Back to sign in</Link>
            </p>
        </form>
    </GuestLayout>
</template>
