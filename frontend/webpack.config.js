const webpack = require('webpack');
const WebpackAssetsManifest = require('webpack-assets-manifest');
const {VueLoaderPlugin} = require('vue-loader');
const path = require('path');

module.exports = {
    mode: (process.env.NODE_ENV === 'production') ? 'production' : 'development',
    entry: {
        Account: '~/pages/Account.js',
        Dashboard: '~/pages/Dashboard.js',
        AdminApiKeys: '~/pages/Admin/ApiKeys.js',
        AdminAuditLog: '~/pages/Admin/AuditLog.js',
        AdminBackups: '~/pages/Admin/Backups.js',
        AdminBranding: '~/pages/Admin/Branding.js',
        AdminCustomFields: '~/pages/Admin/CustomFields.js',
        AdminGeoLite: '~/pages/Admin/GeoLite.js',
        AdminIndex: '~/pages/Admin/Index.js',
        AdminLogs: '~/pages/Admin/Logs.js',
        AdminPermissions: '~/pages/Admin/Permissions.js',
        AdminSettings: '~/pages/Admin/Settings.js',
        AdminShoutcast: '~/pages/Admin/Shoutcast.js',
        AdminStereoTool: '~/pages/Admin/StereoTool.js',
        AdminStations: '~/pages/Admin/Stations.js',
        AdminStorageLocations: '~/pages/Admin/StorageLocations.js',
        AdminUsers: '~/pages/Admin/Users.js',
        PublicFullPlayer: '~/pages/Public/FullPlayer.js',
        PublicHistory: '~/pages/Public/History.js',
        PublicOnDemand: '~/pages/Public/OnDemand.js',
        PublicPlayer: '~/pages/Public/Player.js',
        PublicRequests: '~/pages/Public/Requests.js',
        PublicSchedule: '~/pages/Public/Schedule.js',
        PublicWebDJ: '~/pages/Public/WebDJ.js',
        Recover: '~/pages/Recover.js',
        SetupRegister: '~/pages/Setup/Register.js',
        SetupSettings: '~/pages/Setup/Settings.js',
        SetupStation: '~/pages/Setup/Station.js',
        StationsBulkMedia: '~/pages/Stations/BulkMedia.js',
        StationsFallback: '~/pages/Stations/Fallback.js',
        StationsHelp: '~/pages/Stations/Help.js',
        StationsHlsStreams: '~/pages/Stations/HlsStreams.js',
        StationsLiquidsoapConfig: '~/pages/Stations/LiquidsoapConfig.js',
        StationsMedia: '~/pages/Stations/Media.js',
        StationsMounts: '~/pages/Stations/Mounts.js',
        StationsPlaylists: '~/pages/Stations/Playlists.js',
        StationsPodcasts: '~/pages/Stations/Podcasts.js',
        StationsProfile: '~/pages/Stations/Profile.js',
        StationsProfileEdit: '~/pages/Stations/ProfileEdit.js',
        StationsQueue: '~/pages/Stations/Queue.js',
        StationsRemotes: '~/pages/Stations/Remotes.js',
        StationsStereoToolConfig: '~/pages/Stations/StereoToolConfig.js',
        StationsStreamers: '~/pages/Stations/Streamers.js',
        StationsReportsListeners: '~/pages/Stations/Reports/Listeners.js',
        StationsReportsRequests: '~/pages/Stations/Reports/Requests.js',
        StationsReportsOverview: '~/pages/Stations/Reports/Overview.js',
        StationsReportsSoundExchange: '~/pages/Stations/Reports/SoundExchange.js',
        StationsReportsTimeline: '~/pages/Stations/Reports/Timeline.js',
        StationsSftpUsers: '~/pages/Stations/SftpUsers.js',
        StationsWebhooks: '~/pages/Stations/Webhooks.js'
    },
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
                use: [
                    'vue-loader'
                ]
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
            }
        ]
    },
    plugins: [
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
