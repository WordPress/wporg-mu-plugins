<?php

namespace WordPressdotorg\MU_Plugins\Global_Header_Footer\Footer;

defined( 'WPINC' ) || die();

?>

<!-- wp:group {"tagName":"footer","align":"full","className":"site-footer"} -->
<footer class="wp-block-group site-footer alignfull">
	<!-- wp:group {"className":"site-footer__navigation-container"} -->
	<div class="wp-block-group site-footer__navigation-container">
		<!-- wp:navigation {"orientation":"vertical","className":"site-footer__navigation-important","isResponsive":false} -->
			<!-- wp:navigation-link {"label":"About","url":"https://wordpress.org/about/","kind":"custom","isTopLevelLink":true} /-->
			<!-- wp:navigation-link {"label":"Blog","url":"https://wordpress.org/news","kind":"custom","isTopLevelLink":true} /-->
			<!-- wp:navigation-link {"label":"Hosting","url":"https://wordpress.org/hosting/","kind":"custom","isTopLevelLink":true} /-->
			<!-- wp:navigation-link {"label":"Donate","url":"https://wordpressfoundation.org/donate/","kind":"custom","isTopLevelLink":true} /-->
			<!-- wp:navigation-link {"label":"Get WordPress","url":"https://wordpress.org/download/","kind":"custom","isTopLevelLink":true} /-->
			<!-- wp:navigation-link {"label":"Mobile","url":"https://wordpress.org/mobile/","kind":"custom","isTopLevelLink":true} /-->
		<!-- /wp:navigation -->

		<!-- wp:navigation {"orientation":"vertical","className":"site-footer__navigation-information","isResponsive":false} -->
			<!-- wp:navigation-link {"label":"Support","url":"https://wordpress.org/support/","kind":"custom","isTopLevelLink":true} /-->
			<!-- wp:navigation-link {"label":"Developers","url":"https://developer.wordpress.org/","kind":"custom","isTopLevelLink":true} /-->
			<!-- wp:navigation-link {"label":"Get Involved","url":"https://make.wordpress.org/","kind":"custom","isTopLevelLink":true} /-->
			<!-- wp:navigation-link {"label":"Learn","url":"https://learn.wordpress.org/","kind":"custom","isTopLevelLink":true} /-->
		<!-- /wp:navigation -->

		<!-- wp:navigation {"orientation":"vertical","className":"site-footer__navigation-resources","isResponsive":false} -->
			<!-- wp:navigation-link {"label":"Showcase","url":"https://wordpress.org/showcase/","kind":"custom","isTopLevelLink":true} /-->
			<!-- wp:navigation-link {"label":"Plugins","url":"https://wordpress.org/plugins/","kind":"custom","isTopLevelLink":true} /-->
			<!-- wp:navigation-link {"label":"Themes","url":"https://wordpress.org/themes/","kind":"custom","isTopLevelLink":true} /-->
			<!-- wp:navigation-link {"label":"Patterns","url":"https://wordpress.org/patterns/","kind":"custom","isTopLevelLink":true} /-->
			<!-- wp:navigation-link {"label":"Openverse","url":"https://wordpress.org/openverse/","kind":"custom","isTopLevelLink":true} /-->
		<!-- /wp:navigation -->

		<!-- wp:navigation {"orientation":"vertical","className":"site-footer__navigation-community","isResponsive":false} -->
			<!-- wp:navigation-link {"label":"WordCamp","url":"https://central.wordcamp.org/","kind":"custom","isTopLevelLink":true} /-->
			<!-- wp:navigation-link {"label":"WordPress.TV","url":"https://wordpress.tv/","kind":"custom","isTopLevelLink":true} /-->
			<!-- wp:navigation-link {"label":"BuddyPress","url":"https://buddypress.org/","kind":"custom","isTopLevelLink":true} /-->
			<!-- wp:navigation-link {"label":"bbPress","url":"https://bbpress.org/","kind":"custom","isTopLevelLink":true} /-->
		<!-- /wp:navigation -->

		<!-- wp:navigation {"orientation":"vertical","className":"site-footer__navigation-external","isResponsive":false} -->
			<!-- wp:navigation-link {"label":"WordPress.com","url":"https://wordpress.com/?ref=wporg-footer","kind":"custom","isTopLevelLink":true} /-->
			<!-- wp:navigation-link {"label":"Matt","url":"https://ma.tt/","kind":"custom","isTopLevelLink":true} /-->
			<!-- wp:navigation-link {"label":"Privacy","url":"https://wordpress.org/about/privacy/","kind":"custom","isTopLevelLink":true} /-->
			<!-- wp:navigation-link {"label":"Public Code","url":"https://publiccode.eu/","kind":"custom","isTopLevelLink":true} /-->
		<!-- /wp:navigation -->
	</div> <!-- /wp:group -->

	<!-- wp:group {"className":"site-footer__logos-container"} -->
	<div class="wp-block-group site-footer__logos-container">
		<!-- The design calls for two logos, a small "mark" on mobile/tablet, and the full logo for desktops. -->
		<!-- wp:image {"width":27,"height":27,"className":"site-footer__wporg-logo-mark"} -->
		<figure class="wp-block-image is-resized site-footer__wporg-logo-mark">
			<img src="https://wordpress.org/style/images/w-mark.svg" alt="" width="27" height="27" />
		</figure>
		<!-- /wp:image -->

		<!-- wp:image {"width":160,"height":24,"className":"site-footer__wporg-logo-full"} -->
		<figure class="wp-block-image is-resized site-footer__wporg-logo-full">
			<img src="https://wordpress.org/style/images/wporg-logo.svg?3" alt="" width="160" height="24" />
		</figure>
		<!-- /wp:image -->

		<!-- wp:social-links {"className":"is-style-logos-only"} -->
		<ul class="wp-block-social-links is-style-logos-only">
			<!-- wp:social-link {"url":"https://www.facebook.com/WordPress/","service":"facebook","label":"Visit our Facebook page"} /-->
			<!-- wp:social-link {"url":"https://twitter.com/WordPress","service":"twitter","label":"Visit our Twitter account"} /-->
		</ul> <!-- /wp:social-links -->

		<!-- wp:image {"width":188,"height":13,"className":"site-footer__code_is_poetry"} -->
		<figure class="wp-block-image is-resized site-footer__code_is_poetry">
			<img
				src="https://s.w.org/style/images/code-is-poetry-for-dark-bg.svg"
				alt=""
				width="188"
				height="13"
			/>
		</figure> <!-- /wp:image -->
	</div> <!-- /wp:group -->
</footer> <!-- /wp:group -->
