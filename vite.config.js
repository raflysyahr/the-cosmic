import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import react from '@vitejs/plugin-react';

export default defineConfig({
    plugins: [
        ...laravel({
            input: 'resources/js/app.jsx',
            refresh: true,
        }),
        react(),
    ],
    resolve: {
        alias: {
            '@': '/resources/js',
        },
    },
    build: {
        rollupOptions: {
            input: 'resources/js/app.jsx',
            output: {
                manualChunks(id) {
                    if (id.includes('react-fluentui-emoji')) {
                        return 'fluentui-emoji'
                    }
                },
            },
        },
    },
    server:{
        watch:{
            ignored: ['**/vendor/**', '**/node_modules/**']
        }

    }
});
