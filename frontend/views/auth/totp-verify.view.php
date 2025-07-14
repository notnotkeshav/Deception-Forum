<?php require base_path('frontend/views/partials/header.php'); ?>

<div class="container">
    <h1>Two-Factor Authentication</h1>
    <p>Please enter the 6-digit code from your authenticator app</p>

    <form id="totpForm" method="POST" action="">
        <input type="hidden" name="action" value="verify-login">

        <div class="form-group">
            <label for="code">Verification Code</label>
            <input type="text" id="code" name="code" class="form-control"
                pattern="\d{6}" maxlength="6" required autofocus>
        </div>

        <button type="submit" class="btn btn-primary">Verify</button>
    </form>
    <div id="error-block" class="mt-3 text-danger"></div>
    <div id="success-block" class="mt-3 text-success"></div>
</div>

<script>
    $('#totpForm').on('submit', function(e) {
        e.preventDefault();

        // Clear previous messages
        $('#error-block').empty().hide();
        $('#success-block').empty().hide();

        // Get form data
        const formData = $(this).serialize();

        // Show loading state
        const submitBtn = $(this).find('button[type="submit"]');
        const originalBtnText = submitBtn.text();
        submitBtn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Verifying...');

        $.ajax({
            url: '/verify-totp',
            method: 'POST',
            dataType: 'json',
            data: formData,
            success: function(response) {
                if (response.success) {
                    if (response.details && response.details.session) {
                        // Store session data
                        sessionStorage.setItem('token', response.details.session.token);
                        sessionStorage.setItem('userId', response.details.session.userId);
                        sessionStorage.setItem('user', JSON.stringify(response.details.session.user));

                        if (response.details.session.moderator !== undefined) {
                            sessionStorage.setItem('moderator', response.details.session.moderator);
                        }

                        // Determine redirect URL
                        let redirectUrl = '/threads'; // Default

                        // Check URL params for returnTo
                        const urlParams = new URLSearchParams(window.location.search);
                        const returnTo = urlParams.get('returnTo');
                        if (returnTo) {
                            redirectUrl = decodeURIComponent(returnTo);
                        }

                        // Check response for redirect
                        if (response.details.redirect) {
                            redirectUrl = response.details.redirect;
                        }

                        // Show success and redirect
                        $('#success-block').text('Verification successful! Redirecting...').show();
                        setTimeout(() => {
                            window.location.href = redirectUrl;
                        }, 1000);
                    } else {
                        $('#success-block').text(response.message || 'Verification successful!').show();
                    }
                } else {
                    $('#error-block').text(response.message || 'Verification failed').show();
                    // Reset the code input for retry
                    $('#code').val('').focus();
                }
            },
            error: function(xhr) {
                const error = xhr.responseJSON || {};
                const errorMessage = error.message || 'Verification failed. Please try again.';
                $('#error-block').text(errorMessage).show();
                console.error('TOTP verification error:', error);
            },
            complete: function() {
                // Restore button state
                submitBtn.prop('disabled', false).text(originalBtnText);
            }
        });
    });
</script>

<?php require base_path('frontend/views/partials/footer.php'); ?>