<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import ThemeToggle from '@/Components/ThemeToggle.vue';
import { useTheme } from '@/composables/useTheme';
import { computed, ref } from 'vue';
import { CheckCircleOutlined } from '@ant-design/icons-vue';
import axios from 'axios';

const props = defineProps({
    status: {
        type: String,
        default: null,
    },
});

const watermarkPositions = [
    {
        class: '',
        lightClass: '',
        style: { left: '-8%', top: '-2%', width: '320px', '--wm-rot': '-18deg', '--wm-scale': '1.02', '--wm-delay': '0s', '--wm-duration': '19s', '--wm-offset-x': '0px', '--wm-offset-y': '0px' },
    },
    {
        class: '',
        lightClass: '',
        style: { left: '20%', top: '15%', width: '280px', '--wm-rot': '12deg', '--wm-scale': '0.98', '--wm-delay': '-4s', '--wm-duration': '23s', '--wm-offset-x': '0px', '--wm-offset-y': '0px' },
    },
    {
        class: '',
        lightClass: '',
        style: { right: '10%', top: '8%', width: '340px', '--wm-rot': '-10deg', '--wm-scale': '1.02', '--wm-delay': '-8s', '--wm-duration': '21s', '--wm-offset-x': '0px', '--wm-offset-y': '0px' },
    },
    {
        class: '',
        lightClass: '',
        style: { left: '6%', bottom: '2%', width: '300px', '--wm-rot': '16deg', '--wm-scale': '0.96', '--wm-delay': '-2s', '--wm-duration': '25s', '--wm-offset-x': '0px', '--wm-offset-y': '0px' },
    },
    {
        class: '',
        lightClass: '',
        style: { right: '4%', bottom: '10%', width: '280px', '--wm-rot': '-14deg', '--wm-scale': '1.02', '--wm-delay': '-6s', '--wm-duration': '22s', '--wm-offset-x': '0px', '--wm-offset-y': '0px' },
    },
    {
        class: '',
        lightClass: '',
        style: { left: '38%', bottom: '26%', width: '250px', '--wm-rot': '9deg', '--wm-scale': '0.92', '--wm-delay': '-10s', '--wm-duration': '27s', '--wm-offset-x': '0px', '--wm-offset-y': '0px' },
    },
];

const form = useForm({
    username: '',
    password: '',
    remember: false,
});

const { theme } = useTheme();
const isLightTheme = computed(() => theme.value === 'light');
const loginStatus = ref(null);
const isSubmitting = ref(false);

const inputClass = computed(() => (
    isLightTheme.value
        ? 'w-full bg-white border border-slate-300 rounded-xl px-5 py-3.5 text-slate-900 placeholder-slate-500 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 transition-colors'
        : 'w-full bg-slate-800/50 border border-white/10 rounded-xl px-5 py-3.5 text-white placeholder-slate-500 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 transition-colors'
));

const passwordToggleClass = computed(() => (
    isLightTheme.value
        ? 'absolute inset-y-0 right-3 flex items-center justify-center text-slate-500 hover:text-slate-800 transition-colors'
        : 'absolute inset-y-0 right-3 flex items-center justify-center text-slate-400 hover:text-white transition-colors'
));

const submit = async () => {
    if (isSubmitting.value) return;

    isSubmitting.value = true;
    loginStatus.value = null;
    form.clearErrors();

    try {
        const response = await axios.post(route('login'), form.data(), {
            headers: {
                Accept: 'application/json',
                'X-Login-Flow': 'popup',
            },
        });

        loginStatus.value = 'success';
        form.reset('password');

        window.setTimeout(() => {
            window.location.assign(response.data.redirect);
        }, 20);
    } catch (error) {
        loginStatus.value = null;
        isSubmitting.value = false;

        const errors = error.response?.data?.errors ?? {};
        Object.entries(errors).forEach(([field, messages]) => {
            form.setError(field, messages[0]);
        });

        if (!Object.keys(errors).length) {
            form.setError('username', 'Login gagal. Silakan coba lagi.');
        }
    }
};
</script>

