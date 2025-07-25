<?php
/**
 * Class used to create a metabox with one or more custom fields.
 * 
 * @package WP_Custom_Fields
 * @author Mikael Fourré
 * @version 2.2.1
 * @see https://github.com/FmiKL/wp-custom-fields
 */
class Simple_Meta extends Abstract_Meta {
    /**
     * Renders the meta box.
     *
     * @param WP_Post $post Post object of the post being edited.
     * @since 1.0.0
     */
    public function render( $post ) {
        wp_nonce_field( $this->action_key, $this->nonce_key );

        foreach ( $this->fields as $field ) {
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
                default:
                    if ( $field['type'] === 'date' ) {
                        $value = $this->format_date_for_display( $value );
                    }

                    echo '<p><input type="' . esc_attr( $field['type'] ) . '" class="large-text" name="' . esc_attr( $field['name'] ) . '" value="' . esc_attr( $value ) . '" placeholder="' . esc_attr( $placeholder ) . '"></p>';
                    break;
            }
        }
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

        foreach ( $this->fields as $field ) {
            $field_name = $field['name'];

            if ( array_key_exists( $field_name, $_POST ) ) {
                switch ( $field['type'] ) {
                    case 'textarea':
                        $field_value = sanitize_textarea_field( $_POST[ $field_name ] );
                        break;
                    case 'editor':
                        $field_value = wp_kses_post( $_POST[ $field_name ] );
                        break;
                    default:
                        $field_value = sanitize_text_field( $_POST[ $field_name ] );
                        break;
                }

                if ( $field_value !== '' ) {
                    update_post_meta( $post_id, $field_name, $field_value );
                } else {
                    delete_post_meta( $post_id, $field_name );
                }
            }
        }
    }
}
