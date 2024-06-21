<?php

// Prevent direct file access.
defined( 'ABSPATH' ) || die;

class Mai_Publisher_Output {
	protected $bb_network_code;
	protected $domain;
	protected $network_code;
	protected $domain_hashed;
	protected $sellers_name;
	protected $sellers_id;
	protected $sp_property_id;
	protected $sp_tcf_id;
	protected $sp_msps_id;
	protected $locations;
	protected $ads;
	protected $grouped;
	protected $gam;
	protected $mode;
	protected $suffix;
	protected $dom;
	protected $xpath;

	/**
	 * Constructs the class.
	 *
	 * @since 0.13.0
	 *
	 * @return void
	 */
	function __construct() {
		$this->hooks();
	}

	/**
	 * Runs hooks.
	 *
	 * @since 0.13.0
	 *
	 * @return void
	 */
	function hooks() {
		add_action( 'template_redirect', [ $this, 'start' ], 99998 );
		add_action( 'shutdown',          [ $this, 'end' ], 99998 );
	}

	/**
	 * Starts output buffering.
	 *
	 * @since 0.13.0
	 *
	 * @return void
	 */
	function start() {
		if ( is_admin() ) {
			return;
		}

		$this->mode            = maipub_get_option( 'ad_mode' );
		$this->bb_network_code = '23001026477';
		$this->domain          = (string) maipub_get_option( 'gam_domain' );
		$this->network_code    = (string) maipub_get_option( 'gam_network_code' );
		$this->domain_hashed   = (string) maipub_get_option( 'gam_hashed_domain' );
		$this->sellers_name    = (string) maipub_get_option( 'gam_sellers_name' );
		$this->sellers_id      = (string) maipub_get_option( 'gam_sellers_id' );
		$this->sp_property_id  = (int) maipub_get_option( 'sourcepoint_property_id' );
		$this->sp_msps_id      = (int) maipub_get_option( 'sourcepoint_msps_message_id' );
		$this->sp_tcf_id       = (int) maipub_get_option( 'sourcepoint_tcf_message_id' );
		$this->locations       = maipub_get_locations();
		$this->ads             = maipub_get_page_ads();
		$this->grouped         = $this->get_grouped_ads( $this->ads );
		$this->gam             = [];
		$this->suffix          = maipub_get_suffix();

		// Bail if disabled. Not checking `$this->ads` because there may be manual ads in the content.
		if ( 'disabled' === $this->mode ) {
			return;
		}

		ob_start( [ $this, 'callback' ] );
	}

	/**
	 * Ends output buffering.
	 *
	 * @since 0.13.0
	 *
	 * @return void
	 */
	function end() {
		if ( is_admin() ) {
			return;
		}

		// Bail if disabled. Not checking `$this->ads` because there may be manual ads in the content.
		if ( 'disabled' === $this->mode ) {
			return;
		}

		/**
		 * Pretty sure this is right.
		 * @link https://stackoverflow.com/questions/7355356/whats-the-difference-between-ob-flush-and-ob-end-flush
		 */
		if ( ob_get_length() ) {
			ob_end_flush();
		}
	}