<template>
    <Head title="Login - Sistem Layanan Pengajuan" />
    <div class="app-login-surface relative min-h-screen overflow-hidden flex items-center justify-center p-6 font-sans">
        <div v-if="loginStatus === 'success'" class="login-status-toast" :class="['is-success', isLightTheme ? 'is-light' : 'is-dark']" role="status" aria-live="polite">
            <CheckCircleOutlined />
            <span>Login berhasil</span>
        </div>
        <div aria-hidden="true" class="pointer-events-none absolute inset-0 overflow-hidden">
            <img
                v-for="(watermark, index) in watermarkPositions"
                :key="index"
                src="/Logo%20PGI%202.png"
                alt=""
                class="login-watermark absolute select-none"
                :class="[watermark.class, watermark.lightClass]"
                :style="watermark.style"
            />
        </div>
        <div class="absolute right-6 top-6 z-20">
            <ThemeToggle />
        </div>
        <div class="login-card relative z-10 w-full max-w-5xl rounded-3xl overflow-hidden flex shadow-2xl shadow-black/50" :class="isLightTheme ? 'is-light' : 'is-dark'">
            <div class="login-brand-panel hidden md:flex flex-col items-center justify-center w-1/2 p-12 relative overflow-hidden">
                <div class="relative z-10 flex flex-col items-center text-center">
                    <img src="/PGI-Secondary%20Logo%202%20(1).png" alt="Logo Pusat Gadai Indonesia" class="w-72 max-w-full h-auto rounded-lg" />
                </div>
                <svg class="login-wave hidden md:block" viewBox="40 0 60 100" preserveAspectRatio="none" aria-hidden="true">
                    <path d="M100 0 L50 0 C44 8 58 14 74 18 C60 26 70 36 84 40 C68 48 78 58 88 62 C70 70 66 80 70 84 C50 90 44 96 40 100 L100 100 Z" fill="#6aa8ee" />
                    <path d="M100 0 L62 0 C56 8 68 16 80 20 C68 28 78 38 90 42 C76 50 84 60 90 64 C76 72 72 84 76 88 C62 92 58 96 54 100 L100 100 Z" fill="#a6cbf4" />
                    <path d="M100 0 L72 0 C66 6 76 14 86 18 C78 26 84 36 94 40 C84 48 92 58 96 62 C86 70 84 82 88 86 C78 92 74 96 70 100 L100 100 Z" :fill="isLightTheme ? '#ffffff' : '#0f172a'" />
                </svg>
            </div>

            <div class="login-form-panel w-full md:w-1/2 p-8 md:p-14 flex flex-col justify-center">
                <div class="md:hidden flex flex-col items-center mb-10">
                    <img src="/PGI-Secondary%20Logo%202%20(1).png" alt="Logo Pusat Gadai Indonesia" class="w-56 max-w-full h-auto rounded-lg" />
                </div>

                <div class="mb-10 text-center md:text-left">
                    <h3 class="text-2xl font-bold" :class="isLightTheme ? 'text-slate-900' : 'text-white'">Selamat Datang!</h3>
                    <p class="mt-2 text-sm" :class="isLightTheme ? 'text-slate-500' : 'text-slate-400'">Silakan masuk menggunakan kredensial akun Anda.</p>
                    <p v-if="props.status" class="mt-4 rounded-lg border border-amber-400/30 bg-amber-400/10 px-4 py-3 text-left text-sm text-amber-200" role="alert">
                        {{ props.status }}
                    </p>
                </div>

                <a-form
                    layout="vertical"
                    @finish="submit"
                    class="login-custom-form"
                    :class="isLightTheme ? 'light-login-form' : 'dark-login-form'"
                >
                    <a-form-item
                        label="Username"
                        :validateStatus="form.errors.username ? 'error' : ''"
                        :help="form.errors.username"
                    >
                        <a-input
                            v-model:value="form.username"
                            autofocus
                            autocomplete="username"
                            size="large"
                            placeholder="Masukkan username"
                            class="login-text-input"
                            :class="isLightTheme ? 'light-login-input' : ''"
                            @keydown.enter.prevent="submit"
                        />
                    </a-form-item>

                    <a-form-item
                        label="Password"
                        :validateStatus="form.errors.password ? 'error' : ''"
                        :help="form.errors.password"
                    >
                        <a-input-password
                            v-model:value="form.password"
                            autocomplete="current-password"
                            size="large"
                            placeholder="Masukkan password"
                            class="login-password-input"
                            :class="isLightTheme ? 'light-login-input' : ''"
                            @keydown.enter.prevent="submit"
                        />
                    </a-form-item>

                    <a-button
                        type="primary"
                        html-type="button"
                        @click="submit"
                        :disabled="isSubmitting"
                        :loading="isSubmitting"
                        size="large"
                        block
                        class="login-submit-button mt-4"
                        :class="isLightTheme ? 'light-login-button' : 'dark-login-button'"
                        style="height: 52px; border-radius: 0.75rem; font-weight: 600;"
                    >
                        Login
                    </a-button>
                </a-form>
            </div>
        </div>
    </div>
