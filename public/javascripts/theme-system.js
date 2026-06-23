/**
 * Theme System
 * Dynamic theme creation, switching, and custom CSS
 */

class ThemeSystem {
    constructor() {
        this.themes = [];
        this.activeTheme = null;
        this.init();
    }

    init() {
        this.loadThemes();
        this.applyActiveTheme();
    }

    loadThemes() {
        fetch('/settings/theme')
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    this.themes = data.details.themes || [];
                    this.activeTheme = data.details.activeTheme;
                    if (data.details.customCSS) {
                        this.applyCustomCSS(data.details.customCSS);
                    }
                }
            })
            .catch(err => console.error('Failed to load themes:', err));
    }

    createTheme(name, colors, cssVars, description = '') {
        const formData = new FormData();
        formData.append('csrf_token', window.csrfToken || '');
        formData.append('name', name);
        formData.append('description', description);
        formData.append('colors', JSON.stringify(colors));
        formData.append('cssVars', JSON.stringify(cssVars));

        return fetch('/settings/theme', {
            method: 'PUT',
            body: formData
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                this.loadThemes();
                return data.details;
            } else {
                throw new Error(data.message);
            }
        });
    }

    setActiveTheme(themeId) {
        const formData = new FormData();
        formData.append('csrf_token', window.csrfToken || '');
        formData.append('themeId', themeId);

        return fetch('/settings/theme', {
            method: 'POST',
            body: formData
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                this.loadThemes();
                this.applyActiveTheme();
                return data;
            } else {
                throw new Error(data.message);
            }
        });
    }

    applyActiveTheme() {
        if (!this.activeTheme || !this.activeTheme.cssVars) return;

        let cssContent = ':root {\n';
        for (const [key, value] of Object.entries(this.activeTheme.cssVars)) {
            cssContent += `  --${key}: ${value};\n`;
        }
        cssContent += '}';

        this.injectCSS(cssContent, 'active-theme');
    }

    applyCustomCSS(css) {
        this.injectCSS(css, 'custom-theme-css');
    }

    injectCSS(css, id) {
        let style = document.getElementById(id);
        if (!style) {
            style = document.createElement('style');
            style.id = id;
            document.head.appendChild(style);
        }
        style.textContent = css;
    }

    deleteTheme(themeId) {
        const formData = new FormData();
        formData.append('csrf_token', window.csrfToken || '');
        formData.append('themeId', themeId);

        return fetch('/settings/theme', {
            method: 'DELETE',
            body: formData
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                this.loadThemes();
                return data;
            } else {
                throw new Error(data.message);
            }
        });
    }
}

window.themeSystem = new ThemeSystem();

// ==================== SETTINGS PANEL FUNCTIONS ====================

function loadThemes() {
    fetch('/settings/theme')
        .then(r => r.json())
        .then(data => {
            if (!data.success) return;

            const list = document.getElementById('user-themes-list');
            list.innerHTML = '';

            const themes = data.details.themes || [];
            const active = data.details.activeTheme;

            if (!themes.length) {
                list.innerHTML = '<p style="color: #777; text-align: center; padding: 1em;">No custom themes yet. Create one above!</p>';
                return;
            }

            themes.forEach(theme => {
                const isActive = active && active.id === theme.id;
                const card = document.createElement('div');
                card.className = 'settings-section';
                card.innerHTML = `
                    <div style="display: flex; justify-content: space-between; align-items: start; gap: 1em;">
                        <div style="flex: 1;">
                            <h4 style="color: #ffd700; margin: 0;">${theme.name}</h4>
                            <p style="color: #aaa; margin: 0.3em 0; font-size: 0.9em;">${theme.description || 'No description'}</p>
                            <div style="display: flex; gap: 0.5em; margin-top: 0.5em;">
                                ${Object.values(theme.colors).map(color => `
                                    <div style="width: 20px; height: 20px; background: ${color}; border-radius: 3px; border: 1px solid #333;"></div>
                                `).join('')}
                            </div>
                        </div>
                        <div style="display: flex; gap: 0.5em;">
                            ${isActive ? '<span style="color: #0a0; font-weight: bold;">✓ Active</span>' : `
                                <button type="button" class="btn-custom btn-success" onclick="activateTheme('${theme.id}')">
                                    Activate
                                </button>
                            `}
                            <button type="button" class="btn-custom btn-danger" onclick="deleteTheme('${theme.id}')">
                                Delete
                            </button>
                        </div>
                    </div>
                `;
                list.appendChild(card);
            });
        });
}

function activateTheme(themeId) {
    window.themeSystem.setActiveTheme(themeId)
        .then(() => {
            showAlert('Theme activated!', 'success');
            loadThemes();
        })
        .catch(err => showAlert(err.message, 'error'));
}

function deleteTheme(themeId) {
    if (!confirm('Delete this theme?')) return;

    window.themeSystem.deleteTheme(themeId)
        .then(() => {
            showAlert('Theme deleted', 'success');
            loadThemes();
        })
        .catch(err => showAlert(err.message, 'error'));
}

function saveCustomCSS() {
    const css = document.getElementById('custom-css').value;
    const enabled = document.getElementById('enable-custom-css').checked;

    if (!css.trim() && !enabled) {
        showAlert('No CSS to save', 'error');
        return;
    }

    const formData = new FormData();
    formData.append('csrf_token', window.csrfToken || '');
    formData.append('customCSS', css);
    formData.append('enable', enabled ? '1' : '0');

    fetch('/settings/theme', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            if (enabled && css.trim()) {
                window.themeSystem.applyCustomCSS(css);
            }
            showAlert('Custom CSS saved!', 'success');
        } else {
            showAlert(data.message || 'Error', 'error');
        }
    });
}

// Theme form submission
document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('create-theme-form');
    if (form) {
        form.addEventListener('submit', (e) => {
            e.preventDefault();

            const name = form.querySelector('input[name="name"]').value;
            const description = form.querySelector('textarea[name="description"]').value;
            const primaryColor = document.getElementById('primary-color').value;
            const secondaryColor = document.getElementById('secondary-color').value;
            const accentColor = document.getElementById('accent-color').value;
            const textColor = document.getElementById('text-color').value;

            const colors = { primaryColor, secondaryColor, accentColor, textColor };
            const cssVars = {
                'primary-color': primaryColor,
                'secondary-color': secondaryColor,
                'accent-color': accentColor,
                'text-color': textColor
            };

            window.themeSystem.createTheme(name, colors, cssVars, description)
                .then(() => {
                    showAlert('Theme created successfully!', 'success');
                    form.reset();
                    loadThemes();
                })
                .catch(err => showAlert(err.message, 'error'));
        });
    }
});
