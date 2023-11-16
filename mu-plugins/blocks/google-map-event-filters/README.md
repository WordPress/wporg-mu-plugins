# Google Map Event Filters

This plugins creates the `wporg/google-map-event-filters` block, which displays a map and list of events that match a given filter during a given timeframe. Filters can be setup for anything, but some common examples are watch parties for WP anniversaries and the State of the Word.

It uses the `wporg/google-map` block to display a searchable list and/or map of the selected events.


## Usage

1. Setup the API key needed for the `wporg/google-maps` block. See its README for details.
1. Add a new filter to `filter_potential_events()` if you're not using an existing one.
1. Add the following to a pattern in your theme. `googleMapBlockAttributes` are the attributes that will be passed to the `wporg/google-map` block, see it's README for details.

	```php
	$filter_options = array(
		'filterSlug' => 'wp20',
		'startDate'  => 'April 21, 2023',
		'endDate'    => 'May 30, 2023',

		// This has to be an object, see `WordPressdotorg\MU_Plugins\Google_Map_Event_Filters\render()`.
		'googleMapBlockAttributes' => (object) array(
			'id'      => 'wp20',
			'apiKey'  => 'WORDCAMP_DEV_GOOGLE_MAPS_API_KEY',
		),
	);

	?>

	<!-- wp:wporg/google-map-event-filters <?php echo wp_json_encode( $filter_options ); ?> /-->
	```

	Alternatively, you could take that JSON and manually put it in the post source like this:

	```html
	<!-- wp:wporg/google-map-event-filters {"filterSlug":"sotw","startDate":"December 10, 2023","endDate":"January 12, 2024","googleMapBlockAttributes":{"id":"sotw-2023","apiKey":"WORDCAMP_DEV_GOOGLE_MAPS_API_KEY"}} /-->

	<!-- wp:wporg/google-map-event-filters {"filterSlug":"wp20","startDate":"April 21, 2023","endDate":"May 30, 2023","googleMapBlockAttributes":{"id":"wp20","apiKey":"WORDCAMP_DEV_GOOGLE_MAPS_API_KEY"}} /-->
	```

1. View the page where the block is used. That will create the cron job that updates the data automatically in the future.
1. Run `wp cron event run prime_event_filters` to test the filtering. Look at each title, and add any false positives to `$false_positives` in `filter_potential_events()`. If any events that should be included were ignored, add a keyword from the title to `$keywords`. Run the command after those changes and make sure it's correct now.
