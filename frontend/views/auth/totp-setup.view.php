<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>⛧ Red Skull Authentication ⛧</title>
    <style>
        @font-face {
            font-family: 'vamp';
            src: url('/public/fonts/ScaryVampire.ttf') format('truetype');
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background: #000;
            color: #fff;
            font-family: 'Courier New', monospace;
            height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .auth-container {
            width: 90%;
            max-width: 800px;
            background: #111;
            border: 2px solid #960d0d;
            border-radius: 0.5rem;
            padding: 2rem;
            box-shadow: 0 0 20px rgba(255, 0, 0, 0.2);
        }

        .auth-header {
            text-align: center;
            margin-bottom: 2rem;
            border-bottom: 1px solid #960d0d;
            padding-bottom: 1rem;
        }

        h1 {
            font-family: 'vamp', sans-serif;
            color: #f03;
            font-size: 2rem;
            letter-spacing: 1px;
        }

        .alert {
            padding: 1rem;
            margin-bottom: 1.5rem;
            border-radius: 0.25rem;
            border: 1px solid;
        }

        .alert-info {
            background: rgba(0, 68, 102, 0.2);
            border-color: #006;
            color: #aaf;
        }

        .alert-warning {
            background: rgba(102, 68, 0, 0.2);
            border-color: #960;
            color: #fd6;
        }

        .alert-success {
            background: rgba(0, 68, 0, 0.2);
            border-color: #060;
            color: #afa;
        }

        .btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 0.25rem;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .btn-primary {
            background: #c40303;
            color: #fff;
        }

        .btn-primary:hover {
            background: #960d0d;
            box-shadow: 0 0 10px rgba(255, 0, 0, 0.5);
        }

        .btn-danger {
            background: #960d0d;
            color: #fff;
        }

        .btn-danger:hover {
            background: #c00;
            box-shadow: 0 0 10px rgba(255, 0, 0, 0.5);
        }

        .setup-steps {
            display: flex;
            margin-bottom: 2rem;
        }

        .step {
            flex: 1;
            text-align: center;
            position: relative;
            padding-bottom: 1rem;
        }

        .step-number {
            width: 2rem;
            height: 2rem;
            background: #333;
            color: #fff;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 0.5rem;
            border: 2px solid #444;
        }

        .step.active .step-number {
            background: #c40303;
            border-color: #f03;
        }

        .step.complete .step-number {
            background: #060;
            border-color: #0f0;
        }

        .step-title {
            font-size: 0.8rem;
            color: #888;
        }

        .step.active .step-title {
            color: #f03;
        }

        .step.complete .step-title {
            color: #0f0;
        }

        .step:not(:last-child)::after {
            content: '';
            position: absolute;
            top: 1rem;
            left: 50%;
            width: 100%;
            height: 2px;
            background: #333;
            z-index: -1;
        }

        .step.active:not(:last-child)::after {
            background: linear-gradient(to right, #f03, #333);
        }

        .step.complete:not(:last-child)::after {
            background: #060;
        }

        .qr-container {
            display: flex;
            gap: 2rem;
            margin-bottom: 2rem;
        }

        .qr-box, .manual-box {
            flex: 1;
            padding: 1.5rem;
            border: 1px solid #333;
            border-radius: 0.5rem;
            background: rgba(0, 0, 0, 0.3);
        }

        .qr-box {
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .qr-code {
            width: 200px;
            height: 200px;
            margin-bottom: 1rem;
            border: 1px solid #333;
            padding: 0.5rem;
            background: #fff;
        }

        .secret-key {
            display: flex;
            gap: 0.5rem;
            margin-bottom: 1rem;
        }

        .secret-key input {
            flex: 1;
            padding: 0.5rem;
            background: #000;
            border: 1px solid #333;
            color: #fff;
            font-family: 'Courier New', monospace;
            text-align: center;
        }

        .secret-key button {
            background: #333;
            color: #fff;
            border: none;
            padding: 0 1rem;
            cursor: pointer;
        }

        .secret-key button:hover {
            background: #444;
        }

        .code-input {
            display: flex;
            justify-content: center;
            margin: 2rem 0;
        }

        .code-input input {
            width: 3rem;
            height: 4rem;
            margin: 0 0.5rem;
            text-align: center;
            font-size: 2rem;
            background: #000;
            border: 1px solid #333;
            color: #fff;
        }

        .code-input input:focus {
            outline: none;
            border-color: #f03;
            box-shadow: 0 0 5px rgba(255, 0, 0, 0.5);
        }

        .action-buttons {
            display: flex;
            justify-content: center;
            gap: 1rem;
            margin-top: 2rem;
        }

        .hidden {
            display: none;
        }

        .password-input {
            margin-bottom: 1rem;
        }

        .password-input input {
            width: 100%;
            padding: 0.75rem;
            background: #000;
            border: 1px solid #333;
            color: #fff;
        }

        .password-input input:focus {
            outline: none;
            border-color: #f03;
        }

        .loading {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100px;
        }

        .loading-spinner {
            width: 3rem;
            height: 3rem;
            border: 4px solid rgba(255, 0, 0, 0.3);
            border-radius: 50%;
            border-top-color: #f03;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }
    </style>
</head>

<body>
    <div class="auth-container">
        <div class="auth-header">
            <h1>⛧ TWO-FACTOR AUTHENTICATION ⛧</h1>
        </div>

        <div id="alertContainer"></div>

        <?php if (!$totpEnabled): ?>
            <!-- Initial setup -->
            <div id="setupInitial">
                <div class="alert alert-info">
                    <h3>SECURE YOUR ACCOUNT</h3>
                    <p>Two-factor authentication adds an extra layer of security to your account. To get started, you'll need an authenticator app like Google Authenticator or Authy.</p>
                    <ul>
                        <li>Protects against password theft</li>
                        <li>Prevents unauthorized access</li>
                        <li>Required for full account privileges</li>
                    </ul>
                </div>

                <div style="text-align: center;">
                    <button id="startSetupBtn" class="btn btn-primary">BEGIN SETUP</button>
                </div>
            </div>

            <!-- QR Code step -->
            <div id="setupStep1" class="hidden">
                <div class="alert alert-warning">
                    <strong>WARNING:</strong> Keep this page open until setup is complete!
                </div>

                <div class="setup-steps">
                    <div class="step active">
                        <div class="step-number">1</div>
                        <div class="step-title">SCAN QR CODE</div>
                    </div>
                    <div class="step">
                        <div class="step-number">2</div>
                        <div class="step-title">VERIFY CODE</div>
                    </div>
                </div>

                <div class="qr-container">
                    <div class="qr-box">
                        <h3>SCAN QR CODE</h3>
                        <div id="qrCodeContainer" class="loading">
                            <div class="loading-spinner"></div>
                        </div>
                        <p>Open your authenticator app and scan this code</p>
                    </div>
                    <div class="manual-box">
                        <h3>MANUAL ENTRY</h3>
                        <p>If you can't scan the QR code, enter this secret key manually:</p>
                        <div class="secret-key">
                            <input type="text" id="secretKey" readonly>
                            <button onclick="copySecret()">COPY</button>
                        </div>
                    </div>
                </div>

                <div class="action-buttons">
                    <button id="continueBtn" class="btn btn-primary">CONTINUE</button>
                </div>
            </div>

            <!-- Verification step -->
            <div id="setupStep2" class="hidden">
                <div class="alert alert-info">
                    <strong>FINAL STEP:</strong> Enter the 6-digit code from your authenticator app
                </div>

                <div class="setup-steps">
                    <div class="step complete">
                        <div class="step-number">✓</div>
                        <div class="step-title">SCAN QR CODE</div>
                    </div>
                    <div class="step active">
                        <div class="step-number">2</div>
                        <div class="step-title">VERIFY CODE</div>
                    </div>
                </div>

                <form id="verifyForm">
                    <input type="hidden" name="action" value="verify-setup">
                    <input type="hidden" name="csrf_token" id="csrfToken" value="">

                    <div class="code-input">
                        <input type="text" maxlength="1" pattern="\d" required>
                        <input type="text" maxlength="1" pattern="\d" required>
                        <input type="text" maxlength="1" pattern="\d" required>
                        <input type="text" maxlength="1" pattern="\d" required>
                        <input type="text" maxlength="1" pattern="\d" required>
                        <input type="text" maxlength="1" pattern="\d" required>
                    </div>

                    <div class="action-buttons">
                        <button type="button" id="backBtn" class="btn">BACK</button>
                        <button type="submit" class="btn btn-primary">VERIFY</button>
                    </div>
                </form>
            </div>
        <?php else: ?>
            <!-- TOTP already enabled -->
            <div class="alert alert-success">
                <h3>TWO-FACTOR AUTHENTICATION ENABLED</h3>
                <p>Your account is protected with two-factor authentication.</p>
            </div>

            <div style="margin-top: 2rem;">
                <h3>MANAGE AUTHENTICATION</h3>
                <form id="disableForm">
                    <input type="hidden" name="action" value="disable">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token'] ?? ''; ?>">

                    <div class="password-input">
                        <label for="password">ENTER YOUR PASSWORD TO DISABLE:</label>
                        <input type="password" id="password" name="password" required>
                    </div>

                    <div class="alert alert-warning">
                        <strong>WARNING:</strong> Disabling two-factor authentication will reduce your account security.
                    </div>

                    <div class="action-buttons">
                        <button type="submit" class="btn btn-danger">DISABLE 2FA</button>
                    </div>
                </form>
            </div>
        <?php endif; ?>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize elements
            const startSetupBtn = document.getElementById('startSetupBtn');
            const setupInitial = document.getElementById('setupInitial');
            const setupStep1 = document.getElementById('setupStep1');
            const setupStep2 = document.getElementById('setupStep2');
            const continueBtn = document.getElementById('continueBtn');
            const backBtn = document.getElementById('backBtn');
            const verifyForm = document.getElementById('verifyForm');
            const disableForm = document.getElementById('disableForm');
            const codeInputs = document.querySelectorAll('.code-input input');
            const alertContainer = document.getElementById('alertContainer');

            // Show alert function
            function showAlert(message, type = 'error') {
                const alert = document.createElement('div');
                alert.className = `alert alert-${type}`;
                alert.innerHTML = message;
                alertContainer.appendChild(alert);
                
                setTimeout(() => {
                    alert.remove();
                }, 5000);
            }

            // Start setup process
            startSetupBtn?.addEventListener('click', async function() {
                try {
                    const response = await fetch('/totp-setup', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: 'action=enable&csrf_token=' + encodeURIComponent('<?php echo $csrf_token; ?>')
                    });
                    
                    const data = await response.json();

                    if (data.success) {
                        // Display QR code and secret
                        document.getElementById('secretKey').value = data.details.secret;
                        document.getElementById('csrfToken').value = data.details.csrf_token;

                        // Generate QR code
                        const qrCodeImg = `<img src="https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=${encodeURIComponent(data.details.qrCodeUrl)}" 
                                   alt="TOTP QR Code" class="qr-code">`;

                        document.getElementById('qrCodeContainer').innerHTML = qrCodeImg;

                        // Show QR code step
                        setupInitial.classList.add('hidden');
                        setupStep1.classList.remove('hidden');
                    } else {
                        showAlert(data.message || 'Setup failed. Please try again.');
                    }
                } catch (error) {
                    console.error('Setup error:', error);
                    showAlert('Network error. Please check your connection and try again.');
                }
            });

            // Continue to verification
            continueBtn?.addEventListener('click', function() {
                setupStep1.classList.add('hidden');
                setupStep2.classList.remove('hidden');
                codeInputs[0].focus();
            });

            // Back to QR code
            backBtn?.addEventListener('click', function() {
                setupStep2.classList.add('hidden');
                setupStep1.classList.remove('hidden');
            });

            // Handle verification form
            verifyForm?.addEventListener('submit', async function(e) {
                e.preventDefault();
                
                // Combine code digits
                let code = '';
                codeInputs.forEach(input => {
                    code += input.value;
                });

                if (code.length !== 6) {
                    showAlert('Please enter a complete 6-digit code');
                    return;
                }

                try {
                    const formData = new FormData(verifyForm);
                    formData.append('code', code);

                    const response = await fetch('/totp-setup', {
                        method: 'POST',
                        body: new URLSearchParams(formData)
                    });
                    
                    const data = await response.json();

                    if (data.success) {
                        showAlert('Two-factor authentication setup complete!', 'success');
                        
                        // Redirect after delay
                        setTimeout(() => {
                            window.location.href = data.details.redirect || '/threads';
                        }, 1000);
                    } else {
                        showAlert(data.message || 'Verification failed. Please try again.');
                        codeInputs.forEach(input => {
                            input.value = '';
                        });
                        codeInputs[0].focus();
                    }
                } catch (error) {
                    console.error('Verification error:', error);
                    showAlert('Network error. Please check your connection and try again.');
                }
            });

            // Handle disable form
            disableForm?.addEventListener('submit', async function(e) {
                e.preventDefault();
                
                if (!confirm('Are you sure you want to disable two-factor authentication? This will make your account less secure.')) {
                    return;
                }

                try {
                    const formData = new FormData(disableForm);
                    
                    const response = await fetch('/totp-setup', {
                        method: 'POST',
                        body: new URLSearchParams(formData)
                    });
                    
                    const data = await response.json();

                    if (data.success) {
                        showAlert('Two-factor authentication disabled successfully.', 'success');
                        setTimeout(() => {
                            window.location.reload();
                        }, 1000);
                    } else {
                        showAlert(data.message || 'Failed to disable 2FA. Please try again.');
                    }
                } catch (error) {
                    console.error('Disable error:', error);
                    showAlert('Network error. Please check your connection and try again.');
                }
            });

            // Handle code input navigation
            codeInputs.forEach((input, index) => {
                // Move to next input on digit entry
                input.addEventListener('input', function() {
                    if (this.value.length === 1 && index < codeInputs.length - 1) {
                        codeInputs[index + 1].focus();
                    }
                });

                // Handle backspace
                input.addEventListener('keydown', function(e) {
                    if (e.key === 'Backspace' && this.value.length === 0 && index > 0) {
                        codeInputs[index - 1].focus();
                    }
                });
            });
        });

        // Copy secret key
        function copySecret() {
            const secretKey = document.getElementById('secretKey');
            secretKey.select();
            document.execCommand('copy');
            
            // Show feedback
            const button = event.target.closest('button');
            button.textContent = 'COPIED!';
            setTimeout(() => {
                button.textContent = 'COPY';
            }, 1000);
        }
    </script>
</body>
</html>