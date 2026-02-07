import { defineConfig } from 'vite'
import { resolve } from 'path'

// Build config for the precognition plugin bundle
export default defineConfig(({ command }) => ({
    build: {
        outDir: 'dist',
        emptyOutDir: false,
        lib: {
            entry: resolve(__dirname, 'resources/js/precognition.js'),
            name: 'EasyFormsPrecognition',
            fileName: () => 'js/easy-forms-precognition.js',
            formats: ['iife'],
        },
    },
    esbuild: command === 'build' ? { drop: ['console', 'debugger'] } : {},
}))
