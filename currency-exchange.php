<?php
/*
Plugin Name: Currency Exchange Display
Description: Displays currency exchange rates at the top of the page.
Version: 1.0
Author: Maahyar Azad
*/

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

function currency_exchange_enqueue_scripts() {

    wp_enqueue_script('currency-exchange-js', plugin_dir_url(__FILE__) . 'js/currency-exchange.js', array('jquery'), '1.0', true);

    wp_enqueue_style('currency-exchange-css', plugin_dir_url(__FILE__) . 'css/style.css', array(), '1.0');

    wp_localize_script('currency-exchange-js', 'currencyExchange', array(
        'ajaxurl' => admin_url('admin-ajax.php'), // This will be used to make AJAX requests
    ));
}

add_action('wp_enqueue_scripts', 'currency_exchange_enqueue_scripts');

// Add a shortcode to display the currency exchange
function currency_exchange_shortcode() {
    ob_start();
    ?>
    <div id="currency-exchange-container">
        <div id="currency-exchange">Loading exchange rates...</div>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode('currency_exchange', 'currency_exchange_shortcode');

// Fetch the currency exchange rates from the API
function currency_exchange_fetch_data() {
    // Ensure WordPress knows it's an AJAX request
    if ( ! defined( 'DOING_AJAX' ) || ! DOING_AJAX ) {
        die('Direct access not allowed');
    }

    $response = wp_remote_get('https://open.er-api.com/v6/latest/AED');
    
    if (is_wp_error($response)) {
        wp_die();
        return 'Error fetching exchange rates.';
    }

    $irr_response = wp_remote_get("https://api-web.moneyro.app/multi_currency_rate/recent_rates/");

    if (is_wp_error($irr_response)) {
        wp_send_json_error(array("error" => "Failed to fetch data."));
    }

    $irr_body = wp_remote_retrieve_body($irr_response);
        
    $currency_data = json_decode($irr_body, true);

    // Ensure the correct key exists in the response
    $selling_rate = isset($currency_data['AED']['when_selling_currency_to_user']['change_in_rial']) 
                    ? $currency_data['AED']['when_selling_currency_to_user']['change_in_rial']     
                    : null;

    $data = json_decode(wp_remote_retrieve_body($response), true);

    if ($data['result'] === 'success') {
        $rates = $data['rates'];
        $exchange_info = '<div id="currency-exchange" class="currency-exchange-container">';
        $exchange_info .= '<strong class="desktop-text">Exchange Rates: </strong>';
    
        // Prepare the exchange rates to display, focusing on USD, AED, and EUR
        $currencies_to_display = ['USD', 'EUR'];
    
        foreach ($currencies_to_display as $currency) {
            if (isset($rates[$currency])) {
                // Set the appropriate image for each currency
                $currency_image = '';
                switch ($currency) {
                    case 'USD':
                        $currency_image = '<div class="currency-flag-wrapper"><img alt="usd" loading="lazy" decoding="async" src="https://dgland.ae/wp-content/uploads/2025/03/usd.static.svg" class="currency-flag"></div>';
                        break;
                    case 'EUR':
                        $currency_image = '<div class="currency-flag-wrapper"><img alt="eur" loading="lazy" decoding="async" src="https://dgland.ae/wp-content/uploads/2025/03/eur.static.svg" class="currency-flag"></div>';
                        break;
                }
        
                // Get the formatted rate
                $formatted_rate = number_format($rates[$currency], 4);
        
                // Wrap the currency rate text inside a separate div with data attribute
                $currency_rate = '<div class="currency-rate" data-currency="AED/' . $currency . ' ' . number_format($rates[$currency], 4) . '">AED/' . $currency . '   ' . number_format($rates[$currency], 4) . '</div>';

        
                // Append the content to the $exchange_info variable
                $exchange_info .= '<div class="currency-info-wrapper">' . $currency_image . $currency_rate . '</div> | ';
            }
        }
        
        // Add the AED/IRR exchange rate
        if ($selling_rate !== null) {
            $currency_image = '<div class="currency-flag-wrapper"><img alt="irr" loading="lazy" decoding="async" src="https://dgland.ae/wp-content/uploads/2025/03/irr.static.svg" class="currency-flag"></div>';
        
            // Get the formatted IRR rate
            $formatted_irr_rate = number_format($selling_rate, 0);
        
            // Wrap the currency rate text inside a separate div with data attribute
            $currency_rate = '<div class="currency-rate" data-currency="AED/IRR ' . $formatted_irr_rate . '">AED/IRR   ' . $formatted_irr_rate . '</div>';
        
            // Append the content to the $exchange_info variable
            $exchange_info .= '<div class="currency-info-wrapper">' . $currency_image . $currency_rate . '</div> | ';
        }
        

        // Remove trailing separator and close the container div
        $exchange_info = rtrim($exchange_info, ' | ') . '</div>';
    
        echo wp_kses_post($exchange_info);
        wp_die();
    }
    
    
}

add_action('wp_ajax_get_currency_exchange', 'currency_exchange_fetch_data');
add_action('wp_ajax_nopriv_get_currency_exchange', 'currency_exchange_fetch_data');

// Add a JS handler to update the data
function currency_exchange_js_handler() {
    ?>
    <script type="text/javascript">
        jQuery(document).ready(function($) {
            // Fetch the data from the WordPress AJAX endpoint
            $.ajax({
                url: '<?php echo admin_url('admin-ajax.php'); ?>',
                type: 'GET',
                data: {
                    action: 'get_currency_exchange'
                },
                success: function(response) {
                    $('#currency-exchange').text(response);
                },
                error: function() {
                    $('#currency-exchange').text('Error fetching exchange rates.');
                }
            });
        });
    </script>
    </script>
    <?php
}

add_action('wp_footer', 'currency_exchange_js_handler');
