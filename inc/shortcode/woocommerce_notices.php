<?php

add_shortcode('woocommerce_notices', 'display_woocommerce_notices');
function display_woocommerce_notices()
{
    if (function_exists('wc_print_notices')) {
        wc_print_notices();
    }
}
