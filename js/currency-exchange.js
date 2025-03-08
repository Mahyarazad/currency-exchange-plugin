jQuery(document).ready(function($) {
    
    // AJAX call to fetch currency exchange data
    $.ajax({
        url: currencyExchange.ajaxurl, // WordPress provided AJAX URL
        method: 'GET',
        data: {
            action: 'get_currency_exchange',
        },
        success: function(response) {
            // Add the returned HTML to the body
            $('body').prepend(response);

        },
        error: function(error) {
            console.error('Error fetching currency exchange data:', error);
        }
    });
});
