<?php
include_once(XCPC_DIR . "inc/lib/captcha.php");
$captcha = new Captcha;
?>
<!-- Main Page Layout -->
<div class="doctor_login_page">
    <div class="login-container">
        <button id="loginBtn" onclick="showLoginPopup()">ورود</button>
    </div>

    <!-- Login Popup -->
    <div id="loginPopup" class="popup">
        <div class="popup-content">
            <span class="close-btn" onclick="closeLoginPopup()">&times;</span>
            <h2>ورود به حساب پزشکان</h2>
            <form id="xcpc_phoneForm">
                <label for="phone">شماره موبایل:</label>
                <input type="tel" id="xcpc_phone_input" name="phone" placeholder="شماره موبایل خود را وارد کنید"
                    required>
                <div class="captcha-section">
                    <label for="captcha">کد امنیتی:</label>
                    <div>
                        <img id="xcpc_captcha_img" src="<?php echo $captcha->CaptchaImage(); ?>" alt="Captcha Image" />
                        <input type="text" id="xcpc_captcha_input" name="captcha" placeholder="کد کپچا را وارد کنید"
                            required>
                    </div>
                </div>
                <button type="button" onclick="sendOTP()">ارسال کد</button>
            </form>

            <form id="xcpc_otp_form" style="display: none;">
                <label for="otp">کد تایید:</label>
                <input type="text" id="xcpc_otp" name="otp" placeholder="کد تایید را وارد کنید" required>
                <button type="button" onclick="verifyOTP()">ورود</button>
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
                <button type="button" onclick="signup()">ثبت نام</button>
            </form>
            <div>
                <p id="signup_success_message">ثبت نام با موفقیت انجام شد. انتقال به حساب کاربری...</p>
                <p id="signup_fail_message">ثبت نام با خطا مواجه شد!</p>
            </div>
        </div>
    </div>
</div>