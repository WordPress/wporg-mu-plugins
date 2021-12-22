<?php

namespace WordPressdotorg\MU_Plugins\Global_Header_Footer\Footer;

defined( 'WPINC' ) || die();

if ( function_exists( 'gp_footer' ) ) {
	gp_footer();
} else {
	wp_footer();
}

?>

	</body>
</html>