	/**
	 * Buffer callback.
	 *
	 * @since 0.13.0
	 *
	 * @param string $buffer The full dom markup.
	 *
	 * @return string
	 */
	function callback( $buffer ) {
		// Bail if no buffer.
		if ( ! $buffer ) {
			return $buffer;
		}

		// Bail if XML.
		if ( str_starts_with( trim( $buffer ), '<?xml' ) ) {
			return $buffer;
		}

		// Bail if running on robots.txt file. Somehow WP is loading here.
		if ( did_action( 'do_robotstxt' ) ) {
			return $buffer;
		}

		// Setup dom and xpath.
		$this->dom   = $this->dom_document( $buffer );
		$this->xpath = new DOMXPath( $this->dom );

		// In content.
		if ( isset( $this->grouped['content'] ) && $this->grouped['content'] ) {
			$this->handle_content();
		}

		// Recipes.
		if ( isset( $this->grouped['recipe'] ) && $this->grouped['recipe'] ) {
			$this->handle_recipes();
		}

		// Sidebars.
		$sidebar_before = isset( $this->grouped['before_sidebar_content'] ) && $this->grouped['before_sidebar_content'];
		$sidebar_after  = isset( $this->grouped['after_sidebar_content'] ) && $this->grouped['after_sidebar_content'];

		// Sidebar.
		if ( $sidebar_before || $sidebar_after ) {
			$this->handle_sidebar();
		}

		// Comments.
		if ( isset( $this->grouped['comments'] ) && $this->grouped['comments'] ) {
			$this->handle_comments();
		}

		// Set vars and get all ad units.
		$config_mai    = maipub_get_config( 'ad_units' );
		$config_client = maipub_get_config( 'client' );
		$config_client = isset( $config_client['ad_units'] ) ? $config_client['ad_units'] : [];
		$ad_units      = $this->xpath->query( '//div[contains(concat(" ", normalize-space(@class), " "), " mai-ad-unit ")]' );
		$videos        = $this->xpath->query( '//div[contains(concat(" ", normalize-space(@class), " "), " mai-ad-video ")]' );
		$ads_count     = $ad_units->length;

		// Loop through ad units.
		foreach ( $ad_units as $ad_unit ) {
			// Build name from location and unit.
			$context  = $ad_unit->getAttribute( 'data-context' );
			$location = $ad_unit->getAttribute( 'data-al' );
			$unit     = $ad_unit->getAttribute( 'data-unit' );
			$slot     = $this->increment_string( $unit );
			$name     = sprintf( 'mai-ad-%s-%s', $location, $unit );
			$name     = $this->increment_string( $name );
			$config   = 'client' === $context ? $config_client : $config_mai;

			// Set slot as id.
			$ad_unit->setAttribute( 'id', 'mai-ad-' . $slot );

			// Add analytics tracking.
			$ad_unit->setAttribute( 'data-content-name', $name );
			$ad_unit->setAttribute( 'data-track-content', '' );

			// If ads are active.
			if ( 'active' === $this->mode ) {
				// Add to gam array.
				$this->gam[ $slot ] = [
					'id'           => $unit,
					'sizes'        => $config[ $unit ]['sizes'],
					'sizesDesktop' => $config[ $unit ]['sizes_desktop'],
					'sizesTablet'  => $config[ $unit ]['sizes_tablet'],
					'sizesMobile'  => $config[ $unit ]['sizes_mobile'],
					'targets'      => [],
				];

				// Get and add targets.
				$at      = $ad_unit->getAttribute( 'data-at' );
				$ap      = $ad_unit->getAttribute( 'data-ap' );
				$targets = $ad_unit->getAttribute( 'data-targets' );

				// Ad location.
				if ( $location ) {
					$loc_formatted = str_replace( '-', '_', $location );

					// If mapped target is set, add it.
					if ( isset( $this->locations[ $loc_formatted ]['target'] ) ) {
						$this->gam[ $slot ]['targets']['al'] = $this->locations[ $loc_formatted ]['target'];
					}
				}

				// Ad type.
				if ( $at ) {
					$this->gam[ $slot ]['targets']['at'] = $at;
				}

				// Ad position.
				if ( $ap ) {
					$this->gam[ $slot ]['targets']['ap'] = $ap;
				}

				// Custom targets.
				if ( $targets ) {
					$this->gam[ $slot ]['targets'] = array_merge( $this->gam[ $slot ]['targets'], maipub_sanitize_targets( $targets ) );
				}

				// Get split testing.
				$split_test = $ad_unit->getAttribute( 'data-st' );

				// If split testing, add it.
				if ( $split_test ) {
					$this->gam[ $slot ]['splitTest'] = $split_test;
				}

				// Get context.
				$context = $ad_unit->getAttribute( 'data-context' );

				// Add context.
				$this->gam[ $slot ]['context'] = $context ?: '';
			}
		}

		// Loop through videos.
		foreach ( $videos as $video ) {
			// Build name from location and unit.
			$location = $video->getAttribute( 'data-al' );
			$unit     = $video->getAttribute( 'data-unit' );
			$name     = sprintf( 'mai-ad-%s-%s', $location, $unit );
			$name     = $this->increment_string( $name );

			// Add analytics tracking.
			$video->setAttribute( 'data-content-name', $name );
			$video->setAttribute( 'data-track-content', '' );
		}

		// Set vars.
		$scripts  = [];
		$position = null;

		// Allow filtering of page GAM ads.
		$this->gam = apply_filters( 'mai_publisher_gam_ads', $this->gam );

		// If we have gam domain and ads are active.
		if ( $this->domain && $this->domain_hashed && $this->gam ) {
			$gam_base = $gam_base_client = '';

			// Maybe disable MCM and use Network Code as base.
			if ( defined( 'MAI_PUBLISHER_DISABLE_MCM' ) && MAI_PUBLISHER_DISABLE_MCM && $this->network_code ) {
				$gam_base = "/$this->network_code";
			} else {
				$gam_base = "/$this->bb_network_code";

				// Add network code if it's not the same. If this is a parent/owned site, it will be the same code.
				if ( $this->network_code && $this->network_code !== $this->bb_network_code ) {
					$gam_base .= ",$this->network_code";
				}
			}

			// Finish gam base.
			$gam_base .= "/$this->domain/";

			// Set custom gam base.
			if ( $this->network_code ) {
				$gam_base_client = "/$this->network_code/";
			}

			// Localize data.
			$localize  = [
				'domain'        => $this->domain,
				'sellersName'   => $this->sellers_name,
				'sellersId'     => $this->sellers_id,
				'gamBase'       => $gam_base,
				'gamBaseClient' => $gam_base_client,
				'ads'           => $this->gam,
				'targets'       => $this->get_targets(),
				'amazonUAM'     => maipub_get_option( 'amazon_uam_enabled' ),
			];

			// If sourcepoint data.
			if ( $this->sp_property_id && $this->sp_msps_id && $this->sp_tcf_id ) {
				// Add sourcepoint scripts.
				$scripts = array_merge( $scripts, $this->get_sourcepoint_scripts() );
			}

			// Load GPT.
			$load_gpt = apply_filters( 'mai_publisher_load_gpt', true );

			// If loading GPT from Mai Publisher.
			if ( $load_gpt ) {
				// Get script data.
				$file = "assets/js/mai-publisher-ads{$this->suffix}.js";
				$gpt  = $this->xpath->query( '//script[contains(@src, "https://securepubads.g.doubleclick.net/tag/js/gpt.js")]' );

				// If we have gpt, remove it.
				if ( $gpt->length ) {
					foreach ( $gpt as $gptNode ) {
						// Remove.
						$gptNode->parentNode->removeChild( $gptNode );
					}
				}

				// Add GPT.
				$scripts[] = '<script async id="mai-publisher-gpt" src="https://securepubads.g.doubleclick.net/tag/js/gpt.js"></script>'; // Google Ad Manager GPT.
			}

			// Add mai-publisher-ads scripts.
			$scripts[] = sprintf( '<script>/* <![CDATA[ */%svar maiPubAdsVars = %s;%s/* ]]> */</script>', PHP_EOL, wp_json_encode( $localize ), PHP_EOL );
			$scripts[] = sprintf( '<script async id="mai-publisher-ads" src="%s?ver=%s"></script>', maipub_get_file_data( $file, 'url' ), maipub_get_file_data( $file, 'version' ) ); // Initial testing showed async broke ads.
		}

		// Check connatix. This checks the context of the script to see if it contains the connatix domain.
		$connatix = $this->xpath->query( "//script[contains(text(), 'https://capi.connatix.com')]" );

		// Temporary filters for TPS.
		$load_connatix = apply_filters( 'mai_publisher_load_connatix', $connatix->length ? true : false );

		// If we have connatix ads.
		if ( $load_connatix ) {
			$scripts[] = "<script id=\"mai-publisher-connatix\">!function(n){if(!window.cnx){window.cnx={},window.cnx.cmd=[];var t=n.createElement('iframe');t.src='javascript:false'; t.display='none',t.onload=function(){var n=t.contentWindow.document,c=n.createElement('script');c.src='//cd.connatix.com/connatix.player.js?cid=db8b4096-c769-48da-a4c5-9fbc9ec753f0',c.setAttribute('async','1'),c.setAttribute('type','text/javascript'),n.body.appendChild(c)},n.head.appendChild(t)}}(document);</script>";
		}

		// Allow filtering the scripts.
		$scripts = apply_filters( 'mai_publisher_header_scripts', $scripts );

		// Handle scripts.
		if ( $scripts ) {
			// $scripts  = array_reverse( $scripts ); // Reverse when displaying 'after' something. Leave as-is when it's 'before'.
			$position = $position ?: $this->xpath->query( '//head/title' )->item(0);

			// Insert scripts.
			foreach ( $scripts as $script ) {
				$this->insert_nodes( $script, $position, 'before' );
			}
		}

		// Allow filter the entire dom.
		$this->dom = apply_filters( 'mai_publisher_dom', $this->dom );

		// Save HTML.
		$buffer = $this->dom_html( $this->dom );

		// Remove closing tags that are added by DOMDocument.
		$buffer = str_replace( '</source>', '', $buffer );
		$buffer = str_replace( '</img>', '', $buffer );

		// Allow filtering all of the HTML.
		$buffer = apply_filters( 'mai_publisher_html', $buffer );

		return $buffer;
	}

