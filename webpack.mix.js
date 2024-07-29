let mix = require('laravel-mix');
let fs = require('fs');
let path = require('path');

// Process and minify all CSS files in assets/css
fs.readdirSync('assets/css').forEach(file => {
	if (path.extname(file) === '.css') {
		// Copy original file
		mix.copy(`assets/css/${file}`, `assets/css/${file}`);
		// Minify and save as .min.css
		mix.styles(`assets/css/${file}`, `assets/css/${file.replace('.css', '.min.css')}`)
		.minify(`assets/css/${file.replace('.css', '.min.css')}`);
	}
});

// Process and minify all JS files in assets/js
fs.readdirSync('assets/js').forEach(file => {
	if (path.extname(file) === '.js') {
		// Copy original file
		mix.copy(`assets/js/${file}`, `assets/js/${file}`);
		// Minify and save as .min.js
		mix.js(`assets/js/${file}`, `assets/js/${file.replace('.js', '.min.js')}`)
		.minify(`assets/js/${file.replace('.js', '.min.js')}`);
	}
});

// Set public path
mix.setPublicPath('assets');