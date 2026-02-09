# WordPress Custom Fields

A lightweight PHP class for creating custom meta boxes in the WordPress admin area.

## Features

- Text, email, url, number, color, checkbox, textarea, select fields
- WP Editor integration
- Grouped row fields with dynamic colspan
- Repeatable field groups with drag & drop reordering
- Media library integration via placeholder keywords (`image`, `avatar`, `icon`, `pdf`)
- Automatic image size selection with `{size}px` in placeholder
- Built-in sanitization per field type
- Default values support

## Setup

```php
require_once 'path/to/wp-custom-fields/class-meta-box.php';
```

## Usage

```php
function register_meta() {
    $meta = new Meta_Box( 'My fields', 'my_fields' );
    $meta->set_enables( 'page', 'post' );

    $meta->add_field( 'text', 'title', array( 'placeholder' => 'Title' ) );
    $meta->add_field( 'email', 'email', array( 'placeholder' => 'Email' ) );
    $meta->add_field( 'color', 'theme_color' );
    $meta->add_field( 'select', 'category', array(
        'label'   => 'Pick one',
        'choices' => array( 'News', 'Blog', 'Tutorial' ),
    ) );

    $meta->add_row(
        'address_',
        $meta->add_field( 'text', 'street', array( 'placeholder' => 'Street' ) ),
        $meta->add_field( 'text', 'city', array( 'placeholder' => 'City' ) ),
    );

    $meta->add_repeater(
        'slide_',
        $meta->add_field( 'url', 'image', array( 'placeholder' => 'Select image ~ 800px' ) ),
        $meta->add_field( 'text', 'alt', array( 'placeholder' => 'Alt text' ) ),
    );
}
add_action( 'admin_init', 'register_meta' );
```

## License

[GPL v2](LICENSE)
