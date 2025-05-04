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
        add_menu_page('فیش های واریزی', 'فیش های واریزی', 'edit_posts', 'receipt_images', [$this, 'receipt_images'], 'dashicons-awards', 20);
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
    // $user_id = get_current_user_id();
    // if ($user_id == 85) {
    //     $order_id = 12322;
    //     $user = get_user_by('id', 97);
    //     $phone = get_user_meta(97, 'billing_phone', true);
    //     $order = wc_get_order(12322);
    //     $display_name = $user->display_name;
    //     $order_edit_link = $order->get_edit_order_url();
    //     $total_amount = $order->get_total();
    //     $product_text_list = "";
    //     foreach ($order->get_items() as $item_id => $item) {
    //         $product_name = $item->get_name();
    //         $quantity = $item->get_quantity();
    //         $total = $item->get_total();
    //         $product_text_list .= "\t" . $product_name . " x " . $quantity . " = " . number_format($total);
    //     }
    //     $address = $order->get_billing_address_1() . " " . $order->get_billing_address_2();
    //     $city = $order->get_billing_city();
    //     $state = $order->get_billing_state();
    //     $postcode = $order->get_billing_postcode();
    //     $formated_amount = number_format($total_amount);
    //     echo ("کاربر \n\n نام و نام خانوادگی : $display_name\n شماره تماس : $phone\n مبلغ کل سفارش : $formated_amount\n اقلام سفارش : \n $product_text_list \n شماره سفارش: $order_id\n آدرس سفارش: $state $city $address\n کدپستی سفارش: $postcode\n لینک سفارش:\n$order_edit_link");
    //     SendTelegramAlert("خرید از سایت کاربر \n\n نام و نام خانوادگی : $display_name\n شماره تماس : $phone\n مبلغ کل سفارش : $formated_amount\n اقلام سفارش : \n $product_text_list \n شماره سفارش: $order_id\n آدرس سفارش: $state $city $address\n کدپستی سفارش: $postcode\n لینک سفارش:\n$order_edit_link");
    // }
    // } 
    // public function receipt_images()
    // {
    //     global $wpdb;
    //     $image_receipt = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}image_receipt");
    //     include_once(XCPC_DIR . "inc/pages/callback-receipt-images.php");
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
// اکشن تایید فیش واریزی
add_action('wp_ajax_approve_receipt', 'approve_receipt_callback'); 
function approve_receipt_callback() 
{
    global $wpdb;
    $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
    $order_id = isset($_POST['order_id']) ? $_POST['order_id'] : die();
    $order = new WC_Order($order_id);
    $table_name = $wpdb->prefix . 'image_receipt';
    if ($id > 0) {
        // به‌روزرسانی وضعیت تایید: status = 1 و status_receipt = 1
        $result = $wpdb->update(
            $table_name,
            array(
                'status' => 1,
                'status_receipt' => 1
            ),
            array('id' => $id),
            array('%d', '%d'),
            array('%d')
        );
        if ($result !== false) {
            update_order_status($order_id, "completed");
            include_once(XCPC_DIR . "inc/lib/SMS.php");
            $sms = new SMS();
            $display_name = trim($order->get_billing_first_name() . ' ' . $order->get_billing_last_name());
            $sms->doctorReceiptApprove($order->get_billing_phone(), $display_name, $order_id);
            $current_user = wp_get_current_user();
            $admin_display_name = $current_user->display_name;
            $jalali_date = getIranTime();
            $text = "تایید فیش واریز\n\nشماره سفارش : *$order_id*\nنام اپراتور : *$admin_display_name*\n*$jalali_date*";
            SendTelegramAlert($text);
            wp_send_json_success($text);
        }
    }
    wp_send_json_error();
} 
// اکشن لغو فیش واریزی
add_action('wp_ajax_cancel_receipt', 'cancel_receipt_callback'); 
function cancel_receipt_callback()
{
    global $wpdb;
    $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
    $order_id = isset($_POST['order_id']) ? $_POST['order_id'] : die();
    $order = new WC_Order($order_id);
    $reason = isset($_POST['reason']) ? sanitize_text_field($_POST['reason']) : '';
    $table_name = $wpdb->prefix . 'image_receipt';
    if ($id > 0) {
        // به‌روزرسانی وضعیت لغو: status = 1، status_receipt = 0 و ذخیره دلیل لغو
        $result = $wpdb->update(
            $table_name,
            array(
                'status' => 1,
                'status_receipt' => 0,
                'reason_text' => $reason
            ),
            array('id' => $id),
            array('%d', '%d', '%s'),
            array('%d')
        );
        if ($result !== false) {
            update_order_status($order_id, "cancelled");
            include_once(XCPC_DIR . "inc/lib/SMS.php");
            $sms = new SMS();
            $phone_number = $order->get_billing_phone();
            $display_name = trim($order->get_billing_first_name() . ' ' . $order->get_billing_last_name());
            $sms->doctorReceiptDecline($phone_number, $display_name, $order_id);
            wp_send_json_success();
        }
    }
    wp_send_json_error();
} 
function update_order_status($order_id, $new_status)
{
    // Get the order object
    $order = wc_get_order($order_id);
    if (!$order) {
        return false; // Exit if order doesn't exist
    }
    // Update the order status
    $order->update_status($new_status, 'Order status updated programmatically.');
    // Optional: Add a note to the order
    $order->add_order_note('Status changed to: ' . $new_status);
    return true;
}
