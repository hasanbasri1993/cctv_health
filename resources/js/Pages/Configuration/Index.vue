<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';

const props = defineProps({ config: Object });

const form = useForm({
    polling_channel_interval: props.config.polling_channel_interval,
    polling_storage_interval: props.config.polling_storage_interval,
    polling_device_interval: props.config.polling_device_interval,
    notification_reminder_interval: props.config.notification_reminder_interval,
    telegram_bot_token: props.config.telegram_bot_token === '***configured***' ? '' : (props.config.telegram_bot_token ?? ''),
    telegram_chat_id: props.config.telegram_chat_id ?? '',
    mail_from_address: props.config.mail_from_address ?? '',
    alert_email_recipients: props.config.alert_email_recipients ?? '',
});

function submit() {
    form.post('/configuration');
}
</script>

<template>
    <Head title="Configuration" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">System Configuration</h2>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8 space-y-6">

                <form @submit.prevent="submit" class="space-y-6">

                    <!-- Polling Intervals -->
                    <div class="rounded-lg bg-white p-6 shadow-sm">
                        <h3 class="mb-4 text-base font-semibold text-gray-900">Polling Intervals (minutes)</h3>
                        <div class="grid grid-cols-3 gap-4">
                            <div>
                                <InputLabel for="ch_interval" value="Channel Status" />
                                <TextInput id="ch_interval" v-model.number="form.polling_channel_interval" type="number" min="1" max="60" class="mt-1 block w-full" />
                                <InputError :message="form.errors.polling_channel_interval" class="mt-1" />
                            </div>
                            <div>
                                <InputLabel for="dev_interval" value="Device Health" />
                                <TextInput id="dev_interval" v-model.number="form.polling_device_interval" type="number" min="1" max="60" class="mt-1 block w-full" />
                                <InputError :message="form.errors.polling_device_interval" class="mt-1" />
                            </div>
                            <div>
                                <InputLabel for="st_interval" value="Storage Status" />
                                <TextInput id="st_interval" v-model.number="form.polling_storage_interval" type="number" min="1" max="60" class="mt-1 block w-full" />
                                <InputError :message="form.errors.polling_storage_interval" class="mt-1" />
                            </div>
                        </div>
                        <div class="mt-4">
                            <InputLabel for="reminder_interval" value="Notification Reminder Interval (minutes)" />
                            <TextInput id="reminder_interval" v-model.number="form.notification_reminder_interval" type="number" min="5" max="1440" class="mt-1 block w-48" />
                            <InputError :message="form.errors.notification_reminder_interval" class="mt-1" />
                            <p class="mt-1 text-xs text-gray-500">How often to resend notifications for unresolved alerts.</p>
                        </div>
                    </div>

                    <!-- Telegram -->
                    <div class="rounded-lg bg-white p-6 shadow-sm">
                        <h3 class="mb-4 text-base font-semibold text-gray-900">Telegram Notifications</h3>
                        <div class="space-y-4">
                            <div>
                                <InputLabel for="tg_token" value="Bot Token" />
                                <TextInput id="tg_token" v-model="form.telegram_bot_token" type="password" class="mt-1 block w-full font-mono" :placeholder="config.telegram_bot_token === '***configured***' ? 'Leave blank to keep current' : 'Enter bot token'" autocomplete="off" />
                                <InputError :message="form.errors.telegram_bot_token" class="mt-1" />
                            </div>
                            <div>
                                <InputLabel for="tg_chat" value="Chat ID" />
                                <TextInput id="tg_chat" v-model="form.telegram_chat_id" type="text" class="mt-1 block w-full" placeholder="-100123456789" />
                                <InputError :message="form.errors.telegram_chat_id" class="mt-1" />
                            </div>
                        </div>
                    </div>

                    <!-- Email -->
                    <div class="rounded-lg bg-white p-6 shadow-sm">
                        <h3 class="mb-4 text-base font-semibold text-gray-900">Email Notifications</h3>
                        <div class="space-y-4">
                            <div>
                                <InputLabel for="mail_from" value="From Address" />
                                <TextInput id="mail_from" v-model="form.mail_from_address" type="email" class="mt-1 block w-full" />
                                <InputError :message="form.errors.mail_from_address" class="mt-1" />
                            </div>
                            <div>
                                <InputLabel for="recipients" value="Alert Recipients (comma-separated)" />
                                <TextInput id="recipients" v-model="form.alert_email_recipients" type="text" class="mt-1 block w-full" placeholder="ops@example.com,admin@example.com" />
                                <InputError :message="form.errors.alert_email_recipients" class="mt-1" />
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-end">
                        <PrimaryButton :disabled="form.processing">
                            {{ form.processing ? 'Saving...' : 'Save Configuration' }}
                        </PrimaryButton>
                    </div>
                </form>

            </div>
        </div>
    </AuthenticatedLayout>
</template>