</template>

<style scoped>
.login-card.is-dark { --panel-bg: #0f172a; border: 1px solid rgba(255, 255, 255, 0.1); }
.login-card.is-light { --panel-bg: #ffffff; border: 1px solid #e2e8f0; }
.login-form-panel { background: var(--panel-bg); }
.login-card.is-light .login-form-panel h3 { color: #0f172a; }
.login-card.is-light .login-form-panel p:not([role='alert']) { color: #64748b; }
.login-brand-panel {
    background: linear-gradient(180deg, #0f4fa8 0%, #1d77d8 100%);
}
.login-brand-panel img {
    background: #ffffff;
    padding: 0.75rem;
}
.login-wave {
    position: absolute;
    top: 0;
    right: -1px;
    width: 90px;
    height: 100%;
}
.login-wave .wave-back { fill: rgba(147, 197, 253, 0.55); }
.login-wave .wave-mid { fill: rgba(191, 219, 254, 0.75); }
.login-wave .wave-front { fill: var(--panel-bg); }
/* ---- Dark mode form (default) ---- */
:deep(.dark-login-form .ant-form-item-label > label),
:deep(.login-custom-form:not(.light-login-form) .ant-form-item-label > label) {
    color: #cbd5e1;
    font-size: 0.875rem;
    font-weight: 500;
}
:deep(.dark-login-form .ant-input),
:deep(.dark-login-form .ant-input-affix-wrapper) {
    background-color: rgba(30, 41, 59, 0.5);
    border-color: rgba(255, 255, 255, 0.1);
    color: white;
    border-radius: 0.75rem;
    box-shadow: none !important;
}
:deep(.dark-login-form .ant-input:focus),
:deep(.dark-login-form .ant-input-affix-wrapper:focus),
:deep(.dark-login-form .ant-input-affix-wrapper-focused) {
    border-color: #3b82f6;
    box-shadow: 0 0 0 1px #3b82f6 !important;
}
:deep(.dark-login-form .ant-input-affix-wrapper .ant-input) {
    background-color: transparent !important;
    color: white !important;
    border: 0 !important;
    box-shadow: none !important;
    border-radius: 0 !important;
}
:deep(.dark-login-form .ant-input-affix-wrapper .ant-input::placeholder),
:deep(.dark-login-form .ant-input::placeholder) {
    color: #64748b;
}
:deep(.dark-login-form .ant-input-password-icon) {
    color: #94a3b8;
    display: flex;
    align-items: center;
    justify-content: center;
}
:deep(.dark-login-form .ant-input-password-icon:hover) {
    color: white;
}
:deep(.dark-login-form .ant-form-item-explain-error) {
    color: #f87171;
}

:deep(.light-login-form .ant-form-item-label > label) {
    color: #334155 !important;
}
:deep(.light-login-form .ant-input::placeholder),
:deep(.light-login-form .ant-input-affix-wrapper .ant-input::placeholder) {
    color: #475569 !important;
}
:deep(.light-login-form .ant-input-password-icon) {
    color: #475569 !important;
}

:deep(.light-login-input),
:deep(.light-login-input .ant-input-affix-wrapper) {
    background-color: #ffffff !important;
    border-color: #cbd5e1 !important;
    color: #0f172a !important;
}
:deep(.light-login-input.ant-input),
:deep(.light-login-input .ant-input) {
    background-color: #ffffff !important;
    color: #0f172a !important;
}
:deep(.light-login-input.ant-input-affix-wrapper .ant-input) {
    color: #0f172a !important;
}
:deep(.light-login-input .ant-input::placeholder),
:deep(.light-login-input .ant-input-affix-wrapper .ant-input::placeholder) {
    color: #64748b !important;
}
:deep(.light-login-input .ant-input-password-icon) {
    color: #64748b !important;
}

:deep(.login-password-input .ant-input) {
    border: 0 !important;
    box-shadow: none !important;
    border-radius: 0 !important;
    background: transparent !important;
}

:deep(.login-submit-button) {
    display: inline-flex !important;
    align-items: center !important;
    justify-content: center !important;
    gap: 0.5rem !important;
    line-height: 1 !important;
}

:deep(.login-submit-button.dark-login-button),
:deep(.login-submit-button.dark-login-button:hover),
:deep(.login-submit-button.dark-login-button:focus),
:deep(.login-submit-button.dark-login-button:disabled) {
    border-color: #2563eb !important;
    background: #2563eb !important;
    color: #ffffff !important;
}

:deep(.login-submit-button.dark-login-button:hover),
:deep(.login-submit-button.dark-login-button:focus) {
    background: #1d4ed8 !important;
}

:deep(.login-submit-button.light-login-button),
:deep(.login-submit-button.light-login-button:hover),
:deep(.login-submit-button.light-login-button:focus),
:deep(.login-submit-button.light-login-button:disabled) {
    border-color: #2563eb !important;
    background: #2563eb !important;
    color: #ffffff !important;
}

:deep(.login-submit-button.light-login-button:hover),
:deep(.login-submit-button.light-login-button:focus) {
    background: #1d4ed8 !important;
}

:deep(.login-submit-button:disabled) {
    cursor: wait;
    opacity: 0.78;
}

:deep(.login-submit-button .ant-btn-icon),
:deep(.login-submit-button .anticon) {
    display: inline-flex !important;
    align-items: center !important;
    justify-content: center !important;
    width: 1.25rem;
    height: 1.25rem;
    line-height: 1 !important;
    margin: 0 !important;
}

:deep(.login-submit-button .anticon-spin) {
    animation: login-button-spin 1s linear infinite !important;
}

.login-status-toast {
    position: fixed;
    top: 1.5rem;
    right: 1.5rem;
    z-index: 50;
    display: inline-flex;
    align-items: center;
    gap: 0.7rem;
    min-width: 15rem;
    padding: 0.8rem 1.1rem 0.8rem 0.9rem;
    border: 1px solid rgba(148, 163, 184, 0.22);
    border-left: 3px solid #4ade80;
    border-radius: 0.5rem;
    background: rgba(30, 41, 59, 0.96);
    box-shadow: 0 10px 30px rgba(2, 6, 23, 0.24);
    color: #e2e8f0;
    font-size: 0.8125rem;
    font-weight: 600;
    letter-spacing: 0.01em;
}

.login-status-toast .anticon {
    color: #4ade80;
    font-size: 1.15rem;
}

.login-status-toast.is-success {
    border-color: rgba(74, 222, 128, 0.28);
}

.login-status-toast.is-success .anticon {
    color: #4ade80;
}

.login-status-toast.is-light {
    border-color: #cbd5e1;
    border-left-color: #16a34a;
    background: rgba(255, 255, 255, 0.98);
    box-shadow: 0 10px 30px rgba(15, 23, 42, 0.14);
    color: #334155;
}

.login-status-toast.is-light .anticon {
    color: #16a34a;
}

.login-status-toast.is-light.is-success {
    border-color: #bbf7d0;
}

@keyframes login-button-spin {
    to { transform: rotate(360deg); }
}

@media (max-width: 640px) {
    .login-status-toast {
        top: 1rem;
        right: 1rem;
        left: 1rem;
        justify-content: center;
    }
}
</style>
