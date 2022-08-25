<?php

/**
 * Specify a font to be preloaded.
 *
 * @param array|string $font_faces The font(s) to preload.
 * @return bool If the font will be preloaded.
 */
function global_fonts_preload( $font_faces ) {
	return WordPressdotorg\MU_Plugins\Global_Fonts\preload_font( $font_faces );
}
