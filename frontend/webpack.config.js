const webpack = require('webpack');
const WebpackAssetsManifest = require('webpack-assets-manifest');
const { VueLoaderPlugin } = require('vue-loader');
const path = require('path');

module.exports = {
  mode: 'production',
  entry: {
    Dashboard: '~/pages/Dashboard.js',
    AdminAuditLog: '~/pages/Admin/AuditLog.js',
    AdminBranding: '~/pages/Admin/Branding.js',
    AdminCustomFields: '~/pages/Admin/CustomFields.js',
    AdminPermissions: '~/pages/Admin/Permissions.js',
    AdminStorageLocations: '~/pages/Admin/StorageLocations.js',
    PublicFullPlayer: '~/pages/Public/FullPlayer.js',
    PublicHistory: '~/pages/Public/History.js',
    PublicOnDemand: '~/pages/Public/OnDemand.js',
    PublicPlayer: '~/pages/Public/Player.js',
    PublicRequests: '~/pages/Public/Requests.js',
    PublicSchedule: '~/pages/Public/Schedule.js',
    PublicWebDJ: '~/pages/Public/WebDJ.js',
    StationsMedia: '~/pages/Stations/Media.js',
    StationsMounts: '~/pages/Stations/Mounts.js',
    StationsPlaylists: '~/pages/Stations/Playlists.js',
    StationsPodcasts: '~/pages/Stations/Podcasts.js',
    StationsProfile: '~/pages/Stations/Profile.js',
    StationsQueue: '~/pages/Stations/Queue.js',
    StationsRemotes: '~/pages/Stations/Remotes.js',
    StationsStreamers: '~/pages/Stations/Streamers.js',
    StationsReportsListeners: '~/pages/Stations/Reports/Listeners.js',
    StationsReportsRequests: '~/pages/Stations/Reports/Requests.js',
    StationsReportsOverview: '~/pages/Stations/Reports/Overview.js',
    StationsReportsPerformance: '~/pages/Stations/Reports/Performance.js',
    StationsReportsTimeline: '~/pages/Stations/Reports/Timeline.js'
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
          name (module) {
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
