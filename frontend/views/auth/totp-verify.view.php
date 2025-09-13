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
            max-width: 500px;
            background: #111;
            border: 2px solid #960d0d;
            border-radius: 0.5rem;
            padding: 2rem;
            box-shadow: 0 0 20px rgba(255, 0, 0, 0.2);
            text-align: center;
        }

        .auth-header {
            margin-bottom: 2rem;
            border-bottom: 1px solid #960d0d;
            padding-bottom: 1rem;
        }

        h1 {
            font-family: 'vamp', sans-serif;
            color: #f03;
            font-size: 2rem;
            letter-spacing: 1px;
            margin-bottom: 0.5rem;
        }

        .auth-message {
            color: #aaa;
            margin-bottom: 2rem;
            font-size: 0.9rem;
        }

        .code-input {
            display: flex;
            justify-content: center;
            gap: 0.5rem;
            margin-bottom: 2rem;
        }

        .code-input input {
            width: 3rem;
            height: 4rem;
            text-align: center;
            font-size: 2rem;
            background: #000;
            border: 1px solid #333;
            color: #fff;
            font-family: 'Courier New', monospace;
        }

        .code-input input:focus {
            outline: none;
            border-color: #f03;
            box-shadow: 0 0 5px rgba(255, 0, 0, 0.5);
        }

        .btn {
            padding: 0.75rem 2rem;
            border: none;
            border-radius: 0.25rem;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s;
            text-transform: uppercase;
            letter-spacing: 1px;
            background: #c40303;
            color: #fff;
            font-family: 'Courier New', monospace;
            width: 100%;
            max-width: 200px;
        }

        .btn:hover {
            background: #960d0d;
            box-shadow: 0 0 10px rgba(255, 0, 0, 0.5);
        }

        .btn:disabled {
            background: #333;
            cursor: not-allowed;
        }

        .spinner {
            display: inline-block;
            width: 1rem;
            height: 1rem;
            border: 2px solid rgba(255, 0, 0, 0.3);
            border-radius: 50%;
            border-top-color: #f03;
            animation: spin 1s linear infinite;
            margin-right: 0.5rem;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        .message {
            margin-top: 1rem;
            padding: 0.75rem;
            border-radius: 0.25rem;
            font-weight: bold;
        }

        .error {
            background: rgba(255, 0, 0, 0.1);
            border: 1px solid #f00;
            color: #f00;
        }

        .success {
            background: rgba(0, 255, 0, 0.1);
            border: 1px solid #0f0;
            color: #0f0;
        }
    </style>
</head>

<body>
    <div class="auth-container">
        <div class="auth-header">
            <h1>⛧ VERIFY IDENTITY ⛧</h1>
            <p class="auth-message">Enter the 6-digit code from your authenticator app</p>
        </div>

        <form id="totpForm">
            <input type="hidden" name="action" value="verify-login">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token ?? '') ?>">


            <div class="code-input">
                <input type="text" maxlength="1" pattern="\d" required>
                <input type="text" maxlength="1" pattern="\d" required>
                <input type="text" maxlength="1" pattern="\d" required>
                <input type="text" maxlength="1" pattern="\d" required>
                <input type="text" maxlength="1" pattern="\d" required>
                <input type="text" maxlength="1" pattern="\d" required>
            </div>

            <button type="submit" id="verifyBtn" class="btn">VERIFY</button>
        </form>

        <div id="error-block" class="message error" style="display: none;"></div>
        <div id="success-block" class="message success" style="display: none;"></div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('totpForm');
            const codeInputs = document.querySelectorAll('.code-input input');
            const verifyBtn = document.getElementById('verifyBtn');
            const errorBlock = document.getElementById('error-block');
            const successBlock = document.getElementById('success-block');

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

            // Form submission
            form.addEventListener('submit', async function(e) {
                e.preventDefault();

                // Clear previous messages
                errorBlock.style.display = 'none';
                successBlock.style.display = 'none';

                // Combine code digits
                let code = '';
                codeInputs.forEach(input => {
                    code += input.value;
                });

                if (code.length !== 6) {
                    showError('Please enter a complete 6-digit code');
                    return;
                }

                // Show loading state
                const originalBtnText = verifyBtn.innerHTML;
                verifyBtn.disabled = true;
                verifyBtn.innerHTML = '<span class="spinner"></span> VERIFYING';

                try {
                    const formData = new FormData(form);
                    formData.append('code', code);

                    const response = await fetch('/verify-totp', {
                        method: 'POST',
                        body: new URLSearchParams(formData)
                    });

                    const data = await response.json();

                    if (data.success) {
                        if (data.details?.session) {
                            // Store session data
                            sessionStorage.setItem('token', data.details.session.token);
                            sessionStorage.setItem('userId', data.details.session.userId);
                            sessionStorage.setItem('user', JSON.stringify(data.details.session.user));

                            if (data.details.session.moderator !== undefined) {
                                sessionStorage.setItem('moderator', data.details.session.moderator);
                            }

                            // Show success and redirect
                            showSuccess('Verification successful! Redirecting...');
                            
                            // Determine redirect URL
                            let redirectUrl = '/threads'; // Default
                            const urlParams = new URLSearchParams(window.location.search);
                            const returnTo = urlParams.get('returnTo');
                            if (returnTo) {
                                redirectUrl = decodeURIComponent(returnTo);
                            }
                            if (data.details.redirect) {
                                redirectUrl = data.details.redirect;
                            }

                            setTimeout(() => {
                                window.location.href = redirectUrl;
                            }, 1000);
                        } else {
                            showSuccess(data.message || 'Verification successful!');
                        }
                    } else {
                        showError(data.message || 'Verification failed');
                        // Reset the code input for retry
                        codeInputs.forEach(input => input.value = '');
                        codeInputs[0].focus();
                    }
                } catch (error) {
                    console.error('TOTP verification error:', error);
                    showError('Verification failed. Please try again.');
                } finally {
                    // Restore button state
                    verifyBtn.disabled = false;
                    verifyBtn.innerHTML = originalBtnText;
                }
            });

            function showError(message) {
                errorBlock.textContent = message;
                errorBlock.style.display = 'block';
            }

            function showSuccess(message) {
                successBlock.textContent = message;
                successBlock.style.display = 'block';
            }
        });
    </script>
</body>
</html>