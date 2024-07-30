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

// Create directories if they don't exist
fs.ensureDirSync(jsDestDir);
fs.ensureDirSync(cssDestDir);

// Minify and duplicate JavaScript files
jsSourceDirs.forEach(sourceDir => {
	glob.sync(`${sourceDir}/*.js`).forEach(file => {
		const minFileName     = addMinSuffix(file);
		const minOutputPath   = path.join(jsDestDir, minFileName);
		const fullOutputPath  = path.join(jsDestDir, path.basename(file));

		mix.js(file, minOutputPath);   // Process minified version
		fs.copyFileSync(file, fullOutputPath);  // Copy full version
	});
});

// Minify and duplicate CSS files
cssSourceDirs.forEach(sourceDir => {
	glob.sync(`${sourceDir}/*.css`).forEach(file => {
		const minFileName     = addMinSuffix(file);
		const minOutputPath   = path.join(cssDestDir, minFileName);
		const fullOutputPath  = path.join(cssDestDir, path.basename(file));

		mix.postCss(file, minOutputPath);   // Process minified version
		fs.copyFileSync(file, fullOutputPath);  // Copy full version
	});
});