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