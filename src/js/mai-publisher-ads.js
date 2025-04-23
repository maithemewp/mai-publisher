// define global PBJS and GPT libraries.
window.pbjs      = window.pbjs || { que: [] };
window.googletag = window.googletag || {};
googletag.cmd    = googletag.cmd || [];

// Define global variables.
const ads              = maiPubAdsVars['ads'];
const adSlotIds        = [];
const adSlots          = [];
const gamBase          = maiPubAdsVars.gamBase;
const gamBaseClient    = maiPubAdsVars.gamBaseClient;
const refreshKey       = 'refresh';
const refreshValue     = maiPubAdsVars.targets.refresh;
const refreshTime      = 30; // Time in seconds.
const loadTimes        = {};
const currentlyVisible = {};
const timeoutIds       = {};
const initTimeout      = 1500; // Fallback in case CMP/Matomo never responds.
const bidderTimeout    = 3000;
const fallbackTimeout  = 4000; // Set global failsafe timeout ~1000ms after DM UI bidder timeout.
const debug            = window.location.search.includes('dfpdeb') || window.location.search.includes('maideb') || window.location.search.includes('pbjs_debug=true');
const log              = maiPubAdsVars.debug;
const bidResponses     = { prebid: {}, amazon: {}, timeouts: [] };
let   timestamp        = Date.now();
let   visitorId        = '';
let   cmpReady         = false;
let   matomoReady      = false;

// If debugging, log.
maiPubLog( 'v207' );

// If using Amazon UAM bids, add it early since it's a large script.
if ( maiPubAdsVars.amazonUAM ) {
	/**
	 * Amazon UAD.
	 * Debug via `apstag.debug('enableConsole')`.
	 * Disable debugging via `apstag.debug('disableConsole')`.
	 */
	!function(a9,a,p,s,t,A,g){if(a[a9])return;function q(c,r){a[a9]._Q.push([c,r])}a[a9]={init:function(){q("i",arguments)},fetchBids:function(){q("f",arguments)},setDisplayBids:function(){},targetingKeys:function(){return[]},_Q:[]};A=p.createElement(s);A.async=!0;A.src=t;g=p.getElementsByTagName(s)[0];g.parentNode.insertBefore(A,g)}("apstag",window,document,"script","//c.amazon-adsystem.com/aax2/apstag.js");

	// Initialize apstag.
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
					sid: maiPubAdsVars.sellersId, // The identifier associated with the seller or reseller account within your advertising system.
					hp: 1, // 1 or 0, whether this node is involved in the payment flow.
					name: maiPubAdsVars.sellersName, // Name of the company paid for inventory under seller ID (optional).
					domain: maiPubAdsVars.domain, // Business domain of this node (optional).
				}
			]
		}
	});
}

/**
 * Handle CMP initialization.
 */
if ( typeof __tcfapi === 'function' ) {
	// Set timeout to proceed with initialization if CMP never responds.
	const tcTimeout = setTimeout(() => {
		if ( ! cmpReady ) {
			maiPubLog( 'MaiPub CMP timeout, proceeding with initialization' );
			cmpReady = true;
			checkInit();
		}
	}, initTimeout );

	try {
		// Add event listener for CMP events.
		__tcfapi( 'addEventListener', 2, ( tcData, success ) => {
			if ( cmpReady ) {
				return;
			}

			if ( tcData && ( tcData.eventStatus === 'tcloaded' || tcData.eventStatus === 'useractioncomplete' ) ) {
				cmpReady = true;
				clearTimeout( tcTimeout );
				maiPubLog( 'MaiPub CMP loaded, proceeding with initialization', success, tcData );
				checkInit();
			}
		});
	} catch ( error ) {
		maiPubLog( 'MaiPub CMP error:', error );
		clearTimeout( tcTimeout );
		cmpReady = true;
		checkInit();
	}
} else {
	// No CMP present, mark as ready.
	cmpReady = true;
	checkInit();
}

/**
 * Handle Matomo initialization.
 */
