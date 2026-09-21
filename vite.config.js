import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';
import Components from 'unplugin-vue-components/vite';
import AutoImport from 'unplugin-auto-import/vite';
import { AntDesignVueResolver } from 'unplugin-vue-components/resolvers';

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
        // Auto-import Ant Design Vue components on demand (tree shaking)
        Components({
            resolvers: [
                AntDesignVueResolver({
                    importStyle: false, // CSS sudah di-import via reset.css
                }),
            ],
            dts: false,
        }),
        // Auto-import Vue APIs (ref, computed, etc.)
        AutoImport({
            imports: ['vue'],
            dts: false,
        }),
    ],
    build: {
        // Minifikasi lebih cepat pakai esbuild
        minify: 'esbuild',
        // Skip legacy transpilation — target browser modern
        target: 'esnext',
        chunkSizeWarningLimit: 1500,
        rollupOptions: {
            // Jalankan lebih banyak operasi paralel
            maxParallelFileOps: 20,
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
