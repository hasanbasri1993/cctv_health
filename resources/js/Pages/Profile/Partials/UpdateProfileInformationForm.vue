<script setup>
import { Link, useForm, usePage } from '@inertiajs/vue3';

defineProps({
    mustVerifyEmail: Boolean,
    status: String,
});

const user = usePage().props.auth.user;

const form = useForm({
    name: user.name,
    email: user.email,
});
</script>

<template>
    <section>
        <h3 class="text-[15px] font-semibold text-slate-100">Profile Information</h3>
        <p class="mt-0.5 text-[13px] text-slate-500">Update your name and email address.</p>

        <form @submit.prevent="form.patch(route('profile.update'))" class="mt-5 space-y-4">
            <div>
                <label for="name" class="block text-[13px] font-medium text-slate-300">Name</label>
                <input
                    id="name"
                    v-model="form.name"
                    type="text"
                    required
                    autofocus
                    autocomplete="name"
                    class="mt-1.5 block w-full rounded-md border border-white/[0.08] bg-slate-800/60 px-3 py-2 text-[13px] text-slate-100 focus:border-cyan-500/50 focus:outline-none focus:ring-1 focus:ring-cyan-500/30"
                />
                <p v-if="form.errors.name" class="mt-1 text-[12px] text-red-400">{{ form.errors.name }}</p>
            </div>

            <div>
                <label for="email" class="block text-[13px] font-medium text-slate-300">Email</label>
                <input
                    id="email"
                    v-model="form.email"
                    type="email"
                    required
                    autocomplete="username"
                    class="mt-1.5 block w-full rounded-md border border-white/[0.08] bg-slate-800/60 px-3 py-2 text-[13px] text-slate-100 focus:border-cyan-500/50 focus:outline-none focus:ring-1 focus:ring-cyan-500/30"
                />
                <p v-if="form.errors.email" class="mt-1 text-[12px] text-red-400">{{ form.errors.email }}</p>
            </div>

            <div v-if="mustVerifyEmail && user.email_verified_at === null" class="rounded-md bg-amber-500/10 px-4 py-3 text-[13px] ring-1 ring-amber-500/30">
                <span class="text-amber-400">Email unverified. </span>
                <Link :href="route('verification.send')" method="post" as="button" class="text-amber-300 underline hover:text-amber-200">
                    Resend verification email.
                </Link>
                <div v-if="status === 'verification-link-sent'" class="mt-1 text-emerald-400">
                    Verification link sent.
                </div>
            </div>

            <div class="flex items-center gap-4 pt-1">
                <button
                    type="submit"
                    :disabled="form.processing"
                    class="inline-flex items-center gap-2 rounded-md bg-cyan-600 px-4 py-2 text-[13px] font-medium text-white transition-colors hover:bg-cyan-500 disabled:opacity-50"
                >
                    <svg v-if="form.processing" class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                    </svg>
                    Save
                </button>
                <Transition enter-active-class="transition ease-in-out" enter-from-class="opacity-0" leave-active-class="transition ease-in-out" leave-to-class="opacity-0">
                    <span v-if="form.recentlySuccessful" class="flex items-center gap-1.5 text-[13px] text-emerald-400">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        Saved
                    </span>
                </Transition>
            </div>
        </form>
    </section>
</template>