if ( maiPubAdsVars.matomo?.enabled ) {
	// Check if Matomo is already initialized.
	if ( typeof Matomo !== 'undefined' ) {
		if ( ! visitorId ) {
			visitorId = Matomo.getAsyncTracker().getVisitorId();
		}
		matomoReady = true;
		maiPubLog( `Matomo already initialized, visitorId: ${visitorId}` );
		checkInit();
	} else {
		// Wait for analytics init event.
		document.addEventListener( 'maiPublisherAnalyticsInit', function( event ) {
			if ( ! visitorId ) {
				visitorId = event.detail.tracker.getVisitorId();
			}
			matomoReady = true;
			maiPubLog( `Matomo async event fired, visitorId: ${visitorId}` );
			checkInit();
		}, { once: true } );
	}
} else {
	// Matomo not enabled, mark as ready.
	matomoReady = true;
	checkInit();
}

/**
 * Check if we should initialize GAM.
 * We initialize when either:
 * 1. CMP is ready (or not present) AND Matomo is ready (or not enabled)
 * 2. We've hit our timeout for either system
 */
function checkInit() {
	// Check if we should initialize based on CMP and Matomo states.
	const shouldInit = (
		// CMP is ready or not present.
		( cmpReady || typeof __tcfapi !== 'function' ) &&
		// Matomo is ready or not enabled.
		( matomoReady || ! maiPubAdsVars.matomo?.enabled )
	);

	if ( ! shouldInit ) {
		maiPubLog( 'GAM not initialized, waiting for:', {
			cmp: ! cmpReady && typeof __tcfapi === 'function' ? 'CMP' : null,
			matomo: ! matomoReady && maiPubAdsVars.matomo?.enabled ? 'Matomo' : null
		} );
		return;
	}

	// Initialize the Google Tag.
	initGoogleTag( 'GAM initialized, all systems ready' );
}

/**
 * Initialize the Google Tag once.
 *
 * @param {string} reason - The reason for initializing the Google Tag.
 *
 * @return {void}
 */
function initGoogleTag( reason ) {
	// If still no visitor ID
	if ( ! visitorId ) {
		// Get visitor ID from localStorage
		visitorId = localStorage.getItem( 'maiPubVisitorId' );

		// If still no visitor ID, generate a new one
		if ( ! visitorId ) {
			visitorId = Math.random().toString(36).substring(2, 10) + Date.now().toString(36).substring(2, 10);
			localStorage.setItem( 'maiPubVisitorId', visitorId );
		}

		maiPubLog( `Generated fallback visitorId: ${visitorId}` );
	}

	// Log
	maiPubLog( `Proceeding (${reason}) with visitorId: ${visitorId}` );

	// Push to the queue
	pushGoogleTag( visitorId );
}

/**
 * Push the Google Tag to the queue.
 *
 * @param {string} visitorId - The visitor ID.
 *
 * @return {void}
 */
