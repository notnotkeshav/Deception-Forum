<?php require_once view_path('partials/header.php'); ?>
<?php require_once view_path('partials/navbar.php'); ?>

<div class="container mt-4">
    <div class="row">
        <div class="col-md-8 offset-md-2">
            <div class="card">
                <div class="card-header">
                    <h4><?= htmlspecialchars($heading) ?></h4>
                </div>
                <div class="card-body">
                    <?php if (isset($_SESSION['flash']['success'])): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <?= htmlspecialchars($_SESSION['flash']['success']) ?>
                            <button type="button" class="close" data-dismiss="alert">
                                <span>&times;</span>
                            </button>
                        </div>
                        <?php unset($_SESSION['flash']['success']); ?>
                    <?php endif; ?>

                    <?php if (isset($_SESSION['flash']['error'])): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <?= htmlspecialchars($_SESSION['flash']['error']) ?>
                            <button type="button" class="close" data-dismiss="alert">
                                <span>&times;</span>
                            </button>
                        </div>
                        <?php unset($_SESSION['flash']['error']); ?>
                    <?php endif; ?>

                    <form method="POST" action="/notifications/settings">
                        <div class="form-group">
                            <p class="text-muted mb-3">Choose which notifications you want to receive:</p>
                            
                            <div class="custom-control custom-switch mb-3">
                                <input type="checkbox" class="custom-control-input" id="thread_comment" 
                                       name="thread_comment" <?= $settings['thread_comment'] ? 'checked' : '' ?>>
                                <label class="custom-control-label" for="thread_comment">
                                    <strong>Comments on your threads</strong>
                                    <br><small class="text-muted">Get notified when someone comments on your threads</small>
                                </label>
                            </div>

                            <div class="custom-control custom-switch mb-3">
                                <input type="checkbox" class="custom-control-input" id="comment_reply" 
                                       name="comment_reply" <?= $settings['comment_reply'] ? 'checked' : '' ?>>
                                <label class="custom-control-label" for="comment_reply">
                                    <strong>Replies to your comments</strong>
                                    <br><small class="text-muted">Get notified when someone replies to your comments</small>
                                </label>
                            </div>

                            <div class="custom-control custom-switch mb-3">
                                <input type="checkbox" class="custom-control-input" id="thread_vote" 
                                       name="thread_vote" <?= $settings['thread_vote'] ? 'checked' : '' ?>>
                                <label class="custom-control-label" for="thread_vote">
                                    <strong>Votes on your threads</strong>
                                    <br><small class="text-muted">Get notified when someone upvotes your threads</small>
                                </label>
                            </div>

                            <div class="custom-control custom-switch mb-3">
                                <input type="checkbox" class="custom-control-input" id="comment_vote" 
                                       name="comment_vote" <?= $settings['comment_vote'] ? 'checked' : '' ?>>
                                <label class="custom-control-label" for="comment_vote">
                                    <strong>Votes on your comments</strong>
                                    <br><small class="text-muted">Get notified when someone upvotes your comments</small>
                                </label>
                            </div>

                            <div class="custom-control custom-switch mb-3">
                                <input type="checkbox" class="custom-control-input" id="new_thread" 
                                       name="new_thread" <?= $settings['new_thread'] ? 'checked' : '' ?>>
                                <label class="custom-control-label" for="new_thread">
                                    <strong>New threads</strong>
                                    <br><small class="text-muted">Get notified when new threads are posted (can be noisy)</small>
                                </label>
                            </div>

                            <div class="custom-control custom-switch mb-3">
                                <input type="checkbox" class="custom-control-input" id="mention" 
                                       name="mention" <?= $settings['mention'] ? 'checked' : '' ?>>
                                <label class="custom-control-label" for="mention">
                                    <strong>Mentions</strong>
                                    <br><small class="text-muted">Get notified when someone mentions you</small>
                                </label>
                            </div>

                            <div class="custom-control custom-switch mb-3">
                                <input type="checkbox" class="custom-control-input" id="system" 
                                       name="system" <?= $settings['system'] ? 'checked' : '' ?>>
                                <label class="custom-control-label" for="system">
                                    <strong>System notifications</strong>
                                    <br><small class="text-muted">Get notified about important system announcements</small>
                                </label>
                            </div>
                        </div>

                        <div class="form-group">
                            <button type="submit" class="btn btn-primary">Save Settings</button>
                            <a href="/notifications" class="btn btn-secondary ml-2">Back to Notifications</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Enable instant toggle updates via AJAX
    const toggles = document.querySelectorAll('.custom-control-input');
    
    toggles.forEach(toggle => {
        toggle.addEventListener('change', function() {
            const setting = this.name;
            const enabled = this.checked;
            
            // Show loading state
            this.disabled = true;
            
            fetch('/notifications/settings', {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    setting: setting,
                    enabled: enabled
                })
            })
            .then(response => response.json())
            .then(data => {
                if (!data.success) {
                    // Revert the toggle if update failed
                    this.checked = !enabled;
                    alert('Failed to update setting: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                // Revert the toggle if request failed
                this.checked = !enabled;
                alert('An error occurred while updating the setting');
            })
            .finally(() => {
                this.disabled = false;
            });
        });
    });
});
</script>

<?php require_once view_path('partials/footer.php'); ?>
