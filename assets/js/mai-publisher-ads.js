window.googletag = window.googletag || {};
googletag.cmd    = googletag.cmd || [];

const ads           = maiPubAdsVars['ads'];
const adSlotIds     = [];
const adSlots       = [];
const gamBase       = maiPubAdsVars.gamBase;
const gamBaseClient = maiPubAdsVars.gamBaseClient;
const refreshKey    = 'refresh';
const refreshValue  = maiPubAdsVars.targets.refresh;
const refreshTime   = 30; // Time in seconds.
const debug         = window.location.search.includes('dfpdeb') || window.location.search.includes('maideb');
const log           = debug;

// If debugging, log.
if ( log ) { console.log( 'v154' ); }

// Add to googletag items.
googletag.cmd.push(() => {
	/**
	 * Set SafeFrame -- This setting will only take effect for subsequent ad requests made for the respective slots.
	 * To enable cross domain rendering for all creatives, execute setForceSafeFrame before loading any ad slots.
	 */
	// Disabled for now: https://developers.google.com/publisher-tag/reference#googletag.PubAdsService_setForceSafeFrame
	// googletag.pubads().setForceSafeFrame( true );

	// Set page-level targeting.
	if ( maiPubAdsVars.targets ) {
		Object.keys( maiPubAdsVars.targets ).forEach( key => {
			googletag.pubads().setTargeting( key, maiPubAdsVars.targets[key].toString() );
		});
	}

	// Make ads centered.
	googletag.pubads().setCentering( true );

	// Enable SRA and services.
	googletag.pubads().disableInitialLoad(); // Disable initial load for header bidding.
	googletag.pubads().enableSingleRequest();

	// // Enable services.
	// googletag.enableServices();

	// If using Amazon UAM bids, add it.
	if ( maiPubAdsVars.amazonUAM ) {
		/**
		 * Amazon UAD.
		 * Debug via `apstag.debug('enableConsole')`.
		 * Disable debugging via `apstag.debug('disableConsole')`.
		 */
		!function(a9,a,p,s,t,A,g){if(a[a9])return;function q(c,r){a[a9]._Q.push([c,r])}a[a9]={init:function(){q("i",arguments)},fetchBids:function(){q("f",arguments)},setDisplayBids:function(){},targetingKeys:function(){return[]},_Q:[]};A=p.createElement(s);A.async=!0;A.src=t;g=p.getElementsByTagName(s)[0];g.parentNode.insertBefore(A,g)}("apstag",window,document,"script","//c.amazon-adsystem.com/aax2/apstag.js");
	}

	// If no delay, run on DOMContentLoaded.
	if ( ! maiPubAdsVars.loadDelay ) {
		// Check if DOMContentLoaded has run.
		if ( 'loading' === document.readyState ) {
			// If it's still loading, wait for the event.
			document.addEventListener( 'DOMContentLoaded', maiPubDOMContentLoaded );
		} else {
			// If it's already loaded, execute maiPubDOMContentLoaded().
			maiPubDOMContentLoaded();
		}
	}
	// Delayed on window load.
	else {
		// On window load.
		window.addEventListener( 'load', function() {
			setTimeout( maiPubDOMContentLoaded, maiPubAdsVars.loadDelay );
		});
	}

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

		// Bail if no slot.
		if ( ! slot ) {
			return;
		}

		// Bail if not a mai ad defined here.
		if ( ! adSlotIds.includes( slot.getAdUnitPath() ) ) {
			return;
		}

		// Bail if not refreshing.
		if ( ! Boolean( slot.getTargeting( refreshKey ).shift() ) ) {
			return;
		}

		// Set first load to current time.
		loadTimes[slotId] = Date.now();

		// Set timeout to refresh ads for current visible ads.
		timeoutIds[slotId] = setTimeout(() => {
			// If debugging, log.
			if ( log ) { console.log( 'refreshed via impressionViewable:', slotId ); }

			// Refresh the slot(s).
			maiPubRefreshSlots( [slot] );

		}, refreshTime * 1000 ); // Time in milliseconds.
	});

	/**
	 * Refreshes ads when scrolled back into view.
	 * Only refreshes if it's been n seconds since the ad was initially shown.
	 */
	googletag.pubads().addEventListener( 'slotVisibilityChanged', (event) => {
		const slot   = event.slot;
		const slotId = slot.getSlotElementId();
		const inView = event.inViewPercentage > 5;

		// Bail if no slot.
		if ( ! slot ) {
			return;
		}

		// Bail if not a mai ad defined here.
		if ( ! adSlotIds.includes( slot.getAdUnitPath() ) ) {
			return;
		}

		// Bail if not refreshing.
		if ( ! Boolean( slot.getTargeting( refreshKey ).shift() ) ) {
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

		// If debugging, log.
		if ( log ) { console.log( 'refreshed via slotVisibilityChanged:', slotId ); }

		// Refresh the slot(s).
		maiPubRefreshSlots( [slot] );
	});

	// If debugging, set listeners to log.
	if ( log ) {
		// Log when a slot ID is fetched.
		googletag.pubads().addEventListener( 'slotRequested', (event) => {
			console.log( 'fetched:', event.slot.getSlotElementId() );
		});

		// Log when a slot response is received.
		googletag.pubads().addEventListener( 'slotResponseReceived', (event) => {
			console.log( 'received:', event.slot.getSlotElementId(), event.slot.getResponseInformation(), event );
		});

		// Log when a slot ID is rendered.
		googletag.pubads().addEventListener( 'slotOnload', (event) => {
			console.log( 'rendered:', event.slot.getSlotElementId() );
		});

		// Log when a slot ID visibility changed.
		// googletag.pubads().addEventListener( 'slotVisibilityChanged', (event) => {
		// 	if ( log ) { console.log( 'changed:', event.slot.getSlotElementId(), `${event.inViewPercentage}%` ); }
		// });
	}
}); // End `googletag.cmd.push`.

