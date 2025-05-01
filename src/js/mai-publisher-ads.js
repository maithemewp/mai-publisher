// define global PBJS and GPT libraries.
window.pbjs      = window.pbjs || { que: [] };
window.googletag = window.googletag || {};
googletag.cmd    = googletag.cmd || [];

// Define global variables.
const ads              = maiPubAdsVars['ads'];
const adGamIds         = []; // Stores GAM Ad Unit Paths (e.g., /12345/leaderboard) for slots defined by this script.
const adSlots          = []; // Stores the actual GPT Slot objects returned by defineSlot.
const gamBase          = maiPubAdsVars.gamBase;
const gamBaseClient    = maiPubAdsVars.gamBaseClient;
const refreshKey       = 'refresh';
const refreshValue     = maiPubAdsVars.targets.refresh;
const refreshTime      = 32 * 1000;  // Seconds to milliseconds. Google recommends 30, we add 2 seconds for safety.
const slotManager      = {};
const timeoutManager   = {};
const cmpTimeout       = 2000; // Fallback in case CMP never responds.
const matomoTimeout    = 2000; // Fallback in case Matomo never loads.
const bidderTimeout    = 5000; // Timout for PBJS and Amazon UAM bids.
const fallbackTimeout  = 6000; // Set global failsafe timeout, something longer than the bidderTimeout.
const debug            = window.location.search.includes('dfpdeb') || window.location.search.includes('maideb') || window.location.search.includes('pbjs_debug=true');
const log              = maiPubAdsVars.debug;
const serverConsent    = Boolean( maiPubAdsVars.consent );
const serverPpid       = maiPubAdsVars.ppid;
const localConsent     = maiPubGetLocalConsent();
const localPpid        = maiPubGetLocalPpid();
let   timestamp        = Date.now();
let   consent          = serverConsent || localConsent;
let   ppid             = '';
let   isGeneratingPpid = false;
let   cmpReady         = false;
let   matomoReady      = false;
let   gptInitialized   = false;

// If debugging, log.
maiPubLog( 'v236' );

// If we have a server-side PPID, log it.
if ( serverPpid ) {
	ppid = serverPpid;
	maiPubLog( `Using server-side PPID: ${ ppid }` );
} else if ( localPpid ) {
	ppid = localPpid;
	maiPubLog( `Using local PPID: ${ ppid }` );
}

/**
 * If using Amazon UAM bids, add it early since it's a large script.
 */
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
 * Configure Prebid.js if Magnite is enabled.
 */
if ( maiPubAdsVars.magnite ) {
	// Force integers.
	maiPubAdsVars.ortb2.mobile         = parseInt( maiPubAdsVars.ortb2.mobile );
	maiPubAdsVars.ortb2.privacypolicy  = parseInt( maiPubAdsVars.ortb2.privacypolicy );
	maiPubAdsVars.ortb2.cattax         = parseInt( maiPubAdsVars.ortb2.cattax );
	maiPubAdsVars.ortb2.content.cattax = parseInt( maiPubAdsVars.ortb2.content.cattax );

	// Configure Prebid.js
	pbjs.que.push( function() {
		// Start the config.
		const pbjsConfig = {
			bidderTimeout: bidderTimeout,
			enableTIDs: true,
			ortb2: maiPubAdsVars.ortb2,
			// @link https://github.com/prebid/prebid.github.io/blob/master/dev-docs/modules/schain.md
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
		};

		/**
		 * If we have a ppid, add it.
		 * @link https://docs.prebid.org/dev-docs/modules/userid-submodules/pubprovided.html
		 */
		if ( ppid ) {
			pbjsConfig.userSync = {
				userIds: [{
					name: "pubProvidedId",
					params: {
						eids: [{
							source: maiPubAdsVars.domain,
							uids: [{
								id: ppid,
								atype: 1
							}]
						}]
					}
				}]
			};
		}

		// Log.
		maiPubLog( 'pbjsConfig', pbjsConfig );

		// Set the magnite config.
		pbjs.setConfig( pbjsConfig );

		// // Add bid response tracking.
		// pbjs.onEvent( 'bidResponse', function( bid ) {
		// 	bidResponses.prebid[ bid.bidder ] = {
		// 		value: bid.cpm,
		// 		size: bid.size,
		// 		adUnitCode: bid.adUnitCode,
		// 		timeToRespond: bid.timeToRespond + 'ms'
		// 	};
		// 	maiPubLog( `Prebid bid received from ${ bid.bidder }`, bid );
		// });
		// // Add bid response tracking.
		// pbjs.onEvent( 'bidResponse', function( bid ) {
		// 	bidResponses.prebid[ bid.bidder ] = {
		// 		value: bid.cpm,
		// 		size: bid.size,
		// 		adUnitCode: bid.adUnitCode,
		// 		timeToRespond: bid.timeToRespond + 'ms'
		// 	};
		// 	maiPubLog( `Prebid bid received from ${ bid.bidder }`, bid );
		// });

		// // Add timeout monitoring.
		// pbjs.onEvent( 'bidTimeout', function( timeoutBids ) {
		// 	timeoutBids.forEach(bid => {
		// 		bidResponses.timeouts.push({
		// 			bidder: bid.bidder,
		// 			adUnitCode: bid.adUnitCode,
		// 			timeout: bidderTimeout + 'ms'
		// 		});
		// 	});
		// 	maiPubLog( 'Bid timeout occurred:', timeoutBids );
		// });
		// // Add timeout monitoring.
		// pbjs.onEvent( 'bidTimeout', function( timeoutBids ) {
		// 	timeoutBids.forEach(bid => {
		// 		bidResponses.timeouts.push({
		// 			bidder: bid.bidder,
		// 			adUnitCode: bid.adUnitCode,
		// 			timeout: bidderTimeout + 'ms'
		// 		});
		// 	});
		// 	maiPubLog( 'Bid timeout occurred:', timeoutBids );
		// });

		// // Log when the auction ends.
		// pbjs.onEvent( 'auctionEnd', function( bids ) {
		// 	maiPubLog( 'Prebid auction ended:', bids, {
		// 		prebid: bidResponses.prebid,
		// 		timeouts: bidResponses.timeouts,
		// 		timing: {
		// 			totalTime: Date.now() - timestamp + 'ms',
		// 			bidderTimeout: bidderTimeout + 'ms',
		// 			fallbackTimeout: fallbackTimeout + 'ms'
		// 		}
		// 	});
		// });
		// // Log when the auction ends.
		// pbjs.onEvent( 'auctionEnd', function( bids ) {
		// 	maiPubLog( 'Prebid auction ended:', bids, {
		// 		prebid: bidResponses.prebid,
		// 		timeouts: bidResponses.timeouts,
		// 		timing: {
		// 			totalTime: Date.now() - timestamp + 'ms',
		// 			bidderTimeout: bidderTimeout + 'ms',
		// 			fallbackTimeout: fallbackTimeout + 'ms'
		// 		}
		// 	});
		// });
	});
}

