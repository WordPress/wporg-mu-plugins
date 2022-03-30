<?php
/**
 * Tweaks for specific plugins on WordPress.org.
 *
 * If there's just one tweak for a plugin, feel free to add it here on the index file. If there's more than
 * one, or multiple functions/files are needed, consider adding a separate file or directory for that plugin
 * and loading those files from here.
 *
 * When adding a function here, please prefix it with the plugin's name.
 */

namespace WordPressdotorg\MU_Plugins\Plugin_Tweaks;

defined( 'WPINC' ) || die();

/**
 * Actions and filters.
 */
add_filter( 'wporg_internal_notes_rest_prepare_response', __NAMESPACE__ . '\wporg_internal_notes_replace_rest_author_link' );

/**
 * Replace the Internal Notes default embeddable author link with one from the wporg endpoint.
 *
 * Without this, any note author that isn't a member of the Pattern Directory site will appear as "unknown"
 * on internal notes and logs.
 *
 * @param \WP_REST_Response $response
 *
 * @return \WP_REST_Response
 */
function wporg_internal_notes_replace_rest_author_link( $response ) {
	$response_data = $response->get_data();
	$author = get_user_by( 'id', $response_data['author'] ?? 0 );

	if ( ! $author ) {
		return $response;
	}

	$response->remove_link( 'author' );
	$response->add_link(
		'author',
		rest_url( sprintf( 'wporg/v1/users/%s', $author->user_nicename ) ),
		array(
			'embeddable' => true,
		)
	);

	return $response;
}