	/**
	 * Inserts ads into entry content.
	 *
	 * @since 0.13.0
	 *
	 * @return void
	 */
	function handle_content() {
		$expression = '//div[contains(concat(" ", normalize-space(@class), " "), " entry-content-single ")]';

		// If LearnDash post type.
		if ( class_exists( 'SFWD_LMS' ) && function_exists( 'learndash_get_post_types' ) && is_singular( learndash_get_post_types() ) ) {
			$expression = '//div[contains(concat(" ", normalize-space(@class), " "), " ld-tab-content ")]';
		}

		// Set vars.
		$content  = $this->xpath->query( $expression )->item(0);
		$children = $content ? $content->childNodes : [];
		$tags     = [];

		// Bail if no content node or child nodes.
		if ( ! ( $content && $children ) ) {
			return;
		}

		// Get last child element.
		$last = null;

		// Loop through from the end to the start.
		for ( $i = $children->length - 1; $i >= 0; $i-- ) {
			$node = $children->item($i);

			// Check if the node is an element node.
			if ( $node instanceof DOMElement ) {
				$last = $node;
				break;
			}
		}

		// Loop through in-content ads.
		foreach ( $this->grouped['content'] as $ad ) {
			// Skip if no content.
			if ( ! $ad['content'] ) {
				continue;
			}

			// Skip if no location.
			if ( ! isset( $ad['content_location'] ) || ! $ad['content_location'] ) {
				continue;
			}

			// Get tags by location.
			switch ( $ad['content_location'] ) {
				case 'before':
					$tags = [ 'h2', 'h3' ];
					break;
				case 'after':
					$tags = [ 'div', 'p', 'ol', 'ul', 'blockquote', 'figure', 'iframe' ];
					break;
			}

			// Skip ad if no tags.
			if ( ! $tags ) {
				continue;
			}

			// Filter and sanitize.
			$tags = apply_filters( 'mai_publisher_content_elements', $tags, $ad );
			$tags = array_map( 'sanitize_text_field', $tags );
			$tags = array_filter( $tags );
			$tags = array_unique( $tags );

			// Skip ad if no tags.
			if ( ! $tags ) {
				continue;
			}

			// Start elements.
			$elements = [];

			// Get valid elements.
			foreach ( $children as $node ) {
				if ( ! $node->childNodes->length || ! in_array( $node->nodeName, $tags ) ) {
					continue;
				}

				$elements[] = $node;
			}

			// Skip ad if no elements.
			if ( ! $elements ) {
				continue;
			}

			// Sort counts lowest to highest.
			asort( $ad['content_count'] );

			// Setup counts.
			$item       = 0;
			$tmp_counts = array_flip( $ad['content_count'] );

			// Loop through elements.
			foreach ( $elements as $element ) {
				$item++;

				// Bail if there are no more counts to check.
				if ( ! $tmp_counts ) {
					break;
				}

				// Bail if not an element we need.
				if ( ! isset( $tmp_counts[ $item ] ) ) {
					continue;
				}

				// Before headings.
				if ( 'before' === $ad['content_location'] ) {
					$this->insert_nodes( $ad['content'], $element, 'before' );
				}
				// After elements.
				else {
					/**
					 * Bail if this is the last element.
					 * This avoids duplicates since this location would technically be "after entry content" at this point.
					 */
					if ( $element === $last || null === $element->nextSibling ) {
						break;
					}

					// Insert the node into the dom.
					$this->insert_nodes( $ad['content'], $element, 'after' );
				}

				// Remove from temp counts.
				unset( $tmp_counts[ $item ] );
			}
		}
	}

