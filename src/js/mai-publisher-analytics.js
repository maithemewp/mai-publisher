/**
 * Run Matomo instance.
 *
 * @since 0.1.0
 */
(function() {
	var   _paq             = window._paq = window._paq || [];
	var   analytics        = maiPubAnalyticsVars.analytics;
	var   analyticsPrimary = analytics[0];
	var   analyticsMore    = analytics.slice(1);
	const debug            = window.location.search.includes('dfpdeb') || window.location.search.includes('maideb') || window.location.search.includes('pbjs_debug=true');
	const log              = 'undefined' !== typeof maiPubAdsVars ? maiPubAdsVars.debug : false;

	/**
	 * Sets up trackers.
	 */
	(function() {
		_paq.push( [ 'setTrackerUrl', analyticsPrimary.url + 'matomo.php' ] );
		_paq.push( [ 'setSiteId', analyticsPrimary.id ] );

		for ( const key in analyticsMore ) {
			_paq.push( [ 'addTracker', analyticsMore[ key ].url + 'matomo.php', analyticsMore[ key ].id ] );
		}

		var d = document,
			g = d.createElement( 'script' ),
			s = d.getElementsByTagName( 'script' )[0];

		g.async = true;
		g.src   = analyticsPrimary.url + 'matomo.js';
		s.parentNode.insertBefore( g, s );
	})();

	/**
	 * Handles all trackers asyncronously.
	 */
	window.matomoAsyncInit = function() {
		// Dispatch custom event before processing analytics.
		const event = new CustomEvent( 'beforeMaiPublisherAnalytics', {
			detail: maiPubAnalyticsVars,
			cancelable: true
		});
		document.dispatchEvent( event );

		// Continue with existing tracker code
		for ( const tracker in analytics ) {
			try {
				const matomoTracker = Matomo.getTracker( analytics[ tracker ].url + 'matomo.php', analytics[ tracker ].id );

				// Loop through and push items.
				for ( const key in analytics[ tracker ].toPush ) {

					var func = analytics[ tracker ].toPush[ key ][0];
					var vals = analytics[ tracker ].toPush[ key ].slice(1);
					    vals = vals ? vals : null;

					if ( vals ) {
						matomoTracker[func]( ...vals );
					} else {
						matomoTracker[func]();
					}
				}

				// Dispatch custom event after processing analytics.
				const trackerEvent = new CustomEvent( 'maiPublisherAnalyticsInit', {
					detail: {
						tracker: matomoTracker,
					},
					cancelable: true
				});
				document.dispatchEvent(trackerEvent);

				// If we have an ajax url and body, update the views.
				if ( analytics[ tracker ].ajaxUrl && analytics[ tracker ].body ) {
					// Send ajax request.
					fetch( analytics[ tracker ].ajaxUrl, {
						method: "POST",
						credentials: 'same-origin',
						headers: {
							'Content-Type': 'application/x-www-form-urlencoded',
							'Cache-Control': 'no-cache',
						},
						body: new URLSearchParams( analytics[ tracker ].body ),
					})
					.then(function( response ) {
						if ( ! response.ok ) {
							throw new Error( response.statusText );
						}

						return response.json();
					})
					.then(function( data ) {
					})
					.catch(function( error ) {
						maiPubAnalyticsLog( error );
					});
				}

			} catch( error ) {
				maiPubAnalyticsLog( error );
			}
		}
	};

	/**
	 * Log if debugging.
	 *
	 * @param {mixed} mixed The data to log.
	 *
	 * @return {void}
	 */
	function maiPubAnalyticsLog( ...mixed ) {
		if ( ! ( debug || log ) ) {
			return;
		}

		// Set log variables.
		let timer = 'maipub analytics ';

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
})();
