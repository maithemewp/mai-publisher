window.googletag = window.googletag || {};
googletag.cmd    = googletag.cmd || [];

const ads          = maiPubAdsVars['ads'];
const adSlotIds    = [];
const adSlots      = [];
const immediate    = [];
const gamBase      = maiPubAdsVars.gamBase;
const refreshKey   = 'refresh';
const refreshValue = true;
const refreshTime  = 30; // Time in seconds.
const debug        = window.location.search.includes('dfpdeb') || window.location.search.includes('maideb');

// Separate ATF and BTF slots.
const { adSlotsATF, adSlotsBTF } = Object.entries(ads).reduce( ( acc, [ key, value ] ) => {
	// If above the fold or bottom sticky.
	if ( 'atf' === value.targets.ap || 'bs' === value.targets.ap ) {
		acc.adSlotsATF[ key ] = value;
	} else {
		acc.adSlotsBTF[ key ] = value;
	}

	return acc;

}, { adSlotsATF: {}, adSlotsBTF: {} });

// If debugging, log.
if ( debug ) {
	console.log( 'v22', 'debug:', debug );
}

// Add to googletag items.
googletag.cmd.push(() => {
	/**
	 * Set SafeFrame -- This setting will only take effect for subsequent ad requests made for the respective slots.
	 * To enable cross domain rendering for all creatives, execute setForceSafeFrame before loading any ad slots.
	 *
	 * UAM breaks when this is true.
	 */
	googletag.pubads().setForceSafeFrame( ! maiPubAdsVars.amazonUAM );

	// Loop through ATF ads. The `slug` key is the incremented id like "incontent-2", etc.
	Object.keys( adSlotsATF ).forEach( slug => {
		// Add to immediate array.
		immediate.push( maiPubDefineSlot( slug ) );
	});

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
			if ( debug ) {
				console.log( 'refreshed A:', slotId );
			}

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
		if ( debug ) {
			console.log( 'refreshed B:', slotId );
		}

		maiPubRefreshSlots( [slot] );
	});

	// Enable services.
	googletag.enableServices();

	// If using Amazon UAM bids, add it.
	if ( maiPubAdsVars.amazonUAM ) {
		/**
		 * Amazon UAD.
		 * Debug via `apstag.debug('enableConsole')`
		 */
		!function(a9,a,p,s,t,A,g){if(a[a9])return;function q(c,r){a[a9]._Q.push([c,r])}a[a9]={init:function(){q("i",arguments)},fetchBids:function(){q("f",arguments)},setDisplayBids:function(){},targetingKeys:function(){return[]},_Q:[]};A=p.createElement(s);A.async=!0;A.src=t;g=p.getElementsByTagName(s)[0];g.parentNode.insertBefore(A,g)}("apstag",window,document,"script","//c.amazon-adsystem.com/aax2/apstag.js");
	}

	// Display ATF ads if there are any.
	if ( immediate.length ) {
		maiPubDisplaySlots( immediate );
	}

	// If debugging, set listeners to log.
	if ( debug ) {
		// Log when a slot ID is fetched.
		googletag.pubads().addEventListener( 'slotRequested', (event) => {
			console.log( 'fetched:', event.slot.getSlotElementId() );
		});

		// Log when a slot ID is rendered.
		googletag.pubads().addEventListener( 'slotOnload', (event) => {
			console.log( 'rendered:', event.slot.getSlotElementId() );
		});

		// Log when a slot ID visibility changed.
		// googletag.pubads().addEventListener( 'slotVisibilityChanged', (event) => {
		// 	console.log( 'changed:', event.slot.getSlotElementId(), `${event.inViewPercentage}%` );
		// });
	}
}); // End `googletag.cmd.push`.

/**
 * Handler for IntersectionObserver.
 */
