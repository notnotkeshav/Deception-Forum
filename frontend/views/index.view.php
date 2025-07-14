<!-- Header -->
<?php require(base_path("/frontend/views/partials/header.php")); ?>

<!-- Navbar -->
<?php require(base_path("/frontend/views/partials/navbar.php")); ?>

<!-- Security Alert Section (TOTP Enable) -->
<?php if (isset($_SESSION['user']) && !$_SESSION['user']['totp_enabled']): ?>
    <section class="security-alert bg-warning text-dark py-3">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <div class="d-flex align-items-center">
                        <i class="fas fa-shield-alt me-3 fs-4"></i>
                        <div>
                            <h6 class="mb-1 fw-bold">Enhance Your Account Security</h6>
                            <p class="mb-0 small">Enable Two-Factor Authentication (TOTP) to protect your account from unauthorized access.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 text-md-end mt-2 mt-md-0">
                    <button class="btn btn-dark btn-sm" onclick="enableTOTP()">
                        <i class="fas fa-lock me-2"></i>Enable TOTP
                    </button>
                    <button class="btn btn-outline-secondary btn-sm ms-2" onclick="dismissSecurityAlert()">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
        </div>
    </section>
<?php endif; ?>

<!-- Hero Section -->
<section class="header-section fade-in bg-gradient text-dark d-flex align-items-center justify-content-center" style="height: 100vh; background: linear-gradient(45deg, #f8f9fa, #dcdfe2);">
    <div class="container text-center">
        <h1 class="display-3 fw-bold mb-4 text-shadow">Welcome to the Ultimate Hacking Forum</h1>
        <p class="lead mb-4 text-shadow">Dive into discussions on the latest hacking techniques, tools, and security insights in a secure, encrypted space.</p>
        <a href="#topics" class="btn btn-dark btn-lg px-4 py-2 shadow-lg">Join the Conversation</a>
    </div>
</section>

<!-- Featured Topics -->
<section id="topics" class="container my-5 py-5">
    <h2 class="text-center fade-in text-primary mb-5">Featured Topics</h2>
    <div class="row g-4">
        <div class="col-md-4">
            <div class="card fade-in shadow-sm border-0 rounded-3 overflow-hidden">
                <div class="card-body bg-gradient p-4" style="background: linear-gradient(45deg, #ff7e5f, #feb47b);">
                    <h5 class="card-title text-dark fw-bold">Advanced Encryption Techniques</h5>
                    <p class="card-text text-dark">Master encryption algorithms and secure your data against even the most sophisticated attacks.</p>
                    <a href="#" class="btn btn-outline-dark btn-sm">Learn More</a>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card fade-in shadow-sm border-0 rounded-3 overflow-hidden">
                <div class="card-body bg-gradient p-4" style="background: linear-gradient(45deg, #3b8d99, #6a82fb);">
                    <h5 class="card-title text-dark fw-bold">Penetration Testing 101</h5>
                    <p class="card-text text-dark">Explore the fundamentals of ethical hacking, from vulnerability scanning to advanced penetration testing strategies.</p>
                    <a href="#" class="btn btn-outline-dark btn-sm">Learn More</a>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card fade-in shadow-sm border-0 rounded-3 overflow-hidden">
                <div class="card-body bg-gradient p-4" style="background: linear-gradient(45deg, #ff6a00, #ee0979);">
                    <h5 class="card-title text-dark fw-bold">Dark Web Access and Security</h5>
                    <p class="card-text text-dark">Understand how to safely navigate the dark web, avoid traps, and protect your anonymity online.</p>
                    <a href="#" class="btn btn-outline-dark btn-sm">Learn More</a>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Recent Articles -->
