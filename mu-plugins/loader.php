<?php

/**
 * Load mu-plugins.
 *
 * `utilities/` aren't loaded automatically since they're not used globally.
 */

require_once __DIR__ . '/blocks/global-header-footer/blocks.php';
require_once __DIR__ . '/blocks/screenshot-preview/screenshot-preview.php';
require_once __DIR__ . '/global-fonts/index.php';
require_once __DIR__ . '/plugin-tweaks/index.php';
require_once __DIR__ . '/rest-api/index.php';
require_once __DIR__ . '/skip-to/skip-to.php';
