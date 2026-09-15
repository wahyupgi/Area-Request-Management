<script setup>
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const passwordInput = ref(null);
const currentPasswordInput = ref(null);
const visiblePasswords = ref({
    current_password: false,
    password: false,
    password_confirmation: false,
});

const form = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
});

const updatePassword = () => {
    form.put(route('password.update'), {
        preserveScroll: true,
        onSuccess: () => form.reset(),
        onError: () => {
            if (form.errors.password) {
                form.reset('password', 'password_confirmation');
                passwordInput.value.focus();
            }
            if (form.errors.current_password) {
                form.reset('current_password');
                currentPasswordInput.value.focus();
            }
        },
    });
};

const togglePassword = (field) => {
    visiblePasswords.value[field] = !visiblePasswords.value[field];
};
</script>

<template>
    <section>
        <header>
            <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">Ganti Password</h2>

            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                Gunakan password yang kuat untuk menjaga keamanan akun Anda.
            </p>
        </header>

        <form @submit.prevent="updatePassword" class="mt-6 space-y-6">
            <div>
                <InputLabel for="current_password" value="Password Saat Ini" />

                <div class="relative mt-1">
                    <TextInput
                        id="current_password"
                        ref="currentPasswordInput"
                        v-model="form.current_password"
                        :type="visiblePasswords.current_password ? 'text' : 'password'"
                        class="block w-full pr-12"
                        autocomplete="current-password"
                    />
                    <button type="button" :aria-label="visiblePasswords.current_password ? 'Sembunyikan password saat ini' : 'Tampilkan password saat ini'" class="absolute inset-y-0 right-0 px-3 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-100" @click="togglePassword('current_password')">
                        <svg v-if="visiblePasswords.current_password" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3l18 18M10.584 10.587a2 2 0 002.829 2.829M9.88 4.24A9.77 9.77 0 0112 4c5.523 0 9.75 4.478 9.75 10a10.01 10.01 0 01-2.036 5.995M6.228 6.228C4.238 7.838 2.75 10.38 2.25 14c.33 2.39 1.35 4.48 2.856 6.086" /></svg>
                        <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.25 12s3.5-6 9.75-6 9.75 6 9.75 6-3.5 6-9.75 6-9.75-6-9.75-6z" /><circle cx="12" cy="12" r="2.5" stroke-width="2" /></svg>
                    </button>
                </div>

                <InputError :message="form.errors.current_password" class="mt-2" />
            </div>

            <div>
                <InputLabel for="password" value="Password Baru" />

                <div class="relative mt-1">
                    <TextInput
                        id="password"
                        ref="passwordInput"
                        v-model="form.password"
                        :type="visiblePasswords.password ? 'text' : 'password'"
                        class="block w-full pr-12"
                        autocomplete="new-password"
                    />
                    <button type="button" :aria-label="visiblePasswords.password ? 'Sembunyikan password baru' : 'Tampilkan password baru'" class="absolute inset-y-0 right-0 px-3 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-100" @click="togglePassword('password')">
                        <svg v-if="visiblePasswords.password" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3l18 18M10.584 10.587a2 2 0 002.829 2.829M9.88 4.24A9.77 9.77 0 0112 4c5.523 0 9.75 4.478 9.75 10a10.01 10.01 0 01-2.036 5.995M6.228 6.228C4.238 7.838 2.75 10.38 2.25 14c.33 2.39 1.35 4.48 2.856 6.086" /></svg>
                        <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.25 12s3.5-6 9.75-6 9.75 6 9.75 6-3.5 6-9.75 6-9.75-6-9.75-6z" /><circle cx="12" cy="12" r="2.5" stroke-width="2" /></svg>
                    </button>
                </div>

                <InputError :message="form.errors.password" class="mt-2" />
            </div>

            <div>
                <InputLabel for="password_confirmation" value="Konfirmasi Password Baru" />

                <div class="relative mt-1">
                    <TextInput
                        id="password_confirmation"
                        v-model="form.password_confirmation"
                        :type="visiblePasswords.password_confirmation ? 'text' : 'password'"
                        class="block w-full pr-12"
                        autocomplete="new-password"
                    />
                    <button type="button" :aria-label="visiblePasswords.password_confirmation ? 'Sembunyikan konfirmasi password' : 'Tampilkan konfirmasi password'" class="absolute inset-y-0 right-0 px-3 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-100" @click="togglePassword('password_confirmation')">
                        <svg v-if="visiblePasswords.password_confirmation" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3l18 18M10.584 10.587a2 2 0 002.829 2.829M9.88 4.24A9.77 9.77 0 0112 4c5.523 0 9.75 4.478 9.75 10a10.01 10.01 0 01-2.036 5.995M6.228 6.228C4.238 7.838 2.75 10.38 2.25 14c.33 2.39 1.35 4.48 2.856 6.086" /></svg>
                        <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.25 12s3.5-6 9.75-6 9.75 6 9.75 6-3.5 6-9.75 6-9.75-6-9.75-6z" /><circle cx="12" cy="12" r="2.5" stroke-width="2" /></svg>
                    </button>
                </div>

                <InputError :message="form.errors.password_confirmation" class="mt-2" />
            </div>

            <div class="flex items-center gap-4">
                <PrimaryButton :disabled="form.processing">Simpan Password</PrimaryButton>

                <Transition
                    enter-active-class="transition ease-in-out"
                    enter-from-class="opacity-0"
                    leave-active-class="transition ease-in-out"
                    leave-to-class="opacity-0"
                >
                    <p v-if="form.recentlySuccessful" class="text-sm text-gray-600 dark:text-gray-400">Password berhasil diubah.</p>
                </Transition>
            </div>
        </form>
    </section>
</template>
