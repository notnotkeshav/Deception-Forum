/**
 * Plugins System
 * Secure plugin management and extension system
 */

class PluginSystem {
    constructor() {
        this.plugins = [];
        this.hooks = {};
        this.init();
    }

    init() {
        this.loadPlugins();
        this.registerGlobalHooks();
    }

    loadPlugins() {
        fetch('/plugins/manage')
            .then(r => r.json())
            .then(data => {
                if (data.success && data.details.plugins) {
                    this.plugins = data.details.plugins;
                    this.executePlugins();
                }
            })
            .catch(err => console.error('Failed to load plugins:', err));
    }

    executePlugins() {
        // Execute each plugin in a sandboxed context
        this.plugins.forEach(plugin => {
            if (!plugin.enabled) return;

            try {
                // Create a sandboxed scope for the plugin
                const sandbox = {
                    window: window,
                    document: document,
                    console: console,
                    fetch: fetch,
                    registerHook: (hookName, callback) => this.registerHook(hookName, callback),
                    getConfig: () => plugin.config || {},
                    plugin: plugin,
                    API: {
                        showNotification: (msg) => showAlert(msg, 'success'),
                        getUser: () => fetch('/user').then(r => r.json()),
                        registerKeyboardShortcut: (keys, callback) => {
                            if (window.keyboardShortcuts) {
                                window.keyboardShortcuts.shortcuts[plugin.name] = {
                                    keys,
                                    description: plugin.name,
                                    category: 'plugins',
                                    callback
                                };
                            }
                        }
                    }
                };

                // Execute plugin code safely
                const executePlugin = new Function(
                    'sandbox',
                    `
                    with(sandbox) {
                        ${plugin.manifestJSON.code || ''}
                    }
                    `
                );

                executePlugin(sandbox);

                console.log(`Plugin loaded: ${plugin.name} v${plugin.version}`);
            } catch (err) {
                console.error(`Plugin error (${plugin.name}):`, err);
            }
        });
    }

    registerHook(hookName, callback) {
        if (!this.hooks[hookName]) {
            this.hooks[hookName] = [];
        }
        this.hooks[hookName].push(callback);
    }

    executeHook(hookName, ...args) {
        if (!this.hooks[hookName]) return null;

        let result = args[0];
        this.hooks[hookName].forEach(callback => {
            try {
                result = callback(result, ...args.slice(1)) || result;
            } catch (err) {
                console.error(`Hook error (${hookName}):`, err);
            }
        });

        return result;
    }

    installPlugin(pluginId) {
        const formData = new FormData();
        formData.append('csrf_token', window.csrfToken || '');
        formData.append('pluginId', pluginId);

        return fetch('/plugins/manage', {
            method: 'POST',
            body: formData
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                this.loadPlugins();
                return data;
            } else {
                throw new Error(data.message);
            }
        });
    }

    uninstallPlugin(pluginId) {
        const formData = new FormData();
        formData.append('csrf_token', window.csrfToken || '');
        formData.append('pluginId', pluginId);

        return fetch('/plugins/manage', {
            method: 'DELETE',
            body: formData
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                this.loadPlugins();
                return data;
            } else {
                throw new Error(data.message);
            }
        });
    }

    updatePluginConfig(pluginId, config) {
        const formData = new FormData();
        formData.append('csrf_token', window.csrfToken || '');
        formData.append('pluginId', pluginId);
        formData.append('config', JSON.stringify(config));

        return fetch('/plugins/manage', {
            method: 'PUT',
            body: formData
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                this.loadPlugins();
                return data;
            } else {
                throw new Error(data.message);
            }
        });
    }

    registerGlobalHooks() {
        // Hook points for plugins to extend functionality
        this.hooks = {
            'page_load': [],           // Fired on every page load
            'thread_view': [],         // Fired when viewing a thread
            'comment_render': [],      // Fired when rendering comment
            'message_send': [],        // Fired before sending message
            'user_menu': [],           // Add items to user menu
            'thread_actions': [],      // Add buttons to threads
            'profile_view': []         // Fired when viewing profile
        };
    }
}

