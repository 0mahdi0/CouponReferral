<?php
// این کد را در یک فایل PHP (مثلاً در یک صفحه مدیریت یا قالب) قرار دهید
global $wpdb;
$table_name = $wpdb->prefix . "image_receipt"; 
// دریافت درخواست‌هایی که هنوز پردازش نشده‌اند (status = 0)
$pending_requests = $wpdb->get_results("SELECT * FROM {$table_name} WHERE status = 0", ARRAY_A); 
// دریافت درخواست‌های پردازش شده (تاریخچه: تایید یا لغو شده؛ status = 1)
$history_requests = $wpdb->get_results("SELECT * FROM {$table_name} WHERE status = 1", ARRAY_A);
?>
<style>
    .grid-container {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
        gap: 20px;
    } 
    /* استایل کارت درخواست */
    .card {
        background: #fff;
        border: 1px solid #ddd;
        border-radius: 8px;
        padding: 15px;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
    } 
    .card img {
        max-width: 100%;
        border-radius: 4px;
        margin-bottom: 10px;
    } 
    .card-details {
        margin-bottom: 10px;
    } 
    .card-details p {
        margin: 5px 0;
    } 
    .card-buttons {
        display: flex;
        justify-content: space-between;
    } 
    .card-buttons button {
        padding: 8px 12px;
        border: none;
        border-radius: 4px;
        color: #fff;
        cursor: pointer;
        flex: 1;
        margin: 0 5px;
    } 
    .card-buttons button:first-child {
        margin-right: 5px;
    } 
    .card-buttons button:last-child {
        margin-left: 5px;
    } 
    .approve-btn {
        background-color: #28a745;
    } 
    .cancel-btn {
        background-color: #dc3545;
    } 
    .card-buttons button:disabled {
        opacity: 0.6;
        cursor: not-allowed;
    } 
    /* استایل پاپ‌آپ */
    .modal-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.5);
        display: none;
        justify-content: center;
        align-items: center;
        z-index: 1000;
    } 
    .modal {
        background: #fff;
        padding: 20px;
        border-radius: 8px;
        width: 400px;
        position: relative;
    } 
    .modal h2 {
        margin-bottom: 15px;
    } 
    .modal textarea {
        width: 100%;
        height: 80px;
        padding: 10px;
        margin-bottom: 15px;
        resize: none;
    } 
    .modal .modal-buttons {
        text-align: right;
    } 
    .modal .modal-buttons button {
        padding: 8px 12px;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        color: #fff;
    } 
    .modal .confirm-cancel-btn {
        background-color: #dc3545;
    } 
    .modal .close-modal {
        position: absolute;
        top: 10px;
        left: 10px;
        background: transparent;
        border: none;
        font-size: 18px;
        cursor: pointer;
    } 
    /* استایل تب‌ها */
    .tabs {
        display: flex;
        margin-bottom: 20px;
    } 
    .tab {
        padding: 10px 20px;
        cursor: pointer;
        background: #ddd;
        margin-right: 5px;
        border-radius: 4px 4px 0 0;
    } 
    .tab.active {
        background: #fff;
        border-bottom: 2px solid #fff;
    } 
    .tab-content {
        display: none;
        background: #fff;
        padding: 20px;
        border: 1px solid #ddd;
        border-top: none;
    } 
    .tab-content.active {
        display: block;
    }
</style> 
<div class="tabs">
    <div class="tab active" id="tab-requests-receipt" onclick="showTab('requests-receipt')">فیش های واریزی</div>
    <div class="tab" id="tab-receipt-history" onclick="showTab('receipt-history')">تاریخچه فیش های واریزی</div>
