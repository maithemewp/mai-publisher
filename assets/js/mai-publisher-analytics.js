/**
 * Run Matomo instance.
 *
 * @since 0.1.0
 */
(function() {
	window.matomoAsyncInit = function() {
		try {
			const matomoTracker = Matomo.getTracker( 'https://analytics.bizbudding.com/matomo.php', 23 );

			console.log( maiPubAnalyticsVars.dimensions );

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