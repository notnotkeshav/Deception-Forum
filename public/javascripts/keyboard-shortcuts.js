/**
 * Keyboard Shortcuts System
 * Vim-style and Slack-style shortcuts
 */

class KeyboardShortcutManager {
    constructor() {
        this.shortcuts = {};
        this.keyStates = {};
        this.helpVisible = false;
        this.init();
    }

    init() {
        this.loadShortcuts();
        this.attachListeners();
    }

    loadShortcuts() {
        fetch('/settings/shortcuts')
            .then(r => r.json())
            .then(data => {
                if (data.success && data.details.shortcuts) {
                    data.details.shortcuts.forEach(s => {
                        this.shortcuts[s.action] = {
                            keys: s.keys,
                            description: s.description,
                            category: s.category,
                            enabled: s.enabled
                        };
                    });
                }
            })
            .catch(err => console.error('Failed to load shortcuts:', err));
    }

    attachListeners() {
        document.addEventListener('keydown', (e) => this.handleKeyDown(e));
        document.addEventListener('keyup', (e) => this.handleKeyUp(e));
    }

    handleKeyDown(e) {
        // Ignore if focused on input/textarea (except for specific shortcuts)
        if (['INPUT', 'TEXTAREA'].includes(e.target.tagName)) {
            if (e.key === 'Escape') {
                e.target.blur();
            }
            return;
        }

        const key = this.getKeyCombo(e);

        // Help shortcut (always available)
        if (e.key === '?') {
            e.preventDefault();
            this.toggleHelp();
            return;
        }

        // Check for matching shortcut
        for (const [action, config] of Object.entries(this.shortcuts)) {
            if (!config.enabled) continue;

            if (this.matchesKeys(key, config.keys)) {
                e.preventDefault();
                this.executeAction(action);
                return;
            }
        }
    }

    handleKeyUp(e) {
        // Reset key state
        const key = e.key.toLowerCase();
        delete this.keyStates[key];
    }

    getKeyCombo(e) {
        const parts = [];
        if (e.ctrlKey || e.metaKey) parts.push('ctrl');
        if (e.shiftKey) parts.push('shift');
        if (e.altKey) parts.push('alt');
        parts.push(e.key.toLowerCase());
        return parts.join('+');
    }

    matchesKeys(pressed, shortcutKeys) {
        const shortcutParts = shortcutKeys.split('+').map(k => k.trim().toLowerCase());
        const pressedParts = pressed.split('+').map(k => k.trim().toLowerCase());

        if (shortcutParts.length !== pressedParts.length) return false;
        return shortcutParts.every((p, i) => p === pressedParts[i]);
    }

    executeAction(action) {
        switch (action) {
            case 'nav_down':
                this.navDown();
                break;
            case 'nav_up':
                this.navUp();
                break;
            case 'nav_next':
                this.navNext();
                break;
            case 'nav_prev':
                this.navPrev();
                break;
            case 'go_threads':
                window.location = '/threads';
                break;
            case 'go_profile':
                window.location = '/user';
                break;
            case 'go_notifications':
                window.location = '/notifications';
                break;
            case 'go_messages':
                window.location = '/private-chats';
                break;
            case 'reply_focused':
                this.replyFocused();
                break;
            case 'like_focused':
                this.likeFocused();
                break;
            case 'close_modal':
                this.closeModal();
                break;
            case 'submit_form':
                this.submitForm();
                break;
            case 'search_focus':
                this.focusSearch();
                break;
            default:
                console.log('Action not implemented:', action);
        }
    }

    navDown() {
        const focused = document.activeElement;
        const items = document.querySelectorAll('.thread-item, .comment-item, .message-item');
        if (!items.length) return;

        const currentIndex = Array.from(items).indexOf(focused);
        const nextItem = items[currentIndex + 1] || items[0];
        nextItem.scrollIntoView({ behavior: 'smooth', block: 'center' });
        nextItem.focus();
    }

    navUp() {
        const items = document.querySelectorAll('.thread-item, .comment-item, .message-item');
        if (!items.length) return;

        const focused = document.activeElement;
        const currentIndex = Array.from(items).indexOf(focused);
        const prevItem = items[currentIndex - 1] || items[items.length - 1];
        prevItem.scrollIntoView({ behavior: 'smooth', block: 'center' });
        prevItem.focus();
    }

    navNext() {
        const items = document.querySelectorAll('[data-thread-id]');
        if (!items.length) return;
        const first = items[0];
        if (first) first.click();
    }

    navPrev() {
        window.history.back();
    }

    replyFocused() {
        const replyBtn = document.querySelector('.reply-btn, [data-action="reply"]');
        if (replyBtn) replyBtn.click();
    }

    likeFocused() {
        const likeBtn = document.querySelector('.like-btn, [data-action="like"]');
        if (likeBtn) likeBtn.click();
    }

    closeModal() {
        const modal = document.querySelector('.modal.show, [role="dialog"][aria-hidden="false"]');
        if (modal) {
            const closeBtn = modal.querySelector('[data-bs-dismiss="modal"], .btn-close, [aria-label*="close"]');
            if (closeBtn) closeBtn.click();
        }
    }

    submitForm() {
        const form = document.querySelector('form:focus-within, textarea:focus').closest('form');
        if (form) {
            const submitBtn = form.querySelector('button[type="submit"]');
            if (submitBtn) submitBtn.click();
        }
    }