window.pluginSystem = new PluginSystem();

// ==================== SETTINGS PANEL FUNCTIONS ====================

function loadPlugins() {
    // Load installed plugins
    fetch('/plugins/manage')
        .then(r => r.json())
        .then(data => {
            if (!data.success) return;

            const list = document.getElementById('installed-plugins-list');
            const noPlugins = document.getElementById('no-plugins');
            const plugins = data.details.plugins || [];

            if (!plugins.length) {
                noPlugins.style.display = 'block';
                list.innerHTML = '';
            } else {
                noPlugins.style.display = 'none';
                list.innerHTML = plugins.map(p => `
                    <div class="plugin-card">
                        <div class="plugin-info">
                            <div class="plugin-name">${p.name}</div>
                            <div class="plugin-author">by ${p.author} • v${p.version}</div>
                            <div class="plugin-description">${p.description || 'No description'}</div>
                        </div>
                        <div style="display: flex; gap: 0.5em;">
                            <button type="button" class="btn-custom" onclick="configurePlugin('${p.id}')">
                                ⚙️ Config
                            </button>
                            <button type="button" class="btn-custom btn-danger" onclick="uninstallPlugin('${p.id}')">
                                🗑️ Remove
                            </button>
                        </div>
                    </div>
                `).join('');
            }
        });

    // Load marketplace plugins
    fetch('/plugins/marketplace?limit=10')
        .then(r => r.json())
        .then(data => {
            if (!data.success) return;

            const list = document.getElementById('marketplace-plugins-list');
            const plugins = data.details.plugins || [];

            list.innerHTML = plugins.map(p => `
                <div class="plugin-card">
                    <div class="plugin-info">
                        <div class="plugin-name">${p.name}</div>
                        <div class="plugin-author">
                            by ${p.author}
                            ${p.verified ? '<span style="color: #0a0; margin-left: 0.5em;">✓ Verified</span>' : ''}
                        </div>
                        <div class="plugin-description">${p.description || 'No description'}</div>
                        <div style="color: #777; font-size: 0.85em; margin-top: 0.3em;">
                            ⭐ ${p.rating || '0'} • 📥 ${p.downloads || '0'} downloads
                        </div>
                    </div>
                    <button type="button" class="btn-custom btn-success" onclick="installPlugin('${p.id}')">
                        📥 Install
                    </button>
                </div>
            `).join('');

            if (!plugins.length) {
                list.innerHTML = '<p style="color: #777; text-align: center; padding: 1em;">No plugins available in marketplace</p>';
            }
        });
}

function installPlugin(pluginId) {
    window.pluginSystem.installPlugin(pluginId)
        .then(() => {
            showAlert('Plugin installed successfully!', 'success');
            loadPlugins();
        })
        .catch(err => showAlert(err.message, 'error'));
}

function uninstallPlugin(pluginId) {
    if (!confirm('Uninstall this plugin?')) return;

    window.pluginSystem.uninstallPlugin(pluginId)
        .then(() => {
            showAlert('Plugin uninstalled', 'success');
            loadPlugins();
        })
        .catch(err => showAlert(err.message, 'error'));
}

function configurePlugin(pluginId) {
    showAlert('Plugin configuration coming soon', 'success');
    // This would open a modal with plugin-specific config options
}

// Plugin example/template (for future plugin developers)
const PLUGIN_TEMPLATE = `
// Example Plugin Template
const metadata = {
    id: 'example-plugin',
    name: 'Example Plugin',
    version: '1.0.0',
    author: 'username',
    description: 'An example plugin showing capabilities',
    permissions: ['read_threads', 'read_user_profile'],
    hooks: ['page_load', 'thread_view']
};

registerHook('page_load', () => {
    console.log('Page loaded! Plugin is active.');
});

registerHook('thread_view', (threadData) => {
    console.log('Viewing thread:', threadData);
    return threadData;
});

API.registerKeyboardShortcut('p+h', () => {
    API.showNotification('Plugin keyboard shortcut works!');
});
`;
