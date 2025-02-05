# Mai Publisher
Manage ads and more for websites in the Mai Publisher network.

## How to use
1. Visit Dashboard > Mai Ads > Settings and configure the settings.
1. Visit Dashboard > Mai Ads and configure and default ads or add your own.

### How to remove ad sizes from ad units
```
/**
 * Remove ad sizes from specific ad units.
 *
 * @param array $config The publisher config.
 *
 * @return array
 */
add_filter( 'mai_publisher_config', function( $config ) {
	// Sizes to remove, organized by ad unit key.
	$to_remove = [
		'leaderboard-wide' => [
			[300, 100],
			[466, 60],
			[750, 100],
			[970, 90],
		],
		// Add more ad unit keys and their sizes to remove as needed.
		// 'rectangle-medium' => [
		//     [300, 250],
		//     [300, 300],
		// ],
	];

	// Loop through each ad unit type we want to modify
	foreach ( $to_remove as $ad_unit => $sizes_to_remove ) {
		// Skip if this ad unit doesn't exist in config.
		if ( ! isset( $config['ad_units'][ $ad_unit ] ) ) {
			continue;
		}

		// Loop through the ad units.
		foreach ( $config['ad_units'][ $ad_unit ] as $key => $ad ) {
			// Filter out any sizes that exist in $sizes_to_remove.
			$config['ad_units'][ $ad_unit ][ $key ] = array_filter( $ad, function( $size ) use ( $sizes_to_remove ) {
				return ! in_array( $size, $sizes_to_remove );
			});

			// Reindex the array after removals.
			$config['ad_units'][ $ad_unit ][ $key ] = array_values( $config['ad_units'][ $ad_unit ][ $key ] );
		}
	}

	return $config;
});
```

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