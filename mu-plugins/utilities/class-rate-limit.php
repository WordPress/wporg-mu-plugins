<?php
namespace WordPressdotorg\MU_Plugins\Utilities;

defined( 'WPINC' ) || die();

/**
 * Class Rate_Limit
 *
 * Helps limiting an event to $limit occurrences per $interval seconds, by using wp_cache.
 *
 * On WordPress.org's infrastructure, the cache group is registered as a "no-remote" group via
 * `wp_cache_add_no_remote_groups()` so that counters stay local to each Memcached server. This
 * keeps the limit per-DC, avoiding cross-DC replication and ensuring a fault on one DC doesn't
 * disable functionality everywhere.
 *
 * Usage:
 *
 *     use WordPressdotorg\MU_Plugins\Utilities\Rate_Limit;
 *
 *     $ratelimit = new Rate_Limit( 'bozo_behavior' );
 *
 *     if ( $ratelimit->is_ok() ) {
 *         // ...do the thing...
 *         if ( is_wp_error( $whatever ) ) {
 *             $ratelimit->bump();
 *             return;
 *         }
 *     }
 *
 * ⚠️ This class is used in multiple locations in the WordPress/WordCamp ecosystem. If you make
 * changes to this file, make sure they are tested everywhere.
 *
 * @package WordPressdotorg\MU_Plugins\Utilities
 */
class Rate_Limit {
	private const CACHE_GROUP = 'rate-limit';

	/**
	 * @var string The wp_cache key used to store the counter.
	 */
	private readonly string $cache_key;

	/**
	 * @var int The time window, in seconds, that the counter is valid for.
	 */
	public int $interval;

	/**
	 * @var int The maximum number of occurrences allowed within the interval.
	 */
	public int $limit;

	/**
	 * @var bool Whether to register the cache group as a no-remote group, keeping the counter local
	 *           to each Memcached server (per-DC limiting on WordPress.org).
	 */
	public bool $no_mc_remote;

	/**
	 * Rate_Limit constructor.
	 *
	 * @param string $cache_key    A unique identifier for the event being limited.
	 * @param int    $interval     The time window, in seconds. Default 60.
	 * @param int    $limit        The maximum number of occurrences allowed within the interval. Default 10.
	 * @param bool   $no_mc_remote Whether to keep the cache group local to each Memcached server. Default true.
	 */
	public function __construct( string $cache_key, int $interval = 60, int $limit = 10, bool $no_mc_remote = true ) {
		$this->cache_key    = $cache_key;
		$this->interval     = $interval;
		$this->limit        = $limit;
		$this->no_mc_remote = $no_mc_remote;

		$this->set_cache_group_settings();
	}

	/**
	 * Register the cache group as global and, when supported, as a no-remote group.
	 */
	protected function set_cache_group_settings(): void {
		wp_cache_add_global_groups( array( self::CACHE_GROUP ) );

		// The limit will be per-Memcached-server so that we don't replicate, and so we don't turn
		// off parts of code that are broken in one DC only.
		if ( $this->no_mc_remote && function_exists( 'wp_cache_add_no_remote_groups' ) ) {
			wp_cache_add_no_remote_groups( array( self::CACHE_GROUP ) );
		}
	}

	/**
	 * Bump the counter by $num.
	 *
	 * The first bump within an interval seeds the counter (and a first-bump timestamp) with the
	 * configured TTL. Subsequent bumps within the interval increment the counter but do not
	 * reset its expiration.
	 *
	 * @param int $num The amount to bump by. Default 1.
	 * @return int|true The new counter value on increment, true on initial add, or false on failure.
	 */
	public function bump( int $num = 1 ): int|bool {
		// If it already exists, then we won't create it.
		$data_added = wp_cache_add( $this->cache_key, $num, self::CACHE_GROUP, $this->interval );
		if ( $data_added ) {
			// Store the time of our initial bump. Useful for some derivations.
			wp_cache_add( $this->cache_key . '-first-bump-time', time(), self::CACHE_GROUP, $this->interval );

			return true;
		}

		// Increments do not reset the expiration.
		return wp_cache_incr( $this->cache_key, $num, self::CACHE_GROUP );
	}

	/**
	 * Whether the current count is still within the configured limit.
	 *
	 * @return bool True if the event can still proceed, false if the limit has been exceeded.
	 */
	public function is_ok(): bool {
		return $this->limit >= $this->get_count();
	}

	/**
	 * Get the current counter value, forcing a fresh read from Memcached.
	 */
	public function get_count(): int {
		return (int) wp_cache_get( $this->cache_key, self::CACHE_GROUP, true );
	}

	/**
	 * Get the timestamp of the first bump in the current interval.
	 *
	 * @return int Unix timestamp, or 0 if no bump has occurred (or it has expired).
	 */
	public function get_first_bump_time(): int {
		return (int) wp_cache_get( $this->cache_key . '-first-bump-time', self::CACHE_GROUP, true );
	}

	/**
	 * Get the number of bumps remaining before the limit is reached.
	 */
	public function get_remaining(): int {
		return $this->limit - $this->get_count();
	}

	/**
	 * Reset the counter, allowing the event to occur again immediately.
	 *
	 * @return bool True on success, false on failure.
	 */
	public function clear(): bool {
		return wp_cache_delete( $this->cache_key, self::CACHE_GROUP );
	}
}
