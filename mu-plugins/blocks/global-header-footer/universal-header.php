<?php

namespace WordPressdotorg\MU_Plugins\Global_Header_Footer\Header;

defined( 'WPINC' ) || die();

$is_fse_theme = true; // temp

// meta tags included called automaticaly in FSE themes
if ( ! $is_fse_theme ) {
	wp_head();
}

?>

<!-- wp:group {"tagName":"header","backgroundColor":"dark-strokes-grey","className":"site-header","layout":{"inherit":true}} -->
<header class="wp-block-group site-header has-dark-strokes-grey-background-color has-background"><!-- wp:image {"sizeSlug":"large"} -->
<figure class="wp-block-image size-large"><img src="https://s.w.org/style/images/wporg-logo.svg?3" alt=""/></figure>
<!-- /wp:image -->

<!-- wp:list {"style":{"elements":{"link":{"color":{"text":"var:preset|color|white"}}}}} -->
<ul class="has-link-color"><li><a href="https://wordpress.org/plugins/">Plugins</a></li><li><a href="https://wordpress.org/themes/">Themes</a></li><li><a href="https://wordpress.org/patterns/">Patterns</a></li><li><a href="https://learn.wordpress.org/">Learn</a></li><li><a href="https://wordpress.org/support/">Support</a></li><li><a href="https://wordpress.org/news/">News</a></li><li><a href="https://wordpress.org/about/">About</a></li><li><a href="https://make.wordpress.org/">Get Involved</a></li><li><a href="https://wordpress.org/showcase/">Showcase</a></li><li><a href="https://wordpress.org/mobile/">Mobile</a></li><li><a href="https://wordpress.org/hosting/">Hosting</a></li></ul>
<!-- /wp:list -->

<!-- wp:buttons -->
<div class="wp-block-buttons"><!-- wp:button {"backgroundColor":"dark-strokes-grey","style":{"border":{"radius":"0px"}},"className":"site-header__search"} -->
<div class="wp-block-button site-header__search"><a class="wp-block-button__link has-dark-strokes-grey-background-color has-background" style="border-radius:0px">Search</a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons -->

<!-- wp:buttons -->
<div class="wp-block-buttons"><!-- wp:button {"className":"site-header__get_wordpress"} -->
<div class="wp-block-button site-header__get_wordpress"><a class="wp-block-button__link">Get WordPress</a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons --></header>
<!-- /wp:group -->