function pushGoogleTag( visitorId ) {
	// If we have segments.
	if ( maiPubAdsVars.dcSeg ) {
		// Build the PCD script.
		const pcdScript             = document.createElement( 'script' );
				pcdScript.async     = true;
				pcdScript.id        = 'google-pcd-tag';
				pcdScript.className = 'mai-pcd-tag';
				pcdScript.src       = 'https://pagead2.googlesyndication.com/pagead/js/pcd.js';

		// Build the segments.
		let segments = '';
		maiPubAdsVars.dcSeg.forEach( seg => {
			segments += `dc_seg=${seg};`;
		});

		// Build the audience pixel.
		let audiencePixel = `dc_iu=/${maiPubAdsVars.bbNetworkCode}/DFPAudiencePixel;${segments}gd=${maiPubAdsVars.domainHashed}`;

		// If we have a visitor ID, add it.
		if ( visitorId ) {
			audiencePixel += `;ppid=${visitorId};`;
		}

		// Set the audience pixel.
		pcdScript.setAttribute( 'data-audience-pixel', audiencePixel );

		// Insert before the current script.
		// document.currentScript.parentNode.insertBefore( pcdScript, document.currentScript );

		// Insert at the end of the head.
		document.head.appendChild( pcdScript );
	}

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

		// If we have a visitor ID, set it.
		if ( visitorId ) {
			maiPubLog( 'Setting googletag PPID:', visitorId );
			googletag.pubads().setPublisherProvidedId( visitorId );
		}

		// Enable services.
		googletag.enableServices();

		// If no delay, run on DOMContentLoaded.
		if ( ! maiPubAdsVars.loadDelay ) {
			// Check if DOMContentLoaded has run.
			if ( 'loading' === document.readyState ) {
				// If it's still loading, wait for the event.
				document.addEventListener( 'DOMContentLoaded', maiPubDOMContentLoaded, { once: true } );
			} else {
				// If it's already loaded, execute maiPubDOMContentLoaded().
				maiPubDOMContentLoaded();
			}
		}
		// Delayed on window load.
		else {
			// On window load.
			window.addEventListener( 'load', () => {
				setTimeout( maiPubDOMContentLoaded, maiPubAdsVars.loadDelay );
			}, { once: true });
		}

		/**
		 * Set 30 refresh when an ad is in view.
		 */
		googletag.pubads().addEventListener( 'impressionViewable', (event) => {
			const slot   = event.slot;
			const slotId = slot.getSlotElementId();

			// Bail if not a refreshable slot.
			if ( ! maiPubIsRefreshable( slot ) ) {
				return;
			}

			// Set first load to current time.
			loadTimes[slotId] = Date.now();

			// Clear timeout if it exists.
			if ( timeoutIds[slotId] ) {
				clearTimeout( timeoutIds[slotId] );
			}

			// Set timeout to refresh ads for current visible ads.
			timeoutIds[slotId] = setTimeout(() => {
				// If debugging, log.
				maiPubLog( 'refreshed via impressionViewable: ' + slotId, slot );

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

			// Bail if not a refreshable slot.
			if ( ! maiPubIsRefreshable( slot ) ) {
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
			if ( ! loadTimes?.[slotId] || ( loadTimes[slotId] && Date.now() - loadTimes[slotId] < refreshTime * 1000 ) ) {
				return;
			}

			// If debugging, log.
			maiPubLog( 'refreshed via slotVisibilityChanged: ' + slotId, slot );

			// Refresh the slot(s).
			maiPubRefreshSlots( [slot] );
		});

		/**
		 * Checks if this is a client GAM ad and not the main plugin MCM ad,
		 * if it's a client ad and isEmpty, try to load the main plugin ad.
		 */
		googletag.pubads().addEventListener( 'slotRenderEnded', (event) => {
			// Bail if slot is not empty.
			if ( ! event.isEmpty ) {
				return;
			}

			// Bail if not one of our slots.
			if ( ! maiPubIsMaiSlot( event.slot ) ) {
				return;
			}

			// Get slug from slot ID.
			const slotId = event.slot.getSlotElementId();
			const slug   = slotId.replace( 'mai-ad-', '' );

			// Bail if it's not a client ad.
			if ( 'client' !== ads[slug]['context'] ) {
				return;
			}

			// Bail if no backfill ad with a backfill id.
			if ( ! ( ads?.[slug]?.['backfill'] && ads?.[slug]?.['backfillId'] ) ) {
				return;
			}

			// // If debugging, log.
			// maiPubLog( 'maipub backfilling with: ' + ads[slug]['backfill'], document.getElementById( slotId ).id );

			// // Set the ID to the backfill ID and define/display the backfill ad.
			// document.getElementById( slotId ).id = ads[slug]['backfillId'];

			// // Define and display the main plugin ad.
			// maiPubDisplaySlots( [ maiPubDefineSlot( ads[slug]['backfill'] ) ] );

			// // If debugging, log.
			// maiPubLog( 'maipub destroying: ' + slug );

			// // Unset ads[slug].
			// // delete ads[slug];

			// // Destroy the empty slot.
			// googletag.destroySlots( [ event.slot ] );
		});

		// If debugging, set listeners to log.
		if ( debug || log ) {
			// Log when a slot is requested/fetched.
			googletag.pubads().addEventListener( 'slotRequested', (event) => {
				maiPubLog( 'slotRequested:', event.slot, event );
			});

			// Log when a slot response is received.
			googletag.pubads().addEventListener( 'slotResponseReceived', (event) => {
				maiPubLog( 'slotResponseReceived:', event.slot, event );
			});

			// Log when a slot was loaded.
			googletag.pubads().addEventListener( 'slotOnload', (event) => {
				maiPubLog( 'slotOnload:', event.slot, event );
			});

			// Log when slot render has ended, regardless of whether ad was empty or not.
			googletag.pubads().addEventListener( 'slotRenderEnded', (event) => {
				maiPubLog( 'slotRenderEnded:', event.slot, event );
			});

			// Log when a slot ID visibility changed.
			// googletag.pubads().addEventListener( 'slotVisibilityChanged', (event) => {
			// 	maiPubLog( 'changed:', event.slot.getSlotElementId(), `${event.inViewPercentage}%` );
			// });
		}
	});
}

/**
 * DOMContentLoaded and IntersectionObserver handler.
 *
 * @return {void}
 */
function maiPubDOMContentLoaded() {
	// Select all atf and btf ads.
	const toloadATF = [];
	const adsATF    = document.querySelectorAll( '.mai-ad-unit[data-ap="atf"], .mai-ad-unit[data-ap="bs"]' );
	const adsBTF    = document.querySelectorAll( '.mai-ad-unit:not([data-ap="atf"]):not([data-ap="bs"])' );

	// Add to queue, so they don't step on each other.
	googletag.cmd.push(() => {
		// Define ATF ads.
		adsATF.forEach( adATF => {
			// Get slug.
			const slug = adATF.getAttribute( 'id' ).replace( 'mai-ad-', '' );

			// Add to toloadATF array.
			toloadATF.push( maiPubDefineSlot( slug ) );

			// If debugging, add inline styling.
			if ( debug ) {
				adATF.style.outline = '2px dashed limegreen';

				// Add data-label attribute of slug.
				adATF.setAttribute( 'data-label', slug );
			}
		});

		// Display ATF ads.
		if ( toloadATF.length ) {
			maiPubDisplaySlots( toloadATF );
		}
	});

	// Create the IntersectionObserver.
	const observer  = new IntersectionObserver( (entries, observer) => {
		const toLoadBTF = [];

		// Loop through the entries.
		entries.forEach( entry => {
			// Skip if not intersecting.
			if ( ! entry.isIntersecting ) {
				return;
			}

			// Get slug.
			const slug = entry.target.getAttribute( 'id' ).replace( 'mai-ad-', '' );

			// If debugging, add inline styling.
			if ( debug ) {
				entry.target.style.outline = '2px dashed red';

				// Add data-label attribute of slug.
				entry.target.setAttribute( 'data-label', slug );
			}

			// Add to toLoadBTF array.
			toLoadBTF.push( slug );

			// Unobserve. GAM event listener will handle refreshes.
			observer.unobserve( entry.target );
		}); // End entries loop.

		// Bail if no slots to load.
		if ( ! toLoadBTF.length ) {
			return;
		}

		// Add to queue, so they don't step on each other.
		googletag.cmd.push(() => {
			// Define and display all slots in view.
			maiPubDisplaySlots( toLoadBTF.map( slug => maiPubDefineSlot( slug ) ) );
		});
	}, {
		root: null, // Use the viewport as the root.
		rootMargin: '600px 0px 600px 0px', // Trigger when the top of the element is X away from each part of the viewport.
		threshold: 0 // No threshold needed.
	});

	// Observe each BTF ad.
	adsBTF.forEach( adBTF => {
		observer.observe( adBTF );
	});
}

/**
 * Define a slot.
 *
 * @param {string} slug The ad slug.
 *
 * @return {object} The slot object.
 */
function maiPubDefineSlot( slug ) {
	let toReturn = null;

	// Get base from context.
	const base = ads?.[slug]?.['context'] && 'client' === ads[slug]['context'] ? gamBaseClient : gamBase;

	// Define slot ID.
	const slotId = base + ads[slug]['id'];

	// Get slot element ID.
	const slotElId = 'mai-ad-' + slug;

	// Check for existing slot.
	const existingSlot = adSlots.find( slot => slotElId == slot.getSlotElementId() );

	// If existing, return it.
	if ( existingSlot ) {
		maiPubLog( 'Slot already defined:', existingSlot );

		return existingSlot;
	}

	// Define ad slot. googletag.defineSlot( "/1234567/sports", [728, 90], "div-1" );
	const slot = googletag.defineSlot( slotId, ads[slug].sizes, 'mai-ad-' + slug );

	// Register the ad slot.
	// An ad will not be fetched until refresh is called,
	// due to the disableInitialLoad() method being called earlier.
	googletag.display( 'mai-ad-' + slug );

	// Add slot to our array.
	adSlotIds.push( slotId );
	adSlots.push( slot );

	// If debugging, log.
	maiPubLog( 'defineSlot() & display():', adSlots );

	// If amazon is enabled and ads[slug].sizes only contains a single size named 'fluid'.
	// if ( maiPubAdsVars.amazonUAM && 1 === ads[slug].sizes.length && 'fluid' === ads[slug].sizes[0] ) {
	// 	// If debugging, log.
	// 	maiPubLog( 'disabled safeframe: ' + slot.getSlotElementId() );

	// 	// Disabled SafeFrame for this slot.
	// 	slot.setForceSafeFrame( false );
	// }

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
 * @param {array} slots An array of the defined slots objects.
 *
 * @return {void}
 */
function maiPubDisplaySlots( slots ) {
	// Enable services.
	// This needs to run after defineSlot() but before display()/refresh().
	// If we did this in maiPubDefineSlot() it would run for every single slot, instead of batches.
	// NM, changed this when Magnites docs show it how we had it. Via: https://help.magnite.com/help/web-integration-guide
	// googletag.enableServices();

	// Object to manage each request state.
	const requestManager = {
		adserverRequestSent: false,
		dmBidsReceived: false,
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

	// Handle Magnite/DM bids.
	if ( maiPubAdsVars.magnite ) {
		// Force integers.
		maiPubAdsVars.ortb2.mobile         = parseInt( maiPubAdsVars.ortb2.mobile );
		maiPubAdsVars.ortb2.privacypolicy  = parseInt( maiPubAdsVars.ortb2.privacypolicy );
		maiPubAdsVars.ortb2.cattax         = parseInt( maiPubAdsVars.ortb2.cattax );
		maiPubAdsVars.ortb2.content.cattax = parseInt( maiPubAdsVars.ortb2.content.cattax );

		// Fetch bids from Magnite using Prebid.
		pbjs.que.push( function() {
			// Start the config.
			const pbjsConfig = {
				bidderTimeout: bidderTimeout,
				enableTIDs: true,
				ortb2: maiPubAdsVars.ortb2,
			};

			/**
			 * If we have a visitor ID, add it.
			 * @link https://docs.prebid.org/dev-docs/modules/userid-submodules/pubprovided.html
			 */
			if ( visitorId ) {
				pbjsConfig.userSync = {
					userIds: [{
						name: "pubProvidedId",
						params: {
							eids: [{
								source: maiPubAdsVars.domain,
								uids: [{
									id: visitorId,
									atype: 1
								}]
							}]
						}
					}]
				};
			}

			// If debugging or logging, enable debugging for magnite.
			// Disabled. Magnite said this was breaking their own debugging.
			// if ( debug || log ) {
			// 	pbjsConfig.debugging = {
			// 		enabled: true,
			// 	};
			// }

			// Log.
			maiPubLog( 'pbjsConfig', pbjsConfig );

			// Set the magnite config.
			pbjs.setConfig( pbjsConfig );

			// This is from Claude, Idk if this is an actual event, I couldn't find it in the docs.
			// Add bid response tracking
			pbjs.onEvent('bidResponse', function(bid) {
				bidResponses.prebid[bid.bidder] = {
					value: bid.cpm,
					size: bid.size,
					adUnitCode: bid.adUnitCode,
					timeToRespond: bid.timeToRespond + 'ms'
				};
				maiPubLog('Prebid bid received from ' + bid.bidder, bid);
			});

			// This is from Claude, Idk if this is an actual event, I couldn't find it in the docs.
			// Add timeout monitoring
			pbjs.onEvent('bidTimeout', function(timeoutBids) {
				timeoutBids.forEach(bid => {
					bidResponses.timeouts.push({
						bidder: bid.bidder,
						adUnitCode: bid.adUnitCode,
						timeout: bidderTimeout + 'ms'
					});
				});
				maiPubLog('Bid timeout occurred:', timeoutBids);
			});

			// This is from Claude, Idk if this is an actual event, I couldn't find it in the docs.
			// Add all bid response monitoring
			pbjs.onEvent('allBidsBack', function(bids) {
				maiPubLog('All bids back:', {
					bids: bids,
					timeouts: bidResponses.timeouts,
					timing: {
						totalTime: Date.now() - timestamp + 'ms',
						bidderTimeout: bidderTimeout + 'ms',
						fallbackTimeout: fallbackTimeout + 'ms'
					}
				});
			});

			// Request bids
			pbjs.rp.requestBids( {
				gptSlotObjects: slots,
				callback: function() {
					pbjs.setTargetingForGPTAsync();
					requestManager.dmBidsReceived = true;

					// Log bid responses for debugging
					maiPubLog('All prebid responses:', bidResponses.prebid);

					if ( requestManager.apsBidsReceived ) {
						sendAdserverRequest();
						maiPubLog('refresh() with prebid: ' + slots.map( slot => slot.getSlotElementId() ).join(', '), slots);
					}
				}
			});
		});
	}
	// No magnite.
	else {
		// Set the magnite demand manager request manager to true.
		requestManager.dmBidsReceived = true;
	}

	// Handle Amazon UAM bids.
	if ( maiPubAdsVars.amazonUAM ) {
		// Filter out ads[slug].sizes that only contain a single size named 'fluid'. This was throwing an error in amazon.
		// Filter out client ads.
		const uadSlots = slots
			.filter( slot => {
				const slug = slot.getSlotElementId().replace( 'mai-ad-', '' );

				return ! ( 1 === ads[slug].sizes.length && 'fluid' === ads[slug].sizes[0] ) && 'client' !== ads[slug]['context'];
			})
			.map( slot => {
				const elId = slot.getSlotElementId();
				const slug = elId.replace( 'mai-ad-', '' );

				return {
					slotID: elId,
					slotName: gamBase + ads[slug]['id'],
					sizes: ads[slug].sizes,
				};
			});

		// If we have uadSlots.
		if ( uadSlots.length ) {
			// Set the amazon config.
			const amazonConfig = {
				slots: uadSlots,
				timeout: bidderTimeout,
				params: {
					adRefresh: '1', // Must be string.
				}
			};

			// Log.
			maiPubLog( 'amazonConfig', amazonConfig );

			// Fetch bids from Amazon UAM using apstag.
			const requestStartTime = Date.now();
			apstag.fetchBids( amazonConfig, function(bids) {
				// Log timing information
				const amazonResponseTime = Date.now() - requestStartTime;
				maiPubLog('Amazon response time:', amazonResponseTime + 'ms' );

				// Track Amazon bids
				bids.forEach((bid) => {
					bidResponses.amazon[bid.slotID] = {
						value: bid.amznbid,
						size: bid.size,
						responseTime: amazonResponseTime + 'ms',
						error: bid.error || null
					};
				});
				maiPubLog('Amazon bids received:', bidResponses.amazon);

				// Set apstag bids, then trigger the first request to GAM.
				apstag.setDisplayBids();

				// Set the request manager to true.
				requestManager.apsBidsReceived = true;

				// If we have all bids, send the adserver request.
				if ( requestManager.dmBidsReceived ) {
					sendAdserverRequest();

					maiPubLog( 'refresh() with amazon fetch: ' + uadSlots.map( slot => slot.slotID.replace( 'mai-ad-', '' ) ).join( ', ' ), uadSlots );
				}

				// Log if debugging.
				if ( debug || log ) {
					// Check bid responses for errors.
					bids.forEach((bid) => {
						if ( bid.error ) {
							maiPubLog( 'apstag.fetchBids error:', bid );
						}
					});
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

				maiPubLog( 'refresh() without amazon slots to fetch: ' + slots.map( slot => slot.getSlotElementId() ).join( ', ' ), slots );
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

		maiPubLog( 'refresh() with GAM:' + slots.map( slot => slot.getSlotElementId() ).join( ', ' ), slots );
	}

	// Start the failsafe timeout.
	setTimeout(() => {
		const timeoutData = {
			adserverRequestSent: requestManager.adserverRequestSent,
			dmBidsReceived: requestManager.dmBidsReceived,
			apsBidsReceived: requestManager.apsBidsReceived,
			prebidBids: bidResponses.prebid,
			amazonBids: bidResponses.amazon,
			timeouts: bidResponses.timeouts,
			timing: {
				totalTime: Date.now() - timestamp,
				bidderTimeout: bidderTimeout,
				fallbackTimeout: fallbackTimeout
			}
		};

		// Log if no adserver request has been sent.
		if ( ! requestManager.adserverRequestSent ) {
			maiPubLog( 'refresh() with failsafe timeout. Debug data:', timeoutData );
		}

		// Maybe send request.
		sendAdserverRequest();

	}, fallbackTimeout );
}

/**
 * Check if a slot is a defined mai ad slot.
 * Checks if the ad slot ID is in our array of ad slot IDs.
 *
 * @param {object} slot The ad slot.
 *
 * @return {boolean} True if a mai ad slot.
 */
function maiPubIsMaiSlot( slot ) {
	return slot && adSlotIds.includes( slot.getAdUnitPath() );
}

/**
 * Check if a slot is refreshable.
 * Checks if we have a defined mai ad slot that has targetting set to refresh.
 *
 * @param {object} slot The ad slot.
 *
 * @return {boolean} True if refreshable.
 */
function maiPubIsRefreshable( slot ) {
	return maiPubIsMaiSlot( slot ) && Boolean( slot.getTargeting( refreshKey ).shift() );
}

/**
 * Refreshes slots.
 *
 * @param {array} slots The defined slots.
 *
 * @return {void}
 */
function maiPubRefreshSlots( slots ) {
	if ( maiPubAdsVars.amazonUAM ) {
		maiPubLog( 'setDisplayBids via apstag: ' + slots.map( slot => slot.getSlotElementId() ).join( ', ' ), slots );
		apstag.setDisplayBids();
	}

	// googletag.pubads().refresh( slots );
	googletag.pubads().refresh( slots, { changeCorrelator: false } );
}

/**
 * Log if debugging.
 *
 * @param {mixed} mixed The data to log.
 *
 * @return {void}
 */
function maiPubLog( ...mixed ) {
	if ( ! ( debug || log ) ) {
		return;
	}

	// Set log variables.
	let timer = 'maipub ';

	// Set times.
	const current = Date.now();
	const now     = new Date().toLocaleTimeString( 'en-US', { hour12: true } );

	// If first, start.
	if ( timestamp === current ) {
		timer += 'start';
	}
	// Not first, add time since.
	else {
		timer += current - timestamp + 'ms';
	}

	// Log the combined message.
	console.log( `${timer} ${now}`, mixed );
}