/**
 * Draft Auto-Save System
 * Automatically save drafts to LocalStorage and server
 */

class DraftSystem {
    constructor() {
        this.saveInterval = 30 * 1000; // 30 seconds
        this.saveTimers = {};
        this.init();
    }

    init() {
        this.attachAutoSave();
        this.cleanupExpiredDrafts();
    }

    attachAutoSave() {
        // Auto-save form textareas
        document.addEventListener('input', (e) => {
            if (!['TEXTAREA', 'INPUT'].includes(e.target.tagName)) return;

            const container = e.target.closest('[data-draft-type]');
            if (!container) return;

            const draftType = container.dataset.draftType;
            const threadId = container.dataset.threadId || null;

            clearTimeout(this.saveTimers[draftType]);
            this.saveTimers[draftType] = setTimeout(() => {
                this.saveDraft(draftType, e.target.value, threadId);
            }, this.saveInterval);

            // Also save to LocalStorage immediately for offline support
            this.saveToLocalStorage(draftType, e.target.value, threadId);
        });
    }

    saveDraft(draftType, content, threadId = null) {
        if (!content.trim()) return;

        const formData = new FormData();
        formData.append('draftType', draftType);
        formData.append('content', content);
        if (threadId) formData.append('threadId', threadId);
        formData.append('metadata', JSON.stringify({
            saved_at: new Date().toISOString(),
            device: 'web'
        }));

        fetch('/drafts/save', {
            method: 'POST',
            body: formData
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                this.showDraftIndicator(`Draft saved at ${new Date().toLocaleTimeString()}`);
            }
        })
        .catch(err => console.error('Draft save error:', err));
    }

    saveToLocalStorage(draftType, content, threadId = null) {
        const key = `draft_${draftType}_${threadId || 'global'}`;
        localStorage.setItem(key, JSON.stringify({
            content: content,
            saved_at: new Date().toISOString()
        }));
    }

    loadLocalDraft(draftType, threadId = null) {
        const key = `draft_${draftType}_${threadId || 'global'}`;
        const stored = localStorage.getItem(key);
        return stored ? JSON.parse(stored) : null;
    }

    showDraftIndicator(message) {
        const existing = document.querySelector('[data-draft-indicator]');
        if (existing) existing.remove();

        const indicator = document.createElement('small');
        indicator.dataset.draftIndicator = true;
        indicator.style.cssText = `
            display: block;
            color: #0a0;
            margin-top: 0.3em;
            font-style: italic;
        `;
        indicator.textContent = '💾 ' + message;

        const form = document.activeElement.closest('form') || document.querySelector('form');
        if (form) {
            form.appendChild(indicator);
            setTimeout(() => indicator.remove(), 3000);
        }
    }

    restoreDraft(draftType, threadId = null) {
        const draft = this.loadLocalDraft(draftType, threadId);
        if (draft && draft.content) {
            return draft.content;
        }
        return null;
    }

    clearDraft(draftType, threadId = null) {
        const key = `draft_${draftType}_${threadId || 'global'}`;
        localStorage.removeItem(key);
    }

    cleanupExpiredDrafts() {
        // Clear drafts older than 30 days
        const keys = Object.keys(localStorage);
        const thirtyDaysAgo = new Date(Date.now() - 30 * 24 * 60 * 60 * 1000);

        keys.forEach(key => {
            if (!key.startsWith('draft_')) return;

            const stored = JSON.parse(localStorage.getItem(key));
            const savedAt = new Date(stored.saved_at);

            if (savedAt < thirtyDaysAgo) {
                localStorage.removeItem(key);
            }
        });
    }

    getAllDrafts() {
        const drafts = [];
        const keys = Object.keys(localStorage);

        keys.forEach(key => {
            if (!key.startsWith('draft_')) return;
            const stored = JSON.parse(localStorage.getItem(key));
            const parts = key.substring(6).split('_');
            const draftType = parts[0];
            const threadId = parts[1] === 'global' ? null : parts.slice(1).join('_');

            drafts.push({
                key,
                draftType,
                threadId,
                content: stored.content,
                saved_at: stored.saved_at
            });
        });

        return drafts;
    }
}

