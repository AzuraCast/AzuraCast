import {defineConfig} from 'vite'
import vue from '@vitejs/plugin-vue'
import {glob} from "glob";
import {resolve} from "path";
import eslint from "vite-plugin-eslint";

// https://vitejs.dev/config/
export default defineConfig({
    base: '/static/vite_dist',
    build: {
        rollupOptions: {
            input: glob.sync('./vue/pages/**/*.js').reduce((acc, path) => {
                // vue/pages/Admin/Index becomes AdminIndex
                const entry = path.replace(/\.js$/g, '')
                    .replace(/^vue\/pages\//g, '')
                    .replace(/\//g, '');

                acc[entry] = resolve(__dirname, path)
                return acc
            }, {}),
            output: {
                manualChunks: {
                    vue: ['vue'],
                    lodash: ['lodash'],
                    leaflet: ['leaflet'],
                    hlsjs: ['hls.js']
                }
            }
        },
        manifest: true,
        emptyOutDir: false,
        outDir: resolve(__dirname, '../web/static/vite_dist')
    },
    resolve: {
        alias: {
            '~': resolve(__dirname, './vue')
        },
        extensions: ['.mjs', '.js', '.mts', '.ts', '.jsx', '.tsx', '.json', '.vue']
    },
    plugins: [
        vue(),
        eslint({
            fix: true
        })
    ],
})
