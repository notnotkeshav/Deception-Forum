/**
 * Admin Management System
 * User suspension, post hiding, audit logs
 */

// User search
document.addEventListener('DOMContentLoaded', () => {
    const searchInput = document.getElementById('user-search');
    if (searchInput) {
        searchInput.addEventListener('input', debounce(searchUsers, 300));
    }

    // Form submissions
    const suspendForm = document.getElementById('suspend-form');
    if (suspendForm) {
        suspendForm.addEventListener('submit', suspendUser);
    }

    const hidePostForm = document.getElementById('hide-post-form');
    if (hidePostForm) {
        hidePostForm.addEventListener('submit', hidePost);
    }

    // Load initial data
    loadSuspendedUsers();
});

function debounce(fn, delay) {
    let timeout;
    return function(...args) {
        clearTimeout(timeout);
        timeout = setTimeout(() => fn(...args), delay);
    };
}

// ==================== USER SUSPENSION ====================

function searchUsers(e) {
    const query = e.target.value.trim();
    const resultsContainer = document.getElementById('user-search-results');

    if (!query) {
        resultsContainer.innerHTML = '';
        document.getElementById('reason-group').style.display = 'none';
        document.getElementById('suspend-btn').style.display = 'none';
        return;
    }

    // Search users (mock endpoint - would be real API)
    fetch(`/api/admin/search-users?q=${encodeURIComponent(query)}`)
        .then(r => r.json())
        .then(data => {
            resultsContainer.innerHTML = '';

            if (!data.users || !data.users.length) {
                resultsContainer.innerHTML = '<p style="color: #777;">No users found</p>';
                return;
            }

            data.users.forEach(user => {
                const item = document.createElement('div');
                item.className = 'user-row';
                item.style.cursor = 'pointer';
                item.innerHTML = `
                    <div class="user-info">
                        <div class="user-name">${user.username}</div>
                        <div class="user-status">
                            ${user.email} •
                            ${user.isSuspended ? '<span class="status-suspended">SUSPENDED</span>' : '<span class="status-active">ACTIVE</span>'}
                        </div>
                    </div>
                    <button type="button" class="btn-admin">
                        ${user.isSuspended ? '🔓 Unsuspend' : '🔒 Suspend'}
                    </button>
                `;

                item.addEventListener('click', () => selectUser(user));
                resultsContainer.appendChild(item);
            });
        })
        .catch(err => {
            console.error('Search error:', err);
            resultsContainer.innerHTML = '<p style="color: #f03;">Search error</p>';
        });
}

function selectUser(user) {
    document.getElementById('selected-user-id').value = user.id;
    document.getElementById('user-search').value = user.username;
    document.getElementById('user-search-results').innerHTML = '';
    document.getElementById('reason-group').style.display = 'block';
    document.getElementById('suspend-btn').style.display = 'block';
    document.getElementById('suspend-btn').textContent = user.isSuspended ? '🔓 Unsuspend User' : '🔒 Suspend User';
}

function suspendUser(e) {
    e.preventDefault();

    const userId = document.getElementById('selected-user-id').value;
    const reason = document.querySelector('textarea[name="reason"]').value;
    const btn = document.getElementById('suspend-btn');
    const isSuspending = btn.textContent.includes('Suspend');

    if (!userId) {
        showAdminAlert('Please select a user', 'error');
        return;
    }

    const formData = new FormData();
    formData.append('csrf_token', csrfToken);
    formData.append('userId', userId);
    formData.append('action', isSuspending ? 'suspend' : 'unsuspend');
    formData.append('reason', reason);

    fetch('/admin/suspend-user', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            showAdminAlert(`User ${isSuspending ? 'suspended' : 'unsuspended'} successfully`, 'success');
            document.getElementById('suspend-form').reset();
            document.getElementById('reason-group').style.display = 'none';
            document.getElementById('suspend-btn').style.display = 'none';
            document.getElementById('user-search-results').innerHTML = '';
            loadSuspendedUsers();
        } else {
            showAdminAlert(data.message || 'Error', 'error');
        }
    })
    .catch(err => {
        showAdminAlert(err.message, 'error');
    });
}

