<?php
include_once(XCPC_DIR . "inc/lib/captcha.php");
$captcha = new Captcha;
?>
<!-- Main Page Layout -->
<div class="normal_login_page">
    <!-- Login Popup -->
    <div id="loginPopup" class="popup">
        <div class="popup-content">
            <h2>ورود به حساب</h2>
            <form id="xcpc_phoneForm">
                <label for="phone">شماره موبایل:</label>
                <input type="hidden" value="normal" name="xcpc_login_input_type" id="xcpc_login_input_type">
                <input type="number" dir="ltr" id="xcpc_phone_input" name="phone" placeholder="شماره موبایل خود را وارد کنید"
                    required>
                <div class="captcha-section">
                    <label for="captcha">کد امنیتی:</label>
                    <div>
                        <img id="xcpc_captcha_img" src="<?php echo $captcha->CaptchaImage(); ?>" alt="Captcha Image" />
                        <input type="number" dir="ltr" id="xcpc_captcha_input" name="captcha" placeholder="کد کپچا را وارد کنید"
                            required>
                    </div>
                </div>
                <button type="button" class="login-btn" onclick="sendOTP(this)">ارسال کد</button>
            </form>

            <form id="xcpc_otp_form" style="display: none;">
                <label for="otp">کد تایید:</label>
                <input type="number" id="xcpc_otp" dir="ltr" name="otp" placeholder="کد تایید را وارد کنید" required>
                <button type="button" class="login-btn" onclick="verifyOTP(this)">ورود</button>
            </form>

            <form id="xcpc_signup_form" style="display: none;">
                <label for="otp">نام:</label>
                <input type="text" id="xcpc_signup_firstName" name="firstName" placeholder="نام" required>
                <label for="otp">نام خانوادگی:</label>
                <input type="text" id="xcpc_signup_lastName" name="lastName" placeholder="نام خانوادگی" required>
                <div>
                    <label for="otp">جنسیت:</label>
                </div>
                <div>
                    <input type="radio" id="xcpc_gender_man" name="gender" value="man" , checked="checked">
                    <label for="xcpc_gender_man">مرد</label>
                    <input type="radio" id="xcpc_gender_woman" name="gender" value="woman">
                    <label for="xcpc_gender_woman">زن</label>
                </div>
                <button type="button" class="login-btn" onclick="signup(this)">ثبت نام</button>
            </form>
            <div>
                <p id="signup_success_message">با موفقیت انجام شد. انتقال به حساب کاربری...</p>
                <p id="signup_fail_else"></p>
            </div>
        </div>
    </div>
</div>