const mix        = require('laravel-mix');
const glob       = require('glob');
const path       = require('path');
const fs         = require('fs-extra'); // For copying files
const jsDestDir  = 'build/js';
const cssDestDir = 'build/css';

// Define the source and destination directories
const jsSourceDirs = [
	'assets/js',
	'blocks/**'
];
const cssSourceDirs = [
	'assets/css',
	'blocks/**'
];

// Function to append `.min` before the file extension
const addMinSuffix = (filePath) => {
	const extname  = path.extname(filePath);
	const basename = path.basename(filePath, extname);
	return `${basename}.min${extname}`;
};

// Configure minification options
const minifyOptions = {
	terser: {
		extractComments: false,
		terserOptions: {
			compress: true,
			output: {
				comments: false
			}
		}
	},
	cssNano: {
		preset: ['default', {
			discardComments: { removeAll: true },
			minifyFontValues: true,
			minifySelectors: true
		}]
	}
};

// Process JavaScript files
jsSourceDirs.forEach(sourceDir => {
	glob.sync(`${sourceDir}/*.js`).forEach(file => {
		// Skip files that are already minified
		if (file.includes('.min.js')) return;

		// Create both original and minified versions
		const fileName = path.basename(file);
		const minFileName = addMinSuffix(file);
		const outputPath = path.join(jsDestDir, fileName);
		const minOutputPath = path.join(jsDestDir, minFileName);

		// Copy original file
		mix.after(() => {
			fs.ensureDirSync(jsDestDir);
			fs.copyFileSync(file, outputPath);
		});

		// Create minified version
		mix.js(file, minOutputPath).options({
			terser: minifyOptions.terser
		});
	});
});

// Process CSS files
cssSourceDirs.forEach(sourceDir => {
	glob.sync(`${sourceDir}/*.css`).forEach(file => {
		// Skip files that are already minified
		if (file.includes('.min.css')) return;

		// Create both original and minified versions
		const fileName = path.basename(file);
		const minFileName = addMinSuffix(file);
		const outputPath = path.join(cssDestDir, fileName);
		const minOutputPath = path.join(cssDestDir, minFileName);

		// Copy original file
		mix.after(() => {
			fs.ensureDirSync(cssDestDir);
			fs.copyFileSync(file, outputPath);
		});

		// Create minified version
		mix.postCss(file, minOutputPath, [
			require('cssnano')(minifyOptions.cssNano)
		]);
	});
});