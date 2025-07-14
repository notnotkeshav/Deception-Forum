<?php require base_path('frontend/views/partials/header.php'); ?>

<div class="container mt-4">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card shadow-lg">
                <div class="card-header bg-primary text-white">
                    <h3 class="mb-0"><i class="fas fa-shield-alt"></i> Two-Factor Authentication</h3>
                </div>
                <div class="card-body">
                    <!-- Alert Container -->
                    <div id="alertContainer"></div>

                    <?php if (!$totpEnabled): ?>
                        <!-- Initial setup step -->
                        <div id="setupInitial">
                            <div class="alert alert-info border-0 shadow-sm">
                                <div class="d-flex">
                                    <div class="flex-shrink-0">
                                        <i class="fas fa-info-circle fa-2x text-info"></i>
                                    </div>
                                    <div class="flex-grow-1 ms-3">
                                        <h5 class="alert-heading">Secure Your Account</h5>
                                        <p class="mb-3">Two-factor authentication adds an extra layer of security to your account. You'll need an authenticator app like Google Authenticator, Authy, or Microsoft Authenticator.</p>
                                        <div class="benefits-list">
                                            <h6><i class="fas fa-check-circle text-success"></i> Benefits:</h6>
                                            <ul class="list-unstyled ms-3">
                                                <li><i class="fas fa-check text-success me-2"></i>Protects against password theft</li>
                                                <li><i class="fas fa-check text-success me-2"></i>Prevents unauthorized access</li>
                                                <li><i class="fas fa-check text-success me-2"></i>Meets modern security standards</li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="text-center">
                                <button id="startSetupBtn" class="btn btn-primary btn-lg px-5 py-3">
                                    <i class="fas fa-shield-alt me-2"></i> Start Setup
                                </button>
                            </div>
                        </div>

                        <!-- QR Code display step -->
                        <div id="setupStep1" style="display: none;">
                            <div class="alert alert-warning border-0 shadow-sm">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                <strong>Important:</strong> Keep this page open until setup is complete!
                            </div>

                            <div class="progress mb-4" style="height: 8px;">
                                <div class="progress-bar bg-primary" role="progressbar" style="width: 50%;">
                                    <span class="sr-only">50% Complete</span>
                                </div>
                            </div>
                            <div class="text-center mb-3">
                                <small class="text-muted"><strong>Step 1 of 2:</strong> Add to Authenticator App</small>
                            </div>

                            <div class="row g-4">
                                <div class="col-md-6">
                                    <div class="card border-0 shadow-sm h-100">
                                        <div class="card-header bg-light">
                                            <h5 class="mb-0"><i class="fas fa-qrcode text-primary me-2"></i>Scan QR Code</h5>
                                        </div>
                                        <div class="card-body text-center">
                                            <div id="qrCodeContainer" class="mb-3" style="min-height: 220px; display: flex; align-items: center; justify-content: center;">
                                                <div class="spinner-border text-primary" role="status">
                                                    <span class="visually-hidden">Loading...</span>
                                                </div>
                                            </div>
                                            <p class="text-muted small">Open your authenticator app and scan this QR code</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="card border-0 shadow-sm h-100">
                                        <div class="card-header bg-light">
                                            <h5 class="mb-0"><i class="fas fa-key text-primary me-2"></i>Manual Entry</h5>
                                        </div>
                                        <div class="card-body">
                                            <div class="mb-3">
                                                <label for="secretKey" class="form-label">Secret Key:</label>
                                                <div class="input-group">
                                                    <input type="text" id="secretKey" class="form-control font-monospace" readonly>
                                                    <button class="btn btn-outline-secondary" type="button" onclick="copySecret()">
                                                        <i class="fas fa-copy"></i>
                                                    </button>
                                                </div>
                                            </div>
                                            <div class="alert alert-light border small">
                                                <i class="fas fa-info-circle text-info me-2"></i>
                                                If you can't scan the QR code, manually enter this secret key in your authenticator app.
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="text-center mt-4">
                                <button id="continueBtn" class="btn btn-success btn-lg px-5 py-3">
                                    <i class="fas fa-arrow-right me-2"></i> I've Added the Account
                                </button>
                            </div>
                        </div>

                        <!-- Verification step -->
                        <div id="setupStep2" style="display: none;">
                            <div class="alert alert-info border-0 shadow-sm">
                                <i class="fas fa-mobile-alt me-2"></i>
                                <strong>Final Step:</strong> Enter the 6-digit code from your authenticator app to complete setup.
                            </div>

                            <div class="progress mb-4" style="height: 8px;">
                                <div class="progress-bar bg-success" role="progressbar" style="width: 100%;">
                                    <span class="sr-only">100% Complete</span>
                                </div>
                            </div>
                            <div class="text-center mb-3">
                                <small class="text-muted"><strong>Step 2 of 2:</strong> Verify Setup</small>
                            </div>

                            <form id="verifyForm">
                                <input type="hidden" name="action" value="verify-setup">
                                <input type="hidden" name="csrf_token" id="csrfToken" value="">

                                <div class="row justify-content-center">
                                    <div class="col-md-6">
                                        <div class="card border-0 shadow-sm">
                                            <div class="card-body text-center p-4">
                                                <div class="mb-4">
                                                    <label for="code" class="form-label h5">Verification Code</label>
                                                    <input type="text" id="code" name="code" class="form-control form-control-lg text-center"
                                                        pattern="\d{6}" maxlength="6" required autofocus
                                                        placeholder="000000" style="font-size: 1.8em; letter-spacing: 0.3em; height: 60px;">
                                                    <div class="form-text">Enter the 6-digit code from your authenticator app</div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>
                    <?php else: ?>
                        <!-- TOTP already enabled -->
                        <div class="alert alert-success border-0 shadow-sm">
                            <div class="d-flex">
                                <div class="flex-shrink-0">
                                    <i class="fas fa-check-circle fa-2x text-success"></i>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <h5 class="alert-heading">Two-Factor Authentication Enabled</h5>
                                    <p class="mb-0">Your account is protected with two-factor authentication.</p>
                                </div>
                            </div>
                        </div>

                        <div class="card border-0 shadow-sm mt-4">
                            <div class="card-header bg-light">
                                <h5 class="mb-0"><i class="fas fa-cog me-2"></i>Manage Two-Factor Authentication</h5>
                            </div>
                            <div class="card-body">
                                <form id="disableForm">
                                    <input type="hidden" name="action" value="disable">
                                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token'] ?? ''; ?>">

                                    <div class="mb-3">
                                        <label for="password" class="form-label">Enter your password to disable TOTP:</label>
                                        <input type="password" id="password" name="password" class="form-control" required>
                                    </div>

                                    <div class="alert alert-warning border-0 shadow-sm">
                                        <i class="fas fa-exclamation-triangle me-2"></i>
                                        <strong>Warning:</strong> Disabling two-factor authentication will make your account less secure.
                                    </div>

                                    <button type="submit" class="btn btn-danger">
                                        <i class="fas fa-times me-2"></i> Disable Two-Factor Authentication
                                    </button>
                                </form>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Loading Modal -->
