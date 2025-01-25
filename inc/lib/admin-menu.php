<?php

class CouponReferralMenu
{
    public function __construct()
    {
        add_action('wp_ajax_xcpc_submit_setting_form', [$this, 'xcpc_submit_setting_form']);
    }

    public function addCouponReferralMenu()
    {
        add_menu_page(
            "CouponReferral",
            "خرید ویژه",
            "manage_options",
            "couponreferral",
            [$this, "callbackCouponReferralMenu"],
            "dashicons-cart"
        );
        add_action('admin_enqueue_scripts', [$this, 'xcpcAdminAssets']);
    }

    public function xcpc_submit_setting_form()
    {
        // check_ajax_referer('xcpc_submit_admin_form_nonce', 'security');
        // Retrieve form data
        $formDatas = $_POST['formData'];
        // // Access each form field's content
        // foreach ($formDatas as $key => $formData) {
        //     $formDatas[$key] = sanitize_text_field($formData);
        // }
        // Get all published products
        $args = array(
            'post_type' => 'product',
            'posts_per_page' => -1,
            'post_status' => 'publish', // Only products with 'publish' status
        );
        $product_discount = intval($formDatas['siteDiscount']);
        $special_discount = intval($formDatas['discountCode']);
        $products = get_posts($args);

        foreach ($products as $product_post) {
            $product = wc_get_product($product_post->ID);

            // Get regular price
            $regular_price = $product->get_regular_price();

            if ($regular_price) {
                // Remove existing sale price
                $product->set_sale_price('');

                // Calculate new sale price
                $sale_price = $regular_price - ($regular_price * ($product_discount / 100));
                $special_price = $sale_price - ($sale_price * ($special_discount / 100));
                update_post_meta($product_post->ID, "special_amount", $special_price);
                // Update new sale price
                $product->set_sale_price($sale_price);
                $product->save();
            }
        }

        // Process or store the data as needed
        $xcpcConfig = get_option('xcpcConfig');
        if ($xcpcConfig) {
            update_option("xcpcConfig", $formDatas,);
        } else {
            add_option("xcpcConfig", $formDatas,);
        }
        wp_send_json_success("با موفقیت ذخیره شد", 200);
    }

    public function callbackCouponReferralMenu()
    {
        $xcpcConfig = get_option('xcpcConfig');
        // $xcpcConfig = json_decode($xcpcConfig, true);
        include_once(XCPC_DIR . "inc/pages/callback-coupon-referral-menu.php");
    }
    public function xcpcAdminAssets()
    {
        wp_register_style('xcpc-admin-style', XCPC_URL . 'inc/style/admin.css');
        wp_enqueue_style('xcpc-admin-style');
        wp_enqueue_script('xcpc-admin-script', XCPC_URL . 'inc/js/admin.js', array('jquery'), null, true);
        wp_localize_script('xcpc-admin-script', 'myAjax', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('xcpc_submit_admin_form_nonce')
        ));
    }
}
