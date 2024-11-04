<?php

class CouponReferralCustomer
{
    public function __construct()
    {
        add_action('wp_ajax_nopriv_xcpc_send_sms_to_phone', [$this, 'xcpcSendSmsToPhone']);
        add_action('wp_ajax_nopriv_xcpc_send_otp_veify', [$this, 'xcpcOtpVerify']);
        add_action('wp_ajax_nopriv_xcpc_signup', [$this, 'xcpcSignup']);

        // add_action('wp_ajax_xcpc_send_otp_veify', [$this, 'xcpcOtpVerify']);
        // add_action('wp_ajax_xcpc_send_sms_to_phone', [$this, 'xcpcSendSmsToPhone']);
        // add_action('wp_ajax_xcpc_signup', [$this, 'xcpcSignup']);


        add_action('wp_ajax_apply_custom_coupon_code_ajax', [$this, 'apply_custom_coupon_code_ajax']);
        add_action('wp_ajax_nopriv_apply_custom_coupon_code_ajax', [$this, 'apply_custom_coupon_code_ajax']);


        add_shortcode('xcpc_login', [$this, 'shortcodeXcpcLogin']);
        add_action('woocommerce_cart_coupon', [$this, 'xcpcInputCheapCode']);
    }

    public function apply_custom_coupon_code_ajax()
    {
        if (isset($_POST['xcpc_cheap_code_field']) && !empty($_POST['xcpc_cheap_code_field'])) {
            $custom_code = sanitize_text_field($_POST['xcpc_cheap_code_field']);

            if ($custom_code == 'SPECIALCODE') {
                wp_send_json_success(__('Your custom code has been applied!', 'woocommerce'));
            } else {
                wp_send_json_error(__('Invalid custom code.', 'woocommerce'));
            }
        } else {
            wp_send_json_error(__('Please enter a custom code.', 'woocommerce'));
        }
        wp_die();
    }

    public function xcpcInputCheapCode()
{
?>
    <div class="woocommerce-cart-custom-coupon">
        <label for="xcpc_cheap_code_field">کد خرید ارزان: </label>
        <input type="text" name="xcpc_cheap_code_field" id="xcpc_cheap_code_field" value=""
            placeholder="کد تخفیف را وارد کنید" />
        <button type="button" class="button" id="update_cart_button" disabled>ثبت کد</button>
    </div>
    <script>
        function setupCouponCodeHandler() {
            jQuery(document).ready(function($) {
                // Enable button on keyup
                $('#xcpc_cheap_code_field').on('keyup', function() {
                    $('#update_cart_button').prop('disabled', !$(this).val().length);
                });

                // AJAX for submitting the custom code without page reload
                $('#update_cart_button').on('click', function() {
                    var xcpc_cheap_code_field = $('#xcpc_cheap_code_field').val();

                    $.ajax({
                        url: '<?php echo admin_url('admin-ajax.php'); ?>',
                        type: 'POST',
                        data: {
                            action: 'apply_custom_coupon_code_ajax',
                            xcpc_cheap_code_field: xcpc_cheap_code_field
                        },
                        success: function(response) {
                            if (response.success) {
                                $('.woocommerce-cart-form').before('<div class="woocommerce-message">' + response.data + '</div>');
                            } else {
                                $('.woocommerce-cart-form').before('<div class="woocommerce-error">' + response.data + '</div>');
                            }
                        }
                    });
                });
            });
        }

        // Initial setup
        setupCouponCodeHandler();

        // Reattach event listeners after cart updates
        jQuery(document.body).on('updated_cart_totals', function() {
            setupCouponCodeHandler();
        });
    </script>
<?php
}

