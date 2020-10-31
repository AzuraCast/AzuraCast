const webpack = require('webpack');
const WebpackAssetsManifest = require('webpack-assets-manifest');

module.exports = {
  mode: 'production',
  entry: {
    VueTranslations: './vue/VueTranslations.js',
    Webcaster: './vue/Webcaster.vue',
    RadioPlayer: './vue/RadioPlayer.vue',
    PublicRadioPlayer: './vue/PublicRadioPlayer.vue',
    InlinePlayer: './vue/InlinePlayer.vue',
    SongRequest: './vue/SongRequest.vue',
    AdminStorageLocations: './vue/AdminStorageLocations.vue',
    StationMedia: './vue/StationMedia.vue',
    StationPlaylists: './vue/StationPlaylists.vue',
    StationStreamers: './vue/StationStreamers.vue',
    StationOnDemand: './vue/StationOnDemand.vue',
    StationProfile: './vue/StationProfile.vue'
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
        loader: 'vue-loader',
        options: {}
      },
      {
        test: /\.scss$/,
        use: [
          'vue-style-loader',
          'css-loader',
          'sass-loader'
        ]
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
