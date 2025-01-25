<?php
// function generateCustomAlgorithm($input)
// {
//     // Ensure the input is an 11-digit number
//     if (preg_match('/^\d{11}$/', $input)) {
//         // Get the last 6 digits
//         $lastSixDigits = substr($input, -6);

//         // Convert the digits to an array for shuffling
//         $digitsArray = str_split($lastSixDigits);

//         // Shuffle the array
//         shuffle($digitsArray);

//         // Convert the shuffled array back to a string
//         $shuffledDigits = "exir" . implode('', $digitsArray);

//         // Concatenate "exir" with the shuffled digits
//         return $shuffledDigits;
//     } else {
//         return "Invalid input. Please provide an 11-digit number.";
//     }
// }

function generateCouponCode($length = 8) {

    // Generate 6 random numbers
    $numbers = str_split(str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT));

    // Generate 2 random uppercase letters
    $letters = [
        chr(rand(65, 90)), // First random letter
        chr(rand(65, 90))  // Second random letter
    ];

    // Randomly place letters in the code
    $code = [];
    $letterIndexes = array_rand(range(0, $length - 1), 2);

    for ($i = 0; $i < $length; $i++) {
        if (in_array($i, $letterIndexes)) {
            $code[] = array_shift($letters);
        } else {
            $code[] = array_shift($numbers);
        }
    }

    return strtolower(implode('', $code));
}

function cheapCodeDuplicateCheck($user_id)
{
    global $wpdb;

    // Prepare the SQL query
    $query = $wpdb->prepare(
        "
        SELECT post_title 
        FROM {$wpdb->posts}
        WHERE post_type = 'shop_coupon'
        AND post_author = %d
        AND post_status = 'publish'
        ",
        $user_id
    );

    // Execute the query and fetch results
    $results = $wpdb->get_col($query);

    return $results ? $results : array();
}
function getAutherByCode($code)
{
    global $wpdb;

    // Prepare the SQL query
    $query = $wpdb->prepare(
        "
        SELECT post_author 
        FROM {$wpdb->posts}
        WHERE post_type = 'shop_coupon'
        AND post_title = %s
        AND post_status = 'publish'
        ",
        $code
    );

    // Execute the query and fetch results
    $results = $wpdb->get_col($query);

    return $results ? $results[0] : array();
}

function addOrUpdateSubsetUsers($parent_user, $sub_user): void
{
    $subsetUsers = get_user_meta($parent_user, "subsetUsers", true);
    if (!empty($subsetUsers)) {
        if (!in_array($sub_user, $subsetUsers)) {
            $subsetUsers[] = $sub_user;
            update_user_meta($parent_user, "subsetUsers", $subsetUsers);
        }
    } else {
        add_user_meta($parent_user, "subsetUsers", [$sub_user]);
    }
}

function UserCashbackBalance($user_id, $price)
{
    $userBalance = get_user_meta($user_id, "_current_woo_wallet_balance", true);
    $userBalance = intval($userBalance);
    $xcpcConfig = get_option('xcpcConfig');
    $newprice = ($price * (intval($xcpcConfig['walletReturn']) / 100));
    $updatedBalance = $userBalance + $newprice;
    woo_wallet()->wallet->credit($user_id, $updatedBalance, "");
    return $newprice;
}

