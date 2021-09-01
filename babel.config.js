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
                },
                "useBuiltIns": "usage",
                "corejs": "3.9.1"
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

