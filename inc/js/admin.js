jQuery("#discounts-form").on("submit", function (event) {
    event.preventDefault(); // Prevent the form from submitting the traditional way

    // Collect form data into a JSON object
    var formData = {
        siteDiscount: parseInt(jQuery("#site-discount").val()),
        discountCode: jQuery("#discount-code").val(),
        doctorDiscount: jQuery("#doctor-discount").val(),
        walletReturn: jQuery("#wallet-return").val(),
        withdrawalConditions: conditions,
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

function addConditionsForm() {
    jQuery('.withdrawal-conditions').html("");
    conditions.push({
        "min": 0,
        "max": 0,
        "precent":100
    });
    conditions.forEach((condition, index) => {
        jQuery('.withdrawal-conditions').append(
            `
            <div>
                <input type="number" onkeyup="setConditionValue('min',` + index + `,this)" placeholder="حداقل (تومان)" value="` + condition['min'] + `" class="conditions-min" >
                <input type="number" onkeyup="setConditionValue('max',` + index + `,this)" placeholder="حداکثر (تومان)" value="` + condition['max'] + `" class="conditions-max" >
                <input type="number" onkeyup="setConditionValue('precent',` + index + `,this)" placeholder="درصد برداشت" value="` + condition['precent'] + `" min="1" max="100" disabled class="conditions-precent" >
                <button onclick="removeConditionsForm(` + index + `)" class="submit-button">-</button>
            </div>
            <br>
            `
        );
    });
}

function removeConditionsForm(index) {
    jQuery('.withdrawal-conditions').html("");
    conditions.splice(index, 1);
    conditions.forEach((condition, index) => {
        jQuery('.withdrawal-conditions').append(
            `
            <div>
                <input type="number" onkeyup="setConditionValue('min',` + index + `,this)" placeholder="حداقل (تومان)" value="` + condition['min'] + `" class="conditions-min" >
                <input type="number" onkeyup="setConditionValue('max',` + index + `,this)" placeholder="حداکثر (تومان)" value="` + condition['max'] + `" class="conditions-max" >
                <input type="number" onkeyup="setConditionValue('precent',` + index + `,this)" placeholder="درصد برداشت" value="` + condition['precent'] + `" min="1" max="100" disabled class="conditions-precent" >
                <button onclick="removeConditionsForm(` + index + `)" class="submit-button">-</button>
            </div>
            <br>
            `
        );
    });
}

function setConditionValue(type, index, elem) {
    if (['min', 'max', 'precent'].includes(type)) {
        conditions[index][type] = elem.value;
        if (type === "max") {
            if (elem.value != "0" && elem.value != "") {
                jQuery(elem).parent().find(".conditions-precent").removeAttr('disabled');
            } else {
                conditions[index]['precent'] = 100;
                jQuery(elem).parent().find(".conditions-precent").attr('disabled', 'disabled');
            }
        }
    }
}