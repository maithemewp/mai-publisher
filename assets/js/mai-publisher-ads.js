window.googletag = window.googletag || {};
googletag.cmd    = googletag.cmd || [];

/**
 * Main function to set up ads.
 */
function setupAds() {
	const ads           = maiPubAdsVars['ads'];
	const refreshKey    = 'refresh';
	const refreshvalue  = 'true';
	const refreshTime   = 30; // Time in seconds.

	googletag.cmd.push(() => {
		const gamBase  = maiPubAdsVars['gamBase'];
		const uadSlots = [];

		// Loop through maiPubAdsVars getting key and values. The `slug` key is the incremented id like "incontent-2", etc.
		Object.keys( ads ).forEach( slug => {
			// Define ad slot.
			// googletag.defineSlot( "/1234567/sports", [728, 90], "div-1" );
			const slot = googletag.defineSlot( gamBase + ads[slug]['id'], ads[slug].sizes, 'mai-ad-' + slug );

			// Set refresh targeting.
			slot.setTargeting( refreshKey, refreshvalue );

			// Set slot-level targeting.
			if ( ads[slug].targets ) {
				Object.keys( ads[slug].targets ).forEach( key => {
					slot.setTargeting( key, ads[slug].targets[key] );
				});
			}

			// Set split testing.
			if ( ads[slug].splitTest && 'rand' === ads[slug].splitTest ) {
				// Set 'st' to a value between 0-99.
				slot.setTargeting( 'st', Math.floor(Math.random() * 100) );
			}

			// Get it running.
			slot.addService( googletag.pubads() );

			/**
			 * Define size mapping.
			 * If these breakpoints change, make sure to update the breakpoints in the mai-publisher.css file.
			 */
			slot.defineSizeMapping(
				googletag.sizeMapping()
				.addSize( [ 1024, 768 ], ads[slug].sizesDesktop )
				.addSize( [ 728, 480 ], ads[slug].sizesTablet )
				.addSize( [ 0, 0 ], ads[slug].sizesMobile )
				.build()
			);
		});

		// Set page-level targeting.
		if ( maiPubAdsVars.targets ) {
			Object.keys( maiPubAdsVars.targets ).forEach( key => {
				googletag.pubads().setTargeting( key, maiPubAdsVars.targets[key].toString() );
			});
		}

		/**
		 * Lazy loading.
		 * @link https://developers.google.com/publisher-tag/reference?utm_source=lighthouse&utm_medium=lr#googletag.PubAdsService_enableLazyLoad
		 */
		googletag.pubads().enableLazyLoad({
			// Fetch slots within 2 viewports.
			fetchMarginPercent: 200,
			// Render slots within 1.5 viewports.
			renderMarginPercent: 150,
			// Double the above values on mobile.
			// mobileScaling: 2.0,
		});

		/**
		 * Set SafeFrame -- This setting will only take effect for subsequent ad requests made for the respective slots.
		 * To enable cross domain rendering for all creatives, execute setForceSafeFrame before loading any ad slots.
		 */
		// Currently disabled for Amazon UAM.
		// googletag.pubads().setForceSafeFrame( true );

		// Make ads centered.
		googletag.pubads().setCentering( true );

		// Enable SRA and services.
		googletag.pubads().disableInitialLoad(); // Disable initial load for header bidding.
		googletag.pubads().enableSingleRequest();
		googletag.enableServices();

		// Handle Amazon UAM bids.
		if ( maiPubAdsVars.amazonUAM ) {
			/**
			 * Amazon UAD.
			 * Debug via `apstag.debug('enableConsole')`
			 */
			!function(a9,a,p,s,t,A,g){if(a[a9])return;function q(c,r){a[a9]._Q.push([c,r])}a[a9]={init:function(){q("i",arguments)},fetchBids:function(){q("f",arguments)},setDisplayBids:function(){},targetingKeys:function(){return[]},_Q:[]};A=p.createElement(s);A.async=!0;A.src=t;g=p.getElementsByTagName(s)[0];g.parentNode.insertBefore(A,g)}("apstag",window,document,"script","//c.amazon-adsystem.com/aax2/apstag.js");

			// Initialize apstag and have apstag set bids on the googletag slots when they are returned to the page.
			apstag.init({
				pubID: '79166f25-5776-4c3e-9537-abad9a584b43', // BB.
				adServer: 'googletag',
				// bidTimeout: prebidTimeout,
				// us_privacy: '-1', // https://ams.amazon.com/webpublisher/uam/docs/web-integration-documentation/integration-guide/uam-ccpa.html?source=menu
				// @link https://ams.amazon.com/webpublisher/uam/docs/reference/api-reference.html#configschain
				schain: {
					complete: 1, // Integer 1 or 0 indicating if all preceding nodes are complete.
					ver: '1.0', // Version of the spec used.
					nodes: [
						{
							asi: 'bizbudding.com', // Populate with the canonical domain of the advertising system where the seller.JSON file is hosted.
							sid: maiPubAdsVars['sellersId'], // The identifier associated with the seller or reseller account within your advertising system.
							hp: 1, // 1 or 0, whether this node is involved in the payment flow.
							name: maiPubAdsVars['sellersName'], // Name of the company paid for inventory under seller ID (optional).
							domain: maiPubAdsVars['domain'], // Business domain of this node (optional).
						}
					]
				}
			});

			// Loop through maiPubAdsVars getting key and values.
			Object.keys( ads ).forEach( slug => {
				// Add slot to array for UAD.
				uadSlots.push({
					slotID: 'mai-ad-' + slug,
					slotName: gamBase + ads[slug]['id'],
					sizes: ads[slug].sizes,
				});
			});

			// Fetch bids from Amazon UAM using apstag.
			apstag.fetchBids({
				slots: uadSlots,
				timeout: 2e3,
				params: {
					adRefresh: '1',
				}
			}, function( bids ) {
				// Set apstag bids, then trigger the first request to GAM.
				googletag.cmd.push(function() {
					apstag.setDisplayBids();
					googletag.pubads().refresh();
				});
			});
		}
		// Standard GAM.
		else {
			googletag.pubads().refresh();
		}
	});

	// Set currently visible ads and timeout ids objects.
	const loadTimes        = {};
	const currentlyVisible = {};
	const timeoutIds       = {};

	/**
	 * Set 30 refresh when an ad is in view.
	 */
	googletag.pubads().addEventListener( 'impressionViewable', function( event ) {
		const slot   = event.slot;
		const slotId = slot.getSlotElementId();

		// Bail if not refreshing.
		if ( slot.getTargeting( refreshKey ).indexOf( refreshvalue ) < 0 ) {
			return;
		}

		// Set first load to current time.
		loadTimes[slotId] = Date.now();

		// Set timeout to refresh ads for current visible ads.
		timeoutIds[slotId] = setTimeout(() => {
			// console.log( slotId + ' is refreshing (impressionViewable).' );
			if ( maiPubAdsVars.amazonUAM ) {
				apstag.setDisplayBids();
			}
			googletag.pubads().refresh( [slot] );
		}, refreshTime * 1000 ); // Time in milliseconds.
	});

	/**
	 * Refreshes ads when scrolled back into view.
	 * Only refreshes if it's been n seconds since the ad was initially shown.
	 */
	googletag.pubads().addEventListener( 'slotVisibilityChanged', (event) => {
		const slot   = event.slot;
		const slotId = slot.getSlotElementId();
		const inView = event.inViewPercentage > 20;

		// Bail if not refreshing.
		if ( slot.getTargeting( refreshKey ).indexOf( refreshvalue ) < 0 ) {
			return;
		}

		// If in view and not currently visible, set to visible.
		if ( inView && ! currentlyVisible[slotId] ) {
			currentlyVisible[slotId] = true;
		}
		// If not in view and currently visible, set to not visible.
		else if ( ! inView && currentlyVisible[slotId] ) {
			currentlyVisible[slotId] = false;
		}
		// Not a change we care about.
		else {
			return;
		}

		// If scrolled out of view, clear timeout then bail.
		if ( ! currentlyVisible[slotId] ) {
			clearTimeout( timeoutIds[slotId] );
			return;
		}

		// Bail if loadTimes is undefined, or it hasn't been n seconds (in milliseconds).
		if ( 'undefined' === typeof loadTimes[slotId] || ( loadTimes[slotId] && Date.now() - loadTimes[slotId] < refreshTime * 1000 ) ) {
			return;
		}

		// console.log( slotId + ' is refreshing (slotVisibilityChanged).' );
		if ( maiPubAdsVars.amazonUAM ) {
			apstag.setDisplayBids();
		}

		googletag.pubads().refresh( [slot] );
	});
}

/**
 * When googletag and the API are ready, set up ads.
 */
if ( window.googletag && googletag.apiReady ) {
	setupAds();
} else {
	// If not ready, use the cmd array to queue up the setupAds function.
	googletag.cmd.push(setupAds);
}