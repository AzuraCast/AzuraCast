const webpack = require('webpack');
const WebpackAssetsManifest = require('webpack-assets-manifest');

module.exports = {
  mode: 'production',
  entry: {
    VueTranslations: './vue/VueTranslations.js',
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
    StationsStreamers: './vue/Stations/Streamers.vue',
    StationsReportsOverview: './vue/Stations/Reports/Overview.vue'
  },
  resolve: {
    extensions: ['*', '.js', '.vue', '.json']
  },
  output: {
    publicPath: 'dist/',
    filename: '[name].js',
    sourceMapFilename: '[name].map',
    library: '[name]'
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
        test: /\.vue$/,
        loader: 'vue-loader'
      },
      {
        test: /\.scss$/,
        loader: 'vue-style-loader'
      }
    ]
  },
  plugins: [
    new WebpackAssetsManifest({
      output: '../web/static/webpack.json',
      writeToDisk: true,
      merge: true,
      publicPath: true,
      entrypoints: true
    })
  ],
  performance: {
    hints: false
  }
};
