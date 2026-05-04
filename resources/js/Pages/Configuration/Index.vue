<script setup>
import { Head } from '@inertiajs/vue3';
import { useForm, router, usePage } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { ref, watch } from 'vue';

const props = defineProps({ config: Object });
const page = usePage();

const form = useForm({
    polling_channel_interval: props.config.polling_channel_interval,
    polling_storage_interval: props.config.polling_storage_interval,
    polling_device_interval: props.config.polling_device_interval,
    notification_reminder_interval: props.config.notification_reminder_interval,
    telegram_bot_token: props.config.telegram_bot_token === '***configured***' ? '' : (props.config.telegram_bot_token ?? ''),
    telegram_chat_ids: props.config.telegram_chat_ids ?? '',
    telegram_message_thread_id: props.config.telegram_message_thread_id ?? '',
    mail_from_address: props.config.mail_from_address ?? '',
    alert_email_recipients: props.config.alert_email_recipients ?? '',
});

const telegramTesting = ref(false);
const emailTesting = ref(false);
const showFlash = ref(true);

watch(() => page.props.flash.success, () => {
    showFlash.value = true;
    setTimeout(() => showFlash.value = false, 5000);
});

watch(() => page.props.flash.error, () => {
    showFlash.value = true;
});

function submit() {
    form.post('/configuration');
}

function testTelegram() {
    telegramTesting.value = true;
    router.post('/configuration/test-telegram', {
        telegram_bot_token: form.telegram_bot_token,
        telegram_chat_ids: form.telegram_chat_ids,
        telegram_message_thread_id: form.telegram_message_thread_id,
    }, {
        preserveScroll: true,
        onFinish: () => {
            telegramTesting.value = false;
        },
    });
}

function testEmail() {
    emailTesting.value = true;
    router.post('/configuration/test-email', {
        mail_from_address: form.mail_from_address,
        alert_email_recipients: form.alert_email_recipients,
    }, {
        preserveScroll: true,
        onFinish: () => {
            emailTesting.value = false;
        },
    });
}
</script>

