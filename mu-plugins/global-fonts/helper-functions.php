<?php

/**
 * Specify a font to be preloaded.
 *
 * @param string $fonts The font(s) to preload.
 * @param string $subsets The subset(s) to preload.
 * @return bool If the font will be preloaded.
 */
function global_fonts_preload( $fonts, $subsets = '' ) {
	return WordPressdotorg\MU_Plugins\Global_Fonts\preload_font( $fonts, $subsets );
}
