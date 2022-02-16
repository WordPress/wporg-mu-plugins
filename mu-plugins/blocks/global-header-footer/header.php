<?php

namespace WordPressdotorg\MU_Plugins\Global_Header_Footer\Header;
use function WordPressdotorg\MU_Plugins\Global_Header_Footer\{ get_home_url, get_download_url };

defined( 'WPINC' ) || die();

/**
 * Defined in `render_global_header()`.
 *
 * @var array  $menu_items
 * @var string $locale_title
 * @var string $show_search
 */

$container_class = 'global-header';
if ( ! empty( $locale_title ) ) {
	$container_class .= ' global-header__has-locale-title';
}

$search_args = array(
	'label' => _x( 'Search', 'button label', 'wporg' ),
	'placeholder' => _x( 'Search WP.org...', 'input field placeholder', 'wporg' ),
	'buttonPosition' => 'button-inside',
	'buttonUseIcon' => true,
	'formAction' => 'https://wordpress.org/search/do-search.php',
);

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

	<!-- wp:navigation {"orientation":"horizontal","className":"global-header__navigation","overlayMenu":"mobile"} -->
		<?php

		/*
		 * Loop though menu items and create `navigation-link` block comments.
		 *
		 * This only supports 1 level deep, but that's currently enough for our needs. More than that could be an
		 * information architecture smell anyways.
		 */
		foreach ( $menu_items as $item ) {
			$is_top_level_link = empty( $item['submenu'] );

			printf(
				'<!-- wp:navigation-link {"label":"%s","url":"%s","kind":"%s","isTopLevelLink":%s,"className":"%s"} %s-->',
				// These sometimes come from user input (like with Rosetta menus), but `render_block_core_navigation_link()` will escape the values.
				$item['title'],
				$item['url'],
				$item['type'],
				json_encode( $is_top_level_link ),
				$item['classes'] ?? '',
				$is_top_level_link ? '/' : ''
			);

			if ( ! $is_top_level_link ) {
				foreach( $item['submenu'] as $submenu_item ) {
					printf(
						'<!-- wp:navigation-link {"label":"%s","url":"%s","kind":"%s","isTopLevelLink":true,"className":"%s"} /-->',
						$submenu_item['title'],
						$submenu_item['url'],
						$submenu_item['type'],
						$submenu_item['classes'] ?? '',
					);
				}

				echo '<!-- /wp:navigation-link -->';
			}
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
