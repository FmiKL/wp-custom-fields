// Wait for the document to be loaded
jQuery(document).ready(function($) {
    // If the placeholder contains
    const inputMatch = /image|avatar|icon/i;

    // Holds the media uploader instance
    let mediaUploader;

    // When a double-click event is fired on an input element with a "placeholder" attribute containing "image"
    $(document).on('dblclick', 'input', function(e) {
        e.preventDefault();
        
        if (!($(this).attr('placeholder') && $(this).attr('placeholder').match(inputMatch))) {
            return;
        }

        // If the media uploader instance already exists
        if (mediaUploader) {
            mediaUploader.open();
            return;
        }

        const input = this;

        // Create a new instance of the media uploader
        mediaUploader = wp.media();

        // When an image is selected
        mediaUploader.on('select', function() {
            // Set the value of the clicked input element to the URL of the thumbnail image
            const attachment = mediaUploader.state().get('selection').first().toJSON();
            const imageUrl = attachment.url;

            // Check if the string contains a size value
            const matches = $(input).attr('placeholder').match(/\d+px/);

            if (matches && attachment.sizes && Object.keys(attachment.sizes).length > 0) {
                let thumbnailUrl = '';

                // Find the closest size
                const sizes = Object.keys(attachment.sizes).sort((a, b) => {
                    const size = parseInt(matches[0]);
                    return Math.abs(attachment.sizes[a].width - size) - Math.abs(attachment.sizes[b].width - size);
                });

                // Use the closest size if it is smaller than the original image
                if (attachment.sizes[sizes[0]].width < attachment.width) {
                    thumbnailUrl = attachment.sizes[sizes[0]].url;
                } else {
                    thumbnailUrl = imageUrl;
                }

                $(input).val(thumbnailUrl);
            } else {
                $(input).val(imageUrl);
            }

            // Reset the media uploader instance
            mediaUploader = null;
        });

        // Open the media uploader
        mediaUploader.open();
    });
});
