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
let   maiPubVersion    = 'v236.44';


// If debugging, log.
maiPubLog( `maiPubVersion: ${ maiPubVersion }` );


// Check if we're in debug mode and using minimized version
// TODO: We should check if we are in debug mode and if we are, we should not load the development version.
// The build process should be updated to include the debug version in the build.
/*
if (debug && document.urrentScript.src.includes('.min.js')) {
	// Get the non-minimized version path
	const devScript = document.currentScript.src.replace('.min.js', '.js');
	
	// Create and load the development version
	const script = document.createElement('script');
	script.src = devScript;
	script.async = true;
	
	// Remove the minimized version
	document.currentScript.remove();
	
	// Add the development version
	document.head.appendChild(script);
	
	// Log the switch and stop execution
	maiPubLog(`Switching to development version: ${ devScript }`);
}
*/


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

	maiPubLog( `AmazonUAM enabled for domain, ready to init: ${ maiPubAdsVars.amazonUAM }` );


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
	maiPubLog( `AmazonUAM apstag.init() called, apstag object contents:`, {
		_Q: apstag._Q,
		init: apstag.init,
		fetchBids: apstag.fetchBids,
		setDisplayBids: apstag.setDisplayBids,
		targetingKeys: apstag.targetingKeys
	});	
} else {
	maiPubLog( `AmazonUAM disabled for domain, skipping init` );
}

/**
 * Configure Prebid.js if Magnite is enabled.
 */
if ( maiPubAdsVars.magnite ) {

	maiPubLog( `MagniteDM enabled for domain, ready to init: ${ maiPubAdsVars.magnite }` );

	// Force integers.
	maiPubAdsVars.ortb2.mobile         = parseInt( maiPubAdsVars.ortb2.mobile );
	maiPubAdsVars.ortb2.privacypolicy  = parseInt( maiPubAdsVars.ortb2.privacypolicy );
	maiPubAdsVars.ortb2.cattax         = parseInt( maiPubAdsVars.ortb2.cattax );
	maiPubAdsVars.ortb2.content.cattax = parseInt( maiPubAdsVars.ortb2.content.cattax );


	// dws122: this was firig before the pbjs was loaded, so we need to wait for it to load.
	// Wait for Prebid.js to load
	const waitForPrebid = setInterval(() => {
		if (typeof pbjs !== 'undefined' && typeof pbjs.que !== 'undefined') {
			clearInterval(waitForPrebid);
			maiPubLog( `Prebid.js for MagniteDM loaded, proceeding with configuration` );

			maiPubLog( `About to configure Prebid.js, checking if pbjs exists:`, typeof pbjs );
			maiPubLog( `About to configure Prebid.js, checking if pbjs.que exists:`, typeof pbjs.que );
		

			pbjs.que.push( function() {
				try {
					// Start the config.
					maiPubLog( `pbjs.que.push: starting Prebid.js configuration using MagniteDM` );
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
						maiPubLog( `Adding ppid to Prebid.js configuration: ${ppid}` );
					}
					else {
						maiPubLog( `No ppid to add to Prebid.js configuration, skipping` );
					}

					// Set the magnite config.
					pbjs.setConfig( pbjsConfig );
					maiPubLog( `Prebid.js configuration set, pbjsConfig:`, pbjsConfig );
				} catch (error) {
					maiPubLog('Error in Prebid.js configuration:', error);
				}
			});
		}

	});
	maiPubLog( `Prebid.js for MagniteDM undefined, waiting for it to load` );

} else {
	maiPubLog( `MagniteDM disabled for domain, skipping init` );
}

/**
 * Handle CMP initialization.
 */

