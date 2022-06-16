const MiniCssExtractPlugin = require("mini-css-extract-plugin");
const CopyPlugin = require("copy-webpack-plugin");
const webpack = require('webpack');
const path = require('path');

module.exports = {
    /*
    optimization: {
        splitChunks: {
            cacheGroups: {
                commons: {
                    name: 'commons',
                    chunks: 'initial',
                    minChunks: 2,
                },
            },
        },
    },
    */
    entry: {
        index: './resources/js/index.js',
        home: './resources/js/home/home.js',
        login: './resources/js/login/login.js',
        signup: './resources/js/signup/signup.js',
        emailVerification: './resources/js/email_verification/email_verification.js',
        forgotPassword: './resources/js/forgot_password/forgot_password.js',
        resetPassword: './resources/js/reset_password/reset_password.js',
        setPassword: './resources/js/set_password/set_password.js',
    },
    output: {
        filename: '[name].js',
        path: path.resolve(__dirname, 'public/assets'),
        publicPath: '/assets/',
        pathinfo: false
    },
    node: {
        fs: 'empty',
        child_process: 'empty',
        tls: 'empty',
        net: 'empty',
    },
    plugins: [
        new webpack.ProvidePlugin({
            $: 'jquery',
            jQuery: 'jquery'
        }),
        new MiniCssExtractPlugin({
            filename: './css/[name].css',
        }),
        new CopyPlugin({
                patterns: [
                    {from: './node_modules/bootstrap-tagsinput/dist', to: 'bootstrap-tagsinput'},
                    {from: './node_modules/typeahead.js/dist', to: 'typeahead'},
                    {from: './node_modules/jquery.cookie/jquery.cookie.js', to: 'jquery-cookie/jquery.cookie.js'},
                    {from: './node_modules/crypto-js/crypto-js.js', to: 'crypto-js/crypto-js.js'},
                    {from: './node_modules/pwstrength-bootstrap/dist', to: 'pwstrength-bootstrap'},
                    {from: './node_modules/sweetalert2/dist', to: 'sweetalert2'},
                    {from: './node_modules/urijs/src', to: 'urijs'},
                    {from: './node_modules/chosen-js', to: 'chosen-js'},
                    {from: './node_modules/moment', to: 'moment'},
                    {from: './node_modules/@github/clipboard-copy-element/dist', to: 'clipboard-copy-element'},
                    {from: './node_modules/simplemde/dist', to: 'simplemde'},
                ],
            }
        ),
    ],
    module: {
        rules: [
            {
                test: /\.(js|jsx)$/,
                exclude: /node_modules/,
                use: {
                    loader: 'babel-loader',
                    options: {
                        presets: [
                            [
                                "@babel/preset-env",
                                {"targets": {"node": "current"}}
                            ],
                            '@babel/preset-react',
                            '@babel/preset-flow'
                        ],
                        plugins: [
                            '@babel/plugin-proposal-object-rest-spread',
                            '@babel/plugin-proposal-class-properties'
                        ]
                    }
                }
            },
            {
                test: /\.css$/,
                use: [MiniCssExtractPlugin.loader, "css-loader"]
            },
            {
                test: /\.module\.scss/,
                use: [
                    MiniCssExtractPlugin.loader,
                    {
                        loader: 'css-loader',
                        options: {
                            modules: {
                                localIdentName: "[local]___[hash:base64:5]",
                                hashPrefix: 'schedule-filter-widget',
                            },
                            sourceMap: false
                        }
                    },
                    {
                        loader: 'sass-loader',
                        options: {
                            sourceMap: false
                        }
                    }
                ]
            },
            {
                test: /\.scss/,
                exclude: /\.module\.scss/,
                use: [
                    MiniCssExtractPlugin.loader,
                    // Translates CSS into CommonJS
                    "css-loader",
                    // Compiles Sass to CSS
                    "sass-loader",
                ],
            },
            {
                test: /\.(ttf|eot)(\?v=[0-9]\.[0-9]\.[0-9])?$/,
                use: "file-loader?name=fonts/[name].[ext]"
            },
            {
                test: /\.woff(2)?(\?v=[0-9]\.[0-9]\.[0-9])?$/,
                use: "url-loader?limit=10000&minetype=application/font-woff&name=fonts/[name].[ext]"
            },
            {
                test: /\.svg/,
                use: "file-loader?name=svg/[name].[ext]!svgo-loader"
            },
            {
                test: /\.jpg|\.png|\.gif$/,
                use: "file-loader?name=images/[name].[ext]"
            },
        ]
    }
};