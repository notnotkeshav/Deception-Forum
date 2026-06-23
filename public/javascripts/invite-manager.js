/**
 * Invite Management System
 * Superadmin - Create, view, revoke invites
 */

let inviteOffset = 0;
let inviteLimit = 20;

// ==================== STATISTICS ====================

function loadStats() {
    fetch('/admin/invites')
        .then(r => r.json())
        .then(data => {
            if (!data.success) return;

            const stats = data.details.stats;
            document.getElementById('stat-total').textContent = stats.total || '0';
            document.getElementById('stat-available').textContent = stats.available || '0';
            document.getElementById('stat-used').textContent = stats.used || '0';
            document.getElementById('stat-revoked').textContent = stats.revoked || '0';
            document.getElementById('stat-expired').textContent = stats.expired || '0';
        });
}

// ==================== CREATE INVITES ====================

function attachFormHandlers() {
    // Single invite form
    const singleForm = document.getElementById('create-single-form');
    if (singleForm) {
        singleForm.addEventListener('submit', (e) => {
            e.preventDefault();

            const expirationDays = parseInt(singleForm.querySelector('input[name="expirationDays"]').value);
            const formData = new FormData();
            formData.append('csrf_token', csrfToken);
            formData.append('action', 'single');
            formData.append('expirationDays', expirationDays);

            fetch('/admin/invites', {
                method: 'POST',
                body: formData
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    const invite = data.details.invite;
                    displaySingleInvite(invite);
                    showAlert('Invite created successfully!', 'success');
                    loadStats();
                } else {
                    showAlert(data.message || 'Error', 'error');
                }
            });
        });
    }

    // Bulk invite form
    const bulkForm = document.getElementById('create-bulk-form');
    if (bulkForm) {
        bulkForm.addEventListener('submit', (e) => {
            e.preventDefault();

            const count = parseInt(bulkForm.querySelector('input[name="count"]').value);
            const expirationDays = parseInt(bulkForm.querySelector('input[name="expirationDays"]').value);
            const batchName = bulkForm.querySelector('input[name="batchName"]').value;

            const formData = new FormData();
            formData.append('csrf_token', csrfToken);
            formData.append('action', 'bulk');
            formData.append('count', count);
            formData.append('expirationDays', expirationDays);
            formData.append('batchName', batchName);

            fetch('/admin/invites', {
                method: 'POST',
                body: formData
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    const batch = data.details.batch;
                    displayBulkInvites(batch);
                    showAlert(`Created ${count} invites successfully!`, 'success');
                    loadStats();
                    loadBatches();
                } else {
                    showAlert(data.message || 'Error', 'error');
                }
            });
        });
    }

    // Search in view panel
    const searchInput = document.getElementById('invite-search');
    if (searchInput) {
        searchInput.addEventListener('input', debounce(function() {
            const query = this.value.trim();
            if (query) {
                searchInvites(query);
            } else {
                loadInvites();
            }
        }, 300));
    }
}

function displaySingleInvite(invite) {
    const resultDiv = document.getElementById('single-result');
    resultDiv.innerHTML = `
        <div style="background: #1a3a1a; border: 1px solid #0a0; border-radius: 3px; padding: 1.5em; text-align: center;">
            <div style="font-size: 0.9em; color: #aaa; margin-bottom: 0.5em;">Your new invite code:</div>
            <div style="font-size: 1.5em; font-family: monospace; color: #ffd700; font-weight: bold; margin: 0.5em 0; word-break: break-all;">
                ${invite.code}
            </div>
            <div style="color: #aaa; font-size: 0.9em; margin-top: 1em;">
                ${invite.expiresAt ? `Expires: ${new Date(invite.expiresAt).toLocaleDateString()}` : 'Never expires'}
            </div>
            <button type="button" style="background: #0a0; color: #000; border: none; padding: 0.6em 1.2em; border-radius: 3px; cursor: pointer; font-weight: bold; margin-top: 1em;" onclick="copyToClipboard('${invite.code}')">
                📋 Copy Code
            </button>
        </div>
    `;
}