function SubmitCheapCode($order)
{
    $customer_id = $order->get_customer_id();

    if ($customer_id) {
        $user = get_user_by('id', $customer_id);
        $user_roles = $user->roles;
        if (in_array('customer', $user_roles)) {
            $dataCoupon = cheapCodeDuplicateCheck($customer_id);
            if ($dataCoupon == array()) {
                $userCheapCode = generateCouponCode();
                $xcpcConfig = get_option('xcpcConfig');
                $wcCheapCoupon = wcCheapCoupon($userCheapCode, $customer_id, "percent", $xcpcConfig['discountCode'], 'userCheapCode');
                if ($wcCheapCoupon != 0) {
                    return $userCheapCode;
                }
            } else {
                return $dataCoupon[0];
            }
        } else {
            return "";
        }
    } else {
        return "";
    }
}
function wcCheapCoupon($coupon_code, $customer_id, $discount_type = 'percent', $amount = '0', $description = ''): int|string
{

    // بررسی اینکه آیا کد تخفیف از قبل وجود دارد
    if (wc_get_coupon_id_by_code($coupon_code)) {
        return 0;
    }

    // ایجاد پست جدید برای کد تخفیف
    $coupon = array(
        'post_title'   => $coupon_code,
        'post_content' => '',
        'post_status'  => 'publish',
        'post_author'  => $customer_id,
        'post_type'    => 'shop_coupon'
    );

    $new_coupon_id = wp_insert_post($coupon);

    if (is_wp_error($new_coupon_id)) {
        return 0;
    }

    // تنظیم متا دیتا برای کد تخفیف
    update_post_meta($new_coupon_id, 'discount_type', sanitize_text_field($discount_type));
    update_post_meta($new_coupon_id, 'coupon_amount', floatval($amount));
    update_post_meta($new_coupon_id, 'individual_use', 'no'); // استفاده انفرادی
    update_post_meta($new_coupon_id, 'usage_limit', ''); // بدون محدودیت استفاده کلی
    update_post_meta($new_coupon_id, 'usage_limit_per_user', ''); // هر کاربر فقط یکبار می‌تواند استفاده کند
    update_post_meta($new_coupon_id, 'limit_usage_to_x_items', '');
    update_post_meta($new_coupon_id, 'expiry_date', ''); // بدون تاریخ انقضا
    update_post_meta($new_coupon_id, 'free_shipping', 'no');

    // اضافه کردن ایمیل کاربر به لیست ایمیل‌های مستثنی (تا نتواند از کد استفاده کند)
    // update_post_meta($new_coupon_id, 'exclude_id_addresses', $customer_id);

    // اضافه کردن توضیحات (اختیاری)
    if (! empty($description)) {
        update_post_meta($new_coupon_id, 'description', sanitize_text_field($description));
    }

    return $new_coupon_id;
}
// Exit if accessed directly
if (! defined('ABSPATH')) {
    exit;
}

