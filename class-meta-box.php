<?php
/**
 * Class used to create a meta box with custom fields.
 *
 * @package WP_Custom_Fields
 * @author Mikael Fourré
 * @version 3.0.0
 * @see https://github.com/FmiKL/wp-custom-fields
 */
class Meta_Box {
    /**
     * Path to the assets.
     * 
     * @var string
     * @since 2.0.0
     * @see Meta_Box::enqueues_assets()
     */
    private const ASSETS_PATH = '/wp-custom-fields/assets';

    /**
     * Key used to identify a row.
     *
     * @var string
     * @since 1.0.0
     */
    private const INPUT_ROW_KEY = '_row';

    /**
     * User capability required to edit the post.
     *
     * @var string
     * @since 1.0.0
     * @see Meta_Box::set_capability()
     * @link https://wordpress.org/support/article/roles-and-capabilities/
     */
    private $capability = 'publish_posts';

    /**
     * Types of posts or pages on which the meta box will be displayed,
     * identified by their type or identifiers.
     *
     * @var array<string|int>
     * @since 1.0.0
     * @see Meta_Box::set_enables()
     */
    private $enables = array( 'post', 'page' );

    /**
     * Title of the meta box.
     *
     * @var string
     * @since 1.0.0
     */
    private $title;

    /**
     * Unique identifier of the meta box.
     *
     * @var string
     * @since 1.0.0
     */
    private $id;

    /**
     * Key used for the nonce field.
     *
     * @var string
     * @since 1.0.0
     */
    private $nonce_key;

    /**
     * Key used for the action associated with the nonce.
     *
     * @var string
     * @since 1.0.0
     */
    private $action_key;

    /**
     * Entries registered in the meta box.
     *
     * @var array<array>
     * @since 3.0.0
     * @see Meta_Box::add_field()
     */
    private $entries = array();

    /**
     * @param string $title Title of the meta box.
     * @param string $id    Unique identifier of the meta box.
     * @since 2.0.0
     */
    public function __construct( $title, $id ) {
        $this->title      = $title;
        $this->id         = $id;
        $this->nonce_key  = $id . '_nonce';
        $this->action_key = 'save-' . $id;
        $this->add_hooks();
    }

