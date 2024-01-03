<?php
/**
 * Class used to create a metabox with one or more
 * multiple custom fields.
 * 
 * @package WP_Custom_Fields
 * @author Mikael Fourré
 * @version 2.0.2
 * @see https://github.com/FmiKL/wp-custom-fields
 */
class Multiple_Meta extends Abstract_Meta {
    /**
     * Renders the meta box.
     *
     * @param WP_Post $post Post object of the post being edited.
     * @since 1.0.0
     */
    public function render( $post ) {
        wp_nonce_field( $this->action_key, $this->nonce_key );

        $index = 0;

        echo '<table class="form-table table-tight">';
        foreach ( $this->groups as $prefix => $group ) {
            $row_class = $index % 2 === 0 ? 'even-row' : 'odd-row';
            echo '<tr class="form-field ' . $row_class . '">';
            if ( is_array( $group ) ) {
                $field_count = count( $group );
                $field_index = 0;
                foreach ( $group as $field ) {
                    if ( is_array( $field ) && isset( $field['name'] ) ) {
                        $field_name  = $prefix . $field['name'];
                        $value       = get_post_meta( $post->ID, $field_name, true );
                        $placeholder = $field['options']['placeholder'] ?? '';

                        $field_class = '';
                        if ( $field_index === 0 ) {
                            $field_class = 'first-field';
                        } else if ( $field_index === $field_count - 1 ) {
                            $field_class = 'last-field';
                        }

                        echo '<td>';
                        if ( $field['type'] === 'textarea' ) {
                            $rows = $field['options']['rows'] ?? '5';
                            echo '<textarea class="large-text ' . $field_class . '" name="' . esc_attr( $field_name ) . '" placeholder="' . esc_attr( $placeholder ) . '" rows="' . esc_attr( $rows ) . '">' . esc_textarea( $value ) . '</textarea>';
                        } else {
                            if ( $field['type'] === 'date' ) {
                                $date = DateTime::createFromFormat( 'Y-m-d', $value );
                                if ( $date !== false ) {
                                    $value = $date->format( 'd-m-Y' );
                                }
                            }

                            echo '<input type="' . esc_attr( $field['type'] ) . '" class="large-text ' . $field_class . '" name="' . esc_attr( $field_name ) . '" value="' . esc_attr( $value ) . '" placeholder="' . esc_attr( $placeholder ) . '">';
                        }
                        echo '</td>';

                        $field_index++;
                    }
                }
            }
            echo '</tr>';

            $index++;
        }
        echo '</table>';
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

        foreach ( $this->groups as $prefix => $group ) {
            if ( is_array( $group ) ) {
                foreach ( $group as $field ) {
                    if ( is_array( $field ) && isset( $field['name'] ) ) {
                        $field_name = $prefix . $field['name'];

                        if ( array_key_exists( $field_name, $_POST ) ) {
                            if ( $field['type'] === 'textarea' ) {
                                $field_value = sanitize_textarea_field( $_POST[ $field_name ] );
                            } else {
                                $field_value = sanitize_text_field( $_POST[ $field_name ] );
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
        }
    }
}