function loadSuspendedUsers() {
    // Load list of currently suspended users
    fetch('/api/admin/suspended-users?limit=20')
        .then(r => r.json())
        .then(data => {
            const list = document.getElementById('suspended-users-list');

            if (!data.users || !data.users.length) {
                list.innerHTML = '<p style="color: #777;">No suspended users</p>';
                return;
            }

            list.innerHTML = data.users.map(user => `
                <div class="user-row">
                    <div class="user-info">
                        <div class="user-name">${user.username}</div>
                        <div class="user-status">
                            Suspended: ${new Date(user.suspendedAt).toLocaleDateString()}
                        </div>
                        ${user.suspendReason ? `<div style="color: #f03; margin-top: 0.3em;">Reason: ${user.suspendReason}</div>` : ''}
                    </div>
                    <button type="button" class="btn-admin" onclick="unsuspendQuick('${user.id}')">
                        🔓 Unsuspend
                    </button>
                </div>
            `).join('');
        });
}

function unsuspendQuick(userId) {
    if (!confirm('Unsuspend this user?')) return;

    const formData = new FormData();
    formData.append('csrf_token', csrfToken);
    formData.append('userId', userId);
    formData.append('action', 'unsuspend');

    fetch('/admin/suspend-user', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            showAdminAlert('User unsuspended', 'success');
            loadSuspendedUsers();
        }
    });
}

// ==================== POST HIDING ====================

function hidePost(e) {
    e.preventDefault();

    const postType = document.querySelector('select[name="postType"]').value;
    const postId = document.querySelector('input[name="postId"]').value;
    const reason = document.querySelector('textarea[name="reason"]').value;

    if (!postType || !postId) {
        showAdminAlert('Post type and ID required', 'error');
        return;
    }

    const formData = new FormData();
    formData.append('csrf_token', csrfToken);
    formData.append('postType', postType);
    formData.append('postId', postId);
    formData.append('action', 'hide');
    formData.append('reason', reason);

    fetch('/admin/hide-post', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            showAdminAlert('Post hidden successfully', 'success');
            document.getElementById('hide-post-form').reset();
        } else {
            showAdminAlert(data.message || 'Error', 'error');
        }
    })
    .catch(err => {
        showAdminAlert(err.message, 'error');
    });
}

// ==================== AUDIT LOGS ====================

function loadAuditLogs(filter = '') {
    const url = `/api/admin/audit-logs?limit=50${filter ? '&action=' + encodeURIComponent(filter) : ''}`;

    fetch(url)
        .then(r => r.json())
        .then(data => {
            const list = document.getElementById('audit-logs-list');

            if (!data.logs || !data.logs.length) {
                list.innerHTML = '<p style="color: #777;">No audit logs</p>';
                return;
            }

            list.innerHTML = data.logs.map(log => `
                <div style="background: #1a1a1a; border-left: 2px solid #ffd700; padding: 1em; margin-bottom: 0.5em; border-radius: 3px;">
                    <div style="color: #ffd700; font-weight: bold;">${log.action}</div>
                    <div style="color: #aaa; font-size: 0.9em; margin-top: 0.2em;">
                        ${new Date(log.createdAt).toLocaleString()}
                    </div>
                    <div style="color: #777; margin-top: 0.3em; font-size: 0.85em;">
                        ${log.details ? JSON.stringify(log.details) : 'No details'}
                    </div>
                </div>
            `).join('');
        });
}

// Search audit logs
const auditSearch = document.getElementById('audit-search');
if (auditSearch) {
    auditSearch.addEventListener('input', debounce(function() {
        loadAuditLogs(this.value);
    }, 300));
}

// Load audit logs on tab switch
document.addEventListener('click', (e) => {
    if (e.target.dataset.panel === 'audit-logs') {
        loadAuditLogs();
    }
});

// Initial load
loadAuditLogs();
