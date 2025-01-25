<?php
// Prevent direct access
if (! defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Doctor Dashboard Shortcode and AJAX Handlers
 */

/**
 * Shortcode to display the doctor dashboard
 */
function xcpc_doctor_dashboard_shortcode()
{
    // Ensure the doctor is logged in
    if (! is_user_logged_in()) {
        return do_shortcode('[xcpc_login]');
    }

    // Enqueue JS and CSS
    wp_enqueue_script(
        'doctor-dashboard-script',
        XCPC_URL . 'inc/js/doctor-dashboard.js',
        array('jquery'),
        '1.0',
        true
    );

    wp_enqueue_style(
        'doctor-dashboard-style',
        XCPC_URL . 'inc/style/doctor-dashboard.css'
    );

    // Localize script to pass AJAX URL and nonce
    wp_localize_script(
        'doctor-dashboard-script',
        'DoctorDashboard',
        array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce'    => wp_create_nonce('update_profile_nonce'),
        )
    );

    ob_start();

    if (!isset($_GET['action'])) {
        // Fetch the user's wallet balance
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
        <div class="doctor-dashboard-container">
            <div class="tabs">
                <button class="tab active" data-tab="order">ثبت سفارش</button>
                <button class="tab" data-tab="wallet">کیف پول/برداشت</button>
                <button class="tab" data-tab="history">تاریخچه سفارشات</button>
                <button class="tab" data-tab="profile">پروفایل</button>
                <button class="tab" onclick="window.location.href = '<?= esc_url(wp_logout_url(get_permalink())); ?>'">خروج</button>
            </div>
            <div class="tab-content">
                <!-- ثبت سفارش (Order Placement) Tab -->
                <div class="tab-pane active" id="order">
                    <?php echo do_shortcode('[doctor_product_list]'); ?>
                </div>

                <!-- تاریخچه سفارشات (Order History) Tab -->
                <div class="tab-pane" id="wallet">
                    <div class="withdrawal_page">
                        <div>
                            <div>
                                <h2>جزئیات کیف پول</h2>
                                <p><strong>موجودی کیف پول:</strong> <?php echo number_format($userBalance); ?> تومان</p>
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
                </div>
                <!-- تاریخچه سفارشات (Order History) Tab -->
                <div class="tab-pane" id="history">
                    <?php echo xcpc_display_order_history(); ?>
                </div>

                <!-- پروفایل (Profile) Tab -->
                <div class="tab-pane" id="profile">
                    <?php echo xcpc_display_profile_form(); ?>
                </div>
            </div>
        </div>
    <?php
    }
    return ob_get_clean();
}
add_shortcode('doctor_dashboard', 'xcpc_doctor_dashboard_shortcode');

/**
 * Display Order History
 */
if (! function_exists('xcpc_display_order_history')) {
    function xcpc_display_order_history()
    {
        // Get current doctor ID
        $doctor_id = get_current_user_id();

        // Get orders for the doctor
        $doctor_orders = wc_get_orders(array(
            'customer' => $doctor_id,
            'limit'    => -1,
            'orderby'  => 'date',
            'order'    => 'DESC',
        ));

        if (empty($doctor_orders)) {
            return '<p class="no-orders">شما هنوز سفارشی ثبت نکرده‌اید.</p>';
        }

        ob_start();
    ?>
        <table class="order-history-table">
            <thead>
                <tr>
                    <th>شماره سفارش</th>
                    <th>تاریخ سفارش</th>
                    <th>وضعیت</th>
                    <th>مجموع</th>
                    <th>جزئیات</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($doctor_orders as $order) : ?>
                    <tr>
                        <td><?php echo esc_html($order->get_order_number()); ?></td>
                        <td><?php echo esc_html(wc_format_datetime($order->get_date_created())); ?></td>
                        <td><?php echo esc_html(wc_get_order_status_name($order->get_status())); ?></td>
                        <td><?php echo wp_kses_post($order->get_formatted_order_total()); ?></td>
                        <td><a href="<?php echo esc_url($order->get_view_order_url()); ?>">مشاهده</a></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php
        return ob_get_clean();
    }
}

/**
 * Display Profile Form
 */
if (! function_exists('xcpc_display_profile_form')) {
    function xcpc_display_profile_form()
    {
        $doctor_id   = get_current_user_id();
        $doctor_info = get_userdata($doctor_id);

        // Get user meta
        $first_name = get_user_meta($doctor_id, 'first_name', true);
        $last_name  = get_user_meta($doctor_id, 'last_name', true);
        $email      = $doctor_info->user_email;
        $phone      = get_user_meta($doctor_id, 'billing_phone', true); // Assuming billing phone

        ob_start();
    ?>
        <form id="doctor-profile-form" class="doctor-profile-form">
            <div class="form-group">
                <label for="first_name">نام:</label>
                <input type="text" id="first_name" name="first_name" value="<?php echo esc_attr($first_name); ?>" required />
            </div>
            <div class="form-group">
                <label for="last_name">نام خانوادگی:</label>
                <input type="text" id="last_name" name="last_name" value="<?php echo esc_attr($last_name); ?>" required />
            </div>
            <div class="form-group">
                <label for="email">ایمیل:</label>
                <input type="email" id="email" name="email" value="<?php echo esc_attr($email); ?>" required />
            </div>
            <div class="form-group">
                <label for="phone">تلفن:</label>
                <input type="text" id="phone" name="phone" value="<?php echo esc_attr($phone); ?>" />
            </div>
            <button type="submit" class="profile-submit-btn">ذخیره تغییرات</button>
        </form>
        <div id="profile-update-message"></div>
<?php
        return ob_get_clean();
    }
}

/**
 * AJAX Handler to Update Doctor Profile
 */
if (! function_exists('xcpc_update_doctor_profile')) {
    function xcpc_update_doctor_profile()
    {
        // Check nonce for security
        check_ajax_referer('update_profile_nonce', 'nonce');

        // Ensure the doctor is logged in
        if (! is_user_logged_in()) {
            wp_send_json_error('شما مجاز به انجام این عملیات نیستید.');
        }

        $doctor_id = get_current_user_id();

        // Sanitize and validate input fields
        $first_name = isset($_POST['first_name']) ? sanitize_text_field($_POST['first_name']) : '';
        $last_name  = isset($_POST['last_name']) ? sanitize_text_field($_POST['last_name']) : '';
        $email      = isset($_POST['email']) ? sanitize_email($_POST['email']) : '';
        $phone      = isset($_POST['phone']) ? sanitize_text_field($_POST['phone']) : '';

        // Validate required fields
        if (empty($first_name) || empty($last_name) || empty($email)) {
            wp_send_json_error('لطفاً تمامی فیلدهای الزامی را پر کنید.');
        }

        // Validate email
        if (! is_email($email)) {
            wp_send_json_error('ایمیل وارد شده معتبر نیست.');
        }

        // Update user data
        wp_update_user(array(
            'ID'         => $doctor_id,
            'first_name' => $first_name,
            'last_name'  => $last_name,
            'user_email' => $email,
        ));

        // Update phone number (assuming it's stored as billing phone)
        update_user_meta($doctor_id, 'billing_phone', $phone);

        wp_send_json_success('اطلاعات پروفایل با موفقیت به‌روزرسانی شد.');
    }
}
add_action('wp_ajax_xcpc_update_doctor_profile', 'xcpc_update_doctor_profile');
