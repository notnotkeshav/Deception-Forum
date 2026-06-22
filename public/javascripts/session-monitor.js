(function () {
  'use strict';

  let SESSION_LIFETIME = 150 * 60 * 1000; // 150 minutes
  const WARNING_INTERVALS = [30, 15, 10, 5]; // minutes remaining
  const CHECK_INTERVAL = 60 * 1000;

  let sessionStartTime = null;
  let lastWarningShown = Infinity;

  function init() {
    if (!document.body.dataset.authenticated) return;

    fetchSessionStatus();
    setInterval(checkSessionStatus, CHECK_INTERVAL);
  }

  function fetchSessionStatus() {
    fetch('/session/check', { credentials: 'same-origin' })
      .then(res => res.json())
      .then(data => {
        if (data.success) {
          sessionStartTime = data.details.session_started * 1000;
          if (data.details?.session_lifetime) {
            SESSION_LIFETIME = data.details.session_lifetime * 1000;
          }
        }
      })
      .catch(err => console.error('Session check failed:', err));
  }

  function checkSessionStatus() {
    if (!sessionStartTime) return;

    const elapsed = Date.now() - sessionStartTime;
    const remaining = SESSION_LIFETIME - elapsed;
    const minutesRemaining = Math.floor(remaining / 60000);
    const minutesElapsed = Math.floor(elapsed / 60000);

    // Warn when minutes remaining crosses a threshold
    for (let threshold of WARNING_INTERVALS) {
      if (minutesRemaining <= threshold && lastWarningShown > threshold) {
        lastWarningShown = threshold;
        alert(`⚠️ Session Warning\n\nYour session will expire in ${minutesRemaining} minutes.\n\nAfter expiry, you'll need to re-verify with TOTP.`);
        break;
      }
    }
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