</div> 
<!-- تب درخواست‌های فیش واریزی (در انتظار) -->
<div id="requests-receipt" class="tab-content active">
    <h1>درخواست‌های فیش واریزی</h1>
    <div class="grid-container" id="pending-card-container">
        <?php if (!empty($pending_requests)): ?>
            <?php foreach ($pending_requests as $request): ?>
                <!-- اضافه کردن data-attribute جهت استفاده در جاوا اسکریپت -->
                <div class="card" data-username="<?php echo esc_attr($request['username']); ?>"
                    data-order="<?php echo esc_attr($request['order_id']); ?>"
                    data-img="<?php echo esc_url($request['img']); ?>"
                    data-amount="<?php echo esc_attr($request['amount']); ?>">
                    <div class="card-details">
                        <p><strong>نام درخواست‌دهنده:</strong> <?php echo esc_html($request['username']); ?></p>
                        <p><strong>شماره سفارش:</strong> <?php echo esc_html($request['order_id']); ?></p>
                        <p><strong>قیمت سفارش:</strong> <?php echo number_format($request['amount']); ?> تومان</p>
                    </div>
                    <div class="card-image">
                        <img src="<?php echo esc_url($request['img']); ?>" alt="عکس فیش واریزی">
                    </div>
                    <div class="card-buttons">
                        <!-- فقط برای مواردی که هنوز status = 0 هستند -->
                        <button class="approve-btn" data-id="<?php echo esc_attr($request['id']); ?>">تایید</button>
                        <button class="cancel-btn" data-id="<?php echo esc_attr($request['id']); ?>">لغو</button>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p>هیچ درخواست موجود نیست.</p>
        <?php endif; ?>
    </div>
</div> 
<!-- تب تاریخچه فیش های واریزی (تایید یا لغو شده) -->
<div id="receipt-history" class="tab-content">
    <h1>تاریخچه فیش های واریزی</h1>
    <div class="grid-container" id="history-card-container">
        <?php if (!empty($history_requests)): ?>
            <?php foreach ($history_requests as $request): ?>
                <div class="card">
                    <div class="card-details">
                        <p><strong>نام درخواست‌دهنده:</strong> <?php echo esc_html($request['username']); ?></p>
                        <p><strong>شماره سفارش:</strong> <?php echo esc_html($request['order_id']); ?></p>
                        <p><strong>قیمت سفارش:</strong> <?php echo number_format($request['amount']); ?> تومان</p>
                        <?php if ($request['status_receipt'] == 0): ?>
                            <p><strong>دلیل لغو:</strong> <?php echo esc_html($request['reason_text']); ?></p>
                        <?php endif; ?>
                    </div>
                    <div class="card-image">
                        <img src="<?php echo esc_url($request['img']); ?>" alt="عکس فیش واریزی">
                    </div>
                    <div class="card-buttons">
                        <?php if ($request['status_receipt'] == 1): ?>
                            <button class="approve-btn" disabled>تایید شده</button>
                        <?php else: ?>
                            <button class="cancel-btn" disabled>لغو شده</button>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p>هیچ تاریخچه‌ای موجود نیست.</p>
        <?php endif; ?>
    </div>
</div> 
<!-- پنجره پاپ‌آپ برای لغو درخواست -->
<div class="modal-overlay" id="modal-overlay">
    <div class="modal">
        <button class="close-modal" id="close-modal">&times;</button>
        <h2>لغو درخواست</h2>
        <textarea id="cancel-reason" placeholder="دلیل لغو (اختیاری)"></textarea>
        <div class="modal-buttons">
            <button class="confirm-cancel-btn" id="confirm-cancel-btn">تایید نهایی</button>
        </div>
    </div>
