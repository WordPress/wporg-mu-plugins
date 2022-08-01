<?php

namespace WordPressdotorg\MU_Plugins\Global_Header_Footer\Header;

use function WordPressdotorg\MU_Plugins\Global_Header_Footer\{ get_home_url, get_download_url, get_container_classes };

defined( 'WPINC' ) || die();

/**
 * Defined in `render_global_header()`.
 *
 * @var array  $attributes
 * @var array  $menu_items
 * @var string $locale_title
 * @var string $show_search
 */

$container_class = 'global-header';
if ( ! empty( $locale_title ) ) {
	$container_class .= ' global-header__has-locale-title';
}

$color_scheme = apply_filters( 'wporg_header_color_scheme', $attributes['style'] );

$container_class .= get_container_classes( $color_scheme );

$search_args = array(
	'label' => _x( 'Search', 'button label', 'wporg' ),
	'placeholder' => _x( 'Search WP.org...', 'input field placeholder', 'wporg' ),
	'buttonPosition' => 'button-inside',
	'buttonUseIcon' => true,
	'formAction' => 'https://wordpress.org/search/do-search.php',
);

/**
 * Output menu items (`navigation-link`) & submenus (`navigation-submenu`). If a submenu, recursively iterate
 * through submenu items to output links.
 *
 * @param array   $menu_item An item from the array in `get_global_menu_items` or `get_rosetta_menu_items`.
 * @param boolean $top_level Whether the menu item is a top-level link.
 * @return string
 */
function recursive_menu( $menu_item, $top_level = true ) {
	$has_submenu = ! empty( $menu_item['submenu'] );

	if ( ! $has_submenu ) {
		return sprintf(
			'<!-- wp:navigation-link {"label":"%1$s","url":"%2$s","kind":"%3$s","isTopLevelLink":%4$s,"className":"%5$s"} /-->',
			$menu_item['title'],
			$menu_item['url'],
			$menu_item['type'],
			$top_level ? 'true' : 'false',
			$menu_item['classes'] ?? '',
		);
	}

	$output = sprintf(
		'<!-- wp:navigation-submenu {"label":"%1$s","url":"%2$s","kind":"%3$s","className":"%4$s"} -->',
		$menu_item['title'],
		$menu_item['url'],
		$menu_item['type'],
		$menu_item['classes'] ?? '',
	);

	foreach ( $menu_item['submenu'] as $submenu_item ) {
		$output .= recursive_menu( $submenu_item, false );
	}

	$output .= '<!-- /wp:navigation-submenu -->';

	return $output;
}

?>

<!-- wp:group {"tagName":"header","align":"full","className":"<?php echo esc_attr( $container_class ); ?>"} -->
<header class="wp-block-group alignfull <?php echo esc_attr( $container_class ); ?>">
	<!-- wp:html -->
	<!-- The design calls for two logos, a small "mark" on mobile/tablet, and the full logo for desktops. -->
		<figure class="wp-block-image global-header__wporg-logo-mark">
			<a href="<?php echo esc_url( get_home_url() ); ?>">
				<?php require __DIR__ . '/images/w-mark.svg'; ?>
			</a>
		</figure>

		<figure class="wp-block-image global-header__wporg-logo-full">
			<a href="<?php echo esc_url( get_home_url() ); ?>">
				<?php require __DIR__ . '/images/wporg-logo.svg'; ?>
			</a>
		</figure>
	<!-- /wp:html -->

	<?php if ( ! empty( $locale_title ) ) : ?>
	<!-- wp:paragraph {"className":"global-header__wporg-locale-title"} -->
	<p class="global-header__wporg-locale-title">
		<span><?php echo esc_html( $locale_title ); ?></span>
	</p>
	<!-- /wp:paragraph -->
	<?php endif; ?>

	<!-- wp:navigation {"orientation":"horizontal","className":"global-header__navigation","overlayMenu":"mobile","openSubmenusOnClick":false} -->
		<?php
		/*
		 * Loop though menu items and create navigation item blocks. Recurses through any submenu items to output dropdowns.
		 */
		foreach ( $menu_items as $item ) {
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo recursive_menu( $item );
		}
		?>
	<!-- /wp:navigation -->

	<?php if ( $show_search ) : ?>
	<!--
		The search block is inside a navigation menu because that provides the exact functionality the design
		calls for. It also provides a consistent experience with the primary navigation menu, with respect to
		keyboard navigation, ARIA states, etc. It also saves having to write custom code for all the interactions.
	-->
	<!-- wp:navigation {"orientation":"vertical","className":"global-header__search","overlayMenu":"always"} -->
		<!-- wp:search <?php echo wp_json_encode( $search_args ); ?> /-->
	<!-- /wp:navigation -->
	<?php endif; ?>

	<!-- This is the first of two Get WordPress buttons; the other is in the navigation menu.
		 Two are needed because they have different DOM hierarchies at different breakpoints. -->
	<!-- wp:group {"className":"global-header__desktop-get-wordpress-container"} -->
	<div class="global-header__desktop-get-wordpress-container">
		<a href="<?php echo esc_url( get_download_url() ); ?>" class="global-header__desktop-get-wordpress global-header__get-wordpress">
			<?php echo esc_html_x( 'Get WordPress', 'link anchor text', 'wporg' ); ?>
		</a>
	</div> <!-- /wp:group -->
</header> <!-- /wp:group -->
