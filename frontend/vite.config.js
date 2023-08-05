import {defineConfig} from 'vite'
import vue from '@vitejs/plugin-vue'
import {glob} from "glob";
import {resolve} from "path";
import eslint from "vite-plugin-eslint";

const inputs = glob.sync('./vue/pages/**/*.js').reduce((acc, path) => {
    // vue/pages/Admin/Index becomes AdminIndex
    const entry = path.replace(/\.js$/g, '')
        .replace(/^vue\/pages\//g, '')
        .replace(/\//g, '');

    acc[entry] = resolve(__dirname, path)
    return acc
}, {});

inputs['Layout'] = resolve(__dirname, './js/layout.js');

console.log(inputs);

// https://vitejs.dev/config/
export default defineConfig({
    base: '/static/vite_dist',
    build: {
        rollupOptions: {
            input: inputs,
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
        emptyOutDir: true,
        outDir: resolve(__dirname, '../web/static/vite_dist')
    },
    server: {
        strictPort: true,
        fs: {
            allow: ['..']
        }
    },
    resolve: {
        alias: {
            '!': resolve(__dirname),
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
