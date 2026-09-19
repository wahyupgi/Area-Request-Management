<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import ThemeToggle from '@/Components/ThemeToggle.vue';
import { useTheme } from '@/composables/useTheme';
import { computed, ref } from 'vue';
import { LoginOutlined } from '@ant-design/icons-vue';

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
const showPassword = ref(false);

const inputClass = computed(() => (
    isLightTheme.value
        ? 'w-full bg-white border border-slate-300 rounded-xl px-5 py-3.5 text-slate-900 placeholder-slate-500 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition-colors'
        : 'w-full bg-slate-800/50 border border-white/10 rounded-xl px-5 py-3.5 text-white placeholder-slate-500 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition-colors'
));

const passwordToggleClass = computed(() => (
    isLightTheme.value
        ? 'absolute inset-y-0 right-3 flex items-center justify-center text-slate-500 hover:text-slate-800 transition-colors'
        : 'absolute inset-y-0 right-3 flex items-center justify-center text-slate-400 hover:text-white transition-colors'
));

const submit = () => {
    if (form.processing) return;

    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <Head title="Login - Sistem Layanan Pengajuan" />
    <div class="app-login-surface relative min-h-screen overflow-hidden flex items-center justify-center p-6 font-sans">
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
        <div class="relative z-10 w-full max-w-5xl bg-slate-900/50 backdrop-blur-xl border border-white/10 rounded-3xl overflow-hidden flex shadow-2xl shadow-black/50">
            <!-- Left Side / Branding -->
            <div class="hidden md:flex flex-col items-center justify-center w-1/2 p-12 bg-white/5 relative overflow-hidden">
                <div class="relative z-10 flex flex-col items-center text-center">
                    <img src="/logo-pgi.jpg" alt="Logo PGI" class="w-28 h-28 mb-8 rounded-2xl shadow-2xl shadow-indigo-500/20 object-contain bg-white p-2" />
                    <h1 class="text-3xl font-bold text-white mb-2 leading-tight">SISTEM LAYANAN PENGAJUAN</h1>
                    <h2 class="text-xl font-medium text-indigo-400">Area Manager</h2>
                    <p class="text-slate-400 mt-6 max-w-sm leading-relaxed text-sm">
                        Kelola seluruh pengajuan memo antar area dan cabang dengan lebih mudah, aman, dan terintegrasi secara digital.
                    </p>
                </div>
            </div>

            <!-- Right Side / Form -->
            <div class="w-full md:w-1/2 p-8 md:p-14 flex flex-col justify-center">
                <div class="md:hidden flex flex-col items-center mb-10">
                    <img src="/logo-pgi.jpg" alt="Logo PGI" class="w-24 h-24 mb-4 rounded-xl shadow-lg object-contain bg-white p-1" />
                    <h1 class="text-2xl font-bold text-white text-center leading-tight">SISTEM LAYANAN<br>PENGAJUAN</h1>
                    <h2 class="text-sm font-medium text-indigo-400 text-center mt-1">Area Manager</h2>
                </div>

                <div class="mb-10 text-center md:text-left">
                    <h3 class="text-2xl font-bold text-white">Selamat Datang!</h3>
                    <p class="text-slate-400 mt-2 text-sm">Silakan masuk menggunakan kredensial akun Anda.</p>
                </div>

                <a-form layout="vertical" @finish="submit" class="login-custom-form">
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
                        :loading="form.processing" 
                        size="large" 
                        block
                        class="login-submit-button mt-4"
                        style="height: 52px; border-radius: 0.75rem; font-weight: 600;"
                    >
                        Login <template #icon><LoginOutlined v-if="!form.processing" /></template>
                    </a-button>
                </a-form>
            </div>
        </div>
    </div>
</template>

<style scoped>
:deep(.login-custom-form .ant-form-item-label > label) {
    color: #cbd5e1;
    font-size: 0.875rem;
    font-weight: 500;
}
:deep(.login-custom-form .ant-input),
:deep(.login-custom-form .ant-input-affix-wrapper) {
    background-color: rgba(30, 41, 59, 0.5);
    border-color: rgba(255, 255, 255, 0.1);
    color: white;
    border-radius: 0.75rem;
    box-shadow: none !important;
}
:deep(.login-custom-form .ant-input:focus),
:deep(.login-custom-form .ant-input-affix-wrapper:focus),
:deep(.login-custom-form .ant-input-affix-wrapper-focused) {
    border-color: #6366f1;
    box-shadow: 0 0 0 1px #6366f1 !important;
}
:deep(.login-custom-form .ant-input-affix-wrapper .ant-input) {
    background-color: transparent !important;
    color: white !important;
    border: 0 !important;
    box-shadow: none !important;
    border-radius: 0 !important;
}
:deep(.login-custom-form .ant-input-affix-wrapper .ant-input::placeholder),
:deep(.login-custom-form .ant-input::placeholder) {
    color: #64748b;
}
:deep(.login-custom-form .ant-input-password-icon) {
    color: #94a3b8;
    display: flex;
    align-items: center;
    justify-content: center;
}
:deep(.login-custom-form .ant-input-password-icon:hover) {
    color: white;
}

:deep(.light-login-input),
:deep(.light-login-input .ant-input-affix-wrapper) {
    background-color: #ffffff !important;
    border-color: #cbd5e1 !important;
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

:deep(.login-submit-button .ant-btn-icon),
:deep(.login-submit-button .anticon) {
    display: inline-flex !important;
    align-items: center !important;
    justify-content: center !important;
    width: 1.25rem;
    height: 1.25rem;
    line-height: 1 !important;
    margin: 0 !important;
    transform: none !important;
}
</style>
