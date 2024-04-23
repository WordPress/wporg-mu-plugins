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

## Attributes

| Name          | Type    | Description                                | Default |
|---------------|---------|--------------------------------------------|---------|
| alt           | string  | Alt text for image.                        | ""      |
| href          | string  | Destination for link wrapper, if provided. | ""      |
| src           | string  | Source (website) to capture for display    | ""      |
| width         | integer | Image width                                | 800     |
| viewportWidth | integer | Viewport width                             | 1200    |
