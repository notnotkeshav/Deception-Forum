<?php
/**
 * User Profile Actions
 * Display block button and other user actions
 *
 * Expected variables:
 * - $profileUser (array): the user being viewed
 * - $currentUserId (string): current logged-in user ID
 * - $isBlocked (bool): whether current user has blocked this user
 * - $csrfToken (string): CSRF token
 */

if ($currentUserId === $profileUser['id']) {
    return; // Don't show actions for own profile
}
?>

<div class="user-actions-section" style="margin-top: 1.5em; padding-top: 1.5em; border-top: 1.5px solid #333;">
    <div style="color: #f03; font-size: 0.95em; font-weight: bold; margin-bottom: 1em;">⚠️ USER ACTIONS</div>

    <div class="user-actions" style="display: flex; gap: 1em; flex-wrap: wrap;">
        <!-- Block/Unblock Button -->
        <button class="action-btn action-btn-delete block-user-btn"
                data-user-id="<?= htmlspecialchars($profileUser['id']) ?>"
                id="block-btn-<?= htmlspecialchars($profileUser['id']) ?>"
                style="width: auto; padding: 0.5em 1.2em;">
            <?php if ($isBlocked): ?>
                ✓ BLOCKED — Click to Unblock
            <?php else: ?>
                🚫 BLOCK USER
            <?php endif; ?>
        </button>

        <!-- Report User Button (future) -->
        <button class="action-btn" style="border-color: #ff6b6b; color: #ff6b6b; width: auto; padding: 0.5em 1.2em;" disabled title="Report feature coming soon">
            🚨 REPORT
        </button>
    </div>

    <div style="color: #777; font-size: 0.85em; margin-top: 0.8em;">
        <?php if ($isBlocked): ?>
            <em>You have blocked this user. Their messages and activity are hidden from your view.</em>
        <?php else: ?>
            <em>Click to block this user. Their future messages will be hidden from your view.</em>
        <?php endif; ?>
    </div>
</div>

<script>
// Update block button state after action
document.addEventListener('DOMContentLoaded', function() {
    const blockBtn = document.getElementById('block-btn-<?= htmlspecialchars($profileUser['id']) ?>');
    if (blockBtn) {
        blockBtn.addEventListener('click', function(e) {
            const userId = this.dataset.userId;
            const isCurrentlyBlocked = this.textContent.includes('BLOCKED');

            // Call messageFeatures method
            if (isCurrentlyBlocked) {
                window.messageFeatures?.unblockUser(userId);
            } else {
                window.messageFeatures?.blockUser(userId);
            }

            // Update button text after action
            setTimeout(() => {
                if (isCurrentlyBlocked) {
                    this.innerHTML = '🚫 BLOCK USER';
                } else {
                    this.innerHTML = '✓ BLOCKED — Click to Unblock';
                }
            }, 500);
        });
    }
});
</script>
