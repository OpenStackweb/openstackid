// babel.config.js
module.exports = {
    presets: [
        [
            "@babel/preset-env",
            {
                "targets": {
                    "edge": "17",
                    "firefox": "60",
                    "chrome": "67",
                    "safari": "11.1",
                    "node":"current"
                }
                // no useBuiltIns/corejs: this config is consumed by babel-jest only (webpack.common.js
                // carries its own inline babel options, no polyfills) and the corejs pin pointed at
                // core-js@3 while the installed dependency is core-js@2 - polyfill imports emitted for
                // jest could never resolve. Tests run under current node; no polyfills needed.
            }
        ],
        "@babel/preset-react",
        "@babel/preset-flow"
    ],
    plugins: [
        "@babel/plugin-proposal-object-rest-spread",
        "@babel/plugin-proposal-class-properties"
    ]
};

