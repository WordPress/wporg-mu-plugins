<?php
/**
 * Tweaks for specific plugins on WordPress.org.
 *
 * Each plugin gets its own file or subdirectory in the `plugin-tweaks` directory.
 */

namespace WordPressdotorg\MU_Plugins\Plugin_Tweaks;

defined( 'WPINC' ) || die();

require_once __DIR__ . '/wporg-internal-notes.php';
require_once __DIR__ . '/stream.php';
require_once __DIR__ . '/incompatible-plugins.php';
require_once __DIR__ . '/gutenberg.php';
require_once __DIR__ . '/youtube-shortcode.php';
