const webpack = require('webpack');

module.exports = {
  mode: 'production',
  entry: {
    VueTranslations: './vue/VueTranslations.js',
    Webcaster: './vue/Webcaster.vue',
    RadioPlayer: './vue/RadioPlayer.vue',
    InlinePlayer: './vue/InlinePlayer.vue',
    StationMedia: './vue/StationMedia.vue',
    StationPlaylists: './vue/StationPlaylists.vue',
    StationStreamers: './vue/StationStreamers.vue',
    StationOnDemand: './vue/StationOnDemand.vue'
  },
  resolve: {
    extensions: ['*', '.js', '.vue', '.json']
  },
  output: {
    publicPath: '/static/dist',
    filename: '[name].js',
    sourceMapFilename: '[name].map',
    library: '[name]'
  },
  optimization: {
    splitChunks: {
      cacheGroups: {
        vendor: {
          test: /node_modules/,
          chunks: 'initial',
          name: 'vendor',
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
    new webpack.IgnorePlugin({
      resourceRegExp: /^vue$/
    })
  ],
  performance: {
    hints: false
  }
};