/**
 * DOMContentLoaded and IntersectionObserver handler.
 */
function maiPubDOMContentLoaded() {
	// Separate ATF and BTF slots from passed ads.
	const { adSlotsATF, adSlotsBTF } = Object.entries(ads).reduce( ( acc, [ key, value ] ) => {
		// If above the fold or bottom sticky.
		if ( 'atf' === value.targets.ap || 'bs' === value.targets.ap ) {
			acc.adSlotsATF[ key ] = value;
		} else {
			acc.adSlotsBTF[ key ] = value;
		}

		return acc;

	}, { adSlotsATF: {}, adSlotsBTF: {} });

	// Define and display ATF ads.
	Object.keys( adSlotsATF ).forEach( slug => {
		maiPubDisplaySlots( [ maiPubDefineSlot( slug ) ] );
	});

	// Create the IntersectionObserver.
	const observer = new IntersectionObserver( (entries, observer) => {
		let toLoad = [];

		// Loop through the entries.
		entries.forEach( entry => {
			// Skip if not intersecting.
			if ( ! entry.isIntersecting ) {
				return;
			}

			// Get slot from adSlotsBTF.
			const slug    = entry.target.getAttribute('id').replace( 'mai-ad-', '' );
			const slotBTF = adSlotsBTF[slug];

			// If not in adSlotsBTF.
			if ( undefined === slotBTF ) {
				// Unobserve.
				observer.unobserve( entry.target );
				// Skip.
				return;
			}

			// If debugging, add inline styling.
			if ( debug ) {
				entry.target.style.outline   = '2px dashed red';
				entry.target.style.minWidth  = '300px';
				entry.target.style.minHeight = '120px';
			}

			// Add to toLoad array.
			toLoad.push( slug );

			// Unobserve. GAM event listener will handle refreshes.
			observer.unobserve( entry.target );
		}); // End entries loop.

		// Bail if no slots to load.
		if ( ! toLoad.length ) {
			return;
		}

		// Define and display all slots in view.
		maiPubDisplaySlots( toLoad.map( slug => maiPubDefineSlot( slug ) ) );

		// Clear toLoad array.
		toLoad = [];
	}, {
		root: null, // Use the viewport as the root.
		rootMargin: '600px 0px 600px 0px', // Trigger when the top of the element is X away from each part of the viewport.
		threshold: 0 // No threshold needed.
	});

	// Select all non-atf and non-bs ad units.
	const adUnits = document.querySelectorAll( '.mai-ad-unit:not([data-ap="atf"]):not([data-ap="bs"])' );

	// Observe each element.
	adUnits.forEach( adUnit => {
		observer.observe( adUnit );
	});
}

