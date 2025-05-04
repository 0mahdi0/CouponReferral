// doctor-dashboard.js

jQuery(document).ready(function($) {
    // Tab Switching
    $('.doctor-dashboard-container .tab').on('click', function() {
        var tab = $(this).data('tab');

        // Remove active class from all tabs and add to the clicked tab
        $('.doctor-dashboard-container .tab').removeClass('active');
        $(this).addClass('active');

        // Hide all tab panes and show the selected one
        $('.doctor-dashboard-container .tab-pane').removeClass('active');
        $('#' + tab).addClass('active');
    });

    // Handle Profile Form Submission
    $('#doctor-profile-form').on('submit', function(e) {
        e.preventDefault();

        var formData = {
            action: 'xcpc_update_doctor_profile',
            nonce: DoctorDashboard.nonce,
            first_name: $('#first_name').val(),
            last_name: $('#last_name').val(),
            email: $('#email').val(),
            phone: $('#phone').val(),
        };

        $.ajax({
            type: 'POST',
            url: DoctorDashboard.ajax_url,
            data: formData,
            success: function(response) {
                if (response.success) {
                    $('#profile-update-message').html('<p class="success-message">' + response.data + '</p>');
                } else {
                    $('#profile-update-message').html('<p class="error-message">' + response.data + '</p>');
                }
            },
            error: function() {
                $('#profile-update-message').html('<p class="error-message">یک خطا رخ داد. لطفاً دوباره تلاش کنید.</p>');
            }
        });
    });
});