window.draftSystem = new DraftSystem();

// ==================== SETTINGS PANEL FUNCTIONS ====================

function loadDrafts() {
    fetch('/drafts/retrieve')
        .then(r => r.json())
        .then(data => {
            if (!data.success) return;

            const list = document.getElementById('drafts-list');
            const noDrafts = document.getElementById('no-drafts');
            const drafts = data.details.drafts || [];
            const localDrafts = window.draftSystem.getAllDrafts();

            // Combine both sources
            const allDrafts = [...(drafts || []), ...localDrafts];

            if (!allDrafts.length) {
                noDrafts.style.display = 'block';
                list.innerHTML = '';
                return;
            }

            noDrafts.style.display = 'none';
            list.innerHTML = '';

            allDrafts.forEach((draft, idx) => {
                const item = document.createElement('div');
                item.className = 'draft-item';

                const timestamp = new Date(draft.saved_at || draft.lastSavedAt);
                const isLocal = !draft.id; // Local drafts don't have ID

                item.innerHTML = `
                    <div style="display: flex; justify-content: space-between; align-items: start; gap: 1em;">
                        <div style="flex: 1;">
                            <strong style="color: #ffd700;">
                                ${draft.draftType === 'comment' ? '💬' : draft.draftType === 'thread' ? '📌' : '✉️'}
                                ${draft.draftType.charAt(0).toUpperCase() + draft.draftType.slice(1)} Draft
                            </strong>
                            <div class="draft-preview">${DOMPurify.sanitize(draft.content || '')}</div>
                            <div class="draft-timestamp">
                                Saved: ${timestamp.toLocaleDateString()} at ${timestamp.toLocaleTimeString()}
                                ${isLocal ? ' <span style="color: #f03;">(Local)</span>' : ''}
                            </div>
                        </div>
                        <div style="display: flex; gap: 0.5em; flex-shrink: 0;">
                            <button type="button" class="btn-custom btn-success" onclick="restoreDraft('${draft.key}')">
                                Restore
                            </button>
                            <button type="button" class="btn-custom btn-danger" onclick="deleteDraft('${draft.key}')">
                                Delete
                            </button>
                        </div>
                    </div>
                `;

                list.appendChild(item);
            });
        });
}

function restoreDraft(key) {
    const stored = JSON.parse(localStorage.getItem(key));
    if (!stored) return;

    // Try to find the corresponding textarea
    const textarea = document.querySelector('textarea');
    if (textarea) {
        textarea.value = stored.content;
        textarea.focus();
        showAlert('Draft restored!', 'success');
    } else {
        showAlert('Could not find editor to restore draft. Copy it manually.', 'error');
        // Copy to clipboard
        navigator.clipboard.writeText(stored.content);
    }
}

function deleteDraft(key) {
    if (!confirm('Delete this draft? This cannot be undone.')) return;

    localStorage.removeItem(key);
    showAlert('Draft deleted', 'success');
    loadDrafts();
}

// Auto-populate draft forms with existing drafts on page load
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-draft-type]').forEach(el => {
        if (el.tagName === 'TEXTAREA' && !el.value) {
            const draftType = el.dataset.draftType;
            const threadId = el.dataset.threadId || null;
            const draft = window.draftSystem.restoreDraft(draftType, threadId);

            if (draft) {
                el.value = draft;
                el.style.borderColor = '#0a0';
                const msg = document.createElement('small');
                msg.style.cssText = 'display: block; color: #0a0; margin-top: 0.3em;';
                msg.innerHTML = '📝 <strong>Restored from draft.</strong> Click "Post" to submit or clear to start fresh.';
                el.parentNode.insertBefore(msg, el.nextSibling);

                setTimeout(() => msg.remove(), 8000);
            }
        }
    });
});
