<?php

// Prevent direct file access.
defined( 'ABSPATH' ) || die;

/**
 * Adds settings page.
 *
 * @link /wp-admin/edit.php?post_type=mai_ad&page=adstxt
 */
class Mai_Publisher_Settings_Ad_Txt {
	protected $adstxt;

	/**
	 * Construct the class.
	 */
	function __construct() {
		$this->hooks();
	}

	/**
	 * Add hooks.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	function hooks() {
		add_action( 'admin_menu',             [ $this, 'add_menu_item' ], 12 );
		add_action( 'admin_notices',          [ $this, 'admin_notices' ] );
		add_action( 'admin_post_save_adstxt', [ $this, 'save_adstxt' ] );
	}

	/**
	 * Adds menu item for settings page.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	function add_menu_item() {
		add_submenu_page(
			'edit.php?post_type=mai_ad',
			__( 'Ads.txt Manager', 'mai-publisher' ), // page_title
			__( 'Ads.txt', 'mai-publisher' ), // menu title.
			'manage_options', // capability
			'adstxt', // menu_slug
			[ $this, 'add_content' ], // callback
		);
	}

	/**
	 * Adds admin notices.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	function admin_notices() {
		// Get the ads.txt content.
		$this->adstxt = $this->get_adstxt();

		// If error, show error message.
		if ( is_wp_error( $this->adstxt ) ) {
			printf( '<div class="notice notice-error"><p>%s</p></div>', $this->adstxt->get_error_message() );
		}
	}

	/**
	 * Adds setting page content.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	function add_content() {
		echo '<div class="wrap">';
			printf( '<h2>%s</h2>', __( 'Ad.txt Manager', 'mai-publisher' ) );

			// If error, don't show the field.
			if ( is_wp_error( $this->adstxt ) ) {
				echo __( 'Please fix the above error and reload the page.', 'mai-publisher' );
			}
			// No error.
			else {
				// Enqueue CodeMirror.
				wp_enqueue_script( 'codemirror-js', 'https://cdnjs.cloudflare.com/ajax/libs/codemirror/6.65.7/codemirror.min.js', [], '6.65.7', true );
				wp_enqueue_script( 'codemirror-mode-js', 'https://cdnjs.cloudflare.com/ajax/libs/codemirror/6.65.7/mode/javascript/javascript.min.js', [ 'codemirror-js' ], '6.65.7', true );
				wp_enqueue_style( 'codemirror-css', 'https://cdnjs.cloudflare.com/ajax/libs/codemirror/6.65.7/codemirror.min.css', [], '6.65.7' );

				// Add form.
				printf( '<form method="post" action="%s" id="adstxt-form" class="adstxt-form">', esc_url( admin_url( 'admin-post.php' ) ) );
					// Add hidden fields.
					echo '<input type="hidden" name="action" value="save_adstxt" />';
					wp_nonce_field( 'save_adstxt' );

					// Ads.txt editor.
					echo '<p>';
						printf( '<label for="ads_txt_core">%s</label>', __( 'Ads.txt', 'mai-publisher' ) );
						echo '<textarea id="ads_txt" class="ads_txt" name="ads_txt">';
							echo esc_textarea( $this->adstxt );
						echo '</textarea>';
					echo '</p>';

					// Ads.txt editor.
					echo '<p>';
						printf( '<label for="ads_txt_core">%s</label>', __( 'Core Ads.txt', 'mai-publisher' ) );
						echo '<textarea id="ads_txt_core" class="ads_txt" name="ads_txt_core" readonly>';
							echo esc_textarea( '# TBD, based on other settings' );
						echo '</textarea>';
					echo '</p>';

					// Ads.txt editor.
					// echo '<textarea class="ads_txt" name="ads_txt_oao">';
					// 	echo esc_textarea( $this->adstxt );
					// echo '</textarea>';

					// Our default ads.txt content.

					// Submit button.
					echo '<p class="submit">';
						printf( '<input type="submit" name="submit" id="submit" class="button button-primary" value="%s">', __( 'Save Changes', 'mai-publisher' ) );
						echo '<span class="spinner" style="float:none;vertical-align:top"></span>';
					echo '</p>';
				echo '</form>';
				?>
				<style>
				.CodeMirror {
					width: 100%;
					min-height: 40vh;
					border: 1px solid #ddd;
					box-sizing: border-box;
				}
				</style>
				<script>
				document.addEventListener( 'DOMContentLoaded', function() {
					var textareas = document.querySelectorAll( '.ads_txt' );
					textareas.forEach( function( textarea ) {
						var editor = CodeMirror.fromTextArea( textarea, {
							lineNumbers: true,
							mode: 'shell',
							theme: 'default'
						});
					});
				});
				</script>
				<?php
			}
		echo '</div>';
	}

	/**
	 * Get the existing ads.txt content.
	 *
	 * @return void
	 */
	function get_adstxt() {
		$file_url = untrailingslashit( home_url() ) . '/ads.txt';
		$response = wp_remote_get( $file_url );

		// If error, return error message.
		if ( is_wp_error( $response ) ) {
			return $response;
		}

		// If response code is not 200, return error message.
		if ( 200 !== wp_remote_retrieve_response_code( $response ) ) {
			return new WP_Error( 'adstxt_error', wp_remote_retrieve_response_message( $response ) );
		}

		// Get the body.
		$body = wp_remote_retrieve_body( $response );

		return $body;
	}

	/**
	 * Save the ads.txt content.
	 *
	 * @return void
	 */
	function save_adstxt() {
		// Check nonce.
		check_admin_referer( 'save_adstxt' );

		// Throw error if content is not set.
		if ( ! isset( $_POST['ads_txt'] ) ) {
			wp_die( __( 'No content was found.', 'mai-publisher' ) );
		}

		// Get the content.
		$content = sanitize_textarea_field( $_POST['ads_txt'] );

		// Get the file path.
		$file_path = ABSPATH . 'ads.txt';

		// Overwrite the file with the new data.
		file_put_contents( $file_path, $content );

		// Redirect back.
		wp_safe_redirect( admin_url( 'edit.php?post_type=mai_ad&page=adstxt' ) );
		exit;
	}
}
