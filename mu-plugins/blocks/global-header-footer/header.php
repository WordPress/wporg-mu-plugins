<?php

namespace WordPressdotorg\MU_Plugins\Global_Header_Footer\Header;

defined( 'WPINC' ) || die();

$is_fse_theme = true; // temp

// Meta tags are included automatically in FSE themes.
if ( ! $is_fse_theme ) {
	wp_head();
}

?>

<!-- wp:group {"tagName":"header","align":"full","className":"global-header"} -->
<header class="wp-block-group global-header alignfull">
	<!-- The design calls for two logos, a small "mark" on mobile/tablet, and the full logo for desktops. -->
	<!-- wp:image {"width":27,"height":27,"className":"global-header__wporg-logo-mark"} -->
	<figure class="wp-block-image is-resized global-header__wporg-logo-mark">
		<a href="https://wordpress.org/">
			<img src="https://wordpress.org/style/images/w-mark.svg" alt="" width="27" height="27" />
		</a>
	</figure>
	<!-- /wp:image -->

	<!-- wp:image {"width":160,"height":24,"className":"global-header__wporg-logo-full"} -->
	<figure class="wp-block-image is-resized global-header__wporg-logo-full">
		<a href="https://wordpress.org/">
			<img src="<?php echo plugin_dir_url( __FILE__ ) . 'images/wporg-logo.svg'; ?>" alt="" width="160" height="24" />
		</a>
	</figure>
	<!-- /wp:image -->

	<!--
		The search block is inside a navigation submenu, because that provides the exact functionality the design
		calls for. It also provides a consistent experience with the primary navigation menu, with respect to
		keyboard navigation, ARIA states, etc. It also saves having to write custom code for all the interactions.
	-->
	<!-- wp:navigation {"orientation":"vertical","className":"global-header__search","overlayMenu":"mobile"} -->
		<!-- wp:navigation-link {"label":"Search","url":"#","kind":"custom","isTopLevelLink":false} -->
			<!-- wp:html -->
			<!--
				This markup is forked from the `wp:search` block. The only reason we're not using that, is because the
				`action` URL can't be customized.

				@link https://github.com/WordPress/gutenberg/issues/35572

				The only things that changed were:

				1) The `s` parameter is renamed to `search`, because `do-search.php` requires that.
				2) The instance ID was changed to `99`, to make it likely to be unique.

				If that issue is ever resolved, we should be able to replace this with the Search block, without having
				to change any CSS.
			-->
			<form
				role="search"
				method="get"
				action="https://wordpress.org/search/do-search.php"
				class="wp-block-search__button-outside wp-block-search__text-button global-header__search-form wp-block-search"
			>
				<label for="wp-block-search__input-99" class="wp-block-search__label">Search</label>
				<div class="wp-block-search__inside-wrapper">
					<input
						type="search"
						id="wp-block-search__input-99"
						class="wp-block-search__input"
						name="search"
						value=""
						placeholder="Search WordPress.org..."
						required=""
					>
					<button type="submit" class="wp-block-search__button" aria-label="Submit search"></button>
				</div>
			</form>
			<!-- /wp:html -->
		<!-- /wp:navigation-link -->
	<!-- /wp:navigation -->

	<!-- This is the first of two Get WordPress buttons; the other is in the navigation menu.
		 Two are needed because they have different DOM hierarchies at different breakpoints. -->
	<!-- wp:group {"className":"global-header__desktop-get-wordpress-container"} -->
	<div class="global-header__desktop-get-wordpress-container">
		<a href="https://wordpress.org/download/" class="global-header__desktop-get-wordpress global-header__get-wordpress">Get WordPress</a>
	</div> <!-- /wp:group -->

	<!--
		The "..." menu is used when there isn't enough room to show all the items without wrapping. The items
		inside it need to be duplicated in the top level menu, and have their values kept in sync. Any submenu
		items should be moved to the top level (e.g., Five for the Future), to make them easier to see, and to
		make the CSS simpler.
	-->
	<!-- wp:navigation {"orientation":"horizontal","className":"global-header__navigation","overlayMenu":"mobile"} -->
		<!-- wp:navigation-link {"label":"Plugins","url":"https://wordpress.org/plugins/","kind":"custom","isTopLevelLink":true} /-->
		<!-- wp:navigation-link {"label":"Themes","url":"https://wordpress.org/themes/","kind":"custom","isTopLevelLink":true} /-->
		<!-- wp:navigation-link {"label":"Patterns","url":"https://wordpress.org/patterns/","kind":"custom","isTopLevelLink":true} /-->
		<!-- wp:navigation-link {"label":"Learn","url":"https://learn.wordpress.org/","kind":"custom","isTopLevelLink":true} /-->
		<!-- wp:navigation-link {"label":"Support","url":"https://wordpress.org/support/","kind":"custom","isTopLevelLink":false} -->
			<!-- wp:navigation-link {"label":"Documentation","url":"https://wordpress.org/support/","kind":"custom","isTopLevelLink":true} /-->
			<!-- wp:navigation-link {"label":"Forums","url":"https://wordpress.org/support/forums/","kind":"custom","isTopLevelLink":true} /-->
		<!-- /wp:navigation-link -->
		<!-- wp:navigation-link {"label":"News","url":"https://wordpress.org/news","kind":"custom","isTopLevelLink":true,"className":"current-menu-item"} /-->
		<!-- wp:navigation-link {"label":"About","url":"https://wordpress.org/about/","kind":"custom","isTopLevelLink":true} /-->
		<!-- wp:navigation-link {"label":"Get Involved","url":"https://make.wordpress.org/","kind":"custom","isTopLevelLink":false} -->
			<!-- wp:navigation-link {"label":"Five for the Future","url":"https://wordpress.org/five-for-the-future/","kind":"custom","isTopLevelLink":true} /-->
		<!-- /wp:navigation-link -->
		<!-- wp:navigation-link {"label":"Showcase","url":"https://wordpress.org/showcase/","kind":"custom","isTopLevelLink":true} /-->
		<!-- wp:navigation-link {"label":"Mobile","url":"https://wordpress.org/mobile/","kind":"custom","isTopLevelLink":true} /-->
		<!-- wp:navigation-link {"label":"Hosting","url":"https://wordpress.org/hosting/","kind":"custom","isTopLevelLink":true} /-->
		<!-- wp:navigation-link {"label":"Openverse","url":"https://wordpress.org/openverse/","kind":"custom","isTopLevelLink":true} /-->
		<!-- wp:navigation-link {"label":"Get WordPress","url":"https://wordpress.org/download/","kind":"custom","isTopLevelLink":true,"className":"global-header__mobile-get-wordpress global-header__get-wordpress"} /-->
	<!-- /wp:navigation -->
</header> <!-- /wp:group -->