	/**
	 * Handle recipe ads.
	 *
	 * @since 0.13.0
	 *
	 * @return void
	 */
	function handle_recipes() {
		// $lists = $this->xpath->query( '//div[contains(concat(" ", normalize-space(@class), " "), " wprm-recipe-ingredients-container ") or contains(concat(" ", normalize-space(@class), " "), " wprm-recipe-instructions-container ")]' );
		$lists = $this->xpath->query( '//div[contains(concat(" ", normalize-space(@class), " "), " wprm-recipe-instructions-container ")]' );

		// Bail if no lists.
		if ( ! $lists ) {
			return;
		}

		// Loop through recipe ads.
		foreach ( $this->grouped['recipe'] as $ad ) {
			// Skip if no content.
			if ( ! $ad['content'] ) {
				continue;
			}

			// Loop through containers.
			foreach ( $lists as $list ) {
				$class = $list->getAttribute( 'class' );
				$class = trim( $class . ' mai-ad-container' );
				$list->setAttribute( 'class', $class );
				$this->insert_nodes( $ad['content'], $list, 'append' );
			}
		}
	}

	/**
	 * Handle sidebar ads.
	 *
	 * @since 0.13.0
	 *
	 * @return void
	 */
	function handle_sidebar() {
		$sidebar = $this->xpath->query( '//*[contains(concat(" ", normalize-space(@class), " "), " sidebar ")]' )->item(0);

		// Bail if no sidebar.
		if ( ! $sidebar ) {
			return;
		}

		// Sidebar ads.
		$sidebar_ads = [];

		// Add before sidebar content.
		if ( isset( $this->grouped['before_sidebar_content'] ) ) {
			$before_after           = 'before';
			$sidebar_ads['prepend'] = $this->grouped['before_sidebar_content'];
		}

		// Add after sidebar content.
		if ( isset( $this->grouped['after_sidebar_content'] ) ) {
			$before_after          = 'after';
			$sidebar_ads['append'] = $this->grouped['after_sidebar_content'];
		}

		// Bail if no sidebar ads.
		if ( ! $sidebar_ads ) {
			return;
		}

		// Loop through sidebar ads.
		foreach ( $sidebar_ads as $action => $ads ) {
			foreach ( $ads as $ad ) {
				// Skip if no content.
				if ( ! $ad['content'] ) {
					continue;
				}

				// Check if sticky in Mai Engine.
				if ( class_exists( 'Mai_Engine' ) ) {
					$sticky = false;
					$tags   = new WP_HTML_Tag_Processor( $ad['content'] );

					while ( $tags->next_tag( [ 'tag_name' => 'div', 'class_name' => 'mai-ad-unit' ] ) ) {
						$ap = $tags->get_attribute( 'data-ap' );

						if ( ! $ap ) {
							continue;
						}

						if ( 'vs' !== $ap ) {
							continue;
						}

						$sticky = true;
						break;
					}

					if ( $sticky ) {
						$ad['content'] = sprintf( '<div class="mai-ad-container is-sticky">%s</div>', $ad['content'] );
					}
				}

				// Insert the ad.
				$this->insert_nodes( $ad['content'], $sidebar, $action );
			}
		}
	}

