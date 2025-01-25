<?php
if (WC()->cart->is_empty()) {
    return;
}

// Loop through cart items
foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item) {
    $product_id = $cart_item['product_id']; // Get product ID

    // Check if product has the required meta key
    $meta_value = get_post_meta($product_id, 'product_connections', true);

    // If meta key does not exist or is empty, remove item from cart
    if (empty($meta_value)) {
        WC()->cart->remove_cart_item($cart_item_key);
    }
}
$current_cart = WC()->cart->get_cart();
$total_regular_price = 0;
?>
<div class="doctor-checkout">
    <!-- Patient Information Section -->
    <?php
    if (!empty($current_cart)) {
    ?>
        <div class="patient-info">
            <h3>اطلاعات بیمار</h3>
            <div class="patient-check-phone">
                <div class="input-group-patient">
                    <label for="patient-phone">شماره همراه</label>
                    <input type="text" id="patient-phone" placeholder="شماره همراه بیمار" required>
                </div>
                <button class="checkout-pay-button search-patient-by-phone" onclick="searchPatientByPhone()">جستجو شماره</button>
            </div>
            <div class="patient-all-data">
                <div class="input-group-patient">
                    <label for="patient-name">نام</label>
                    <input type="text" id="patient-name" placeholder="نام بیمار" required>
                </div>
                <div class="input-group-patient">
                    <label for="patient-lastname">نام خانوادگی</label>
                    <input type="text" id="patient-lastname" placeholder="نام خانوادگی بیمار" required>
                </div>
                <div class="input-group-patient">
                    <label for="patient-gender">جنسیت</label>
                    <select id="patient-gender">
                        <option value="male">مرد</option>
                        <option value="female">زن</option>
                    </select>
                </div>
                <div class="input-group-patient">
                    <label for="patient-disease">بیماری</label>
                    <input type="text" id="patient-disease" placeholder="بیماری" required>
                </div>

                <div class="input-group-patient">
                    <label for="patient-state">نام استان</label>
                    <input type="text" id="patient-state" placeholder="نام استان" required>
                </div>
                <div class="input-group-patient">
                    <label for="patient-city">نام شهر</label>
                    <input type="text" id="patient-city" placeholder="نام شهر" required>
                </div>
                <div class="input-group-patient">
                    <label for="patient-address">آدرس</label>
                    <textarea id="patient-address" rows="3" placeholder="آدرس بیمار" required></textarea>
                </div>
            </div>
            <p class="patient-form-error" id="patient-form-error">تمام فیلد ها باید پر شوند</p>
        </div>
    <?php
    }
    ?>
    <!-- Product List Section -->
    <div class="checkout-product-list">
        <h3>محصولات انتخاب شده</h3>
        <div id="checkout-product-list">
            <?php if (!empty($current_cart)) : ?>
                <table class="checkout-product-table">
                    <thead>
                        <tr>
                            <th>نام محصول</th>
                            <th>تعداد</th>
                            <th>قیمت واحد</th>
                            <th>قیمت کل</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($current_cart as $cart_item_key => $cart_item) : ?>
                            <?php
                            $product = $cart_item['data'];
                            $product_name = $product->get_name();
                            $product_quantity = $cart_item['quantity'];
                            $product_price = $product->get_regular_price();
                            $total_price = $product_price * $product_quantity;
                            $total_regular_price = $total_regular_price + $total_price;
                            ?>
                            <tr>
                                <td><strong><?php echo esc_html($product_name); ?></strong></td>
                                <td><?php echo esc_html($product_quantity); ?></td>
                                <td><?php echo wc_price($product_price); ?></td>
                                <td><?php echo wc_price($total_price); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else : ?>
                <div class="checkout-product-item">سبد خرید خالی است.</div>
            <?php endif; ?>
        </div>
        <?php
        if (empty($current_cart)) {
            $shop_page_url = get_permalink(wc_get_page_id('shop'));

        ?>
            <button class="checkout-pay-button" onclick="window.location.href = '<?= $shop_page_url ?>'">فروشگاه</button>
        <?php
        } else {
        ?>
            <div class="checkout-total-amount">جمع کل: <span id="checkout-total-amount"><?php echo number_format($total_regular_price); ?> تومان</span></div>
            <button class="checkout-pay-button" onclick="processPayment()">پرداخت</button>
        <?php
        }
        ?>
    </div>
</div>