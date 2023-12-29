<?php

// Prevent direct file access.
defined( 'ABSPATH' ) || die;

class Mai_Publisher_Output {
	protected $domain;
	protected $network_code;
	protected $locations;
	protected $ads;
	protected $grouped;
	protected $dom;
	protected $xpath;
	protected $gam;
	protected $mode;
	protected $suffix;

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

		$this->domain       = (string) maipub_get_option( 'gam_domain' );
		$this->network_code = (string) maipub_get_option( 'gam_network_code' );
		$this->sellers_name = (string) maipub_get_option( 'gam_sellers_name' );
		$this->sellers_id   = (string) maipub_get_option( 'gam_sellers_id' );
		$this->locations    = maipub_get_locations();
		$this->ads          = maipub_get_page_ads();
		$this->grouped      = $this->get_grouped_ads( $this->ads );
		$this->gam          = [];
		$this->mode         = maipub_get_option( 'ad_mode', false );
		$this->suffix       = maipub_get_suffix();

		// Bail if no ads or ads are disabled.
		if ( ! $this->ads || 'disabled' === $this->mode ) {
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

		// Bail if no ads.
		if ( ! $this->ads ) {
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

		// Setup dom and xpath.
		$this->dom   = $this->dom_document( $buffer );
		$this->xpath = new DOMXPath( $this->dom );

		// In content.
		if ( isset( $this->grouped['content'] ) && $this->grouped['content'] ) {
			$this->handle_content();
		}

		// In entries.
		if ( isset( $this->grouped['entries'] ) && $this->grouped['entries'] ) {
			$this->handle_entries();
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
		$config   = maipub_get_config( 'ad_units' );
		$ad_units = $this->xpath->query( '//div[contains(concat(" ", normalize-space(@class), " "), " mai-ad-unit ")]' );

		// Loop through ad units.
		foreach ( $ad_units as $ad_unit ) {
			// Build slot from ad unit.
			$unit = $ad_unit->getAttribute( 'data-unit' );
			$slot = $this->get_slot( $unit );

			// Set slot as id.
			$ad_unit->setAttribute( 'id', 'mai-ad-' . $slot );

			// If ads are active.
			if ( ! $this->mode ) {
				// Build script, import into dom and append to ad unit.
				$script = sprintf( '<script>window.googletag = window.googletag || {};googletag.cmd = googletag.cmd || [];if ( window.googletag && googletag.apiReady ) { googletag.cmd.push(function(){ googletag.display("mai-ad-%s"); }); }</script>', $slot );
				$this->insert_nodes( $script, $ad_unit, 'append' );

				// Add to gam array.
				$this->gam[ $slot ] = [
					'id'           => $unit,
					'sizes'        => $config[ $unit ]['sizes'],
					'sizesDesktop' => $config[ $unit ]['sizes_desktop'],
					'sizesTablet'  => $config[ $unit ]['sizes_tablet'],
					'sizesMobile'  => $config[ $unit ]['sizes_mobile'],
					'targets'      => [],
				];

				// Add analytics tracking.
				$ad_unit->setAttribute( 'data-content-name', esc_attr( $slot ) );
				$ad_unit->setAttribute( 'data-track-content', '' );

				// Get and add targets.
				$at      = $ad_unit->getAttribute( 'data-at' );
				$ap      = $ad_unit->getAttribute( 'data-ap' );
				$targets = $ad_unit->getAttribute( 'data-targets' );

				if ( $at ) {
					$this->gam[ $slot ]['targets']['at'] = $at;
				}

				if ( $ap ) {
					$this->gam[ $slot ]['targets']['ap'] = $ap;
				}

				if ( $targets ) {
					$this->gam[ $slot ]['targets'] = array_merge( $this->gam[ $slot ]['targets'], maipub_get_valid_targets( $targets ) );
				}

				// Add split testing.
				$split_test = $ad_unit->getAttribute( 'data-st' );

				if ( $split_test ) {
					$this->gam[ $slot ]['splitTest'] = $split_test;
				}
			}
		}

		// Start scripts array.
		$scripts = [];

		// If we have gam domain and ads are active.
		if ( $this->domain && $this->gam ) {
			$gam_base = '';

			// Maybe disable MCM and use Network Code as base.
			if ( defined( 'MAI_PUBLISHER_DISABLE_MCM' ) && MAI_PUBLISHER_DISABLE_MCM && $this->network_code ) {
				$gam_base = "/$this->network_code";
			} else {
				$gam_base = '/23001026477';

				if ( $this->network_code ) {
					$gam_base .= ",$this->network_code";
				}
			}

			$gam_base .= "/$this->domain/";
			$localize  = [
				'domain'      => $this->domain,
				'sellersName' => $this->sellers_name,
				'sellersId'   => $this->sellers_id,
				'gamBase'     => $gam_base,
				'ads'         => $this->gam,
				'targeting'   => $this->get_targets(),
				'amazonUAM'   => maipub_get_option( 'amazon_uam_enabled' ),
			];

			// Build scripts.
			$file      = "assets/js/mai-publisher-ads{$this->suffix}.js";
			$scripts[] = sprintf( '<script src="https://securepubads.g.doubleclick.net/tag/js/gpt.js?ver=%s"></script>', maipub_get_file_data( $file, 'version' ) );  // Google Ad Manager GPT.
			$scripts[] = sprintf( '<script>/* <![CDATA[ */%svar maiPubAdsVars = %s;%s/* ]]> */</script>', PHP_EOL, wp_json_encode( $localize ), PHP_EOL );
			$scripts[] = sprintf( '<script src="%s?ver=%s"></script>', maipub_get_file_data( $file, 'url' ), maipub_get_file_data( $file, 'version' ) ); // Initial testing showed async broke ads.
		}

		// Handle scripts.
		if ( $scripts ) {
			$element = $this->xpath->query( '//head/link' )->item(0);

			// Insert scripts.
			foreach ( $scripts as $script ) {
				$this->insert_nodes( $script, $element, 'before' );
			}
		}

		// Check connatix. This checks the context of the script to see if it contains the connatix domain.
		$connatix = $this->xpath->query( "//script[contains(text(), 'https://capi.connatix.com')]" );

		// If we have connatix ads.
		if ( $connatix->length ) {
			$script = "<script>!function(n){if(!window.cnx){window.cnx={},window.cnx.cmd=[];var t=n.createElement('iframe');t.src='javascript:false'; t.display='none',t.onload=function(){var n=t.contentWindow.document,c=n.createElement('script');c.src='//cd.connatix.com/connatix.player.js?cid=db8b4096-c769-48da-a4c5-9fbc9ec753f0',c.setAttribute('async','1'),c.setAttribute('type','text/javascript'),n.body.appendChild(c)},n.head.appendChild(t)}}(document);</script>";
			$head   = $this->dom->getElementsByTagName( 'head' )->item(0);

			// Append to head.
			$this->insert_nodes( $script, $head, 'append' );
		}

		// Save HTML.
		$buffer = $this->dom->saveHTML();

		// Remove closing tags that are added by DOMDocument.
		$buffer = str_replace( '</source>', '', $buffer );

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
		$content  = $this->xpath->query( '//div[contains(concat(" ", normalize-space(@class), " "), " entry-content-single ")]' )->item(0);
		$children = $content ? $content->childNodes : [];
		$tags     = [];

		// Bail if no content node or child nodes.
		if ( ! ( $content && $children ) ) {
			return;
		}

		// Get last child element.
		$last = $children->item( $children->length - 1 );

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
	 * Handle in-entries ads.
	 *
	 * @since 0.13.0
	 *
	 * @return void
	 */
	function handle_entries() {
		$wrap    = $this->xpath->query( '//div[contains(concat(" ", normalize-space(@class), " "), " entries-archive ")]/div[contains(concat(" ", normalize-space(@class), " "), " entries-wrap ")]' )->item(0);
		$entries = $wrap ? count( $wrap->childNodes ) : 0;

		// Bail if no wrap and no entries.
		if ( ! ( $wrap && $entries ) ) {
			return;
		}

		// Mai Theme v2 logic for rows/columns inline styles.
		if ( class_exists( 'Mai_Engine' ) ) {
			// Set has rows.
			$has_rows = false;

			// Check if we have an ad with rows.
			foreach ( $this->grouped['entries'] as $ad ) {
				if ( 'rows' === $ad['content_item'] ) {
					$has_rows = true;
					break;
				}
			}

			// Set rows.
			if ( $has_rows ) {
				$style   = $wrap->getAttribute( 'style' );
				$styles  = array_filter( explode( ';' , $style ) );
				$columns = [];

				// Build columns array from inlines styles.
				foreach ( $styles as $style ) {
					if ( ! str_starts_with( $style, '--columns-' ) ) {
						continue;
					}

					$column = explode( ':', $style );

					if ( 2 !== count( $column ) ) {
						continue;
					}

					$break  = str_replace( '--columns-', '', $column[0] );
					$column = explode( '/', $column[1] );

					if ( 2 !== count( $column ) ) {
						continue;
					}

					$columns[ $break ] = $column[1];
				}

				// If columns.
				if ( $columns ) {
					// Get existing styles as an array.
					$style  = $wrap->getAttribute( 'style' );
					$styles = array_filter( explode( ';' , $style ) );
					$styles = array_map( 'trim', $styles );

					// Add row breakpoint styles.
					foreach ( $columns as $break => $column ) {
						$styles[] = sprintf( '--maipub-row-%s:%s;', $break, $column );
					}

					// Set new styles.
					$wrap->setAttribute( 'style', implode( ';', $styles ) . ';' );

					// Insert styles before wrap.
					$file   = "assets/css/mai-engine{$this->suffix}.css";
					$link   = sprintf( '<link href="%s" rel="stylesheet">', maipub_get_file_data( $file, 'url' ) );
					$this->insert_nodes( $link, $wrap, 'before' );
				}
			}
		} // End Mai_Engine logic.

		// Loop through entries ads.
		foreach ( $this->grouped['entries'] as $ad ) {
			// Skip if no content.
			if ( ! $ad['content'] ) {
				continue;
			}

			// Setup vars.
			$class = [];
			$style = [];

			// Build atts.
			switch ( $ad['content_item'] ) {
				case 'rows':
					$class[] = 'maipub-row';
					break;
				case 'entries':
					$class[] = 'maipub-entry';
					break;
			}

			// Sort counts lowest to highest.
			asort( $ad['content_count'] );

			// Mai Theme v2 logic for inserting rows/columns.
			if ( class_exists( 'Mai_Engine' ) ) {
				$compare = null;

				// If counting rows.
				if ( 'rows' === $ad['content_item'] ) {
					$args    = mai_get_template_args();
					$columns = mai_get_breakpoint_columns( $args );

					// If columns.
					if ( isset( $columns['lg'] ) && $columns['lg'] ) {
						// Get desktop rows and round up.
						$rows    = $entries / (int) $columns['lg'];
						$compare = absint( ceil( $rows ) );
					}

				}
				// If counting entries.
				elseif ( 'entries' === $ad['content_item'] ) {
					$class[] = 'entry';
					$class[] = 'entry-archive';
					$class[] = 'is-column';
					$compare = $entries;
				}

				// If comparing.
				if ( ! is_null( $compare ) ) {
					// Remove counts that are greater than the posts per page.
					foreach ( $ad['content_count'] as $index => $count ) {
						if ( (int) $count >= $compare ) {
							// Remove this one and any after it, and break.
							$ad['content_count'] = array_slice( $ad['content_count'], 0, $index );
							break;
						}
					}
				}

				// Loop through each ad count.
				foreach ( $ad['content_count'] as $count ) {
					$item_class   = $class;
					$item_style   = $style;
					$item_style[] = 'rows' === $ad['content_item'] ? "order:calc(var(--maipub-row) * {$count})" : "order:{$count}";
					$item_atts    = [
						'class' => trim( implode( ' ', $item_class ) ),
						'style' => trim( implode( ';', $item_style ) ),
					];

					// Build ad with wrapper.
					$html = sprintf( '<div%s>%s</div>', maipub_build_attributes( $item_atts ), $ad['content'] );

					// Insert the html into the dom.
					$this->insert_nodes( $html, $wrap, 'append' );
				}
			}
			// Not Mai_Engine.
			else {
				// Loop through each ad count.
				foreach ( $ad['content_count'] as $count ) {
					// Insert the html into the dom.
					$this->insert_nodes( $ad['content'], $wrap, 'append' );
				}
			}
		} // End ad loop.
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
			$sidebar_ads['prepend'] = $this->grouped['before_sidebar_content'];
		}

		// Add after sidebar content.
		if ( isset( $this->grouped['after_sidebar_content'] ) ) {
			$sidebar_ads['append'] = $this->grouped['after_sidebar_content'];
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
		$comments = $this->xpath->query( '//ol[contains(concat(" ", normalize-space(@class), " "), " comment-list ")]/li[contains(concat(" ", normalize-space(@class), " "), " comment ")]' );

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
	 * Increments the slot ID, if needed.
	 *
	 * @since 0.13.0
	 *
	 * @param string $slot
	 *
	 * @return string
	 */
	function get_slot( $slot ) {
		static $counts  = [];

		if ( isset( $counts[ $slot ] ) ) {
			$counts[ $slot ]++;
			$slot = $slot . '-' . $counts[ $slot ];
		} else {
			$counts[ $slot ] = 1;
		}

		return $slot;
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

		// Encode. Can't use `mb_convert_encoding()` because it's deprecated in PHP 8.2.
		// @link https://stackoverflow.com/questions/8218230/php-domdocument-loadhtml-not-encoding-utf-8-correctly
		$html = mb_encode_numericentity( $html, [0x80, 0x10FFFF, 0, ~0], 'UTF-8' );

		// Load the content in the document HTML.
		$dom->loadHTML( $html );

		// Handle errors.
		libxml_clear_errors();

		// Restore.
		libxml_use_internal_errors( $libxml_previous_state );

		return $dom;
	}

	/**
	 * Insert node based on an action value.
	 *
	 * @since 0.13.0
	 *
	 * @param string|DOMNode[] $insert The node to insert.
	 * @param DOMNode          $target The target element.
	 * @param string           $action The insertion location.
	 *
	 * @return void
	 */
	function insert_nodes( $insert, $target, $action ) {
		// If string, convert to node.
		if ( is_string( $insert ) ) {
			$insert = $this->import_nodes( $insert );
		}

		// Bail if nothing to insert.
		if ( ! $insert ) {
			return;
		}

		// Make sure this is an array.
		$insert = (array) $insert;

		// Reverse so they are inserted in the order we want.
		$insert = array_reverse( $insert );

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
		if ( $this->domain ) {
			$targets['gd'] = maipub_encode( $this->domain, 14 ); // Character limit needs to match in gam_hashed_domain_callback() in class-settings.php.
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
			$targets['cp'] = wp_parse_url( $path, PHP_URL_PATH );
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
			$targets = array_merge( $targets, maipub_get_valid_targets( $global ) );
		}

		// Custom key value pairs.
		if ( $custom ) {
			$targets = array_merge( $targets, maipub_get_valid_targets( $custom ) );
		}

		return $targets;
	}
}