/**
 * Define a slot.
 *
 * @param {string} slug The ad slug.
 */
function maiPubDefineSlot( slug ) {
	let toReturn = null;

	// Get base from context.
	const base = 'client' === ads[slug]['context'] ? gamBaseClient : gamBase;

	// Define slot ID.
	const slotId = base + ads[slug]['id'];

	// Define ad slot. googletag.defineSlot( "/1234567/sports", [728, 90], "div-1" );
	const slot = googletag.defineSlot( slotId, ads[slug].sizes, 'mai-ad-' + slug );

	// If debugging, log.
	if ( log ) { console.log( 'defineSlot(): ', slug ); }

	// Register the ad slot.
	// An ad will not be fetched until refresh is called,
	// due to the disableInitialLoad() method being called earlier.
	googletag.display( 'mai-ad-' + slug );

	// If debugging, log.
	if ( log ) { console.log( 'display(): ', 'mai-ad-' + slug ); }

	// Add slot to our array.
	adSlotIds.push( slotId );
	adSlots.push( slot );

	// If amazon is enalbed and ads[slug].sizes only contains a single size named 'fluid'.
	if ( maiPubAdsVars.amazonUAM && 1 === ads[slug].sizes.length && 'fluid' === ads[slug].sizes[0] ) {
		// If debugging, log.
		if ( log ) { console.log( 'disabled safeframe: ', slot.getSlotElementId() ); }

		// Disabled SafeFrame for this slot.
		slot.setForceSafeFrame( false );
	}

	// Set refresh targeting.
	slot.setTargeting( refreshKey, refreshValue );

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

	// Set to return.
	toReturn = slot;

	return toReturn;
}

/**
 * Display slots.
 * The requestManager logic take from Magnite docs.
 *
 * @link https://help.magnite.com/help/web-integration-guide#parallel-header-bidding-integrations
 *
 * @param {array} slots The defined slots.
 */
