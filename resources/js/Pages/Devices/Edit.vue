<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';

const props = defineProps({
    device: {
        type: Object,
        required: true,
    },
});

const form = useForm({
    name: props.device.name ?? '',
    ip_address: props.device.ip_address ?? '',
    port: props.device.port ?? 80,
    username: props.device.username ?? '',
    password: '',
    model: props.device.model ?? '',
});

function submit() {
    form.put(`/devices/${props.device.id}`);
}
</script>

<template>
    <Head :title="`Edit: ${device.name}`" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center gap-3">
                <Link :href="`/devices/${device.id}`" class="text-gray-400 hover:text-gray-600">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                </Link>
                <h2 class="text-xl font-semibold leading-tight text-gray-800">Edit: {{ device.name }}</h2>
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
                            />
                            <InputError :message="form.errors.username" class="mt-1" />
                        </div>

                        <!-- Password -->
                        <div>
                            <InputLabel for="password" value="New Password" />
                            <TextInput
                                id="password"
                                v-model="form.password"
                                type="password"
                                class="mt-1 block w-full"
                                autocomplete="new-password"
                                placeholder="Leave blank to keep current password"
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
                            />
                            <InputError :message="form.errors.model" class="mt-1" />
                        </div>

                        <!-- Actions -->
                        <div class="flex items-center justify-end gap-3 border-t border-gray-100 pt-4">
                            <Link :href="`/devices/${device.id}`">
                                <SecondaryButton type="button">Cancel</SecondaryButton>
                            </Link>
                            <PrimaryButton :disabled="form.processing">
                                {{ form.processing ? 'Saving...' : 'Save Changes' }}
                            </PrimaryButton>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
