jQuery(document).ready(function($) {

    $.ajax({
        url: currencyExchange.ajaxurl,
        method: 'GET',
        data: {
            action: 'get_currency_exchange',
        },
        success: function(response) {
            // Clear the container before injecting new HTML
            $('#currency-exchange').empty().html(response);
        },
        error: function(error) {
            console.error('Error fetching currency exchange data:', error);
        }
    });

});
