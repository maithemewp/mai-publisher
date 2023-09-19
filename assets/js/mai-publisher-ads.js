window.googletag = window.googletag || {};
googletag.cmd    = googletag.cmd || [];

if ( window.googletag && googletag.apiReady ) {
	const ads           = maiPubVars['ads'];
	const refreshKey    = 'refresh';
	const refreshvalue  = 'true';
	const prebidTimeout = 2000;

	googletag.cmd.push(() => {

		// Loop through maiPubVars getting key and values.
		for ( const id in ads ) {
			// Define ad slot.
			const slot = googletag
				.defineSlot( '/22487526518/' + maiPubVars['gamDomain'] + '/' + id, ads[id].sizes, 'mai-ad-' + id )
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
	});

	/**
	 * Amazon UAD.
	 * Debug via `apstag.debug('enableConsole')`
	 */
	// !function(a9,a,p,s,t,A,g){if(a[a9])return;function q(c,r){a[a9]._Q.push([c,r])}a[a9]={init:function(){q("i",arguments)},fetchBids:function(){q("f",arguments)},setDisplayBids:function(){},targetingKeys:function(){return[]},_Q:[]};A=p.createElement(s);A.async=!0;A.src=t;g=p.getElementsByTagName(s)[0];g.parentNode.insertBefore(A,g)}("apstag",window,document,"script","//c.amazon-adsystem.com/aax2/apstag.js");

	/********************************************
	 * Standard auction with apstag.            *
	 ********************************************/

	// // Initialize apstag and have apstag set bids on the googletag slots when they are returned to the page.
	// apstag.init({
	// 	pubID: '79166f25-5776-4c3e-9537-abad9a584b43', // BB.
	// 	adServer: 'googletag',
	// 	// bidTimeout: prebidTimeout,
	// 	// us_privacy: '-1', // https://ams.amazon.com/webpublisher/uam/docs/web-integration-documentation/integration-guide/uam-ccpa.html?source=menu
	// });

	// const uadSlots = [];

	// for ( const id in ads ) {
	// 	uadSlots.push({
	// 		slotID: 'mai-ad-' + id,
	// 		slotName: '/22487526518/' + maiPubVars['gamDomain'] + '/' + id,
	// 		sizes: ads[id].sizes,
	// 	});
	// }

	// // Request the bids for the four googletag slots.
	// apstag.fetchBids({
	// 	slots: uadSlots,
	// 	timeout: prebidTimeout,
	// }, function( bids ) {
	// 	// Set apstag bids, then trigger the first request to GAM.
	// 	googletag.cmd.push(function() {
	// 		apstag.setDisplayBids();
	// 		googletag.pubads().refresh();
	// 	});
	// });

	/********************************************
	 * End standard auction.                    *
	 ********************************************/

	/********************************************
	 * Parallel auction with prebid.            *
	 ********************************************/

	// apstag.init({
	// 	pubID: '79166f25-5776-4c3e-9537-abad9a584b43', // BB.
	// 	adServer: 'googletag',
	// });

	// const FAILSAFE_TIMEOUT = 1000;
	// const requestManager = {
	// 	adserverRequestSent: false,
	// 	aps: false,
	// 	prebid: false
	// };

	// // when both APS and Prebid have returned, initiate ad request
	// function biddersBack() {
	// 	if (requestManager.aps && requestManager.prebid) {
	// 		sendAdserverRequest();
	// 	}
	// 	return;
	// }

	// // sends adserver request
	// function sendAdserverRequest() {
	// 	if (requestManager.adserverRequestSent === true) {
	// 		return;
	// 	}
	// 	requestManager.adserverRequestSent = true;
	// 	googletag.cmd.push(function() {
	// 		googletag.pubads().refresh();
	// 	});
	// }

	// // sends bid request to APS and Prebid
	// function requestHeaderBids() {
	// 	const uadSlots = [];

	// 	for ( const id in ads ) {
	// 		uadSlots.push({
	// 			slotID: 'mai-ad-' + id,
	// 			slotName: '/22487526518/' + maiPubVars['gamDomain'] + '/' + id,
	// 			sizes: ads[id].sizes,
	// 		});
	// 	}

	// 	console.log( uadSlots );

	// 	// APS request
	// 	apstag.fetchBids({
	// 			slots: uadSlots,
	// 		}, function(bids) {
	// 			// console.log(bids);
	// 			googletag.cmd.push(function() {
	// 				apstag.setDisplayBids();
	// 				requestManager.aps = true; // signals that APS request has completed
	// 				biddersBack(); // checks whether both APS and Prebid have returned
	// 			});
	// 		}
	// 	);

		// var pbjs = pbjs || {};
		// pbjs.que = pbjs.que || [];

		// // put prebid request here
		// pbjs.que.push(function() {
		// 	pbjs.requestBids({
		// 		bidsBackHandler: function() {
		// 			googletag.cmd.push(function() {
		// 				pbjs.setTargetingForGPTAsync();
		// 				requestManager.prebid = true; // signals that Prebid request has completed
		// 				biddersBack(); // checks whether both APS and Prebid have returned
		// 			})
		// 		}
		// 	});
		// });
	// }

	// // initiate bid request
	// requestHeaderBids();

	// // set failsafe timeout
	// window.setTimeout(function() {
	// 	sendAdserverRequest();
	// }, FAILSAFE_TIMEOUT );

	/********************************************
	 * End parallel auction.                    *
	 ********************************************/

	/********************************************
	 * Parallel auction with prebid & sovrn.    *
	 ********************************************/

	const adUnits = [];

	for ( const id in ads ) {
		adUnits.push({
			code: 'mai-ad-' + id,
			mediaTypes: {
				banner: {
					sizes: ads[id].sizes
				}
			},
			bids: [
				{
					bidder: 'sovrn',
					params: {
						tagid: '1166957', // sovrn_tmspn_header_970x250 (Multi-size)
					}
				},
				{
					bidder: 'sovrn',
					params: {
						tagid: '1166958', // sovrn_tmspn_header_970x250 (Single-size)
					}
				},
			]
		});
	}

	const pbjs = pbjs || {};
	pbjs.que   = pbjs.que || [];

	pbjs.que.push(function() {
		pbjs.setConfig({
			timeout: prebidTimeout,
		});
		pbjs.addAdUnits(adUnits);
		pbjs.requestBids({
			bidsBackHandler: function() {
				googletag.cmd.push(function() {
					pbjs.que.push(function() {
						pbjs.setTargetingForGPTAsync();
						googletag.pubads().refresh();
					});
				});
			}
		});
	});

	/********************************************
	 * End parallel auction.                    *
	 ********************************************/

	/**
	 * Refresh ads only when they are in view and after expiration of refreshSeconds.
	 */
	// googletag.pubads().addEventListener( 'impressionViewable', function( event ) {
	// 	const slot = event.slot;

	// 	if ( slot.getTargeting( refreshKey ).indexOf( refreshvalue ) >= 0 ) {
	// 		setTimeout( function() {
	// 			googletag.pubads().refresh([slot]);
	// 		}, 30 * 1000 ); // 30 seconds.
	// 	}
	// });
}