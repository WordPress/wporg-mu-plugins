# Screenshot Preview

This block uses [mShots](https://github.com/Automattic/mShots) to screenshot a site and show a thumbnail preview.

## Full Site Editing themes

1. Add the block with the required attributes to the theme's `block-templates/*.html` files. For example

```html
<!-- wp:wporg/screenshot-preview {"src":"https://wordpress.org/"} /-->
```

Or for a linked image:

```html
<!-- wp:wporg/screenshot-preview {"src":"https://developer.wordpress.org/","href":"https://developer.wordpress.org/","alt":"WordPress Developer Resources"} /-->
```

## Classic themes in the w.org network

The same as above, but instead of adding the block to `block-templates/*.html` files, you'd add it to `themes/{template}`:

```php
echo do_blocks( '<!-- wp:wporg/screenshot-preview {"src":"https://wordpress.org/"} /-->' );
```

## Showing/hiding an image

If you pass through `"isHidden":true` as a block attribute, this will trigger a CSS class to hide the block, and prevent any network requests loading the image. To show the image, currently this requires manually dispatching a custom event.

For example, in another block on a button click event, the `wporg-show` event can be triggered. This will call `actions.makeVisible` within the correct element context, flipping off the `isHidden` value. The context update then triggers the `watch` action, which fetches the image if it's not already loaded.

```js
// container is a wrapper around multiple screenshot preview blocks.
container.querySelectorAll( '.wp-block-wporg-screenshot-preview' ).forEach( ( element ) => {
    const event = new Event( 'wporg-show' );
    element.dispatchEvent( event );
} );
```

## Attributes

| Name           | Type    | Description                                | Default |
|----------------|---------|--------------------------------------------|---------|
| alt            | string  | Alt text for image.                         | ""     |
| fullPage       | boolean | If true, image only captures page content, up to viewportHeight. If false, image is fixed height (viewportHeight), with whitespace surrounding. | true |
| href           | string  | Destination for link wrapper, if provided.  | ""     |
| isHidden       | boolean | If true, hide the block with CSS, prevent network requests. | false |
| src            | string  | Source (website) to capture for display     | ""     |
| viewportHeight | integer | Viewport height (or max-height if fullPage) | 0      |
| viewportWidth  | integer | Viewport width                              | 1200   |
| width          | integer | Image width                                 | 800    |
