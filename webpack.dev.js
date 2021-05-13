const merge                     = require('webpack-merge');
const common                    = require('./webpack.common.js');

module.exports = merge(common, {
    watch:true,
    plugins: [
        //new CleanWebpackPlugin(),
    ],
    mode: 'development',
    devtool: 'inline-source-map',
    devServer: {
        contentBase: './public/assets',
        historyApiFallback: true
    },
    optimization: {
        minimize: false
    },
});