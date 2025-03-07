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

module.exports = {
    ...defaultConfig,
    entry: entries,
    devtool: 'source-map', // Explicitly enable source maps
};