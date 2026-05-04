<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import DeleteUserForm from './Partials/DeleteUserForm.vue';
import UpdatePasswordForm from './Partials/UpdatePasswordForm.vue';
import UpdateProfileInformationForm from './Partials/UpdateProfileInformationForm.vue';
import { Head } from '@inertiajs/vue3';

defineProps({
    mustVerifyEmail: Boolean,
    status: String,
});
</script>

<template>
    <Head title="Profile" />

    <AuthenticatedLayout>
        <template #header>
            <div>
                <h2 class="text-[17px] font-semibold text-slate-100">Profile</h2>
                <p class="text-[13px] text-slate-500">Manage your account information and security</p>
            </div>
        </template>

        <div class="mx-auto max-w-2xl space-y-4">
            <div class="rounded-lg border border-white/[0.06] bg-slate-900/60 p-6 shadow-[0_1px_2px_rgba(0,0,0,0.4)]">
                <UpdateProfileInformationForm :must-verify-email="mustVerifyEmail" :status="status" />
            </div>
            <div class="rounded-lg border border-white/[0.06] bg-slate-900/60 p-6 shadow-[0_1px_2px_rgba(0,0,0,0.4)]">
                <UpdatePasswordForm />
            </div>
            <div class="rounded-lg border border-red-500/20 bg-slate-900/60 p-6 shadow-[0_1px_2px_rgba(0,0,0,0.4)]">
                <DeleteUserForm />
            </div>
        </div>
    </AuthenticatedLayout>
</template>
