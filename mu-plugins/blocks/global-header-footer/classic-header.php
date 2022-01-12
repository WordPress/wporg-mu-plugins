<?php

namespace WordPressdotorg\MU_Plugins\Global_Header_Footer\Header;

use function WordPressdotorg\MU_Plugins\Global_Header_Footer\render_global_styles;

defined( 'WPINC' ) || die();

/*
 * `template-canvas.php` provides similar markup automatically for FSE templates, but Classic themes need it
 *  explicitly declared.
 */
?><!DOCTYPE html>
<html <?php language_attributes(); ?>>
	<head>
		<meta charset="<?php bloginfo( 'charset' ); ?>" />
		<meta name="viewport" content="width=device-width, initial-scale=1" />

		<!--
		  Output the FSE styles that the header/footer relies on.

		  Some of them may conflict with rules that the Classic theme applies to the content area. These are
		  output first, so they can be overridden if needed.
		-->
		<style id="global-styles-for-classic-themes">
			<?php render_global_styles(); ?>
		</style>

		<?php

		wp_head();

		?>
	</head>

	<body <?php body_class(); ?>>
		<?php wp_body_open();
