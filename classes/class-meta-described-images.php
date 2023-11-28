<?php

class Meta_Described_Images extends Abstract_Repeat_Meta {
    protected $title = 'My images';
    protected $meta_key = 'my_desc_images';
    protected $nonce = '_my_desc_images_nonce';

    public function inputs( $values, $iteration ) {
?>
        <input
            type="text" placeholder="Image URL" required
            name="<?php echo $this->get_input_name( $iteration, 'img' ); ?>"
            value="<?php echo $this->get_input_value( $values, $iteration, 'img' ); ?>"
        >
        <input
            type="text" placeholder="Description" required
            name="<?php echo $this->get_input_name( $iteration, 'info' ); ?>"
            value="<?php echo $this->get_input_value( $values, $iteration, 'info' ); ?>"
        >
<?php
    }
}
