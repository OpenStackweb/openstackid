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
        clients: './resources/js/oauth2/profile/clients/clients.js',
        consent: './resources/js/oauth2/consent.js',
        editUser: './resources/js/admin/edit_user/edit_user.js',
        emailVerification: './resources/js/email_verification/email_verification.js',
        forgotPassword: './resources/js/forgot_password/forgot_password.js',
        home: './resources/js/home/home.js',
        login: './resources/js/login/login.js',
        profile: './resources/js/profile/profile.js',
        resetPassword: './resources/js/reset_password/reset_password.js',
        setPassword: './resources/js/set_password/set_password.js',
        signup: './resources/js/signup/signup.js',
        editClient: './resources/js/oauth2/profile/edit_client/edit_client.js',
    },
    output: {
        filename: '[name].js',
        path: path.resolve(__dirname, 'public/assets'),
        publicPath: '/assets/',
        pathinfo: false
    },
    resolve: {
        fallback: {
            "fs" : false,
            "crypto" : false,
            path: false,
            stream: false,
            buffer: false,
            http: false,
            os: false,
            zlib:false,
            https: false,
            url:false,
            assert:false,
            tls: false,
            net:false,
            child_process: false
        }
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
                test: /\.less/,
                use: [MiniCssExtractPlugin.loader, "css-loader", "less-loader"]
            },
            {
                test: /\.module\.scss/,
                use: [
                    MiniCssExtractPlugin.loader,
                    {
                        loader: 'css-loader',
                        options: {
                            modules: true,
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
                use: [MiniCssExtractPlugin.loader, "css-loader", 'sass-loader'],
            },
            {
                test: /\.(ttf|eot)(\?v=[0-9]\.[0-9]\.[0-9])?$/,
                type: "asset/resource"
            },
            {
                test: /\.woff(2)?(\?v=[0-9]\.[0-9]\.[0-9])?$/,
                type: "asset/resource"
            },
            {
                test: /\.svg/,
                type: "asset/resource"
            },
            {
                test: /\.jpg|\.png|\.gif$/,
                type: "asset/resource"
            },
        ]
    }
};