<div class="modal fade" id="loadingModal" tabindex="-1" aria-labelledby="loadingModalLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-body text-center p-4">
                <div class="spinner-border text-primary mb-3" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p id="loadingText" class="mb-0">Processing...</p>
            </div>
        </div>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
<script>
    $(document).ready(function() {
        // Initialize elements
        const $startSetupBtn = $('#startSetupBtn');
        const $setupInitial = $('#setupInitial');
        const $setupStep1 = $('#setupStep1');
        const $setupStep2 = $('#setupStep2');
        const $continueBtn = $('#continueBtn');
        const $backBtn = $('#backBtn');
        const $verifyForm = $('#verifyForm');
        const $disableForm = $('#disableForm');
        const $loadingModal = $('#loadingModal');
        const $alertContainer = $('#alertContainer');
        const $codeInput = $('#code');

        // Utility functions
        function showLoading(text = 'Processing...') {
            $('#loadingText').text(text);
            $loadingModal.modal('show');
        }

        function hideLoading() {
            $loadingModal.modal('hide');
        }

        function showAlert(message, type = 'danger') {
            const alertHtml = `
            <div class="alert alert-${type} alert-dismissible fade show border-0 shadow-sm" role="alert">
                <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'} me-2"></i>
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        `;
            $alertContainer.html(alertHtml);

            // Scroll to alert
            $('html, body').animate({
                scrollTop: $alertContainer.offset().top - 100
            }, 300);

            // Auto-hide success alerts after 5 seconds
            if (type === 'success') {
                setTimeout(() => {
                    $alertContainer.find('.alert').alert('close');
                }, 5000);
            }
        }

        // Start setup process
        $startSetupBtn.on('click', async function() {

            try {
                const response = await $.ajax({
                    url: '/totp-setup',
                    method: 'POST',
                    data: {
                        action: 'enable'
                    },
                    dataType: 'json'
                });

                if (response.success) {
                    // Display QR code and secret
                    $('#secretKey').val(response.details.secret);
                    $('#csrfToken').val(response.details.csrf_token);

                    // Generate QR code
                    const qrCodeImg = `<img src="https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=${encodeURIComponent(response.details.qrCodeUrl)}" 
                                   alt="TOTP QR Code" class="img-fluid rounded shadow-sm" style="max-width: 200px;">`;

                    $('#qrCodeContainer').html(qrCodeImg);

                    // Show QR code step with animation
                    $setupInitial.fadeOut(300, function() {
                        $setupStep1.fadeIn(300);
                    });
                } else {
                    showAlert(response.message || 'Setup failed. Please try again.');
                }
            } catch (error) {
                console.error('Setup error:', error);
                showAlert('Network error. Please check your connection and try again.');
            }
        });

        // Continue to verification
        $continueBtn.on('click', function() {
            $setupStep1.fadeOut(300, function() {
                $setupStep2.fadeIn(300);
                $codeInput.focus();
            });
        });

        // Back to QR code
        $backBtn.on('click', function() {
            $setupStep2.fadeOut(300, function() {
                $setupStep1.fadeIn(300);
                $codeInput.val('').removeClass('is-valid is-invalid');
            });
        });

        // Handle verification form
        $verifyForm.on('submit', async function(e) {
            e.preventDefault();
            showLoading('Verifying code...');

            try {
                const response = await $.ajax({
                    url: '/totp-setup',
                    method: 'POST',
                    data: $(this).serialize(),
                    dataType: 'json'
                });

                showAlert('Two-factor authentication setup complete!', 'success');
                hideLoading()
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
                    }
                } else {
                    showAlert(response.message || 'Verification failed. Please try again.');
                    $codeInput.val('').removeClass('is-valid').addClass('is-invalid').focus();
                }
            } catch (error) {
                console.error('Verification error:', error);
                showAlert('Network error. Please check your connection and try again.');
            }
        });

        // Handle disable form
        $disableForm.on('submit', async function(e) {
            e.preventDefault();

            const confirmed = await showConfirmDialog(
                'Disable Two-Factor Authentication',
                'Are you sure you want to disable two-factor authentication? This will make your account less secure. You can re-enable it at any time.',
                'Disable',
                'danger'
            );

            if (!confirmed) return;

            showLoading('Disabling 2FA...');

            try {
                const response = await $.ajax({
                    url: '/totp-setup',
                    method: 'POST',
                    data: $(this).serialize(),
                    dataType: 'json'
                });

                if (response.success) {
                    showAlert('Two-factor authentication disabled successfully.', 'success');
                    setTimeout(() => {
                        window.location.reload();
                    }, 2000);
                } else {
                    showAlert(response.message || 'Failed to disable 2FA. Please try again.');
                }
            } catch (error) {
                console.error('Disable error:', error);
                showAlert('Network error. Please check your connection and try again.');
            } finally {
                hideLoading();
            }
        });

        // Auto-format and validate code input
        $codeInput.on('input', function() {
            let value = $(this).val().replace(/\D/g, '');
            $(this).val(value);

            if (value.length === 6) {
                $(this).addClass('is-valid').removeClass('is-invalid');
                // Auto-submit after brief delay
                setTimeout(() => {
                    $verifyForm.submit();
                }, 500);
            } else if (value.length > 0) {
                $(this).removeClass('is-valid is-invalid');
            }
        });

        // Add paste support for code input
        $codeInput.on('paste', function(e) {
            setTimeout(() => {
                let value = $(this).val().replace(/\D/g, '').substring(0, 6);
                $(this).val(value);
                if (value.length === 6) {
                    $(this).addClass('is-valid');
                }
            }, 10);
        });

        // Custom confirm dialog using jQuery
        // Replace the showConfirmDialog function with this updated version
        function showConfirmDialog(title, message, confirmText, type = 'primary') {
            return new Promise((resolve) => {
                const modalId = 'confirmModal-' + Math.random().toString(36).substr(2, 9);
                const modalHtml = `
            <div class="modal fade" id="${modalId}" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">${title}</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <p class="mb-0">${message}</p>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="button" class="btn btn-${type}" id="confirmBtn">${confirmText}</button>
                        </div>
                    </div>
                </div>
            </div>
        `;

                $('body').append(modalHtml);
                const modalElement = document.getElementById(modalId);
                const modal = new bootstrap.Modal(modalElement);

                // Show the modal
                modal.show();

                // Handle confirm button click
                modalElement.querySelector('#confirmBtn').addEventListener('click', function() {
                    modal.hide();
                    resolve(true);
                });

                // Handle modal hidden event
                modalElement.addEventListener('hidden.bs.modal', function() {
                    modal.dispose();
                    modalElement.remove();
                    resolve(false);
                });
            });
        }
    });

    // Copy secret key function
    function copySecret() {
        const $secretKey = $('#secretKey');
        const $copyBtn = $(event.target).closest('button');

        if (navigator.clipboard && window.isSecureContext) {
            navigator.clipboard.writeText($secretKey.val()).then(() => {
                showCopyFeedback($copyBtn);
            }).catch(() => {
                fallbackCopyText($secretKey[0], $copyBtn);
            });
        } else {
            fallbackCopyText($secretKey[0], $copyBtn);
        }
    }

    function fallbackCopyText(element, $copyBtn) {
        element.select();
        element.setSelectionRange(0, 99999);

        try {
            document.execCommand('copy');
            showCopyFeedback($copyBtn);
        } catch (err) {
            alert('Failed to copy. Please select and copy manually.');
        }
    }

    function showCopyFeedback($copyBtn) {
        const originalHtml = $copyBtn.html();
        $copyBtn.html('<i class="fas fa-check"></i> Copied!')
            .removeClass('btn-outline-secondary')
            .addClass('btn-success')
            .prop('disabled', true);

        setTimeout(() => {
            $copyBtn.html(originalHtml)
                .removeClass('btn-success')
                .addClass('btn-outline-secondary')
                .prop('disabled', false);
        }, 1000);
    }
</script>

<?php require base_path('frontend/views/partials/footer.php'); ?>