const defaultConfig = require('@wordpress/scripts/config/webpack.config');
const path = require('path');
const glob = require('glob');

// Get all entry points
const entries = [...glob.sync('./src/js/*.js'), ...glob.sync('./src/css/*.css')]
    .reduce((acc, file) => {
        const name = path.basename(file, path.extname(file));
        acc[name] = './' + file;
        return acc;
    }, {});

// Create a modified config with source maps enabled
const config = {
    ...defaultConfig,
    entry: entries,
    devtool: 'source-map',
};

// Enable source maps for CSS
if (config.module && config.module.rules) {
    config.module.rules.forEach(rule => {
        if (rule.test && rule.test.toString().includes('.css') && Array.isArray(rule.use)) {
            rule.use.forEach(loader => {
                if (loader && typeof loader === 'object' && loader.options) {
                    loader.options.sourceMap = true;
                }
            });
        }
    });
}

module.exports = config;