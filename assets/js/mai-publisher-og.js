window.googletag = window.googletag || {};
googletag.cmd    = googletag.cmd || [];

if ( window.googletag && googletag.apiReady ) {
	googletag.cmd.push(() => {
		const ads          = maiPubVars['ads'];
		const refreshKey   = 'refresh';
		const refreshvalue = 'true';

		// Loop through maiPubVars getting key and values.
		for ( const id in ads ) {
			// Define ad slot.
			const slot = googletag
				.defineSlot( '/22487526518/' + maiPubVars['gam_domain'] + '/' + id, ads[id].sizes, 'mai-ad-' + id )
				.addService( googletag.pubads() )
				.setTargeting( refreshKey, refreshvalue );

			/**
			 * Define size mapping.
			 * If these breakpoints change, make sure to update the breakpoints in the mai-publisher.css file.
			 */
			slot.defineSizeMapping(
				googletag.sizeMapping()
				.addSize( [ 1024, 768 ], ads[id].sizesDesktop )
				.addSize( [ 640, 480 ], ads[id].sizesTablet )
				.addSize( [ 0, 0 ], ads[id].sizesMobile )
				.build()
			);
		}

		// TODO: Configure page-level targeting.
		// googletag.pubads().setTargeting( 'interests', 'basketball' );

		/**
		 * Lazy loading.
		 * @link https://developers.google.com/publisher-tag/reference?utm_source=lighthouse&utm_medium=lr#googletag.PubAdsService_enableLazyLoad
		 */
		// googletag.pubads().enableLazyLoad({
		// 	// Fetch slots within 5 viewports.
		// 	fetchMarginPercent: 200,
		// 	// Render slots within 2 viewports.
		// 	renderMarginPercent: 150,
		// 	// Double the above values on mobile.
		// 	// mobileScaling: 2.0,
		// });

		/**
		 * Set SafeFrame -- This setting will only take effect for subsequent ad requests made for the respective slots.
		 * To enable cross domain rendering for all creatives, execute setForceSafeFrame before loading any ad slots.
		 */
		googletag.pubads().setForceSafeFrame( true );

		// Make ads centered.
		googletag.pubads().setCentering( true );

		// Enable SRA and services.
		googletag.pubads().disableInitialLoad(); // Disable initial load for header bidding.
		googletag.pubads().enableSingleRequest();
		googletag.enableServices();

		/**
		 * Amazon UAD.
		 * Debug via `apstag.debug('enableConsole')`
		 */
		!function(a9,a,p,s,t,A,g){if(a[a9])return;function q(c,r){a[a9]._Q.push([c,r])}a[a9]={init:function(){q("i",arguments)},fetchBids:function(){q("f",arguments)},setDisplayBids:function(){},targetingKeys:function(){return[]},_Q:[]};A=p.createElement(s);A.async=!0;A.src=t;g=p.getElementsByTagName(s)[0];g.parentNode.insertBefore(A,g)}("apstag",window,document,"script","//c.amazon-adsystem.com/aax2/apstag.js");

		// Initialize apstag and have apstag set bids on the googletag slots when they are returned to the page.
		apstag.init({
			pubID: '79166f25-5776-4c3e-9537-abad9a584b43', // BB.
			adServer: 'googletag',
			bidTimeout: 2000,
		});

		const uadSlots = [];

		for ( const id in ads ) {
			uadSlots.push({
				slotID: id,
				slotName: '/22487526518/' + maiPubVars['gam_domain'] + '/' + id,
				sizes: ads[id].sizes,
			});
		}

		// Request the bids for the four googletag slots.
		apstag.fetchBids({
			slots: uadSlots,
			// timeout: 2000,
		}, function( bids ) {
			// Set apstag bids, then trigger the first request to GAM.
			googletag.cmd.push(function() {
				apstag.setDisplayBids();
				googletag.pubads().refresh();
			});
		});

		// Refresh ads only when they are in view and after expiration of refreshSeconds.
		// googletag.pubads().addEventListener( 'impressionViewable', function( event ) {
		// 	const slot = event.slot;

		// 	if ( slot.getTargeting( refreshKey ).indexOf( refreshvalue ) >= 0 ) {
		// 		setTimeout( function() {
		// 			googletag.pubads().refresh([slot]);
		// 		}, 30 * 1000 ); // 30 seconds.
		// 	}
		// });
	});
}