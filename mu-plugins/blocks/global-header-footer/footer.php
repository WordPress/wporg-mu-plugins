<?php

namespace WordPressdotorg\MU_Plugins\Global_Header_Footer\Footer;

use function WordPressdotorg\MU_Plugins\Global_Header_Footer\{ get_cip_text, get_home_url, get_container_classes, is_rosetta_site, get_localized_link };

defined( 'WPINC' ) || die();

/**
 * Defined in `render_global_footer()`.
 *
 * @var array  $attributes
 * @var string $locale_title
 */

$container_class = 'global-footer';

?>

<!-- wp:group {"tagName":"nav","align":"full","style":{"spacing":{"blockGap":"var:preset|spacing|20"}},"className":"global-footer__navigation-container","layout":{"type":"grid","minimumColumnWidth":"150px"}} -->
<nav class="wp-block-group alignfull global-footer__navigation-container" aria-label="<?php esc_html_e( 'Footer', 'wporg' ); ?>">
	<!-- wp:list -->
	<ul>
		<!-- wp:list-item -->
		<li><a href="<?php echo esc_url( get_localized_link( 'https://wordpress.org/about/' ) ); ?>"><?php echo esc_html_x( 'About', 'Menu item title', 'wporg' ); ?></a></li>
		<!-- /wp:list-item -->
		<!-- wp:list-item -->
		<li><a href="<?php echo esc_url( get_localized_link( 'https://wordpress.org/news/' ) ); ?>"><?php echo esc_html_x( 'News', 'Menu item title', 'wporg' ); ?></a></li>
		<!-- /wp:list-item -->
		<!-- wp:list-item -->
		<li><a href="<?php echo esc_url( get_localized_link( 'https://wordpress.org/hosting/' ) ); ?>"><?php echo esc_html_x( 'Hosting', 'Menu item title', 'wporg' ); ?></a></li>
		<!-- /wp:list-item -->
		<!-- wp:list-item -->
		<li><a href="<?php echo esc_url( get_localized_link( 'https://wordpress.org/about/privacy/' ) ); ?>"><?php echo esc_html_x( 'Privacy', 'Menu item title', 'wporg' ); ?></a></li>
		<!-- /wp:list-item -->
	</ul>
	<!-- /wp:list -->

	<!-- wp:list -->
	<ul>
		<!-- wp:list-item -->
		<li><a href="<?php echo esc_url( get_localized_link( 'https://wordpress.org/showcase/' ) ); ?>"><?php echo esc_html_x( 'Showcase', 'Menu item title', 'wporg' ); ?></a></li>
		<!-- /wp:list-item -->
		<!-- wp:list-item -->
		<li><a href="<?php echo esc_url( get_localized_link( 'https://wordpress.org/themes/' ) ); ?>"><?php echo esc_html_x( 'Themes', 'Menu item title', 'wporg' ); ?></a></li>
		<!-- /wp:list-item -->
		<!-- wp:list-item -->
		<li><a href="<?php echo esc_url( get_localized_link( 'https://wordpress.org/plugins/' ) ); ?>"><?php echo esc_html_x( 'Plugins', 'Menu item title', 'wporg' ); ?></a></li>
		<!-- /wp:list-item -->
		<!-- wp:list-item -->
		<li><a href="<?php echo esc_url( get_localized_link( 'https://wordpress.org/patterns/' ) ); ?>"><?php echo esc_html_x( 'Patterns', 'Menu item title', 'wporg' ); ?></a></li>
		<!-- /wp:list-item -->
	</ul>
	<!-- /wp:list -->

	<!-- wp:list -->
	<ul>
		<!-- wp:list-item -->
		<li><a href="<?php echo esc_url( get_localized_link( 'https://learn.wordpress.org/' ) ); ?>"><?php echo esc_html_x( 'Learn', 'Menu item title', 'wporg' ); ?></a></li>
		<!-- /wp:list-item -->
		<?php if ( is_rosetta_site() ) { ?>
			<!-- wp:list-item -->
			<li><a href="<?php echo esc_url( get_localized_link( 'https://wordpress.org/support/' ) ); ?>"><?php echo esc_html_x( 'Support', 'Menu item title', 'wporg' ); ?></a></li>
			<!-- /wp:list-item -->
		<?php } else { ?>
			<!-- wp:list-item -->
			<li><a href="https://wordpress.org/documentation/"><?php echo esc_html_x( 'Documentation', 'Menu item title', 'wporg' ); ?></a></li>
			<!-- /wp:list-item -->
		<?php } ?>
		<!-- wp:list-item -->
		<li><a href="<?php echo esc_url( get_localized_link( 'https://developer.wordpress.org/' ) ); ?>"><?php echo esc_html_x( 'Developers', 'Menu item title', 'wporg' ); ?></a></li>
		<!-- /wp:list-item -->
		<!-- wp:list-item -->
		<li><a href="https://wordpress.tv/"><?php echo esc_html_x( 'WordPress.tv ↗', 'Menu item title', 'wporg' ); ?></a></li>
		<!-- /wp:list-item -->
	</ul>
	<!-- /wp:list -->

	<!-- wp:list -->
	<ul>
		<!-- wp:list-item -->
		<li><a href="https://make.wordpress.org/"><?php echo esc_html_x( 'Get Involved', 'Menu item title', 'wporg' ); ?></a></li>
		<!-- /wp:list-item -->
		<!-- wp:list-item -->
		<li><a href="https://events.wordpress.org/"><?php echo esc_html_x( 'Events', 'Menu item title', 'wporg' ); ?></a></li>
		<!-- /wp:list-item -->
		<!-- wp:list-item -->
		<li><a href="https://wordpressfoundation.org/donate/"><?php echo esc_html_x( 'Donate ↗', 'Menu item title', 'wporg' ); ?></a></li>
		<!-- /wp:list-item -->
		<!-- wp:list-item -->
		<li><a href="https://mercantile.wordpress.org/"><?php echo esc_html_x( 'Swag ↗', 'Menu item title', 'wporg' ); ?></a></li>
		<!-- /wp:list-item -->
	</ul>
	<!-- /wp:list -->

	<!-- wp:list -->
	<ul>
		<!-- wp:list-item -->
		<li><a href="https://wordpress.com/?ref=wporg-footer"><?php echo esc_html_x( 'WordPress.com ↗', 'Menu item title', 'wporg' ); ?></a></li>
		<!-- /wp:list-item -->
		<!-- wp:list-item -->
		<li><a href="https://ma.tt/"><?php echo esc_html_x( 'Matt ↗', 'Menu item title', 'wporg' ); ?></a></li>
		<!-- /wp:list-item -->
		<!-- wp:list-item -->
		<li><a href="https://bbpress.org/"><?php echo esc_html_x( 'bbPress ↗', 'Menu item title', 'wporg' ); ?></a></li>
		<!-- /wp:list-item -->
		<!-- wp:list-item -->
		<li><a href="https://buddypress.org/"><?php echo esc_html_x( 'BuddyPress ↗', 'Menu item title', 'wporg' ); ?></a></li>
		<!-- /wp:list-item -->
	</ul>
	<!-- /wp:list -->