<section id="articles" class="container my-5 py-5">
    <h2 class="text-center fade-in text-primary mb-5">Recent Articles</h2>
    <div class="row g-4">
        <div class="col-md-4">
            <div class="card fade-in shadow-sm border-0 rounded-3 overflow-hidden">
                <div class="card-body p-4">
                    <h5 class="card-title text-dark fw-bold">Exploring the World of Zero-Day Exploits</h5>
                    <p class="card-text text-dark">An in-depth analysis of zero-day vulnerabilities, their risks, and how ethical hackers can identify and mitigate them.</p>
                    <a href="#" class="btn btn-outline-dark btn-sm">Read More</a>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card fade-in shadow-sm border-0 rounded-3 overflow-hidden">
                <div class="card-body p-4">
                    <h5 class="card-title text-dark fw-bold">The Future of Artificial Intelligence in Cybersecurity</h5>
                    <p class="card-text text-dark">A look at how AI technologies are transforming the landscape of cybersecurity and the future of automated threat detection.</p>
                    <a href="#" class="btn btn-outline-dark btn-sm">Read More</a>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card fade-in shadow-sm border-0 rounded-3 overflow-hidden">
                <div class="card-body p-4">
                    <h5 class="card-title text-dark fw-bold">Secure Your IoT Devices: Best Practices</h5>
                    <p class="card-text text-dark">A guide on how to secure Internet of Things (IoT) devices, from home automation systems to industrial IoT networks.</p>
                    <a href="#" class="btn btn-outline-dark btn-sm">Read More</a>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Upcoming Webinars -->
<section id="webinars" class="container my-5 py-5">
    <h2 class="text-center fade-in text-primary mb-5">Upcoming Webinars</h2>
    <div class="row g-4">
        <div class="col-md-4">
            <div class="card fade-in shadow-sm border-0 rounded-3 overflow-hidden">
                <div class="card-body p-4">
                    <h5 class="card-title text-dark fw-bold">Ethical Hacking: From Beginner to Pro</h5>
                    <p class="card-text text-dark">Join us for a comprehensive webinar on ethical hacking, perfect for beginners looking to get into the field.</p>
                    <a href="#" class="btn btn-outline-dark btn-sm">Register Now</a>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card fade-in shadow-sm border-0 rounded-3 overflow-hidden">
                <div class="card-body p-4">
                    <h5 class="card-title text-dark fw-bold">Mastering Phishing Attacks</h5>
                    <p class="card-text text-dark">Learn how to identify, prevent, and protect against phishing attacks in this expert-led webinar.</p>
                    <a href="#" class="btn btn-outline-dark btn-sm">Register Now</a>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card fade-in shadow-sm border-0 rounded-3 overflow-hidden">
                <div class="card-body p-4">
                    <h5 class="card-title text-dark fw-bold">Advanced Malware Analysis</h5>
                    <p class="card-text text-dark">Dive into advanced techniques used in malware analysis and reverse engineering in this hands-on session.</p>
                    <a href="#" class="btn btn-outline-dark btn-sm">Register Now</a>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Community Feedback -->
<section id="feedback" class="container my-5 py-5">
    <h2 class="text-center fade-in text-primary mb-5">What Our Community Says</h2>
    <div class="row g-4">
        <div class="col-md-4">
            <div class="card fade-in shadow-sm border-0 rounded-3 overflow-hidden">
                <div class="card-body p-4">
                    <p class="card-text text-dark">"The discussions here are enlightening! I've learned so much about ethical hacking, and I appreciate the wealth of knowledge available."</p>
                    <h5 class="fw-bold text-dark">John D.</h5>
                    <p class="text-muted">Ethical Hacker</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card fade-in shadow-sm border-0 rounded-3 overflow-hidden">
                <div class="card-body p-4">
                    <p class="card-text text-dark">"This forum has helped me advance my skills in penetration testing. The webinars are particularly helpful for hands-on learning!"</p>
                    <h5 class="fw-bold text-dark">Sarah M.</h5>
                    <p class="text-muted">Cybersecurity Specialist</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card fade-in shadow-sm border-0 rounded-3 overflow-hidden">
                <div class="card-body p-4">
                    <p class="card-text text-dark">"The resources and tutorials here are top-notch. Highly recommend this community to anyone serious about security."</p>
                    <h5 class="fw-bold text-dark">Mark T.</h5>
                    <p class="text-muted">Security Consultant</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Footer -->
<footer class="footer bg-light text-dark text-center py-4">
    <p>&copy; 2025 Hacking Forum. All Rights Reserved.</p>
    <p>
        <a href="#" class="text-dark text-decoration-none">Privacy Policy</a> |
        <a href="#" class="text-dark text-decoration-none">Terms of Service</a>
    </p>
</footer>

<script>
    // TOTP Enable Functions
    function enableTOTP() {
        window.location.href = '/totp-setup'
    }

    function dismissSecurityAlert() {
        document.querySelector('.security-alert').style.display = 'none';
        // Set a cookie to remember dismissal
        document.cookie = "totp_alert_dismissed=true; path=/; max-age=86400"; // 24 hours
    }
</script>

<?php require(base_path("/frontend/views/partials/footer.php")); ?>