function displayBulkInvites(batch) {
    const resultDiv = document.getElementById('bulk-result');
    const codes = batch.codes.join('\n');

    resultDiv.innerHTML = `
        <div style="background: #1a3a1a; border: 1px solid #0a0; border-radius: 3px; padding: 1.5em;">
            <h5 style="color: #0a0; margin: 0 0 1em 0;">✓ Batch Created Successfully!</h5>

            <div style="background: #1a1a1a; border: 1px solid #333; border-radius: 3px; padding: 1em; margin-bottom: 1em;">
                <div style="color: #ffd700; font-weight: bold; margin-bottom: 0.5em;">📦 ${batch.count} Codes Generated</div>
                <textarea style="background: #0a0a0a; border: 1px solid #333; color: #0a0; font-family: monospace; width: 100%; height: 150px; padding: 0.5em; border-radius: 3px; resize: none;" readonly>${codes}</textarea>
            </div>

            <div style="display: flex; gap: 0.5em;">
                <button type="button" class="btn-dashboard" onclick="copyToClipboard('${codes.split('\\n').join(', ')}')">
                    📋 Copy All
                </button>
                <button type="button" class="btn-dashboard" onclick="downloadInvites('${batch.batchId}')">
                    📥 Download CSV
                </button>
            </div>

            ${batch.expiresAt ? `<p style="color: #aaa; font-size: 0.9em; margin-top: 1em;">Expires: ${new Date(batch.expiresAt).toLocaleDateString()}</p>` : ''}
        </div>
    `;
}

function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(() => {
        showAlert('Copied to clipboard!', 'success');
    });
}

function downloadInvites(batchId) {
    // Trigger CSV download
    const form = document.createElement('form');
    form.method = 'GET';
    form.action = '/admin/invites/export';
    form.innerHTML = `<input type="hidden" name="batchId" value="${batchId}">`;
    document.body.appendChild(form);
    form.submit();
    form.remove();
}

// ==================== VIEW INVITES ====================

function loadInvites(filter = 'all') {
    inviteOffset = 0;
    currentFilter = filter || 'all';

    let url = `/admin/invites?limit=${inviteLimit}&offset=0`;
    if (filter !== 'all') {
        url += `&status=${filter}`;
    }

    fetch(url)
        .then(r => r.json())
        .then(data => {
            if (!data.success) return;

            const list = document.getElementById('invites-list');
            const invites = data.details.invites || [];

            if (!invites.length) {
                list.innerHTML = '<p style="color: #777; text-align: center; padding: 1em;">No invites found</p>';
                return;
            }

            list.innerHTML = invites.map(invite => renderInviteCard(invite)).join('');
        });
}

function renderInviteCard(invite) {
    let status = 'unused';
    let statusLabel = 'Available';
    let statusClass = 'status-unused';

    if (invite.isRevoked) {
        status = 'revoked';
        statusLabel = 'Revoked';
        statusClass = 'status-revoked';
    } else if (invite.used) {
        status = 'used';
        statusLabel = 'Used';
        statusClass = 'status-used';
    } else if (invite.expiresAt && new Date(invite.expiresAt) < new Date()) {
        status = 'expired';
        statusLabel = 'Expired';
        statusClass = 'status-expired';
    }

    return `
        <div class="invite-card">
            <div class="invite-info">
                <div class="invite-code">
                    ${invite.code}
                    <span class="invite-status ${statusClass}">${statusLabel}</span>
                </div>
                <div class="invite-meta">
                    Created: ${new Date(invite.createdAt).toLocaleDateString()}
                    ${invite.expiresAt ? `• Expires: ${new Date(invite.expiresAt).toLocaleDateString()}` : '• Never expires'}
                    ${invite.usedByName ? `• Used by: ${invite.usedByName}` : ''}
                </div>
            </div>
            <div style="display: flex; gap: 0.5em; flex-shrink: 0;">
                <button type="button" class="btn-dashboard btn-danger" onclick="revokeInvite('${invite.id}')">
                    🚫 Revoke
                </button>
            </div>
        </div>
    `;
}

function filterInvites(filter) {
    document.querySelectorAll('.filter-btn').forEach(btn => btn.classList.remove('active'));
    event.target.classList.add('active');
    loadInvites(filter === 'all' ? null : filter);
}

function loadMoreInvites() {
    inviteOffset += inviteLimit;

    let url = `/admin/invites?limit=${inviteLimit}&offset=${inviteOffset}`;
    if (currentFilter !== 'all') {
        url += `&status=${currentFilter}`;
    }

    fetch(url)
        .then(r => r.json())
        .then(data => {
            if (!data.success) return;

            const list = document.getElementById('invites-list');
            const invites = data.details.invites || [];

            if (!invites.length) {
                showAlert('No more invites to load', 'error');
                return;
            }

            invites.forEach(invite => {
                list.innerHTML += renderInviteCard(invite);
            });
        });
}

function searchInvites(query) {
    const list = document.getElementById('invites-list');
    const cards = list.querySelectorAll('.invite-card');

    cards.forEach(card => {
        const code = card.querySelector('.invite-code').textContent;
        if (code.includes(query)) {
            card.style.display = 'flex';
        } else {
            card.style.display = 'none';
        }
    });
}

