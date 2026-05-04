<script setup>
import { ref, computed } from 'vue';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import { Link, usePage } from '@inertiajs/vue3';
import { useColorMode } from '@/Composables/useColorMode';

const { isLight, toggle, init } = useColorMode();
init();

const showingNavigationDropdown = ref(false);
const page = usePage();

const navItems = computed(() => {
    const items = [
        { label: 'Dashboard', href: route('dashboard'), active: route().current('dashboard') },
        { label: 'Devices', href: route('devices.index'), active: route().current('devices.*') },
        { label: 'Alerts', href: route('alerts.index'), active: route().current('alerts.*') },
    ];
    if (page.props.auth.user.role === 'admin') {
        items.push({ label: 'Config', href: route('configuration.index'), active: route().current('configuration.*') });
    }
    return items;
});

// TODO: replace with real notification count from API
const notificationCount = ref(3);

const breadcrumb = computed(() => {
    if (route().current('dashboard')) return 'Dashboard';
    if (route().current('devices.*')) return 'Devices';
    if (route().current('alerts.*')) return 'Alerts';
    if (route().current('configuration.*')) return 'Configuration';
    if (route().current('profile.*')) return 'Profile';
    return '';
});
</script>

<template>
    <div class="min-h-screen bg-slate-950 text-slate-200">
        <!-- Sticky Top Bar -->
        <nav class="sticky top-0 z-40 border-b border-white/[0.06] bg-slate-900/95 backdrop-blur supports-[backdrop-filter]:bg-slate-900/80">
            <div class="mx-auto flex h-14 max-w-[1400px] items-center justify-between px-4 sm:px-6 lg:px-8">
                <!-- Left: Logo + Nav -->
                <div class="flex items-center gap-8">
                    <Link :href="route('dashboard')" class="flex items-center gap-2">
                        <div class="flex h-8 w-8 items-center justify-center rounded-md bg-cyan-500/15 ring-1 ring-cyan-400/30">
                            <ApplicationLogo class="h-5 w-5 fill-current text-cyan-400" />
                        </div>
                        <span class="hidden text-sm font-semibold text-slate-100 sm:inline">CCTV Early Warning</span>
                    </Link>

                    <div class="hidden items-center gap-1 sm:flex">
                        <Link
                            v-for="item in navItems"
                            :key="item.href"
                            :href="item.href"
                            :class="[
                                'rounded-md px-3 py-1.5 text-[13px] font-medium transition-colors',
                                item.active
                                    ? 'bg-slate-800 text-cyan-400'
                                    : 'text-slate-400 hover:bg-slate-800/60 hover:text-slate-100',
                            ]"
                        >
                            {{ item.label }}
                        </Link>
                    </div>
                </div>

                <!-- Center: Breadcrumb (md+) -->
                <div class="hidden items-center gap-2 text-[13px] text-slate-500 md:flex">
                    <span>Pages</span>
                    <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                    <span class="font-medium text-slate-200">{{ breadcrumb }}</span>
                </div>

                <!-- Right: Toggle + Bell + User -->
                <div class="flex items-center gap-2">
                    <!-- Light/Dark toggle -->
                    <button
                        type="button"
                        @click="toggle"
                        class="rounded-md p-2 text-slate-400 transition-colors hover:bg-slate-800/60 hover:text-slate-100"
                        :aria-label="isLight ? 'Switch to dark mode' : 'Switch to light mode'"
                    >
                        <svg v-if="!isLight" class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364-6.364l-.707.707M6.343 17.657l-.707.707M17.657 17.657l-.707-.707M6.343 6.343l-.707-.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/>
                        </svg>
                        <svg v-else class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12.79A9 9 0 1111.21 3 7 7 0 0021 12.79z"/>
                        </svg>
                    </button>

                    <Link
                        :href="route('alerts.index')"
                        class="relative rounded-md p-2 text-slate-400 transition-colors hover:bg-slate-800/60 hover:text-slate-100"
                        aria-label="Notifications"
                    >
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.4-1.4A2 2 0 0118 14.2V11a6 6 0 10-12 0v3.2c0 .5-.2 1-.6 1.4L4 17h5m6 0a3 3 0 11-6 0m6 0H9" />
                        </svg>
                        <span
                            v-if="notificationCount > 0"
                            class="absolute -right-0.5 -top-0.5 inline-flex h-4 min-w-[1rem] items-center justify-center rounded-full bg-cyan-500 px-1 text-[10px] font-semibold text-slate-950"
                        >{{ notificationCount > 9 ? '9+' : notificationCount }}</span>
                    </Link>

                    <div class="hidden sm:block">
                        <Dropdown align="right" width="48">
                            <template #trigger>
                                <button
                                    type="button"
                                    class="inline-flex items-center gap-2 rounded-md px-2 py-1.5 text-[13px] text-slate-300 transition-colors hover:bg-slate-800/60 hover:text-slate-100"
                                >
                                    <span class="flex h-7 w-7 items-center justify-center rounded-full bg-slate-700 text-xs font-semibold text-slate-200">
                                        {{ ($page.props.auth.user.name ?? '?').charAt(0).toUpperCase() }}
                                    </span>
                                    <span class="max-w-[120px] truncate font-medium">{{ $page.props.auth.user.name }}</span>
                                    <svg class="h-3.5 w-3.5 text-slate-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                    </svg>
                                </button>
                            </template>
                            <template #content>
                                <DropdownLink :href="route('profile.edit')">Profile</DropdownLink>
                                <DropdownLink :href="route('logout')" method="post" as="button">Log Out</DropdownLink>
                            </template>
                        </Dropdown>
                    </div>

                    <!-- Mobile hamburger -->
                    <button
                        @click="showingNavigationDropdown = !showingNavigationDropdown"
                        class="rounded-md p-2 text-slate-400 transition-colors hover:bg-slate-800/60 hover:text-slate-100 sm:hidden"
                        aria-label="Menu"
                    >
                        <svg class="h-5 w-5" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                            <path v-if="!showingNavigationDropdown" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                            <path v-else stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Mobile nav -->
            <div v-if="showingNavigationDropdown" class="border-t border-white/[0.06] bg-slate-900 sm:hidden">
                <div class="space-y-1 px-3 py-3">
                    <Link
                        v-for="item in navItems"
                        :key="item.href"
                        :href="item.href"
                        :class="[
                            'block rounded-md px-3 py-2 text-sm font-medium',
                            item.active ? 'bg-slate-800 text-cyan-400' : 'text-slate-300 hover:bg-slate-800/60',
                        ]"
                    >{{ item.label }}</Link>
                </div>
                <div class="border-t border-white/[0.06] px-3 py-3">
                    <p class="text-sm font-medium text-slate-100">{{ $page.props.auth.user.name }}</p>
                    <p class="text-xs text-slate-500">{{ $page.props.auth.user.email }}</p>
                    <div class="mt-2 space-y-1">
                        <Link :href="route('profile.edit')" class="block rounded-md px-3 py-2 text-sm text-slate-300 hover:bg-slate-800/60">Profile</Link>
                        <Link :href="route('logout')" method="post" as="button" class="block w-full rounded-md px-3 py-2 text-left text-sm text-slate-300 hover:bg-slate-800/60">Log Out</Link>
                    </div>
                </div>
            </div>
        </nav>

        <!-- Page Heading -->
        <header v-if="$slots.header" class="border-b border-white/[0.06] bg-slate-900/40">
            <div class="mx-auto max-w-[1400px] px-4 py-4 sm:px-6 lg:px-8">
                <slot name="header" />
            </div>
        </header>

        <!-- Page Content -->
        <main class="mx-auto max-w-[1400px] px-4 py-5 sm:px-6 lg:px-8">
            <slot />
        </main>
    </div>
</template>