</nav>
<!-- /wp:group -->

<!-- wp:group {"className":"global-footer__logos-container"} -->
<div class="wp-block-group global-footer__logos-container">
	<!-- wp:group {"className":"global-footer__wporg-container"} -->
	<div class="wp-block-group global-footer__wporg-container">
		<!-- wp:group {"layout":{"type":"flex","allowOrientation":false,"justifyContent":"left","flexWrap":"nowrap"}} -->
		<div class="wp-block-group">
			<!-- wp:html -->
			<figure class="wp-block-image global-footer__wporg-logo-full">
				<a href="<?php echo esc_url( get_home_url() ); ?>">
					<?php require __DIR__ . '/images/wporg-logo.svg'; ?>
				</a>
			</figure>
			<!-- /wp:html -->

			<?php if ( ! empty( $locale_title ) ) : ?>
			<!-- wp:paragraph {"className":"global-footer__wporg-locale-title"} -->
			<p class="global-footer__wporg-locale-title">
				<a href="<?php echo esc_url( add_query_arg( 'locale', get_locale(), 'https://make.wordpress.org/polyglots/teams/' ) ); ?>">
					<?php echo esc_html( $locale_title ); ?>
				</a>
			</p>
			<!-- /wp:paragraph -->
			<?php endif; ?>
		</div>
		<!-- /wp:group -->

		<!-- wp:paragraph {"className":"global-footer__trademark"} -->
		<p class="global-footer__trademark">
			<?php echo 'The WordPress&reg; trademark is the intellectual property of the WordPress Foundation.'; ?>
		</p>
		<!-- /wp:paragraph -->
	</div>
	<!-- /wp:group -->

	<!-- wp:social-links {"className":"is-style-logos-only"} -->
	<ul class="wp-block-social-links is-style-logos-only">
		<!-- wp:social-link {"url":"https://www.x.com/WordPress","service":"x","label":"<?php echo esc_html_x( 'Visit our X (formerly Twitter) account', 'Menu item title', 'wporg' ); ?>"} /-->
		<!-- wp:social-link {"url":"https://bsky.app/profile/wordpress.org","service":"bluesky","label":"<?php echo esc_html_x( 'Visit our Bluesky account', 'Menu item title', 'wporg' ); ?>"} /-->
		<!-- wp:social-link {"url":"https://mastodon.world/@WordPress","service":"mastodon","rel":"me","label":"<?php echo esc_html_x( 'Visit our Mastodon account', 'Menu item title', 'wporg' ); ?>"} /-->
		<!-- wp:social-link {"url":"https://www.threads.net/@wordpress","service":"threads","label":"<?php echo esc_html_x( 'Visit our Threads account', 'Menu item title', 'wporg' ); ?>"} /-->
		<!-- wp:social-link {"url":"https://www.facebook.com/WordPress/","service":"facebook","label":"<?php echo esc_html_x( 'Visit our Facebook page', 'Menu item title', 'wporg' ); ?>"} /-->
		<!-- wp:social-link {"url":"https://www.instagram.com/wordpress/","service":"instagram","label":"<?php echo esc_html_x( 'Visit our Instagram account', 'Menu item title', 'wporg' ); ?>"} /-->
		<!-- wp:social-link {"url":"https://www.linkedin.com/company/wordpress","service":"linkedin","label":"<?php echo esc_html_x( 'Visit our LinkedIn account', 'Menu item title', 'wporg' ); ?>"} /-->
		<!-- wp:social-link {"url":"https://www.tiktok.com/@wordpress","service":"tiktok","label":"<?php echo esc_html_x( 'Visit our TikTok account', 'Menu item title', 'wporg' ); ?>"} /-->
		<!-- wp:social-link {"url":"https://www.youtube.com/wordpress","service":"youtube","label":"<?php echo esc_html_x( 'Visit our YouTube channel', 'Menu item title', 'wporg' ); ?>"} /-->
		<!-- wp:social-link {"url":"https://wordpress.tumblr.com/","service":"tumblr","label":"<?php echo esc_html_x( 'Visit our Tumblr account', 'Menu item title', 'wporg' ); ?>"} /-->
	</ul> <!-- /wp:social-links -->
</div> <!-- /wp:group -->

<!-- wp:group {"className":"global-footer__code-is-poetry-container"} -->
<div class="wp-block-group global-footer__code-is-poetry-container">
	<?php if ( str_starts_with( get_locale(), 'en_' ) ) : ?>
		<!-- Inline the graphic so it can use the MrsEaves outlines and inherit the footer colour. -->
		<?php require __DIR__ . '/images/code-is-poetry.svg'; ?>
	<?php else : ?>
		<!-- Use text so it can be translated. -->
		<span class="global-footer__code_is_poetry">
			<?php echo esc_html( get_cip_text() ); ?>
		</span>
	<?php endif; ?>
</div>
<!-- /wp:group -->
