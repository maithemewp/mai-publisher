/**
 * Run Matomo instance.
 *
 * @since 0.1.0
 */
(function() {
	var _paq = window._paq = window._paq || [];

	// if ( maiPubAnalyticsVars.enabledSite ) {
		// Sets user ID as user email.
		if ( maiPubAnalyticsVars.userId ) {
			_paq.push( [ 'setUserId', maiPubAnalyticsVars.userId ] );
		}

		// Adds all custom dimensions passed through PHP. Must be before trackPageView.
		for ( const key in maiPubAnalyticsVars.dimSite ) {
			_paq.push( [ 'setCustomDimension', key, maiPubAnalyticsVars.dimSite[ key ] ] );
		}

		_paq.push( [ 'enableLinkTracking' ] );
		_paq.push( [ 'trackVisibleContentImpressions' ] );
		// _paq.push( [ 'trackAllContentImpressions' ] );
		_paq.push( [ 'trackPageView' ] );
	// }


	window.matomoAsyncInit = function() {
		try {
			const matomoTracker = Matomo.getTracker( 'https://bizbudding.info/', 1 );

			console.log( matomoTracker );

			// Adds all custom dimensions passed through PHP. Must be before trackPageView.
			for ( const key in maiPubAnalyticsVars.dimGlobal ) {
				matomoTracker.setCustomDimension( key, maiPubAnalyticsVars.dimGlobal[ key ] );
			}

			// matomoTracker.enableLinkTracking();
			// matomoTracker.setDocumentTitle( document.title );
			matomoTracker.trackPageView();

		} catch( err ) {
			console.log( err );
		}
	};

	(function() {
		var u = maiPubAnalyticsVars.trackerUrl;

		_paq.push( [ 'setTrackerUrl', u + 'matomo.php' ] );
		_paq.push( [ 'setSiteId', maiPubAnalyticsVars.siteId ] );

		// console.log( Matomo.getAsyncTracker( 'https://bizbudding.info/', '1' ) );
		// _paq.push( [ 'addTracker', secondaryTracker, secondaryWebsiteId ] );
		_paq.push( [ 'addTracker', 'https://bizbudding.info/', '1' ] );

		var d = document,
			g = d.createElement( 'script' ),
			s = d.getElementsByTagName( 'script' )[0];

		g.async = true;
		g.src   = u + 'matomo.js';
		s.parentNode.insertBefore( g, s );
	})();

	// If we have the data we need, update the views.
	if ( maiPubAnalyticsVars.enabledSite
		&& maiPubAnalyticsVars.ajaxUrl
		&& maiPubAnalyticsVars.nonce
		&& maiPubAnalyticsVars.type
		&& maiPubAnalyticsVars.id
		&& maiPubAnalyticsVars.url
		&& maiPubAnalyticsVars.current ) {

		// Send ajax request.
		fetch( maiPubAnalyticsVars.ajaxUrl, {
			method: "POST",
			credentials: 'same-origin',
			headers: {
				'Content-Type': 'application/x-www-form-urlencoded',
				'Cache-Control': 'no-cache',
			},
			body: new URLSearchParams(
				{
					action: 'maipub_views',
					nonce: maiPubAnalyticsVars.nonce,
					type: maiPubAnalyticsVars.type,
					id: maiPubAnalyticsVars.id,
					url: maiPubAnalyticsVars.url,
					current: maiPubAnalyticsVars.current,
				}
			),
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
})();

// (function() {
// 	var _paq    = window._paq = window._paq || [];
// 	var siteUrl = 'https://bizbudding.info/';
// 	var siteID  = '1';

// 	// Only run if Matomo is not already loaded. Mostly for Mai Analytics.
// 	if ( ! _paq.length ) {
// 		(function() {
// 			_paq.push( ['setTrackerUrl', siteUrl + 'matomo.php'] );
// 			_paq.push( ['setSiteId', siteID] );

// 			var d=document, g=d.createElement('script'), s=d.getElementsByTagName('script')[0];
// 			g.type='text/javascript'; g.async=true; g.src=siteUrl+'matomo.js'; s.parentNode.insertBefore(g,s);
// 		})();
// 	}


// 	window.matomoAsyncInit = function() {
// 		try {
// 			const matomoTracker = Matomo.getTracker( siteUrl, siteID );

// 			// Adds all custom dimensions passed through PHP. Must be before trackPageView.
// 			for ( const key in maiPubAnalyticsVars.dimensions ) {
// 				matomoTracker.setCustomDimension( key, maiPubAnalyticsVars.dimensions[ key ] );
// 			}

// 			// matomoTracker.enableLinkTracking();
// 			matomoTracker.setDocumentTitle( document.title );
// 			matomoTracker.trackPageView();

// 		} catch( err ) {
// 			console.log( err );
// 		}
// 	};
// })();