    /**
     * Hooks the methods to the appropriate actions.
     * 
     * @since 1.0.0
     */
    private function add_hooks() {
        add_action( 'add_meta_boxes', array( $this, 'add' ), 10, 2 );
        add_action( 'save_post', array( $this, 'save' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueues_assets' ) );
    }

    /**
     * Sets the post types where the meta box is enabled.
     *
     * @param string|int $enables Post types, post names, or page IDs where the meta box is enabled.
     * @since 2.0.0
     */
    public function set_enables( ...$enables ) {
        $this->enables = $enables;
    }

    /**
     * Sets the user capability required to save the meta box.
     *
     * @param string $capability User capability required.
     * @since 2.0.0
     * @link https://developer.wordpress.org/reference/functions/current_user_can/
     */
    public function set_capability( $capability ) {
        $this->capability = $capability;
    }

    /**
     * Adds a field to the meta box.
     *
     * @param string $type    Field type (e.g., "text", "number", "url").
     * @param string $name    Field name (e.g., "my_field").
     * @param array  $options Additional options for the field.
     *                        If the placeholder contains a reserved word (e.g., "image", "avatar" or "icon"),
     *                        a double-click will trigger the WordPress media library to open directly.
     *                        If the placeholder contains an indication of an image size "{size}px", the selected image will be the one closest to this size.
     * @return array Field that was added.
     * @since 2.0.0
     */
    public function add_field( $type, $name, $options = array() ) {
        $field = array(
            'type'    => $type,
            'name'    => $name,
            'options' => $options,
        );

        $this->entries[] = array( 'mode' => 'field', 'data' => $field );

        return $field;
    }

    /**
     * Groups fields into a row.
     *
     * @param string $prefix    Prefix for the row.
     * @param array  ...$fields Fields to group together.
     * @since 3.0.0
     */
    public function add_row( $prefix, ...$fields ) {
        array_splice( $this->entries, -count( $fields ) );

        $this->entries[] = array(
            'mode'   => 'row',
            'prefix' => $prefix,
            'fields' => $fields,
        );
    }

    /**
     * Groups fields into a repeater.
     *
     * @param string $prefix    Prefix for the repeater.
     * @param array  ...$fields Fields to group together.
     * @since 3.0.0
     */
    public function add_repeater( $prefix, ...$fields ) {
        array_splice( $this->entries, -count( $fields ) );

        $this->entries[] = array(
            'mode'   => 'repeater',
            'prefix' => $prefix,
            'fields' => $fields,
        );
    }

    /**
     * Adds the meta box if the post type is enabled.
     *
     * @param string  $post_type Post type of the post.
     * @param WP_Post $post      Post object of the post.
     * @since 1.0.0
     * @link https://developer.wordpress.org/reference/hooks/add_meta_boxes/
     */
    public function add( $post_type, $post ) {
        if (
            in_array( $post->ID, $this->enables ) ||
            in_array( $post_type, $this->enables ) ||
            in_array( $post->post_name, $this->enables )
        ) {
            add_meta_box( $this->id, $this->title, array( $this, 'render' ) );
        }
    }

    /**
     * Renders the meta box.
     *
     * @param WP_Post $post Post object of the post being edited.
     * @since 1.0.0
     */
    public function render( $post ) {
        wp_nonce_field( $this->action_key, $this->nonce_key );

        $count = count( $this->entries );
        $i     = 0;

        while ( $i < $count ) {
            $entry = $this->entries[ $i ];

            switch ( $entry['mode'] ) {
                case 'field':
                    $this->render_field( $post, $entry['data'] );
                    $i++;
                    break;
                case 'row':
                    $rows = array();
                    while ( $i < $count && $this->entries[ $i ]['mode'] === 'row' ) {
                        $rows[] = $this->entries[ $i ];
                        $i++;
                    }
                    $this->render_rows( $post, $rows );
                    break;
                case 'repeater':
                    $this->render_repeater( $post, $entry );
                    $i++;
                    break;
            }
        }
    }

    /**
     * Renders a single field.
     *
     * @param WP_Post $post  Post object of the post being edited.
     * @param array   $field Field to render.
     * @since 3.0.0
     */
    private function render_field( $post, $field ) {
        $value       = get_post_meta( $post->ID, $field['name'], true );
        $placeholder = $field['options']['placeholder'] ?? '';
        $default     = $field['options']['default'] ?? '';

        if ( $value === '' && $default !== '' ) {
            $value = $default;
        }

        switch ( $field['type'] ) {
            case 'textarea':
                $rows = $field['options']['rows'] ?? '10';
                echo '<p><textarea class="large-text" name="' . esc_attr( $field['name'] ) . '" placeholder="' . esc_attr( $placeholder ) . '" rows="' . esc_attr( $rows ) . '">' . esc_textarea( $value ) . '</textarea></p>';
                break;
            case 'editor':
                $value   = htmlspecialchars_decode( $value );
                $uniq_id = uniqid( 'wp_editor_' );
                $rows    = $field['options']['rows'] ?? '10';

                wp_editor( $value, $uniq_id, array(
                    'textarea_name' => $field['name'],
                    'textarea_rows' => $rows,
                    'media_buttons' => false,
                    'wpautop'       => false,
                ) );
                break;
            case 'select':
                $label   = $field['options']['label'] ?? '';
                $choices = $field['options']['choices'] ?? array();

                echo '<p><select class="widefat" name="' . esc_attr( $field['name'] ) . '">';
                if ( $label ) {
                    echo '<option value="" ' . ( $value ? '' : 'selected' ) . ' disabled>' . esc_html( $label ) . '</option>';
                }
                foreach ( $choices as $choice ) {
                    echo '<option value="' . esc_attr( $choice ) . '" ' . selected( $value, $choice, false ) . '>' . esc_html( $choice ) . '</option>';
                }
                echo '</select></p>';
                break;
            case 'checkbox':
                echo '<p><input type="checkbox" name="' . esc_attr( $field['name'] ) . '" value="1" ' . checked( $value, '1', false ) . '></p>';
                break;
            default:
                echo '<p><input type="' . esc_attr( $field['type'] ) . '" class="large-text" name="' . esc_attr( $field['name'] ) . '" value="' . esc_attr( $value ) . '" placeholder="' . esc_attr( $placeholder ) . '"></p>';
                break;
        }
    }

    /**
     * Renders grouped rows.
     *
     * @param WP_Post $post Post object of the post being edited.
     * @param array   $rows Rows to render.
     * @since 3.0.0
     */
    private function render_rows( $post, $rows ) {
        $max_fields = $this->get_max_fields_per_row( $rows );

        echo '<table class="form-table table-tight">';
        foreach ( $rows as $index => $row ) {
            $row_class   = $index % 2 === 0 ? 'even-row' : 'odd-row';
            $fields      = $row['fields'];
            $field_count = count( $fields );

            list( $base_colspan, $extra ) = $this->get_colspan_values( $max_fields, $field_count );
            list( $first_key, $last_key ) = $this->get_first_and_last_keys( $fields );

            echo '<tr class="form-field ' . $row_class . '">';
            foreach ( $fields as $key => $field ) {
                $field_name  = $row['prefix'] . $field['name'];
                $value       = get_post_meta( $post->ID, $field_name, true );
                $placeholder = $field['options']['placeholder'] ?? '';

                $field_class = '';
                if ( $key === $first_key ) {
                    $field_class = 'first-field';
                } elseif ( $key === $last_key ) {
                    $field_class = 'last-field';
                }

                $colspan_val = $base_colspan;
                if ( $key === $last_key ) {
                    $colspan_val += $extra;
                }
                $colspan = ( $colspan_val > 1 ) ? ' colspan="' . $colspan_val . '"' : '';

                echo '<td' . $colspan . '>';
                switch ( $field['type'] ) {
                    case 'textarea':
                        $rows_attr = $field['options']['rows'] ?? '5';
                        echo '<textarea class="large-text ' . esc_attr( $field_class ) . '" name="' . esc_attr( $field_name ) . '" placeholder="' . esc_attr( $placeholder ) . '" rows="' . esc_attr( $rows_attr ) . '">' . esc_textarea( $value ) . '</textarea>';
                        break;
                    case 'select':
                        $label   = $field['options']['label'] ?? '';
                        $choices = $field['options']['choices'] ?? array();

                        echo '<select class="widefat ' . esc_attr( $field_class ) . '" name="' . esc_attr( $field_name ) . '">';
                        if ( $label ) {
                            echo '<option value="" ' . ( $value ? '' : 'selected' ) . ' disabled>' . esc_html( $label ) . '</option>';
                        }
                        foreach ( $choices as $choice ) {
                            echo '<option value="' . esc_attr( $choice ) . '" ' . selected( $value, $choice, false ) . '>' . esc_html( $choice ) . '</option>';
                        }
                        echo '</select>';
                        break;
                    case 'checkbox':
                        echo '<input type="checkbox" class="' . esc_attr( $field_class ) . '" name="' . esc_attr( $field_name ) . '" value="1" ' . checked( $value, '1', false ) . '>';
                        break;
                    default:
                        echo '<input type="' . esc_attr( $field['type'] ) . '" class="large-text ' . esc_attr( $field_class ) . '" name="' . esc_attr( $field_name ) . '" value="' . esc_attr( $value ) . '" placeholder="' . esc_attr( $placeholder ) . '">';
                        break;
                }
                echo '</td>';
            }
            echo '</tr>';
        }
        echo '</table>';
    }

    /**
     * Returns the maximum number of fields in any row.
     *
     * @param array $rows Rows to check.
     * @return int Maximum number of fields in any row.
     * @since 3.0.0
     */
    private function get_max_fields_per_row( $rows ) {
        $max_fields = 0;
        foreach ( $rows as $row ) {
            $max_fields = max( $max_fields, count( $row['fields'] ) );
        }
        return $max_fields;
    }

    /**
     * Calculates base colspan and extra for the last field.
     *
     * @param int   $max_fields  Maximum number of fields in any row.
     * @param int   $field_count Number of fields in the current group.
     * @return array Array of base colspan and extra for the last field.
     * @since 2.2.3
     */
    private function get_colspan_values( $max_fields, $field_count ) {
        if ( $field_count === 0 ) {
            return array( 1, 0 );
        }

        $base_colspan = floor( $max_fields / $field_count );
        $extra        = $max_fields - ( $base_colspan * $field_count );
        
        return array( $base_colspan, $extra );
    }

    /**
     * Renders a repeater table.
     *
     * @param WP_Post $post  Post object being edited.
     * @param array   $entry Repeater entry.
     * @since 3.0.0
     */
    private function render_repeater( $post, $entry ) {
        ?>
        <table class="table-repeater">
            <tbody class="table-container">
                <?php $this->render_repeater_template( $entry['prefix'], $entry['fields'] ); ?>
                <?php $this->render_repeater_elements( $post, $entry['prefix'], $entry['fields'] ); ?>
            </tbody>
            <?php $this->render_repeater_footer(); ?>
        </table>
        <?php
    }

    /**
     * Renders a repeater template row.
     *
     * @param string $prefix Prefix of the fields.
     * @param array  $fields Fields to render.
     * @since 3.0.0
     */
    private function render_repeater_template( $prefix, $fields ) {
        ?>
        <tr class="table-template">
            <td>
                <button type="button" class="button-move-up button button-secondary">&uarr;</button>
                <button type="button" class="button-move-down button button-secondary">&darr;</button>
            </td>
            <td>
                <?php 
                list( $first_key, $last_key ) = $this->get_first_and_last_keys( $fields );
                foreach ( $fields as $key => $field ) {
                    $field_class  = ( $key === $first_key || count( $fields ) === 1 ) ? 'first-field' : '';
                    $field_class .= $key === $last_key ? 'last-field' : '';

                    $field_name = $this->id . '[' . $prefix . $field['name'] . '][' . self::INPUT_ROW_KEY . ']';

                    switch ( $field['type'] ) {
                        case 'textarea':
                            echo '<textarea class="large-text ' . esc_attr( $field_class ) . '" name="' . esc_attr( $field_name ) . '" placeholder="' . esc_attr( $field['options']['placeholder'] ?? '' ) . '" rows="' . esc_attr( $field['options']['rows'] ?? '5' ) . '"></textarea>';
                            break;
                        case 'select':
                            $label   = $field['options']['label'] ?? '';
                            $choices = $field['options']['choices'] ?? array();

                            echo '<select class="widefat ' . esc_attr( $field_class ) . '" name="' . esc_attr( $field_name ) . '">';
                            if ( $label ) {
                                echo '<option value="" selected disabled>' . esc_html( $label ) . '</option>';
                            }
                            foreach ( $choices as $choice ) {
                                echo '<option value="' . esc_attr( $choice ) . '">' . esc_html( $choice ) . '</option>';
                            }
                            echo '</select>';
                            break;
                        case 'checkbox':
                            echo '<input type="checkbox" class="' . esc_attr( $field_class ) . '" name="' . esc_attr( $field_name ) . '" value="1">';
                            break;
                        default:
                            echo '<input type="' . esc_attr( $field['type'] ) . '" class="large-text ' . esc_attr( $field_class ) . '" name="' . esc_attr( $field_name ) . '" placeholder="' . esc_attr( $field['options']['placeholder'] ?? '' ) . '">';
                            break;
                    }
                }
                ?>
            </td>
            <td>
                <button type="button" class="button-remove button button-secondary">&times;</button>
            </td>
        </tr>
        <?php
    }

    /**
     * Renders the existing repeater elements.
     *
     * @param WP_Post $post   Post object being edited.
     * @param string  $prefix Prefix of the fields.
     * @param array   $fields Fields to render.
     * @since 3.0.0
     */
    private function render_repeater_elements( $post, $prefix, $fields ) {
        $data = $this->get_repeater_data( $post, $prefix, $fields );

        if ( ! empty( $data ) ) {
            $iteration = 0;
            foreach ( $this->transpose_repeater_data( $data ) as $row ) {
                $iteration++;
                $this->render_repeater_element( $prefix, $fields, $row, $iteration );
            }
        }
    }

    /**
     * Returns the data for the given repeater fields.
     *
     * @param WP_Post $post   Post object being edited.
     * @param string  $prefix Prefix of the fields.
     * @param array   $fields Fields to get data for.
     * @return array Data for the given fields.
     * @since 3.0.0
     */
    private function get_repeater_data( $post, $prefix, $fields ) {
        $data = array();
        foreach ( $fields as $field ) {
            $post_meta = get_post_meta( $post->ID, $prefix . $field['name'], true );
            if ( $post_meta ) {
                $data[ $field['name'] ] = $post_meta;
            }
        }
        return $data;
    }

    /**
     * Transposes repeater data from field-based to row-based.
     *
     * @param array  $data Data to transpose.
     * @return array Transposed data.
     * @since 3.0.0
     */
    private function transpose_repeater_data( $data ) {
        $rows = array();
        foreach ( $data as $key => $values ) {
            foreach ( $values as $index => $value ) {
                $rows[ $index ][ $key ] = $value;
            }
        }
        return $rows;
    }

    /**
     * Renders a repeater element row.
     *
     * @param string $prefix    Prefix of the fields.
     * @param array  $fields    Fields definition.
     * @param array  $row       Row data.
     * @param int    $iteration Iteration number of the element.
     * @since 3.0.0
     */
    private function render_repeater_element( $prefix, $fields, $row, $iteration ) {
        ?>
        <tr class="table-element">
            <td>
                <button type="button" class="button-move-up button button-secondary">&uarr;</button>
                <button type="button" class="button-move-down button button-secondary">&darr;</button>
            </td>
            <td>
                <?php 
                list( $first_key, $last_key ) = $this->get_first_and_last_keys( $fields );

                foreach ( $fields as $key => $field ) {
                    $value = $row[ $field['name'] ] ?? '';

                    $field_class  = ( $key === $first_key || count( $fields ) === 1 ) ? 'first-field' : '';
                    $field_class .= $key === $last_key ? 'last-field' : '';

                    $field_name = $this->id . '[' . $prefix . $field['name'] . '][' . $iteration . ']';

                    switch ( $field['type'] ) {
                        case 'textarea':
                            echo '<textarea class="large-text ' . esc_attr( $field_class ) . '" name="' . esc_attr( $field_name ) . '">' . esc_textarea( $value ) . '</textarea>';
                            break;
                        case 'select':
                            $label   = $field['options']['label'] ?? '';
                            $choices = $field['options']['choices'] ?? array();

                            echo '<select class="widefat ' . esc_attr( $field_class ) . '" name="' . esc_attr( $field_name ) . '">';
                            if ( $label ) {
                                echo '<option value="" ' . ( $value ? '' : 'selected' ) . ' disabled>' . esc_html( $label ) . '</option>';
                            }
                            foreach ( $choices as $choice ) {
                                echo '<option value="' . esc_attr( $choice ) . '" ' . selected( $value, $choice, false ) . '>' . esc_html( $choice ) . '</option>';
                            }
                            echo '</select>';
                            break;
                        case 'checkbox':
                            echo '<input type="checkbox" class="' . esc_attr( $field_class ) . '" name="' . esc_attr( $field_name ) . '" value="1" ' . checked( $value, '1', false ) . '>';
                            break;
                        default:
                            echo '<input type="' . esc_attr( $field['type'] ) . '" class="large-text ' . esc_attr( $field_class ) . '" name="' . esc_attr( $field_name ) . '" value="' . esc_attr( $value ) . '">';
                            break;
                    }
                }
                ?>
            </td>
            <td>
                <button type="button" class="button-remove button button-secondary">&times;</button>
            </td>
        </tr>
        <?php
    }

    /**
     * Renders a repeater footer.
     *
     * @since 3.0.0
     */
    private function render_repeater_footer() {
        ?>
        <tfoot>
            <tr>
                <td colspan="3">
                    <button type="button" class="button-add button button-primary button-large">Add</button>
                </td>
            </tr>
        </tfoot>
        <?php
    }

    /**
     * Returns the first and last keys of an array.
     *
     * @param array  $array Array to get the keys from.
     * @return array An array with the first and last keys.
     * @since 2.0.0
     */
    private function get_first_and_last_keys( $array ) {
        $keys      = array_keys( $array );
        $first_key = reset( $keys );
        $last_key  = end( $keys );
    
        return array( $first_key, $last_key );
    }

    /**
     * Saves the meta box.
     *
     * @param int $post_id Post ID of the post being saved.
     * @since 1.0.0
     * @link https://developer.wordpress.org/reference/hooks/save_post/
     * @link https://developer.wordpress.org/reference/functions/update_post_meta/
     */
    public function save( $post_id ) {
        if ( ! $this->check_security( $post_id ) ) {
            return;
        }

        foreach ( $this->entries as $entry ) {
            switch ( $entry['mode'] ) {
                case 'field':
                    $this->save_field( $post_id, $entry['data'] );
                    break;
                case 'row':
                    $this->save_row( $post_id, $entry );
                    break;
                case 'repeater':
                    $this->save_repeater( $post_id, $entry );
                    break;
            }
        }
    }

    /**
     * Saves a single field.
     *
     * @param int   $post_id Post ID of the post being saved.
     * @param array $field   Field to save.
     * @since 3.0.0
     */
    private function save_field( $post_id, $field ) {
        $field_name = $field['name'];

        if ( array_key_exists( $field_name, $_POST ) ) {
            $field_value = $this->sanitize_field_value( $_POST[ $field_name ], $field );

            if ( $field_value !== '' ) {
                update_post_meta( $post_id, $field_name, $field_value );
            } else {
                delete_post_meta( $post_id, $field_name );
            }
        }
    }

    /**
     * Saves a row of fields.
     *
     * @param int   $post_id Post ID of the post being saved.
     * @param array $entry   Row entry to save.
     * @since 3.0.0
     */
    private function save_row( $post_id, $entry ) {
        foreach ( $entry['fields'] as $field ) {
            $field_name = $entry['prefix'] . $field['name'];

            if ( array_key_exists( $field_name, $_POST ) ) {
                $field_value = $this->sanitize_field_value( $_POST[ $field_name ], $field );

                if ( $field_value !== '' ) {
                    update_post_meta( $post_id, $field_name, $field_value );
                } else {
                    delete_post_meta( $post_id, $field_name );
                }
            }
        }
    }

    /**
     * Saves a repeater.
     *
     * @param int   $post_id Post ID of the post being saved.
     * @param array $entry   Repeater entry to save.
     * @since 3.0.0
     */
    private function save_repeater( $post_id, $entry ) {
        if ( ! array_key_exists( $this->id, $_POST ) || ! is_array( $_POST[ $this->id ] ) ) {
            return;
        }

        $field_map = array();
        foreach ( $entry['fields'] as $field ) {
            $field_map[ $entry['prefix'] . $field['name'] ] = $field;
        }

        $post_data = array();
        foreach ( $_POST[ $this->id ] as $key => $values ) {
            if ( ! is_array( $values ) || ! isset( $field_map[ $key ] ) ) {
                continue;
            }

            $post_data[ $key ] = array();
            foreach ( $values as $value ) {
                $post_data[ $key ][] = $this->sanitize_field_value( $value, $field_map[ $key ] );
            }
        }

        foreach ( $post_data as $key => $value ) {
            $has_values = array_filter(
                $value,
                static function ( $item ) {
                    return $item !== '' && $item !== null;
                }
            );

            if ( ! empty( $has_values ) ) {
                update_post_meta( $post_id, $key, $value );
            } else {
                delete_post_meta( $post_id, $key );
            }
        }
    }

    /**
     * Verifies the nonce and the user capability.
     *
     * @param int  $post_id Post ID of the post being saved.
     * @return bool Returns true if the nonce is valid and the user has the capability to save the meta box.
     * @since 1.0.0
     */
    private function check_security( $post_id ) {
        if (
            ! isset( $_POST[ $this->nonce_key ] ) ||
            ! wp_verify_nonce( $_POST[ $this->nonce_key ], $this->action_key )
        ) {
            return false;
        }

        if ( ! current_user_can( $this->capability, $post_id ) ) {
            return false;
        }

        return true;
    }

    /**
     * Sanitizes a field value based on its type.
     *
     * @param mixed $input Raw value.
     * @param array $field Field configuration.
     * @return string Sanitized value or empty string on invalid input.
     * @since 2.2.5
     */
    private function sanitize_field_value( $input, $field ) {
        if ( is_array( $input ) ) {
            return '';
        }

        $type  = $field['type'] ?? 'text';
        $value = trim( (string) $input );

        switch ( $type ) {
            case 'checkbox':
                return ( '1' === $value ) ? '1' : '0';
            case 'email':
                return sanitize_email( $value );
            case 'url':
                return esc_url_raw( $value );
            case 'number':
                return is_numeric( $value ) ? $value : '';
            case 'color':
                $sanitized = sanitize_hex_color( $value );
                return $sanitized ? $sanitized : '';
            case 'textarea':
                return sanitize_textarea_field( $value );
            case 'editor':
                return wp_kses_post( $value );
            case 'select':
                $choices = isset( $field['options']['choices'] ) && is_array( $field['options']['choices'] )
                    ? array_values( $field['options']['choices'] )
                    : array();
                return in_array( $value, $choices, true ) ? $value : '';
            default:
                return sanitize_text_field( $value );
        }
    }

    /**
     * Enqueues the necessary scripts and styles.
     * 
     * @since 1.0.0
     */
    public function enqueues_assets() {
        wp_enqueue_media();

        $assets_path_directory_uri = get_template_directory_uri() . self::ASSETS_PATH;

        if ( ! wp_script_is( 'field-media', 'registered' ) ) {
            wp_enqueue_script( 'field-media', $assets_path_directory_uri . '/js/field-media.js', array(), null, true );
        }

        if ( ! wp_script_is( 'field-repeater', 'registered' ) ) {
            wp_enqueue_script( 'field-repeater', $assets_path_directory_uri . '/js/field-repeater.js', array(), null, true );
        }

        if ( ! wp_style_is( 'field-meta', 'registered' ) ) {
            wp_enqueue_style( 'field-meta', $assets_path_directory_uri . '/css/field-meta.css', array(), null );
        }
    }
}
