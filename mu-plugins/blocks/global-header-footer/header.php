<?php

namespace WordPressdotorg\MU_Plugins\Global_Header_Footer\Header;

defined( 'WPINC' ) || die();

$is_fse_theme = true; // temp

// Meta tags are included automatically in FSE themes.
if ( ! $is_fse_theme ) {
	wp_head();
}

?>

<!-- wp:group {"tagName":"header","className":"site-header","layout":{"inherit":true}} -->
<header class="wp-block-group site-header"><!-- wp:image {"width":160,"height":24,"className":"site-header__wporg-logo"} -->
<figure class="wp-block-image is-resized site-header__wporg-logo"><img src="https://s.w.org/style/images/wporg-logo.svg?3" alt="" width="160" height="24"/></figure>
<!-- /wp:image -->

<!-- wp:navigation {"orientation":"horizontal","isResponsive":true} -->
<!-- wp:navigation-link {"label":"Plugins","url":"https://wordpress.org/plugins/","kind":"custom","isTopLevelLink":true} /-->

<!-- wp:navigation-link {"label":"Themes","url":"https://wordpress.org/themes/","kind":"custom","isTopLevelLink":true} /-->

<!-- wp:navigation-link {"label":"Patterns","url":"https://wordpress.org/patterns/","kind":"custom","isTopLevelLink":true} /-->

<!-- wp:navigation-link {"label":"OpenVerse","url":"https://wordpress.org/openverse/","kind":"custom","isTopLevelLink":true} /-->

<!-- wp:navigation-link {"label":"Learn","url":"https://learn.wordpress.org/","kind":"custom","isTopLevelLink":true} /-->

<!-- wp:navigation-link {"label":"Support","url":"https://wordpress.org/support/","kind":"custom","isTopLevelLink":true} /-->

<!-- wp:navigation-link {"label":"News","url":"https://wordpress.org/news","kind":"custom","isTopLevelLink":true} /-->

<!-- wp:navigation-link {"label":"About","url":"https://wordpress.org/about/","kind":"custom","isTopLevelLink":true} /-->

<!-- wp:navigation-link {"label":"Get Involved","url":"https://make.wordpress.org/","kind":"custom","isTopLevelLink":true} /-->

<!-- wp:navigation-link {"label":"Showcase","url":"https://wordpress.org/showcase/","kind":"custom","isTopLevelLink":true} /-->

<!-- wp:navigation-link {"label":"Mobile","url":"https://wordpress.org/mobile/","kind":"custom","isTopLevelLink":true} /-->

<!-- wp:navigation-link {"label":"Hosting","url":"https://wordpress.org/hosting/","kind":"custom","isTopLevelLink":true} /-->
<!-- /wp:navigation -->

<!-- wp:buttons {"className":"site-header__action-buttons"} -->
<div class="wp-block-buttons site-header__action-buttons"><!-- wp:button {"className":"site-header__search"} -->
<div class="wp-block-button site-header__search"><a class="wp-block-button__link" href="https://wordpress.org/search/">Search</a></div>
<!-- /wp:button -->

<!-- wp:button {"className":"site-header__get_wordpress"} -->
<div class="wp-block-button site-header__get_wordpress"><a class="wp-block-button__link" href="https://wordpress.org/download/">Get WordPress</a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons --></header>
<!-- /wp:group -->
