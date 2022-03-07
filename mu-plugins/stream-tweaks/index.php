<?php

namespace WordPressdotorg\MU_Plugins\Stream_Tweaks;

add_filter( 'wp_stream_connectors', __NAMESPACE__ . '\replace_posts_connector' );

/**
 * Substitute Stream's posts connector class for our modified version.
 *
 * @param array $connector_classes
 *
 * @return mixed
 */
function replace_posts_connector( $connector_classes ) {
	if ( ! function_exists( 'wp_stream_get_instance' ) ) {
		return $connector_classes;
	}

	require_once __DIR__ . '/class-connector-posts.php';

	$stream_plugin_instance = wp_stream_get_instance();
	$new_posts_connector = new Connector_Posts( $stream_plugin_instance->log ); // WordPressdotorg namespaced version.

	// Remove the default posts connector class if it has been initialized.
	if ( isset( $connector_classes[ $new_posts_connector->name ] ) ) {
		unset( $connector_classes[ $new_posts_connector->name ] );
	}

	if ( $new_posts_connector->is_dependency_satisfied() ) {
		$connector_classes[ $new_posts_connector->name ] = $new_posts_connector;
	}

	return $connector_classes;
}
