window.googletag = window.googletag || { cmd: [] };

googletag.cmd.push( function() {
	var ads          = maiPubVars['ads'];
	var refreshKey   = 'refresh';
	var refreshvalue = 'true';

	// Loop through maiPubVars getting key and values.
	for ( var id in ads ) {
		// console.log( id, ads[id].sizes );

		// Define ad slot.
		var slot = googletag.defineSlot( '/22487526518/' + maiPubVars['gam_domain'] + '/' + id, ads[id].sizes, 'mai-ad-' + id )
			.setTargeting( refreshKey, refreshvalue )
			.addService( googletag.pubads() );

		// Define size mapping.
		// If these breakpoints change, make sure to update the breakpoints in the mai-publisher.css file.
		slot.defineSizeMapping(
			googletag.sizeMapping()
			.addSize( [ 1024, 768 ], ads[id].sizesDesktop )
			.addSize( [ 640, 480 ], ads[id].sizesTablet )
			.addSize( [ 0, 0 ], ads[id].sizesMobile )
			.build()
		);
	}

	// Set SafeFrame -- This setting will only take effect for subsequent ad requests made for the respective slots.
	// To enable cross domain rendering for all creatives, execute setForceSafeFrame before loading any ad slots.
	googletag.pubads().setForceSafeFrame( true );

	// Make ads centered.
	googletag.pubads().setCentering( true );

	// Refresh ads only when they are in view and after expiration of refreshSeconds.
	googletag.pubads().addEventListener( 'impressionViewable', function( event ) {
		var slot = event.slot;

		if ( slot.getTargeting( refreshKey ).indexOf( refreshvalue ) >= 0 ) {
			setTimeout( function() {
				googletag.pubads().refresh([slot]);
			}, 30 * 1000 ); // 30 seconds.
		}
	});

	// Enable SRA and services.
	googletag.pubads().enableSingleRequest();
	googletag.enableServices();
});