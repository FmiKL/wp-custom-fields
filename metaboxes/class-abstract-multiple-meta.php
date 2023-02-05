<?php
/**
 * Extend and implement the "inputs" method
 * to create a multiple fields.
 */
abstract class Abstract_Multiple_Meta {
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

        $this->inputs( $values );
    }

    /**
     * Create the fields.
     *
     * @param array $values
     * @return void
     */
    abstract protected function inputs( $values );

    /**
     * Get the name key from the input.
     *
     * @param string $key
     * @return string
     */
    protected function get_input_name( $key ) {
        return $this->meta_key . '[' . $key . ']';
    }

    /**
     * Get the default value for the input.
     *
     * @param array $values
     * @param string $key
     * @param string $default
     * @return string
     */
    protected function get_input_value( $values, $key, $default = '' ) {
        return $values[ $key ] ?? $default;
    }

    /**
     * Add the script that allows
     * to recover a media.
     *
     * @return void
     */
    public function enqueue_scripts() {
        wp_enqueue_script( 'media-script', get_stylesheet_directory_uri() . '/assets/js/field-media.js', array(), false, true );
    }

    /**
     * Save values in database.
     *
     * @param integer $post_id
     * @return void
     */
    public function save( $post_id ) {
        if (
            array_key_exists( $this->meta_key, $_POST ) &&
            current_user_can( $this->capability, $post_id ) &&
            wp_verify_nonce( $_POST[ $this->nonce ], $this->nonce )
        ) {
            if ( array_key_exists( $this->meta_key, $_POST ) ) {
                $meta_data = $_POST[ $this->meta_key ];
                
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
}
