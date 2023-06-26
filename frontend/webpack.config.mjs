import WebpackAssetsManifest from 'webpack-assets-manifest';
import {VueLoaderPlugin} from 'vue-loader';
import path from 'path';
import {glob} from 'glob';
import ESLintPlugin from 'eslint-webpack-plugin';

const __dirname = path.dirname(new URL(import.meta.url).pathname);

export default {
    mode: (process.env.NODE_ENV === 'production') ? 'production' : 'development',
    entry: glob.sync('./vue/pages/**/*.js').reduce((acc, path) => {
        // vue/pages/Admin/Index becomes AdminIndex
        const entry = path.replace(/\.js$/g, '')
            .replace(/^vue\/pages\//g, '')
            .replace(/\//g, '');

        acc[entry] = './' + path
        return acc
    }, {}),
    resolve: {
        enforceExtension: false,
        alias: {
            '~': path.resolve(__dirname, './vue')
        },
        extensions: ['.js', '.vue', '.json']
    },
    output: {
        path: path.resolve(__dirname, '../web/static/webpack_dist'),
        publicPath: '/static/webpack_dist/',
        filename: '[name].[contenthash].js',
        sourceMapFilename: '[name].[contenthash].map',
        library: '[name]',
        assetModuleFilename: 'images/[contenthash][ext]'
    },
    optimization: {
        splitChunks: {
            cacheGroups: {
                translations: {
                    test: /translations\.json$/,
                    chunks: 'all',
                    enforce: true,
                    name: 'translations'
                },
                vendor: {
                    test: /[\\/]node_modules[\\/]/,
                    chunks: 'all',
                    enforce: true,
                    name(module) {
                        // get the name. E.g. node_modules/packageName/not/this/part.js
                        // or node_modules/packageName
                        const packageName = module.context.match(/[\\/]node_modules[\\/](.*?)([\\/]|$)/)[1];

                        // npm package names are URL-safe, but some servers don't like @ symbols
                        return `vendor-${packageName.replace('@', '')}`;
                    }
                }
            }
        }
    },
    module: {
        rules: [
            {
                test: /\.vue$/i,
                loader: 'vue-loader',
                options: {
                    compilerOptions: {
                        compatConfig: {
                            MODE: 2
                        }
                    }
                }
            },
            {
                test: /\.scss$/i,
                use: [
                    'vue-style-loader',
                    'css-loader',
                    'sass-loader'
                ]
            },
            {
                test: /\.css$/i,
                use: [
                    'vue-style-loader',
                    'css-loader'
                ]
            },
            {
                test: /\.(png|jpe?g|gif|svg|eot|ttf|woff|woff2)$/i,
                type: 'asset/resource'
            },
        ]
    },
    plugins: [
        new ESLintPlugin({
            extensions: ['js', 'vue'],
            fix: true,
        }),
        new WebpackAssetsManifest({
            output: path.resolve(__dirname, '../web/static/webpack.json'),
            writeToDisk: true,
            merge: true,
            publicPath: true,
            entrypoints: true
        }),
        new VueLoaderPlugin()
    ],
    target: 'web',
    performance: {
        hints: false
    }
};
