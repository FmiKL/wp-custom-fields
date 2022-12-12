<?php

class Meta_Content extends Abstract_Simple_Meta {
    protected $title = 'My content';
    protected $meta_key = 'my_content';
    protected $nonce = '_my_content_nonce';

    public function input( $value ) {
        $settings = array(
            'textarea_name' => $this->meta_key,
            'media_buttons' => false,
            'wpautop'       => false,
        );

        wp_editor( $value, $this->meta_key, $settings );
    }
}
