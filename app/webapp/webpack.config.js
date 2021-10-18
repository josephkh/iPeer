'use strict';
const path = require('path');
const HtmlWebpackPlugin = require('html-webpack-plugin');
const MiniCssExtractPlugin = require('mini-css-extract-plugin');
// Try the environment variable, otherwise use root
const ASSET_PATH = process.env.ASSET_PATH || '/';  // ASSET_PATH='../webroot/js'

const config = {
  mode: process.env.NODE_ENV === 'production' ? 'production' : 'development',
  devtool: process.env.NODE_ENV === 'production' ? 'source-map' : 'inline-source-map',
  entry: {
    main: [
      'regenerator-runtime/runtime.js', // regeneratorRuntime not defined
      path.resolve(__dirname, './src/main.tsx')
    ]
  },
  output: {
    // path: path.resolve(__dirname, '../webroot/js', 'dist'),
    path: path.resolve(__dirname, 'dist'),
    // filename: '[name].bundle.js',
    filename: './js/[name].bundle.js',
    publicPath: ASSET_PATH,
    assetModuleFilename: 'img/[name][ext]',
    clean: true
  },
  devServer: {
    // historyApiFallback: true,
    historyApiFallback: {
      index: '/',
    },
    static: {
      directory: path.resolve(__dirname, 'dist'),
      watch: true,
    },
    compress: true,
    port: 3000,
    open: true,
    hot: true,
  },
  resolve: {
    extensions: ['.ts', '.tsx', '.js', '.jsx', '.json', '.scss'],
  },
  module: {
    rules: [
      {
        test: /\.(js|ts)x?$/,
        exclude: /node_modules/,
        use: {
          loader: 'babel-loader',
          options: {
            'presets': [
              '@babel/preset-env',
              '@babel/preset-react',
              '@babel/preset-typescript'
            ]
          }
        }
      },
      { // Loading CSS/SCSS/SASS
        test: /\.s[ac]ss$/i,
        use: [
          MiniCssExtractPlugin.loader,
          'css-loader',
          'sass-loader'
        ]
      },
      { // Loading Images
        test: /\.(svg|ico|png|gif|jp[e]g|webp)$/,
        type: 'asset/resource'
      },
      { // Loading Fonts
        test: /\.(woff|woff2|eot|ttf|otf)$/i,
        type: 'asset/resource',
      },
    ]
  },
  plugins: [
    new MiniCssExtractPlugin({
      filename: 'css/[name].styles.css',
      // chunkFilename: './public/styles.css',
      // minimize: true
    }),
    new HtmlWebpackPlugin({
      template: path.resolve(__dirname, './public/index.html'),
      favicon: path.resolve(__dirname, './public/favicon.ico'),
      title: 'iPeer',
      filename: 'index.html',
      minify: {
        removeComments: true,
        collapseWhitespace: true,
      },
    })
  ]
};

module.exports = config;