<?php

// Prevent direct file access.
defined( 'ABSPATH' ) || die;

return [
	'ad_units' => [
		'billboard' => [
			'sizes'         => [ [970, 250] ],
			'sizes_desktop' => [ [970, 250] ],
			'sizes_tablet'  => [ [1, 1] ],
			'sizes_mobile'  => [ [1, 1] ],
			'post_content'  => '<!-- wp:group {"align":"full","backgroundColor":"alt","layout":{"type":"constrained"},"contentWidth":"no","verticalSpacingTop":"sm","verticalSpacingBottom":"sm","verticalSpacingLeft":"xs","verticalSpacingRight":"xs","marginTop":"xl","marginBottom":"xl"} -->
			<div class="wp-block-group alignfull has-alt-background-color has-background"><!-- wp:acf/mai-ad-unit {"name":"acf/mai-ad-unit","data":{"maipub_ad_unit_id":"billboard"},"mode":"preview"} /--></div>
			<!-- /wp:group -->',
		],
		'button' => [
			'sizes'         => [ [125, 125], [120, 90], [120, 60] ],
			'sizes_desktop' => [ [125, 125], [120, 90], [120, 60] ],
			'sizes_tablet'  => [ [125, 125], [120, 90], [120, 60] ],
			'sizes_mobile'  => [ [125, 125], [120, 90], [120, 60] ],
			'post_content'  => '<!-- wp:group {"backgroundColor":"alt","layout":{"type":"constrained"},"contentWidth":"no","verticalSpacingTop":"xs","verticalSpacingBottom":"xs","verticalSpacingLeft":"xs","verticalSpacingRight":"xs","marginTop":"xl","marginBottom":"xl"} -->
			<div class="wp-block-group alignfull has-alt-background-color has-background"><!-- wp:acf/mai-ad-unit {"name":"acf/mai-ad-unit","data":{"maipub_ad_unit_id":"button"},"mode":"preview"} /--></div>
			<!-- /wp:group -->',
		],
		'footer' => [
			'sizes'         => [ [320, 50], [468, 60], [728, 90], [970, 90] ],
			'sizes_desktop' => [ [728, 90], [970, 90] ],
			'sizes_tablet'  => [ [320, 50], [468, 60] ],
			'sizes_mobile'  => [ [320, 50] ],
			'post_content'  => '<!-- wp:group {"align":"full","backgroundColor":"alt","layout":{"type":"constrained"},"contentWidth":"no","verticalSpacingTop":"sm","verticalSpacingBottom":"sm","verticalSpacingLeft":"xs","verticalSpacingRight":"xs"} -->
			<div class="wp-block-group alignfull has-alt-background-color has-background"><!-- wp:acf/mai-ad-unit {"name":"acf/mai-ad-unit","data":{"maipub_ad_unit_id":"footer"},"mode":"preview"} /--></div>
			<!-- /wp:group -->',
		],
		'fullscreen' => [
			'sizes'         => [ [320, 480], [468, 60], [480, 320], [768, 1024], [1024, 768] ],
			'sizes_desktop' => [ [768, 1024], [1024, 768] ],
			'sizes_tablet'  => [ [468, 60], [480, 320] ],
			'sizes_mobile'  => [ [320, 50] ],
			'post_content'  => '<!-- wp:acf/mai-ad-unit {"name":"acf/mai-ad-unit","data":{"maipub_ad_unit_id":"fullscreen"},"mode":"preview"} /-->',
		],
		'halfpage' => [
			'sizes'         => [ [300, 600] ],
			'sizes_desktop' => [ [300, 600] ],
			'sizes_tablet'  => [ [300, 600] ],
			'sizes_mobile'  => [ [300, 600] ],
			'post_content'  => '<!-- wp:group {"backgroundColor":"alt","layout":{"type":"constrained"},"contentWidth":"no","verticalSpacingTop":"sm","verticalSpacingBottom":"sm","verticalSpacingLeft":"xs","verticalSpacingRight":"xs","marginTop":"xl","marginBottom":"xl"} -->
			<div class="wp-block-group has-alt-background-color has-background"><!-- wp:acf/mai-ad-unit {"name":"acf/mai-ad-unit","data":{"id":"halfpage","_id":"maipub_ad_unit_id"},"mode":"preview"} /--></div>
			<!-- /wp:group -->',
		],
		'header' => [
			'sizes'         => [ [320, 50], [468, 60], [728, 90], [970, 90], [970, 250] ],
			'sizes_desktop' => [ [970, 250], [970, 90] ],
			'sizes_tablet'  => [ [728, 90], [468, 60] ],
			'sizes_mobile'  => [ [320, 50]],
			'post_content'  => '<!-- wp:group {"align":"full","backgroundColor":"alt","layout":{"type":"constrained"},"contentWidth":"no","verticalSpacingTop":"sm","verticalSpacingBottom":"sm","verticalSpacingLeft":"xs","verticalSpacingRight":"xs"} -->
			<div class="wp-block-group alignfull has-alt-background-color has-background"><!-- wp:acf/mai-ad-unit {"name":"acf/mai-ad-unit","data":{"maipub_ad_unit_id":"header"},"mode":"preview"} /--></div>
			<!-- /wp:group -->',
		],
		'incontent' => [
			'sizes'         => [ [300, 100], [300, 250], [336, 280], [750, 100], [750, 200], [750, 300], [970, 66], [970, 250] ],
			'sizes_desktop' => [ [970, 250], [750, 300], [750, 200], [750, 100], [970, 66] ],
			'sizes_tablet'  => [ [336, 280], [300, 250], [300, 100] ],
			'sizes_mobile'  => [ [336, 280], [300, 250], [300, 100] ],
			'post_content'  => '<!-- wp:group {"backgroundColor":"alt","layout":{"type":"constrained"},"contentWidth":"no","verticalSpacingTop":"xs","verticalSpacingBottom":"xs","verticalSpacingLeft":"xs","verticalSpacingRight":"xs","marginTop":"xl","marginBottom":"xl"} -->
			<div class="wp-block-group has-alt-background-color has-background"><!-- wp:acf/mai-ad-unit {"name":"acf/mai-ad-unit","data":{"id":"incontent","_id":"maipub_ad_unit_id"},"mode":"preview"} /--></div>
			<!-- /wp:group -->',
		],
		'infeed' => [
			'sizes'         => [ [240, 400], [300, 250], [300, 600] ],
			'sizes_desktop' => [ [300, 600], [300, 250], [240, 400] ],
			'sizes_tablet'  => [ [300, 600], [300, 250], [240, 400] ],
			'sizes_mobile'  => [ [300, 600], [300, 250], [240, 400] ],
			'post_content'  => '<!-- wp:group {"backgroundColor":"alt","layout":{"type":"constrained"},"contentWidth":"no","verticalSpacingTop":"xs","verticalSpacingBottom":"xs","verticalSpacingLeft":"xs","verticalSpacingRight":"xs","marginTop":"xl","marginBottom":"xl"} -->
			<div class="wp-block-group has-alt-background-color has-background"><!-- wp:acf/mai-ad-unit {"name":"acf/mai-ad-unit","data":{"maipub_ad_unit_id":"infeed"},"mode":"preview"} /--></div>
			<!-- /wp:group -->',
		],
		'inrecipe' => [
			'sizes'         => [ [200, 200], [250, 250], [300, 300], [400, 400] ],
			'sizes_desktop' => [ [400, 400], [300, 300], [250, 250], [200, 200] ],
			'sizes_tablet'  => [ [400, 400], [300, 300], [250, 250], [200, 200] ],
			'sizes_mobile'  => [ [300, 300], [250, 250], [200, 200]],
			'post_content'  => '<!-- wp:group {"backgroundColor":"alt","layout":{"type":"constrained"},"contentWidth":"no","verticalSpacingTop":"xs","verticalSpacingBottom":"xs","verticalSpacingLeft":"xs","verticalSpacingRight":"xs","marginTop":"xl","marginBottom":"xl"} -->
			<div class="wp-block-group has-alt-background-color has-background"><!-- wp:acf/mai-ad-unit {"name":"acf/mai-ad-unit","data":{"maipub_ad_unit_id":"inrecipe"},"mode":"preview"} /--></div>
			<!-- /wp:group -->',
		],
		'leaderboard' => [
			'sizes'         => [ [970, 90], [728, 90], [320, 50], [300, 50] ],
			'sizes_desktop' => [ [970, 90], [728, 90] ],
			'sizes_tablet'  => [ [728, 90] ],
			'sizes_mobile'  => [ [320, 50], [300, 50] ],
			'post_content'  => '<!-- wp:group {"align":"full","backgroundColor":"alt","layout":{"type":"constrained"},"contentWidth":"no","verticalSpacingTop":"sm","verticalSpacingBottom":"sm","verticalSpacingLeft":"xs","verticalSpacingRight":"xs","marginTop":"xl","marginBottom":"xl"} -->
			<div class="wp-block-group alignfull has-alt-background-color has-background"><!-- wp:acf/mai-ad-unit {"name":"acf/mai-ad-unit","data":{"maipub_ad_unit_id":"leaderboard"},"mode":"preview"} /--></div>
			<!-- /wp:group -->',
		],
		'medium-rectangle' => [
			'sizes'         => [ [300, 250] ],
			'sizes_desktop' => [ [300, 250] ],
			'sizes_tablet'  => [ [300, 250] ],
			'sizes_mobile'  => [ [300, 250] ],
			'post_content'  => '<!-- wp:group {"backgroundColor":"alt","layout":{"type":"constrained"},"contentWidth":"no","verticalSpacingTop":"xs","verticalSpacingBottom":"xs","verticalSpacingLeft":"xs","verticalSpacingRight":"xs","marginTop":"xl","marginBottom":"xl"} -->
			<div class="wp-block-group has-alt-background-color has-background"><!-- wp:acf/mai-ad-unit {"name":"acf/mai-ad-unit","data":{"maipub_ad_unit_id":"medium-rectangle"},"mode":"preview"} /--></div>
			<!-- /wp:group -->',
		],
		'micro-bar' => [
			'sizes'         => [ [88, 31] ],
			'sizes_desktop' => [ [88, 31] ],
			'sizes_tablet'  => [ [88, 31] ],
			'sizes_mobile'  => [ [88, 31] ],
			'post_content'  => '<!-- wp:group {"backgroundColor":"alt","layout":{"type":"constrained"},"contentWidth":"no","verticalSpacingTop":"xs","verticalSpacingBottom":"xs","verticalSpacingLeft":"xs","verticalSpacingRight":"xs","marginTop":"xl","marginBottom":"xl"} -->
			<div class="wp-block-group has-alt-background-color has-background"><!-- wp:acf/mai-ad-unit {"name":"acf/mai-ad-unit","data":{"maipub_ad_unit_id":"micro-bar"},"mode":"preview"} /--></div>
			<!-- /wp:group -->',
		],
		'podcast-footer' => [
			'sizes'         => [ [320, 50], [468, 60], [728, 90], [970, 90] ],
			'sizes_desktop' => [ [970, 90], [728, 90] ],
			'sizes_tablet'  => [ [728, 90], [468, 60] ],
			'sizes_mobile'  => [ [320, 50] ],
			'post_content'  => '<!-- wp:group {"align":"full","backgroundColor":"alt","layout":{"type":"constrained"},"contentWidth":"no","verticalSpacingTop":"xs","verticalSpacingBottom":"xs","verticalSpacingLeft":"xs","verticalSpacingRight":"xs","marginTop":"xl","marginBottom":"xl"} -->
			<div class="wp-block-group alignfull has-alt-background-color has-background"><!-- wp:acf/mai-ad-unit {"name":"acf/mai-ad-unit","data":{"maipub_ad_unit_id":"podcast-footer"},"mode":"preview"} /--></div>
			<!-- /wp:group -->',
		],
		'podcast-header' => [
			'sizes'         => [ [320, 50], [468, 60], [728, 90], [970, 90] ],
			'sizes_desktop' => [ [970, 90], [728, 90] ],
			'sizes_tablet'  => [ [728, 90], [468, 60] ],
			'sizes_mobile'  => [ [320, 50] ],
			'post_content'  => '<!-- wp:group {"align":"full","backgroundColor":"alt","layout":{"type":"constrained"},"contentWidth":"no","verticalSpacingTop":"xs","verticalSpacingBottom":"xs","verticalSpacingLeft":"xs","verticalSpacingRight":"xs"} -->
			<div class="wp-block-group alignfull has-alt-background-color has-background"><!-- wp:acf/mai-ad-unit {"name":"acf/mai-ad-unit","data":{"maipub_ad_unit_id":"incontent"},"mode":"preview"} /--></div>
			<!-- /wp:group -->',
		],
		'skyscraper' => [
			'sizes'         => [ [160, 600], [120, 600] ],
			'sizes_desktop' => [ [160, 600], [120, 600] ],
			'sizes_tablet'  => [ [160, 600], [120, 600] ],
			'sizes_mobile'  => [ [160, 600], [120, 600] ],
			'post_content'  => '<!-- wp:group {"backgroundColor":"alt","layout":{"type":"constrained"},"contentWidth":"no","verticalSpacingTop":"xs","verticalSpacingBottom":"xs","verticalSpacingLeft":"xs","verticalSpacingRight":"xs","marginTop":"xl","marginBottom":"xl"} -->
			<div class="wp-block-group has-alt-background-color has-background"><!-- wp:acf/mai-ad-unit {"name":"acf/mai-ad-unit","data":{"maipub_ad_unit_id":"skyscraper"},"mode":"preview"} /--></div>
			<!-- /wp:group -->',
		],
		'sponsored-sidebar' => [
			'sizes'         => [ [320, 50], [300, 600], [300, 250], [160, 600] ],
			'sizes_desktop' => [ [320, 50], [300, 600], [300, 250], [160, 600] ],
			'sizes_tablet'  => [ [320, 50], [300, 600], [300, 250], [160, 600] ],
			'sizes_mobile'  => [ [320, 50], [300, 600], [300, 250], [160, 600] ],
			'post_content'  => '<!-- wp:group {"backgroundColor":"alt","layout":{"type":"constrained"},"contentWidth":"no","verticalSpacingTop":"xs","verticalSpacingBottom":"xs","verticalSpacingLeft":"xs","verticalSpacingRight":"xs"} -->
			<div class="wp-block-group has-alt-background-color has-background"><!-- wp:acf/mai-ad-unit {"name":"acf/mai-ad-unit","data":{"id":"skyscraper","_id":"maipub_ad_unit_id"},"mode":"preview"} /--></div>
			<!-- /wp:group -->',
		],
	],
	// TODO.
	'categories' => [],
];