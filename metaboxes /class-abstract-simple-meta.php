<?php
/**
 * Extend and implement the "input" method
 * to create a simple field.
 */
abstract class Abstract_Simple_Meta {
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
    protected string $context = 'advanced';
    
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
     * Render the form field.
     *
     * @param WP_Post $post
     * @return void
     */
    public function render( $post ) {
        wp_nonce_field( $this->nonce, $this->nonce );

        $this->input( get_post_meta( $post->ID, $this->meta_key, true ) );
    }

    /**
     * Create the field.
     *
     * @param string $value
     * @return void
     */
    abstract protected function input( $value );

    /**
     * Saves value in database.
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
            if ( $_POST[ $this->meta_key ] != '' ) {
                update_post_meta( $post_id, $this->meta_key, $_POST[ $this->meta_key ] );
            } else {
                delete_post_meta( $post_id, $this->meta_key );
            }
        }
    }
}
