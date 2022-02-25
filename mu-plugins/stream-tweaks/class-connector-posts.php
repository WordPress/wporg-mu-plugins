<?php
/**
 * Connector for Posts
 *
 * This is a hotfix for the Stream plugin to enable logging post changes that happen through an API context (i.e.
 * in the block editor). This change has been merged in the GitHub repo for the Stream plugin, but as of version
 * 3.8.2 it has not been released to the plugin directory.
 */

namespace WordPressdotorg\MU_Plugins\Stream_Tweaks;

/**
 * Class - Connector_Posts
 */
class Connector_Posts extends \WP_Stream\Connector_Posts {
	/**
	 * Register connector in the WP Frontend
	 *
	 * @var bool
	 */
	public $register_frontend = true;
}
