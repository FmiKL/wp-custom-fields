// Wait for the document to be loaded
jQuery(document).ready(function($) {
    // For each element with the 'js-repeater' class
    $('.js-repeater').each(function(index, element) {
        // Clone the template element
        const template = $('.js-template', element).clone();
    
        // Remove the template element
        $('.js-template', element).remove();
    
        // Define function to handle each repeated element
        function handleElement(element) {
            // Update the name attribute of the input element(s)
            $('input[name*="_row"]', element).each(function(index, element) {
                const nbElements = $('.js-element').length;
                element.name = element.name.replace('[_row]', `[${nbElements}]`);
            });
    
            // Handle remove element button click event
            $('.js-remove', element).click(function() {
                $(element).remove();
            });
    
            // Handle move up button click event
            $('.js-move-up', element).click(function() {
                $(element).insertBefore($(element).prev());
            });
    
            // Handle move down button click event
            $('.js-move-down', element).click(function() {
                $(element).insertAfter($(element).next());
            });
        }
    
        // Handle each repeated element
        $('.js-element', element).each((index, element) => {
            handleElement(element);
        });
    
        // Handle add new element button click event
        $('.js-add', element).click(function() {
            // Clone a new template element
            const newTemplate = template.clone();
    
            // Prepare the new element
            newTemplate.removeClass('js-template').addClass('js-element');
    
            // Insert the new element
            newTemplate.appendTo($('.js-container', element)).show();
    
            // Handle the new element
            handleElement(newTemplate);
        });
    });
});
