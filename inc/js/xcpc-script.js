function showLoginPopup() {
    document.getElementById("loginPopup").style.display = "block";
}

function closeLoginPopup() {
    document.getElementById("loginPopup").style.display = "none";
}

function sendOTP() {
    var current_path = window.location.pathname.split("/")[2];
    var type;
    switch (current_path) {
        case "popup_doctor_login":
            type = "doctor";
            break;
        case "popup_login":
            type = "normal";
            break;
    }
    var phoneNumber = jQuery("#xcpc_phone_input").val();
    var captcha = jQuery("#xcpc_captcha_input").val();
    jQuery.ajax({
        type: "POST",
        url: XcpcAjax.ajaxurl,
        data: {
            action: "xcpc_send_sms_to_phone",
            formData: {
                "type": type,
                "phone": phoneNumber,
                "captcha": captcha
            }
        },
        success: function (response) {
            // if (response.success) {
            //     alert(response.data);
            // }else{
            //     alert(response.data);
            // }
            alert(response.data);
            document.getElementById("xcpc_phoneForm").style.display = "none";
            document.getElementById("xcpc_otp_form").style.display = "block";
        },
        error: function (err) {
            console.log(err);
        }
    });
}

// verify the OTP entered by the user
function verifyOTP() {
    var OTP = jQuery("#xcpc_otp").val();
    jQuery.ajax({
        type: "POST",
        url: XcpcAjax.ajaxurl,
        data: {
            action: "xcpc_send_otp_veify",
            formData: {
                "otp": OTP,
            }
        },
        success: function (response) {
            if (response.success) {
                if (response.data['is_new'] == false) {
                    alert(response.data['message']);
                    window.location.href = "/wp/my-account";
                } else {
                    document.getElementById("xcpc_otp_form").style.display = "none";
                    document.getElementById("xcpc_signup_form").style.display = "block";
                }

            }
        },
        error: function (err) {
            console.log(err);
            alert("Error in otp verification!");
        }
    });
}

function signup() {
    var firstName = jQuery("#xcpc_signup_firstName").val();
    var lastName = jQuery("#xcpc_signup_lastName").val();
    var selectedGender = jQuery('input[name="gender"]:checked').val();
    console.log(selectedGender);

    jQuery.ajax({
        type: "POST",
        url: XcpcAjax.ajaxurl,
        data: {
            action: "xcpc_signup",
            formData: {
                "first_name": firstName,
                "last_name": lastName,
                "gender": selectedGender
            }
        },
        success: function (response) {
            if (response.success) {
                if (response.data == "ثبت نام با موفقیت انجام شد") {
                    document.getElementById("signup_success_message").style.display = "block";
                    window.location.href = "/wp/my-account";
                } else {
                    document.getElementById("signup_fail_message").style.display = "block";
                }

            }
        },
        error: function (err) {
            console.log(err);
            alert("Error in signup verification!");
        }
    });
}