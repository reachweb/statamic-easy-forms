import { defineConfig } from 'vite'
import tailwindcss from '@tailwindcss/vite'

export default defineConfig({
    plugins: [tailwindcss()],
    build: {
        manifest: true,
        outDir: 'dist',
        rollupOptions: {
            input: { 'easy-forms': 'resources/js/frontend.js' },
            output: {
                entryFileNames: 'js/[name].js',
                chunkFileNames: 'js/[name]-[hash].js',
                assetFileNames: (assetInfo) => {
                    // CSS files go to css/, everything else to assets/
                    return assetInfo.names[0]?.endsWith('.css')
                        ? 'css/[name][extname]'
                        : 'assets/[name]-[hash][extname]'
                },
            },
        },
    },
})
