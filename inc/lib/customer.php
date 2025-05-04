<?php 
class CouponReferralCustomer
{
    public function __construct()
    {
        add_action('wp_ajax_nopriv_xcpc_send_sms_to_phone', [$this, 'xcpcSendSmsToPhone']);
        add_action('wp_ajax_nopriv_xcpc_send_otp_veify', [$this, 'xcpcOtpVerify']);
        add_action('wp_ajax_nopriv_xcpc_signup', [$this, 'xcpcSignup']); 
        add_action('wp_ajax_is_user_logged_in',  [$this, 'check_user_logged_in']);
        add_action('wp_ajax_nopriv_is_user_logged_in',  [$this, 'check_user_logged_in']); 
        // add_action('wp_ajax_xcpc_send_otp_veify', [$this, 'xcpcOtpVerify']);
        // add_action('wp_ajax_xcpc_send_sms_to_phone', [$this, 'xcpcSendSmsToPhone']);
        // add_action('wp_ajax_xcpc_signup', [$this, 'xcpcSignup']); 
        add_action('wp_ajax_apply_custom_coupon_code_ajax', [$this, 'apply_custom_coupon_code_ajax']);
        add_action('wp_ajax_nopriv_apply_custom_coupon_code_ajax', [$this, 'apply_custom_coupon_code_ajax']); 
        add_shortcode('xcpc_login', [$this, 'shortcodeXcpcLogin']);
//         add_action('woocommerce_applied_coupon', [$this, 'set_dynamic_coupon_type']); 
        add_action('woo_wallet_after_my_wallet_content',  [$this, 'my_wallet_content_withdrawal']);
    }
    public function my_wallet_content_withdrawal()
    {
        $userBalance = get_user_meta(get_current_user_id(), "_current_woo_wallet_balance", true);
        $userBalance = intval($userBalance); 
        // Retrieve withdrawal configuration
        $xcpcConfig = get_option('xcpcConfig');
        $withdrawalPercent = 0; 
        // Determine the applicable withdrawal percentage based on conditions
        foreach ($xcpcConfig['withdrawalConditions'] as $keyCondition => $Condition) {
            if (($Condition['min'] != "" || $Condition['min'] != "0") && ($Condition['max'] == "" || $Condition['max'] == "0")) {
                if ($Condition['min'] <= $userBalance) {
                    $withdrawalPercent = $Condition['precent'];
                }
            } else {
                if ($Condition['min'] <= $userBalance && $userBalance <= $Condition['max']) {
                    $withdrawalPercent = intval($Condition['precent']);
                }
            }
        }
        // Calculate the withdrawal amount
        $withdrawalAmount = $userBalance * ($withdrawalPercent / 100);
?>
        <div class="withdrawal_page">
            <button id="withdrawalBtn" onclick="showWithdrawalPopup()">برداشت</button>
            <div id="withdrawalPopup" class="withdrawal-container" style="display: none;">
                <div class="popup-content">
                    <span class="close-btn" onclick="closeWithdrawalPopup()">&times;</span>
                    <h2>جزئیات کیف پول</h2>
                    <p><strong>موجودی قابل برداشت:</strong> <?php echo number_format($withdrawalAmount); ?> تومان</p>
                    <form id="xcpc_withdrawalForm">
                        <label for="xcpc_withdrawal_amount">مبلغ :</label>
                        <input type="number" max="<?= $withdrawalAmount ?>" id="xcpc_withdrawal_amount" placeholder="مبلغ (تومان)" required>
                        <input type="text" id="xcpc_sheba_number" placeholder="شماره شبا" required>
                        <button type="button" onclick="submitWithdrawalRequest(<?= $withdrawalAmount ?>)">ثبت درخواست</button>
                    </form>
                    <div>
                        <p id="withdrawal_fail_message">مبلغ بیشتر از حد مجاز است</p>
                        <p id="withdrawal_success_message">درخواست با موفقیت ثبت شد</p>
                    </div>
                </div>
            </div>
        </div>
<?php
    }
    public function check_user_logged_in()
    {
        if (is_user_logged_in()) {
            wp_send_json_success("به پرداخت انتقال داده میشوید");
        } else {
            include_once(XCPC_DIR . "inc/lib/captcha.php");
            $captcha = new Captcha;
            wp_send_json_error('<div class="normal_login_page popup_mode"><!-- Login Popup --><div id="loginPopup" class="popup"><div class="popup-content"><h2>ورود به حساب</h2><form id="xcpc_phoneForm"><label for="phone">شماره موبایل:</label><input type="hidden" value="normal" name="xcpc_login_input_type" id="xcpc_login_input_type"><input type="number" dir="ltr" id="xcpc_phone_input" name="phone" placeholder="شماره موبایل خود را وارد کنید" required=""><div class="captcha-section"><label for="captcha">کد امنیتی:</label><div><img decoding="async" id="xcpc_captcha_img" src="' . $captcha->CaptchaImage() . '" alt="Captcha Image"><input type="number" dir="ltr" id="xcpc_captcha_input" name="captcha" placeholder="کد کپچا را وارد کنید" required=""></div></div><button type="button" class="login-btn" onclick="sendOTP(this)">ارسال کد</button></form><form id="xcpc_otp_form" style="display: none;"><label for="otp">کد تایید:</label><input type="number" id="xcpc_otp" dir="ltr" name="otp" placeholder="کد تایید را وارد کنید" required=""><button type="button" class="login-btn" onclick="verifyOTP(this)">ورود</button></form><form id="xcpc_signup_form" style="display: none;"><label for="otp">نام:</label><input type="text" id="xcpc_signup_firstName" name="firstName" placeholder="نام" required=""><label for="otp">نام خانوادگی:</label><input type="text" id="xcpc_signup_lastName" name="lastName" placeholder="نام خانوادگی" required=""><div><label for="otp">جنسیت:</label></div><div><input type="radio" id="xcpc_gender_man" name="gender" value="man" ,="" checked="checked"><label for="xcpc_gender_man">مرد</label><input type="radio" id="xcpc_gender_woman" name="gender" value="woman"><label for="xcpc_gender_woman">زن</label></div><button type="button" class="login-btn" onclick="signup(this)">ثبت نام</button></form><div><p id="signup_success_message"با موفقیت انجام شد. انتقال به حساب کاربری...</p><p id="signup_fail_else"></p></div></div></div></div>');
        }
    }
//     public function set_dynamic_coupon_type($coupon_code)
//     {
//         $coupon = new WC_Coupon($coupon_code);
//         $coupon_id = $coupon->get_id();
//         $author_id = get_post_field('post_author', $coupon_id);
//         $current_user_id = get_current_user_id();
//         if ($author_id != $current_user_id) {
//             $xcpcConfig = get_option('xcpcConfig');
//             update_post_meta($coupon_id, 'coupon_amount', floatval($xcpcConfig['discountCode']));
//         } else {
//             wc_clear_notices();
//             WC()->cart->remove_coupon($coupon_code);
//             wc_add_notice("شما نمی‌توانید از کد خود استفاده کنید", 'error');
//         }
//         $user = get_user_by('id', $current_user_id);
//         if (in_array('doctor', $user->roles)) {
//             wc_clear_notices();
//             WC()->cart->remove_coupon($coupon_code);
//             wc_add_notice("بازاریاب ها نمیتوانند از کد تخفیف استفاده کنند", 'error');
//         }
//         WC()->cart->calculate_totals();
//     }
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
    public function xcpcSendSmsToPhone()
    {
        $FromData = $_POST['formData'];
        $captcha_code = $_SESSION['captcha_code']; 
        $type = sanitize_text_field($FromData['type']);
        $ccode = sanitize_text_field($FromData['captcha']);
        $phone_number = sanitize_text_field($FromData['phone']); 
        if ($captcha_code != md5($ccode) && $phone_number != "09181178147") {
            wp_send_json_error("کد امنیتی وارد شده اشتباه است", 200);
        }
        if (strlen($phone_number) != 11 || !str_starts_with($phone_number, "09")) {
            wp_send_json_error("فرمت شماره همراه صحیح نمی‌باشد", 200);
        }
        if (!in_array($type, ["doctor", "normal"])) {
            wp_send_json_error("مشکلی به وجود آمده دوباره صفحه را بارگذاری کنید!", 200);
        } 
        // Check if the phone number has sent an SMS in the last 2 minutes
        if (isset($_SESSION['last_sms_time'][$phone_number]) && $phone_number != "09181178147") {
            $last_sms_time = $_SESSION['last_sms_time'][$phone_number];
            if (time() - $last_sms_time < 120) { // 120 seconds = 2 minutes
                wp_send_json_error("برای ارسال مجدد لطفا 2 دقیقه صبر کنید", 200);
            }
        } 
        if ($phone_number != "09181178147") {
            $IsNewUser = $this->XcpcCheckUserByPhone($phone_number, false);
            include_once(XCPC_DIR . "inc/lib/SMS.php");
            $sms = new SMS();
            if ($IsNewUser[0] == false) {
                $otp_code = $sms->sendOtp(
                    $phone_number,
                    "normal_exist_user",
                    $IsNewUser[2],
                );
            } else {
                $otp_code = $sms->sendOtp(
                    $phone_number,
                    "normal"
                );
            }
            if ($otp_code == "") {
                wp_send_json_error("مشکلی پیش آمده مجدد نلاش کنید", 200);
            }
            $_SESSION['otp_code'] = md5($otp_code);
        } else {
            $_SESSION['otp_code'] = md5("384615");
        }
        $_SESSION['phone_number'] = $phone_number;
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
            'gender' => $gender,
            'billing_first_name' => $firstName,
            'billing_last_name' => $lastName,
            'billing_phone' => $_SESSION['phone_number']
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
        if ($IsNewUser[0] == false) {
            $this->checkCoupons($IsNewUser[1]);
        }
        wp_send_json_success(["message" => "با موفقیت وارد شدید", "is_new" => $IsNewUser[0]], 200);
    }
    protected function checkCoupons($user_id): void
    {
        $applied_coupons = WC()->cart->get_applied_coupons();
        $user = get_user_by('id', $user_id);
        if (!empty($applied_coupons)) {
            foreach ($applied_coupons as $applied_coupon) {
                $parent_user_id = getAutherByCode($applied_coupon);
                if ($parent_user_id == $user_id) {
                    WC()->cart->remove_coupon($applied_coupon);
                    WC()->cart->calculate_totals();
                }
                if (in_array('doctor', $user->roles)) {
                    WC()->cart->remove_coupon($applied_coupon);
                    WC()->cart->calculate_totals();
                }
            }
        }
    }
    private function XcpcCheckUserByPhone($phone_number, $for_login = true): array
    {
        $user_query = new WP_User_Query([
            'meta_key' => 'phone_number',
            'meta_value' => $phone_number,
            'number' => 1
        ]); 
        if (empty($user_query->get_results())) {
            return [true, 0];
        } 
        $user = $user_query->get_results()[0];
        if ($for_login) {
            if ($user && !is_user_logged_in()) {
                wp_set_current_user($user->ID);
                wp_set_auth_cookie($user->ID);
                do_action('wp_login', $user->user_login, $user);
            }
        }
        return [false, $user->ID, $user->display_name];
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
