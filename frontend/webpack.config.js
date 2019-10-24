const webpack = require('webpack');
//const CopyWebpackPlugin = require('copy-webpack-plugin');
const Encore = require('@symfony/webpack-encore');

Encore
    .setOutputPath('./../web/assets/')
    .setPublicPath('/assets')
    .addEntry('app', './src/js/main')
    .addStyleEntry('styles', './src/style/main.less')
    .enableLessLoader()
    .autoProvidejQuery()
    .enableSourceMaps(!Encore.isProduction())
    .cleanupOutputBeforeBuild()
    .enableVersioning()
;

Encore
    .addPlugin(new webpack.optimize.CommonsChunkPlugin({
        name: "vendor",
        minChunks: function (module) {
            return module.context && module.context.indexOf("node_modules") !== -1;
        }
    }))
    .addPlugin(new webpack.optimize.CommonsChunkPlugin({
        name: "manifest",
        minChunks: Infinity
    }))
    // .addPlugin(new CopyWebpackPlugin([
    //     {from: 'src/static/vendor/sciact', to: 'static'}
    // ]))
;

module.exports = Encore.getWebpackConfig();