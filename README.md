# Mai Publisher
Manage ads and more for websites in the Mai Publisher network.

## How to use
1. Visit Dashboard > Mai Ads > Settings and configure the settings.
1. Visit Dashboard > Mai Ads and configure and default ads or add your own.

## How to ad client GAM ads
1. Make sure the client network code is set in the settings.
1. Run the following filter to add existing ads from GAM.

```
/**
 * Add a new client to the publisher config.
 * This will populate the options for the Mai Client GAM Ad Unit block.
 *
 * @param array $config The existing config.
 *
 * @return array
 */
add_filter( 'mai_publisher_config', function( $config ) {
	$config['client'] = [
		'label'    => 'Website or Company Name', // Use for the block label in the editor.
		'ad_units' => [
			'test' => [
				'sizes'         => [ [300, 50], [300, 100], [300, 250], [320, 50], [336, 280], [580, 400], [728, 90], [750, 100], [750, 200], [750, 300] ],
				'sizes_desktop' => [ [580, 400], [728, 90], [750, 100], [750, 200], [750, 300] ],
				'sizes_tablet'  => [ [300, 50], [300, 100], [300, 250], [320, 50], [336, 280], [580, 400], [728, 90] ],
				'sizes_mobile'  => [ [300, 50], [300, 100], [300, 250], [320, 50], [336, 280], [580, 400] ],
			],
			'withprefix/Test/' => [
				'sizes'         => [ [300, 50], [300, 100], [300, 250], [320, 50], [336, 280], [580, 400], [728, 90], [750, 100], [750, 200], [750, 300],  [970, 90], [970, 250] ],
				'sizes_desktop' => [ [580, 400], [728, 90], [750, 100], [750, 200], [750, 300], [970, 90], [970, 250] ],
				'sizes_tablet'  => [ [300, 50], [300, 100], [300, 250], [320, 50], [336, 280], [580, 400] ],
				'sizes_mobile'  => [ [300, 50], [300, 100], [300, 250], [320, 50], [336, 280] ],
			],
			'nestedwithprefix/Test/Nested.withPeriod' => [
				'sizes'         => [ [240, 400], [250, 250], [300, 250], [300, 600], [320, 480], [336, 280], [480, 320], [580, 400], [768, 1024], [1024, 768] ],
				'sizes_desktop' => [ [480, 320], [580, 400], [768, 1024], [1024, 768] ],
				'sizes_tablet'  => [ [320, 480], [468, 60], [480, 320], [580, 400] ],
				'sizes_mobile'  => [ [240, 400], [250, 250], [300, 250], [300, 600], [320, 480], [336, 280] ],
			],
		],
	];

	return $config;
});
```

## Developers
This document outlines the steps to set up the development environment, run tasks, and manage the plugin.

### Prerequisites

1. **Node.js**: Ensure that you have Node.js installed. You can download and install it from [nodejs.org](https://nodejs.org/).

2. **npm**: npm (Node Package Manager) is included with Node.js. Verify its installation with:
```
bash
npm -v
```

### Install Dependencies
```
npm install
```

### Updating Node.js
If you need to update Node.js to the latest version, you can use nvm (Node Version Manager).

1. Install nvm: Follow the installation instructions on (nvm’s GitHub page)[https://github.com/nvm-sh/nvm].
2. Update Node.js: Use nvm to install the latest version:
```
nvm install node
nvm use node
```

### Running Development Tasks
- Development Build: To compile and minify assets for development:
```
npm run dev
```

- Production Build: To compile and minify assets for production:
```
npm run production
```

- Watch for Changes: To continuously watch and rebuild assets on file changes:
```
npm run watch
```

### Directory Structure
- assets/js: JavaScript source files.
- assets/css: CSS source files.
- blocks: Block-specific source files for JavaScript and CSS.
- build/js: Full (non-minified) and minified JavaScript files.
- build/css: Full (non-minified) and minified CSS files.