/**
 * Handle CMP initialization.
 */
if ( 'function' === typeof __tcfapi ) {
	// Set timeout to proceed with initialization if CMP never responds.
	const cmpTimeoutId = setTimeout(() => {
		if ( ! cmpReady ) {
			maiPubLog( 'CMP timeout, proceeding with initialization' );
			cmpReady = true;
			maiPubMaybeInit();
		}
	}, cmpTimeout );

	try {
		// Add event listener for CMP events.
		__tcfapi( 'addEventListener', 2, ( tcData, success ) => {
			if ( cmpReady ) {
				return;
			}

			// If we have loaded or completed.
			if ( tcData && ( tcData.eventStatus === 'tcloaded' || tcData.eventStatus === 'useractioncomplete' ) ) {
				cmpReady = true;
				consent  = Boolean( success );
				clearTimeout( cmpTimeoutId );
				maiPubLog( `CMP loaded, proceeding with initialization: ${ success }`, tcData );
				maiPubMaybeInit();
			}
		});
	} catch ( error ) {
		maiPubLog( 'CMP error:', error );
		clearTimeout( cmpTimeoutId );
		cmpReady = true;
		maiPubMaybeInit();
	}
} else {
	// No CMP present, mark as ready.
	cmpReady = true;
	maiPubMaybeInit();
}

/**
 * Handle PPID and Matomo initialization.
 */
if ( maiPubAdsVars.matomo.enabled && maiPubAdsVars.shouldTrack ) {
	// Set timeout to proceed with initialization if Matomo never responds.
	const matomoTimeoutId = setTimeout(() => {
		if ( ! matomoReady ) {
			maiPubLog( 'Matomo timeout, proceeding with initialization' );
			matomoReady = true;
			maiPubMaybeInit();
		}
	}, matomoTimeout );

	// If we already have a PPID, proceed without waiting for Matomo.
	if ( ppid ) {
		matomoReady = true;
		clearTimeout( matomoTimeoutId );
		maiPubLog( `Skipping Matomo initialization, using existing PPID: ${ppid}` );
		maiPubMaybeInit();
	}
	// Check if Matomo is already initialized.
	else if ( 'undefined' !== typeof Matomo ) {
		try {
			// Get the tracker.
			const tracker = Matomo.getAsyncTracker();

			// If we have a tracker.
			if ( tracker ) {
				// Get the visitor ID.
				const visitorId = tracker.getVisitorId();

				// If we have a visitor ID, generate a PPID from it.
				if ( visitorId ) {
					maiPubGeneratePpid( visitorId ).then( transformedPpid => {
						ppid        = transformedPpid;
						matomoReady = true;
						clearTimeout( matomoTimeoutId );
						maiPubLog( `Matomo already initialized, generated PPID from visitor ID: ${ppid}` );
						maiPubMaybeInit();
					}).catch( error => {
						maiPubLog( 'Error generating PPID from Matomo visitor ID:', error );
						// Fallback to random PPID.
						maiPubGeneratePpid().then( transformedPpid => {
							ppid        = transformedPpid;
							matomoReady = true;
							clearTimeout( matomoTimeoutId );
							maiPubLog( `Matomo already initialized, generated random PPID after generation error: ${ppid}` );
							maiPubMaybeInit();
						});
					});
				}
			}
		} catch (error) {
			maiPubLog( 'Error accessing Matomo:', error );
			// Fallback to random PPID.
			maiPubGeneratePpid().then( transformedPpid => {
				ppid        = transformedPpid;
				matomoReady = true;
				clearTimeout( matomoTimeoutId );
				maiPubLog( `Matomo already initialized, generated random PPID after catching error: ${ppid}` );
				maiPubMaybeInit();
			});
		}
	}
	// No matomo and no ppid, wait for analytics init event
	else {
		maiPubLog( 'Matomo not initialized, waiting for analytics init event' );
		// Wait for analytics init event.
		document.addEventListener( 'maiPublisherAnalyticsInit', function( event ) {
			// Get and transform the visitor ID immediately.
			maiPubGeneratePpid( event.detail.tracker.getVisitorId() ).then( transformedPpid => {
				ppid        = transformedPpid;
				matomoReady = true;
				clearTimeout( matomoTimeoutId );
				maiPubLog( `Matomo async event fired, generated PPID from visitor ID: ${ppid}` );
				maiPubMaybeInit();
			});
		}, { once: true } );
	}
} else {
	matomoReady = true;

	if ( ! maiPubAdsVars.matomo.enabled ) {
		maiPubLog( 'Matomo disabled' );
	} else if ( ! maiPubAdsVars.shouldTrack ) {
		maiPubLog( 'Matomo enabled but should not track' );
	}

	maiPubMaybeInit();
}

