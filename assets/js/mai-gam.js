// Begin Mai Ads GAM settings for sugarmakers.org.
window.googletag = window.googletag || {cmd: []};
googletag.cmd.push(function() {
	var REFRESH_KEY   = 'refresh';
	var REFRESH_VALUE = 'true';

	// Number of seconds to wait after the slot becomes viewable before we refresh the ad.
	var SECONDS_TO_WAIT_AFTER_VIEWABILITY = 30;

	// these mappsings are in alphabetical order; similiar to how they are listed in GAM Ad Units

	// Define footer mappings.
	var footerAll     = [ [970,90], [728,90], [468, 60], [320, 50] ];
	var footerDesktop = [ [970, 90], [728, 90] ];
	var footerTablet  = [ [468, 60], [320, 50] ];
	var footerMobile  = [ [320, 50] ];
	var footerSizeMap = googletag.sizeMapping()
	.addSize( [1024, 768], footerDesktop )
	.addSize( [640, 480], footerTablet )
	.addSize( [0, 0], footerMobile )
	.build();

	// Define fullscreen mappings.
	var fullscreenAll     = [ [1024, 768], [768, 1024], [480, 320], [468, 60], [320,50] ];
	var fullscreenDesktop = [ [1024, 768], [768, 1024] ];
	var fullscreenTablet  = [ [480, 320], [468, 60] ];
	var fullscreenMobile  = [ [320, 50] ];
	var fullscreenSizeMap = googletag.sizeMapping()
	.addSize( [1024, 768], fullscreenDesktop )
	.addSize( [640, 480], fullscreenTablet )
	.addSize( [0, 0], fullscreenMobile )
	.build();

	// Define header mappings.
	var headerAll     = [ [970,250], [970,90], [728,90], [468, 60], [320, 50] ];
	var headerDesktop = [ [970, 250], [970, 90], [728, 90] ];
	var headerTablet  = [ [468, 60], [320, 50] ];
	var headerMobile  = [ [320, 50] ];
	var headerSizeMap = googletag.sizeMapping()
		.addSize( [1024, 768], headerDesktop )
		.addSize( [640, 480], headerTablet )
		.addSize( [0, 0], headerMobile )
		.build();

	// Define incontent mappings.
	var incontentAll     = [ [970, 250], [970, 66], [750, 300], [750, 200], [750, 100], [336, 280], [300, 250], [300, 100] ];
	var incontentDesktop = [ [970, 250], [970, 66], [750, 300], [750, 200], [750, 100] ];
	var incontentTablet  = [ [336, 280], [300, 250], [300, 100] ];
	var incontentMobile  = [ [300, 250], [300, 100] ];
	var incontentSizeMap = googletag.sizeMapping()
		.addSize( [1024, 768], incontentDesktop )
		.addSize( [640, 480], incontentTablet )
		.addSize( [0, 0], incontentMobile )
		.build();

	// Define infeed mappings.
	//
	var infeedAll     = [ [300, 600], [300, 250], [240, 400] ];
	var infeedDesktop = [ [300, 600], [300, 250] ];
	var infeedTablet  = [ [300, 250], [240, 400] ];
	var infeedMobile  = [ [300, 250], [240, 400] ];
	var infeedSizeMap = googletag.sizeMapping()
		.addSize( [1024, 768], infeedDesktop )
		.addSize( [640, 480], infeedTablet )
		.addSize( [0, 0], infeedMobile )
		.build();

	// Define inrecipe mappings.
	//
	var inrecipeAll     = [ [400, 400], [300, 300], [250, 250], [200, 200] ];
	var inrecipeDesktop = [ [400, 400], [300, 300], [250, 250], [200, 200] ];
	var inrecipeTablet  = [ [250, 250], [200, 200] ];
	var inrecipeMobile  = [ [250, 250], [200, 200] ];
	var inrecipeSizeMap = googletag.sizeMapping()
		.addSize( [1024, 768], inrecipeDesktop )
		.addSize( [640, 480], inrecipeTablet )
		.addSize( [0, 0], inrecipeMobile )
		.build();

		// Define podcast-footer mappings.
	var podcast_footerAll     = [ [970,90], [728,90], [468, 60], [320, 50] ];
	var podcast_footerDesktop = [ [970, 90], [728, 90] ];
	var podcast_footerTablet  = [ [468, 60], [320, 50] ];
	var podcast_footerMobile  = [ [320, 50] ];
	var podcast_footerSizeMap = googletag.sizeMapping()
	.addSize( [1024, 768], podcast_footerDesktop )
	.addSize( [640, 480], podcast_footerTablet )
	.addSize( [0, 0], podcast_footerMobile )
	.build();

	// Define podcast-header mappings.
	var podcast_headerAll     = [ [970,250], [970,90], [728,90], [468, 60], [320, 50] ];
	var podcast_headerDesktop = [ [970, 250], [970, 90], [728, 90] ];
	var podcast_headerTablet  = [ [468, 60], [320, 50] ];
	var podcast_headerMobile  = [ [320, 50] ];
	var podcast_headerSizeMap = googletag.sizeMapping()
		.addSize( [1024, 768], podcast_headerDesktop )
		.addSize( [640, 480], podcast_headerTablet )
		.addSize( [0, 0], podcast_headerMobile )
		.build();

	// Define sponsored-sidebar mappings.
	var sponsored_sidebarAll     = [ [300, 250] ];
	var sponsored_sidebarDesktop = [ [300, 250] ];
	var sponsored_sidebarTablet  = [ [300, 520] ];
	var sponsored_sidebarMobile  = [ [300, 250] ];
	var sponsored_sidebarSizeMap = googletag.sizeMapping()
		.addSize( [1024, 768], sponsored_sidebarDesktop )
		.addSize( [640, 480], sponsored_sidebarTablet )
		.addSize( [0, 0], sponsored_sidebarMobile )
		.build();


	// Set SafeFrame -- This setting will only take effect for subsequent ad requests made for the respective slots.
	// To enable cross domain rendering for all creatives, execute setForceSafeFrame before loading any ad slots.
	googletag.pubads().setForceSafeFrame( true );

	// Make ads centered.
	googletag.pubads().setCentering( true );

	// Define Ad Slots.  These Ad Slots are defined using the Ad Unit Mappings above.
	// TODO: in the future, this will be created dynamically based upon the presence of a block on a page for the ad unit


	// console.log( maiAdsHelperVars.slot_ids );
	// console.log( maiAdsHelperVars.domain );

	if ( maiAdsHelperVars.slot_ids.includes( 'div-mai-ad-footer' ) ) {
		var footer = googletag.defineSlot( '/22487526518/' + maiAdsHelperVars.domain + '/footer', footerAll, 'div-mai-ad-footer' )
						.setTargeting( REFRESH_KEY, REFRESH_VALUE )
						.addService( googletag.pubads() );

		footer.defineSizeMapping( footerSizeMap );
	}

	if ( maiAdsHelperVars.slot_ids.includes( 'div-mai-ad-header' ) ) {
		var header = googletag.defineSlot( '/22487526518/' + maiAdsHelperVars.domain + '/header', headerAll, 'div-mai-ad-header' )
						.setTargeting( REFRESH_KEY, REFRESH_VALUE )
						.addService( googletag.pubads() );

		header.defineSizeMapping( headerSizeMap );
	}

	if ( maiAdsHelperVars.slot_ids.includes( 'div-mai-ad-fullscreen' ) ) {
		var fullscreen = googletag.defineSlot( '/22487526518/' + maiAdsHelperVars.domain + '/fullscreen', fullscreenAll, 'div-mai-ad-fullscreen' )
						.setTargeting( REFRESH_KEY, REFRESH_VALUE )
						.addService( googletag.pubads() );

		fullscreen.defineSizeMapping( fullscreenSizeMap );
	}

	// we will define five (5) incontent ads for now
	if ( maiAdsHelperVars.slot_ids.includes( 'div-mai-ad-incontent-1' ) ) {
		var incontent1 = googletag.defineSlot( '/22487526518/' + maiAdsHelperVars.domain + '/incontent', incontentAll, 	'div-mai-ad-incontent-1' )
						.setTargeting( REFRESH_KEY, REFRESH_VALUE )
						.addService( googletag.pubads() );

		incontent1.defineSizeMapping( incontentSizeMap );
	}
	if ( maiAdsHelperVars.slot_ids.includes( 'div-mai-ad-incontent-2' ) ) {
		var incontent2 = googletag.defineSlot( '/22487526518/' + maiAdsHelperVars.domain + '/incontent', incontentAll, 	'div-mai-ad-incontent-2' )
						.setTargeting( REFRESH_KEY, REFRESH_VALUE )
						.addService( googletag.pubads() );

		incontent2.defineSizeMapping( incontentSizeMap );
	}
	if ( maiAdsHelperVars.slot_ids.includes( 'div-mai-ad-incontent-3' ) ) {
		var incontent3 = googletag.defineSlot( '/22487526518/' + maiAdsHelperVars.domain + '/incontent', incontentAll, 	'div-mai-ad-incontent-3' )
						.setTargeting( REFRESH_KEY, REFRESH_VALUE )
						.addService( googletag.pubads() );

		incontent3.defineSizeMapping( incontentSizeMap );
	}
	if ( maiAdsHelperVars.slot_ids.includes( 'div-mai-ad-incontent-4' ) ) {
		var incontent4 = googletag.defineSlot( '/22487526518/' + maiAdsHelperVars.domain + '/incontent', incontentAll, 	'div-mai-ad-incontent-4' )
						.setTargeting( REFRESH_KEY, REFRESH_VALUE )
						.addService( googletag.pubads() );

		incontent4.defineSizeMapping( incontentSizeMap );
	}
	if ( maiAdsHelperVars.slot_ids.includes( 'div-mai-ad-incontent-5' ) ) {
		var incontent5 = googletag.defineSlot( '/22487526518/' + maiAdsHelperVars.domain + '/incontent', incontentAll, 	'div-mai-ad-incontent-5' )
						.setTargeting( REFRESH_KEY, REFRESH_VALUE )
						.addService( googletag.pubads() );

		incontent5.defineSizeMapping( incontentSizeMap );
	}

	// we will define three (3) infeed ads for now
	if ( maiAdsHelperVars.slot_ids.includes( 'div-mai-ad-infeed-1' ) ) {
		var infeed1 = googletag.defineSlot( '/22487526518/' + maiAdsHelperVars.domain + '/infeed', infeedAll, 'div-mai-ad-infeed-1' )
						.setTargeting( REFRESH_KEY, REFRESH_VALUE )
						.addService( googletag.pubads() );

		infeed1.defineSizeMapping( infeedSizeMap );
	}
	if ( maiAdsHelperVars.slot_ids.includes( 'div-mai-ad-infeed-2' ) ) {
		var infeed2 = googletag.defineSlot( '/22487526518/' + maiAdsHelperVars.domain + '/infeed', infeedAll, 'div-mai-ad-infeed-2' )
						.setTargeting( REFRESH_KEY, REFRESH_VALUE )
						.addService( googletag.pubads() );

		infeed2.defineSizeMapping( infeedSizeMap );
	}
	if ( maiAdsHelperVars.slot_ids.includes( 'div-mai-ad-infeed-3' ) ) {
		var infeed3 = googletag.defineSlot( '/22487526518/' + maiAdsHelperVars.domain + '/infeed', infeedAll, 'div-mai-ad-infeed-3' )
						.setTargeting( REFRESH_KEY, REFRESH_VALUE )
						.addService( googletag.pubads() );

		infeed3.defineSizeMapping( infeedSizeMap );
	}

	// define inrecipe ad unit
	if ( maiAdsHelperVars.slot_ids.includes( 'div-mai-ad-inrecipe' ) ) {
		var inrecipe = googletag.defineSlot( '/22487526518/' + maiAdsHelperVars.domain + '/inrecipe', inrecipeAll, 'div-mai-ad-inrecipe' )
						.setTargeting( REFRESH_KEY, REFRESH_VALUE )
						.addService( googletag.pubads() );

		inrecipe.defineSizeMapping( inrecipeSizeMap );
	}

	// define special slot for sidebar house ads
	if ( maiAdsHelperVars.slot_ids.includes( 'div-mai-ad-sponsored-sidebar' ) ) {
		var sponsored_sidebar = googletag.defineSlot( '/22487526518/' + maiAdsHelperVars.domain + '/sponsored-sidebar', sponsored_sidebarAll, 'div-mai-ad-sponsored-sidebar' )
						.addService( googletag.pubads() );

		sponsored_sidebar.defineSizeMapping( sponsored_sidebarSizeMap );
	}

	// define special ad units for Podcasts
	if ( maiAdsHelperVars.slot_ids.includes( 'div-mai-ad-podcast-header' ) ) {
		var podcast_header = googletag.defineSlot( '/22487526518/' + maiAdsHelperVars.domain + '/podcast-header', podcast_headerAll, 'div-mai-ad-podcast-header' )
						.addService( googletag.pubads() );

		podcast_header.defineSizeMapping( podcast_headerSizeMap );
	}
	if ( maiAdsHelperVars.slot_ids.includes( 'div-mai-ad-podcast-footer' ) ) {
		var podcast_footer = googletag.defineSlot( '/22487526518/' + maiAdsHelperVars.domain + '/podcast-footer', podcast_footerAll, 'div-mai-ad-podcast-footer' )
						.setTargeting( REFRESH_KEY, REFRESH_VALUE )
						.addService( googletag.pubads() );

		podcast_footer.defineSizeMapping( podcast_footerSizeMap );
	}

	// refresh ads only when they are in view and after expiration of SECONDS_TO_WAIT_AFTER_VIEWABILITY
	googletag.pubads().addEventListener('impressionViewable', function(event) {
		var slot = event.slot;
		if (slot.getTargeting(REFRESH_KEY).indexOf(REFRESH_VALUE) > -1) {
			setTimeout(function() {
				googletag.pubads().refresh([slot]);
			}, SECONDS_TO_WAIT_AFTER_VIEWABILITY * 1000);
		}
	});

	// Enable SRA and services.
	googletag.pubads().enableSingleRequest();
	googletag.enableServices();
});
// End Mai Ads GAM Settings.