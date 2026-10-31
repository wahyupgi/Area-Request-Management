import './bootstrap';
import '../css/app.css';

import { createApp, h } from 'vue';
import { createInertiaApp, router } from '@inertiajs/vue3';
import { ZiggyVue } from '../../vendor/tightenco/ziggy';
import { initializeTheme } from './composables/useTheme';
import AppShellLayout from './Layouts/AppShellLayout.vue';
import { installInertiaPrefetch } from './utils/inertiaPrefetch';

import 'ant-design-vue/dist/reset.css';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';
const pages = import.meta.glob('./Pages/**/*.vue', { eager: true });

initializeTheme();

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) => {
        const pageModule = pages[`./Pages/${name}.vue`];
        if (!pageModule) {
            throw new Error(`Page not found: ./Pages/${name}.vue`);
        }
        const component = pageModule.default || pageModule;
        if (component && !name.startsWith('Auth/') && !component.layout) {
            component.layout = AppShellLayout;
        }
        return pageModule;
    },
    setup({ el, App, props, plugin }) {
        installInertiaPrefetch(router, props.initialPage);

        return createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(ZiggyVue)
            .mount(el);
    },
    progress: false,
});
