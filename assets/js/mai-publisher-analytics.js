/**
 * Run Matomo instance.
 *
 * @since 0.1.0
 */
(function() {
	var _paq             = window._paq = window._paq || [];
	var analytics        = maiPubAnalyticsVars.analytics;
	var analyticsPrimary = analytics[0];
	var analyticsMore    = analytics.slice(1);

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

				// If we have an ajax url and body, pdate the views.
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
						console.log( 'Mai Publisher Views' );
						console.log( error.name, error.message );
					});
				}

			} catch( err ) {
				console.log( err );
			}
		}
	};
})();
