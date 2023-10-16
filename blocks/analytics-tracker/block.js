function addMaiAnalyticsTrackerTransform( settings, name ) {
	if ( name !== 'acf/mai-analytics-tracker' ) {
		return settings;
	}

	settings.transforms = {
		from: [
			{
				type: 'block',
				isMultiBlock: true,
				blocks: [ '*' ],
				__experimentalConvert( blocks ) {
					// Clone the Blocks to be inside the new container.
					// Failing to create new block references causes the original blocks
					// to be replaced in the switchToBlockType call thereby meaning they
					// are removed both from their original location and within the
					// new group block.
					const groupInnerBlocks = blocks.map( ( block ) => {
						return wp.blocks.createBlock(
							block.name,
							block.attributes,
							block.innerBlocks
						);
					} );

					return wp.blocks.createBlock(
						'acf/mai-analytics-tracker',
						{},
						groupInnerBlocks
					);
				},
			},
		],
		to: [
			{
				type: 'block',
				name: 'Unwrap Mai Analytics Tracker block',
				blocks: [ '*' ],
				transform: ( attributes, innerBlocks ) => innerBlocks,
			},
		],
	};

	return settings;
}

wp.hooks.addFilter(
	'blocks.registerBlockType',
	'mai-analytics/mai-analytics-tracker',
	addMaiAnalyticsTrackerTransform
);
