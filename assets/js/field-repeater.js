jQuery(document).ready(function($) {
    $('.table-repeater').each(function(index, element) {
        const template = $('.table-template', element).clone();
        $('.table-template', element).remove();

        function handleElement(element) {
            $('input[name*="_row"]', element).each(function(index, element) {
                const nbElements = $('.table-element').length;
                element.name = element.name.replace('[_row]', `[${nbElements}]`);
            });

            $('.button-remove', element).click(function() {
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
            const newTemplate = template.clone();
            newTemplate.removeClass('table-template').addClass('table-element');
            newTemplate.appendTo($('.table-container', element)).show();
            handleElement(newTemplate);
        });
    });
});
