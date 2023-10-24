<?php

// Prevent direct file access.
defined( 'ABSPATH' ) || die;

/**
 * Gets the content age.
 * Index 0 is the min months, index 1 is the readable string label.
 *
 * @since TBD
 *
 * @return array
 */
function maipub_get_content_age() {
	$age = null;

	if ( ! is_null( $age ) ) {
		return $age;
	}

	$age = [];

	if ( ! is_singular() ) {
		return $age;
	}

	$date = get_the_date( 'F j, Y' );

	if ( ! $date ) {
		return $age;
	}

	$date   = new DateTime( $date );
	$today  = new DateTime( 'now' );
	$days   = $today->diff( $date )->format( '%a' );

	if ( ! $days ) {
		return $age;
	}

	// Ranges. Key is min months, value is min/max days.
	$ranges = [
		'0'  => [ 0, 29 ],      // Under 1 month.
		'1'  => [ 30, 89 ],     // 1-3 months.
		'3'  => [ 90, 179 ],    // 3-6 months.
		'6'  => [ 180, 364 ],   // 6-12 months.
		'12' => [ 367, 729 ],   // 1-2 years.
	];

	foreach ( $ranges as $key => $values ) {
		if ( ! filter_var( $days, FILTER_VALIDATE_INT,
			[
				'options' => [
					'min_range' => $values[0],
					'max_range' => $values[1],
				],
			],
		)) {
			continue;
		}

		switch ( $key ) {
			case '0':
				$age = [ $key, __( 'Under 1 month', 'mai-ads-manager' ) ];
			break;
			case '1':
				$age = [ $key, __( '1-3 months', 'mai-ads-manager' ) ];
			break;
			case '3':
				$age = [ $key, __( '3-6 months', 'mai-ads-manager' ) ];
			break;
			case '6':
				$age = [ $key, __( '6-12 months', 'mai-ads-manager' ) ];
			break;
			case '12':
				$age = [ $key, __( '1-2 years', 'mai-ads-manager' ) ];
			break;
		}
	}

	if ( ! $age && $days > 729 ) {
		$age = [ '24', __( 'Over 2 years', 'mai-ads-manager' ) ];
	}

	return $age;
}
