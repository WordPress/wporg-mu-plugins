<?php

namespace WordPressdotorg\MU_Plugins\Global_Header_Footer\Header;

defined( 'WPINC' ) || die();

$is_fse_theme = true; // temp

// Meta tags are included automatically in FSE themes.
if ( ! $is_fse_theme ) {
	wp_head();
}

?>

<!-- wp:group {"tagName":"header","className":"site-header"} -->
<header class="wp-block-group site-header">
	<!-- The design calls for two logos, a small "mark" on mobile/tablet, and the full logo for desktops. -->
	<!-- wp:image {"width":27,"height":27,"className":"site-header__wporg-logo-mark"} -->
	<figure class="wp-block-image is-resized site-header__wporg-logo-mark">
		<a href="https://wordpress.org/">
			<img src="https://wordpress.org/style/images/w-mark.svg" alt="" width="27" height="27" />
		</a>
	</figure>
	<!-- /wp:image -->

	<!-- wp:image {"width":160,"height":24,"className":"site-header__wporg-logo-full"} -->
	<figure class="wp-block-image is-resized site-header__wporg-logo-full">
		<a href="https://wordpress.org/">
			<img src="https://wordpress.org/style/images/wporg-logo.svg?3" alt="" width="160" height="24" />
		</a>
	</figure>
	<!-- /wp:image -->

	<!-- wp:group {"className":"site-header__search-container"} -->
	<div class="wp-block-group site-header__search-container">
		<!-- wp:html -->
		<button
			aria-haspopup="true"
			aria-expanded="false"
			aria-label="Open search"
			class="site-header__open-search"
		>
			<img
				src="<?php echo esc_url( plugins_url( '/images/search.svg', __FILE__ ) ); ?>"
				alt=""
				width="18"
				height="17"
			/>
		</button>

		<button
			aria-haspopup="false"
			aria-expanded="false"
			aria-label="Open search"
			class="site-header__close-search"
		>
			<img
				src="<?php echo esc_url( plugins_url( '/images/close.svg', __FILE__ ) ); ?>"
				alt=""
				width="21"
				height="21"
			/>
		</button>
		<!-- /wp:html -->

		<!-- wp:search {"className":"site-header__search-form","label":"Search","placeholder":"Search WordPress.org...","buttonText":"Submit search"} /-->
	</div> <!-- /wp:group -->

	<!-- This is the first of two Get WordPress buttons; the other is in the navigation menu.
		 Two are needed because they have different DOM hierarchies at different breakpoints. -->
	<!-- wp:group {"className":"site-header__desktop-get-wordpress-container"} -->
	<div class="site-header__desktop-get-wordpress-container">
		<a href="https://wordpress.org/download/" class="site-header__desktop-get-wordpress site-header__get-wordpress">Get WordPress</a>
	</div> <!-- /wp:group -->

	<!--
		The "..." menu is used when there isn't enough room to show all the items without wrapping. The items
		inside it need to be duplicated in the top level menu, and have their values kept in sync. Any submenu
		items should be moved to the top level (e.g., Five for the Future), to make them easier to see, and to
		make the CSS simpler.
	-->
	<!-- wp:navigation {"orientation":"horizontal","className":"site-header__navigation","isResponsive":true} -->
		<!-- wp:navigation-link {"label":"Plugins","url":"https://wordpress.org/plugins/","kind":"custom","isTopLevelLink":true} /-->
		<!-- wp:navigation-link {"label":"Themes","url":"https://wordpress.org/themes/","kind":"custom","isTopLevelLink":true} /-->
		<!-- wp:navigation-link {"label":"Patterns","url":"https://wordpress.org/patterns/","kind":"custom","isTopLevelLink":true} /-->
		<!-- wp:navigation-link {"label":"Learn","url":"https://learn.wordpress.org/","kind":"custom","isTopLevelLink":true} /-->
		<!-- wp:navigation-link {"label":"Support","url":"https://wordpress.org/support/","kind":"custom","isTopLevelLink":false} -->
			<!-- wp:navigation-link {"label":"Documentation","url":"https://wordpress.org/support/","kind":"custom","isTopLevelLink":true} /-->
			<!-- wp:navigation-link {"label":"Forums","url":"https://wordpress.org/support/forums/","kind":"custom","isTopLevelLink":true} /-->
		<!-- /wp:navigation-link -->
		<!-- wp:navigation-link {"label":"News","url":"https://wordpress.org/news","kind":"custom","isTopLevelLink":true,"className":"current-menu-item"} /-->
		<!-- wp:navigation-link {"label":"About","url":"https://wordpress.org/about/","kind":"custom","isTopLevelLink":true,"className":"site-header__overflow-item"} /-->
		<!-- wp:navigation-link {"label":"Get Involved","url":"https://make.wordpress.org/","kind":"custom","isTopLevelLink":false,"className":"site-header__overflow-item"} -->
			<!-- wp:navigation-link {"label":"Five for the Future","url":"https://wordpress.org/five-for-the-future/","kind":"custom","isTopLevelLink":true} /-->
		<!-- /wp:navigation-link -->
		<!-- wp:navigation-link {"label":"Showcase","url":"https://wordpress.org/showcase/","kind":"custom","isTopLevelLink":true,"className":"site-header__overflow-item"} /-->
		<!-- wp:navigation-link {"label":"Mobile","url":"https://wordpress.org/mobile/","kind":"custom","isTopLevelLink":true,"className":"site-header__overflow-item"} /-->
		<!-- wp:navigation-link {"label":"Hosting","url":"https://wordpress.org/hosting/","kind":"custom","isTopLevelLink":true,"className":"site-header__overflow-item"} /-->
		<!-- wp:navigation-link {"label":"Openverse","url":"https://wordpress.org/openverse/","kind":"custom","isTopLevelLink":true,"className":"site-header__overflow-item"} /-->
		<!-- wp:navigation-link {"label":"...","url":"#","kind":"custom","isTopLevelLink":false,"className":"site-header__overflow-menu"} -->
			<!-- wp:navigation-link {"label":"About","url":"https://wordpress.org/about/","kind":"custom","isTopLevelLink":true} /-->
			<!-- wp:navigation-link {"label":"Get Involved","url":"https://make.wordpress.org/","kind":"custom","isTopLevelLink":true} /-->
			<!-- wp:navigation-link {"label":"Five for the Future","url":"https://wordpress.org/five-for-the-future/","kind":"custom","isTopLevelLink":true} /-->
			<!-- wp:navigation-link {"label":"Showcase","url":"https://wordpress.org/showcase/","kind":"custom","isTopLevelLink":true} /-->
			<!-- wp:navigation-link {"label":"Mobile","url":"https://wordpress.org/mobile/","kind":"custom","isTopLevelLink":true} /-->
			<!-- wp:navigation-link {"label":"Hosting","url":"https://wordpress.org/hosting/","kind":"custom","isTopLevelLink":true} /-->
			<!-- wp:navigation-link {"label":"Openverse","url":"https://wordpress.org/openverse/","kind":"custom","isTopLevelLink":true} /-->
		<!-- /wp:navigation-link -->
		<!-- wp:navigation-link {"label":"Get WordPress","url":"https://wordpress.org/download/","kind":"custom","isTopLevelLink":true,"className":"site-header__mobile-get-wordpress site-header__get-wordpress"} /-->
	<!-- /wp:navigation -->
</header> <!-- /wp:group -->
