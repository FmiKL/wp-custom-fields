<?php
/**
 * Extend and implement the "inputs" method
 * to create a repeater field.
 */
abstract class Abstract_Repeat_Meta {
    /**
     * The types of capability required to save
     * the post meta.
     * 
     * The capability "publish_posts" can edit
     * pages and articles while "publish_pages"
     * only does pages.
     *
     * @var string
     */
    protected $capability = 'publish_posts';

    /**
     * The types of content where the meta
     * box should be enabled.
     * 
     * The types can be used, but also the IDs
     * or Slugs of the pages on which the meta
     * must be present.
     *
     * @var array
     */
    protected $enables = array( 'post', 'page' );

    /**
     * The context in the screen where the
     * box should appear.
     *
     * @var string One of "normal", "side" or "advanced".
     */
    protected $context = 'advanced';
    
    /**
     * The title of the meta box.
     *
     * @var string
     */
    protected $title;

    /**
     * The name of the meta key used to
     * store the values.
     *
     * @var string
     */
    protected $meta_key;

    /**
     * The nonce value used to verify
     * the form submission.
     *
     * @var string
     */
    protected $nonce;

    /**
     * Used to define key to replace.
     * 
     * @var string
     */
    private const INPUT_ROW_KEY = '_row';

    /**
     * Initialize the methods.
     *
     * @return void
     */
    public function init() {
        add_action( 'add_meta_boxes', array( $this, 'add' ), 10, 2 );
        add_action( 'save_post', array( $this, 'save' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
    }

    /**
     * Add the meta box to the page.
     *
     * @param string $post_type
     * @param WP_Post $post
     * @return void
     */
    public function add( $post_type, $post ) {
        if (
            in_array( $post->ID, $this->enables ) ||
            in_array( $post_type, $this->enables ) ||
            in_array( $post->post_name, $this->enables )
        ) {
            add_meta_box( $this->meta_key, $this->title, array( $this, 'render' ), null, $this->context );
        }
    }

    /**
     * Render the form fields.
     *
     * @param WP_Post $post
     * @return void
     */
    public function render( $post ) {
        $postMeta = get_post_meta( $post->ID, $this->meta_key, true );
        $values = json_decode( wp_unslash( $postMeta ), true ) ?? array();

        wp_nonce_field( $this->nonce, $this->nonce );
?>
        <table class="wrapper js-repeater">
            <tbody class="container js-container">
                <?php for ( $i = -1; $i < count( $values ); $i++ ) : ?>
                    <tr class="<?= ( $i == -1 ) ? 'js-template' : 'js-element' ?>">
                    <td>
                        <button type="button" class="js-move-up button button-secondary">&uarr;</button>
                        <button type="button" class="js-move-down button button-secondary">&darr;</button>
                    </td>
                    <td>
                        <input type="hidden" name="_<?php echo $this->meta_key; ?>_sent" value="1">
                        <?php $this->inputs( $values, $i ); ?>
                    </td>
                    <td>
                        <button type="button" class="js-remove button button-secondary">Remove</button>
                    </td>
                    </tr>
                <?php endfor; ?>
            </tbody>
            <tfoot>
            <tr>
                <td colspan="3">
                    <button type="button" class="js-add button button-primary button-large">Add</button>
                </td>
            </tr>
            </tfoot>
        </table>
<?php
    }

    /**
     * Create the fields to repeat.
     *
     * @param array $values
     * @param integer $iteration
     * @return void
     */
    abstract protected function inputs( $values, $iteration );

    /**
     * Get the name key from the input.
     *
     * @param integer $iteration
     * @param string $key
     * @return string
     */
    protected function get_input_name( $iteration, $key = '' ) {
        $name = $this->meta_key;

        if ( $iteration == -1 ) {
            $name .= '[' . self::INPUT_ROW_KEY . ']';
        } else {
            $name .= '[' . $iteration . ']';
        }

        if ( $key ) {
            $name .= '[' . $key . ']';
        }

        return $name;
    }

    /**
     * Get the default value of the input.
     *
     * @param array $values
     * @param integer $iteration
     * @param string $key
     * @param string $default
     * @return string
     */
    protected function get_input_value( $values, $iteration, $key = '', $default = '' ) {
        if ( $key ) {
            return $values[ $iteration ][ $key ] ?? $default;
        } else {
            return $values[ $iteration ] ?? $default;
        }
    }

    /**
     * Add the script that allows fields
     * to be repeated and to recover a media.
     *
     * @return void
     */
    public function enqueue_scripts() {
        wp_enqueue_script( 'repeater-script', get_stylesheet_directory_uri() . '/assets/js/field-repeater.js', array(), false, true );
    }

    /**
     * Save values in database.
     *
     * @param integer $post_id
     * @return void
     */
    public function save( $post_id ) {
        if ( array_key_exists( $this->meta_key, $_POST ) ) {
            $meta_data = array_values( $_POST[ $this->meta_key ] );
            
            foreach ( $meta_data as $key => $value ) {
                if ( ! empty( $value ) ) {
                    $value = str_replace( [ "\r\n", "'", '"' ], [ '', '&rsquo;', '&quot;' ],  $value );
                    $meta_data[ $key ] = $value;
                }
            }

            $data = json_encode( $meta_data, JSON_UNESCAPED_UNICODE );
            update_post_meta( $post_id, $this->meta_key, $data );
        } else {
            delete_post_meta( $post_id, $this->meta_key );
        }
    }
}