/**
 * Check if we should initialize GAM.
 * We initialize when either:
 * 1. CMP is ready (or not present) AND Matomo is ready (or not enabled)
 * 2. We've hit our timeout for either system
 */
function maiPubMaybeInit() {
	// Check if we should initialize based on CMP and Matomo states.
	const shouldInit = (
		// CMP is ready or not present.
		( cmpReady || 'function' !== typeof __tcfapi ) &&
		// Matomo is ready or not enabled.
		( matomoReady || ! maiPubAdsVars.matomo.enabled )
	);

	// If we shouldn't initialize.
	if ( ! shouldInit ) {
		// Build a string of what we're waiting for.
		const waitingFor = [];
		if ( ! cmpReady && 'function' === typeof __tcfapi ) {
			waitingFor.push( 'CMP' );
		}
		if ( ! matomoReady && maiPubAdsVars.matomo.enabled ) {
			waitingFor.push( 'Matomo' );
		}

		// Log reason.
		maiPubLog( 'GAM not initialized, waiting for ' + waitingFor.join( ' and ' ) );

		// Bail, not initializing yet.
		return;
	}

	// If still no ppid.
	if ( ! ppid ) {
		// Generate a random PPID.
		maiPubGeneratePpid().then( transformedPpid => {
			ppid = transformedPpid;
			maiPubLog( `Generated random PPID: ${ppid}` );
			maiPubLog( `Initializing GAM with ppid: ${ppid}` );
			maiPubInit();
		});
	}
	// We have a ppid.
	else {
		maiPubLog( `Initializing GAM with ppid: ${ppid}` );
		maiPubInit();
	}
}

/**
 * Push the Google Tag to the queue.
 *
 * @return {void}
 */
function maiPubInit() {
	// If consent is different from the local consent, store it.
	if ( consent !== serverConsent || consent !== localConsent ) {
		maiPubSetLocalConsent( consent );
	}

	// If ppid is different from the local ppid, store it.
	if ( ppid && ( ppid !== serverPpid || ! localPpid || ppid !== localPpid ) ) {
		maiPubSetLocalPpid( ppid );
	}

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

		// If we have a ppid, add it.
		if ( ppid ) {
			audiencePixel += `;ppid=${ppid}`;
		}

		// Set the audience pixel.
		pcdScript.setAttribute( 'data-audience-pixel', audiencePixel );

		// Insert before the current script.
		// document.currentScript.parentNode.insertBefore( pcdScript, document.currentScript );

		// Insert at the end of the head.
		document.head.appendChild( pcdScript );
	}

	// If no delay, run on DOMContentLoaded.
	if ( ! maiPubAdsVars.loadDelay ) {
		// Check if DOMContentLoaded has run.
		if ( 'loading' === document.readyState ) {
			// If it's still loading, wait for the event.
			document.addEventListener( 'DOMContentLoaded', maiPubRun, { once: true } );
		} else {
			// If it's already loaded, execute maiPubRun().
			maiPubRun();
		}
	}
	// Delayed on window load.
	else {
		// On window load.
		window.addEventListener( 'load', () => {
			setTimeout( maiPubRun, maiPubAdsVars.loadDelay );
		}, { once: true });
	}
}

/**
 * DOMContentLoaded and IntersectionObserver handler.
 *
 * @return {void}
 */
