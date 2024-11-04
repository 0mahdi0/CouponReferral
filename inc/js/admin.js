jQuery("#discounts-form").on("submit", function (event) {
    event.preventDefault(); // Prevent the form from submitting the traditional way

    // Collect form data into a JSON object
    var formData = {
        siteDiscount: parseInt(jQuery("#site-discount").val()),
        discountCode: jQuery("#discount-code").val(),
        doctorDiscount: jQuery("#doctor-discount").val(),
        walletReturn: jQuery("#wallet-return").val(),
        withdrawalConditions: jQuery("#withdrawal-conditions").val(),
        specificUserReturn: jQuery("#specific-user-return").val(),
        eventDiscount: jQuery("#event-discount").val()
    };

    // Send AJAX request to WordPress
    jQuery.ajax({
        type: "POST",
        url: myAjax.ajaxurl,
        data: {
            action: "xcpc_submit_setting_form",
            formData: formData
        },
        success: function (response) {
            // if (response.success) {
            //     alert("Form submitted successfully!");
            // } else {
            //     alert("Form submission failed. Please try again.");
            // }
        }
    });
});

function showTab(tabId) {
    jQuery(".tab-content").removeClass("active");
    jQuery("#" + tabId).addClass("active");
    jQuery(".tab").removeClass("active");
    jQuery("#tab-" + tabId).addClass("active");
}