<?php
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
<!-- Main Page Layout -->
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