const path = require('path');
const fs = require('fs');
const MiniCssExtractPlugin = require('mini-css-extract-plugin');
const postcss = require('postcss');
const PrefixWrap = require('postcss-prefixwrap');
// Kaldırılanlar: purgecss-webpack-plugin, glob-all
// Çift minify önlendi: postcss-loader, clean-css-loader kaldırıldı

const plugins = [
    new MiniCssExtractPlugin({
        filename: '[name].css',
    }),

    // main-admin.css — main.css'e .acf-block-preview prefix'i ekler
    // ACF block editor preview'unda tema stillerinin doğru görünmesi için gerekli
    {
        apply: (compiler) => {
            compiler.hooks.afterEmit.tap('AfterEmitPlugin', (compilation) => {
                const mainCssPath   = path.resolve(__dirname, 'static/css/main.css');
                const adminCssPath  = path.resolve(__dirname, 'static/css/main-admin.css');

                const mainContent = fs.readFileSync(mainCssPath, 'utf8');
                const prefixed    = postcss([PrefixWrap('.acf-block-preview', {
                    ignoredSelectors: [':root', '.wp-block'],
                    prefixRootTags: true,
                })]).process(mainContent).css;

                fs.writeFileSync(adminCssPath, prefixed);
            });
        },
    },

    // font-faces.css — icons.css içindeki @font-face bloklarını ayırır
    // ttf/otf/eot/woff formatları temizlenerek sadece woff2 kalır, lazy load için ayrı dosya
    {
        apply: (compiler) => {
            compiler.hooks.afterEmit.tap('ExtractFontFacesPlugin', (compilation) => {
                const iconsCssPath    = path.resolve(__dirname, 'static/css/icons.css');
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

                    const cleanedIconsCss = content.replace(fontFaceRegex, '');
                    fs.writeFileSync(iconsCssPath, cleanedIconsCss);

                    console.log('✓ font-face blokları temizlendi → font-faces.css oluşturuldu.');
                }
            });
        },
    },
];

const entries = {};

// Sadece var olan dosyaları entry olarak ekle
const validateEntry = (filePath) => {
    if (Array.isArray(filePath)) {
        const filtered = filePath.filter(fp => fs.existsSync(fp));
        return filtered.length > 0 ? filtered : null;
    }
    return fs.existsSync(filePath) ? filePath : null;
};

const checkAndAdd = (name, entryPath) => {
    const valid = validateEntry(entryPath);
    if (valid) entries[name] = valid;
};

checkAndAdd('icons',            path.join(__dirname, 'static/css/icons.css'));
checkAndAdd('header',           path.join(__dirname, 'static/css/header.css'));
checkAndAdd('header-rtl',       path.join(__dirname, 'static/css/header-rtl.css'));
checkAndAdd('main',             path.join(__dirname, 'static/css/main.css'));
checkAndAdd('main-rtl',         path.join(__dirname, 'static/css/main-rtl.css'));

checkAndAdd('main-combined', [
    path.join(__dirname, 'static/css/icons.css'),
    path.join(__dirname, 'static/css/header.css'),
    path.join(__dirname, 'static/css/main.css'),
    path.join(__dirname, 'static/css/blocks.css'),
]);

checkAndAdd('main-combined-rtl', [
    path.join(__dirname, 'static/css/icons.css'),
    path.join(__dirname, 'static/css/header-rtl.css'),
    path.join(__dirname, 'static/css/main-rtl.css'),
    path.join(__dirname, 'static/css/blocks-rtl.css'),
]);

module.exports = {
    mode: 'production',
    entry: entries,
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
                // Sadece MiniCssExtractPlugin + css-loader — çift minify yok.
                // Girdi dosyaları SCSS → scssphp aşamasında zaten minified geliyor.
                use: [
                    MiniCssExtractPlugin.loader,
                    {
                        loader: 'css-loader',
                        options: {
                            import: false, // @import resolve etme — zaten çözülmüş
                            url: true,     // url() font referansları için gerekli
                        },
                    },
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
