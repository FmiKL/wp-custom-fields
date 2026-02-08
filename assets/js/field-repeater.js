jQuery(document).ready(function($) {
    $('.table-repeater').each(function(index, element) {
        // Keep an in-memory copy of the template row and remove it from the DOM.
        const template = $('.table-template', element).clone();
        $('.table-template', element).remove();

        function handleElement(element) {
            // Replace the temporary "_row" index with the next numeric index.
            $('input[name*="_row"]', element).each(function(index, element) {
                const nbElements = $('.table-element').length;
                element.name = element.name.replace('[_row]', `[${nbElements}]`);
            });

            $('.button-remove', element).click(function() {
                // Keep at least one row visible: clear first row instead of removing it.
                if ($(element).is(':first-child')) {
                    $('input', element).val('');
                } else {
                    $(element).remove();
                }
            });

            $('.button-move-up', element).click(function() {
                $(element).insertBefore($(element).prev());
            });

            $('.button-move-down', element).click(function() {
                $(element).insertAfter($(element).next());
            });
        }

        $('.table-element', element).each((index, element) => {
            handleElement(element);
        });

        $('.button-add', element).click(function() {
            // Clone the template, convert it to a live row, and wire its controls.
            const newTemplate = template.clone();
            newTemplate.removeClass('table-template').addClass('table-element');
            newTemplate.appendTo($('.table-container', element)).show();
            handleElement(newTemplate);
        });
    });
});
