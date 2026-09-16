import { ref } from 'vue';

const storageKey = 'area-request-theme';
const theme = ref('dark');

const applyTheme = (value) => {
    theme.value = value;
    document.documentElement.classList.toggle('theme-light', value === 'light');
    document.documentElement.classList.toggle('dark', value === 'dark');
    document.documentElement.dataset.theme = value;
    localStorage.setItem(storageKey, value);
};

export const initializeTheme = () => {
    const storedTheme = localStorage.getItem(storageKey);
    const preferredTheme = window.matchMedia('(prefers-color-scheme: light)').matches ? 'light' : 'dark';

    applyTheme(storedTheme || preferredTheme);
};

export const useTheme = () => ({
    theme,
    setTheme: applyTheme,
    toggleTheme: () => applyTheme(theme.value === 'dark' ? 'light' : 'dark'),
});
