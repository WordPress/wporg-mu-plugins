<?php

$current_post_id = $block->context['postId'];
if ( ! $current_post_id ) {
	return;
}

$attributes = apply_filters( 'wporg_ratings_stars_attributes', $attributes, $current_post_id );

if ( ! isset( $attributes['rating'] ) ) {
	return;
}

?>
<div <?php echo get_block_wrapper_attributes(); // phpcs:ignore ?>>
	<?php if ( ! $attributes['rating'] ) : ?>
	<!-- Hide the "See all…" link if there are no ratings. -->
	<style>.wporg-ratings-link{display:none}</style>
	<div class="wporg-ratings-stars__label-empty">
		<?php esc_html_e( 'This has not been rated yet.', 'wporg' ); ?>
	</div>
	<?php else : ?>
	<div class="wporg-ratings-stars__icons">
		<?php

		$display_rating = round( $attributes['rating'] / 10 ) * 0.5;
		for ( $i = 0; $i < 5; $i++ ) {
			if ( $i + 1 <= $display_rating ) {
				echo '<svg class="is-star-filled" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" aria-hidden="true" focusable="false"><path d="M11.776 4.454a.25.25 0 01.448 0l2.069 4.192a.25.25 0 00.188.137l4.626.672a.25.25 0 01.139.426l-3.348 3.263a.25.25 0 00-.072.222l.79 4.607a.25.25 0 01-.362.263l-4.138-2.175a.25.25 0 00-.232 0l-4.138 2.175a.25.25 0 01-.363-.263l.79-4.607a.25.25 0 00-.071-.222L4.754 9.881a.25.25 0 01.139-.426l4.626-.672a.25.25 0 00.188-.137l2.069-4.192z"></path></svg>';
			} else if ( $i + 0.5 === $display_rating ) {
				echo '<svg class="is-star-half" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" aria-hidden="true" focusable="false"><path d="M9.518 8.783a.25.25 0 00.188-.137l2.069-4.192a.25.25 0 01.448 0l2.07 4.192a.25.25 0 00.187.137l4.626.672a.25.25 0 01.139.427l-3.347 3.262a.25.25 0 00-.072.222l.79 4.607a.25.25 0 01-.363.264l-4.137-2.176a.25.25 0 00-.233 0l-4.138 2.175a.25.25 0 01-.362-.263l.79-4.607a.25.25 0 00-.072-.222L4.753 9.882a.25.25 0 01.14-.427l4.625-.672zM12 14.533c.28 0 .559.067.814.2l1.895.997-.362-2.11a1.75 1.75 0 01.504-1.55l1.533-1.495-2.12-.308a1.75 1.75 0 01-1.317-.957L12 7.39v7.143z"></path></svg>';
			} else {
				echo '<svg class="is-star-empty" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" aria-hidden="true" focusable="false"><path fill-rule="evenodd" d="M9.706 8.646a.25.25 0 01-.188.137l-4.626.672a.25.25 0 00-.139.427l3.348 3.262a.25.25 0 01.072.222l-.79 4.607a.25.25 0 00.362.264l4.138-2.176a.25.25 0 01.233 0l4.137 2.175a.25.25 0 00.363-.263l-.79-4.607a.25.25 0 01.072-.222l3.347-3.262a.25.25 0 00-.139-.427l-4.626-.672a.25.25 0 01-.188-.137l-2.069-4.192a.25.25 0 00-.448 0L9.706 8.646zM12 7.39l-.948 1.921a1.75 1.75 0 01-1.317.957l-2.12.308 1.534 1.495c.412.402.6.982.503 1.55l-.362 2.11 1.896-.997a1.75 1.75 0 011.629 0l1.895.997-.362-2.11a1.75 1.75 0 01.504-1.55l1.533-1.495-2.12-.308a1.75 1.75 0 01-1.317-.957L12 7.39z" clip-rule="evenodd"></path></svg>';
			}
		}
		?>
	</div>

	<div class="wporg-ratings-stars__label">
		<?php
		printf(
			// translators: %s is the current rating value.
			esc_html__( '%s out of 5 stars.', 'wporg' ),
			'<span>' . $display_rating . '</span>' // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		);
		?>
	</div>
	<?php endif; ?>
</div>
