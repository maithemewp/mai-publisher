( function( $ ) {

	if ( 'object' !== typeof acf ) {
		return;
	}

	var postKeys = [ 'maigam_single_taxonomy' ];
	var taxoKeys = [ 'maigam_single_terms' ];

	/**
	 * Uses current post types or taxonomy for use in other field queries.
	 *
	 * @since 0.1.0
	 *
	 * @return object
	 */
	acf.addFilter( 'select2_ajax_data', function( data, args, $input, field, instance ) {
		if ( $input && postKeys.includes( data.field_key ) ) {

			var postField = acf.getFields(
				{
					key: 'maigam_single_types',
					parent: field.$el.parents( '.acf-row' ).parents( '.acf-row' ),
				}
			);

			if ( postField ) {
				var first = postField.shift();
				var value = first ? first.val() : '';
				data.post_type = value;
			}
		}

		if ( field && taxoKeys.includes( data.field_key ) ) {

			var taxoField = acf.getFields(
				{
					key: 'maigam_single_taxonomy',
					sibling: field.$el,
				}
			);

			if ( taxoField ) {
				var first = taxoField.shift();
				var value = first ? first.val() : '';
				data.taxonomy = value;
			}
		}

		return data;
	} );

} )( jQuery );
