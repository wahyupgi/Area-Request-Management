<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import ThemeToggle from '@/Components/ThemeToggle.vue';
import { ref } from 'vue';

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

const showPassword = ref(false);

const submit = () => {
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

                <form @submit.prevent="submit" class="space-y-5">
                    <div>
                        <label for="username" class="block text-sm font-medium text-slate-300 mb-2">Username</label>
                        <input id="username" v-model="form.username" type="text" autofocus autocomplete="username" class="w-full bg-slate-800/50 border border-white/10 rounded-xl px-5 py-3.5 text-white placeholder-slate-500 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition-colors" placeholder="Masukkan username" />
                        <p v-if="form.errors.username" class="text-red-400 text-sm mt-2">{{ form.errors.username }}</p>
                    </div>

                    <div>
                        <label for="password" class="block text-sm font-medium text-slate-300 mb-2">Password</label>
                        <div class="relative">
                            <input id="password" v-model="form.password" :type="showPassword ? 'text' : 'password'" autocomplete="current-password" class="w-full bg-slate-800/50 border border-white/10 rounded-xl px-5 py-3.5 pr-14 text-white placeholder-slate-500 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition-colors" placeholder="Masukkan password" />
                            <button type="button" :aria-label="showPassword ? 'Sembunyikan password' : 'Tampilkan password'" class="absolute inset-y-0 right-0 flex items-center px-5 text-slate-400 hover:text-white transition-colors" @click="showPassword = !showPassword">
                                <svg v-if="showPassword" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3l18 18M10.584 10.587a2 2 0 002.829 2.829M9.88 4.24A9.77 9.77 0 0112 4c5.523 0 9.75 4.478 9.75 10a10.01 10.01 0 01-2.036 5.995M6.228 6.228C4.238 7.838 2.75 10.38 2.25 14c.33 2.39 1.35 4.48 2.856 6.086" /></svg>
                                <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.25 12s3.5-6 9.75-6 9.75 6 9.75 6-3.5 6-9.75 6-9.75-6-9.75-6z" /><circle cx="12" cy="12" r="2.5" stroke-width="2" /></svg>
                            </button>
                        </div>
                        <p v-if="form.errors.password" class="text-red-400 text-sm mt-2">{{ form.errors.password }}</p>
                    </div>

                    <button type="submit" :disabled="form.processing" class="w-full py-4 mt-4 bg-indigo-600 hover:bg-indigo-500 disabled:opacity-50 text-white font-bold rounded-xl transition-all shadow-lg shadow-indigo-500/25 flex justify-center items-center gap-2">
                        <span v-if="form.processing">Memproses...</span>
                        <span v-else>Login</span>
                        <svg v-if="!form.processing" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                    </button>
                </form>
            </div>
        </div>
    </div>
</template>
