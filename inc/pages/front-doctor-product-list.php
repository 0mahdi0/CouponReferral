<?php
function doctor_product_list_shortcode() {
    $args = array(
        'post_type' => 'product',
        'posts_per_page' => -1
    );
    $products = new WP_Query($args);

    ob_start();
    if ($products->have_posts()) {
        echo '<div class="doctor-product-list">';
        while ($products->have_posts()) {
            $products->the_post();
            global $product;
            $doctor_price = get_post_meta($product->get_id(), '_doctor_price', true);
            
            echo '<div class="product-item">';
            echo '<a href="' . get_permalink() . '" class="product-title">' . get_the_title() . '</a>';
            echo '<div class="price-container">';
            echo '<span class="doctor-price">قیمت پزشک: ' . wc_price($doctor_price) . '</span>';
            echo '<span class="patient-price">قیمت بیمار: ' . $product->get_price_html() . '</span>';
            echo '</div>';
            echo '</div>';
        }
        echo '</div>';
    } else {
        echo '<p class="no-products">محصولی یافت نشد.</p>';
    }
    wp_reset_postdata();
    return ob_get_clean();
}
