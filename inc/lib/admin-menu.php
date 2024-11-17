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
            "خرید ارزان",
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
