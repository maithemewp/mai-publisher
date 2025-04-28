// define global PBJS and GPT libraries.
window.pbjs      = window.pbjs || { que: [] };
window.googletag = window.googletag || {};
googletag.cmd    = googletag.cmd || [];

// Define global variables.
const ads                 = maiPubAdsVars['ads'];
const adSlotIds           = [];
const adSlots             = [];
const gamBase             = maiPubAdsVars.gamBase;
const gamBaseClient       = maiPubAdsVars.gamBaseClient;
const refreshKey          = 'refresh';
const refreshValue        = maiPubAdsVars.targets.refresh;
const refreshTime         = 32;                                                                                                                                            // Time in seconds. Google recommends 30, we add 2 seconds for safety.
const lastRefreshTimes    = {};
const currentlyVisible    = {};
const currentlyProcessing = {};
const timeoutIds          = {};
const cmpTimeout          = 2000;                                                                                                                                          // Fallback in case CMP never responds.
const matomoTimeout       = 2000;                                                                                                                                          // Fallback in case Matomo never loads.
const bidderTimeout       = 5000;
const fallbackTimeout     = 6000;                                                                                                                                          // Set global failsafe timeout ~1000ms after DM UI bidder timeout.
const debug               = window.location.search.includes('dfpdeb') || window.location.search.includes('maideb') || window.location.search.includes('pbjs_debug=true');
const log                 = maiPubAdsVars.debug;
const bidResponses        = { prebid: {}, amazon: {}, timeouts: [] };
const serverConsent       = Boolean( maiPubAdsVars.consent );
const serverPpid          = maiPubAdsVars.ppid;
const localConsent        = getLocalConsent();
const localPpid           = getLocalPpid();
let   timestamp           = Date.now();
let   consent             = serverConsent || localConsent;
let   ppid                = '';
let   isGeneratingPpid    = false;
let   cmpReady            = false;
let   matomoReady         = false;

