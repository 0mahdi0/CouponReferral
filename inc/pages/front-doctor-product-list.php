<?php
function doctor_product_list_shortcode()
{
    $args = array(
        'post_type' => 'product',
        "order" => "ASC",
        'posts_per_page' => -1,
        'post_status'    => 'draft',
        'meta_query'     => [
            [
                'key' => 'product_connections',
            ],
        ],
    );
    $products = new WP_Query($args);

    // Retrieve xcpcConfig option from wp_options
    $xcpcConfig = get_option('xcpcConfig');
    $doctorDiscount = isset($xcpcConfig['doctorDiscount']) ? $xcpcConfig['doctorDiscount'] : 0;

    // Enqueue necessary JavaScript and CSS
    wp_enqueue_script('doctor-product-list-script', XCPC_URL . 'inc/js/doctor-product-list.js', array('jquery'), '1.0', true);
    wp_localize_script('doctor-product-list-script', 'doctorProductList', array(
        'ajax_url' => admin_url('admin-ajax.php')
    ));

    ob_start();
    if ($products->have_posts()) {
        echo '<div class="doctor-product-list">';
        while ($products->have_posts()) {
            $products->the_post();
            global $product;

            // **1. Get the doctor price directly from the product**
            $doctor_price = $product->get_price();

            // Ensure doctor_price is a valid number
            $doctor_price = is_numeric($doctor_price) ? $doctor_price : 0;

            // Apply doctorDiscount to doctor_price
            // Assuming doctorDiscount is a percentage (e.g., 20 for 20%)
            $discounted_price = $doctor_price;

            if (is_numeric($doctorDiscount) && $doctorDiscount > 0) {
                $discount_amount = ($doctor_price * $doctorDiscount) / 100;
                $discounted_price = $doctor_price - $discount_amount;
            }

            // **3. Get current quantity of the product in cart**
            $quantity_in_cart = 0;
            foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item) {
                if ($cart_item['product_id'] == $product->get_id()) {
                    $quantity_in_cart = $cart_item['quantity'];
                    break;
                }
            }

            echo '<div class="product-item" data-product_id="' . esc_attr($product->get_id()) . '">';
            // Display the product image if it exists
            if (has_post_thumbnail()) {
                echo '<span>';
                echo get_the_post_thumbnail($product->get_id(), 'thumbnail');
                echo '</span>';
            }
            // Display the product title
            echo '<p class="product-title">' . get_the_title() . '</p>';
            // Display the prices
            echo '<div class="price-container">';
            echo '<span class="doctor-price">قیمت نماینده: ' . wc_price($discounted_price) . '</span>';
            echo '<span class="patient-price">قیمت برای بیمار: ' . number_format($product->get_price()) . ' تومان</span>';
            echo '</div>';
            // **2. Add quantity buttons and counter with optimized UI**
            echo '<div class="quantity-container">';
            echo '<button class="quantity-btn plus-btn" type="button">+</button>';
            echo '<input type="text" class="quantity-input" value="' . esc_attr($quantity_in_cart) . '" size="2" readonly />';
            echo '<button class="quantity-btn minus-btn" type="button">-</button>';
            echo '</div>';
            echo '</div>';
        }
        echo '</div>';
        echo "<button class='tab' onclick='window.location.href= `https://exirnab.com/doctor-checkout/`'>ادامه و پرداخت</button>";
    } else {
        echo '<p class="no-products">محصولی یافت نشد.</p>';
    }
    wp_reset_postdata();
    return ob_get_clean();
}
add_shortcode('doctor_product_list', 'doctor_product_list_shortcode');
