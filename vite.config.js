import { defineConfig } from 'vite'
import tailwindcss from '@tailwindcss/vite'
import { resolve } from 'path'

// Build config for the main easy-forms bundle
export default defineConfig(({ command }) => ({
    plugins: [tailwindcss()],
    build: {
        manifest: true,
        outDir: 'dist',
        emptyOutDir: true,
        lib: {
            entry: resolve(__dirname, 'resources/js/frontend.js'),
            name: 'EasyForms',
            fileName: () => 'js/easy-forms.js',
            cssFileName: 'css/easy-forms',
            formats: ['iife'],
        },
    },
    esbuild: command === 'build' ? { drop: ['console', 'debugger'] } : {},
}))
