<?php

class Meta_Part_Title extends Abstract_Multiple_Meta {
    protected $title = 'My title';
    protected $meta_key = 'my_part_title';
    protected $nonce = '_my_part_title_nonce';

    public function inputs( $values ) {
?>
        <p>
        <input
            type="text" class="large-text" placeholder="Part 1" required
            name="<?php echo $this->get_input_name( 'part_1' ); ?>"
            value="<?php echo $this->get_input_value( $values, 'part_1' ); ?>"
        >
        </p>
        <p>
        <input
            type="text" class="large-text" placeholder="Part 2" required
            name="<?php echo $this->get_input_name( 'part_2' ); ?>"
            value="<?php echo $this->get_input_value( $values, 'part_2' ); ?>"
        >
        </p>
<?php
    }
}
