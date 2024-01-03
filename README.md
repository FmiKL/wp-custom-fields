# WordPress Custom Fields

This repository introduces a PHP class designed to simplify the creation of custom fields in the WordPress admin interface. Ideal for theme and plugin developers, this class allows easy and complete integration of custom fields.

## Features

- **Ease of Custom Fields Creation**: Allows the easy creation of different types of custom fields in WordPress.
- **Customizable Fields**: Supports various field types such as textarea, text, email, color, etc.
- **Advanced Media Integration**: Specific keywords in placeholders trigger the opening of the WordPress media library.

## Installation and Setup

1. Clone or download this repository into your WordPress environment.
2. Import the classes you need into your `functions.php` file or your plugin.

```php
require_once 'path/to/wp-custom-fields/class-abstract-meta.php';
require_once 'path/to/wp-custom-fields/class-multiple-meta.php';
require_once 'path/to/wp-custom-fields/class-repeat-meta.php';
require_once 'path/to/wp-custom-fields/class-simple-meta.php';
```

## Usage Example

### Create One or More Simple Fields

```php
function add_simple_description_meta() {
    $meta = new Simple_Meta( 'Description', 'simple_description' );
    $meta->set_enables( 'page', 3 );

    $meta->add_field(
        'text',
        'my_title',
        array( 'placeholder' => 'Enter your title' )
    );

    $meta->add_field(
        'textarea',
        'my_description',
        array(
            'placeholder' => 'Enter your description',
            'rows'        => 7,
        )
    );
}
add_action( 'admin_init', 'add_simple_description_meta' );
```

### Create One or More Multiple Fields

```php
function add_multipart_title_meta() {
    $meta = new Multiple_Meta( 'Multipart title', 'multipart_title' );
    $meta->set_enables( 5 );

    $meta->group_fields(
        'my_title_',
        $meta->add_field(
            'text',
            'part_1',
            array( 'placeholder' => 'Enter the part 1 of the title' )
        ),
        $meta->add_field(
            'text',
            'part_2',
            array( 'placeholder' => 'Enter the part 2 of the title' )
        ),
    );
}
add_action( 'admin_init', 'add_multipart_title_meta' );
```

### Create One or More Repeat Fields

```php
function add_repeat_image_meta() {
    $meta = new Repeat_Meta( 'Repeat image', 'repeat_image' );
    $meta->set_enables( 'page', 'post' );

    $meta->group_fields(
        'my_image_',
        $meta->add_field(
            'url',
            'url',
            array( 'placeholder' => 'Select your image ~ 800px' )
        ),
    );

    $meta->group_fields(
        'my_desc_image_',
        $meta->add_field(
            'url',
            'url',
            array( 'placeholder' => 'Select your image' )
        ),
        $meta->add_field(
            'text',
            'alt',
            array( 'placeholder' => 'Enter your description' )
        ),
    );
}
add_action( 'admin_init', 'add_repeat_image_meta' );
```

## Contributing

Contributions to improve this project are welcome, whether they be bug fixes, documentation improvements, or new feature suggestions.

## License

This project is distributed under the [GNU General Public License version 2 (GPL v2)](LICENSE).