if ( 'function' === typeof __tcfapi ) {
	// Set timeout to proceed with initialization if CMP never responds.
	maiPubLog( `CMP: set timeout to proceed with initialization if CMP never responds` );
	const cmpTimeoutId = setTimeout(() => {
		if ( ! cmpReady ) {
			maiPubLog( 'CMP: timeout, proceeding with initialization' );
			cmpReady = true;
			maiPubMaybeInit();
		}
	}, cmpTimeout );

	try {
		// Add event listener for CMP events.
		maiPubLog( `CMP: adding event listener for CMP events` );
		__tcfapi( 'addEventListener', 2, ( tcData, success ) => {
			if ( cmpReady ) {
				return;
			}
			
			// If we have loaded or completed.
			if ( tcData && ( tcData.eventStatus === 'tcloaded' || tcData.eventStatus === 'useractioncomplete' ) ) {
				cmpReady = true;
				consent  = Boolean( success );
				clearTimeout( cmpTimeoutId );
				maiPubLog( `CMP: loaded, proceeding with initialization`, { 
					tcData: tcData 
				});
				
				// TODO: This would be a great place to add a check to see if we have Google Basic Consent.
				// Check if tcData is valid
				if ( typeof __tcfapi === 'function' ) {
					// Check for Google basic consent and eConsent
					__tcfapi('getTCData', 2, (data, success) => {
						if ( success && data.vendor && data.vendor.consents ) {
							// Check Google's vendor ID (755)
							const hasGoogleConsent = data.vendor.consents[755];
							maiPubLog( `CMP: Google basic consent status:`, hasGoogleConsent );

							// Check for eConsent (purpose 1)
							const hasEConsent = data.purpose && data.purpose.consents && data.purpose.consents[1];
							maiPubLog( `CMP: eConsent status:`, hasEConsent );
						} else {
							maiPubLog( `CMP: Unable to determine Google basic consent and eConsent status` );
						}
					});
				} else {
					maiPubLog( `CMP: __tcfapi not available for Google basic consent and eConsent check` );
				}

				maiPubMaybeInit();
			}
			else {
				maiPubLog( `CMP: event not good status: ${ tcData.eventStatus }`, tcData );
			}
		});
	} catch ( error ) {
		maiPubLog( 'CMP: event listener error:', error );
		clearTimeout( cmpTimeoutId );
		cmpReady = true;
		maiPubMaybeInit();
	}
} else {
	maiPubLog( `CMP not present, marking as ready` );
	cmpReady = true;
	maiPubMaybeInit();
}

/**
 * Handle PPID and Matomo initialization.
 */
if ( maiPubAdsVars.matomo.enabled && maiPubAdsVars.shouldTrack ) {
	maiPubLog( `Matomo: enabled and should track` );
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
	// Matomo is disabled or should not track, or both.
	matomoReady = true;

	if ( ! maiPubAdsVars.matomo.enabled ) {
		maiPubLog( 'Matomo was set as disabled' );
	} 
	if ( ! maiPubAdsVars.shouldTrack ) {
		maiPubLog( 'Matomo was set as should not track' );
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
	maiPubLog( `maiPubMaybeInit(): checking if we should initialize GAM, based on CMP and Matomo states` );
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
		maiPubLog( `maiPubMaybeInit(): GAM not initialized, waiting for ${waitingFor.join( ' and ' )}` );

		// Bail, not initializing yet.
		return;
	}
	maiPubLog( `maiPubMaybeInit(): looks like we should initialize GAM` );

	// If still no ppid.
	if ( ! ppid ) {
		// Generate a random PPID.
		maiPubGeneratePpid().then( transformedPpid => {
			ppid = transformedPpid;
			maiPubLog( `maiPubMaybeInit(): still No PPID available, generated random PPID: ${ppid}` );
		}).catch( error => {
			maiPubLog( `maiPubMaybeInit(): error generating random PPID: ${error}` );
		});

	}

	maiPubLog( `maiPubMaybeInit(): initializing GAM with ppid: ${ppid}. Now calling maiPubInit()` );
	maiPubInit();
}

/**
 * Push the Google Tag to the queue.
 *
 * @return {void}
 */
