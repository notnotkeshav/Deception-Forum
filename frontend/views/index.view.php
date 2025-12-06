<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome Shadowbreed</title>
<link rel="shortcut icon" href="/public/images/favicon.ico" type="image/x-icon">
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
            font-family: 'Arial', sans-serif;
            background-color: #000;
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
        }

        main {
            width: 100vw;
            height: 100vh;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        #logo-container {
            width: 50vw;
            height: 100vh;
            overflow: hidden;
        }

        img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        #form-container {
            font-family: 'Courier New', monospace;
            display: flex;
            flex-direction: column;
            justify-content: space-around;
            align-items: center;
            height: 100vh;
            padding: 2rem;
            text-align: center;
        }

        form {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 1rem;
            border: 0.125rem solid white;
            border-radius: 0.5rem;
            padding: 1.5rem 2rem;
            width: 90%;
            max-width: 40rem;
        }

        #welcome-msg {
            width: 50vw;
            padding: 0 1.5rem;
            text-align: center;
        }

        .welcome-heading {
            font-size: 3rem;
            font-weight: bolder;
            font-family: 'vamp', sans-serif;
            letter-spacing: 0.1em;
        }

        .welcome-text {
            font-size: 1.2rem;
            font-family: 'Courier New', Courier, monospace;
            color: #fff;
            margin: 1.5rem 0;
        }

        .no-turn-back {
            color: #faa307;
            font-size: 1.5rem;
            margin-top: 0.7rem;
        }

        ul {
            width: max-content;
            margin: 1.2rem auto;
            color: #d00000;
        }

        li {
            text-align: left;
            font-size: 1.5rem;
            font-weight: 700;
        }

        li::marker {
            content: '𖤐 ';
        }

        .last-msg {
            color: #b388eb;
            font-size: 1.4rem;
        }

        .modal-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.8);
            z-index: 1000;
            backdrop-filter: blur(0.2rem);
        }

        .modal-content {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: #1a1a1a;
            border: 0.125rem solid #333;
            border-radius: 0.5rem;
            max-width: 25rem;
            width: 90%;
            color: #fff;
            box-shadow: 0 0 1.25rem rgba(255, 0, 0, 0.3);
        }

        .modal-header {
            padding: 1rem 1.25rem;
            border-bottom: 0.0625rem solid #333;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .modal-header h3 {
            margin: 0;
            color: #ff0000;
            font-size: 1rem;
            text-align: center;
        }

        .close-btn {
            margin-left: 2rem;
            background: none;
            border: none;
            color: #fff;
            font-size: 1.5rem;
            cursor: pointer;
            width: 1.875rem;
            height: 1.875rem;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .close-btn:hover {
            color: #ff0000;
        }

        .modal-body {
            padding: 1.25rem;
            text-align: center;
        }

        .modal-body p {
            margin: 0 0 1rem 0;
            font-size: 0.875rem;
        }

        .modal-loading .modal-content {
            opacity: 0.8;
        }

        .modal-loading .modal-btn {
            background: #666;
            cursor: not-allowed;
        }

        .captcha-container {
            position: relative;
            display: inline-block;
            margin: 1rem 0;
        }

        #captcha-image {
            border: 0.125rem solid #666;
            border-radius: 0.25rem;
            cursor: pointer;
            transition: border-color 0.3s ease;
            max-width: 12.5rem;
            height: 5rem;
        }

        #captcha-image:hover {
            border-color: #ff0000;
        }

        .captcha-refresh {
            position: absolute;
            top: -0.5rem;
            right: -0.5rem;
            background: #333;
            color: #fff;
            border: none;
            border-radius: 50%;
            width: 1.5rem;
            height: 1.5rem;
            cursor: pointer;
            font-size: 0.75rem;
            transition: background-color 0.3s ease;
        }

        .captcha-refresh:hover {
            background: #ff0000;
        }

        #captcha-input {
            width: 100%;
            max-width: 12.5rem;
            padding: 0.5rem 0.75rem;
            margin: 0.625rem 0;
            border: 0.0625rem solid #666;
            border-radius: 0.25rem;
            background: #333;
            color: #fff;
            text-align: center;
            text-transform: uppercase;
            letter-spacing: 0.125rem;
            font-weight: bold;
            font-size: 0.875rem;
        }

        #captcha-input:focus {
            outline: none;
            border-color: #ff0000;
        }

        .captcha-hint {
            font-size: 0.6875rem;
            color: #666;
            margin-top: 0.3125rem;
        }

        .modal-footer {
            padding: 1rem 1.25rem;
            border-top: 0.0625rem solid #333;
            display: flex;
            gap: 0.625rem;
            justify-content: center;
        }

        .modal-btn {
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 0.25rem;
            cursor: pointer;
            font-size: 0.875rem;
            transition: background-color 0.3s ease;
            background: #ff0000;
            color: #fff;
        }

        .modal-btn:hover {
            background: #cc0000;
        }

        .modal-btn.cancel {
            background: #666;
        }

        .modal-btn.cancel:hover {
            background: #555;
        }

        #captcha-message {
            margin: 0.625rem 1.25rem;
            padding: 0.625rem;
            border-radius: 0.25rem;
            font-size: 0.75rem;
            font-weight: bold;
            text-align: center;
            display: none;
        }

        #captcha-message.success {
            background: rgba(0, 255, 0, 0.1);
            color: #00ff00;
            border: 0.0625rem solid #00ff00;
            display: block;
        }

        #captcha-message.error {
            background: rgba(255, 0, 0, 0.1);
            color: #ff0000;
            border: 0.0625rem solid #ff0000;
            display: block;
        }

        #shadowbreed-form {
            background-color: #0a0a0a;
            border: 0.0625rem solid #ff0033;
            padding: 2rem;
            border-radius: 0.5rem;
            box-shadow: 0 0 0.75rem rgba(255, 0, 0, 0.2);
            width: 100%;
            max-width: 30rem;
            font-family: 'Courier New', monospace;
            color: #ffffff;
        }

        #shadowbreed-form label {
            font-weight: bold;
            font-size: 1.5rem;
            color: #ffffff;
        }

        #invite-code {
            background-color: #000000;
            color: #ffffff;
            border: 0.0625rem solid #ff0033;
            padding: 0.8rem;
            font-size: 1rem;
            border-radius: 0.25rem;
            outline: none;
            font-family: 'Courier New', monospace;
            transition: box-shadow 0.3s, border-color 0.3s;
            width: 100%;
        }

        #invite-code:focus {
            border-color: #ffffff;
            box-shadow: 0 0 0.375rem #ff0033;
        }

        #submit-btn {
            background-color: #ff0033;
            color: #ffffff;
            font-weight: bold;
            padding: 0.8rem;
            border: none;
            border-radius: 0.25rem;
            cursor: pointer;
            font-family: 'Courier New', monospace;
            transition: background-color 0.3s, box-shadow 0.3s;
            width: 100%;
        }

        #submit-btn:hover {
            background-color: #cc0022;
            box-shadow: 0 0 0.5rem #ff0033;
        }

        #form-message {
            display: none;
            min-height: 1.5rem;
            color: #ff0033;
            font-style: italic;
            font-size: 0.9rem;
            text-align: center;
        }
    </style>