function maiPubDisplaySlots( slots ) {
	// // Loop through and register.
	// slots.forEach( slot => {
	// 	// If debugging, log.
	// 	if ( log ) { console.log( 'display(): ', slot.getSlotElementId() ); }

	// 	// Register the ad slot.
	// 	// An ad will not be fetched until refresh is called,
	// 	// due to the disableInitialLoad() method being called earlier.
	// 	googletag.display( slot.getSlotElementId() );
	// });

	// Enable services.
	// This needs to run after defineSlot() but before display()/refresh().
	// If we did this in maiPubDefineSlot() it would run for every single slot, instead of batches.
	googletag.enableServices();

	// Set global failsafe timeout ~500ms after DM UI bidder timeout.
	const fallbackTimeout = 3500;

	// Object to manage each request state.
	const requestManager = {
		adserverRequestSent: false,
		dmBidsReceived: true, // This is true for now, intil we implement Prebid.js/Magnite.
		apsBidsReceived: false,
	};

	/**
	 * Send request to ad-server.
	 *
	 * @link https://help.magnite.com/help/web-integration-guide#parallel-header-bidding-integrations
	 */
	const sendAdserverRequest = function() {
		if ( ! requestManager.adserverRequestSent ) {
			requestManager.adserverRequestSent = true;
			googletag.pubads().refresh( slots );
		}
	}

	// // Request bids through DM.
	// pbjs.que.push( function() {
	// 	pbjs.rp.requestBids( {
	// 		gptSlotObjects: slots,
	// 		callback: function() {
	// 			pbjs.setTargetingForGPTAsync();

	// 			requestManager.dmBidsReceived = true;

	// 			if ( requestManager.apsBidsReceived ) {
	// 				sendAdserverRequest();
	// 			}
	// 		}
	// 	});
	// });

	// Handle Amazon UAM bids.
	if ( maiPubAdsVars.amazonUAM ) {
		const uadSlots = [];

		// Loop through slots.
		slots.forEach( slot => {
			// Get slug from slot ID.
			const slug = slot.getSlotElementId().replace( 'mai-ad-', '' );

			// Skip if ads[slug].sizes only contains a single size named 'fluid'. This was throwing an error in amazon.
			if ( 1 === ads[slug].sizes.length && 'fluid' === ads[slug].sizes[0] ) {
				// Remove from slots array and skip.
				// delete slots[slug];
				return;
			}

			// Bail if it's a client ad.
			if ( 'client' === ads[slug]['context'] ) {
				return;
			}

			// Add slot to array for UAD.
			uadSlots.push({
				slotID: 'mai-ad-' + slug,
				slotName: gamBase + ads[slug]['id'],
				sizes: ads[slug].sizes,
			});
		});

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

		// If we have uadSlots.
		if ( uadSlots.length ) {
			// Fetch bids from Amazon UAM using apstag.
			apstag.fetchBids({
				slots: uadSlots,
				timeout: 2e3,
				params: {
					adRefresh: '1', // Must be string.
				}
			}, function( bids ) {
				// Set apstag bids, then trigger the first request to GAM.
				apstag.setDisplayBids();

				// Set the request manager to true.
				requestManager.apsBidsReceived = true;

				// If we have all bids, send the adserver request.
				if ( requestManager.dmBidsReceived ) {
					sendAdserverRequest();

					if ( log ) { console.log( 'refresh() with amazon fetch', slots ); }
				}
			});
		}
		// No UAD, but we have others.
		else if ( slots.length ) {
			// Set the request manager to true.
			requestManager.apsBidsReceived = true;

			// If we have all bids, send the adserver request.
			if ( requestManager.dmBidsReceived ) {
				sendAdserverRequest();

				if ( log ) { console.log( 'refresh() without amazon slots to fetch', slots ); }
			}
		}
	}
	// Standard GAM.
	else {
		// Bail if no slots.
		if ( ! slots.length ) {
			return;
		}

		// Set the request manager to true.
		requestManager.apsBidsReceived = true;

		// If we have all bids, send the adserver request.
		if ( requestManager.dmBidsReceived ) {
			sendAdserverRequest();
		}

		if ( log ) { console.log( 'refresh() with GAM', slots ); }
	}

	// Start the failsafe timeout.
	setTimeout( function() {
		// Log if no adserver request has been sent.
		if ( ! requestManager.adserverRequestSent ) {
			if ( log ) { console.log( 'refresh() with failsafe timeout', slots ); }
		}

		// Maybe send request.
		sendAdserverRequest();

	}, fallbackTimeout );
}

/**
 * Refreshes slots.
 *
 * @param {array} slots The defined slots.
 */
function maiPubRefreshSlots( slots ) {
	if ( maiPubAdsVars.amazonUAM ) {
		console.log( 'setDisplayBids via apstag', slots );
		apstag.setDisplayBids();
	}

	// googletag.pubads().refresh( slots );
	googletag.pubads().refresh( slots, { changeCorrelator: false } );
}
