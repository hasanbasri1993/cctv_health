<script setup>
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <div class="flex min-h-screen flex-col items-center justify-center bg-slate-950 px-4 py-12">
        <!-- Brand -->
        <Link href="/" class="mb-8 flex flex-col items-center gap-3">
            <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-cyan-500/15 ring-1 ring-cyan-400/30">
                <ApplicationLogo class="h-7 w-7 fill-current text-cyan-400" />
            </div>
            <span class="text-[15px] font-semibold text-slate-200">CCTV Early Warning</span>
        </Link>

        <!-- Card -->
        <div class="w-full max-w-md rounded-xl border border-white/[0.06] bg-slate-900/80 px-7 py-8 shadow-[0_4px_32px_rgba(0,0,0,0.5)] backdrop-blur">
            <slot />
        </div>
    </div>
</template>