function register_wallet_withdraw_post_type()
{

    $labels = array(
        'name'                  => _x('Wallet Withdraws', 'Post Type General Name', 'text_domain'),
        'singular_name'         => _x('Wallet Withdraw', 'Post Type Singular Name', 'text_domain'),
        'menu_name'             => __('Wallet Withdraws', 'text_domain'),
        'name_admin_bar'        => __('Wallet Withdraw', 'text_domain'),
        'archives'              => __('Wallet Withdraw Archives', 'text_domain'),
        'attributes'            => __('Wallet Withdraw Attributes', 'text_domain'),
        'parent_item_colon'     => __('Parent Wallet Withdraw:', 'text_domain'),
        'all_items'             => __('All Wallet Withdraws', 'text_domain'),
        'add_new_item'          => __('Add New Wallet Withdraw', 'text_domain'),
        'add_new'               => __('Add New', 'text_domain'),
        'new_item'              => __('New Wallet Withdraw', 'text_domain'),
        'edit_item'             => __('Edit Wallet Withdraw', 'text_domain'),
        'update_item'           => __('Update Wallet Withdraw', 'text_domain'),
        'view_item'             => __('View Wallet Withdraw', 'text_domain'),
        'view_items'            => __('View Wallet Withdraws', 'text_domain'),
        'search_items'          => __('Search Wallet Withdraw', 'text_domain'),
        'not_found'             => __('Not found', 'text_domain'),
        'not_found_in_trash'    => __('Not found in Trash', 'text_domain'),
        'featured_image'        => __('Featured Image', 'text_domain'),
        'set_featured_image'    => __('Set featured image', 'text_domain'),
        'remove_featured_image' => __('Remove featured image', 'text_domain'),
        'use_featured_image'    => __('Use as featured image', 'text_domain'),
        'insert_into_item'      => __('Insert into wallet withdraw', 'text_domain'),
        'uploaded_to_this_item' => __('Uploaded to this wallet withdraw', 'text_domain'),
        'items_list'            => __('Wallet withdraws list', 'text_domain'),
        'items_list_navigation' => __('Wallet withdraws list navigation', 'text_domain'),
        'filter_items_list'     => __('Filter wallet withdraws list', 'text_domain'),
    );
    $args = array(
        'label'                 => __('Wallet Withdraw', 'text_domain'),
        'description'           => __('Post Type for Wallet Withdrawals', 'text_domain'),
        'labels'                => $labels,
        'supports'              => array('title', 'editor', 'author', 'custom-fields', 'thumbnail', 'excerpt', 'comments'),
        'taxonomies'            => array(), // Removed 'category' and 'post_tag'
        'hierarchical'          => false,
        'public'                => true,
        'show_ui'               => true,
        'show_in_menu'          => true,
        'menu_position'         => 25, // Position in the admin menu
        'menu_icon'             => 'dashicons-money', // Dashicon for the menu
        'show_in_admin_bar'     => true,
        'show_in_nav_menus'     => true, // Enables the post type in navigation menus
        'can_export'            => true,
        'has_archive'           => true,
        'exclude_from_search'   => false,
        'publicly_queryable'    => true,
        'capability_type'       => 'post', // You can set to 'wallet_withdraw' and define custom capabilities
        'show_in_rest'          => false, // Disable Gutenberg editor
    );
    register_post_type('wallet_withdraw', $args);
}
add_action('init', 'register_wallet_withdraw_post_type', 0);

/**
 * 2. Function to Create a New Wallet Withdraw Post
 *
 * @param string $title        The title of the withdrawal request.
 * @param string $content      The content or description.
 * @param int    $author_id    The user ID who is making the withdrawal.
 * @param array  $meta_fields  An associative array of meta fields.
 *
 * @return int|WP_Error The ID of the created post or a WP_Error object on failure.
 */
function create_wallet_withdraw_post($title, $content, $author_id, $meta_fields = array())
{

    // Prepare the post data
    $post_data = array(
        'post_title'    => wp_strip_all_tags($title),
        'post_content'  => $content,
        'post_status'   => 'pending', // You can set to 'publish', 'draft', etc.
        'post_author'   => $author_id,
        'post_type'     => 'wallet_withdraw',
    );

    // Insert the post into the database
    $post_id = wp_insert_post($post_data);

    // Check for errors
    if (is_wp_error($post_id)) {
        return $post_id;
    }

    // If there are meta fields, add them to the post
    if (! empty($meta_fields) && is_array($meta_fields)) {
        foreach ($meta_fields as $meta_key => $meta_value) {
            update_post_meta($post_id, $meta_key, $meta_value);
        }
    }

    return $post_id;
}

/**
 * 3. Automatically Add Wallet Withdraw Archive to Main Menu
 *
 * Note: The "اطلاعات برداشت" menu page has been removed as per user request.
 */
function add_wallet_withdraw_to_main_menu()
{
    // Define the menu location slug
    $menu_location = 'primary'; // Change this if your theme uses a different location slug

    // Get the menu assigned to the location
    $locations = get_nav_menu_locations();
    if (! isset($locations[$menu_location])) {
        // If the primary menu doesn't exist, you might want to create it or exit
        return;
    }

    $menu_id = $locations[$menu_location];

    // Get all items in the menu
    $menu_items = wp_get_nav_menu_items($menu_id);

    // Define the archive link URL
    $archive_link = get_post_type_archive_link('wallet_withdraw');

    // Check if the menu already has a link to the wallet_withdraw archive
    $menu_item_exists = false;
    foreach ($menu_items as $item) {
        if ($item->url === $archive_link) {
            $menu_item_exists = true;
            break;
        }
    }

    // If the menu item doesn't exist, add it
    if (! $menu_item_exists) {
        // Prepare the menu item data
        $menu_item_data = array(
            'menu-item-object'   => 'custom',
            'menu-item-type'     => 'custom',
            'menu-item-title'    => 'Wallet Withdraw', // Change this to your desired menu label
            'menu-item-url'      => $archive_link,
            'menu-item-status'   => 'publish',
            'menu-item-parent-id' => 0,
        );

        // Add the menu item
        wp_update_nav_menu_item($menu_id, 0, $menu_item_data);
    }
}
add_action('after_setup_theme', 'add_wallet_withdraw_to_main_menu');