    public function xcpcSendSmsToPhone()
    {
        $FromData = $_POST['formData'];
        $captcha_code = $_SESSION['captcha_code'];

        $type = sanitize_text_field($FromData['type']);
        $ccode = sanitize_text_field($FromData['captcha']);
        $phone_number = sanitize_text_field($FromData['phone']);

        if ($captcha_code != md5($ccode)) {
            wp_send_json_error("کد امنیتی وارد شده اشتباه است", 200);
        }
        if (strlen($phone_number) != 11 || !str_starts_with($phone_number, "09")) {
            wp_send_json_error("فرمت شماره همراه صحیح نمی‌باشد", 200);
        }
        if (!in_array($type, ["doctor", "normal"])) {
            wp_send_json_error("type format is incorrect!", 200);
        }

        // Check if the phone number has sent an SMS in the last 2 minutes
        if (isset($_SESSION['last_sms_time'][$phone_number])) {
            $last_sms_time = $_SESSION['last_sms_time'][$phone_number];
            if (time() - $last_sms_time < 120) { // 120 seconds = 2 minutes
                wp_send_json_error("You can only send one SMS every 2 minutes.", 200);
            }
        }

        include_once(XCPC_DIR . "inc/lib/SMS.php");
        $sms = new SMS();
        $otp_code = $sms->sendOtp(
            $phone_number,
            $type
        );
        if ($otp_code == "") {
            wp_send_json_error("مشکلی پیش آمده مجدد نلاش کنید", 200);
        }
        $_SESSION['phone_number'] = $phone_number;
        $_SESSION['otp_code'] = md5($otp_code);
        $_SESSION['last_sms_time'][$phone_number] = time(); // Update the last SMS time
        wp_send_json_success("کد موقت با موفقیت برای شما ارسال شد", 200);
    }
    public function xcpcSignup()
    {
        $FromData = $_POST['formData'];

        $firstName = sanitize_text_field($FromData['first_name']);
        $lastName = sanitize_text_field($FromData['last_name']);
        $gender = sanitize_text_field($FromData['gender']);

        if ($firstName == "" || $lastName == "" || !in_array($gender, ["woman", "man"])) {
            wp_send_json_error("لطفا تمام فیلد ها را پر کنید", 200);
        }
        if (!isset($_SESSION['phone_number'])) {
            wp_send_json_error("مشکلی پیش آمده است صفحه را بروز کنید", 200);
        }
        $this->XcpcCreateNewUser($_SESSION['phone_number'], [
            'display_name' => $firstName . " " . $lastName,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'gender' => $gender
        ]);
        wp_send_json_success("ثبت نام با موفقیت انجام شد", 200);
    }
    public function xcpcOtpVerify()
    {
        $FromData = $_POST['formData'];
        $otp_code = $_SESSION['otp_code'];

        $otp = sanitize_text_field($FromData['otp']);

        if ($otp_code != md5($otp)) {
            wp_send_json_error("کد موقت وارد شده اشتباه است", 200);
        }
        if (!isset($_SESSION['phone_number'])) {
            wp_send_json_error("مشکلی پیش آمده است صفحه را بروز کنید", 200);
        }
        $IsNewUser = $this->XcpcCheckUserByPhone($_SESSION['phone_number']);
        wp_send_json_success(["message" => "با موفقیت وارد شدید", "is_new" => $IsNewUser], 200);
    }
    private function XcpcCheckUserByPhone($phone_number): bool
    {
        $user_query = new WP_User_Query([
            'meta_key' => 'phone_number',
            'meta_value' => $phone_number,
            'number' => 1
        ]);

        if (empty($user_query->get_results())) {
            return true;
        }

        $user = $user_query->get_results()[0];
        if ($user && !is_user_logged_in()) {
            wp_set_current_user($user->ID);
            wp_set_auth_cookie($user->ID);
            do_action('wp_login', $user->user_login, $user);
        }
        return false;
    }
    private function XcpcCreateNewUser($phone_number, $user_data)
    {
        if (!empty($user_data)) {
            $username = 'user_' . wp_generate_password(6, false);
            $password = wp_generate_password();

            $user_id = wp_create_user($username, $password);

            if (!is_user_logged_in()) {
                wp_set_current_user($user_id);
                wp_set_auth_cookie($user_id);
                do_action('wp_login', $username, $password);
            }
            if (!is_wp_error($user_id)) {
                update_user_meta($user_id, 'phone_number', $phone_number);
                // Update display_name if provided
                if (isset($user_data['display_name'])) {
                    wp_update_user([
                        'ID' => $user_id,
                        'display_name' => $user_data['display_name']
                    ]);
                }

                foreach ($user_data as $key => $value) {
                    update_user_meta($user_id, $key, $value);
                }
            } else {
                return $user_id;
            }
        } else {
            return null;
        }
    }
    public function shortcodeXcpcLogin()
    {
        ob_start();
        include_once(XCPC_DIR . "inc/pages/front-login-popup.php");
        return ob_get_clean();
    }
}
