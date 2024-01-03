<?php
/**
 * Class used to create a metabox with one or more
 * repeatable custom fields.
 * 
 * @package WP_Custom_Fields
 * @author Mikael Fourré
 * @version 2.0.2
 * @see https://github.com/FmiKL/wp-custom-fields
 */
class Repeat_Meta extends Abstract_Meta {
    /**
     * Key used to identify a row.
     * @since 1.0.0
     */
    private const INPUT_ROW_KEY = '_row';

    /**
     * Renders the meta box.
     *
     * @param WP_Post $post Post object of the post being edited.
     * @since 1.0.0
     */
    public function render( $post ) {
        wp_nonce_field( $this->action_key, $this->nonce_key );

        foreach ( $this->groups as $prefix => $group ) {
            $this->render_table( $post, $prefix, $group );
        }
    }

    /**
     * Renders a table with the added fields.
     *
     * @param WP_Post $post   Post object being edited.
     * @param string  $prefix Prefix of the fields.
     * @param array   $group  Grouped fields to render.
     * @since 2.0.0
     */
    private function render_table( $post, $prefix, $group ) {
        ?>
        <table class="wrapper table-repeater">
            <tbody class="container table-container">
                <?php $this->render_template( $prefix, $group ); ?>
                <?php $this->render_elements( $post, $prefix, $group ); ?>
            </tbody>
            <?php $this->render_footer(); ?>
        </table>
        <?php
    }

    /**
     * Renders a table template row.
     *
     * @param string $prefix Prefix of the fields.
     * @param array  $group  Grouped fields to render.
     * @since 2.0.0
     */
    private function render_template( $prefix, $group ) {
        ?>
        <tr class="table-template">
            <td>
                <button type="button" class="button-move-up button button-secondary">&uarr;</button>
                <button type="button" class="button-move-down button button-secondary">&darr;</button>
            </td>
            <td>
                <?php 
                list( $first_key, $last_key ) = $this->get_first_and_last_keys( $group );
                foreach ( $group as $key => $field ) : ?>
                    <input
                        type="text" class="large-text <?php echo ( $key === $first_key || count( $group ) === 1 ) ? 'first-field' : ''; ?> <?php echo $key === $last_key ? 'last-field' : ''; ?>"
                        name="<?php echo esc_attr( $this->id . '[' . $prefix . $field['name'] . '][' . self::INPUT_ROW_KEY . ']' ); ?>"
                        placeholder="<?php echo esc_attr( $field['options']['placeholder'] ?? '' ); ?>"
                    >
                <?php endforeach; ?>
            </td>
            <td>
                <button type="button" class="button-remove button button-secondary">&times;</button>
            </td>
        </tr>
        <?php
    }

    /**
     * Returns the first and last keys of an array.
     *
     * @param array  $array Array to get the keys from.
     * @return array An array with the first and last keys.
     * @since 2.0.0
     */
    private function get_first_and_last_keys( $array )  {
        $keys      = array_keys( $array );
        $first_key = reset( $keys );
        $last_key  = end( $keys );
    
        return array( $first_key, $last_key );
    }

    /**
     * Renders the existing elements.
     *
     * @param WP_Post $post   Post object being edited.
     * @param string  $prefix Prefix of the fields.
     * @param array   $group  Grouped fields to render.
     * @since 2.0.0
     */
    private function render_elements( $post, $prefix, $group ) {
        $data = $this->get_data( $post, $prefix, $group );

        if ( ! empty( $data ) ) {
            $iteration = 0;
            foreach ( $this->get_fields( $data ) as $field ) {
                $iteration++;
                $this->render_element( $prefix, $field, $iteration );
            }
        }
    }

    /**
     * Returns the data for the given group.
     *
     * @param WP_Post $post   Post object being edited.
     * @param string  $prefix Prefix of the fields.
     * @param array   $group  Grouped fields to render.
     * @return array  Data for the given group.
     * @since 2.0.0
     */
    private function get_data( $post, $prefix, $group ) {
        $data = array();
        foreach ( $group as $field ) {
            $post_meta = get_post_meta( $post->ID, $prefix . $field['name'], true );
            if ( $post_meta ) {
                $data[ $field['name'] ] = $post_meta;
            }
        }
        return $data;
    }

    /**
     * Returns the fields from the given array.
     *
     * @param array  $data Array to get the fields from.
     * @return array Fields from the given array.
     * @since 2.0.0
     */
    private function get_fields( $array ) {
        $fields = array();
        foreach ( $array as $k => $sub_array ) {
            foreach ( $sub_array as $l => $value ) {
                $fields[ $l ][ $k ] = $value;
            }
        }
        return $fields;
    }

    /**
     * Renders a table element row.
     *
     * @param string $prefix    Prefix of the fields.
     * @param array  $field     Field to render.
     * @param int    $iteration Iteration number of the field.
     * @since 2.0.0
     */
    private function render_element( $prefix, $field, $iteration ) {
        ?>
        <tr class="table-element">
            <td>
                <button type="button" class="button-move-up button button-secondary">&uarr;</button>
                <button type="button" class="button-move-down button button-secondary">&darr;</button>
            </td>
            <td>
                <?php 
                list( $first_key, $last_key ) = $this->get_first_and_last_keys( $field );
                foreach ( $field as $key => $value ) : ?>
                    <input
                        type="text" class="large-text <?php echo ( $key === $first_key || count( $field ) === 1 ) ? 'first-field' : ''; ?> <?php echo $key === $last_key ? 'last-field' : ''; ?>"
                        name="<?php echo esc_attr( $this->id . '[' . $prefix . $key . '][' . $iteration . ']' ); ?>" value="<?php echo esc_attr( $value ); ?>"
                    >
                <?php endforeach; ?>
            </td>
            <td>
                <button type="button" class="button-remove button button-secondary">&times;</button>
            </td>
        </tr>
        <?php
    }

    /**
     * Renders a table footer.
     * @since 2.0.0
     */
    private function render_footer() {
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

        if ( array_key_exists( $this->id, $_POST ) && is_array( $_POST[ $this->id ] ) ) {
            $post_data = array();
            foreach ( $_POST[ $this->id ] as $key => $values ) {
                if ( is_array( $values ) ) {
                    foreach ( $values as $value ) {
                        $post_data[ $key ][] = sanitize_text_field( $value );
                    }
                }
            }

            foreach ( $post_data as $key => $value ) {
                if ( ! empty( array_filter( $value ) ) ) {
                    update_post_meta( $post_id, $key, $value );
                } else {
                    delete_post_meta( $post_id, $key );
                }
            }
        }
    }
}