/**
 * 4. Customize Admin Columns for Wallet Withdraw
 *
 * Removed the 'اطلاعات' (Info) column as per user request.
 */
function ww_custom_columns($columns)
{
    // Remove unwanted columns if any
    unset($columns['date']);

    // Define new columns
    $columns = array(
        'cb'                   => '<input type="checkbox" />',
        'full_name'            => 'نام و نام خانوادگی',
        'user_phone'           => 'شماره همراه کاربر',
        'withdraw_amount'      => 'مبلغ برداشت',
        'withdraw_status'      => 'وضعیت برداشت',
        'sheba_number'         => 'شماره شبا', // New Column
        'status_change_date'   => 'تاریخ تغییر وضعیت', // New Column
        'date'                 => 'تاریخ',
    );

    return $columns;
}
add_filter('manage_wallet_withdraw_posts_columns', 'ww_custom_columns');

/**
 * Populate the custom columns with data.
 *
 * @param string $column  Column name.
 * @param int    $post_id Post ID.
 */
function ww_custom_columns_content($column, $post_id)
{
    switch ($column) {
        case 'request_date':
            $request_date = get_post_meta($post_id, 'transaction_date', true);
            if ($request_date) {
                echo date_i18n('Y/m/d H:i', strtotime($request_date));
            } else {
                echo '—';
            }
            break;

        case 'full_name':
            $full_name = get_post_meta($post_id, 'full_name', true);
            echo $full_name ? esc_html($full_name) : '—';
            break;

        case 'user_phone':
            $user_phone = get_post_meta($post_id, 'user_phone', true);
            echo $user_phone ? esc_html($user_phone) : '—';
            break;

        case 'withdraw_amount':
            $amount = get_post_meta($post_id, 'withdraw_amount', true);
            if ($amount) {
                echo number_format_i18n($amount) . ' تومان'; // Assuming the currency is Toman
            } else {
                echo '—';
            }
            break;

        case 'withdraw_status':
            $status = get_post_meta($post_id, 'withdraw_status', true);
            $status_labels = array(
                'rejected'  => 'رد شده',
                'accepted'  => 'قبول شده',
                'pending'   => 'در انتظار',
            );
            echo isset($status_labels[$status]) ? $status_labels[$status] : '—';
            break;

        case 'sheba_number':
            $sheba_number = get_post_meta($post_id, 'sheba_number', true);
            echo $sheba_number ? esc_html($sheba_number) : '—';
            break;

        case 'status_change_date':
            $status_change_date = get_post_meta($post_id, 'status_change_date', true);
            if ($status_change_date) {
                echo date_i18n('Y/m/d H:i', strtotime($status_change_date));
            } else {
                echo '—';
            }
            break;

            // Removed 'info' column content
    }
}
add_action('manage_wallet_withdraw_posts_custom_column', 'ww_custom_columns_content', 10, 2);

/**
 * 5. Remove "Add New" Button from Wallet Withdraws Admin Page
 */
function ww_remove_add_new_button()
{
    global $pagenow;

    // Check if on the wallet_withdraw post type listing page
    if ($pagenow === 'edit.php' && isset($_GET['post_type']) && $_GET['post_type'] === 'wallet_withdraw') {
        // Remove the "Add New" button using CSS
        echo '<style type="text/css">
            .page-title-action { display: none; }
        </style>';
    }
}
add_action('admin_head', 'ww_remove_add_new_button');

