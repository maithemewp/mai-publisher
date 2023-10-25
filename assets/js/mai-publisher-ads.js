window.googletag = window.googletag || {};
googletag.cmd    = googletag.cmd || [];

if ( window.googletag && googletag.apiReady ) {
	const ads           = maiPubAdsVars['ads'];
	const refreshKey    = 'refresh';
	const refreshvalue  = 'true';
	const refreshTime   = 30; // Time in seconds.

	googletag.cmd.push(() => {
		const gamBase = maiPubAdsVars['gamBase'];

		// Loop through maiPubAdsVars getting key and values.
		Object.keys( ads ).forEach( slug => {
			// Define ad slot.
			const slot = googletag.defineSlot( gamBase + slug, ads[slug].sizes, 'mai-ad-' + slug );

			// Set refresh targeting.
			slot.setTargeting( refreshKey, refreshvalue )

			// Set slot-level targeting.
			if ( ads[slug].targeting ) {
				Object.keys( ads[slug].targeting ).forEach( key => {
					slot.setTargeting( key, ads[slug].targeting[key] );
				});
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
		if ( maiPubAdsVars.targeting ) {
			Object.keys( maiPubAdsVars.targeting ).forEach( key => {
				googletag.pubads().setTargeting( key, maiPubAdsVars.targeting[key].toString() );
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
		googletag.pubads().setForceSafeFrame( true );

		// Make ads centered.
		googletag.pubads().setCentering( true );

		// Enable SRA and services.
		// googletag.pubads().disableInitialLoad(); // Disable initial load for header bidding.
		googletag.pubads().enableSingleRequest();
		googletag.enableServices();
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
		googletag.pubads().refresh( [slot] );
	});
}