document.addEventListener( 'DOMContentLoaded', function() {
	// Push, so this runs after the above code.
	googletag.cmd.push(() => {
		let initialLoad = true;
		let toLoad      = [];

		// Create the IntersectionObserver.
		const observer = new IntersectionObserver( (entries, observer) => {
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

				// If debugging, add border.
				if ( debug ) {
					entry.target.style.border = '2px dashed red';
				}

				// Add to toLoad array.
				toLoad.push( slug );

				// Unobserve. GAM event listener will handle refreshes.
				observer.unobserve( entry.target );
			}); // End entries loop.

			// Process the first array immediately after observing.
			if ( initialLoad ) {
				// console.log( 'Elements in view immediately:', toLoad );
				// Process the slots.
				processSlots();
				// Set initialLoad to false.
				initialLoad = false;
			}
			// All batching via a short delay.
			else {
				setTimeout(() => {
					// console.log( 'Elements in view after delay:', toLoad );
					// Process the slots.
					processSlots();
				}, 200 );
			}
		}, {
			root: null, // Use the viewport as the root
			rootMargin: '600px 0px 600px 0px', // Trigger when the top of the element is X away from each part of the viewport.
			threshold: 0 // No threshold needed
		});

		// Function to process slots.
		function processSlots() {
			// Define and display all slots in view.
			maiPubDisplaySlots( toLoad.map( slug => maiPubDefineSlot( slug ) ) );
			// Clear toLoad array.
			toLoad = [];
		}

		// Select all ad units.
		const adUnits = document.querySelectorAll( '.mai-ad-unit:not([data-ap="atf"])' );

		// Observe each element.
		adUnits.forEach( adUnit => {
			observer.observe( adUnit );
		});
	});
});

/**
 * Define a slot.
 *
 * @param {string} slug The ad slug.
 */
function maiPubDefineSlot( slug ) {
	let toReturn = null;

	googletag.cmd.push(() => {
		// Define slot ID.
		const slotId = gamBase + ads[slug]['id'];
		// Define ad slot. googletag.defineSlot( "/1234567/sports", [728, 90], "div-1" );
		const slot = googletag.defineSlot( slotId, ads[slug].sizes, 'mai-ad-' + slug );

		// Add slot to our array.
		adSlotIds.push( slotId );
		adSlots.push( slot );

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

		toReturn = slot;
	});

	return toReturn;
}

/**
 * Initial display of slots.
 *
 * @param {array} slots The defined slots.
 */
function maiPubDisplaySlots( slots ) {
	// Push it.
	googletag.cmd.push(() => {
		// Handle Amazon UAM bids.
		if ( maiPubAdsVars.amazonUAM ) {
			const uadSlots = [];

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

			// Loop through slots.
			slots.forEach( slot => {
				// Get slug from slot ID.
				const slug = slot.getSlotElementId().replace( 'mai-ad-', '' );

				// Skip if ads[slug].sizes only contains a single size named 'fluid'. This was throwing an error in amazon.
				if ( 1 === ads[slug].sizes.length && 'fluid' === ads[slug].sizes[0] ) {
					// Remove from slots array and skip.
					delete slots[slug];
					return;
				}

				// Add slot to array for UAD.
				uadSlots.push({
					slotID: 'mai-ad-' + slug,
					slotName: gamBase + ads[slug]['id'],
					sizes: ads[slug].sizes,
				});
			});

			// Bail if no uadSlots.
			if ( ! uadSlots.length ) {
				return;
			}

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
					googletag.pubads().refresh( slots );
				});
			});
		}
		// Standard GAM.
		else {
			googletag.pubads().refresh( slots );
		}
	});
}

/**
 * Refreshes slots.
 *
 * @param {array} slots The defined slots.
 */
function maiPubRefreshSlots( slots ) {
	googletag.cmd.push(() => {
		if ( maiPubAdsVars.amazonUAM ) {
			apstag.setDisplayBids();
		}

		googletag.pubads().refresh( slots );
	});
}