	/**
	 * Handle after header ad.
	 *
	 * @since 0.13.0
	 *
	 * @return void
	 */
	function handle_comments() {
		$comments = $this->xpath->query( '//ol[contains(concat(" ", normalize-space(@class), " "), " comment-list ")]/li[contains(concat(" ", normalize-space(@class), " "), " comment ") and not(position() = last())]' );

		if ( ! $comments->length ) {
			return;
		}

		// Loop through comments ads.
		foreach ( $this->grouped['comments'] as $ad ) {
			// Skip if no content.
			if ( ! $ad['content'] ) {
				continue;
			}

			// Set item and counter.
			$item  = 0;
			$count = $ad['comment_count'];

			// Loop through comments.
			foreach ( $comments as $comment ) {
				$item ++;

				// Skip if it does equal count or isn't divisble by count.
				if ( $item !== $count && 0 !== ( $item % $count ) ) {
					continue;
				}

				// Insert the ad into the dom.
				$this->insert_nodes( $ad['content'], $comment, 'after' );
			}
		}
	}

	/**
	 * Gets the full DOMDocument object, including DOCTYPE and <html>.
	 *
	 * @since 0.13.0
	 *
	 * @link https://stackoverflow.com/questions/29493678/loadhtml-libxml-html-noimplied-on-an-html-fragment-generates-incorrect-tags
	 *
	 * @param string $html Any given HTML string.
	 *
	 * @return DOMDocument
	 */
	function dom_document( $html ) {
		// Create the new document.
		$dom = new DOMDocument();

		// Modify state.
		$libxml_previous_state = libxml_use_internal_errors( true );

		// We don't need this here since it's running so late, all of the content
		// should already be encoded.
		// This was causing issues because it's converting ALL entities,
		// including stuff in data-attributes, etc.
		// $html = mb_encode_numericentity( $html, [0x80, 0x10FFFF, 0, ~0], 'UTF-8' );

		// Load the content in the document HTML.
		$dom->loadHTML( $html );

		// Handle errors.
		libxml_clear_errors();

		// Restore.
		libxml_use_internal_errors( $libxml_previous_state );

		return $dom;
	}

	/**
	 * Saves HTML from DOMDocument and decode entities.
	 *
	 * @since 1.1.0
	 *
	 * @param DOMDocument $dom
	 *
	 * @return string
	 */
	function dom_html( $dom ) {
		$html = $dom->saveHTML();

		// We don't need this here since it's running so late, all of the content
		// should already be encoded.
		// This was causing issues because it's converting ALL entities,
		// including stuff in data-attributes, etc.
		// $html = mb_convert_encoding( $html, 'UTF-8', 'HTML-ENTITIES' );

		return $html;
	}

	/**
	 * Insert node based on an action value.
	 *
	 * @since 0.13.0
	 *
	 * @param string|DOMNode[] $insert The node(s) to insert.
	 * @param DOMNode          $target The target element.
	 * @param string           $action The insertion location.
	 *
	 * @return void
	 */
	function insert_nodes( $insert, $target, $action ) {
		// If string, convert to node.
		if ( $insert && is_string( $insert ) ) {
			$insert = $this->import_nodes( $insert );
		}

		// Bail if nothing to insert.
		if ( ! $insert ) {
			return;
		}

		// Reverse the array if displaying after or prepending, so they end up in the right order.
		// After would put each element directly after the target, so they would end up in reverse order.
		// Prepend would put each element directly before the first child of the target, so they would end up in reverse order.
		if ( in_array( $action, [ 'after', 'prepend' ] ) ) {
			$insert = array_reverse( $insert );
		}

		// Filter only DOMElement nodes from array.
		$insert = array_filter( $insert, function( $node ) {
			return $node instanceof DOMElement;
		});

		// Find the action.
		switch ( $action ) {
			// Insert before this element.
			case 'before':
				foreach ( $insert as $node ) {
					$target->parentNode->insertBefore( $node, $target );
				}
				break;
			/**
			 * Insert after this element. There is no insertAfter() in PHP ¯\_(ツ)_/¯.
			 * @link https://gist.github.com/deathlyfrantic/cd8d7ef8ba91544cdf06
			 */
			case 'after':
				foreach ( $insert as $node ) {
					$target->parentNode->insertBefore( $node, $target->nextSibling );
				}
				break;
			// Insert before first child.
			case 'prepend':
				foreach ( $insert as $node ) {
					$target->insertBefore( $node, $target->firstChild );
				}
				break;
			// Insert after last child.
			case 'append':
				foreach ( $insert as $node ) {
					$target->appendChild( $node );
				}
				break;
		}
	}

