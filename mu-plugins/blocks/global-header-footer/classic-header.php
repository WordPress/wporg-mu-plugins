<?php

namespace WordPressdotorg\MU_Plugins\Global_Header_Footer\Header;

use function WordPressdotorg\MU_Plugins\Global_Header_Footer\{ get_global_styles };

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
			<?php echo get_global_styles(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		</style>

		<?php

		wp_head();

		/*
		 * Normally GlotPress only calls `gp_head()`, not `wp_head()`. We're intentionally calling both, because
		 * we need to output the global styles from Core, scripts for the Navigation and Global Header blocks, etc.
		 * Without those, the global header and footer won't work properly.
		 *
		 * This won't be necessary once GlotPress transitions to use standard WP themes.
		 * See https://github.com/GlotPress/GlotPress-WP/issues/8
		 */
		if ( function_exists( 'gp_head' ) ) {
			gp_head();
		}

		?>
	</head>

	<body <?php body_class(); ?>>
		<?php wp_body_open();
