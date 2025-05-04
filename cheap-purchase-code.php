<?php 
/**
 * @package xcpc
 */
/*
    Plugin Name: Coupon Referral
    Plugin URI: https://meyti-dev.ir
    Description: افزونه بازاریابی و خرید پزشکان 
    Version: 1.0.0
    Author: mahdi hosseini & Reza Asadi
    Author URI: https://meyti-dev.ir
    Text Domain: xcpc
*/ 
// Ensure the file is not accessed directly
defined('ABSPATH') || exit(); 
// Define constants outside the class
define("XCPC_DIR", trailingslashit(plugin_dir_path(__FILE__)));
define("XCPC_URL", trailingslashit(plugin_dir_url(__FILE__)));
define("TELEGRAM_BOT_TOKEN", "7864975865:AAH6BP1GABtBqumIDVeS3eUtsi086anNHBU");
define("TELEGRAM_CHANEL", "-1002465997483");
define("SECRET_KEY", "XCPC_Meyti_Xshell");
define("ENCRYPT_METHOD", "chacha20"); 
add_filter('woo_wallet_hide_rechargeable_product', '__return_false'); 
class CouponReferral
{
    public function __construct()
    {
        $this->includesFiles();
        $this->includesClasses();
    } 
    private function includesFiles()
    {
        include_once(XCPC_DIR . "vendor/autoload.php");
        include_once(XCPC_DIR . "inc/lib/functions.php");
        include_once(XCPC_DIR . "inc/lib/admin-menu.php");
        include_once(XCPC_DIR . "inc/lib/customer.php");
        include_once(XCPC_DIR . "inc/lib/doctor.php");
        include_once(XCPC_DIR . "inc/lib/payment/index.php");
        include_once(XCPC_DIR . "inc/shortcode/woocommerce_notices.php");
        include_once(XCPC_DIR . "inc/pages/front-doctor-dashboard.php");
    } 
    private function includesClasses()
    {
        add_action('admin_menu', [new CouponReferralMenu(), 'addCouponReferralMenu']);
        new CouponReferralCustomer();
        new CouponReferralDoctor();
        add_action('init', [$this, 'xcpcInitSession']);
        add_action('wp_enqueue_scripts', [$this, 'xcpcFrontAssets']);
    } 
    public function xcpcFrontAssets()
    {
        wp_register_style('xcpc-front-style', XCPC_URL . 'inc/style/xcpc-style.css');
        wp_enqueue_style('xcpc-front-style');
        wp_enqueue_script('xcpc-front-script', XCPC_URL . 'inc/js/xcpc-script.js', ['jquery'], null, true);
        wp_localize_script('xcpc-front-script', 'XcpcAjax', [
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce'   => wp_create_nonce('xcpc__nonce')
        ]);
    } 
    public function xcpcInitSession()
    {
        if (!session_id()) {
            session_start();
        }
    }
} 
// Initialize the plugin
add_action('plugins_loaded', 'init_coupon_referral', 20);
function init_coupon_referral()
{
    if (class_exists('WooCommerce') && class_exists("WooWallet")) {
        new CouponReferral();
    } else {
        if (!class_exists('WooCommerce')) {
            add_action('admin_notices', function () {
                echo '<div class="error"><p>The Coupon Referral plugin requires WooCommerce to be install and active.</p></div>';
            });
        }
        if (!class_exists("WooWallet")) {
            add_action('admin_notices', function () {
                echo '<div class="error"><p>The Coupon Referral plugin requires Woo Wallet to be install and active.</p></div>';
            });
        }
    }
} 
// Register the activation hook outside the class
register_activation_hook(__FILE__, 'xcpcActive');
function xcpcActive()
{
    require_once XCPC_DIR . 'inc/install.php';
} 
add_filter('woocommerce_breadcrumb_defaults', 'prefix_change_breadcrumb_home_text');
/**
 * Rename "home" in WooCommerce breadcrumb
 */
function prefix_change_breadcrumb_home_text($defaults)
{
    $defaults['home'] = 'اکسیرناب';
    return $defaults;
} 
add_action('template_redirect', 'nbs_remove_default_woocommerce_login_form');
function nbs_remove_default_woocommerce_login_form()
{
    // Check if we are on the "My Account" page and the user is not logged in
    if (is_account_page() && !is_user_logged_in()) {
        // Remove WooCommerce login form and its surrounding title.
        remove_action('woocommerce_before_customer_login_form', 'woocommerce_output_all_notices', 10);
        remove_action('woocommerce_login_form', 'woocommerce_login_form', 10);
        remove_action('woocommerce_after_customer_login_form', 'woocommerce_output_all_notices', 10);
        // Remove the default login heading/title.
        remove_action('woocommerce_before_customer_login_form', 'woocommerce_login_header', 10);
    }
} 
add_action('woocommerce_before_customer_login_form', 'nbs_custom_login_form');
function nbs_custom_login_form()
{
    if (!is_user_logged_in()) {
        echo do_shortcode('[xcpc_login]');
    }
} 
add_filter('woocommerce_checkout_fields', 'custom_remove_checkout_fields');
function custom_remove_checkout_fields($fields) {
    unset($fields['billing']['billing_email']); // Remove the email field
    unset($fields['billing']['billing_company']); // Remove the email field
    return $fields;
} 
// Disable WordPress user registration notification emails
add_filter('send_email_change_email', '__return_false');
add_filter('send_password_change_email', '__return_false'); 
// Disable the admin notification email when a new user registers
add_filter('wp_new_user_notification_email_admin', '__return_false'); 
// Optional: Disable the user notification email when they register
add_filter('wp_new_user_notification_email', '__return_false');
