const webpack = require('webpack');
const WebpackAssetsManifest = require('webpack-assets-manifest');
const { VueLoaderPlugin } = require('vue-loader');
const path = require('path');

module.exports = {
  mode: 'development',
  entry: {
    Base: './vue/VueBase.js',
    InlinePlayer: './vue/InlinePlayer.vue',
    Dashboard: './vue/Dashboard.vue',
    AdminBranding: './vue/Admin/Branding.vue',
    AdminStorageLocations: './vue/Admin/StorageLocations.vue',
    PublicFullPlayer: './vue/Public/FullPlayer.vue',
    PublicHistory: './vue/Public/History.vue',
    PublicOnDemand: './vue/Public/OnDemand.vue',
    PublicPlayer: './vue/Public/Player.vue',
    PublicRequests: './vue/Public/Requests.vue',
    PublicSchedule: './vue/Public/Schedule.vue',
    PublicWebDJ: './vue/Public/WebDJ.vue',
    StationsMedia: './vue/Stations/Media.vue',
    StationsMounts: './vue/Stations/Mounts.vue',
    StationsPlaylists: './vue/Stations/Playlists.vue',
    StationsPodcasts: './vue/Stations/Podcasts.vue',
    StationsProfile: './vue/Stations/Profile.vue',
    StationsQueue: './vue/Stations/Queue.vue',
    StationsRemotes: './vue/Stations/Remotes.vue',
    StationsStreamers: './vue/Stations/Streamers.vue',
    StationsReportsListeners: './vue/Stations/Reports/Listeners.vue',
    StationsReportsRequests: './vue/Stations/Reports/Requests.vue',
    StationsReportsOverview: './vue/Stations/Reports/Overview.vue',
    StationsReportsTimeline: './vue/Stations/Reports/Timeline.vue'
  },
  resolve: {
    enforceExtension: false,
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
        moment: {
          test: /[\\/]node_modules[\\/]moment/,
          name: 'vendor-moment',
          priority: 2,
          chunks: 'initial',
          enforce: true
        },
        fullcalendar: {
          test: /[\\/]node_modules[\\/]@fullcalendar/,
          name: 'vendor-fullcalendar',
          priority: 2,
          chunks: 'initial',
          enforce: true
        },
        leaflet: {
          test: /[\\/]node_modules[\\/]leaflet/,
          name: 'vendor-leaflet',
          priority: 2,
          chunks: 'initial',
          enforce: true
        },
        vuelidate: {
          test: /[\\/]node_modules[\\/]vuelidate/,
          name: 'vendor-vuelidate',
          priority: 2,
          chunks: 'initial',
          enforce: true
        },
        vendor: {
          test: /[\\/]node_modules[\\/]/,
          name: 'vendor',
          chunks: 'initial',
          enforce: true
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