    focusSearch() {
        const searchInput = document.querySelector('input[type="search"], [placeholder*="search" i]');
        if (searchInput) searchInput.focus();
    }

    toggleHelp() {
        this.helpVisible = !this.helpVisible;

        if (this.helpVisible) {
            this.showHelp();
        } else {
            const helpModal = document.getElementById('shortcuts-help-modal');
            if (helpModal) helpModal.remove();
        }
    }

    showHelp() {
        const existing = document.getElementById('shortcuts-help-modal');
        if (existing) existing.remove();

        const modal = document.createElement('div');
        modal.id = 'shortcuts-help-modal';
        modal.style.cssText = `
            position: fixed; top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(0,0,0,0.8); display: flex; align-items: center;
            justify-content: center; z-index: 10000; animation: fadeIn 0.3s;
        `;

        const content = document.createElement('div');
        content.style.cssText = `
            background: #121212; border: 1px solid #ffd700; color: #fff;
            padding: 2em; border-radius: 4px; max-width: 600px; max-height: 80vh;
            overflow-y: auto;
        `;

        const grouped = this.groupShortcuts();
        let html = '<h2 style="color: #ffd700; margin-bottom: 1.5em;">⌨️ Keyboard Shortcuts</h2>';

        for (const [category, shortcuts] of Object.entries(grouped)) {
            html += `<h4 style="color: #ffaa00; margin-top: 1em; margin-bottom: 0.5em;">${category}</h4>`;
            html += '<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.5em; margin-bottom: 1em;">';

            shortcuts.forEach(s => {
                html += `
                    <div style="background: #1a1a1a; padding: 0.5em; border-radius: 3px;">
                        <code style="color: #9b59b6; font-size: 0.9em;">${s.keys}</code><br>
                        <small style="color: #aaa;">${s.description}</small>
                    </div>
                `;
            });

            html += '</div>';
        }

        html += '<button style="background: #ffd700; color: #000; border: none; padding: 0.6em 1.2em; border-radius: 3px; cursor: pointer; width: 100%; font-weight: bold;" onclick="document.getElementById(\'shortcuts-help-modal\').remove();">✓ Close</button>';

        content.innerHTML = html;
        modal.appendChild(content);
        document.body.appendChild(modal);

        modal.addEventListener('click', (e) => {
            if (e.target === modal) modal.remove();
        });
    }

    groupShortcuts() {
        const grouped = {};
        for (const [action, config] of Object.entries(this.shortcuts)) {
            if (!config.category) config.category = 'other';
            if (!grouped[config.category]) grouped[config.category] = [];
            grouped[config.category].push({ ...config, action });
        }
        return grouped;
    }
}

// Initialize globally
window.addEventListener('DOMContentLoaded', () => {
    window.keyboardShortcuts = new KeyboardShortcutManager();
});

// ==================== SETTINGS PANEL FUNCTIONS ====================

function loadShortcuts() {
    fetch('/settings/shortcuts')
        .then(r => r.json())
        .then(data => {
            if (!data.success) return;

            const list = document.getElementById('shortcuts-list');
            list.innerHTML = '';

            const grouped = {};
            data.details.shortcuts.forEach(s => {
                if (!grouped[s.category]) grouped[s.category] = [];
                grouped[s.category].push(s);
            });

            for (const [category, shortcuts] of Object.entries(grouped)) {
                const section = document.createElement('div');
                section.innerHTML = `<h5 style="color: #ffaa00; margin-top: 1em; margin-bottom: 0.8em;">${category}</h5>`;

                shortcuts.forEach(s => {
                    const row = document.createElement('div');
                    row.className = 'shortcut-row';
                    row.innerHTML = `
                        <div>
                            <label style="margin: 0; color: #ffd700; font-weight: bold;">${s.description}</label>
                            <small style="color: #777; display: block; margin-top: 0.2em;">${s.action}</small>
                        </div>
                        <input type="text" value="${s.keys}" data-action="${s.action}" style="font-family: monospace;">
                        <button type="button" class="btn-custom btn-success" onclick="saveShortcut('${s.action}', this)">✓</button>
                    `;
                    section.appendChild(row);
                });

                list.appendChild(section);
            }
        });
}

function saveShortcut(action, btn) {
    const input = btn.previousElementSibling;
    const keys = input.value.trim();

    if (!keys) {
        showAlert('Keys cannot be empty', 'error');
        return;
    }

    const formData = new FormData();
    formData.append('csrf_token', csrfToken);
    formData.append('action', action);
    formData.append('keys', keys);

    fetch('/settings/shortcuts', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            showAlert(`Shortcut updated: ${keys}`, 'success');
            window.keyboardShortcuts.loadShortcuts();
        } else {
            showAlert(data.message || 'Error', 'error');
        }
    });
}

function resetShortcuts() {
    if (!confirm('Reset all shortcuts to default? This cannot be undone.')) return;

    const formData = new FormData();
    formData.append('csrf_token', csrfToken);

    fetch('/settings/shortcuts', {
        method: 'DELETE',
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            showAlert('Shortcuts reset to default', 'success');
            loadShortcuts();
            window.keyboardShortcuts.loadShortcuts();
        }
    });
}