<template>
    <Head title="Configuration" />

    <AuthenticatedLayout>
        <template #header>
            <div>
                <h2 class="text-[17px] font-semibold text-slate-100">System Configuration</h2>
                <p class="text-[13px] text-slate-500">Polling intervals and notification settings</p>
            </div>
        </template>

        <div class="mx-auto max-w-3xl">
            <!-- Flash Messages -->
            <div v-if="showFlash && page.props.flash.success" class="mb-4 rounded-lg border border-emerald-500/20 bg-emerald-500/10 px-4 py-3">
                <div class="flex items-center gap-2">
                    <svg class="h-4 w-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                    <p class="text-[13px] text-emerald-400">{{ page.props.flash.success }}</p>
                </div>
            </div>

            <div v-if="showFlash && page.props.flash.error" class="mb-4 rounded-lg border border-red-500/20 bg-red-500/10 px-4 py-3">
                <div class="flex items-center gap-2">
                    <svg class="h-4 w-4 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                    <p class="text-[13px] text-red-400">{{ page.props.flash.error }}</p>
                </div>
            </div>

            <form @submit.prevent="submit" class="space-y-4">

                <!-- Polling Intervals -->
                <div class="rounded-lg border border-white/[0.06] bg-slate-900/60 shadow-[0_1px_2px_rgba(0,0,0,0.4)]">
                    <div class="border-b border-white/[0.06] px-5 py-3">
                        <h3 class="text-[15px] font-semibold text-slate-100">Polling Intervals</h3>
                        <p class="text-[12px] text-slate-500">How often background jobs query each device (minutes)</p>
                    </div>
                    <div class="p-5 space-y-5">
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                            <div>
                                <label for="ch_interval" class="block text-[13px] font-medium text-slate-300">Channel Status</label>
                                <input
                                    id="ch_interval"
                                    v-model.number="form.polling_channel_interval"
                                    type="number" min="1" max="60"
                                    class="mt-1.5 block w-full rounded-md border border-white/[0.08] bg-slate-800/60 px-3 py-2 text-[13px] text-slate-100 focus:border-cyan-500/50 focus:outline-none focus:ring-1 focus:ring-cyan-500/30"
                                />
                                <p v-if="form.errors.polling_channel_interval" class="mt-1 text-[12px] text-red-400">{{ form.errors.polling_channel_interval }}</p>
                            </div>
                            <div>
                                <label for="dev_interval" class="block text-[13px] font-medium text-slate-300">Device Health</label>
                                <input
                                    id="dev_interval"
                                    v-model.number="form.polling_device_interval"
                                    type="number" min="1" max="60"
                                    class="mt-1.5 block w-full rounded-md border border-white/[0.08] bg-slate-800/60 px-3 py-2 text-[13px] text-slate-100 focus:border-cyan-500/50 focus:outline-none focus:ring-1 focus:ring-cyan-500/30"
                                />
                                <p v-if="form.errors.polling_device_interval" class="mt-1 text-[12px] text-red-400">{{ form.errors.polling_device_interval }}</p>
                            </div>
                            <div>
                                <label for="st_interval" class="block text-[13px] font-medium text-slate-300">Storage Status</label>
                                <input
                                    id="st_interval"
                                    v-model.number="form.polling_storage_interval"
                                    type="number" min="1" max="60"
                                    class="mt-1.5 block w-full rounded-md border border-white/[0.08] bg-slate-800/60 px-3 py-2 text-[13px] text-slate-100 focus:border-cyan-500/50 focus:outline-none focus:ring-1 focus:ring-cyan-500/30"
                                />
                                <p v-if="form.errors.polling_storage_interval" class="mt-1 text-[12px] text-red-400">{{ form.errors.polling_storage_interval }}</p>
                            </div>
                        </div>

                        <div>
                            <label for="reminder_interval" class="block text-[13px] font-medium text-slate-300">Notification Reminder Interval (minutes)</label>
                            <input
                                id="reminder_interval"
                                v-model.number="form.notification_reminder_interval"
                                type="number" min="5" max="1440"
                                class="mt-1.5 block w-40 rounded-md border border-white/[0.08] bg-slate-800/60 px-3 py-2 text-[13px] text-slate-100 focus:border-cyan-500/50 focus:outline-none focus:ring-1 focus:ring-cyan-500/30"
                            />
                            <p class="mt-1 text-[12px] text-slate-500">How often to resend notifications for unresolved alerts.</p>
                            <p v-if="form.errors.notification_reminder_interval" class="mt-1 text-[12px] text-red-400">{{ form.errors.notification_reminder_interval }}</p>
                        </div>
                    </div>
                </div>

                <!-- Telegram -->
                <div class="rounded-lg border border-white/[0.06] bg-slate-900/60 shadow-[0_1px_2px_rgba(0,0,0,0.4)]">
                    <div class="border-b border-white/[0.06] px-5 py-3 flex items-center justify-between">
                        <div>
                            <h3 class="text-[15px] font-semibold text-slate-100">Telegram Notifications</h3>
                            <p class="text-[12px] text-slate-500">Alerts sent via Telegram bot</p>
                        </div>
                        <button
                            type="button"
                            @click="testTelegram"
                            :disabled="telegramTesting"
                            class="inline-flex items-center gap-1.5 rounded-md bg-[#229ED9]/20 px-3 py-1.5 text-[12px] font-medium text-[#229ED9] transition-colors hover:bg-[#229ED9]/30 disabled:opacity-50"
                        >
                            <svg v-if="telegramTesting" class="h-3.5 w-3.5 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                            </svg>
                            <svg v-else class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                            </svg>
                            {{ telegramTesting ? 'Sending...' : 'Test Send' }}
                        </button>
                    </div>
                    <div class="space-y-4 p-5">
                        <div>
                            <label for="tg_token" class="block text-[13px] font-medium text-slate-300">Bot Token</label>
                            <input
                                id="tg_token"
                                v-model="form.telegram_bot_token"
                                type="password"
                                autocomplete="off"
                                :placeholder="config.telegram_bot_token === '***configured***' ? 'Leave blank to keep current' : 'Enter bot token'"
                                class="mt-1.5 block w-full rounded-md border border-white/[0.08] bg-slate-800/60 px-3 py-2 font-mono text-[13px] text-slate-100 placeholder-slate-600 focus:border-cyan-500/50 focus:outline-none focus:ring-1 focus:ring-cyan-500/30"
                            />
                            <div v-if="config.telegram_bot_token === '***configured***'" class="mt-1 flex items-center gap-1.5">
                                <span class="inline-flex h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                                <span class="text-[12px] text-emerald-400">Token configured</span>
                            </div>
                            <p v-if="form.errors.telegram_bot_token" class="mt-1 text-[12px] text-red-400">{{ form.errors.telegram_bot_token }}</p>
                        </div>
                        <div>
                            <label for="tg_chats" class="block text-[13px] font-medium text-slate-300">Chat IDs</label>
                            <input
                                id="tg_chats"
                                v-model="form.telegram_chat_ids"
                                type="text"
                                placeholder="-100123456789, -100987654321"
                                class="mt-1.5 block w-full rounded-md border border-white/[0.08] bg-slate-800/60 px-3 py-2 font-mono text-[13px] text-slate-100 placeholder-slate-500 focus:border-cyan-500/50 focus:outline-none focus:ring-1 focus:ring-cyan-500/30"
                            />
                            <p class="mt-1 text-[12px] text-slate-500">Comma-separated for multiple chats/groups.</p>
                            <p v-if="form.errors.telegram_chat_ids" class="mt-1 text-[12px] text-red-400">{{ form.errors.telegram_chat_ids }}</p>
                        </div>
                        <div>
                            <label for="tg_thread" class="block text-[13px] font-medium text-slate-300">Message Thread ID (Topic)</label>
                            <input
                                id="tg_thread"
                                v-model="form.telegram_message_thread_id"
                                type="text"
                                placeholder="Leave blank for general chat"
                                class="mt-1.5 block w-full rounded-md border border-white/[0.08] bg-slate-800/60 px-3 py-2 font-mono text-[13px] text-slate-100 placeholder-slate-500 focus:border-cyan-500/50 focus:outline-none focus:ring-1 focus:ring-cyan-500/30"
                            />
                            <p class="mt-1 text-[12px] text-slate-500">Optional: Send to a specific topic/thread in a group.</p>
                            <p v-if="form.errors.telegram_message_thread_id" class="mt-1 text-[12px] text-red-400">{{ form.errors.telegram_message_thread_id }}</p>
                        </div>
                    </div>
                </div>

                <!-- Email -->
                <div class="rounded-lg border border-white/[0.06] bg-slate-900/60 shadow-[0_1px_2px_rgba(0,0,0,0.4)]">
                    <div class="border-b border-white/[0.06] px-5 py-3 flex items-center justify-between">
                        <div>
                            <h3 class="text-[15px] font-semibold text-slate-100">Email Notifications</h3>
                            <p class="text-[12px] text-slate-500">Alert recipients via SMTP</p>
                        </div>
                        <button
                            type="button"
                            @click="testEmail"
                            :disabled="emailTesting"
                            class="inline-flex items-center gap-1.5 rounded-md bg-cyan-500/20 px-3 py-1.5 text-[12px] font-medium text-cyan-400 transition-colors hover:bg-cyan-500/30 disabled:opacity-50"
                        >
                            <svg v-if="emailTesting" class="h-3.5 w-3.5 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                            </svg>
                            <svg v-else class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                            </svg>
                            {{ emailTesting ? 'Sending...' : 'Test Send' }}
                        </button>
                    </div>
                    <div class="space-y-4 p-5">
                        <div>
                            <label for="mail_from" class="block text-[13px] font-medium text-slate-300">From Address</label>
                            <input
                                id="mail_from"
                                v-model="form.mail_from_address"
                                type="email"
                                class="mt-1.5 block w-full rounded-md border border-white/[0.08] bg-slate-800/60 px-3 py-2 text-[13px] text-slate-100 placeholder-slate-500 focus:border-cyan-500/50 focus:outline-none focus:ring-1 focus:ring-cyan-500/30"
                            />
                            <p v-if="form.errors.mail_from_address" class="mt-1 text-[12px] text-red-400">{{ form.errors.mail_from_address }}</p>
                        </div>
                        <div>
                            <label for="recipients" class="block text-[13px] font-medium text-slate-300">Alert Recipients</label>
                            <input
                                id="recipients"
                                v-model="form.alert_email_recipients"
                                type="text"
                                placeholder="ops@example.com, admin@example.com"
                                class="mt-1.5 block w-full rounded-md border border-white/[0.08] bg-slate-800/60 px-3 py-2 text-[13px] text-slate-100 placeholder-slate-500 focus:border-cyan-500/50 focus:outline-none focus:ring-1 focus:ring-cyan-500/30"
                            />
                            <p class="mt-1 text-[12px] text-slate-500">Comma-separated email addresses.</p>
                            <p v-if="form.errors.alert_email_recipients" class="mt-1 text-[12px] text-red-400">{{ form.errors.alert_email_recipients }}</p>
                        </div>
                    </div>
                </div>

                <!-- Actions -->
                <div class="flex items-center justify-end gap-3 rounded-lg border border-white/[0.06] bg-slate-900/60 px-5 py-4">
                    <span v-if="form.recentlySuccessful" class="flex items-center gap-1.5 text-[13px] text-emerald-400">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                        Saved
                    </span>
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="inline-flex items-center gap-2 rounded-md bg-cyan-600 px-5 py-2 text-[13px] font-medium text-white transition-colors hover:bg-cyan-500 disabled:opacity-50"
                    >
                        <svg v-if="form.processing" class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                        </svg>
                        {{ form.processing ? 'Saving…' : 'Save Configuration' }}
                    </button>
                </div>

            </form>
        </div>
    </AuthenticatedLayout>
</template>
