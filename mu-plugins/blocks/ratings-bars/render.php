<?php

$current_post_id = $block->context['postId'];
if ( ! $current_post_id ) {
	return;
}

/**
 * Get the ratings data via filter, so that individual sites can provide
 * this regardless of rating data format.
 *
 * @param array $data {
 *     Array of ratings data.
 *
 *     The return value should use the following format.
 *
 *     @type int    $ratingsCount The total of ratings, must match sum of all
 *                                values in ratings.
 *     @type int[]  $ratings      Rating count. The array must have 5 items, ex:
 *                                [1 => count of 1-star, …, 5 => count of 5-star].
 *     @type int    $rating       The average rating on a scale of 0 - 100.
 *     @type string $supportUrl   URL to support forum.
 * }
 * @param int $current_post_id The ID of the current post.
 */
$data = apply_filters( 'wporg_ratings_data', array(), $current_post_id );

$defaults = array(
	'ratingsCount' => 0,
	'ratings' => [],
	'supportUrl' => '',
);

$data = wp_parse_args( $data, $defaults );

if ( empty( $data['ratings'] ) || empty( $data['ratingsCount'] ) ) {
	echo '<p>' . esc_html__( 'No ratings have been submitted yet.', 'wporg' ) . '</p>';
	return;
}

?>
<ul <?php echo get_block_wrapper_attributes(); // phpcs:ignore ?>>
<?php
foreach ( range( 5, 1 ) as $stars ) :
	if ( ! isset( $data['ratings'][ $stars ] ) ) {
		continue;
	}
	$count = $data['ratings'][ $stars ];
	$rating_bar_width = 100 * $count / $data['ratingsCount'];
	$support_url = add_query_arg( 'filter', $stars, $data['supportUrl'] );
	?>
	<li class="wporg-ratings-bars__bar">
		<a href="<?php echo esc_url( $support_url ); ?>">
			<span class="screen-reader-text">
			<?php
				// translators: %1$d: count of reviews. %2$d: level of star rating (ex, 5-star).
				echo esc_html( sprintf( _n( '%1$d %2$d-star review', '%1$d %2$d-star reviews', $count, 'wporg' ), $count, $stars ) );
			?>
			</span>
			<span aria-hidden="true" class="wporg-ratings-bars__bar-label">
			<?php
				// translators: %d: star review amount, 1-5; ex "5 stars".
				echo esc_html( sprintf( _n( '%d star', '%d stars', $stars, 'wporg' ), $stars ) );
			?>
			</span>
			<span aria-hidden="true" class="wporg-ratings-bars__bar-background">
				<span class="wporg-ratings-bars__bar-foreground" style="width: <?php echo intval( $rating_bar_width ); ?>%;"></span>
			</span>
			<span aria-hidden="true" class="wporg-ratings-bars__bar-count"><?php echo intval( $count ); ?></span>
		</a>
	</li>
<?php endforeach; ?>
</ul>
