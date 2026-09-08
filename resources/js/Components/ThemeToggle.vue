<script setup>
import { computed } from 'vue';
import { useTheme } from '@/composables/useTheme';

const { theme, toggleTheme } = useTheme();
const label = computed(() => theme.value === 'dark' ? 'Aktifkan mode terang' : 'Aktifkan mode gelap');
</script>

<template>
    <button
        type="button"
        :aria-label="label"
        :title="label"
        @click="toggleTheme"
        class="theme-toggle inline-flex h-10 w-10 items-center justify-center rounded-lg border border-white/10 bg-slate-800/70 text-slate-300 shadow-lg shadow-black/10 transition-colors hover:bg-slate-700 hover:text-white"
    >
        <svg v-if="theme === 'dark'" class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 3v2.25M12 18.75V21M3 12h2.25M18.75 12H21M5.636 5.636l1.591 1.591m9.546 9.546l1.591 1.591m0-12.728l-1.591 1.591m-9.546 9.546l-1.591 1.591M15.75 12a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0z" />
        </svg>
        <svg v-else class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M21.752 15.002A9.718 9.718 0 0112 21.75C6.615 21.75 2.25 17.385 2.25 12A9.718 9.718 0 018.998 2.248a.75.75 0 01.909.916A7.5 7.5 0 0018.836 12.1a.75.75 0 01.916.902z" />
        </svg>
    </button>
</template>
