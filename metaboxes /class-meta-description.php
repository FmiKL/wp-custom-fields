<?php

class Meta_Description extends Abstract_Simple_Meta {
    protected $title = 'My description';
    protected $meta_key = 'my_description';
    protected $nonce = '_my_description_nonce';

    public function input( $value ) {
?>
        <p>
        <input
            type="text" class="large-text" placeholder="Description" required
            name="<?php echo $this->meta_key; ?>" value="<?php echo $value; ?>"
        >
        </p>
<?php
    }
}
