const path = require('path');
const MiniCssExtractPlugin = require('mini-css-extract-plugin');

module.exports = {
    entry: {
        app: './src/app.js',
        place: './src/place.js',
    },
    output: {
        path: path.resolve(__dirname, 'dist'),
        filename: 'js/[name].bundle.js'
    },
    module: {
        rules: [
            {
                test: /\.(jpg|JPG|jpeg|png|gif|mp3|svg|ttf|woff2|woff|eot)$/,
                loader: "file-loader",
                options: {
                    name: 'images/[name].[ext]'
                },
            }, {
                test: /\.scss$/,
                use: [
                    MiniCssExtractPlugin.loader, {
                        loader: 'css-loader'
                    }, {
                        loader: 'sass-loader',
                        options: {
                            sourceMap: true,
                        }
                    }
                ]
            }
        ]
    },
    plugins: [
        // new webpack.ProvidePlugin({
        //     $: 'jquery',
        //     jQuery: 'jquery'
        // }),

        new MiniCssExtractPlugin({
            filename: 'css/style.css'
        }),
    ]
};