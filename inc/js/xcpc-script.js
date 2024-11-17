function sendOTP(elem) {
    jQuery(elem).html('<i class="fa fa-spinner fa-spin" aria-hidden="true"></i>');
    jQuery(elem).prop('disabled', true);
    jQuery("#signup_fail_else").hide();
    var phoneNumber = jQuery("#xcpc_phone_input").val();
    var type = jQuery("#xcpc_login_input_type").val();
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
            if (response.success) {
                document.getElementById("xcpc_phoneForm").style.display = "none";
                document.getElementById("xcpc_otp_form").style.display = "block";
            } else {
                jQuery("#signup_fail_else").show();
                jQuery("#signup_fail_else").html(response.data);
            }
            jQuery(elem).html('ارسال کد');
            jQuery(elem).prop('disabled', false);
        },
        error: function (err) {
            console.log(err);
            jQuery(elem).html('ارسال کد');
            jQuery(elem).prop('disabled', false);
        }
    });
}

// verify the OTP entered by the user
function verifyOTP(elem) {
    jQuery(elem).html('<i class="fa fa-spinner fa-spin" aria-hidden="true"></i>');
    jQuery(elem).prop('disabled', true);
    jQuery("#signup_fail_else").hide();
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
                    jQuery("#signup_success_message").show();
                    window.location.href = "/my-account";
                } else {
                    document.getElementById("xcpc_otp_form").style.display = "none";
                    document.getElementById("xcpc_signup_form").style.display = "block";
                }
            } else {
                jQuery("#signup_fail_else").show();
                jQuery("#signup_fail_else").html(response.data);
            }
            jQuery(elem).html('ورود');
            jQuery(elem).prop('disabled', false);
        },
        error: function (err) {
            jQuery(elem).html('ورود');
            jQuery(elem).prop('disabled', false);
        }
    });
}

function signup(elem) {
    jQuery(elem).html('<i class="fa fa-spinner fa-spin" aria-hidden="true"></i>');
    jQuery(elem).prop('disabled', true);
    jQuery("#signup_fail_else").hide();
    var firstName = jQuery("#xcpc_signup_firstName").val();
    var lastName = jQuery("#xcpc_signup_lastName").val();
    var selectedGender = jQuery('input[name="gender"]:checked').val();
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
                jQuery("#signup_success_message").show();
                window.location.href = "/my-account";
            } else {
                jQuery("#signup_fail_else").show();
                jQuery("#signup_fail_else").html(response.data);
                jQuery(elem).html('ثبت نام');
                jQuery(elem).prop('disabled', false);
            }
        },
        error: function (err) {
            jQuery(elem).html('ثبت نام');
            jQuery(elem).prop('disabled', false);
        }
    });
}

function processPayment() {
    // Gather form data
    jQuery("#patient-form-error").hide();
    var patient_name = jQuery("#patient-name").val();
    var patient_lastname = jQuery("#patient-lastname").val();
    var patient_gender = jQuery("#patient-gender").val();
    var patient_disease = jQuery("#patient-disease").val();
    var patient_phone = jQuery("#patient-phone").val();
    var patient_address = jQuery("#patient-address").val();
    var patient_city = jQuery("#patient-city").val();
    var patient_state = jQuery("#patient-state").val();
    if (!patient_name || !patient_lastname || !patient_gender || !patient_disease || !patient_phone || !patient_address || !patient_city || !patient_state) {
        jQuery("#patient-form-error").show();
        return false;
    }
    const data = {
        action: 'process_doctor_checkout_payment', // This action should be registered in WordPress
        nonce: XcpcAjax.nonce,
        patient_name: patient_name,
        patient_lastname: patient_lastname,
        patient_gender: patient_gender,
        patient_disease: patient_disease,
        patient_phone: patient_phone,
        patient_address: patient_address,
        patient_city: patient_city,
        patient_state: patient_state,
    };

    // AJAX request
    jQuery.ajax({
        url: XcpcAjax.ajaxurl, // Ensure this URL is defined in WordPress
        type: 'POST',
        data: data,
        success: function (response) {
            if (response.success) {
                jQuery('body').append(response.data.message);
            } else {
                console.log(response.data.message);
            }
        },
        error: function (error) {
            console.error('Error:', error);
        }
    });
}

function searchPatientByPhone() {
    var patient_phone = jQuery("#patient-phone").val();
    const data = {
        action: 'process_search_patient_by_phone', // This action should be registered in WordPress
        nonce: XcpcAjax.nonce,
        patient_phone: patient_phone
    };
    // AJAX request
    jQuery.ajax({
        url: XcpcAjax.ajaxurl, // Ensure this URL is defined in WordPress
        type: 'POST',
        data: data,
        success: function (response) {
            if (response.success) {
                if (response.data.patient_info) {
                    jQuery("#patient-name").val(response.data.patient_info['name']);
                    jQuery("#patient-lastname").val(response.data.patient_info['lastname']);
                    jQuery("#patient-gender").val(response.data.patient_info['gender']);
                    jQuery("#patient-disease").val(response.data.patient_info['disease']);
                    jQuery("#patient-phone").val(response.data.patient_info['phone']);
                    jQuery("#patient-address").val(response.data.patient_info['paddress']);
                    jQuery("#patient-city").val(response.data.patient_info['city']);
                    jQuery("#patient-state").val(response.data.patient_info['pstate']);
                }
                jQuery(".patient-all-data").show();
            }
        },
        error: function (error) {
            console.error('Error:', error);
        }
    });
}

function showWithdrawalPopup() {
    document.getElementById('withdrawalPopup').style.display = 'block';
}

function closeWithdrawalPopup() {
    document.getElementById('withdrawalPopup').style.display = 'none';
}

function submitWithdrawalRequest(accessibleWithdrawalAmount) {
    jQuery("#withdrawal_fail_message").hide();
    var withdrawal_amount = jQuery("#xcpc_withdrawal_amount").val();
    var sheba_number = jQuery("#xcpc_sheba_number").val();
    if (accessibleWithdrawalAmount < withdrawal_amount) {
        jQuery("#withdrawal_fail_message").show();
        return false;
    }
    const data = {
        action: 'process_withdrawal_request', // This action should be registered in WordPress
        nonce: XcpcAjax.nonce,
        withdrawal_amount: withdrawal_amount,
        sheba_number: sheba_number
    };
    jQuery.ajax({
        url: XcpcAjax.ajaxurl, // Ensure this URL is defined in WordPress
        type: 'POST',
        data: data,
        success: function (response) {
            if (response.success) {
                jQuery("#withdrawal_success_message").show();
                setTimeout(() => {
                    closeWithdrawalPopup();
                }, 1500);
            }
        },
        error: function (error) {
            console.error('Error:', error);
        }
    });
}

jQuery("#xcpc_phoneForm, #xcpc_otp_form ,#xcpc_signup_form").submit(function (e) {
    e.preventDefault()
    return false;
});