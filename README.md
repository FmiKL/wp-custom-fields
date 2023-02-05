# Custom Fields for WordPress

This repository aims to provide examples of classes for creating custom fields for WordPress that can be used to avoid the use of an external plugin.

Simply extend the corresponding abstract class and implement the desired parameters as well as the method that will contain the fields.

To use wp media in your form, make sure that the placeholder of the input contains the word "image," "avatar," or "icon."


## Classes to extend depending on use case:

- Abstract_Simple_Meta: extend this class to create a simple field with a single value.
- Abstract_Multiple_Meta: extend this class to create a multiple field with multiple values.
- Abstract_Repeat_Meta: extend this class to create a simple/multiple field with repeating value(s).


## Some examples based on desired fields:

- Meta_Description: a class to create a simple content field, such as a meta-description.
- Meta_Content: a class to create a field with a visual text editor to add, for example, an HTML text area.
- Meta_Part_Title: a class to create a multiple field with two content areas, such as a two-part title.
- Meta_Described_Images: a class to create a repeat field with two content areas, an image and its description.
- Meta_Image: a class to create a simple image field, with an opening on a media.
