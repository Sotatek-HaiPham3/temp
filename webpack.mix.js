const mix = require('laravel-mix');

/*
 |--------------------------------------------------------------------------
 | Mix Asset Management
 |--------------------------------------------------------------------------
 |
 | Mix provides a clean, fluent API for defining some Webpack build steps
 | for your Laravel application. By default, we are compiling the Sass
 | file for the application as well as bundling up all the JS files.
 |
 */

const webpackConfig   = {
  resolve: {
    extensions: [".js", ".jsx"]
  },
  module: {
    rules: [
    {
      test: /\.(ts|js)x?$/,
      exclude: /(node_modules)/,
      use: {
        loader: "babel-loader",
        options: {
          plugins: [
          "@babel/plugin-proposal-optional-chaining"
          ]
        }
      }
    }
    ]
  }
};

mix.js([
      'resources/assets/js/admin/app.js',
      'resources/assets/js/admin/lib/adminlte.js'
    ], 'public/js/admin/app.js')
   .webpackConfig(webpackConfig)
   .sass('resources/assets/sass/admin/app.scss', 'public/css/admin.css')
   .version();

/**
 * Webpack configuration
 */


mix.webpackConfig({
    plugins: [
    ],
    resolve: {
      alias: {
        "requestfactory": path.resolve(__dirname, './resources/assets/js/request/RequestFactory.js'),
        "common": path.resolve(__dirname, './resources/assets/js/common'),
        "lib": path.resolve(__dirname, './resources/assets/js/lib'),
      }
    },
});