</div> 
<script>
    // تابع تغییر تب‌ها
    function showTab(tabId) {
        var tabs = document.querySelectorAll('.tab');
        var contents = document.querySelectorAll('.tab-content');
        tabs.forEach(function (tab) {
            tab.classList.remove('active');
        });
        contents.forEach(function (content) {
            content.classList.remove('active');
        });
        document.getElementById('tab-' + tabId).classList.add('active');
        document.getElementById(tabId).classList.add('active');
    } 
    // تابع قالب‌بندی عدد به صورت جداکننده هزارگان
    function formatNumber(num) {
        return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
    } 
    var ajaxUrl = "<?php echo admin_url('admin-ajax.php'); ?>"; 
    // تابع تایید درخواست (Ajax)
    function approveAction(card, requestId) {
        fetch(ajaxUrl, {
            method: "POST",
            headers: { "Content-Type": "application/x-www-form-urlencoded; charset=UTF-8" },
            body: new URLSearchParams({
                action: "approve_receipt",
                id: requestId,
                order_id: card.dataset.order
            })
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // استخراج اطلاعات از data-attribute های کارت
                    var username = card.dataset.username;
                    var order = card.dataset.order;
                    var img = card.dataset.img;
                    var amount = card.dataset.amount;
                    var formattedAmount = formatNumber(amount); 
                    // حذف پیام "هیچ تاریخچه‌ای موجود نیست." (در صورت وجود) از تب تاریخچه
                    var historyContainer = document.getElementById('history-card-container');
                    var emptyHistoryMsg = historyContainer.querySelector('p');
                    if (emptyHistoryMsg && emptyHistoryMsg.textContent.trim() === "هیچ تاریخچه‌ای موجود نیست.") {
                        emptyHistoryMsg.remove();
                    } 
                    // ایجاد کارت جدید برای تاریخچه (تایید شده)
                    var newCard = document.createElement('div');
                    newCard.className = 'card';
                    newCard.setAttribute('data-username', username);
                    newCard.setAttribute('data-order', order);
                    newCard.setAttribute('data-img', img);
                    newCard.setAttribute('data-amount', amount);
                    newCard.innerHTML =
                        '<div class="card-details">' +
                        '<p><strong>نام درخواست‌دهنده:</strong> ' + username + '</p>' +
                        '<p><strong>شماره سفارش:</strong> ' + order + '</p>' +
                        '<p><strong>قیمت سفارش:</strong> ' + formattedAmount + ' تومان</p>' +
                        '</div>' +
                        '<div class="card-image">' +
                        '<img src="' + img + '" alt="عکس فیش واریزی">' +
                        '</div>' +
                        '<div class="card-buttons">' +
                        '<button class="approve-btn" disabled>تایید شده</button>' +
                        '</div>'; 
                    historyContainer.appendChild(newCard);
                    // حذف کارت از تب درخواست‌ها
                    card.remove();
                    // در صورت خالی شدن تب درخواست‌ها، اضافه کردن پیام "هیچ درخواست موجود نیست."
                    var pendingContainer = document.getElementById('pending-card-container');
                    if (pendingContainer.children.length === 0) {
                        pendingContainer.innerHTML = '<p>هیچ درخواست موجود نیست.</p>';
                    }
                } else {
                    alert("خطا در تایید درخواست.");
                }
            })
            .catch(error => {
                console.error("Error:", error);
                alert("خطا در ارسال درخواست تایید.");
            });
    } 
    // تابع لغو درخواست (Ajax)
    function cancelAction(card, requestId, reason) {
        fetch(ajaxUrl, {
            method: "POST",
            headers: { "Content-Type": "application/x-www-form-urlencoded; charset=UTF-8" },
            body: new URLSearchParams({
                action: "cancel_receipt",
                id: requestId,
                order_id: card.dataset.order,
                reason: reason
            })
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    var username = card.dataset.username;
                    var order = card.dataset.order;
                    var img = card.dataset.img;
                    var amount = card.dataset.amount;
                    var formattedAmount = formatNumber(amount); 
                    // حذف پیام "هیچ تاریخچه‌ای موجود نیست." از تب تاریخچه در صورت وجود
                    var historyContainer = document.getElementById('history-card-container');
                    var emptyHistoryMsg = historyContainer.querySelector('p');
                    if (emptyHistoryMsg && emptyHistoryMsg.textContent.trim() === "هیچ تاریخچه‌ای موجود نیست.") {
                        emptyHistoryMsg.remove();
                    } 
                    // ایجاد کارت جدید برای تاریخچه (لغو شده)
                    var detailsHtml = '<div class="card-details">' +
                        '<p><strong>نام درخواست‌دهنده:</strong> ' + username + '</p>' +
                        '<p><strong>شماره سفارش:</strong> ' + order + '</p>' +
                        '<p><strong>قیمت سفارش:</strong> ' + formattedAmount + ' تومان</p>';
                    if (reason) {
                        detailsHtml += '<p><strong>دلیل لغو:</strong> ' + reason + '</p>';
                    }
                    detailsHtml += '</div>'; 
                    var newCard = document.createElement('div');
                    newCard.className = 'card';
                    newCard.setAttribute('data-username', username);
                    newCard.setAttribute('data-order', order);
                    newCard.setAttribute('data-img', img);
                    newCard.setAttribute('data-amount', amount);
                    newCard.innerHTML = detailsHtml +
                        '<div class="card-image">' +
                        '<img src="' + img + '" alt="عکس فیش واریزی">' +
                        '</div>' +
                        '<div class="card-buttons">' +
                        '<button class="cancel-btn" disabled>لغو شده</button>' +
                        '</div>'; 
                    historyContainer.appendChild(newCard);
                    // حذف کارت از تب درخواست‌ها
                    card.remove();
                    // در صورت خالی شدن تب درخواست‌ها، اضافه کردن پیام "هیچ درخواست موجود نیست."
                    var pendingContainer = document.getElementById('pending-card-container');
                    if (pendingContainer.children.length === 0) {
                        pendingContainer.innerHTML = '<p>هیچ درخواست موجود نیست.</p>';
                    }
                } else {
                    alert("خطا در لغو درخواست.");
                }
            })
            .catch(error => {
                console.error("Error:", error);
                alert("خطا در ارسال درخواست لغو.");
            });
    } 
    // رویداد کلیک برای دکمه‌های تایید
    document.querySelectorAll('.approve-btn').forEach(function (button) {
        button.addEventListener('click', function () {
            var card = button.closest('.card');
            var requestId = button.dataset.id;
            approveAction(card, requestId);
            // غیر فعال کردن دکمه‌های کارت برای جلوگیری از کلیک مجدد
            card.querySelectorAll('button').forEach(function (btn) {
                btn.disabled = true;
            });
        });
    }); 
    // متغیر برای ذخیره دکمه لغو انتخاب‌شده
    var currentCancelButton = null; 
    // رویداد کلیک برای دکمه‌های لغو
    document.querySelectorAll('.cancel-btn').forEach(function (button) {
        button.addEventListener('click', function () {
            currentCancelButton = button;
            showModal();
        });
    }); 
    // المان‌های مربوط به پاپ‌آپ
    var modalOverlay = document.getElementById('modal-overlay');
    var closeModalBtn = document.getElementById('close-modal');
    var confirmCancelBtn = document.getElementById('confirm-cancel-btn');
    var cancelReasonInput = document.getElementById('cancel-reason'); 
    // نمایش پاپ‌آپ
    function showModal() {
        modalOverlay.style.display = 'flex';
        cancelReasonInput.value = ''; // پاکسازی تکست‌باکس
    } 
    // بستن پاپ‌آپ
    function hideModal() {
        modalOverlay.style.display = 'none';
    } 
    closeModalBtn.addEventListener('click', function () {
        hideModal();
    }); 
    // رویداد تایید نهایی لغو در پاپ‌آپ
    confirmCancelBtn.addEventListener('click', function () {
        var reason = cancelReasonInput.value;
        if (currentCancelButton) {
            var card = currentCancelButton.closest('.card');
            var requestId = currentCancelButton.dataset.id;
            cancelAction(card, requestId, reason);
            card.querySelectorAll('button').forEach(function (btn) {
                btn.disabled = true;
            });
        }
        hideModal();
    });
</script>