/**
 * 6. Remove "اطلاعات برداشت" Admin Menu Page
 *
 * Since we have removed the "اطلاعات برداشت" menu page, ensure that no code adds it.
 * If previously added, ensure it's removed.
 *
 * In our updated plugin, we no longer add it, so no action is required here.
 */

/**
 * 7. Add Custom Row Action for Detailed Information
 *
 * Instead of an "اطلاعات" column, we'll add a custom row action link "اطلاعات" to each withdrawal request.
 * This link will direct to the standard "Edit" screen where detailed information can be viewed and edited.
 */

/**
 * 8. Remove "Categories" and "Tags" Meta Boxes from Edit Screen
 */
function ww_remove_taxonomy_meta_boxes()
{
    remove_meta_box('categorydiv', 'wallet_withdraw', 'side'); // Categories
    remove_meta_box('tagsdiv-post_tag', 'wallet_withdraw', 'side'); // Tags
}
add_action('add_meta_boxes', 'ww_remove_taxonomy_meta_boxes', 99);

/**
 * 9. Remove "اطلاعات برداشت" Admin Menu Page if Exists
 *
 * If by any chance the "اطلاعات برداشت" admin page exists, remove it.
 */
function ww_remove_info_admin_page()
{
    remove_menu_page('wallet_withdraw_info'); // Remove the menu page by slug
}
add_action('admin_menu', 'ww_remove_info_admin_page', 999);

/**
 * 10. Clean Up: Ensure No Residual Submenu Items
 *
 * In case any residual submenu items exist, remove them.
 */
function ww_clean_up_submenus()
{
    // Remove "Add New" submenu if it still exists
    remove_submenu_page('edit.php?post_type=wallet_withdraw', 'post-new.php?post_type=wallet_withdraw');
}
add_action('admin_menu', 'ww_clean_up_submenus', 999);

/**
 * 11. Add Meta Boxes for Withdrawal Details
 *
 * Adds a dedicated meta box to the Wallet Withdraw post editing screen.
 */
function ww_register_meta_boxes()
{
    add_meta_box(
        'ww_withdraw_details',          // ID
        'جزئیات برداشت',               // Title
        'ww_withdraw_details_callback', // Callback
        'wallet_withdraw',              // Post type
        'normal',                       // Context
        'high'                          // Priority
    );
}
add_action('add_meta_boxes', 'ww_register_meta_boxes');

/**
 * Callback function to display meta box content.
 *
 * @param WP_Post $post The post object.
 */
