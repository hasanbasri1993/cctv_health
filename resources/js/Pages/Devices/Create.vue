<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';

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
                <Link href="/devices" class="text-gray-400 hover:text-gray-600">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                </Link>
                <h2 class="text-xl font-semibold leading-tight text-gray-800">Add Device</h2>
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-2xl px-4 sm:px-6 lg:px-8">
                <div class="overflow-hidden rounded-lg bg-white shadow">
                    <form @submit.prevent="submit" class="p-6 space-y-6">

                        <!-- Name -->
                        <div>
                            <InputLabel for="name" value="Device Name" />
                            <TextInput
                                id="name"
                                v-model="form.name"
                                type="text"
                                class="mt-1 block w-full"
                                placeholder="e.g. Front Door DVR"
                                required
                                autofocus
                            />
                            <InputError :message="form.errors.name" class="mt-1" />
                        </div>

                        <!-- IP Address + Port -->
                        <div class="grid grid-cols-3 gap-4">
                            <div class="col-span-2">
                                <InputLabel for="ip_address" value="IP Address" />
                                <TextInput
                                    id="ip_address"
                                    v-model="form.ip_address"
                                    type="text"
                                    class="mt-1 block w-full font-mono"
                                    placeholder="192.168.1.100"
                                    required
                                />
                                <InputError :message="form.errors.ip_address" class="mt-1" />
                            </div>
                            <div>
                                <InputLabel for="port" value="Port" />
                                <TextInput
                                    id="port"
                                    v-model.number="form.port"
                                    type="number"
                                    class="mt-1 block w-full"
                                    min="1"
                                    max="65535"
                                />
                                <InputError :message="form.errors.port" class="mt-1" />
                            </div>
                        </div>

                        <!-- Username -->
                        <div>
                            <InputLabel for="username" value="Username" />
                            <TextInput
                                id="username"
                                v-model="form.username"
                                type="text"
                                class="mt-1 block w-full"
                                placeholder="admin"
                            />
                            <InputError :message="form.errors.username" class="mt-1" />
                        </div>

                        <!-- Password -->
                        <div>
                            <InputLabel for="password" value="Password" />
                            <TextInput
                                id="password"
                                v-model="form.password"
                                type="password"
                                class="mt-1 block w-full"
                                autocomplete="new-password"
                            />
                            <InputError :message="form.errors.password" class="mt-1" />
                        </div>

                        <!-- Model -->
                        <div>
                            <InputLabel for="model" value="Model (optional)" />
                            <TextInput
                                id="model"
                                v-model="form.model"
                                type="text"
                                class="mt-1 block w-full"
                                placeholder="e.g. Hikvision DS-7208HQHI-K2"
                            />
                            <InputError :message="form.errors.model" class="mt-1" />
                        </div>

                        <!-- Actions -->
                        <div class="flex items-center justify-end gap-3 border-t border-gray-100 pt-4">
                            <Link href="/devices">
                                <SecondaryButton type="button">Cancel</SecondaryButton>
                            </Link>
                            <PrimaryButton :disabled="form.processing">
                                {{ form.processing ? 'Saving...' : 'Add Device' }}
                            </PrimaryButton>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
