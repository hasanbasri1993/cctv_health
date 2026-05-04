<script setup>
import { Head, Link } from '@inertiajs/vue3';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';

defineProps({
    canLogin: { type: Boolean },
    canRegister: { type: Boolean },
});
</script>

<template>
    <Head title="CCTV Early Warning" />

    <div class="flex min-h-screen flex-col items-center justify-center bg-slate-950 px-4 py-12">
        <!-- Brand -->
        <div class="mb-10 flex flex-col items-center gap-4 text-center">
            <div class="flex h-16 w-16 items-center justify-center rounded-2xl bg-cyan-500/15 ring-2 ring-cyan-400/30">
                <ApplicationLogo class="h-9 w-9 fill-current text-cyan-400" />
            </div>
            <div>
                <h1 class="text-[22px] font-bold text-slate-100">CCTV Early Warning</h1>
                <p class="mt-1 text-[13px] text-slate-500">Real-time monitoring for Hikvision DVR fleets</p>
            </div>
        </div>

        <!-- Feature cards -->
        <div class="mb-10 grid max-w-3xl grid-cols-1 gap-4 sm:grid-cols-3">
            <div class="rounded-xl border border-white/[0.06] bg-slate-900/80 p-5 text-center backdrop-blur">
                <div class="mb-3 flex justify-center">
                    <div class="rounded-lg bg-cyan-500/15 p-2.5">
                        <svg class="h-5 w-5 text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                        </svg>
                    </div>
                </div>
                <h3 class="mb-1 text-[13px] font-semibold text-slate-200">Live Monitoring</h3>
                <p class="text-[12px] text-slate-500">Track online status, video loss, and storage health across all DVRs in real time.</p>
            </div>

            <div class="rounded-xl border border-white/[0.06] bg-slate-900/80 p-5 text-center backdrop-blur">
                <div class="mb-3 flex justify-center">
                    <div class="rounded-lg bg-amber-500/15 p-2.5">
                        <svg class="h-5 w-5 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 17h5l-1.405-1.405A2 2 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                        </svg>
                    </div>
                </div>
                <h3 class="mb-1 text-[13px] font-semibold text-slate-200">Alert Center</h3>
                <p class="text-[12px] text-slate-500">Receive and acknowledge alerts for camera offline, video loss, and storage faults.</p>
            </div>

            <div class="rounded-xl border border-white/[0.06] bg-slate-900/80 p-5 text-center backdrop-blur">
                <div class="mb-3 flex justify-center">
                    <div class="rounded-lg bg-emerald-500/15 p-2.5">
                        <svg class="h-5 w-5 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"/>
                        </svg>
                    </div>
                </div>
                <h3 class="mb-1 text-[13px] font-semibold text-slate-200">DVR Management</h3>
                <p class="text-[12px] text-slate-500">Add and manage Hikvision devices with channel and storage detail views.</p>
            </div>
        </div>

        <!-- Auth actions -->
        <div v-if="canLogin" class="flex items-center gap-3">
            <template v-if="$page.props.auth.user">
                <Link
                    :href="route('dashboard')"
                    class="rounded-lg bg-cyan-600 px-5 py-2.5 text-[13px] font-semibold text-white transition-colors hover:bg-cyan-500"
                >
                    Go to Dashboard
                </Link>
            </template>
            <template v-else>
                <Link
                    :href="route('login')"
                    class="rounded-lg border border-white/10 bg-slate-800/60 px-5 py-2.5 text-[13px] font-medium text-slate-300 transition-colors hover:bg-slate-700 hover:text-slate-100"
                >
                    Sign in
                </Link>
                <Link
                    v-if="canRegister"
                    :href="route('register')"
                    class="rounded-lg bg-cyan-600 px-5 py-2.5 text-[13px] font-semibold text-white transition-colors hover:bg-cyan-500"
                >
                    Get Started
                </Link>
            </template>
        </div>

        <!-- Footer -->
        <p class="mt-12 text-[12px] text-slate-600">
            CCTV Early Warning Monitor — Powered by Laravel + Vue 3
        </p>
    </div>
</template>
