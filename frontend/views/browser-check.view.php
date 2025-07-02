<?php
require(base_path("/frontend/views/partials/header.php"));
?>

<div class="container-fluid min-vh-100 d-flex align-items-center justify-content-center bg-dark">
    <div class="row justify-content-center w-100">
        <div class="col-12 col-md-8 col-lg-6 col-xl-5">
            <!-- Main Warning Card -->
            <div class="card bg-dark border-danger shadow-lg">
                <div class="card-header bg-danger text-white text-center py-3">
                    <i class="fas fa-exclamation-triangle fa-2x mb-2"></i>
                    <h3 class="mb-0 fw-bold">ACCESS DENIED</h3>
                </div>
                
                <div class="card-body p-4">
                    <!-- Terminal-style header -->
                    <div class="bg-black rounded p-3 mb-4 border border-secondary">
                        <div class="d-flex align-items-center mb-2">
                            <span class="badge bg-success rounded-pill me-2"></span>
                            <span class="badge bg-warning rounded-pill me-2"></span>
                            <span class="badge bg-danger rounded-pill me-2"></span>
                            <small class="text-white ms-2">security_check.sh</small>
                        </div>
                        <code class="text-success">
                            <span class="text-primary">root@hackforum:~$</span> browser_validation --strict<br>
                            <span class="text-warning">[!]</span> <span class="text-danger">INCOMPATIBLE BROWSER DETECTED</span><br>
                            <span class="text-warning">[!]</span> <span class="text-info">Security protocols require Firefox desktop client</span>
                        </code>
                    </div>

                    <!-- Main message -->
                    <div class="text-center mb-4">
                        <i class="fab fa-firefox-browser text-warning fa-4x mb-3"></i>
                        <h4 class="text-white mb-3">Firefox Desktop Required</h4>
                        <p class="text-light mb-3">
                            This secure platform requires <strong class="text-warning">Firefox on desktop</strong> 
                            for enhanced security protocols and proper functionality.
                        </p>
                    </div>

                    <!-- Features list -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <h6 class="text-info mb-3">
                                <i class="fas fa-shield-alt me-2"></i>Security Features Enabled:
                            </h6>
                            <ul class="list-unstyled">
                                <li class="text-light mb-2">
                                    <i class="fas fa-check text-success me-2"></i>
                                    Advanced encryption protocols
                                </li>
                                <li class="text-light mb-2">
                                    <i class="fas fa-check text-success me-2"></i>
                                    Enhanced privacy protection
                                </li>
                                <li class="text-light mb-2">
                                    <i class="fas fa-check text-success me-2"></i>
                                    Secure communication channels
                                </li>
                                <li class="text-light">
                                    <i class="fas fa-check text-success me-2"></i>
                                    Anti-fingerprinting measures
                                </li>
                            </ul>
                        </div>
                    </div>

                    <!-- Action buttons -->
                    <div class="d-grid gap-2">
                        <a href="https://www.mozilla.org/firefox/" 
                           class="btn btn-warning btn-lg fw-bold" 
                           target="_blank" 
                           rel="noopener noreferrer">
                            <i class="fas fa-download me-2"></i>Download Firefox
                        </a>
                        <button onclick="location.reload()" class="btn btn-outline-info">
                            <i class="fas fa-sync-alt me-2"></i>Recheck Browser
                        </button>
                    </div>
                </div>

                <!-- Footer with additional info -->
                <div class="card-footer bg-secondary text-center py-2">
                    <small class="text-muted">
                        <i class="fas fa-info-circle me-1"></i>
                        Mobile browsers and other desktop browsers are not supported
                    </small>
                </div>
            </div>

            <!-- Additional warning badges -->
            <div class="text-center mt-4">
                <span class="badge bg-dark border border-danger text-danger me-2 p-2">
                    <i class="fas fa-ban me-1"></i>Chrome Blocked
                </span>
                <span class="badge bg-dark border border-danger text-danger me-2 p-2">
                    <i class="fas fa-ban me-1"></i>Safari Blocked
                </span>
                <span class="badge bg-dark border border-danger text-danger p-2">
                    <i class="fas fa-ban me-1"></i>Mobile Blocked
                </span>
            </div>

            <!-- Animated matrix-style background effect -->
            <div class="position-fixed top-0 start-0 w-100 h-100" style="z-index: -1; opacity: 0.1;">
                <div class="matrix-bg"></div>
            </div>
        </div>
    </div>
</div>

<style>
/* Custom dark theme enhancements */
.bg-dark {
    background-color: #0d1117 !important;
}

.card.bg-dark {
    background-color: #161b22 !important;
    border: 1px solid #30363d;
}

.matrix-bg {
    background: linear-gradient(45deg, 
        rgba(0, 255, 0, 0.1) 0%, 
        rgba(0, 255, 0, 0.05) 25%, 
        transparent 50%, 
        rgba(0, 255, 0, 0.05) 75%, 
        rgba(0, 255, 0, 0.1) 100%);
    height: 100vh;
    animation: matrix-scroll 20s linear infinite;
}

@keyframes matrix-scroll {
    0% { transform: translateY(-100%); }
    100% { transform: translateY(100%); }
}

/* Glowing effects */
.btn-warning:hover {
    box-shadow: 0 0 20px rgba(255, 193, 7, 0.5);
    transform: translateY(-2px);
    transition: all 0.3s ease;
}

.card {
    transition: transform 0.3s ease;
}

.card:hover {
    transform: translateY(-5px);
}

/* Terminal-style code block */
code {
    font-family: 'Courier New', monospace;
    font-size: 0.85rem;
    line-height: 1.4;
}

/* Pulsing animation for warning icon */
.fa-exclamation-triangle {
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0% { opacity: 1; }
    50% { opacity: 0.7; }
    100% { opacity: 1; }
}

/* Custom scrollbar for dark theme */
::-webkit-scrollbar {
    width: 8px;
}

::-webkit-scrollbar-track {
    background: #161b22;
}

::-webkit-scrollbar-thumb {
    background: #30363d;
    border-radius: 4px;
}

::-webkit-scrollbar-thumb:hover {
    background: #484f58;
}
</style>

<?php require(base_path("/frontend/views/partials/footer.php")); ?>