</head>

<body>
    <main>
        <div id="logo-container">
            <img src="/public/images/logo.svg" alt="logo">
        </div>

        <div id="form-container">
            <div id="welcome-msg">
                <p class="welcome-heading">
                    ⛧ WELCOME, SHADOWBREED ⛧
                </p>
                <p class="welcome-text">
                    <strong>
                        You stand at the gate of the Thirteen Veins.
                        <br>
                        This domain does not exist.
                        <br>
                        Only those marked by the Red Skull may pass.
                        <br>
                        Input your Invite Cipher to proceed.
                    </strong>
                </p>
                <p class="no-turn-back">
                    If you do not possess one, turn back. <br>This is not your dimension
                </p>
                <br>
                <ul>
                    <li>No logs.</li>
                    <li>No resets.</li>
                    <li>One identity</li>
                </ul>
                <br>

                <p class="last-msg">
                    Unauthorized attempts will be remembered.
                </p>
            </div>

            <form id="shadowbreed-form" action="/signup" method="get">
                <label for="invite-code">Enter Invite Code:</label>
                <input type="text" name="invite-code" placeholder="Enter invitecode" id="invite-code" required>

                <button type="submit" id="submit-btn">Continue to Register</button>

                <!-- Message area -->
                <div id="form-message"></div>
            </form>
        </div>

        <!-- CAPTCHA Modal -->
        <div id="captcha-modal" class="modal-overlay">
            <div class="modal-content">
                <div class="modal-header">
                    <h3>⛧ PROVE YOUR MORTALITY ⛧</h3>
                    <button type="button" class="close-btn" onclick="closeCaptchaModal()">&times;</button>
                </div>
                <div class="modal-body">
                    <p>The void demands proof you are not a digital wraith:</p>
                    <div class="captcha-container">
                        <img id="captcha-image"
                            src=""
                            alt="CAPTCHA Challenge"
                            onclick="refreshCaptcha()"
                            title="Click to refresh">
                        <button type="button" class="captcha-refresh" onclick="refreshCaptcha()">
                            <span>⟲</span>
                        </button>
                    </div>
                    <input type="text"
                        id="captcha-input"
                        placeholder="Enter the cipher"
                        autocomplete="off"
                        maxlength="10">
                    <div class="captcha-hint">Click image to refresh if unclear</div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="modal-btn" onclick="verifyCaptcha()">Verify & Continue</button>
                    <button type="button" class="modal-btn cancel" onclick="closeCaptchaModal()">Cancel</button>
                </div>
                <div id="captcha-message"></div>
            </div>
        </div>
    </main>

    <script>
        let inviteCodeValue = '';

        // Handle form submission
        document.getElementById('shadowbreed-form').addEventListener('submit', function(e) {
            e.preventDefault();

            const inviteInput = document.getElementById('invite-code');
            const messageDiv = document.getElementById('form-message');

            // Clear previous messages
            messageDiv.style.display = 'none';

            // Validate invite code
            if (!inviteInput.value.trim()) {
                showFormMessage('Enter your Invite Cipher, shadowbreed.', 'error');
                inviteInput.focus();
                return;
            }

            // Store invite code and show CAPTCHA modal
            inviteCodeValue = inviteInput.value.trim();
            openCaptchaModal();
        });

        // Open CAPTCHA modal
        function openCaptchaModal() {
            const modal = document.getElementById('captcha-modal');
            const captchaInput = document.getElementById('captcha-input');

            modal.style.display = 'block';

            // Load CAPTCHA image
            refreshCaptcha();

            // Focus on input after a short delay
            setTimeout(() => {
                captchaInput.focus();
            }, 300);
        }

        // Close CAPTCHA modal
        function closeCaptchaModal() {
            const modal = document.getElementById('captcha-modal');
            const captchaInput = document.getElementById('captcha-input');
            const messageDiv = document.getElementById('captcha-message');

            modal.style.display = 'none';
            captchaInput.value = '';
            messageDiv.style.display = 'none';
        }

        // Refresh CAPTCHA
        function refreshCaptcha() {
            const img = document.getElementById('captcha-image');
            const input = document.getElementById('captcha-input');
            const messageDiv = document.getElementById('captcha-message');

            // Clear input and messages
            input.value = '';
            messageDiv.style.display = 'none';

            // Load new CAPTCHA with timestamp to prevent caching
            img.src = '/captcha?' + new Date().getTime();

            // Handle image load errors (GD extension not available)
            img.onerror = function() {
                showCaptchaMessage('CAPTCHA service unavailable. Enable GD extension in PHP.', 'error');
            };
        }

        // Verify CAPTCHA
        async function verifyCaptcha() {
            const captchaInput = document.getElementById('captcha-input');
            const modal = document.getElementById('captcha-modal');
            const verifyBtn = modal.querySelector('.modal-btn:not(.cancel)');

            if (!captchaInput.value.trim()) {
                showCaptchaMessage('Enter the cipher to proceed.', 'error');
                captchaInput.focus();
                return;
            }

            // Show loading state
            modal.classList.add('modal-loading');
            verifyBtn.textContent = 'Verifying...';

            try {
                const formData = new FormData();
                formData.append('captcha', captchaInput.value);

                const response = await fetch('/captcha', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.success) {
                    showCaptchaMessage('Challenge accepted. Entering the void...', 'success');

                    setTimeout(() => {
                        // Redirect to signup with invite code
                        window.location.href = `/signup?invite=${encodeURIComponent(inviteCodeValue)}`;
                    }, 1500);

                } else {
                    showCaptchaMessage(result.message || 'The cipher was rejected. Try again.', 'error');
                    setTimeout(refreshCaptcha, 1500);
                }

            } catch (error) {
                console.error('CAPTCHA verification error:', error);
                showCaptchaMessage('The void consumed your attempt. Try again.', 'error');
                setTimeout(refreshCaptcha, 1500);
            } finally {
                modal.classList.remove('modal-loading');
                verifyBtn.textContent = 'Verify & Continue';
            }
        }

        // Show CAPTCHA message
        function showCaptchaMessage(text, type) {
            const messageDiv = document.getElementById('captcha-message');
            messageDiv.textContent = text;
            messageDiv.className = type;
            messageDiv.style.display = 'block';
        }

        // Show form message
        function showFormMessage(text, type) {
            const messageDiv = document.getElementById('form-message');
            messageDiv.textContent = text;
            messageDiv.className = type;
            messageDiv.style.display = 'block';
        }

        // Auto-uppercase CAPTCHA input
        document.getElementById('captcha-input').addEventListener('input', function(e) {
            this.value = this.value.toUpperCase();
        });

        // Handle Enter key in CAPTCHA input
        document.getElementById('captcha-input').addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                verifyCaptcha();
            }
        });

        // Handle Enter key on CAPTCHA image
        document.getElementById('captcha-image').addEventListener('keydown', function(e) {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                refreshCaptcha();
            }
        });

        // Close modal on Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeCaptchaModal();
            }
        });

        // Close modal when clicking outside
        document.getElementById('captcha-modal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeCaptchaModal();
            }
        });

        // Auto-focus on invite code input when page loads
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('invite-code').focus();
        });
    </script>
</body>

</html>
