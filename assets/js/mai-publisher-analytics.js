/**
 * Run Matomo instance.
 *
 * @since 0.1.0
 */
(function() {
	var _paq    = window._paq = window._paq || [];
	var siteUrl = 'https://bizbudding.info/';
	var siteID  = '1';

	// Only run if Matomo is not already loaded. Mostly for Mai Analytics.
	if ( ! _paq.length ) {
		(function() {
			_paq.push( ['setTrackerUrl', siteUrl + 'matomo.php'] );
			_paq.push( ['setSiteId', siteID] );

			var d=document, g=d.createElement('script'), s=d.getElementsByTagName('script')[0];
			g.type='text/javascript'; g.async=true; g.src=siteUrl+'matomo.js'; s.parentNode.insertBefore(g,s);
		})();
	}


	window.matomoAsyncInit = function() {
		try {
			const matomoTracker = Matomo.getTracker( siteUrl, siteID );

			// Adds all custom dimensions passed through PHP. Must be before trackPageView.
			for ( const key in maiPubAnalyticsVars.dimensions ) {
				matomoTracker.setCustomDimension( key, maiPubAnalyticsVars.dimensions[ key ] );
			}

			// matomoTracker.enableLinkTracking();
			matomoTracker.setDocumentTitle( document.title );
			matomoTracker.trackPageView();

		} catch( err ) {
			console.log( err );
		}
	};
})();