<?php

if (isset($_POST['OrderId'])) {
    include_once(XCPC_DIR . "inc/lib/payment/newGate.php");
    $Payment_Melli = new Payment_Melli;
    $paymentVerify = $Payment_Melli->paymentVerify();

    // Buffer the output to avoid any "headers already sent" issues
    ob_start();

    if ($paymentVerify['status']) { ?>
        <div class="message-box _success">
            <h2> پرداخت انجام شد </h2>
            <p> <?= $paymentVerify['message'] ?> </p>
        </div>
    <?php
    } else {
    ?>
        <div class="message-box _success _failed">
            <h2> پرداخت ناموفق </h2>
            <p> <?= $paymentVerify['message'] ?> </p>
        </div>
    <?php
    }

    // Flush the buffer
    ob_end_flush();
} else {
    $rehome_url = home_url();
    ?>
    <script>
        window.location.href = '<?= $rehome_url ?>';
    </script>
<?php
}