	/**
	 * Build the temporary dom.
	 * Special characters were causing issues with `appendXML()`.
	 *
	 * @since 0.13.0
	 *
	 * @link https://stackoverflow.com/questions/4645738/domdocument-appendxml-with-special-characters
	 * @link https://www.py4u.net/discuss/974358
	 *
	 * @return DOMNode[]
	 */
	function import_nodes( $content ) {
		if ( ! $content ) {
			return false;
		}

		$tmp = $this->dom_document( "<div>$content</div>" );

		// Handle wraps.
		$container = $tmp->getElementsByTagName('div')->item(0);
		$container = $container->parentNode->removeChild( $container );

		while ( $tmp->firstChild ) {
			$tmp->removeChild( $tmp->firstChild );
		}

		while ( $container->firstChild ) {
			$tmp->appendChild( $container->firstChild );
		}

		$nodes = [];

		foreach ( $tmp->childNodes as $node ) {
			if ( ! $node instanceof DOMElement ) {
				continue;
			}

			$nodes[] = $this->dom->importNode( $node, true );
		}

		return $nodes;
	}

	/**
	 * Loop through ads and group by location.
	 *
	 * @since 0.13.0
	 *
	 * @param array $page_ads The existing ads array.
	 *
	 * @return array
	 */
	function get_grouped_ads( $page_ads ) {
		$ads = [];

		foreach ( $page_ads as $ad ) {
			// Skip if not a valid location.
			if ( ! isset( $ad['location'] ) || ! $ad['location'] || ! isset( $this->locations[ $ad['location'] ] ) ) {
				continue;
			}

			// Store and unset location.
			$location = $ad['location'];
			unset( $ad['location'] );

			// Make sure location key is set.
			$ads[ $location ] = isset( $ads[ $location ] ) ? $ads[ $location ] : [];

			// Add to new array.
			$ads[ $location ][] = $ad;
		}

		return $ads;
	}

	/**
	 * Get targets.
	 * These must be exist in our GAM 360.
	 *
	 * @since 0.13.0
	 *
	 * @return void
	 */
	function get_targets() {
		$targets = [];
		$age     = maipub_get_content_age();
		$creator = maipub_get_content_creator();
		$group   = maipub_get_content_group();
		$path    = maipub_get_current_page( 'url' );
		$type    = maipub_get_content_type();
		$iabct   = maipub_get_current_page( 'iabct' );
		$page_id = maipub_get_current_page_id();
		$global  = maipub_get_option( 'gam_targets' );
		$custom  = $page_id ? get_post_meta( $page_id, 'maipub_targets', true ) : '';

		// Hashed domain.
		if ( $this->domain_hashed ) {
			// $targets['gd'] = maipub_encode( $this->domain, 14 ); // Character limit needs to match in gam_hashed_domain_callback() in class-settings.php.
			$targets['gd'] = $this->domain_hashed;
		}

		// Sellers ID.
		if ( $this->network_code ) {
			$targets['gs'] = maipub_encode( $this->network_code );
		}

		// Content age.
		if ( $age ) {
			$targets['ca'] = $age[0];
		}

		// Content creator.
		if ( $creator ) {
			$targets['cc'] = $creator;
		}

		// Content group/category.
		if ( $group ) {
			$targets['cg'] = $group;
		}

		// Content path.
		if ( $path ) {
			// Get path.
			$cp = wp_parse_url( $path, PHP_URL_PATH );

			// This was null in some scenarios like posts being shown on the homepage.
			if ( $cp ) {
				$targets['cp'] = $cp;
			}
		}

		// Content type.
		if ( $type ) {
			$targets['ct'] = $type;
		}

		// IAB Content Taxonomy.
		if ( $iabct ) {
			$targets['iabct'] = $iabct;
		}

		// Global key value pairs.
		if ( $global ) {
			$targets = array_merge( $targets, maipub_sanitize_targets( $global ) );
		}

		// Custom key value pairs.
		if ( $custom ) {
			$targets = array_merge( $targets, maipub_sanitize_targets( $custom ) );
		}

		// Force refresh key value.
		$targets['refresh'] = isset( $targets['refresh'] ) ? rest_sanitize_boolean( $targets['refresh'] ) : true;

		return $targets;
	}

