<?php

class Meta_Image extends Abstract_Simple_Meta {
    protected $title = 'My image';
    protected $meta_key = 'my_image';
    protected $nonce = '_my_image_nonce';

    public function input( $value ) {
?>
        <p>
        <input
            type="text" class="large-text" placeholder="Image URL" required
            name="<?php echo $this->meta_key; ?>" value="<?php echo $value; ?>"
        >
        </p>
<?php
    }
}