function maiPubInit() {
	// If consent is different from the local consent, store it.
	if ( consent !== serverConsent || consent !== localConsent ) {
		maiPubLog( `maiPubInit(): Consent different from local consent, storing consent` );
		maiPubSetLocalConsent( consent );
	}

	// If ppid is different from the local ppid, store it.
	if ( ppid && ( ppid !== serverPpid || ! localPpid || ppid !== localPpid ) ) {
		maiPubLog( `maiPubInit(): PPID different from local ppid, storing ppid` );
		maiPubSetLocalPpid( ppid );
	}
/* dws122 comment out segments for now
	// If we have segments.
	if ( maiPubAdsVars.dcSeg ) {
		maiPubLog( `maiPubInit(): We have segments, building PCD script` );
		// Build the PCD script.
		const pcdScript             = document.createElement( 'script' );
				pcdScript.async     = true;
				pcdScript.id        = 'google-pcd-tag';
				pcdScript.className = 'mai-pcd-tag';
				pcdScript.src       = 'https://pagead2.googlesyndication.com/pagead/js/pcd.js';

		// Build the segments.
		// TODO:  Not sure if this is correct.  It appears to be building the segments string 
		// from the dcSeg array and seg, when viewing in console this is empty...
		let segments = '';
		maiPubAdsVars.dcSeg.forEach( seg => {
			segments += `dc_seg=${seg};`;
		});
		maiPubLog( `maiPubInit(): We have segments, building the segments string: ${segments}` );

		// Build the audience pixel.
		let audiencePixel = `dc_iu=/${maiPubAdsVars.bbNetworkCode}/DFPAudiencePixel;${segments}gd=${maiPubAdsVars.domainHashed}`;
		maiPubLog( `maiPubInit(): We have segments, building the audience pixel: ${audiencePixel}` );

		// If we have a ppid, add it.
		if ( ppid ) {
			audiencePixel += `;ppid=${ppid}`;
			maiPubLog( `maiPubInit(): We have segments, and a ppid, adding it to the audience pixel: ${audiencePixel}` );
		}

		// Set the audience pixel.
		pcdScript.setAttribute( 'data-audience-pixel', audiencePixel );

		// Insert before the current script.
		// document.currentScript.parentNode.insertBefore( pcdScript, document.currentScript );

		// Insert at the end of the head.
		document.head.appendChild( pcdScript );
	}
	else {
		maiPubLog( `maiPubInit(): No segments, skipping PCD script` );
	}
*/

	// If no delay, run on DOMContentLoaded.
	if ( ! maiPubAdsVars.loadDelay ) {
		// Check if DOMContentLoaded has run.
		if ( 'loading' === document.readyState ) {
			// If it's still loading, wait for the event.
			maiPubLog( `maiPubInit(): No delay, but waiting for DOMContentLoaded` );
			document.addEventListener( 'DOMContentLoaded', maiPubRun, { once: true } );
		} else {
			// If it's already loaded, execute maiPubRun().
			maiPubLog( `maiPubInit(): No delay, DOMContentLoaded has run, running maiPubRun` );
			maiPubRun();
		}
	}
	// Delayed on window load.
	else {
		maiPubLog( `maiPubInit(): Delayed on window load, setting up event listener ${maiPubAdsVars.loadDelay}ms` );
		// On window load.
		window.addEventListener( 'load', () => {
			maiPubLog( `maiPubInit(): Delayed on window load, setting up event listener ${maiPubAdsVars.loadDelay}ms, running maiPubRun` );
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

		maiPubLog( `maiPubRun() entering the observer,entries:`, entries );
		// Loop through the entries.
		entries.forEach( entry => {
			// Get slug.
			const slotId = entry.target.getAttribute( 'id' );
			const slug   = slotId.replace( 'mai-ad-', '' );

			// If intersecting, then we are going to classify the slot as ATF.
			if ( entry.isIntersecting ) {
				// Set the slot to visible (using slotManager)
				maiPubLog( `maiPubRun(): Intersecting, setting slot to visible: ${slotId}` );
				slotManager[ slotId ].visible = true;

				// GPT is not initialized.
				// then we define and display the in view slots,
				// enable services,
				// refresh the slots,
				// then gptInitialized = true;
				if ( ! gptInitialized ) {
					maiPubLog( `maiPubRun(): GPT is not initialized, adding slug to ATFSlugs: ${slug}` );	
					ATFSlugs.push( slug );
				}
				else {
					// GPT is initialized, so we are going to classify the slot as BTF.
					// dws122: why is this happening?
					maiPubLog( `maiPubRun(): GPT is initialized, adding slug to BTFSlugs: ${slug}` );
					BTFSlugs.push( slug );
				}

				// If ?maideb or ?dfpdeb is on via querry parameter, then add inline styling.
				if ( debug ) {
					// Add inline styling.
					entry.target.style.outline = '2px dashed red';

					// Add data-label attribute of slug.
					maiPubLog( `maiPubRun(): Intersecting, debug set, adding data-label attribute of slug: ${slug}` );
					entry.target.setAttribute( 'data-label', slug );
				}

				// Add the slug to the slugsToRequest array.
				// slugsToRequest.push( slug );

				// Unobserve the displayed slots, let GAM events handle refreshing and visibility.
				maiPubLog( `maiPubRun(): Intersecting, unobserving slot to let GAM events handle refreshing and visibility: ${slotId}` );
				observer.unobserve( entry.target );

			}
			// Not intersecting.
			else {
				maiPubLog( `maiPubRun(): Not intersecting, setting slot to not visible: ${slotId}` );
				slotManager[ slotId ].visible = false;
			}
		});

		// If there are ATF slugs.
		if ( ATFSlugs.length ) {
			maiPubLog( `maiPubRun(): There are ATF slugs, defining and displaying them` );
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
					maiPubLog( `maiPubRun(): Defining the ATF slot: ${slug}` );

					maiPubDefineSlot( slug );

					// Display.
					maiPubLog( `maiPubRun(): Displaying the ATF slot: 'mai-ad-'${slug}` );
					googletag.display( 'mai-ad-' + slug );
				});

				// Enable services so ATF ads can be refreshed.
				googletag.enableServices();

				// Set GPT initialized to true.
				gptInitialized = true;

				// Maybe request the slots.
				maiPubLog( `maiPubRun(): calling maiPubMaybeRequestSlots() for the ATF slots: ${ATFSlugs.join( ', ' )}` );
				maiPubMaybeRequestSlots( ATFSlugs );
			});
		}

		// If there are BTF slugs to define, define them.
		if ( BTFSlugs.length ) {
			maiPubLog( `maiPubRun(): There are BTF slugs, defining and displaying them` );
			/**
			 * Define and display the BTF slots.
			 * Set GPT initialized to true.
			 * Maybe request the slots.
			 */
			googletag.cmd.push(() => {
				// Loop through the BTF slugs.
				BTFSlugs.forEach( slug => {
					// Define the slot.
					maiPubLog( `maiPubRun(): Defining the BTF slot: ${slug}` );

					maiPubDefineSlot( slug );

					// Display the slot.
					maiPubLog( `maiPubRun(): Displaying the BTF slot: 'mai-ad-'${slug}` );
					googletag.display( 'mai-ad-' + slug );
				});

				// Maybe request the slots.
				maiPubLog( `maiPubRun(): calling maiPubMaybeRequestSlots() for the BTF slots: ${BTFSlugs.join( ', ' )}` );
				maiPubMaybeRequestSlots( BTFSlugs );
			});
		}
	}, {
		root: null, // Use the viewport as the root.
		rootMargin: '600px 0px 600px 0px', // Trigger when the top of the element is X away from each part of the viewport.
		threshold: 0 // No threshold needed.
	});
	maiPubLog( `maiPubRun(): Observer is set, root to null, rootMargin to 600px 0px 600px 0px, threshold to 0` );

	/**
	 * 1. Setup Google Tag.
	 */
	try {
		googletag.cmd.push(() => {

			maiPubLog( `googletag.cmd.push(): entering` );

			// Disabled for now: https://developers.google.com/publisher-tag/reference#googletag.PubAdsService_setForceSafeFrame
			// googletag.pubads().setForceSafeFrame( true );

			// Get the IAB categories, removing duplicates.
			// We can have duplicates IAB categories if the parent category and child category 
			// have the same IAB Marketing Category.
			const iabCats = [...new Set( [ maiPubAdsVars.iabGlobalCat, maiPubAdsVars.iabCat ].filter( cat => cat ) )];
			maiPubLog( `googletag.cmd.push(): Got the IAB categories ${iabCats}, removing duplicates: ${iabCats.join( ', ' )}` );

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
				maiPubLog( `googletag.cmd.push(): setConfig() for PPS IAB_CONTENT_2_2 IAB Categories:`, {
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
			maiPubLog( `googletag.cmd.push(): googletag.pubads() Disabling initial load for header bidding` );

			// Enable single request.
			googletag.pubads().enableSingleRequest();
			maiPubLog( `googletag.cmd.push(): googletag.pubads() Enabling single request` );

			// Make ads centered.
			maiPubLog( `googletag.cmd.push(): googletag.pubads() Making ads centered` );
			googletag.pubads().setCentering( true );

			// If we have a ppid, set it.
			if ( ppid ) {
				googletag.pubads().setPublisherProvidedId( ppid );
				maiPubLog( `googletag.cmd.push(): googletag.pubads() Setting PPID: ${ppid}` );
			}

			// Set page-level targeting.
			if ( maiPubAdsVars.targets ) {
				Object.keys( maiPubAdsVars.targets ).forEach( key => {
					googletag.pubads().setTargeting( key, maiPubAdsVars.targets[key].toString() );
					maiPubLog( `googletag.cmd.push(): googletag.pubads() page-level targeting for ${key}:`, googletag.pubads().getTargeting(key) );
				});
			}
		});
	} catch (error) {
		maiPubLog('Error in Google Tag setup:', error);
	}

	/**
	 * 2. Observe the BTF ad units.
	 */

// TODO:  Not sure the above comment is correct.  This section of code appears to be observing all the ad units
// and adding them to the slotManager.

	maiPubLog( `Ready to identify ad units, add to slot manager, and observe them` );
	try {
		googletag.cmd.push(() => {
			try {
				// Get all the ad units.
				const adUnits = document.querySelectorAll( '.mai-ad-unit' );
				maiPubLog( `googletag.cmd.push(): Found ${adUnits.length} ad units:`, Array.from(adUnits).map(unit => ({
					id: unit.id,
					className: unit.className
				})));

				// Observe each ad unit.
				adUnits.forEach( adUnit => {
					// Get slotId.
					const slotId = adUnit.getAttribute( 'id' );
					maiPubLog( `googletag.cmd.push(): Found ad unit: ${slotId}` );
					// Add the slot to the slotManager.
					slotManager[ slotId ] = {
						processing: false,
						visible: null,
						lastRefreshTime: 0,
						firstRender: true
					};
					maiPubLog( `googletag.cmd.push(): Added to slotManager: slotId=${slotId}, data=`, slotManager[slotId] );
					// Observe the ad unit.
					observer.observe( adUnit );
					maiPubLog( `googletag.cmd.push(): Observed the ad unit: ${adUnit.id}, classname: (${adUnit.className})` );
				});
				maiPubLog( 'googletag.cmd.push(): Successfully processed all ad units' );
			} catch (innerError) {
				maiPubLog( 'googletag.cmd.push(): Error processing ad units:', innerError );
			}
		});
	} catch (error) {
		maiPubLog( 'googletag.cmd.push(): Error in googletag.cmd.push:', error );
	}

	/**
	 * 5. Add event listeners to handle refreshable slots.
	 */
	maiPubLog( `Ready to add event listeners to handle refreshable slots` );
	try {
		googletag.cmd.push(() => {
			try {
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
						maiPubLog( `maiPubRun(): slot ${slotId} is not refreshable` );
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

				maiPubLog( 'googletag.cmd.push(): Successfully added event listeners' );
			} catch (innerError) {
				maiPubLog( 'googletag.cmd.push(): Error adding event listeners:', innerError );
			}
		});
	} catch (error) {
		maiPubLog( 'googletag.cmd.push(): Error in googletag.cmd.push for event listeners:', error );
	}
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
	maiPubLog( `maiPubDefineSlot(): base: ${base}` );

	// Get the slot ID.
	const slotDivId = 'mai-ad-' + slug;
	maiPubLog( `maiPubDefineSlot(): slotDivId: ${slotDivId}` );

	// Define slot ID (GAM Path).
	const gamId = base + ads[slug]['id'];
	maiPubLog( `maiPubDefineSlot(): GAM Ad Unit Path: ${gamId}` );


	// Define the slot and related operations within the command queue.
	try {
		googletag.cmd.push(() => {
			// Define ad slot. googletag.defineSlot( "/1234567/sports", [728, 90], "div-1" );


			// sizes is from config.php
			const slot = googletag.defineSlot( gamId, ads[slug].sizes, slotDivId );
			maiPubLog( `maiPubDefineSlot() googletag.cmd.push(): defineSlot: ${gamId}, sizes: ${ads[slug].sizes}, slotDivId: ${slotDivId}` );

			// Get it running.
			slot.addService( googletag.pubads() );
			maiPubLog( `maiPubDefineSlot() googletag.cmd.push(): addService` );

			// Set refresh targeting.
			slot.setTargeting( refreshKey, refreshValue );
			maiPubLog( `maiPubDefineSlot() googletag.cmd.push(): refreshKey: ${refreshKey}, refreshValue: ${refreshValue}` );
			
			// Set slot-level targeting.
			if ( ads[slug].targets ) {
				Object.keys( ads[slug].targets ).forEach( key => {
					slot.setTargeting( key, ads[slug].targets[key] );
					maiPubLog( `maiPubDefineSlot() googletag.cmd.push(): slot-level targeting: key ${key}, value: ${ads[slug].targets[key]}` );
				});
			}

			// Set split testing.
			if ( ads[slug].splitTest && 'rand' === ads[slug].splitTest ) {
				// Set 'st' to a value between 0-99.
				slot.setTargeting( 'st', Math.floor(Math.random() * 100) );
				maiPubLog( `maiPubDefineSlot() googletag.cmd.push(): splitTest: ${ads[slug].splitTest}, st: ${Math.floor(Math.random() * 100)}` );
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
			maiPubLog( `maiPubDefineSlot() googletag.cmd.push(): defineSizeMapping` );

			// Log.
			maiPubLog( `Defined slot: ${slotDivId} via ${gamId}`, {
				gamId: gamId,
				slot: slot,
				targets: slot.getTargetingMap(),
			} );

			// Add slot to our tracking arrays after it's defined.
			adGamIds.push( gamId );
			adSlots.push( slot );
		});
	} catch (error) {
		maiPubLog( 'Error in googletag.cmd.push for slot definition:', error );
	}
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
				maiPubLog( `maiPubMaybeRequestSlots(): warning: Slot object not found for slug ${slug} during request check.` );
				return false;
			}

			// Get the slot ID.
			const slotId = slot.getSlotElementId();

			// If first render, return true, force a request.
			if ( slotManager[ slotId ].firstRender ) {
				maiPubLog( `maiPubMaybeRequestSlots(): first request for ${slotId}` );
				return true;
			}

			// Bail if the slot is already being processed.
			if ( slotManager[ slotId ].processing ) {
				maiPubLog( `maiPubMaybeRequestSlots(): skipping request for ${slotId} - already being processed` );
				return false;
			}

			// Bail if the slot is not visible.
			if ( ! slotManager[ slotId ].visible ) {
				maiPubLog( `maiPubMaybeRequestSlots(): skipping request for ${slotId} - not visible` );

				// Clear the timeout.
				clearTimeout( timeoutManager[ slotId ] );
				delete timeoutManager[ slotId ];

				return false;
			}

			// If last refresh time.
			if ( slotManager[ slotId ].lastRefreshTime ) {
				// Bail if the slot has been refreshed too recently.
				if ( ( now - slotManager[ slotId ].lastRefreshTime ) < refreshTime ) {
					maiPubLog( `maiPubMaybeRequestSlots(): skipping request for ${slotId} - ${Math.round( ( now - slotManager[ slotId ].lastRefreshTime ) / 1000 )} seconds since the last refresh` );
					return false;
				}
				maiPubLog( `maiPubMaybeRequestSlots(): requesting slot ${slotId} - ${Math.round( ( now - slotManager[ slotId ].lastRefreshTime ) / 1000 )} seconds since the last refresh` );
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
		try {
			googletag.cmd.push(() => {
				// Set the request manager to true and refresh the slots.
				requestManager.adserverRequestSent = true;

				maiPubLog( `sendAdserverRequest(): requestManager() set to true to refresh slots: ${slots.map( slot => slot.getSlotElementId() ).join( ', ' )}` );

				// Queue the refresh operation.
				try {
					// Update firstRender flag.
					slots.forEach( slot => {
						const slotId = slot.getSlotElementId();
						slotManager[ slotId ].firstRender = false;
					});
			
					// Refresh the slots.
					try {
						googletag.pubads().refresh( slots, { changeCorrelator: false } );
						maiPubLog( `sendAdserverRequest(): googletag.pubads().refresh() called for slots ${slots.length} ${1 === slots.length ? 'slot' : 'slots'} via refresh(): ${slots.map( slot => slot.getSlotElementId() ).join( ', ' )}` );
					} catch (error) {
						maiPubLog( 'Error calling googletag.pubads().refresh():', error );
					}
				} catch (error) {
					maiPubLog( 'Error refreshing individual slots:', error );
				}
			});
		} catch (error) {
			maiPubLog( 'Error in queing refresh operation:', error );
		}
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
						maiPubLog( `sendAdserverRequest(): Prebid response time: ${ prebidResponseTime }ms`, {
							bids: bids,
							timedOut: timedOut,
							auctionId: auctionId
						} );

						// If we have all bids, send the adserver request.
						if ( requestManager.amazonBidsReceived ) {
							maiPubLog( 'sendAdserverRequest(): Sending adserver request via Prebid bids' );
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
			prebidBidsReceived: requestManager.prebidBidsReceived,
			amazonBidsReceived: requestManager.amazonBidsReceived,
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
	} else if ( maiPubGetCookiePpid() ) {
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

	// If not debugging or logging, bail.
	if ( ! debug && ! log ) {
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

	// Log to console with both timer and caller name.
	console.log( `${timer} ${now}`, ...mixed );
}