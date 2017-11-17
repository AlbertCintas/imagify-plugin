<?php
defined( 'ABSPATH' ) || die( 'Cheatin&#8217; uh?' );

add_action( 'admin_init', '_imagify_upgrader' );
/**
 * Tell WP what to do when admin is loaded aka upgrader.
 *
 * @since 1.0
 */
function _imagify_upgrader() {
	$current_version = get_imagify_option( 'version' );

	// You can hook the upgrader to trigger any action when Imagify is upgraded.
	// First install.
	if ( ! $current_version ) {
		do_action( 'imagify_first_install' );
	}
	// Already installed but got updated.
	elseif ( IMAGIFY_VERSION !== $current_version ) {
		do_action( 'imagify_upgrade', IMAGIFY_VERSION, $current_version );
	}

	// If any upgrade has been done, we flush and update version.
	if ( did_action( 'imagify_first_install' ) || did_action( 'imagify_upgrade' ) ) {
		update_imagify_option( 'version', IMAGIFY_VERSION );
	}
}

add_action( 'imagify_first_install', '_imagify_first_install' );
/**
 * Keeps this function up to date at each version.
 *
 * @since 1.0
 */
function _imagify_first_install() {
	// Set a transient to know when we will have to display a notice to ask the user to rate the plugin.
	set_site_transient( 'imagify_seen_rating_notice', true, DAY_IN_SECONDS * 3 );
}

add_action( 'imagify_upgrade', '_imagify_new_upgrade', 10, 2 );
/**
 * What to do when Imagify is updated, depending on versions.
 *
 * @since 1.0
 *
 * @param string $imagify_version New Imagify version.
 * @param string $current_version Old Imagify version.
 */
function _imagify_new_upgrade( $imagify_version, $current_version ) {
	global $wpdb;

	$options = Imagify_Options::get_instance();

	// 1.2
	if ( version_compare( $current_version, '1.2' ) < 0 ) {
		// Update all already optimized images status from 'error' to 'already_optimized'.
		$query = new WP_Query( array(
			'post_type'              => 'attachment',
			'post_status'            => 'inherit',
			'post_mime_type'         => 'image',
			'meta_key'               => '_imagify_status',
			'meta_value'             => 'error',
			'posts_per_page'         => -1,
			'update_post_term_cache' => false,
			'no_found_rows'          => true,
			'fields'                 => 'ids',
		) );

		if ( $query->posts ) {
			foreach ( (array) $query->posts as $id ) {
				$attachment_error = get_imagify_attachment( 'wp', $id, 'imagify_upgrade' )->get_optimized_error();

				if ( false !== strpos( $attachment_error, 'This image is already compressed' ) ) {
					update_post_meta( $id, '_imagify_status', 'already_optimized' );
				}
			}
		}

		// Auto-activate the Admin Bar option.
		$options->set( 'admin_bar_menu', 1 );
	}

	// 1.3.2
	if ( version_compare( $current_version, '1.3.2' ) < 0 ) {
		// Update all already optimized images status from 'error' to 'already_optimized'.
		$query = new WP_Query( array(
			'post_type'              => 'attachment',
			'post_status'            => 'inherit',
			'post_mime_type'         => 'image',
			'meta_query'             => array(
				'relation' => 'AND',
				array(
					'key'     => '_imagify_data',
					'compare' => 'EXISTS',
				),
				array(
					'key'     => '_imagify_optimization_level',
					'compare' => 'NOT EXISTS',
				),
			),
			'posts_per_page'         => -1,
			'update_post_term_cache' => false,
			'no_found_rows'          => true,
			'fields'                 => 'ids',
		) );

		if ( $query->posts ) {
			foreach ( (array) $query->posts as $id ) {
				$attachment_stats = get_imagify_attachment( 'wp', $id, 'imagify_upgrade' )->get_stats_data();

				if ( isset( $attachment_stats['aggressive'] ) ) {
					update_post_meta( $id, '_imagify_optimization_level', (int) $attachment_stats['aggressive'] );
				}
			}
		}
	}

	// 1.4.5
	if ( version_compare( $current_version, '1.4.5' ) < 0 ) {
		// Delete all transients used for async optimization.
		$wpdb->query( 'DELETE from ' . $wpdb->options . ' WHERE option_name LIKE "_transient_imagify-async-in-progress-%"' );
	}

	// 1.7
	if ( version_compare( $current_version, '1.7' ) < 0 ) {
		// Migrate data.
		$old_options = get_option( 'imagify_settings' );

		if ( is_array( $old_options ) && ! empty( $old_options['total_size_images_library']['raw'] ) && ! empty( $old_options['average_size_images_per_month']['raw'] ) ) {
			Imagify_Data::get_instance()->set( array(
				'total_size_images_library'     => $old_options['total_size_images_library']['raw'],
				'average_size_images_per_month' => $old_options['average_size_images_per_month']['raw'],
			) );
		} else {
			// They are not set? Strange, but ok, let's calculate them.
			_do_imagify_update_library_size_calculations();
		}

		if ( imagify_is_active_for_network() ) {
			// Now we can delete the option if we don't use it for the settings.
			delete_option( 'imagify_settings' );
		} else {
			// Make sure the settings are auloaded.
			$wpdb->query( $wpdb->prepare( "UPDATE {$wpdb->options} SET `autoload` = 'yes' WHERE `autoload` != 'yes' AND option_name = %s", $options->get_option_name() ) );
		}

		// Cleanup the settings (they're not deleted, they're only sanitized and saved).
		$options->set( array() );
	}
}
