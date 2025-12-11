import {defineConfig} from 'vite'
import vue from '@vitejs/plugin-vue'
import {glob} from "glob";
import {resolve} from "path";
import eslintPlugin from "@nabla/vite-plugin-eslint";
import Icons from 'unplugin-icons/vite';

const inputs = {};

glob.sync('./frontend/js/pages/**/*.js').forEach((path) => {
    // vue/pages/Admin/Index becomes AdminIndex
    const entry = path.replace(/\.js$/g, '')
        .replace(/^frontend\/js\/pages\//g, '')
        .replace(/\//g, '');

    inputs[entry] = resolve(__dirname, path)
});

// Ensure all images are included in the manifest.
glob.sync('./frontend/img/**/*.*').forEach((path) => {
    const entry = path.replace(/\.[^/.]+$/g, '')
        .replace(/^frontend\/img\//g, '')
        .replace(/\//g, '');

    inputs['img_' + entry] = resolve(__dirname, path)
});

inputs['Layout'] = resolve(__dirname, './frontend/js/layout.js');

console.log(inputs);

const frontendBaseDir = resolve(__dirname, './frontend/');

// https://vitejs.dev/config/
export default defineConfig({
    root: frontendBaseDir,
    base: '/static/vite_dist',
    build: {
        rollupOptions: {
            input: inputs,
            output: {
                manualChunks: {
                    vue: ['vue'],
                    estoolkit: ['es-toolkit'],
                    leaflet: ['leaflet'],
                    hlsjs: ['hls.js'],
                    zxcvbn: ['zxcvbn'],
                },
                chunkFileNames: (assetInfo) => {
                    // Special handling for translations
                    if (assetInfo.name && assetInfo.name === 'translations') {
                        const translationParts = assetInfo.facadeModuleId
                            .split('/');

                        const translationPath = translationParts[translationParts.length - 2];
                        return `translations-${translationPath}-[hash:8].js`
                    }

                    // Name chunk after its file if it has one.
                    if (assetInfo.moduleIds.length === 1) {
                        const modulePath = assetInfo.moduleIds.slice().shift();

                        if (modulePath.includes(frontendBaseDir)) {
                            let path = modulePath.replace(frontendBaseDir + '/', '')
                                .replaceAll('/', '-');

                            if (path.includes('?')) {
                                path = path.split('?').slice(0, -1).join('?');
                            }
                            if (path.includes('.')) {
                                path = path.split('.').slice(0, -1).join('?');
                            }

                            return `${path}-[hash:8].js`;
                        }

                        if (modulePath.indexOf('~icons') === 0) {
                            const path = modulePath.split('/')
                                .slice(1)
                                .join('-');

                            return `icon-${path}-[hash:8].js`;
                        }
                    }

                    if (assetInfo.name) {
                        const assetName = assetInfo.name.replace(
                            '.vue_vue_type_style_index_0_lang',
                            ''
                        ).replace(
                            '.vue_vue_type_script_setup_true_lang',
                            ''
                        );

                        return `${assetName}-[hash:8].js`;
                    }

                    return '[name]-[hash:8].js';
                }
            }
        },
        manifest: true,
        emptyOutDir: true,
        chunkSizeWarningLimit: '1m',
        outDir: resolve(__dirname, './web/static/vite_dist')
    },
    css: {
        preprocessorOptions: {
            scss: {
                quietDeps: true,
                silenceDeprecations: ['legacy-js-api', 'import']
            }
        }
    },
    server: {
        strictPort: true,
        host: true,
        allowedHosts: true,
        fs: {
            allow: [
                resolve(__dirname, './frontend/'),
                resolve(__dirname, './node_modules/'),
                resolve(__dirname, './translations/')
            ]
        }
    },
    resolve: {
        alias: {
            '!': resolve(__dirname, '.'),
            '~': resolve(__dirname, './frontend')
        },
        extensions: ['.mjs', '.js', '.mts', '.ts', '.jsx', '.tsx', '.json', '.vue']
    },
    plugins: [
        vue(),
        Icons({
            compiler: 'vue3',
            iconCustomizer(collection, icon, props) {
                props.class = 'icon';
                props.fill = 'currentColor';
                props.focusable = 'false';
                props['aria-hidden'] = 'true';
            },
        }),
        eslintPlugin(),
    ],
})
