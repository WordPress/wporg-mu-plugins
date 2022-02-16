<?php

namespace WordPressdotorg\MU_Plugins\Global_Header_Footer\Footer;
use function WordPressdotorg\MU_Plugins\Global_Header_Footer\{ get_home_url };

defined( 'WPINC' ) || die();

/**
 * Defined in `render_global_footer()`.
 *
 * @var string $locale_title
 */

?>

<!-- wp:group {"tagName":"footer","align":"full","className":"global-footer"} -->
<footer class="wp-block-group global-footer alignfull">
	<!-- wp:group {"className":"global-footer__navigation-container"} -->
	<div class="wp-block-group global-footer__navigation-container">
		<!-- wp:navigation {"orientation":"vertical","className":"global-footer__navigation-important","overlayMenu":"never"} -->
			<!-- wp:navigation-link {"label":"<?php echo esc_html_x( 'About', 'Menu item title', 'wporg' ); ?>","url":"https://wordpress.org/about/","kind":"custom","isTopLevelLink":true} /-->
			<!-- wp:navigation-link {"label":"<?php echo esc_html_x( 'News', 'Menu item title', 'wporg' ); ?>","url":"https://wordpress.org/news/","kind":"custom","isTopLevelLink":true} /-->
			<!-- wp:navigation-link {"label":"<?php echo esc_html_x( 'Hosting', 'Menu item title', 'wporg' ); ?>","url":"https://wordpress.org/hosting/","kind":"custom","isTopLevelLink":true} /-->
			<!-- wp:navigation-link {"label":"<?php echo esc_html_x( 'Donate', 'Menu item title', 'wporg' ); ?>","url":"https://wordpressfoundation.org/donate/","kind":"custom","isTopLevelLink":true} /-->
		<!-- /wp:navigation -->

		<!-- wp:navigation {"orientation":"vertical","className":"global-footer__navigation-information","overlayMenu":"never"} -->
			<!-- wp:navigation-link {"label":"<?php echo esc_html_x( 'Support', 'Menu item title', 'wporg' ); ?>","url":"https://wordpress.org/support/","kind":"custom","isTopLevelLink":true} /-->
			<!-- wp:navigation-link {"label":"<?php echo esc_html_x( 'Developers', 'Menu item title', 'wporg' ); ?>","url":"https://developer.wordpress.org/","kind":"custom","isTopLevelLink":true} /-->
			<!-- wp:navigation-link {"label":"<?php echo esc_html_x( 'Get Involved', 'Menu item title', 'wporg' ); ?>","url":"https://make.wordpress.org/","kind":"custom","isTopLevelLink":true} /-->
			<!-- wp:navigation-link {"label":"<?php echo esc_html_x( 'Learn', 'Menu item title', 'wporg' ); ?>","url":"https://learn.wordpress.org/","kind":"custom","isTopLevelLink":true} /-->
		<!-- /wp:navigation -->

		<!-- wp:navigation {"orientation":"vertical","className":"global-footer__navigation-resources","overlayMenu":"never"} -->
			<!-- wp:navigation-link {"label":"<?php echo esc_html_x( 'Showcase', 'Menu item title', 'wporg' ); ?>","url":"https://wordpress.org/showcase/","kind":"custom","isTopLevelLink":true} /-->
			<!-- wp:navigation-link {"label":"<?php echo esc_html_x( 'Plugins', 'Menu item title', 'wporg' ); ?>","url":"https://wordpress.org/plugins/","kind":"custom","isTopLevelLink":true} /-->
			<!-- wp:navigation-link {"label":"<?php echo esc_html_x( 'Themes', 'Menu item title', 'wporg' ); ?>","url":"https://wordpress.org/themes/","kind":"custom","isTopLevelLink":true} /-->
			<!-- wp:navigation-link {"label":"<?php echo esc_html_x( 'Patterns', 'Menu item title', 'wporg' ); ?>","url":"https://wordpress.org/patterns/","kind":"custom","isTopLevelLink":true} /-->
		<!-- /wp:navigation -->

		<!-- wp:navigation {"orientation":"vertical","className":"global-footer__navigation-community","overlayMenu":"never"} -->
			<!-- wp:navigation-link {"label":"<?php echo esc_html_x( 'WordCamp', 'Menu item title', 'wporg' ); ?>","url":"https://central.wordcamp.org/","kind":"custom","isTopLevelLink":true} /-->
			<!-- wp:navigation-link {"label":"<?php echo esc_html_x( 'WordPress.TV', 'Menu item title', 'wporg' ); ?>","url":"https://wordpress.tv/","kind":"custom","isTopLevelLink":true} /-->
			<!-- wp:navigation-link {"label":"<?php echo esc_html_x( 'BuddyPress', 'Menu item title', 'wporg' ); ?>","url":"https://buddypress.org/","kind":"custom","isTopLevelLink":true} /-->
			<!-- wp:navigation-link {"label":"<?php echo esc_html_x( 'bbPress', 'Menu item title', 'wporg' ); ?>","url":"https://bbpress.org/","kind":"custom","isTopLevelLink":true} /-->
		<!-- /wp:navigation -->

		<!-- wp:navigation {"orientation":"vertical","className":"global-footer__navigation-external","overlayMenu":"never"} -->
			<!-- wp:navigation-link {"label":"<?php echo esc_html_x( 'WordPress.com', 'Menu item title', 'wporg' ); ?>","url":"https://wordpress.com/?ref=wporg-footer","kind":"custom","isTopLevelLink":true} /-->
			<!-- wp:navigation-link {"label":"<?php echo esc_html_x( 'Matt', 'Menu item title', 'wporg' ); ?>","url":"https://ma.tt/","kind":"custom","isTopLevelLink":true} /-->
			<!-- wp:navigation-link {"label":"<?php echo esc_html_x( 'Privacy', 'Menu item title', 'wporg' ); ?>","url":"https://wordpress.org/about/privacy/","kind":"custom","isTopLevelLink":true} /-->
			<!-- wp:navigation-link {"label":"<?php echo esc_html_x( 'Public Code', 'Menu item title', 'wporg' ); ?>","url":"https://publiccode.eu/","kind":"custom","isTopLevelLink":true} /-->
		<!-- /wp:navigation -->
	</div> <!-- /wp:group -->

	<!-- wp:group {"className":"global-footer__logos-container"} -->
	<div class="wp-block-group global-footer__logos-container">
		<!-- wp:group {"layout":{"type":"flex","allowOrientation":false,"justifyContent":"left"}} -->
		<div class="wp-block-group">
			<!-- wp:html -->
			<!-- The design calls for two logos, a small "mark" on mobile/tablet, and the full logo for desktops. -->
				<figure class="wp-block-image global-footer__wporg-logo-mark">
					<a href="<?php echo esc_url( get_home_url() ); ?>">
						<?php require __DIR__ . '/images/w-mark.svg'; ?>
					</a>
				</figure>

				<figure class="wp-block-image global-footer__wporg-logo-full">
					<a href="<?php echo esc_url( get_home_url() ); ?>">
						<?php require __DIR__ . '/images/wporg-logo.svg'; ?>
					</a>
				</figure>
			<!-- /wp:html -->

			<?php if ( ! empty( $locale_title ) ) : ?>
			<!-- wp:paragraph {"className":"global-footer__wporg-locale-title"} -->
			<p class="global-footer__wporg-locale-title">
				<a href="https://make.wordpress.org/polyglots/teams/">
					<?php echo esc_html( $locale_title ); ?>
				</a>
			</p>
			<!-- /wp:paragraph -->
			<?php endif; ?>
		</div>
		<!-- /wp:group -->

		<!-- wp:social-links {"className":"is-style-logos-only"} -->
		<ul class="wp-block-social-links is-style-logos-only">
			<!-- wp:social-link {"url":"https://www.facebook.com/WordPress/","service":"facebook","label":"<?php echo esc_html_x( 'Visit our Facebook page', 'Menu item title', 'wporg' ); ?>"} /-->
			<!-- wp:social-link {"url":"https://twitter.com/WordPress","service":"twitter","label":"<?php echo esc_html_x( 'Visit our Twitter account', 'Menu item title', 'wporg' ); ?>"} /-->
		</ul> <!-- /wp:social-links -->

		<!-- wp:image {"width":188,"height":13,"className":"global-footer__code_is_poetry"} -->
		<figure class="wp-block-image is-resized global-footer__code_is_poetry">
			<img
				src="https://s.w.org/style/images/code-is-poetry-for-dark-bg.svg"
				alt="<?php echo esc_html_x( 'Code is Poetry', 'Image alt text', 'wporg' ); ?>"
				width="188"
				height="13"
			/>
		</figure> <!-- /wp:image -->
	</div> <!-- /wp:group -->
</footer> <!-- /wp:group -->
