<script setup>
import { useForm } from '@inertiajs/vue3';
import { nextTick, ref } from 'vue';

const confirmingUserDeletion = ref(false);
const passwordInput = ref(null);

const form = useForm({ password: '' });

const confirmUserDeletion = () => {
    confirmingUserDeletion.value = true;
    nextTick(() => passwordInput.value?.focus());
};

const deleteUser = () => {
    form.delete(route('profile.destroy'), {
        preserveScroll: true,
        onSuccess: () => closeModal(),
        onError: () => passwordInput.value?.focus(),
        onFinish: () => form.reset(),
    });
};

const closeModal = () => {
    confirmingUserDeletion.value = false;
    form.clearErrors();
    form.reset();
};
</script>

<template>
    <section>
        <h3 class="text-[15px] font-semibold text-red-400">Delete Account</h3>
        <p class="mt-0.5 text-[13px] text-slate-500">
            Permanently delete your account and all associated data. This cannot be undone.
        </p>

        <button
            type="button"
            @click="confirmUserDeletion"
            class="mt-5 rounded-md border border-red-500/40 bg-red-500/10 px-4 py-2 text-[13px] font-medium text-red-400 transition-colors hover:bg-red-500/20 hover:text-red-300"
        >
            Delete Account
        </button>
    </section>

    <!-- Confirmation modal -->
    <Teleport to="body">
        <Transition
            enter-active-class="transition ease-out duration-200"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition ease-in duration-150"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div v-if="confirmingUserDeletion" class="fixed inset-0 z-50 flex items-center justify-center p-4">
                <!-- Backdrop -->
                <div class="absolute inset-0 bg-slate-950/80 backdrop-blur-sm" @click="closeModal"></div>

                <!-- Dialog -->
                <div class="relative w-full max-w-md rounded-xl border border-red-500/20 bg-slate-900 p-6 shadow-xl">
                    <h2 class="text-[16px] font-semibold text-slate-100">Delete your account?</h2>
                    <p class="mt-2 text-[13px] text-slate-400">
                        All your data will be permanently deleted. Enter your password to confirm.
                    </p>

                    <div class="mt-5">
                        <label for="del_password" class="block text-[13px] font-medium text-slate-300">Password</label>
                        <input
                            id="del_password"
                            ref="passwordInput"
                            v-model="form.password"
                            type="password"
                            placeholder="Enter your password"
                            @keyup.enter="deleteUser"
                            class="mt-1.5 block w-full rounded-md border border-white/[0.08] bg-slate-800/60 px-3 py-2 text-[13px] text-slate-100 placeholder-slate-600 focus:border-red-500/50 focus:outline-none focus:ring-1 focus:ring-red-500/30"
                        />
                        <p v-if="form.errors.password" class="mt-1 text-[12px] text-red-400">{{ form.errors.password }}</p>
                    </div>

                    <div class="mt-6 flex items-center justify-end gap-3">
                        <button
                            type="button"
                            @click="closeModal"
                            class="rounded-md px-4 py-2 text-[13px] font-medium text-slate-400 transition-colors hover:bg-slate-800/60 hover:text-slate-200"
                        >
                            Cancel
                        </button>
                        <button
                            type="button"
                            @click="deleteUser"
                            :disabled="form.processing"
                            class="inline-flex items-center gap-2 rounded-md bg-red-600 px-4 py-2 text-[13px] font-medium text-white transition-colors hover:bg-red-500 disabled:opacity-50"
                        >
                            <svg v-if="form.processing" class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                            </svg>
                            Delete Account
                        </button>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>
