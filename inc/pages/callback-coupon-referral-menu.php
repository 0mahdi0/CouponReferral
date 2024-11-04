<div class="tabs">
    <div class="tab active" id="tab-discounts" onclick="showTab('discounts')">تعیین تخفیف ها</div>
    <div class="tab" id="tab-users" onclick="showTab('users')">کاربران و زیرمجموعه ها</div>
</div>

<div id="discounts" class="tab-content active">
    <h2>تعیین تخفیف ها</h2>

    <!-- Form with sections and input fields -->
    <form id="discounts-form">
        <div class="discount-section">
            <label for="site-discount">تعیین تخفیف های کلی سایت</label>
            <input type="number" name="site-discount" id="site-discount" value="<?= $xcpcConfig['siteDiscount'] ?>"
                required>
        </div>
        <div class="discount-section">
            <label for="discount-code">تعیین تخفیف کد خرید ارزان</label>
            <input type="number" name="discount-code" id="discount-code" value="<?= $xcpcConfig['discountCode'] ?>"
                required>
        </div>
        <div class="discount-section">
            <label for="doctor-discount">تعیین تخفیف پزشک</label>
            <input type="number" name="doctor-discount" id="doctor-discount" value="<?= $xcpcConfig['doctorDiscount'] ?>"
                required>
        </div>
        <div class="discount-section">
            <label for="wallet-return">درصد بازگشت مبلغ به کیف پول</label>
            <input type="number" name="wallet-return" id="wallet-return" min="0" max="100"
                value="<?= $xcpcConfig['walletReturn'] ?>" required>
        </div>
        <div class="discount-section">
            <label for="withdrawal-conditions">شرط های برداشت پول و تعیین درصد های آن</label>
            <input type="number" name="withdrawal-conditions" id="withdrawal-conditions"
                value="<?= $xcpcConfig['withdrawalConditions'] ?>" required>
        </div>
        <div class="discount-section">
            <label for="specific-user-return">درصد بازگشت مبلغ به کیف پول برای کاربر های خاص</label>
            <input type="number" name="specific-user-return" id="specific-user-return"
                value="<?= $xcpcConfig['specificUserReturn'] ?>" required>
        </div>
        <div class="discount-section">
            <label for="event-discount">تخفیف رویداد ها</label>
            <input type="number" name="event-discount" id="event-discount" value="<?= $xcpcConfig['eventDiscount'] ?>"
                required>
        </div>

        <!-- Single submit button -->
        <button type="submit" class="submit-button">ثبت تخفیف ها</button>
    </form>
</div>

<div id="users" class="tab-content">
    <h2>کاربران و زیرمجموعه ها</h2>

    <!-- List view for users and subcategories -->
    <ul class="user-list">
        <li>کاربران بازاریاب و آمار زیرمجموعه ها</li>
        <!-- Add more users/subcategories as needed -->
    </ul>

    <div class="description" id="left-description"></div>
</div>