function maiPubRun() {
	/**
	 * Setup the IntersectionObserver.
	 * This doesn't run until step 2, after Google Tag is setup.
	 */
	const observer = new IntersectionObserver( (entries, observer) => {
		const ATFSlugs = [];
		const BTFSlugs = [];

		// Loop through the entries.
		entries.forEach( entry => {
			// Get slug.
			const slotId = entry.target.getAttribute( 'id' );
			const slug   = slotId.replace( 'mai-ad-', '' );

			// If intersecting.
			if ( entry.isIntersecting ) {
				// Set the slot to visible (using slotManager).
				slotManager[ slotId ].visible = true;

				// GPT is not initialized.
				// then we define and display the in view slots,
				// enable services,
				// refresh the slots,
				// then gptInitialized = true;
				if ( ! gptInitialized ) {
					ATFSlugs.push( slug );
				}
				// GPT is initialized.
				else {
					// then we only define, display, and refresh the in view slots.
					BTFSlugs.push( slug );
				}

				// If debugging, add inline styling.
				if ( debug ) {
					// Add inline styling.
					entry.target.style.outline = '2px dashed red';

					// Add data-label attribute of slug.
					entry.target.setAttribute( 'data-label', slug );
				}

				// Add the slug to the slugsToRequest array.
				// slugsToRequest.push( slug );

				// Unobserve the displayed slots, let GAM events handle refreshing and visibility.
				observer.unobserve( entry.target );
			}
			// Not intersecting.
			else {
				// Force the slot to not visible.
				slotManager[ slotId ].visible = false;
			}
		});

		// If there are ATF slugs.
		if ( ATFSlugs.length ) {
			/**
			 * Define and display the ATF slots.
			 * Enable services so ATF ads can be refreshed.
			 * Set GPT initialized to true.
			 * Maybe request the slots.
			 */
			googletag.cmd.push(() => {
				// Loop through the ATF slugs.
				ATFSlugs.forEach( slug => {
					// Define the slot.
					maiPubDefineSlot( slug );

					// Display.
					googletag.display( 'mai-ad-' + slug );
				});

				// Enable services so ATF ads can be refreshed.
				googletag.enableServices();

				// Set GPT initialized to true.
				gptInitialized = true;

				// Maybe request the slots.
				maiPubMaybeRequestSlots( ATFSlugs );
			});
		}

		// If there are BTF slugs to define, define them.
		if ( BTFSlugs.length ) {
			/**
			 * Define and display the BTF slots.
			 * Set GPT initialized to true.
			 * Maybe request the slots.
			 */
			googletag.cmd.push(() => {
				// Loop through the BTF slugs.
				BTFSlugs.forEach( slug => {
					// Define the slot.
					maiPubDefineSlot( slug );

					// Display the slot.
					googletag.display( 'mai-ad-' + slug );
				});

				// Maybe request the slots.
				maiPubMaybeRequestSlots( BTFSlugs );
			});
		}
	}, {
		root: null, // Use the viewport as the root.
		rootMargin: '600px 0px 600px 0px', // Trigger when the top of the element is X away from each part of the viewport.
		threshold: 0 // No threshold needed.
	});

	/**
	 * 1. Setup Google Tag.
	 */
	googletag.cmd.push(() => {
		/**
		 * Set SafeFrame -- This setting will only take effect for subsequent ad requests made for the respective slots.
		 * To enable cross domain rendering for all creatives, execute setForceSafeFrame before loading any ad slots.
		 */
		// Disabled for now: https://developers.google.com/publisher-tag/reference#googletag.PubAdsService_setForceSafeFrame
		// googletag.pubads().setForceSafeFrame( true );

		// Get the IAB categories, removing duplicates.
		const iabCats = [...new Set( [ maiPubAdsVars.iabGlobalCat, maiPubAdsVars.iabCat ].filter( cat => cat ) )];

		// If we have IAB categories, set them in the config as Publisher Provided Signals (PPS).
		if ( iabCats.length ) {
			/**
			 * Set Google Publisher Tag config for PPS.
			 * The docs make it seem like it only supports IAB Content Categories 2.2, not 3.0.
			 *
			 * @link https://developers.google.com/publisher-tag/reference#googletag.config.PublisherProvidedSignalsConfig
			 */
			googletag.setConfig({
				pps: {
					taxonomies: {
						IAB_CONTENT_2_2: {
							values: iabCats,
						},
					},
				},
			});
		}

		// Disable initial load for header bidding.
		googletag.pubads().disableInitialLoad();

		// Enable single request.
		googletag.pubads().enableSingleRequest();

		// Make ads centered.
		googletag.pubads().setCentering( true );

		// If we have a ppid, set it.
		if ( ppid ) {
			maiPubLog( 'Setting googletag PPID:', ppid );
			googletag.pubads().setPublisherProvidedId( ppid );
		}

		// Set page-level targeting.
		if ( maiPubAdsVars.targets ) {
			Object.keys( maiPubAdsVars.targets ).forEach( key => {
				googletag.pubads().setTargeting( key, maiPubAdsVars.targets[key].toString() );
			});

			// Log the page-level targeting that was set.
			maiPubLog( 'Set page-level targeting from:', maiPubAdsVars.targets );
		}
	});

	/**
	 * 2. Observe the BTF ad units.
	 */
	googletag.cmd.push(() => {
		// Get all the ad units.
		const adUnits = document.querySelectorAll( '.mai-ad-unit' );

		// Observe each ad unit.
		adUnits.forEach( adUnit => {
			// Get slotId.
			const slotId = adUnit.getAttribute( 'id' );

			// Add the slot to the slotManager.
			slotManager[ slotId ] = {
				processing: false,
				visible: null,
				lastRefreshTime: 0,
				firstRender: true,
			};

			// Observe the ad unit.
			observer.observe( adUnit );
		});
	});

	/**
	 * 5. Add event listeners to handle refreshable slots.
	 */
	googletag.cmd.push(() => {
		/**
		 * Update the slot manager when a slot is rendered.
		 */
		googletag.pubads().addEventListener( 'slotRenderEnded', (event) => {
			const slot = event.slot;

			// Bail if not a Mai Publisher slot.
			if ( ! maiPubIsMaiSlot( slot ) ) {
				// Log.
				maiPubLog( `Slot ${slot.getSlotElementId()} is not a Mai Publisher slot, via slotRenderEnded` );
				return;
			}

			// Get the slot ID and slug.
			const slotId = slot.getSlotElementId();
			const slug   = slotId.replace( 'mai-ad-', '' );

			// Update the last refresh time and mark processing as complete.
			slotManager[ slotId ].lastRefreshTime = Date.now();
			slotManager[ slotId ].processing      = false;

			// Log if the slot is empty.
			if ( event.isEmpty ) {
				maiPubLog( `Slot empty: ${slotId}`, {
					slug: slug,
					adUnitPath: slot.getAdUnitPath(),
					sizes: event.size,
					targeting: slot.getTargetingMap(),
					event: event,
				});
			}
			// Log if the slot is not empty.
			else {
				maiPubLog( `Slot filled: ${slotId}`, {
					slug: slug,
					adUnitPath: slot.getAdUnitPath(),
					sizes: event.size,
					targeting: slot.getTargetingMap(),
					event: event,
				});
			}

			// Bail if not refreshable.
			if ( ! maiPubIsRefreshable( slot ) ) {
				return;
			}

			// Set timeout to potentially request the slot later.
			timeoutManager[ slotId ] = setTimeout( () => {
				maiPubMaybeRequestSlots( [ slug ] );
			}, refreshTime );
		});

		/**
		 * Update the slot manager when a slot's visibility changes.
		 * This event is fired whenever the on-screen percentage of an ad slot's area changes.
		 * The event is throttled and will not fire more often than once every 200ms.
		 *
		 * @link https://developers.google.com/publisher-tag/reference#googletag.events.SlotVisibilityChangedEvent
		 */
		googletag.pubads().addEventListener( 'slotVisibilityChanged', (event) => {
			const slot = event.slot;

			// Bail if not a Mai Publisher slot.
			if ( ! maiPubIsMaiSlot( slot ) ) {
				maiPubLog( `Slot ${slot.getSlotElementId()} is not a Mai Publisher slot, via slotVisibilityChanged` );
				return;
			}

			// Bail if not refreshable.
			if ( ! maiPubIsRefreshable( slot ) ) {
				return;
			}

			// Get the slot ID, slug, and check if it's in view.
			const slotId = slot.getSlotElementId();
			const slug   = slotId.replace( 'mai-ad-', '' );
			const inView = event.inViewPercentage > 5;

			// Update the slot manager.
			slotManager[ slotId ].visible = inView;

			// If the slot is visible, maybe request the slot.
			if ( inView ) {
				maiPubMaybeRequestSlots( [ slug ] );
			}
		});

		// // If debugging, set listeners to log.
		// if ( debug || log ) {
		// 	// Log when a slot is requested/fetched.
		// 	googletag.pubads().addEventListener( 'slotRequested', (event) => {
		// 		maiPubLog( 'slotRequested:', event.slot, event );
		// 	});

		// 	// Log when a slot was loaded.
		// 	googletag.pubads().addEventListener( 'slotOnload', (event) => {
		// 		maiPubLog( 'slotOnload:', event.slot, event );
		// 	});

		// 	// Log when a slot response is received.
		// 	googletag.pubads().addEventListener( 'slotResponseReceived', (event) => {
		// 		maiPubLog( 'slotResponseReceived:', event.slot, event );
		// 	});

		// 	// Log when slot render has ended, regardless of whether ad was empty or not.
		// 	googletag.pubads().addEventListener( 'slotRenderEnded', (event) => {
		// 		maiPubLog( 'slotRenderEnded:', event.slot, event );
		// 	});

		// 	// Log when a slot ID visibility changed.
		// 	googletag.pubads().addEventListener( 'slotVisibilityChanged', (event) => {
		// 		maiPubLog( 'changed:', event.slot.getSlotElementId(), `${event.inViewPercentage}%` );
		// 	});
		// }
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
	// Get base from context.
	const base = ads?.[slug]?.['context'] && 'client' === ads[slug]['context'] ? gamBaseClient : gamBase;

	// Get the slot ID.
	const slotId = 'mai-ad-' + slug;

	// Define slot ID (GAM Path).
	const gamId = base + ads[slug]['id'];

	// Define the slot and related operations within the command queue.
	googletag.cmd.push(() => {
		// Define ad slot. googletag.defineSlot( "/1234567/sports", [728, 90], "div-1" );
		const slot = googletag.defineSlot( gamId, ads[slug].sizes, slotId );

		// Get it running.
		slot.addService( googletag.pubads() );

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

		// Log.
		maiPubLog( `Defined slot: ${slotId} via ${gamId}`, {
			gamId: gamId,
			slot: slot,
			targets: slot.getTargetingMap(),
		} );

		// Add slot to our tracking arrays after it's defined.
		adGamIds.push( gamId );
		adSlots.push( slot );
	});
}

/**
 * Maybe request a slot.
 *
 * @param {array} slots The slots to request.
 * @param {array} slugs The slugs of the slots to potentially request.
 *
 * @return {void}
 */
function maiPubMaybeRequestSlots( slugs ) {
	// Queue the logic to ensure slots are defined and GAM is ready.
	googletag.cmd.push(() => {
		// Set timestamp.
		const now = Date.now();

		// Process slugs to get actual slots and filter.
		const slotsToRequest = slugs.map( slug => {
			// Find the corresponding slot object from the global array.
			return adSlots.find( s => 'mai-ad-' + slug === s.getSlotElementId() );
		}).filter( slot => {
			// Ensure slot exists (was found and defined).
			if ( ! slot ) {
				maiPubLog( `Warning: Slot object not found for slug ${slug} during request check.` );
				return false;
			}

			// Get the slot ID.
			const slotId = slot.getSlotElementId();

			// If first render, return true, force a request.
			if ( slotManager[ slotId ].firstRender ) {
				maiPubLog( `First request for ${slotId}` );
				return true;
			}

			// Bail if the slot is already being processed.
			if ( slotManager[ slotId ].processing ) {
				// maiPubLog( `Skipping request for ${slotId} - already being processed` );
				return false;
			}

			// Bail if the slot is not visible.
			if ( ! slotManager[ slotId ].visible ) {
				// maiPubLog( `Skipping request for ${slotId} - not visible` );

				// Clear the timeout.
				clearTimeout( timeoutManager[ slotId ] );
				delete timeoutManager[ slotId ];

				return false;
			}

			// If last refresh time.
			if ( slotManager[ slotId ].lastRefreshTime ) {
				// Bail if the slot has been refreshed too recently.
				if ( ( now - slotManager[ slotId ].lastRefreshTime ) < refreshTime ) {
					// maiPubLog( `Skipping request for ${slotId} - ${Math.round( ( now - slotManager[ slotId ].lastRefreshTime ) / 1000 )} seconds since the last refresh` );
					return false;
				}

				// Log.
				maiPubLog( `Requesting slot ${slotId} - ${Math.round( ( now - slotManager[ slotId ].lastRefreshTime ) / 1000 )} seconds since the last refresh` );
			}

			return true;
		} );

		// Bail if no slots to request after filtering.
		if ( ! slotsToRequest.length ) {
			return;
		}

		// Request the filtered slot objects.
		maiPubRequestSlots( slotsToRequest );
	});
}

/**
 * Request slots.
 * The requestManager logic take from Magnite docs.
 *
 * @link https://help.magnite.com/help/web-integration-guide#parallel-header-bidding-integrations
 *
 * @param {array} slots The slots to request.
 *
 * @return {void}
 */
function maiPubRequestSlots( slots ) {
	// Loop through the slots.
	slots.forEach( slot => {
		// Get the slot ID.
		const slotId = slot.getSlotElementId();

		// Mark the slot as being processed.
		slotManager[ slotId ].processing = true;

		// Clear any scheduled timeouts before requesting.
		clearTimeout( timeoutManager[ slotId ] );
		delete timeoutManager[ slotId];
	});

	// Log.
	maiPubLog( `Requesting slots: ${slots.map( slot => slot.getSlotElementId() ).join( ', ' )}` );

	// Object to manage each request state.
	const requestManager = {
		adserverRequestSent: false,
		prebidBidsReceived: ! maiPubAdsVars.magnite, // If Magnite is disabled, consider bids received.
		amazonBidsReceived: ! maiPubAdsVars.amazonUAM, // If Amazon is disabled, consider bids received.
	};

	/**
	 * Send request to ad-server.
	 *
	 * @link https://help.magnite.com/help/web-integration-guide#parallel-header-bidding-integrations
	 */
	const sendAdserverRequest = function() {
		// Bail if the request has already been sent.
		if ( requestManager.adserverRequestSent ) {
			// Log.
			maiPubLog( 'Adserver request already sent, skipping. State:', {
				prebidBidsReceived: requestManager.prebidBidsReceived,
				amazonBidsReceived: requestManager.amazonBidsReceived,
				slots: slots.map(slot => slot.getSlotElementId())
			});

			// Return.
			return;
		}

		// Queue the refresh operation.
		googletag.cmd.push(() => {
			// Set the request manager to true and refresh the slots.
			requestManager.adserverRequestSent = true;

			// Log.
			maiPubLog( `Sending adserver request: ${slots.map( slot => slot.getSlotElementId() ).join( ', ' )}` );

			// Refresh the slots.
			maiPubRefreshSlots( slots );
		});
	}

	// Handle Magnite/DM bids.
	if ( maiPubAdsVars.magnite ) {
		// Fetch bids from Magnite using Prebid.
		pbjs.que.push( function() {
			// Track request start time.
			const prebidStartTime = Date.now();

			// Request bids from Magnite/Prebid.
			pbjs.rp.requestBids( {
				gptSlotObjects: slots,
				timeout: bidderTimeout,
				// bidsBackHandler: function() { // It seems Magnite doesn't use this.
				callback: function( bids, timedOut, auctionId ) {
					// Queue all GAM-dependent operations.
					googletag.cmd.push(() => {
						// Set targeting
						pbjs.setTargetingForGPTAsync && pbjs.setTargetingForGPTAsync( slots.map( slot => slot.getSlotElementId() ) );

						// Set the request manager to true.
						requestManager.prebidBidsReceived = true;

						// Log timing information.
						const prebidResponseTime = Date.now() - prebidStartTime;
						maiPubLog( `Prebid response time: ${ prebidResponseTime }ms`, {
							bids: bids,
							timedOut: timedOut,
							auctionId: auctionId
						} );

						// If we have all bids, send the adserver request.
						if ( requestManager.amazonBidsReceived ) {
							maiPubLog( 'Sending adserver request via Prebid bids' );
							sendAdserverRequest();
						}
					});
				}
			});
		});
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
			const amazonStartTime = Date.now();
			apstag.fetchBids( amazonConfig, function( bids ) {
				// Queue all GAM-dependent operations.
				googletag.cmd.push(() => {
					// Log timing information.
					const amazonResponseTime = Date.now() - amazonStartTime;
					maiPubLog( `Amazon response time: ${ amazonResponseTime }ms`, bids );

					// Set apstag bids, then trigger the first request to GAM, regardless of whether we have bids from Amazon.
					apstag.setDisplayBids();

					// Set the request manager to true.
					requestManager.amazonBidsReceived = true;

					// If we have all bids, send the adserver request.
					if ( requestManager.prebidBidsReceived ) {
						maiPubLog( `Sending adserver request via amazon: ${ uadSlots.map( slot => slot.slotID.replace( 'mai-ad-', '' ) ).join( ', ' ) }`, uadSlots );
						sendAdserverRequest();
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
			});
		}
		// No UAD, but we have others.
		else {
			// Set the request manager to true.
			requestManager.amazonBidsReceived = true;

			// If we have all bids, send the adserver request.
			if ( requestManager.prebidBidsReceived ) {
				maiPubLog( `Sending adserver request without amazon slots to fetch: ${ slots.map( slot => slot.getSlotElementId() ).join( ', ' ) }`, slots );
				sendAdserverRequest();
			}
		}
	}

	// Standard GAM.
	if ( ! ( maiPubAdsVars.magnite && maiPubAdsVars.amazonUAM ) ) {
		maiPubLog( `Sending adserver request with GAM: ${ slots.map( slot => slot.getSlotElementId() ).join( ', ' ) }`, slots );
		sendAdserverRequest();
	}

	// Start the failsafe timeout.
	setTimeout(() => {
		// Bail if already sent.
		if ( requestManager.adserverRequestSent ) {
			return;
		}

		// Log.
		maiPubLog( 'refresh() with failsafe timeout. Debug data:', {
			adserverRequestSent: requestManager.adserverRequestSent,
			dmBidsReceived: requestManager.dmBidsReceived,
			apsBidsReceived: requestManager.apsBidsReceived,
			timing: {
				totalTime: Date.now() - timestamp,
				bidderTimeout: bidderTimeout,
				fallbackTimeout: fallbackTimeout
			}
		} );

		// Send request.
		sendAdserverRequest();

	}, fallbackTimeout );
}

/**
 * Refreshes slots.
 *
 * @param {array} slots The defined slots.
 *
 * @return {void}
 */
function maiPubRefreshSlots( slots ) {
	// Queue the refresh operation.
	googletag.cmd.push(() => {
		// Update firstRender flag.
		slots.forEach( slot => {
			const slotId = slot.getSlotElementId();
			slotManager[ slotId ].firstRender = false;
		});

		// Log.
		maiPubLog( `Displaying/refreshing ${slots.length} ${1 === slots.length ? 'slot' : 'slots'} via refresh(): ${slots.map( slot => slot.getSlotElementId() ).join( ', ' )}` );

		// Refresh the slots.
		googletag.pubads().refresh( slots, { changeCorrelator: false } );
	});
}

/**
 * Check if a slot is a defined mai ad slot.
 * Checks if the ad slot ID is in our array of ad slot IDs.

 * Used by GAM event listeners to determine if an event pertains to a slot
 * managed by this script, by comparing the event slot's Ad Unit Path.
 *
 * @param {object} slot The ad slot.
 *
 * @return {boolean} True if a mai ad slot.
 */
function maiPubIsMaiSlot( slot ) {
	// Check if the slot object exists and if its GAM Ad Unit Path is in our tracked array.
	return slot && adGamIds.includes( slot.getAdUnitPath() );
}

/**
 * Check if a slot has targetting set to refresh.
 *
 * @param {object} slot The ad slot.
 *
 * @return {boolean} True if refreshable.
 */
function maiPubIsRefreshable( slot ) {
	return slot && Boolean( slot.getTargeting( refreshKey ).shift() );
}

/**
 * Generate a GAM-compliant PPID from a single string identifier.
 * JS equivalent of PHP maipub_generate_ppid function in functions-utility.php
 * except this generates a random PPID if no identifier is provided
 * and it also checks for session storage if cookie is not available.
 *
 * @param {string} identifier The identifier (Matomo Visitor ID or user email).
 *
 * @return {Promise<string|null>} A GAM-compliant PPID (64-character hexadecimal) or null if generation fails.
 */
async function maiPubGeneratePpid( identifier = '' ) {
	// If we're already generating a PPID, wait for it to complete.
	if ( isGeneratingPpid ) {
		return new Promise( ( resolve ) => {
			// Wait for current generation to complete.
			const check = setInterval(() => {
				if ( ! isGeneratingPpid ) {
					clearInterval( check );
					resolve( ppid );
				}
			}, 100);
		});
	}

	// Set the flag.
	isGeneratingPpid = true;

	try {
		// Convert input to string to handle unexpected types (e.g., null, number).
		// Ensures compatibility with TextEncoder, which requires a string.
		let input = String( identifier || '' );

		// If we don't have an identifier.
		if ( ! input ) {
			// Check if crypto is available
			if ( ! window.crypto || ! window.crypto.subtle ) {
				throw new Error( 'Web Crypto API not available' );
			}

			// Generate a UUID v4 as a unique fallback identifier.
			// UUID ensures high uniqueness (collision risk ~1 in 2^128) for anonymous users.
			input = typeof crypto.randomUUID === 'function'
				? crypto.randomUUID() // e.g., '123e4567-e89b-12d3-a456-426614174000'.
				: Array.from( crypto.getRandomValues( new Uint8Array( 16 ) ) )
					.map( b => b.toString( 16 ).padStart( 2, '0' ) )
					.join( '' ); // Fallback: 32-char random hex string.
		}

		// Encode the input string to a Uint8Array (UTF-8 bytes) as an ArrayBuffer.
		// Web Crypto API's digest method requires an ArrayBuffer input for hashing.
		const msgBuffer = new TextEncoder().encode( input );

		// Compute SHA-256 hash of the encoded input.
		// SHA-256 produces a 32-byte (256-bit) hash, ensuring the output is cryptographically secure
		// and meaningless to Google, meeting GAM's encryption requirement.
		const hashBuffer = await crypto.subtle.digest( 'SHA-256', msgBuffer );

		// Convert the hash ArrayBuffer to an array of bytes (0–255).
		// Allows iteration over the binary data to transform it into a string format.
		const hashArray = Array.from( new Uint8Array( hashBuffer ) );

		// Convert each byte to a two-character hexadecimal string (0–9, a–f).
		// - toString(16) converts a byte to hex (e.g., 94 -> '5e').
		// - padStart(2, '0') ensures single-digit hex values (e.g., 0x0) become '00'.
		// - join('') combines into a 64-character string (32 bytes × 2 chars/byte).
		// Hexadecimal ensures the output is alphanumeric, meeting GAM's format requirement.
		// (no need for URL encoding) and producing a 64-character string within 22–150 chars.
		const finalPpid = hashArray.map( b => b.toString( 16 ).padStart( 2, '0' ) ).join( '' );

		return finalPpid;

	} catch ( error ) {
		// Catch any errors (e.g., invalid input, Web Crypto API issues) to prevent
		// unhandled promise rejections that could break ad scripts (e.g., GAM, Prebid).
		// Log the error for debugging without disrupting execution.
		maiPubLog( 'Error transforming ppid:', error );

		// Return null to allow calling code to skip PPID usage safely.
		// GAM ignores null PPIDs, processing ad requests without them, avoiding errors.
		return null;

	} finally {
		isGeneratingPpid = false;
	}
}

/**
 * Get the consent from cookie or local storage.
 *
 * @return {boolean} The consent from cookie or local storage.
 */
function maiPubGetLocalConsent() {
	// Set cached consent variable.
	let scopedConsent = false;

	// Check for existing consent in cookie.
	scopedConsent = document.cookie.match( /(?:^|;)\s*maipub_consent=([^;]*)(?:;|$)/ );
	scopedConsent = scopedConsent && scopedConsent[1] ? scopedConsent[1] : false;

	// If no cookie consent, check local storage.
	if ( ! scopedConsent ) {
		scopedConsent = localStorage.getItem( 'maipub_consent' );
	}

	return Boolean( scopedConsent );
}

/**
 * Get the PPID from cookie or local storage.
 *
 * @return {string} The PPID from cookie or local storage, or an empty string if not found.
 */
function maiPubGetLocalPpid() {
	const cookiePpid  = maiPubGetCookiePpid();
	const storagePpid = localStorage.getItem( 'maipub_ppid' );

	// Return cookie PPID if we have one, otherwise try localStorage
	return cookiePpid || storagePpid || '';
}

/**
 * Get the PPID from cookie.
 *
 * @return {string} The PPID from cookie, or an empty string if not found.
 */
function maiPubGetCookiePpid() {
	const cookieMatch = document.cookie.match( /(?:^|;)\s*maipub_ppid=([^;]*)(?:;|$)/ );

	return cookieMatch?.[1] || '';
}

/**
 * Store the consent. In cookie and local storage always
 * since if we don't have consent we can store false in cookie.
 *
 * @param {boolean} consent The consent to set.
 *
 * @return {void}
 */
function maiPubSetLocalConsent( consent ) {
	// Log.
	maiPubLog( `Storing consent in cookie and session storage: ${ consent }` );

	// Store the consent in a cookie for persistence.
	document.cookie = `maipub_consent=${ consent };path=/;max-age=31104000;SameSite=Lax;Secure`;

	// Store in local storage also, for fallback.
	localStorage.setItem( 'maipub_consent', consent );
}

/**
 * Store the PPID. In cookie with consent, and local storage always.
 *
 * @param {string} ppid The PPID to set.
 *
 * @return {void}
 */
function maiPubSetLocalPpid( ppid ) {
	// Store the PPID in a cookie for persistence if generated from UUID.
	// Ensures consistent PPID across sessions for anonymous users, improving ad targeting.
	// 12-month expiration.
	if ( consent ) {
		maiPubLog( `Storing PPID in cookie: ${ ppid }` );
		document.cookie = `maipub_ppid=${ ppid };path=/;max-age=31104000;SameSite=Lax;Secure`;
	} else if ( getCookiePpid() ) {
		maiPubLog( 'No consent, removing PPID from cookie' );
		// If consent is removed and we have a PPID cookie, delete it.
		document.cookie = 'maipub_ppid=;path=/;expires=Thu, 01 Jan 1970 00:00:00 GMT;SameSite=Lax;Secure';
	}

	// Store in local storage also, for fallback.
	maiPubLog( `Storing PPID in session storage: ${ ppid }` );
	localStorage.setItem( 'maipub_ppid', ppid );
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