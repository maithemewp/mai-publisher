let mix = require('laravel-mix');
let fs = require('fs');
let path = require('path');

// Ensure /min directories exist
if (!fs.existsSync('assets/js/min')) {
	fs.mkdirSync('assets/js/min', { recursive: true });
}
if (!fs.existsSync('assets/css/min')) {
	fs.mkdirSync('assets/css/min', { recursive: true });
}

// Process and minify all CSS files in assets/css
fs.readdirSync('assets/css').forEach(file => {
	if (path.extname(file) === '.css') {
		const outputFileName = `assets/css/min/${file}`;
		mix.styles(`assets/css/${file}`, outputFileName)
		.minify(outputFileName);
	}
});

// Process and minify all JS files in assets/js
fs.readdirSync('assets/js').forEach(file => {
	if (path.extname(file) === '.js') {
		const outputFileName = `assets/js/min/${file}`;
		mix.js(`assets/js/${file}`, outputFileName)
		.minify(outputFileName);
	}
});

// Set public path (if needed)
mix.setPublicPath('assets');