	/**
	 * Get Sourcepoint scripts.
	 *
	 * @since 1.6.6
	 *
	 * @return string
	 */
	function get_sourcepoint_scripts() {
		$scripts   = [];
		$scripts[] = '<script>"use strict";function _typeof(t){return(_typeof="function"==typeof Symbol&&"symbol"==typeof Symbol.iterator?function(t){return typeof t}:function(t){return t&&"function"==typeof Symbol&&t.constructor===Symbol&&t!==Symbol.prototype?"symbol":typeof t})(t)}!function(){var t=function(){for(var t,e,o=[],n=window,r=n;r;){try{if(r.frames.__tcfapiLocator){t=r;break}}catch(a){}if(r===n.top)break;r=r.parent}t||(!function t(){var e=n.document,o=!!n.frames.__tcfapiLocator;if(!o){if(e.body){var r=e.createElement("iframe");r.style.cssText="display:none",r.name="__tcfapiLocator",e.body.appendChild(r)}else setTimeout(t,5)}return!o}(),n.__tcfapi=function(){for(var t=arguments.length,n=Array(t),r=0;r<t;r++)n[r]=arguments[r];if(!n.length)return o;"setGdprApplies"===n[0]?n.length>3&&2===parseInt(n[1],10)&&"boolean"==typeof n[3]&&(e=n[3],"function"==typeof n[2]&&n[2]("set",!0)):"ping"===n[0]?"function"==typeof n[2]&&n[2]({gdprApplies:e,cmpLoaded:!1,cmpStatus:"stub"}):o.push(n)},n.addEventListener("message",function(t){var e="string"==typeof t.data,o={};if(e)try{o=JSON.parse(t.data)}catch(n){}else o=t.data;var r="object"===_typeof(o)&&null!==o?o.__tcfapiCall:null;r&&window.__tcfapi(r.command,r.version,function(o,n){var a={__tcfapiReturn:{returnValue:o,success:n,callId:r.callId}};t&&t.source&&t.source.postMessage&&t.source.postMessage(e?JSON.stringify(a):a,"*")},r.parameter)},!1))};"undefined"!=typeof module?module.exports=t:t()}();</script>';
		$scripts[] = '<script>window.__gpp_addFrame=function(e){if(!window.frames[e]){if(document.body){var t=document.createElement("iframe");t.style.cssText="display:none",t.name=e,document.body.appendChild(t)}else window.setTimeout(window.__gpp_addFrame,10,e)}},window.__gpp_stub=function(){var e=arguments;if(__gpp.queue=__gpp.queue||[],__gpp.events=__gpp.events||[],!e.length||1==e.length&&"queue"==e[0])return __gpp.queue;if(1!=e.length||"events"!=e[0]){__gpp.events;var t=e[0],s=e.length>1?e[1]:null,a=e.length>2?e[2]:null;if("ping"===t)s({gppVersion:"1.1",cmpStatus:"stub",cmpDisplayStatus:"hidden",signalStatus:"not ready",supportedAPIs:["2:tcfeuv2","5:tcfcav1","6:uspv1","7:usnatv1","8:uscav1","9:usvav1","10:uscov1","11:usutv1","12:usctv1"],cmpId:0,sectionList:[],applicableSections:[],gppString:"",parsedSections:{}},!0);else if("addEventListener"===t){"lastId"in __gpp||(__gpp.lastId=0),__gpp.lastId++;var n=__gpp.lastId;__gpp.events.push({id:n,callback:s,parameter:a}),s({eventName:"listenerRegistered",listenerId:n,data:!0,pingData:{gppVersion:"1.1",cmpStatus:"stub",cmpDisplayStatus:"hidden",signalStatus:"not ready",supportedAPIs:["2:tcfeuv2","5:tcfcav1","6:uspv1","7:usnatv1","8:uscav1","9:usvav1","10:uscov1","11:usutv1","12:usctv1"],cmpId:0,sectionList:[],applicableSections:[],gppString:"",parsedSections:{}}},!0)}else if("removeEventListener"===t){for(var p=!1,i=0;i<__gpp.events.length;i++)if(__gpp.events[i].id==a){__gpp.events.splice(i,1),p=!0;break}s({eventName:"listenerRemoved",listenerId:a,data:p,pingData:{gppVersion:"1.1",cmpStatus:"stub",cmpDisplayStatus:"hidden",signalStatus:"not ready",supportedAPIs:["2:tcfeuv2","5:tcfcav1","6:uspv1","7:usnatv1","8:uscav1","9:usvav1","10:uscov1","11:usutv1","12:usctv1"],cmpId:0,sectionList:[],applicableSections:[],gppString:"",parsedSections:{}}},!0)}else"hasSection"===t?s(!1,!0):"getSection"===t||"getField"===t?s(null,!0):__gpp.queue.push([].slice.apply(e))}},window.__gpp_msghandler=function(e){var t="string"==typeof e.data;try{var s=t?JSON.parse(e.data):e.data}catch(a){s=null}if("object"==typeof s&&null!==s&&"__gppCall"in s){var n=s.__gppCall;window.__gpp(n.command,function(s,a){var p={__gppReturn:{returnValue:s,success:a,callId:n.callId}};e.source.postMessage(t?JSON.stringify(p):p,"*")},"parameter"in n?n.parameter:null,"version"in n?n.version:"1.1")}},"__gpp"in window&&"function"==typeof window.__gpp||(window.__gpp=window.__gpp_stub,window.addEventListener("message",window.__gpp_msghandler,!1),window.__gpp_addFrame("__gppLocator"));</script>';
		$scripts[] = '<script>!function(){var a=window,e=document;function t(e){var t="string"==typeof e.data;try{var n=t?JSON.parse(e.data):e.data;if(n.__cmpCall){var p=n.__cmpCall;a.__uspapi(p.command,p.parameter,function(a,n){var r={__cmpReturn:{returnValue:a,success:n,callId:p.callId}};e.source.postMessage(t?JSON.stringify(r):r,"*")})}}catch(r){}}!function t(){if(!a.frames.__uspapiLocator){if(e.body){var n=e.body,p=e.createElement("iframe");p.style.cssText="display:none",p.name="__uspapiLocator",n.appendChild(p)}else setTimeout(t,5)}}(),"function"!=typeof __uspapi&&(a.__uspapi=function a(){var e=arguments;if(__uspapi.a=__uspapi.a||[],!e.length)return __uspapi.a;"ping"===e[0]?e[2]({gdprAppliesGlobally:!1,cmpLoaded:!1},!0):__uspapi.a.push([].slice.apply(e))},__uspapi.msgHandler=t,a.addEventListener("message",t,!1))}();</script>';
		$scripts[] = '<script>
			window._sp_queue = [];
			window._sp_ = {
				config: {
					accountId: 1970,
					baseEndpoint: "https://cdn.privacy-mgmt.com",
					propertyId: ' . (string) $this->sp_property_id . ',
					usnat: { includeUspApi: true },
					gdpr: { },
					events: {
						onMessageChoiceSelect: function() {
							console.log( "[event] onMessageChoiceSelect", arguments );
						},
						onMessageReady: function() {
							console.log( "[event] onMessageReady", arguments );
						},
						onMessageChoiceError: function() {
							console.log( "[event] onMessageChoiceError", arguments );
						},
						onPrivacyManagerAction: function() {
							console.log( "[event] onPrivacyManagerAction", arguments );
						},
						onPMCancel: function() {
							console.log( "[event] onPMCancel", arguments );
						},
						onMessageReceiveData: function() {
							console.log( "[event] onMessageReceiveData", arguments );
						},
						onSPPMObjectReady: function() {
							console.log( "[event] onSPPMObjectReady", arguments );
						},
						onConsentReady: function (message_type, uuid, string, info) {
							console.log( "[event] onConsentReady", arguments );
							if((message_type == "usnat") && (info.applies)){
								/* code to insert the USNAT footer link */
								document.getElementById("pmLink").style.visibility="visible";
								document.getElementById("pmLink").innerHTML= "Do Not Sell/Share My Personal Information";
								document.getElementById("pmLink").onclick= function(){
									window._sp_.usnat.loadPrivacyManagerModal( "' . $this->sp_msps_id . '" );
								}
							}
							if((message_type == "gdpr") && (info.applies)){
								/* code to insert the GDPR footer link */
								document.getElementById("pmLink").style.visibility="visible";
								document.getElementById("pmLink").innerHTML= "Privacy Preferences";
								document.getElementById("pmLink").onclick= function(){
									window._sp_.gdpr.loadPrivacyManagerModal( "' . $this->sp_tcf_id . '" );
								}
							}
						},
						onError: function() {
							console.log( "[event] onError", arguments );
						},
					}
				}
			}
		</script>';

		$scripts[] = '<script async src="https://cdn.privacy-mgmt.com/unified/wrapperMessagingWithoutDetection.js"></script>';

		return $scripts;
	}