function ww_withdraw_details_callback($post)
{
    // Add a nonce field for security
    wp_nonce_field('ww_save_withdraw_details', 'ww_withdraw_details_nonce');

    // Retrieve existing meta data
    $full_name          = get_post_meta($post->ID, 'full_name', true);
    $user_phone         = get_post_meta($post->ID, 'user_phone', true);
    $withdraw_amount    = get_post_meta($post->ID, 'withdraw_amount', true);
    $withdraw_status    = get_post_meta($post->ID, 'withdraw_status', true);
    $transaction_date   = get_post_meta($post->ID, 'transaction_date', true);
    $sheba_number       = get_post_meta($post->ID, 'sheba_number', true);
    $status_change_date = get_post_meta($post->ID, 'status_change_date', true);

    // Determine if the status dropdown should be disabled
    $withdraw_status = ($withdraw_status == '') ? 'pending' : $withdraw_status;
    $disable_status = ('pending' !== $withdraw_status) ? true : false;

?>
    <table class="form-table">
        <tr>
            <th><label for="ww_full_name">نام و نام خانوادگی</label></th>
            <td>
                <input type="text" id="ww_full_name" name="ww_full_name" value="<?php echo esc_attr($full_name); ?>" class="regular-text" />
            </td>
        </tr>
        <tr>
            <th><label for="ww_user_phone">شماره همراه کاربر</label></th>
            <td>
                <input type="text" id="ww_user_phone" name="ww_user_phone" value="<?php echo esc_attr($user_phone); ?>" class="regular-text" />
            </td>
        </tr>
        <tr>
            <th><label for="ww_withdraw_amount">مبلغ برداشت (تومان)</label></th>
            <td>
                <input type="number" id="ww_withdraw_amount" name="ww_withdraw_amount" value="<?php echo esc_attr($withdraw_amount); ?>" class="regular-text" />
            </td>
        </tr>
        <tr>
            <th><label for="ww_withdraw_status">وضعیت برداشت</label></th>
            <td>
                <select id="ww_withdraw_status" name="ww_withdraw_status" <?php echo $disable_status ? 'disabled' : ''; ?>>
                    <option value="pending" <?php selected($withdraw_status, 'pending'); ?>>در انتظار</option>
                    <option value="accepted" <?php selected($withdraw_status, 'accepted'); ?>>قبول شده</option>
                    <option value="rejected" <?php selected($withdraw_status, 'rejected'); ?>>رد شده</option>
                </select>
                <?php if ($disable_status) : ?>
                    <input type="hidden" name="ww_withdraw_status" value="<?php echo esc_attr($withdraw_status); ?>" />
                <?php endif; ?>
            </td>
        </tr>
        <tr>
            <th><label for="ww_transaction_date">تاریخ درخواست</label></th>
            <td>
                <input type="text" id="ww_transaction_date" name="ww_transaction_date" value="<?php echo esc_attr(date('Y-m-d\TH:i', strtotime($transaction_date))); ?>" />
            </td>
        </tr>
        <tr>
            <th><label for="ww_sheba_number">شماره شبا</label></th>
            <td>
                <input type="text" id="ww_sheba_number" name="ww_sheba_number" value="<?php echo esc_attr($sheba_number); ?>" class="regular-text" />
            </td>
        </tr>
        <tr>
            <th><label for="ww_status_change_date">تاریخ تغییر وضعیت</label></th>
            <td>
                <input type="text" id="ww_status_change_date" name="ww_status_change_date" value="<?php echo esc_attr($status_change_date); ?>" class="regular-text" readonly />
            </td>
        </tr>
    </table>
<?php
}

/**
 * Save Meta Box Data
 *
 * @param int $post_id The ID of the post being saved.
 */
function ww_save_withdraw_details($post_id)
{
    // Check if our nonce is set.
    if (! isset($_POST['ww_withdraw_details_nonce'])) {
        return;
    }

    // Verify that the nonce is valid.
    if (! wp_verify_nonce($_POST['ww_withdraw_details_nonce'], 'ww_save_withdraw_details')) {
        return;
    }

    // If this is an autosave, our form has not been submitted, so we don't want to do anything.
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    // Check the user's permissions.
    if (isset($_POST['post_type']) && 'wallet_withdraw' === $_POST['post_type']) {
        if (! current_user_can('edit_post', $post_id)) {
            return;
        }
    } else {
        return;
    }

    /* OK, it's safe to save the data now. */

    // Retrieve the old status to detect changes
    $old_status = get_post_meta($post_id, 'withdraw_status', true);

    // Initialize a flag to detect unauthorized status change attempts
    $unauthorized_status_change = false;

    // Sanitize and save each field.

    if (isset($_POST['ww_full_name'])) {
        $full_name = sanitize_text_field($_POST['ww_full_name']);
        update_post_meta($post_id, 'full_name', $full_name);
    }

    if (isset($_POST['ww_user_phone'])) {
        $user_phone = sanitize_text_field($_POST['ww_user_phone']);
        update_post_meta($post_id, 'user_phone', $user_phone);
    }

    if (isset($_POST['ww_withdraw_amount'])) {
        $withdraw_amount = floatval($_POST['ww_withdraw_amount']);
        update_post_meta($post_id, 'withdraw_amount', $withdraw_amount);
    }

    if (isset($_POST['ww_withdraw_status'])) {
        $withdraw_status = sanitize_text_field($_POST['ww_withdraw_status']);
        $allowed_statuses = array('pending', 'accepted', 'rejected');

        // Only allow status change if the current status is 'pending'
        if (in_array($withdraw_status, $allowed_statuses, true)) {
            update_post_meta($post_id, 'withdraw_status', $withdraw_status);

            // If the status has changed, update the status_change_date
            if ($withdraw_status !== $old_status) {
                $current_datetime = current_time('mysql');
                update_post_meta($post_id, 'status_change_date', $current_datetime);
            }
        }
    }

    if (isset($_POST['ww_transaction_date'])) {
        $transaction_date = sanitize_text_field($_POST['ww_transaction_date']);
        // Convert datetime-local to MySQL datetime format
        $transaction_date = date('Y-m-d H:i:s', strtotime($transaction_date));
        update_post_meta($post_id, 'transaction_date', $transaction_date);
    }

    if (isset($_POST['ww_sheba_number'])) {
        $sheba_number = sanitize_text_field($_POST['ww_sheba_number']);
        update_post_meta($post_id, 'sheba_number', $sheba_number);
    }

}
add_action('save_post', 'ww_save_withdraw_details');

