import { defineConfig } from 'vite'
import tailwindcss from '@tailwindcss/vite'

export default defineConfig({
    plugins: [
        tailwindcss(),
    ],
    build: {
        manifest: true,
        outDir: 'dist',
        rollupOptions: {
            input: {
                'easy-forms': 'resources/js/frontend.js',
            },
            output: {
                entryFileNames: 'js/[name].js',
                chunkFileNames: 'js/[name]-[hash].js',
                assetFileNames: (assetInfo) => {
                    if (assetInfo.name && assetInfo.name.endsWith('.css')) {
                        return 'css/[name][extname]'
                    }
                    return 'assets/[name]-[hash][extname]'
                },
            },
        },
    },
})