	/**
	 * Increments a string, if needed.
	 *
	 * @since 0.23.0
	 *
	 * @param string $string
	 *
	 * @return string
	 */
	function increment_string( $string ) {
		static $counts  = [];

		if ( isset( $counts[ $string ] ) ) {
			$counts[ $string ]++;
			$string = $string . '-' . $counts[ $string ];
		} else {
			$counts[ $string ] = 1;
		}

		return $string;
	}

	/**
	 * Minify inline JS.
	 *
	 * @since 0.1.0
	 *
	 * @link https://gist.github.com/Rodrigo54/93169db48194d470188f
	 *
	 * @param string $input
	 *
	 * @return string
	 */
	function minify_js( $input ) {
		if ( '' === trim( $input ) ) {
			return $input;
		}

		$input = preg_replace(
			[
				// Remove comment(s).
				'#\s*("(?:[^"\\\]++|\\\.)*+"|\'(?:[^\'\\\\]++|\\\.)*+\')\s*|\s*\/\*(?!\!|@cc_on)(?>[\s\S]*?\*\/)\s*|\s*(?<![\:\=])\/\/.*(?=[\n\r]|$)|^\s*|\s*$#',
				// Remove white-space(s) outside the string and regex.
				'#("(?:[^"\\\]++|\\\.)*+"|\'(?:[^\'\\\\]++|\\\.)*+\'|\/\*(?>.*?\*\/)|\/(?!\/)[^\n\r]*?\/(?=[\s.,;]|[gimuy]|$))|\s*([!%&*\(\)\-=+\[\]\{\}|;:,.<>?\/])\s*#s',
				// Remove the last semicolon.
				'#;+\}#',
				// Minify object attribute(s) except JSON attribute(s). From `{'foo':'bar'}` to `{foo:'bar'}`.
				'#([\{,])([\'])(\d+|[a-z_][a-z0-9_]*)\2(?=\:)#i',
				// --ibid. From `foo['bar']` to `foo.bar`.
				'#([a-z0-9_\)\]])\[([\'"])([a-z_][a-z0-9_]*)\2\]#i'
			],
			[
				'$1',
				'$1$2',
				'}',
				'$1$3',
				'$1.$3'
			],
			$input
		);

		return trim( $input );
	}
}