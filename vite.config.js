import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';

export default defineConfig({
    plugins: [
        laravel({
            input: 'resources/js/app.js',
            refresh: true,
        }),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
    ],
    build: {
        chunkSizeWarningLimit: 1500,
        rollupOptions: {
            output: {
                manualChunks(id) {
                    if (id.includes('node_modules')) {
                        if (id.includes('@antv/') || id.includes('@ant-design/charts-util')) {
                            return 'charts';
                        }
                        if (id.includes('@ant-design/icons-vue')) {
                            return 'ant-design-icons';
                        }
                        if (id.includes('ant-design-vue')) {
                            return 'ant-design-vue';
                        }
                        if (id.includes('@inertiajs') || id.includes('@vue') || id.includes('vue-router') || id.includes('vue')) {
                            return 'vue-vendor';
                        }
                        return 'vendor';
                    }
                },
            },
        },
    },
});
