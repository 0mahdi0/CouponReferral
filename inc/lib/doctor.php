<?php

class CouponReferralDoctor
{
    public function __construct()
    {
        // add_action('wp_ajax_nopriv_xcpc_send_sms_to_phone', [$this, 'xcpcSendSmsToPhone']);
        // add_action('wp_ajax_nopriv_xcpc_send_otp_veify', [$this, 'xcpcOtpVerify']);
        // add_action('wp_ajax_nopriv_xcpc_signup', [$this, 'xcpcSignup']);

        // add_action('wp_ajax_xcpc_send_otp_veify', [$this, 'xcpcOtpVerify']);
        // add_action('wp_ajax_xcpc_send_sms_to_phone', [$this, 'xcpcSendSmsToPhone']);
        // add_action('wp_ajax_xcpc_signup', [$this, 'xcpcSignup']);

        add_action('wp_ajax_update_cart_quantity', [$this, 'update_cart_quantity']);
        add_action('wp_ajax_process_doctor_checkout_payment', [$this, 'process_doctor_checkout_payment']);
        add_action('wp_ajax_process_search_patient_by_phone', [$this, 'process_search_patient_by_phone']);
        add_action('wp_ajax_process_withdrawal_request', [$this, 'process_withdrawal_request']);

        add_shortcode('xcpc_doctor_login', [$this, 'shortcodeXcpcDoctorLogin']);
        add_shortcode('doctor_product_list', [$this, 'shortcodeXcpcDoctorProductList']);
        add_shortcode('doctor_checkout', [$this, 'shortcodeXcpcDoctorCheckout']);
        add_shortcode('payment_verify', [$this, 'shortcodeXcpcDoctorPaymentVerify']);
        add_shortcode('xcpc_wallet_withdraw', [$this, 'shortcodeWalletWithdraw']);
    }

