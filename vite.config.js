import { defineConfig } from 'vite';
import vue from '@vitejs/plugin-vue';

// Builds the dashboard into dist/ with stable file names (app.js, app.css).
// dist/ is committed and published to public/vendor/log-cleaner by
// `php artisan vendor:publish --tag=log-cleaner-assets`.
export default defineConfig({
    plugins: [vue()],
    publicDir: false,
    build: {
        outDir: 'dist',
        emptyOutDir: true,
        cssCodeSplit: false,
        sourcemap: false,
        rollupOptions: {
            input: 'resources/js/app.js',
            output: {
                entryFileNames: 'app.js',
                assetFileNames: (asset) => (asset.names?.[0]?.endsWith('.css') ? 'app.css' : '[name][extname]'),
                inlineDynamicImports: true,
            },
        },
    },
});