/**
 * 13. (Optional) Automatically Update Status Change Date When Status Changes via Other Means
 *
 * This ensures that the 'status_change_date' is updated even if the status is changed outside the meta box (e.g., via bulk actions).
 */
function ww_update_status_change_date($new_status, $old_status, $post)
{
    if ('wallet_withdraw' !== $post->post_type) {
        return;
    }

    // Check if the status has changed
    if ($new_status !== $old_status) {
        // Update the status_change_date meta field
        $current_datetime = current_time('mysql');
        update_post_meta($post->ID, 'status_change_date', $current_datetime);
    }
}
add_action('transition_post_status', 'ww_update_status_change_date', 10, 3);


// Prevent Elementor from connecting to my.elementor.com
add_filter('elementor/connect/additional-connect-info', '__return_empty_array');
add_filter('elementor/connect/connect-url', '__return_empty_string');
add_filter('elementor/connect/remote-info-data', '__return_empty_array');

// Handle the base-app.php errors by providing default values
add_filter('elementor/connect/apps/get_client_data', function($client_data) {
    if (is_wp_error($client_data)) {
        return [
            'client_id' => '',
            'auth_secret' => '',
            'redirect_uri' => '',
            'callback' => '',
            'admin_notice' => '',
        ];
    }
    return $client_data;
}, 10, 1);

// Prevent connection attempts entirely without showing error
add_filter('pre_http_request', function($pre, $parsed_args, $url) {
    if (strpos($url, 'my.elementor.com') !== false) {
        // Return a valid response to avoid the error
        return [
            'body' => '',
            'response' => [
                'code' => 200,
            ],
            'headers' => [],
            'cookies' => [],
        ];
    }
    return $pre;
}, 10, 3);

// Disable Elementor Connect Library
add_action('elementor/init', function() {
    if (class_exists('\Elementor\Core\Common\Modules\Connect\Module')) {
        remove_action('elementor/editor/before_enqueue_scripts', [
            \Elementor\Core\Common\Modules\Connect\Module::class,
            'enqueue_connect_scripts'
        ]);
    }
});

// Remove Connect menu item
add_action('admin_menu', function() {
    remove_submenu_page('elementor', 'elementor-connect');
}, 99);

// Disable library sync
add_filter('elementor/api/get_templates/body_args', '__return_empty_array');

// Prevent 404 errors on API routes
add_filter('elementor/api/get_info_data', '__return_empty_array');

// Suppress specific WP_Error notices
add_action('init', function() {
    remove_action('admin_notices', [\Elementor\Core\Common\Modules\Connect\Module::class, 'admin_notice']);
});
