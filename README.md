# Mai Publisher
Manage ads and more for websites in the Mai Publisher network

## How to check sidebars for ads.
Sidebars are not checked by default. You can manually and conditionally add sidebars to check on each page via the filter below:
```
/**
 * Dynamically add sidebar checks to Mai Publisher.
 *
 * @param $sidebar The existing sidebars.
 *
 * @return array
 */
add_filter( 'maipub_sidebars', function( $sidebars ) {
	if ( mai_has_sidebar() ) {
		if ( ! is_singular( 'my_cpt' ) ) {
			$sidebars[] = 'my_custom_sidebar';
		} else {
			$sidebars[] = 'sidebar';
		}
	}

	return $sidebars;
});
```