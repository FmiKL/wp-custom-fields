<?php
/**
 * An abstract class extended to create meta boxes.
 * Provides required methods that can be used or overridden as needed.
 *
 * @package WP_Custom_Fields
 * @author Mikael Fourré
 * @version 2.0.2
 * @see https://github.com/FmiKL/wp-custom-fields
 */
abstract class Abstract_Meta {
    /**
     * Path to the assets.
     * 
     * @var string
     * @since 2.0.0
     * @see Option_Page::enqueues_assets()
     */
    private const ASSETS_PATH = '/wp-custom-fields/assets';

    /**
     * User capability required to edit the post.
     *
     * @var string
     * @since 1.0.0
     * @see Abstract_Meta::set_capability()
     * @link https://wordpress.org/support/article/roles-and-capabilities/
     */
    protected $capability = 'publish_posts';

    /**
     * Types of posts or pages on which the meta box will be displayed,
     * identified by their type or identifiers.
     *
     * @var array<string|int>
     * @since 1.0.0
     * @see Abstract_Meta::set_enables()
     */
    protected $enables = array( 'post', 'page' );

    /**
     * Title of the meta box.
     *
     * @var string
     * @since 1.0.0
     */
    protected $title;

    /**
     * Key used for the nonce field.
     *
     * @var string
     * @since 1.0.0
     */
    protected $nonce_key;

    /**
     * Key used for the action associated with the nonce.
     *
     * @var string
     * @since 1.0.0
     */
    protected $action_key;

    /**
     * Unique identifier of the meta box.
     *
     * @var string
     * @since 1.0.0
     */
    protected $id;

    /**
     * Grouped fields wrapped in a meta box.
     *
     * @var array<string, array>
     * @since 2.0.0
     * @see Abstract_Meta::group_fields()
     */
    protected $groups = array();

    /**
     * Fields for add to the meta box.
     *
     * @var array<array>
     * @since 2.0.0
     * @see Abstract_Meta::add_field()
     */
    protected $fields = array();

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
     * Adds hooks the methods to the appropriate actions.
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
        array_push( $this->fields, $field );

        return $field;
    }

    /**
     * Groups the fields together.
     *
     * @param string $prefix    Prefix for the group.
     * @param array  ...$fields Fields to group together.
     * @since 2.0.0
     */
    public function group_fields( $prefix, ...$fields ) {
        if ( ! isset( $this->groups[ $prefix ] ) ) {
            $this->groups[ $prefix ] = array();
        }

        foreach ( $fields as $field ) {
            array_push( $this->groups[ $prefix ], $field );
        }
    }

    /**
     * Enqueues the necessary scripts and styles.
     * 
     * @since 1.0.0
     */
    public function enqueues_assets() {
        $assets_path_directory_uri = get_template_directory_uri() . self::ASSETS_PATH;

        if ( ! wp_script_is( 'field-media', 'registered' ) ) {
            wp_enqueue_script( 'field-media', $assets_path_directory_uri . '/js/field-media.js', array(), false, true );
        }

        if ( ! wp_script_is( 'field-repeater', 'registered' ) ) {
            wp_enqueue_script( 'field-repeater', $assets_path_directory_uri . '/js/field-repeater.js', array(), false, true );
        }

        if ( ! wp_style_is( 'field-meta', 'registered' ) ) {
            wp_enqueue_style( 'field-meta', $assets_path_directory_uri . '/css/field-meta.css' );
        }
    }

    /**
     * Verifies the nonce and the user capability.
     *
     * @param int   $post_id Post ID of the post being saved.
     * @return bool Returns true if the nonce is valid and the user has the capability to save the meta box.
     * @since 1.0.0
     */
    protected function check_security( $post_id ) {
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
     * Renders the meta box.
     *
     * @param WP_Post $post Post object of the post being edited.
     * @since 1.0.0
     */
    abstract public function render( $post );

    /**
     * Saves the meta box.
     *
     * @param int $post_id Post ID of the post being saved.
     * @since 1.0.0
     */
    abstract public function save( $post_id );
}
