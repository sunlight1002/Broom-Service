// webpack.mix.js

const mix = require("laravel-mix");

mix.js("resources/js/app.js", "public/js")
    .react()
    .extract(["react"])
    .postCss("resources/css/app.css", "public/css", [
        //
    ])
    .webpackConfig({
        module: {
            rules: [
                {
                    test: /\.pdf$/, // Matches PDF files
                    use: "file-loader", // Use file-loader for PDFs
                },
            ],
        },
    });