function revokeInvite(inviteId) {
    if (!confirm('Revoke this invite? It will no longer be usable.')) return;

    const formData = new FormData();
    formData.append('csrf_token', csrfToken);
    formData.append('inviteId', inviteId);

    fetch('/admin/invites', {
        method: 'DELETE',
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            showAlert('Invite revoked successfully', 'success');
            loadInvites(currentFilter !== 'all' ? currentFilter : null);
            loadStats();
        } else {
            showAlert(data.message || 'Error', 'error');
        }
    });
}

// ==================== BATCHES ====================

function loadBatches() {
    fetch('/admin/invites/batches')
        .then(r => r.json())
        .then(data => {
            if (!data.success) return;

            const list = document.getElementById('batches-list');
            const batches = data.details.batches || [];

            if (!batches.length) {
                list.innerHTML = '<p style="color: #777;">No batches created yet</p>';
                return;
            }

            list.innerHTML = batches.map(batch => `
                <div class="batch-card">
                    <div class="batch-name">${batch.batchName}</div>
                    <div class="batch-stats">
                        <div class="batch-stat">
                            Total: <strong style="color: #ffd700;">${batch.totalCodes}</strong>
                        </div>
                        <div class="batch-stat">
                            Used: <strong style="color: #3498db;">${batch.usedInvites || 0}</strong>
                        </div>
                        <div class="batch-stat">
                            Revoked: <strong style="color: #f03;">${batch.revokedInvites || 0}</strong>
                        </div>
                        <div class="batch-stat">
                            Created: <strong>${new Date(batch.createdAt).toLocaleDateString()}</strong>
                        </div>
                    </div>
                    <div style="margin-top: 0.8em; display: flex; gap: 0.5em;">
                        <button type="button" class="btn-dashboard" onclick="viewBatchInvites('${batch.id}')">
                            👁️ View Codes
                        </button>
                        <button type="button" class="btn-dashboard btn-danger" onclick="revokeBatch('${batch.id}')">
                            🚫 Revoke All
                        </button>
                    </div>
                </div>
            `).join('');
        });
}

function viewBatchInvites(batchId) {
    fetch(`/admin/invites?batchId=${batchId}`)
        .then(r => r.json())
        .then(data => {
            if (!data.success) return;

            document.querySelectorAll('.dashboard-tab-btn').forEach(btn => {
                btn.classList.remove('active');
                if (btn.dataset.panel === 'view') btn.classList.add('active');
            });

            document.querySelectorAll('.dashboard-panel').forEach(panel => {
                panel.classList.remove('active');
            });

            document.getElementById('view-panel').classList.add('active');

            const list = document.getElementById('invites-list');
            const invites = data.details.invites || [];

            list.innerHTML = invites.map(invite => renderInviteCard(invite)).join('');
        });
}

function revokeBatch(batchId) {
    if (!confirm('Revoke all invites in this batch? This cannot be undone.')) return;

    const formData = new FormData();
    formData.append('csrf_token', csrfToken);
    formData.append('batchId', batchId);

    fetch('/admin/invites', {
        method: 'DELETE',
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            showAlert('Batch revoked successfully', 'success');
            loadBatches();
            loadStats();
        } else {
            showAlert(data.message || 'Error', 'error');
        }
    });
}

function loadRecentInvites() {
    fetch('/admin/invites?limit=5')
        .then(r => r.json())
        .then(data => {
            if (!data.success) return;

            const list = document.getElementById('recent-invites');
            const invites = data.details.invites || [];

            if (!invites.length) {
                list.innerHTML = '<p style="color: #777;">No invites yet</p>';
                return;
            }

            list.innerHTML = invites.slice(0, 5).map(invite => {
                const status = invite.isRevoked ? 'revoked' : (invite.used ? 'used' : 'available');
                const statusColor = status === 'available' ? '#0a0' : (status === 'used' ? '#3498db' : '#f03');

                return `
                    <div style="background: #1a1a1a; padding: 0.8em; margin-bottom: 0.5em; border-left: 2px solid ${statusColor}; border-radius: 2px;">
                        <div style="font-family: monospace; color: #ffd700; font-weight: bold;">${invite.code}</div>
                        <div style="color: #aaa; font-size: 0.9em; margin-top: 0.2em;">
                            ${status.toUpperCase()} • ${new Date(invite.createdAt).toLocaleDateString()}
                        </div>
                    </div>
                `;
            }).join('');
        });
}

function exportInvites() {
    const formData = new FormData();
    formData.append('csrf_token', csrfToken);

    fetch('/admin/invites/export', {
        method: 'GET'
    })
    .then(r => r.blob())
    .then(blob => {
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `invites_${new Date().toISOString().split('T')[0]}.csv`;
        a.click();
        URL.revokeObjectURL(url);
        showAlert('CSV exported successfully', 'success');
    });
}

function debounce(fn, delay) {
    let timeout;
    return function(...args) {
        clearTimeout(timeout);
        timeout = setTimeout(() => fn.apply(this, args), delay);
    };
}
