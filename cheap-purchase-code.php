<?php

/**
 * @package xcpc
 */
/*
    Plugin Name: Coupon Referral
    Plugin URI: https://meyti-dev.ir
    Description: .
    Version: 1.0.0
    Author: mahdi hosseini
    Author URI: https://meyti-dev.ir
    Text Domain: xcpc
*/

class CouponReferral
{

    public function __construct()
    {
        defined('ABSPATH') || exit();
        $this->define();
    }
    public function define()
    {
        define("XCPC_DIR", trailingslashit(plugin_dir_path(__FILE__)));
        define("XCPC_URL", trailingslashit(plugin_dir_url(__FILE__)));
        define("SECRET_KEY", "XCPC_Meyti_Xshell");
        define("ENCRYPT_METHOD", "chacha20");
        $this->includesFiles();
        $this->includesClasses();
        $this->includesFunctions();
    }

    private function includesFiles()
    {
        include_once(XCPC_DIR . "vendor/autoload.php");
        include_once(XCPC_DIR . "inc/lib/functions.php");
        include_once(XCPC_DIR . "inc/lib/admin-menu.php");
        include_once(XCPC_DIR . "inc/lib/customer.php");
        include_once(XCPC_DIR . "inc/lib/doctor.php");
        include_once(XCPC_DIR . "inc/lib/payment/index.php");
    }
    private function includesClasses()
    {
        add_action('admin_menu', [new CouponReferralMenu, 'addCouponReferralMenu']);
        new CouponReferralCustomer;
        new CouponReferralDoctor;
        add_action('init', [$this, 'xcpcInitSession']);
        add_action('wp_enqueue_scripts', [$this, 'xcpcFrontAssets']);

    }
    private function includesFunctions()
    {
        // register_activation_hook(__FILE__, [$this, 'xcpc_active']);
        // register_deactivation_hook(__FILE__, [$this, 'xcpc_deactive']);
    }

    public function xcpcFrontAssets()
    {
        wp_register_style('xcpc-front-style', XCPC_URL . 'inc/style/xcpc-style.css');
        wp_enqueue_style('xcpc-front-style');
        wp_enqueue_script('xcpc-front-script', XCPC_URL . 'inc/js/xcpc-script.js', array('jquery'), null, true);
        wp_localize_script('xcpc-front-script', 'XcpcAjax', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('xcpc__nonce')
        ));
    }
    public function xcpcInitSession()
    {
        if (!session_id()) {
            session_start();
        }
    }
    // public function xcpc_active(){
    //     include_once("installer.php");
    // }

    // public function xcpc_deactive(){}
}

add_action('plugins_loaded', 'init_coupon_referral', 20);
function init_coupon_referral() {
    if (class_exists('WooCommerce')) {
        new CouponReferral();
    } else {
        add_action('admin_notices', function() {
            echo '<div class="error"><p>پلاگین Coupon Referral نیاز به فعال بودن ووکامرس دارد.</p></div>';
        });
    }
}
