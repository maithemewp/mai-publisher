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

				console.log( analytics[ tracker ].url );

				// Loop through and push items.
				for ( const key in analytics[ tracker ].push ) {
					var func = analytics[ tracker ].push[ key ][0];
					var vals = analytics[ tracker ].push[ key ].slice(1);
					    vals = vals ? vals : null;

					console.log( ...vals );

					if ( vals ) {
						matomoTracker[func]( ...vals );
					} else {
						matomoTracker[func]();
					}
				}
			} catch( err ) {
				console.log( err );
			}
		}
	};
})();
