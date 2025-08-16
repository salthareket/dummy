const path = require('path');
const fs = require('fs');
const MiniCssExtractPlugin = require('mini-css-extract-plugin');
const { PurgeCSSPlugin } = require('purgecss-webpack-plugin');
const postcss = require('postcss');
const PrefixWrap = require('postcss-prefixwrap');
const glob = require('glob-all');

const enable_ecommerce = process.env.enable_ecommerce === 'true'; // PHP'den gelen sabiti al
const enable_purgecss = false; // 🔥 KOŞULLU hale getirildi — true yaparsan aktif olur

const paths = [
    path.join(__dirname, '../../plugins/**/*.php'),
    path.join(__dirname, '**/*.php'),
    path.join(__dirname, 'static/js/**/*.js'),
    path.join(__dirname, 'theme/templates/**/*.twig'),
    path.join(__dirname, 'vendor/salthareket/theme/src//templates/**/*.twig')
];
if (enable_ecommerce) {
    paths.push(path.join(__dirname, 'templates/**/*.twig'));
} else {
    paths.push(path.join(__dirname, 'templates/!(woo)/**/*.twig'));
}

// JSON dosyasının yolu
const jsonPath = path.resolve(__dirname, 'theme/static/data/css_safelist.json');
let dynamicSafelist = [];
if (fs.existsSync(jsonPath)) {
    const jsonContent = fs.readFileSync(jsonPath, 'utf8');
    dynamicSafelist = JSON.parse(jsonContent).dynamicSafelist || [];
}
const staticSafelist = [
    'fa-facebook',
    'fa-facebook-f',
    'fa-x-twitter',
    'fa-instagram',
    'fa-linkedin',
    'fa-threads',
    'fa-youtube',
    'fa-vimeo',
    'fa-vimeo-v',
    'fa-tiktok',
    'fa-whatsapp',
    'plyr',
    'ratio',
    'fade',
    'show',
    'table',
    'visible',
    'invisible',
    /^fixed-/,
    /^svg-/,
    /^fa-file-/,
    /^header-tools-/,
    /^offcanvas-/,
    /^text-/,
    /^btn-/,
    /^z-/,
    /^p-/,
    /^px-/,
    /^py-/,
    /^pt-/,
    /^pb-/,
    /^ps-/,
    /^pe-/,
    /^m-/,
    /^mx-/,
    /^my-/,
    /^mt-/,
    /^mb-/,
    /^ms-/,
    /^me-/,
    /^g-/,
    /^gx-/,
    /^gy-/,
    /^d-/,
    /^w-/,
    /^h-/,
    /^mh-/,
    /^min-/,
    /^mw-/,
    /^ls-/,
    /^lh-/,
    /^row-/,
    /^col-/,
    /^flex-/,
    /^justify-/,
    /^align-/,
    /^font-/,
    /^border-/,
    /^ratio-/,
    /^object-/,
    /^swiper-/,
    /^plyr/,
    /^plyr-/,
    /^plyr_/,
    /^ug-/,
    /^tease-/,
    /^img-/,
    /^bg-/,
    /^aos-/,
    /^data-aos/,
    /^page-/,
    /^single-/,
    /^block-/,
    /^archive-/,
    /^table-/,
    /^fw-/,
    /^title-/,
    /^text-/,
    /^fixed-/,
    /^form--/,
    /^position-/,
    'jarallax',
    /^jarallax-/,
    /^jg-/,
    /^lg-/,
    /^icon-/,
    /^label-/,
    /^slinky-/,
];
const combinedSafelist = [...dynamicSafelist, ...staticSafelist];

const plugins = [
    new MiniCssExtractPlugin({
        filename: '[name].css',
    }),
    {
        apply: (compiler) => {
            compiler.hooks.afterEmit.tap('AfterEmitPlugin', (compilation) => {
                const acfBlocksFilePath = path.resolve(__dirname, 'static/css/main.css');
                const acfBlocksV1FilePath = path.resolve(__dirname, 'static/css/main-admin.css');

                const acfBlocksContent = fs.readFileSync(acfBlocksFilePath, 'utf8');
                const prefixedAcfBlocksContent = postcss([PrefixWrap(".acf-block-preview", {
                    ignoredSelectors: [":root", ".wp-block"],
                    prefixRootTags: true,
                })]).process(acfBlocksContent).css;

                fs.writeFileSync(acfBlocksV1FilePath, prefixedAcfBlocksContent);
            });
        },
    },
    {
        apply: (compiler) => {
            compiler.hooks.afterEmit.tap('ExtractFontFacesPlugin', (compilation) => {
                const iconsCssPath = path.resolve(__dirname, 'static/css/icons.css');
                const fontFacesCssPath = path.resolve(__dirname, 'static/css/font-faces.css');

                if (!fs.existsSync(iconsCssPath)) {
                    console.warn('⚠ icons.css bulunamadı.');
                    return;
                }

                let content = fs.readFileSync(iconsCssPath, 'utf8');
                const fontFaceRegex = /@font-face\s*{[^}]+}/gi;
                const matches = content.match(fontFaceRegex);

                if (matches && matches.length > 0) {
                    const cleanedFontFaces = matches.map(face => {
                        return face
                            .replace(/url\([^)]+?\.(ttf|otf|eot|woff)(\?[^)]*)?\)\s*format\([^)]+\),?/gi, '')
                            .replace(/,\s*}/g, '}')
                            .replace(/src:\s*,/g, 'src:');
                    });

                    fs.writeFileSync(fontFacesCssPath, cleanedFontFaces.join('\n\n'));

                    // icons.css'ten font-face bloklarını çıkar
                    const cleanedIconsCss = content.replace(fontFaceRegex, '');
                    fs.writeFileSync(iconsCssPath, cleanedIconsCss);

                    console.log(`✓ font-face blokları temizlendi → font-faces.css oluşturuldu.`);
                }
            });
        }
    }
];

// 🔥 Koşullu olarak PurgeCSS eklentisini ekle
if (enable_purgecss) {
    plugins.push(
        new PurgeCSSPlugin({
            paths: glob.sync(paths),
            purgeOptions: {
                rejected: false,
            },
            safelist: {
                standard: combinedSafelist,
            },
            keyframes: false,
        })
    );
}

module.exports = {
    mode: 'production',
    entry: {
        icons: path.join(__dirname, 'static/css/icons.css'),
        main: path.join(__dirname, 'static/css/main.css'),
        "main-rtl": path.join(__dirname, 'static/css/main-rtl.css'),
        "main-combined": [
            path.join(__dirname, 'static/css/icons.css'),
            path.join(__dirname, 'static/css/main.css'),
            path.join(__dirname, 'static/css/blocks.css'),
        ],
        "main-combined-rtl": [
            path.join(__dirname, 'static/css/icons.css'),
            path.join(__dirname, 'static/css/main-rtl.css'),
            path.join(__dirname, 'static/css/blocks-rtl.css'),
        ],
    },
    output: {
        path: path.resolve(__dirname, 'static/css/'),
    },
    resolve: {
        modules: [path.resolve(__dirname, '../../../node_modules/'), 'node_modules'],
    },
    module: {
        rules: [
            {
                test: /\.css$/,
                use: [
                    MiniCssExtractPlugin.loader,
                    'css-loader',
                    'postcss-loader',
                    {
                        loader: 'clean-css-loader',
                        options: {
                            level: 1
                        }
                    }
                ],
            },
            {
                test: /\.(woff|woff2|eot|ttf|otf)$/i,
                type: 'asset/resource',
                generator: {
                    filename: '../fonts/[hash][ext][query]',
                },
            },
        ],
    },
    plugins,
};