// If debugging, log.
maiPubLog( 'v225' );

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

		// Add bid response tracking.
		pbjs.onEvent( 'bidResponse', function( bid ) {
			bidResponses.prebid[ bid.bidder ] = {
				value: bid.cpm,
				size: bid.size,
				adUnitCode: bid.adUnitCode,
				timeToRespond: bid.timeToRespond + 'ms'
			};
			maiPubLog( `Prebid bid received from ${ bid.bidder }`, bid );
		});

		// Add timeout monitoring.
		pbjs.onEvent( 'bidTimeout', function( timeoutBids ) {
			timeoutBids.forEach(bid => {
				bidResponses.timeouts.push({
					bidder: bid.bidder,
					adUnitCode: bid.adUnitCode,
					timeout: bidderTimeout + 'ms'
				});
			});
			maiPubLog( 'Bid timeout occurred:', timeoutBids );
		});

		// Log when the auction ends.
		pbjs.onEvent( 'auctionEnd', function( bids ) {
			maiPubLog( 'Prebid auction ended:', bids, {
				prebid: bidResponses.prebid,
				timeouts: bidResponses.timeouts,
				timing: {
					totalTime: Date.now() - timestamp + 'ms',
					bidderTimeout: bidderTimeout + 'ms',
					fallbackTimeout: fallbackTimeout + 'ms'
				}
			});
		});
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
			maybeInitGoogleTag();
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
				maybeInitGoogleTag();
			}
		});
	} catch ( error ) {
		maiPubLog( 'CMP error:', error );
		clearTimeout( cmpTimeoutId );
		cmpReady = true;
		maybeInitGoogleTag();
	}
} else {
	// No CMP present, mark as ready.
	cmpReady = true;
	maybeInitGoogleTag();
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
			maybeInitGoogleTag();
		}
	}, matomoTimeout );

	// If we already have a PPID, proceed without waiting for Matomo.
	if ( ppid ) {
		matomoReady = true;
		clearTimeout( matomoTimeoutId );
		maiPubLog( `Skipping Matomo initialization, using existing PPID: ${ppid}` );
		maybeInitGoogleTag();
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
					generatePpid( visitorId ).then( transformedPpid => {
						ppid        = transformedPpid;
						matomoReady = true;
						clearTimeout( matomoTimeoutId );
						maiPubLog( `Matomo already initialized, generated PPID from visitor ID: ${ppid}` );
						maybeInitGoogleTag();
					}).catch( error => {
						maiPubLog( 'Error generating PPID from Matomo visitor ID:', error );
						// Fallback to random PPID.
						generatePpid().then( transformedPpid => {
							ppid        = transformedPpid;
							matomoReady = true;
							clearTimeout( matomoTimeoutId );
							maiPubLog( `Matomo already initialized, generated random PPID after generation error: ${ppid}` );
							maybeInitGoogleTag();
						});
					});
				}
			}
		} catch (error) {
			maiPubLog( 'Error accessing Matomo:', error );
			// Fallback to random PPID.
			generatePpid().then( transformedPpid => {
				ppid        = transformedPpid;
				matomoReady = true;
				clearTimeout( matomoTimeoutId );
				maiPubLog( `Matomo already initialized, generated random PPID after catching error: ${ppid}` );
				maybeInitGoogleTag();
			});
		}
	}
	// No matomo and no ppid, wait for analytics init event
	else {
		maiPubLog( 'Matomo not initialized, waiting for analytics init event' );
		// Wait for analytics init event.
		document.addEventListener( 'maiPublisherAnalyticsInit', function( event ) {
			// Get and transform the visitor ID immediately.
			generatePpid( event.detail.tracker.getVisitorId() ).then( transformedPpid => {
				ppid        = transformedPpid;
				matomoReady = true;
				clearTimeout( matomoTimeoutId );
				maiPubLog( `Matomo async event fired, generated PPID from visitor ID: ${ppid}` );
				maybeInitGoogleTag();
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

	maybeInitGoogleTag();
}

/**
 * Check if we should initialize GAM.
 * We initialize when either:
 * 1. CMP is ready (or not present) AND Matomo is ready (or not enabled)
 * 2. We've hit our timeout for either system
 */
function maybeInitGoogleTag() {
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
		generatePpid().then( transformedPpid => {
			ppid = transformedPpid;
			maiPubLog( `Generated random PPID: ${ppid}` );
			maiPubLog( `Initializing GAM with ppid: ${ppid}` );
			initGoogleTag();
		});
	}
	// We have a ppid.
	else {
		maiPubLog( `Initializing GAM with ppid: ${ppid}` );
		initGoogleTag();
	}
}

/**
 * Push the Google Tag to the queue.
 *
 * @return {void}
 */
function initGoogleTag() {
	// If consent is different from the local consent, store it.
	if ( consent !== serverConsent || consent !== localConsent ) {
		setLocalConsent( consent );
	}

	// If ppid is different from the local ppid, store it.
	if ( ppid && ( ppid !== serverPpid || ! localPpid || ppid !== localPpid ) ) {
		setLocalPpid( ppid );
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

	// Push the Google Tag to the queue.
	googletag.cmd.push(() => {
		try {
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

			// If we have a ppid, set it.
			if ( ppid ) {
				maiPubLog( 'Setting googletag PPID:', ppid );
				googletag.pubads().setPublisherProvidedId( ppid );
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
			 * Clear the processing flag when a slot response is received.
			 */
			googletag.pubads().addEventListener( 'slotResponseReceived', function( event ) {
				const slotId = event.slot.getSlotElementId();

				// Bail if the slot is not being processed.
				if ( ! currentlyProcessing[ slotId ] ) {
					return;
				}

				// Clear the processing flag and update last refresh time.
				delete currentlyProcessing[ slotId ];
				lastRefreshTimes[ slotId ] = Date.now();
				maiPubLog( `Cleared processing flag for ${slotId} after slotResponseReceived` );
			});

			/**
			 * Clear the processing flag when a slot is rendered.
			 */
			googletag.pubads().addEventListener( 'slotRenderEnded', function( event ) {
				const slotId = event.slot.getSlotElementId();

				// Bail if the slot is not being processed.
				if ( ! currentlyProcessing[ slotId ] ) {
					return;
				}

				// Clear the processing flag and update last refresh time.
				delete currentlyProcessing[ slotId ];
				lastRefreshTimes[ slotId ] = Date.now();
				maiPubLog( `Cleared processing flag for ${slotId} after slotRenderEnded` );
			});

			/**
			 * Clear the processing flag when a slot errors.
			 */
			googletag.pubads().addEventListener( 'slotError', function( event ) {
				const slotId = event.slot.getSlotElementId();

				// Bail if the slot is not being processed.
				if ( ! currentlyProcessing[ slotId ] ) {
					return;
				}

				// Clear the processing flag and update last refresh time.
				delete currentlyProcessing[ slotId ];
				lastRefreshTimes[ slotId ] = Date.now();
				maiPubLog( `Cleared processing flag for ${slotId} after slotError` );
			});

			/**
			 * Handle the impressionViewable event.
			 * Set the slot as visible and handle display logic.
			 */
			googletag.pubads().addEventListener( 'impressionViewable', (event) => {
				const slot   = event.slot;
				const slotId = slot.getSlotElementId();

				// Bail if not a refreshable slot.
				if ( ! maiPubIsRefreshable( slot ) ) {
					return;
				}

				// Ad is visible.
				currentlyVisible[ slotId ] = true;
				maiPubLog( `Slot ${slotId} impression viewable` );

				// Handle display logic.
				maiPubMaybeDisplaySlot( slot, 'impressionViewable' );
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

				// Update visibility state.
				currentlyVisible[ slotId ] = inView;
				maiPubLog( `Slot ${slotId} visibility changed to ${inView ? 'visible' : 'invisible'} (${event.inViewPercentage}%)` );

				// If becoming visible, handle display logic.
				if ( inView ) {
					// Handle display logic.
					maiPubMaybeDisplaySlot( slot, 'slotVisibilityChanged' );
				}
				// If becoming invisible, clear any pending refresh timeout.
				else if ( timeoutIds[ slotId ] ) {
					clearTimeout( timeoutIds[ slotId ] );
					maiPubLog( `cleared refresh timeout for ${slotId} - no longer visible` );
				}
			});

			// /**
			//  * Checks if this is a client GAM ad and not the main plugin MCM ad,
			//  * if it's a client ad and isEmpty, try to load the main plugin ad.
			//  */
			// googletag.pubads().addEventListener( 'slotRenderEnded', (event) => {
			// 	// Bail if slot is not empty.
			// 	if ( ! event.isEmpty ) {
			// 		return;
			// 	}

			// 	// Bail if not one of our slots.
			// 	if ( ! maiPubIsMaiSlot( event.slot ) ) {
			// 		return;
			// 	}

			// 	// Get slug from slot ID.
			// 	const slotId = event.slot.getSlotElementId();
			// 	const slug   = slotId.replace( 'mai-ad-', '' );

			// 	// Bail if it's not a client ad.
			// 	if ( 'client' !== ads[slug]['context'] ) {
			// 		return;
			// 	}

			// 	// Bail if no backfill ad with a backfill id.
			// 	if ( ! ( ads?.[slug]?.['backfill'] && ads?.[slug]?.['backfillId'] ) ) {
			// 		return;
			// 	}

			// 	// // If debugging, log.
			// 	// maiPubLog( 'maipub backfilling with: ' + ads[slug]['backfill'], document.getElementById( slotId ).id );

			// 	// // Set the ID to the backfill ID and define/display the backfill ad.
			// 	// document.getElementById( slotId ).id = ads[slug]['backfillId'];

			// 	// // Define and display the main plugin ad.
			// 	// maiPubDisplaySlots( [ maiPubDefineSlot( ads[slug]['backfill'] ) ] );

			// 	// // If debugging, log.
			// 	// maiPubLog( 'maipub destroying: ' + slug );

			// 	// // Unset ads[slug].
			// 	// // delete ads[slug];

			// 	// // Destroy the empty slot.
			// 	// googletag.destroySlots( [ event.slot ] );
			// });

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
		} catch ( error ) {
			maiPubLog( 'Error initializing GAM:', error );
			// Potentially retry or fallback to simpler setup
		}
	});
}

/**
 * Request slots.
 * The requestManager logic take from Magnite docs.
 *
 * Flow:
 * 1. If force=true, skips timing check entirely.
 * 2. If force=false, filters slots based on timing check.
 * 3. Handles bid requests and refreshes eligible slots.
 *
 * @link https://help.magnite.com/help/web-integration-guide#parallel-header-bidding-integrations
 *
 * @param {array}   slotsToProcess The slots to process
 * @param {boolean} forceProcess   Whether to force refresh
 */
function maiPubRequestSlots( slotsToProcess, forceProcess ) {
	// Only filter if not forcing refresh.
	const slotsToRefresh = forceProcess ? slotsToProcess : slotsToProcess.filter( slot => {
		const { shouldRefresh, timeSinceLastRefresh } = maiPubShouldRefreshSlot( slot );
		if ( ! shouldRefresh ) {
			maiPubLog( `Skipping refresh of ${slot.getSlotElementId()}, refreshed ${Math.floor(timeSinceLastRefresh/1000)}s ago` );
			return false;
		}
		const timeSinceLastRefreshText = Infinity === timeSinceLastRefresh ? 'First refresh' : `${Math.floor(timeSinceLastRefresh/1000)}s since last refresh`;
		maiPubLog( `Processing ${slot.getSlotElementId()}, ${timeSinceLastRefreshText}` );
		return true;
	});

	// If no slots to refresh, bail.
	if ( ! slotsToRefresh.length ) {
		maiPubLog( 'No slots to refresh after filtering.' );
		return;
	}

	// Filter out slots that are already being processed.
	const slotsToRefreshNow = slotsToRefresh.filter( slot => {
		const slotId = slot.getSlotElementId();
		if ( currentlyProcessing[ slotId ] ) {
			maiPubLog( `Skipping ${slotId} - already being processed` );
			return false;
		}
		return true;
	});

	// If no slots to process after filtering, bail.
	if ( ! slotsToRefreshNow.length ) {
		maiPubLog( 'No slots to process after filtering out already processing slots' );
		return;
	}

	// Mark slots as being processed with a timestamp
	slotsToRefreshNow.forEach( slot => {
		const slotId = slot.getSlotElementId();
		currentlyProcessing[ slotId ] = Date.now();
		maiPubLog( `Set processing flag for ${slotId}` );
	});

	// Log slots being refreshed.
	maiPubLog( `Processing ${slotsToRefreshNow.length} slots:`, slotsToRefreshNow.map( slot => slot.getSlotElementId() ) );

	// Object to manage each request state.
	const requestManager = {
		adserverRequestSent: false,
		dmBidsReceived: ! maiPubAdsVars.magnite, // If Magnite is disabled, consider bids received.
		apsBidsReceived: ! maiPubAdsVars.amazonUAM, // If Amazon is disabled, consider bids received.
	};

	/**
	 * Send request to ad-server.
	 *
	 * @link https://help.magnite.com/help/web-integration-guide#parallel-header-bidding-integrations
	 */
	const sendAdserverRequest = function() {
		// Bail if the request has already been sent.
		if ( requestManager.adserverRequestSent ) {
			maiPubLog( 'Adserver request already sent, skipping. State:', {
				dmBidsReceived: requestManager.dmBidsReceived,
				apsBidsReceived: requestManager.apsBidsReceived,
				slots: slotsToRefreshNow.map(slot => slot.getSlotElementId())
			});
			return;
		}

		// Set the request manager to true and refresh the slots.
		requestManager.adserverRequestSent = true;
		maiPubLog( 'Sending adserver request for slots:', slotsToRefreshNow.map(slot => slot.getSlotElementId()));

		// Refresh the slots.
		maiPubRefreshSlots( slotsToRefreshNow );
	}

	// Handle Magnite/DM bids.
	if ( maiPubAdsVars.magnite ) {
		// Fetch bids from Magnite using Prebid.
		pbjs.que.push( function() {
			// Track request start time
			const prebidRequestStartTime = Date.now();

			// Request bids from Magnite/Prebid.
			pbjs.rp.requestBids( {
				gptSlotObjects: slotsToRefreshNow,
				timeout: bidderTimeout,
				bidsBackHandler: function() {
					// Set targeting.
					pbjs.setTargetingForGPTAsync && pbjs.setTargetingForGPTAsync( slotsToRefreshNow.map( slot => slot.getSlotElementId() ) );

					// Set the request manager to true.
					requestManager.dmBidsReceived = true;

					// Log timing information
					const prebidResponseTime = Date.now() - prebidRequestStartTime;
					maiPubLog( `Prebid response time: ${ prebidResponseTime }ms` );

					// Log.
					maiPubLog( 'Prebid bids received:', {
						prebid: bidResponses.prebid,
						timeouts: bidResponses.timeouts,
						timing: {
							totalTime: Date.now() - timestamp + 'ms',
							bidderTimeout: bidderTimeout + 'ms',
							fallbackTimeout: fallbackTimeout + 'ms',
							responseTime: prebidResponseTime + 'ms'
						}
					});

					// If we have all bids, send the adserver request.
					if ( requestManager.apsBidsReceived ) {
						maiPubLog( 'Sending adserver request after Prebid bids' );
						sendAdserverRequest();
					}
				}
			});
		});
	}

	// Handle Amazon UAM bids.
	if ( maiPubAdsVars.amazonUAM ) {
		// Filter out ads[slug].sizes that only contain a single size named 'fluid'. This was throwing an error in amazon.
		// Filter out client ads.
		const uadSlots = slotsToRefreshNow
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
			const uadRequestStartTime = Date.now();
			apstag.fetchBids( amazonConfig, function( bids ) {
				// Log timing information
				const amazonResponseTime = Date.now() - uadRequestStartTime;
				maiPubLog( `Amazon response time: ${ amazonResponseTime }ms` );

				// Track Amazon bids
				bids.forEach((bid) => {
					bidResponses.amazon[bid.slotID] = {
						value: bid.amznbid,
						size: bid.size,
						responseTime: amazonResponseTime + 'ms',
						error: bid.error || null
					};
				});

				// Log.
				if ( ! bids.length ) {
					maiPubLog( 'No Amazon bids received.' );
				} else {
					maiPubLog( 'Amazon bids received:', bidResponses.amazon );
				}

				// Set apstag bids, then trigger the first request to GAM.
				apstag.setDisplayBids();

				// Set the request manager to true.
				requestManager.apsBidsReceived = true;

				// If we have all bids, send the adserver request.
				if ( requestManager.dmBidsReceived ) {
					maiPubLog( `Sending adserver request with amazon fetch: ${ uadSlots.map( slot => slot.slotID.replace( 'mai-ad-', '' ) ).join( ', ' ) }`, uadSlots );
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
		}
		// No UAD, but we have others.
		else {
			// Set the request manager to true.
			requestManager.apsBidsReceived = true;

			// If we have all bids, send the adserver request.
			if ( requestManager.dmBidsReceived ) {
				maiPubLog( `Sending adserver request without amazon slots to fetch: ${ slotsToRefreshNow.map( slot => slot.getSlotElementId() ).join( ', ' ) }`, slotsToRefreshNow );
				sendAdserverRequest();
			}
		}
	}

	// Standard GAM.
	if ( ! maiPubAdsVars.magnite && ! maiPubAdsVars.amazonUAM ) {
		maiPubLog( `Sending adserver request with GAM: ${ slotsToRefreshNow.map( slot => slot.getSlotElementId() ).join( ', ' ) }`, slotsToRefreshNow );
		sendAdserverRequest();
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
 * Debounced version of maiPubRequestSlots.
 *
 * @param {array}   slots The slots to process.
 * @param {boolean} force Whether to force refresh.
 *
 * @return {void}
 */
function maiPubRequestSlotsDebounced( slots, force ) {
	return maiPubDebounce( maiPubRequestSlots, 100 )( slots, force );
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
async function generatePpid( identifier = '' ) {
	// If we're already generating a PPID, wait for it to complete.
	if ( isGeneratingPpid ) {
		return new Promise( ( resolve ) => {
			// Wait for current generation to complete
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
function getLocalConsent() {
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
function getLocalPpid() {
	const cookiePpid = getCookiePpid();
	const storagePpid = localStorage.getItem( 'maipub_ppid' );

	// Return cookie PPID if we have one, otherwise try localStorage
	return cookiePpid || storagePpid || '';
}

/**
 * Get the PPID from cookie.
 *
 * @return {string} The PPID from cookie, or an empty string if not found.
 */
function getCookiePpid() {
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
function setLocalConsent( consent ) {
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
function setLocalPpid( ppid ) {
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
 * Helper function to handle slot display logic.
 *
 * Flow:
 * 1. Checks if slot should refresh based on timing.
 * 2. If should refresh, calls maiPubDisplaySlots with force=true to bypass timing check.
 * 3. If not ready to refresh but visible, sets timeout to check again.
 *
 * @param {object} slot      The slot to display.
 * @param {string} eventName The event name for logging.
 *
 * @return {void}
 */
function maiPubMaybeDisplaySlot( slot, eventName ) {
	const slotId = slot.getSlotElementId();

	// If the slot is already being processed, skip this check.
	if ( currentlyProcessing[ slotId ] ) {
		maiPubLog( `Skipping refresh check for ${slotId} - already being processed` );
		return;
	}

	// Check if we should refresh now or set a timeout.
	const { shouldRefresh, timeUntilNextRefresh } = maiPubShouldRefreshSlot( slot );

	// If we should refresh, display the slot.
	if ( shouldRefresh ) {
		// Log.
		maiPubLog( `refreshed via ${ eventName }: ${ slotId }`, slot );

		// Use maiPubDisplaySlots with force=true to bypass timing check.
		// This prevents circular logic since force=true skips the timing check in maiPubDisplaySlots.
		maiPubDisplaySlots( [ slot ], true );
	}
	// Not ready to refresh yet and timeUntilNextRefresh is positive.
	else if ( currentlyVisible[ slotId ] && timeUntilNextRefresh > 0 ) {
		// Clear any existing timeout before setting a new one.
		if ( timeoutIds[ slotId ] ) {
			clearTimeout( timeoutIds[ slotId ] );
		}

		// Set timeout to check again in timeUntilNextRefresh.
		timeoutIds[ slotId ] = setTimeout(() => {
			// Bail if the slot is no longer visible.
			if ( ! currentlyVisible[ slotId ] ) {
				return;
			}
			// Log.
			maiPubLog( `checking refresh for ${slotId} after ${Math.floor(timeUntilNextRefresh/1000)}s timeout` );

			// Try to the slot.
			maiPubMaybeDisplaySlot( slot, 'timeout' );

		}, timeUntilNextRefresh );
	}
	// Not displaying and not seeing a timeout.
	else {
		maiPubLog( `not refreshing ${ slotId } because it's not visible or no valid timeUntilNextRefresh` );
	}
}

/**
 * Display slots.
 * The requestManager logic take from Magnite docs.
 *
 * Flow:
 * 1. If force=true, skips timing check entirely.
 * 2. If force=false, filters slots based on timing check.
 * 3. Handles bid requests and refreshes eligible slots.
 *
 * @link https://help.magnite.com/help/web-integration-guide#parallel-header-bidding-integrations
 *
 * @param {array}   slots An array of the defined slots objects.
 * @param {boolean} force Whether to force refresh without checking timing.
 *
 * @return {void}
 */
function maiPubDisplaySlots( slots, force = false ) {
	// Enable services.
	// This needs to run after defineSlot() but before display()/refresh().
	// If we did this in maiPubDefineSlot() it would run for every single slot, instead of batches.
	// NM, changed this when Magnites docs show it how we had it. Via: https://help.magnite.com/help/web-integration-guide
	// googletag.enableServices();

	// Log initial state.
	maiPubLog( `Display slots called with ${slots.length} slots, force=${force}` );

	// If force is true, process immediately without debounce
	if ( force ) {
		maiPubRequestSlots( slots, force );
		return;
	}

	// Use the debounced version.
	maiPubRequestSlotsDebounced( slots, force );
}

/**
 * Refreshes slots.
 * This should only be called from maiPubDisplaySlots after getting bids.
 *
 * @param {array} slots The defined slots.
 *
 * @return {void}
 */
function maiPubRefreshSlots( slots ) {
	// Log.
	maiPubLog( `Refreshing ${slots.length} slots:`, slots.map( slot => slot.getSlotElementId() ) );

	// Refresh the slots.
	googletag.pubads().refresh( slots, { changeCorrelator: false } );
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
 * Check if a slot should be refreshed based on timing.
 *
 * The refresh timing works in two parts:
 * 1. Visible Time Tracking:
 *    - Only counts time towards refresh when the slot is visible
 *    - When invisible, timeSinceLastRefresh is 0
 *    - This prevents refreshes of invisible slots
 *
 * 2. Timeout Scheduling:
 *    - Uses actual elapsed time since last refresh
 *    - Continues counting even when slot is invisible
 *    - Ensures timeouts fire at correct intervals
 *    - When timeout fires, checks visibility again
 *
 * @param {object} slot The slot to check.
 * @param {number} now  Optional current timestamp.
 *
 * @return {object} Object containing:
 *                 - shouldRefresh: boolean indicating if slot should be refreshed
 *                 - timeSinceLastRefresh: number of milliseconds since last refresh (only while visible)
 *                 - timeUntilNextRefresh: number of milliseconds until next refresh (based on actual elapsed time)
 */
function maiPubShouldRefreshSlot( slot, now = Date.now() ) {
	const slotId = slot.getSlotElementId();

	// Bail if the slot is already being processed.
	if ( currentlyProcessing[ slotId ] ) {
		maiPubLog( `Slot ${slotId} is already being processed, skipping refresh check` );
		return {
			shouldRefresh: false,
			timeSinceLastRefresh: 0,
			timeUntilNextRefresh: 0
		};
	}

	const lastRefresh = lastRefreshTimes[ slotId ];

	// If never refreshed, should refresh immediately.
	if ( ! lastRefresh ) {
		maiPubLog( `Slot ${slotId} never refreshed, should refresh immediately` );
		return {
			shouldRefresh: true,
			timeSinceLastRefresh: Infinity, // Forces immediate refresh.
			timeUntilNextRefresh: 0
		};
	}

	// Calculate the actual elapsed time since last refresh.
	// This continues counting even when slot is invisible.
	const elapsedTime = now - lastRefresh;

	// Only count time towards refresh when slot is visible.
	// When invisible, timeSinceLastRefresh is 0, preventing refreshes.
	const timeSinceLastRefresh = currentlyVisible[ slotId ] ? elapsedTime : 0;

	// Calculate when the next refresh should occur.
	// Based on actual elapsed time, not just visible time.
	const timeUntilNextRefresh = Math.max( 0, ( refreshTime * 1000 ) - elapsedTime );

	const result = {
		// Only refresh if we've accumulated enough visible time.
		shouldRefresh: timeSinceLastRefresh >= ( refreshTime * 1000 ),
		// How long the slot has been visible since last refresh.
		timeSinceLastRefresh,
		// How long until the next refresh check.
		timeUntilNextRefresh
	};

	maiPubLog( `Slot ${slotId} refresh check:`, {
		lastRefresh,
		now,
		elapsedTime,
		isVisible: currentlyVisible[ slotId ],
		timeSinceLastRefresh,
		requiredTime: refreshTime * 1000,
		shouldRefresh: result.shouldRefresh
	});

	return result;
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
 * Create a debounced version of a function.
 *
 * @param {Function} func The function to debounce.
 * @param {number}   wait The number of milliseconds to wait.
 *
 * @return {Function} The debounced function.
 */
function maiPubDebounce( func, wait ) {
	let timeout;
	return function(...args) {
		clearTimeout( timeout );
		timeout = setTimeout(() => func.apply( this, args ), wait );
	};
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

// Set up periodic check for stuck processing slots
setInterval(() => {
	const now = Date.now();
	const stuckSlots = Object.entries(currentlyProcessing)
		.filter(([slotId, startTime]) => {
			// If a slot has been processing for more than 30 seconds, consider it stuck
			return now - startTime > 30000;
		})
		.map(([slotId]) => slotId);

	if (stuckSlots.length) {
		maiPubLog(`WARNING: Found ${stuckSlots.length} stuck slots:`, stuckSlots);
		// Clear the stuck slots to prevent them from blocking future refreshes
		stuckSlots.forEach(slotId => {
			delete currentlyProcessing[slotId];
			maiPubLog(`Cleared stuck processing flag for ${slotId}`);
		});
	}
}, 30000); // Check every 30 seconds