    public function process_withdrawal_request()
    {
        $withdrawal_amount = sanitize_text_field($_POST['withdrawal_amount']);
        $sheba_number = sanitize_text_field($_POST['sheba_number']);
        // Fetch the user's wallet balance
        $user = wp_get_current_user();
        $userBalance = get_user_meta($user->ID, "_current_woo_wallet_balance", true);
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
        if ($withdrawalAmount < $withdrawal_amount) {
            wp_send_json_error("مبلغ بیشتر از حد مجاز است", 200);
        }
        $updatedBalance = intval($userBalance) - intval($withdrawal_amount);
        woo_wallet()->wallet->debit($user->ID, $updatedBalance, "");
        create_wallet_withdraw_post(
            "درخواست برداشت از کیف پول " . $user->user_firstname . " " . $user->user_lastname,
            "",
            $user->ID,
            [
                "full_name" => $user->user_firstname . " " . $user->user_lastname,
                "user_phone" => $user->billing_phone,
                "withdraw_amount" => $withdrawal_amount,
                "withdraw_status" => "pending",
                "transaction_date" => date('Y-m-d\TH:i'),
                "sheba_number" => $sheba_number,
                "status_change_date" => "",
            ]
        );
        wp_send_json_success("درخواست با موفقیت ثبت شد");
    }
    public function process_search_patient_by_phone()
    {
        $patient_phone = sanitize_text_field($_POST['patient_phone']);
        if (strlen($patient_phone) != 11 || !str_starts_with($patient_phone, "09")) {
            wp_send_json_error("فرمت شماره همراه صحیح نمی‌باشد حتما از اعداد انگلیسی استفاده کنید", 200);
        }
        global $wpdb;

        // Prepare the SQL query
        $query = $wpdb->prepare(
            "
            SELECT * 
            FROM {$wpdb->prefix}patients
            WHERE phone = %s
            ",
            $patient_phone
        );

        // Execute the query and fetch results
        $results = $wpdb->get_row($query);
        if ($results) {
            wp_send_json_success(['patient_info' => [
                "name" => $results->fname,
                "lastname" => $results->lname,
                "gender" => $results->gender,
                "disease" => $results->disease,
                "phone" => $results->phone,
                "paddress" => $results->paddress,
                "city" => $results->city,
                "pstate" => $results->pstate,
            ]], 200);
        } else {
            wp_send_json_success("پیدا نشد", 200);
        }
    }
    public function process_doctor_checkout_payment()
    {
        // Verify nonce for security
        // if (!check_ajax_referer('xcpc__nonce', 'xcpc_nonce', false)) {
        //     wp_send_json_error(['message' => 'Security check failed.']);
        //     wp_die();
        // }
        // Capture patient data sent via AJAX

        $patient_name = sanitize_text_field($_POST['patient_name']);
        $patient_lastname = sanitize_text_field($_POST['patient_lastname']);
        $patient_gender = sanitize_text_field($_POST['patient_gender']);
        $patient_disease = sanitize_text_field($_POST['patient_disease']);
        $patient_phone = sanitize_text_field($_POST['patient_phone']);
        $patient_city = sanitize_textarea_field($_POST['patient_city']);
        $patient_state = sanitize_textarea_field($_POST['patient_state']);
        $patient_address = sanitize_textarea_field($_POST['patient_address']);
        if (strlen($patient_phone) != 11 || !str_starts_with($patient_phone, "09")) {
            wp_send_json_error("فرمت شماره همراه صحیح نمی‌باشد حتما از اعداد انگلیسی استفاده کنید", 200);
        }
        // Initialize WooCommerce order object
        $order = wc_create_order();

        // Loop through cart items and add them to the order
        $current_cart = WC()->cart->get_cart();
        if (empty($current_cart)) {
            wp_send_json_error(['message' => 'سبد خرید خالی می‌باشد']);
            die();
        }
        foreach ($current_cart as $cart_item_key => $cart_item) {
            $product = $cart_item['data'];
            $quantity = $cart_item['quantity'];

            $order->add_product($product, $quantity); // Add each product to order
        }

        // Get current user data
        $current_user = wp_get_current_user();
        $billing_data = [
            'first_name' => $current_user->first_name,
            'last_name'  => $current_user->last_name,
            'email'      => $current_user->user_email,
            'phone'      => get_user_meta($current_user->ID, 'billing_phone', true),
            'address_1'  => get_user_meta($current_user->ID, 'billing_address_1', true),
            'city'       => get_user_meta($current_user->ID, 'billing_city', true),
            'state'      => get_user_meta($current_user->ID, 'billing_state', true),
            'postcode'   => get_user_meta($current_user->ID, 'billing_postcode', true),
            'country'    => get_user_meta($current_user->ID, 'billing_country', true),
        ];

        // Set billing data for the order
        $order->set_address($billing_data, 'billing');

        // Set shipping data with patient information
        $shipping_data = [
            'first_name' => $patient_name,
            'last_name'  => $patient_lastname,
            'phone'      => $patient_phone,
            'address_1'  => $patient_address,
            'city'       => $patient_city,
            'state'      => $patient_state,
            'postcode'   => '',
            'country'    => '',
        ];

        $order->set_address($shipping_data, 'shipping');

        // Add order meta for additional patient details
        $order->update_meta_data('_patient_gender', $patient_gender);
        $order->update_meta_data('_patient_disease', $patient_disease);

        // Calculate totals and update order status
        $order->calculate_totals();
        $order->update_status('pending'); // or 'completed' if no further action is needed

        global $wpdb;

        // Check for duplicate entry based on phone number
        $table_name = $wpdb->prefix . "patients";
        $existing_patient = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name WHERE phone = %s",
            $patient_phone
        ));

        if ($existing_patient == 0) {
            // Insert patient data into the custom table if not duplicate
            $wpdb->insert(
                $table_name,
                [
                    'user_id' => $current_user->ID,
                    'fname' => $patient_name,
                    'lname' => $patient_lastname,
                    'gender' => $patient_gender,
                    'phone' => $patient_phone,
                    'disease' => $patient_disease,
                    'paddress' => $patient_address,
                    'city' => $patient_city,
                    'pstate' => $patient_state,
                    'date' => current_time('mysql'),
                ],
                [
                    '%d',
                    '%s',
                    '%s',
                    '%s',
                    '%s',
                    '%s',
                    '%s',
                    '%s',
                    '%s',
                    '%s'
                ]
            );
        }

        $order_id = $order->get_id();
        // Send response back to the frontend
        if (!$order_id) {
            wp_send_json_error(['message' => 'Failed to create order. Please try again.']);
            die();
        }

        include_once(XCPC_DIR . "inc/lib/payment/newGate.php");
        $Payment_Melli = new Payment_Melli;
        $CreatePayLink = $Payment_Melli->CreatePayLink($order_id);
        if ($CreatePayLink['status']) {
            wp_send_json_success(['message' => $CreatePayLink['message']], 200);
        } else {
            wp_send_json_error(['message' => 'Failed to create order. Please try again.']);
        }
    }
    public function update_cart_quantity()
    {
        // Verify nonce for security (optional but recommended)
        // if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'update_cart_quantity_nonce' ) ) {
        //     wp_send_json_error( 'Invalid security token.' );
        // }

        if (!isset($_POST['product_id'], $_POST['quantity'])) {
            wp_send_json_error('Invalid data.');
        }

        $product_id = intval($_POST['product_id']);
        $quantity = intval($_POST['quantity']);

        if ($product_id <= 0) {
            wp_send_json_error('Invalid product ID.');
        }

        $product = wc_get_product($product_id);
        if (! $product) {
            wp_send_json_error('Product not found.');
        }

        $cart_item_key = WC()->cart->find_product_in_cart(WC()->cart->generate_cart_id($product_id));

        if ($quantity > 0) {
            if ($cart_item_key) {
                // Update quantity
                WC()->cart->set_quantity($cart_item_key, $quantity, true);
            } else {
                // Add product to cart
                WC()->cart->add_to_cart($product_id, $quantity);
            }
        } else {
            // Quantity is zero, remove product from cart
            if ($cart_item_key) {
                WC()->cart->remove_cart_item($cart_item_key);
            }
        }

        // Recalculate totals
        WC()->cart->calculate_totals();

        wp_send_json_success();
    }

    public function xcpcSendSmsToPhone()
    {
        $FromData = $_POST['formData'];
        $captcha_code = $_SESSION['captcha_code'];

        $ccode = sanitize_text_field($FromData['captcha']);
        $phone_number = sanitize_text_field($FromData['phone']);

        if ($captcha_code != md5($ccode)) {
            wp_send_json_error("کد امنیتی وارد شده اشتباه است", 200);
        }
        if (strlen($phone_number) != 11 || !str_starts_with($phone_number, "09")) {
            wp_send_json_error("فرمت شماره همراه صحیح نمی‌باشد", 200);
        }

        include_once(XCPC_DIR . "inc/lib/SMS.php");
        $sms = new SMS();
        $otp_code = $sms->sendOtp(
            $phone_number,
            "doctor"
        );
        if ($otp_code == "") {
            wp_send_json_error("مشکلی پیش آمده مجدد نلاش کنید", 200);
        }
        $_SESSION['phone_number'] = $phone_number;
        $_SESSION['otp_code'] = md5($otp_code);
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
    public function shortcodeXcpcDoctorLogin()
    {
        ob_start();
        include_once(XCPC_DIR . "inc/pages/front-doctor-login-popup.php");
        return ob_get_clean();
    }
    public function shortcodeXcpcDoctorProductList()
    {
        include_once(XCPC_DIR . "inc/pages/front-doctor-product-list.php");
        return doctor_product_list_shortcode();
    }
    public function shortcodeXcpcDoctorCheckout()
    {
        ob_start();
        if (!isset($_GET['action'])) {
            include_once(XCPC_DIR . "inc/pages/front-doctor-checkout.php");
        }
        return ob_get_clean();
    }
    public function shortcodeXcpcDoctorPaymentVerify()
    {
        if (!isset($_GET['action'])) {
            include_once(XCPC_DIR . "inc/pages/front-doctor-payment-verify.php");
        }
        return ob_get_clean();
    }
    public function shortcodeWalletWithdraw()
    {
        if (!isset($_GET['action'])) {
            include_once(XCPC_DIR . "inc/pages/front-wallet-withdraw-popup.php");
        }
        return ob_get_clean();
    }
}
