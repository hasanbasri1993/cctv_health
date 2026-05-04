<script setup>
import { computed } from 'vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps({ status: String });

const form = useForm({});
const submit = () => { form.post(route('verification.send')); };
const verificationLinkSent = computed(() => props.status === 'verification-link-sent');
</script>

<template>
    <GuestLayout>
        <Head title="Email Verification" />

        <div class="mb-3 flex h-12 w-12 items-center justify-center rounded-full bg-cyan-500/10 ring-1 ring-cyan-500/30">
            <svg class="h-6 w-6 text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
            </svg>
        </div>

        <h1 class="mb-1 text-[18px] font-semibold text-slate-100">Verify your email</h1>
        <p class="mb-6 text-[13px] text-slate-500">
            We sent a verification link to your email. Click it to activate your account. Didn't receive it?
        </p>

        <div v-if="verificationLinkSent" class="mb-5 rounded-md bg-emerald-500/10 px-4 py-3 text-[13px] font-medium text-emerald-400 ring-1 ring-emerald-500/30">
            New verification link sent to your email address.
        </div>

        <form @submit.prevent="submit">
            <button
                type="submit"
                :disabled="form.processing"
                class="flex w-full items-center justify-center gap-2 rounded-md bg-cyan-600 py-2.5 text-[13px] font-semibold text-white transition-colors hover:bg-cyan-500 disabled:opacity-50"
            >
                <svg v-if="form.processing" class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                </svg>
                {{ form.processing ? 'Sending…' : 'Resend Verification Email' }}
            </button>
        </form>

        <div class="mt-4 text-center">
            <Link :href="route('logout')" method="post" as="button" class="text-[13px] text-slate-500 hover:text-slate-300">
                Log out
            </Link>
        </div>
    </GuestLayout>
</template>
