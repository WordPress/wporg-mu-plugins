<?php

namespace WordPressdotorg\MU_Plugins\Global_Header_Footer\Header;
use function WordPressdotorg\MU_Plugins\Global_Header_Footer\{ get_home_url, get_download_url };

defined( 'WPINC' ) || die();

/**
 * Defined in `render_global_header()`.
 *
 * @var array $menu_items
 */

/**
 * Defined in `render_global_header()`.
 *
 * @var string $locale_title
 */

?>

<!-- wp:group {"tagName":"header","align":"full","className":"global-header"} -->
<header class="wp-block-group global-header alignfull">
	<!-- The design calls for two logos, a small "mark" on mobile/tablet, and the full logo for desktops. -->
	<!-- wp:image {"width":27,"height":27,"className":"global-header__wporg-logo-mark"} -->
	<figure class="wp-block-image is-resized global-header__wporg-logo-mark">
		<a href="<?php echo esc_url( get_home_url() ); ?>">
			<img src="https://wordpress.org/style/images/w-mark.svg" alt="WordPress.org" width="27" height="27" />
		</a>
	</figure>
	<!-- /wp:image -->

	<!-- wp:image {"width":160,"height":24,"className":"global-header__wporg-logo-full"} -->
	<figure class="wp-block-image is-resized global-header__wporg-logo-full">
		<a href="<?php echo esc_url( get_home_url() ); ?>">
			<img src="<?php echo plugin_dir_url( __FILE__ ) . 'images/wporg-logo.svg'; ?>" alt="WordPress.org" width="160" height="24" />
		</a>
	</figure>
	<!-- /wp:image -->

	<?php if ( ! empty( $locale_title ) ) : ?>
	<!-- wp:paragraph {"className":"global-header__wporg-local-title"} -->
	<p class="global-header__wporg-local-title">
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

	<!--
		The search block is inside a navigation submenu, because that provides the exact functionality the design
		calls for. It also provides a consistent experience with the primary navigation menu, with respect to
		keyboard navigation, ARIA states, etc. It also saves having to write custom code for all the interactions.
	-->
	<!-- wp:navigation {"orientation":"vertical","className":"global-header__search","overlayMenu":"mobile"} -->
		<!-- wp:navigation-link {"label":"<?php echo esc_html_x( 'Search', 'button label', 'wporg' ); ?>","url":"#","kind":"custom","isTopLevelLink":false} -->
			<!-- wp:html -->
			<li>
				<!--
					This markup is forked from the `wp:search` block. The only reason we're not using that, is because the
					`action` URL can't be customized.

					@link https://github.com/WordPress/gutenberg/issues/35572

					The only things that changed were:

					1) The instance ID was changed to `99`, to make it likely to be unique.
					2) Internationalizing the labels. See https://github.com/WordPress/gutenberg/issues/36061 and
					   related issues for a possible future alternative.

					If that issue is ever resolved, we should be able to replace this with the Search block, without having
					to change any CSS.
				-->
				<form
					role="search"
					method="get"
					action="https://wordpress.org/search/do-search.php"
					class="wp-block-search__button-outside wp-block-search__text-button global-header__search-form wp-block-search"
				>
					<label for="wp-block-search__input-99" class="wp-block-search__label">
						<?php echo esc_html_x( 'Search', 'button label', 'wporg' ); ?>
					</label>
					<div class="wp-block-search__inside-wrapper">
						<input
							type="search"
							id="wp-block-search__input-99"
							class="wp-block-search__input"
							name="s"
							value=""
							placeholder="<?php echo esc_attr_x( 'Search WP.org...', 'input field placeholder', 'wporg' ); ?>"
							required=""
						>
						<button type="submit" class="wp-block-search__button" aria-label="<?php echo esc_attr_x( 'Submit search', 'button label', 'wporg' ); ?>"></button>
					</div>
				</form>
			</li>
			<!-- /wp:html -->
		<!-- /wp:navigation-link -->
	<!-- /wp:navigation -->

	<!-- This is the first of two Get WordPress buttons; the other is in the navigation menu.
		 Two are needed because they have different DOM hierarchies at different breakpoints. -->
	<!-- wp:group {"className":"global-header__desktop-get-wordpress-container"} -->
	<div class="global-header__desktop-get-wordpress-container">
		<a href="<?php echo esc_url( get_download_url() ); ?>" class="global-header__desktop-get-wordpress global-header__get-wordpress">
			<?php echo esc_html_x( 'Get WordPress', 'link anchor text', 'wporg' ); ?>
		</a>
	</div> <!-- /wp:group -->
</header> <!-- /wp:group -->
