<?php

$attributes = apply_filters( 'wporg_block_ratings_bars', $attributes );

if ( ! $attributes['num_ratings'] ) {
	return;
}

?>
<ul <?php echo get_block_wrapper_attributes(); // phpcs:ignore ?>>
<?php
foreach ( range( 5, 1 ) as $stars ) :
	if ( ! isset( $attributes['ratings'][ $stars - 1 ] ) ) {
		continue;
	}
	$count = $attributes['ratings'][ $stars - 1 ];
	$rating_bar_width = 100 * $count / $attributes['num_ratings'];
	?>
	<li class="wporg-ratings-bars__bar">
		<a href="<?php echo esc_url( $attributes['support_url'] . $attributes['slug'] . '/reviews/?filter=' . $stars ); ?>">
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
