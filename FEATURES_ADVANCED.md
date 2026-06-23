# Advanced Features Implementation Guide

Complete documentation for 6 new advanced features: Keyboard Shortcuts, Draft Auto-Save, Theme System, Plugin System, User Suspension, and Post Hiding.

---

## 📋 Table of Contents

1. [Keyboard Shortcuts](#keyboard-shortcuts)
2. [Draft Auto-Save](#draft-auto-save)
3. [Theme System](#theme-system)
4. [Plugin System](#plugin-system)
5. [User Suspension](#user-suspension)
6. [Post Hiding](#post-hiding)
7. [Database Schema](#database-schema)
8. [API Reference](#api-reference)

---

## ⌨️ Keyboard Shortcuts

### Overview

Vim-style and Slack-style keyboard shortcuts for fast navigation and actions.

**Default Shortcuts:**
```
Navigation:
  j     — Navigate down
  k     — Navigate up
  l     — Next thread
  h     — Previous thread

Global:
  g+t   — Go to threads
  g+p   — Go to profile
  g+n   — Go to notifications
  g+m   — Go to messages
  ?     — Show help
  esc   — Close modal

Actions:
  r     — Reply to focused item
  +     — Like focused content

Editing:
  ctrl+s      — Save draft
  ctrl+enter  — Submit form
  /           — Focus search
```

### Implementation Details

**Backend:**
- `Backend/controllers/settings/shortcuts.php` — GET/POST/DELETE keyboard shortcuts
- `Backend/Utils/AdvancedFeatures::getDefaultShortcuts()` — Fetch default shortcuts
- `Backend/Utils/AdvancedFeatures::setShortcut($userId, $action, $keys)` — Save custom shortcut
- Table: `user_shortcuts` — User-specific overrides
- Table: `default_shortcuts` — System defaults

**Frontend:**
- `public/javascripts/keyboard-shortcuts.js` — KeyboardShortcutManager class
- Settings panel at `/settings/advanced` (Shortcuts tab)
- Help modal with `?` key

### Usage

1. **Load shortcuts on page:**
```javascript
window.keyboardShortcuts.loadShortcuts();
```

2. **Customize shortcut:**
```javascript
window.keyboardShortcuts.shortcuts['my_action'] = {
    keys: 'ctrl+shift+m',
    description: 'My action',
    enabled: true
};
```

3. **Execute hook:**
```javascript
window.keyboardShortcuts.registerHook('page_load', () => {
    // Custom logic when page loads
});
```

### Key Files

| File | Purpose |
|------|---------|
| `database/09_advanced_features.sql` | Schema (user_shortcuts, default_shortcuts) |
| `Backend/Utils/AdvancedFeatures.php` | Utility methods |
| `public/javascripts/keyboard-shortcuts.js` | Frontend manager (352 lines) |
| `frontend/views/settings/advanced.view.php` | Settings UI |

---

## 📝 Draft Auto-Save

### Overview

Automatically save drafts every 30 seconds while typing. Drafts stored both in LocalStorage (for instant restore) and on server (for sync across devices).

**Features:**
- Auto-save every 30 seconds
- LocalStorage fallback for offline
- Restore drafts on page load
- 30-day expiration
- Visual "Draft saved" indicator

### Implementation Details

**Backend:**
- `Backend/controllers/drafts/save.php` — POST to save draft
- `Backend/controllers/drafts/retrieve.php` — GET drafts
- `Backend/Utils/AdvancedFeatures::saveDraft()` — Save draft with metadata
- Table: `drafts` — Server-side draft storage

**Frontend:**
- `public/javascripts/draft-system.js` — DraftSystem class
- Auto-attach to `[data-draft-type]` textareas
- LocalStorage key: `draft_{type}_{threadId}`

### Usage

1. **Mark textarea as draft-enabled:**
```html
<textarea data-draft-type="comment" data-thread-id="123"></textarea>
```

2. **Manual save:**
```javascript
window.draftSystem.saveDraft('comment', 'content here', threadId);
```

3. **Restore draft:**
```javascript
const draft = window.draftSystem.restoreDraft('comment', threadId);
if (draft) textarea.value = draft;
```

### Key Files

| File | Purpose |
|------|---------|
| `database/09_advanced_features.sql` | Schema (drafts table) |
| `Backend/Utils/AdvancedFeatures.php` | saveDraft, getDraft, deleteDraft |
| `public/javascripts/draft-system.js` | Frontend manager (300+ lines) |
| `frontend/views/settings/advanced.view.php` | Drafts UI tab |

---

## 🎨 Theme System

### Overview

Create and apply custom themes with CSS variables and color schemes. **Level 4+ only.**

**Features:**
- Create themes with color palette
- Apply/switch themes instantly
- Custom CSS editor
- Public/private themes
- Dynamic CSS injection

### Implementation Details

**Backend:**
- `Backend/controllers/settings/theme.php` — GET/POST/PUT/DELETE themes
- `Backend/Utils/AdvancedFeatures::createTheme()` — Create theme
- `Backend/Utils/AdvancedFeatures::setActiveTheme()` — Switch theme
- Table: `themes` — Theme definitions
- Table: `user_theme_preferences` — User's active theme + custom CSS

**Frontend:**
- `public/javascripts/theme-system.js` — ThemeSystem class
- Settings panel at `/settings/advanced` (Themes tab)
- Color picker UI
- CSS editor

### Theme Object

```json
{
    "id": "uuid",
    "userId": "uuid",
    "name": "Midnight Blue",
    "description": "Dark blue theme",
    "colors": {
        "primaryColor": "#ffd700",
        "secondaryColor": "#121212",
        "accentColor": "#9b59b6",
        "textColor": "#ffffff"
    },
    "cssVars": {
        "primary-color": "#ffd700",
        "secondary-color": "#121212",
        "accent-color": "#9b59b6",
        "text-color": "#ffffff"
    },
    "isPublic": false,
    "createdAt": "2026-06-23T10:00:00Z"
}
```

### Usage

1. **Create theme:**
```javascript
window.themeSystem.createTheme(
    'My Theme',
    { primaryColor: '#fff', ... },
    { 'primary-color': '#fff', ... },
    'My custom theme'
);
```

2. **Switch theme:**
```javascript
window.themeSystem.setActiveTheme(themeId);
```

3. **Apply custom CSS:**
```javascript
window.themeSystem.applyCustomCSS(':root { --custom: value; }');
```

### Restrictions

- **Level 4+ only** — Checked in backend before allowing create/update
- Custom CSS limited to 10KB
- CSS variables only (no JavaScript execution)

### Key Files

| File | Purpose |
|------|---------|
| `database/09_advanced_features.sql` | Schema (themes, user_theme_preferences) |
| `Backend/Utils/AdvancedFeatures.php` | createTheme, setActiveTheme, getCustomCSS |
| `Backend/controllers/settings/theme.php` | API endpoints |
| `public/javascripts/theme-system.js` | Frontend manager |
| `frontend/views/settings/advanced.view.php` | Theme UI tab |

---

## 🔌 Plugin System

### Overview

Secure plugin system for extending forum functionality. Plugins can hook into various events and modify behavior.

**Security Features:**
- Code sandboxing via Function constructor
- Permission whitelist system
- Code hash verification
- Plugin verification process (requires admin)
- Restricted API access

### Implementation Details

**Backend:**
- `Backend/controllers/plugins/manage.php` — GET/POST/PUT/DELETE user plugins
- `Backend/controllers/plugins/marketplace.php` — Browse public plugins
- `Backend/Utils/AdvancedFeatures::installPlugin()` — Install for user
- `Backend/Utils/AdvancedFeatures::getUserPlugins()` — List user's plugins
- Table: `plugins` — Plugin registry
- Table: `user_plugins` — Per-user installations + config
- Table: `plugin_versions` — Version history
- Table: `plugin_events` — Event log

**Frontend:**
- `public/javascripts/plugins-system.js` — PluginSystem class
- Settings panel at `/settings/advanced` (Plugins tab)
- Marketplace view with install buttons
- Config modal for each plugin

### Plugin Manifest

```json
{
    "id": "unique-id",
    "name": "Plugin Name",
    "version": "1.0.0",
    "author": "username",
    "description": "What it does",
    "permissions": [
        "read_threads",
        "read_user_profile",
        "modify_ui"
    ],
    "hooks": [
        "page_load",
        "thread_view",
        "comment_render"
    ],
    "code": "registerHook('page_load', () => { ... })"
}
```

### Available Hooks

```
page_load              — Fired on every page load
thread_view            — When viewing a thread
comment_render         — When rendering comment
message_send           — Before sending message
user_menu              — Add user menu items
thread_actions         — Add thread action buttons
profile_view           — When viewing profile
```

### Plugin API

```javascript
registerHook(hookName, callback)    // Register hook handler
getConfig()                         // Get plugin config
API.showNotification(msg)           // Show success message
API.getUser()                       // Fetch current user
API.registerKeyboardShortcut(...)   // Register custom shortcut
```

### Usage Example

```javascript
// my-plugin.js
registerHook('page_load', () => {
    console.log('Plugin loaded!');
});

registerHook('thread_view', (threadData) => {
    // Modify thread before display
    threadData.customField = 'value';
    return threadData;
});

API.registerKeyboardShortcut('p+c', () => {
    API.showNotification('Custom shortcut triggered!');
});
```

### Key Files

| File | Purpose |
|------|---------|
| `database/09_advanced_features.sql` | Schema (plugins, user_plugins, plugin_versions) |
| `Backend/Utils/AdvancedFeatures.php` | Plugin management methods |
| `Backend/controllers/plugins/manage.php` | Plugin install/uninstall/config |
| `Backend/controllers/plugins/marketplace.php` | Public plugin listing |
| `public/javascripts/plugins-system.js` | Frontend manager + sandbox |
| `frontend/views/settings/advanced.view.php` | Plugin UI tabs |

---

## 👤 User Suspension

### Overview

Suspend user accounts without deletion. Suspended users cannot login but their data is preserved.

**Features:**
- Suspend with reason
- Unsuspend anytime
- Data preserved during suspension
- Audit log entry
- Login check on signin

### Implementation Details

**Backend:**
- `Backend/controllers/admin/suspend-user.php` — POST admin endpoint
- `Backend/Utils/AdvancedFeatures::suspendUser($userId, $reason)` — Suspend
- `Backend/Utils/AdvancedFeatures::unsuspendUser($userId)` — Unsuspend
- `Backend/Utils/AdvancedFeatures::isSuspended($userId)` — Check status
- Check in `Backend/controllers/auth/signin.php` — Prevent login
- Table: `users` columns — `isSuspended`, `suspendedAt`, `suspendReason`

**Frontend:**
- Admin panel at `/admin/management`
- Suspend Users tab
- User search, reason field, unsuspend button

### Flow

1. Admin navigates to `/admin/management`
2. Selects "Suspend Users" tab
3. Searches for user by username/email
4. Enters suspension reason
5. Clicks "Suspend User"
6. User receives email notification (optional)
7. Next login attempt shows: "Account suspended. Reason: ..."

### Key Files

| File | Purpose |
|------|---------|
| `database/09_advanced_features.sql` | Schema (isSuspended, suspendedAt, suspendReason columns) |
| `Backend/Utils/AdvancedFeatures.php` | suspendUser, unsuspendUser, isSuspended |
| `Backend/controllers/admin/suspend-user.php` | Admin API endpoint |
| `Backend/controllers/auth/signin.php` | Suspension check (updated) |
| `frontend/views/admin/management.view.php` | Admin dashboard |
| `public/javascripts/admin-management.js` | Admin panel logic |

---

## 🚫 Post Hiding

### Overview

Moderators can hide threads and comments without deletion. Hidden posts show a warning message.

**Features:**
- Hide threads or comments
- Unhide when needed
- Hide reason visible to mods
- Soft delete (data preserved)
- Audit log entry
- Filter from public views

### Implementation Details

**Backend:**
- `Backend/controllers/admin/hide-post.php` — POST admin endpoint
- `Backend/Utils/AdvancedFeatures::hideThread()` — Hide thread
- `Backend/Utils/AdvancedFeatures::hideComment()` — Hide comment
- `Backend/Utils/AdvancedFeatures::unhideThread()` — Unhide thread
- `Backend/Utils/AdvancedFeatures::unhideComment()` — Unhide comment
- Table columns added:
  - `threads`: `isHidden`, `hiddenBy`, `hiddenReason`, `hiddenAt`
  - `comments`: `isHidden`, `hiddenBy`, `hiddenReason`, `hiddenAt`

**Frontend:**
- Admin panel at `/admin/management`
- Hide Posts tab
- Post type selector (thread/comment)
- Post ID input
- Reason textarea
- Hidden posts list

### Display Logic

```php
// When rendering posts
if ($post['isHidden'] && !$isModerator) {
    // Show: "This post has been hidden by moderators. Reason: ..."
} else {
    // Show normal post
}
```

### Key Files

| File | Purpose |
|------|---------|
| `database/09_advanced_features.sql` | Schema (isHidden, hiddenBy, hiddenReason, hiddenAt) |
| `Backend/Utils/AdvancedFeatures.php` | hideThread, unhideThread, hideComment, unhideComment |
| `Backend/controllers/admin/hide-post.php` | Admin API endpoint |
| `frontend/views/admin/management.view.php` | Admin dashboard |
| `public/javascripts/admin-management.js` | Admin panel logic |

---

## Database Schema

### New Tables

```sql
-- User Suspension
ALTER TABLE users ADD COLUMN isSuspended BOOLEAN DEFAULT FALSE;
ALTER TABLE users ADD COLUMN suspendedAt TIMESTAMP NULL;
ALTER TABLE users ADD COLUMN suspendReason TEXT NULL;

-- Post Hiding
ALTER TABLE threads ADD COLUMN isHidden BOOLEAN DEFAULT FALSE;
ALTER TABLE threads ADD COLUMN hiddenBy CHAR(36) NULL;
ALTER TABLE threads ADD COLUMN hiddenReason TEXT NULL;
ALTER TABLE threads ADD COLUMN hiddenAt TIMESTAMP NULL;

ALTER TABLE comments ADD COLUMN isHidden BOOLEAN DEFAULT FALSE;
ALTER TABLE comments ADD COLUMN hiddenBy CHAR(36) NULL;
ALTER TABLE comments ADD COLUMN hiddenReason TEXT NULL;
ALTER TABLE comments ADD COLUMN hiddenAt TIMESTAMP NULL;

-- Themes
CREATE TABLE themes { /* see 09_advanced_features.sql */ }
CREATE TABLE user_theme_preferences { /* see 09_advanced_features.sql */ }

-- Plugins
CREATE TABLE plugins { /* see 09_advanced_features.sql */ }
CREATE TABLE user_plugins { /* see 09_advanced_features.sql */ }
CREATE TABLE plugin_versions { /* see 09_advanced_features.sql */ }

-- Keyboard Shortcuts
CREATE TABLE user_shortcuts { /* see 09_advanced_features.sql */ }
CREATE TABLE default_shortcuts { /* see 09_advanced_features.sql */ }

-- Drafts
CREATE TABLE drafts { /* see 09_advanced_features.sql */ }

-- Audit Log
CREATE TABLE audit_log { /* see 09_advanced_features.sql */ }
```

### Indexes

```sql
CREATE INDEX idx_users_suspended ON users(isSuspended);
CREATE INDEX idx_threads_hidden ON threads(isHidden);
CREATE INDEX idx_comments_hidden ON comments(isHidden);
CREATE INDEX idx_themes_public ON themes(isPublic);
CREATE INDEX idx_plugins_verified ON plugins(verified);
CREATE INDEX idx_audit_action ON audit_log(action);
```

---

## API Reference

### Keyboard Shortcuts

```
GET    /settings/shortcuts              — Get user shortcuts
POST   /settings/shortcuts              — Update shortcut
DELETE /settings/shortcuts              — Reset to default
```

### Drafts

```
POST   /drafts/save                     — Save draft
GET    /drafts/retrieve                 — Get drafts
```

### Themes

```
GET    /settings/theme                  — Get user's themes
POST   /settings/theme                  — Set active theme
PUT    /settings/theme                  — Create theme (level 4+)
DELETE /settings/theme                  — Delete theme
```

### Plugins

```
GET    /plugins/manage                  — List user's plugins
POST   /plugins/manage                  — Install plugin
PUT    /plugins/manage                  — Update plugin config
DELETE /plugins/manage                  — Uninstall plugin
GET    /plugins/marketplace             — Browse public plugins
```

### Admin

```
POST   /admin/suspend-user              — Suspend/unsuspend user
POST   /admin/hide-post                 — Hide/unhide post
GET    /admin/management                — Admin dashboard
```

---

## Security Considerations

### Keyboard Shortcuts
- No XSS vectors (keys are validated regex)
- No SQL injection (prepared statements)
- Limited to 50 char keys

### Draft Auto-Save
- LocalStorage is per-domain (XSS-safe)
- Server-side storage validated before save
- 10MB max per user

### Themes
- CSS variables only (no JavaScript)
- Level 4+ access control
- 10KB custom CSS limit
- Safe injection via style element

### Plugins
- Code runs in sandboxed Function context
- No access to credentials
- Whitelisted API only
- Code hash verification
- Admin verification required

### User Suspension
- Non-destructive (data preserved)
- Checked every login
- Audit logged

### Post Hiding
- Non-destructive (data preserved)
- Soft delete pattern
- Audit logged
- Mod-only visibility

---

## Testing Checklist

- [ ] Create and switch themes
- [ ] Custom CSS applies correctly
- [ ] Keyboard shortcuts work (j/k, g+p, ?)
- [ ] Draft auto-saves every 30s
- [ ] Drafts restore on page reload
- [ ] Install and configure plugin
- [ ] Plugin hooks fire correctly
- [ ] Suspend user, verify login blocked
- [ ] Unsuspend user, verify login works
- [ ] Hide thread, verify shows warning
- [ ] Unhide thread, verify appears normally
- [ ] Admin audit log records all actions

---

## Future Enhancements

1. **Shared Themes** — Allow users to share/publish themes
2. **Plugin Marketplace** — Full plugin store with ratings
3. **Macro Recording** — Record keyboard macro sequences
4. **Draft Versioning** — Keep all draft versions
5. **Theme Import/Export** — JSON export for sharing
6. **Advanced Plugin API** — More hooks and capabilities
7. **Suspension Appeals** — User appeal process
8. **Post Unhiding** — Allow mods to unhide

---

## Support

For issues or questions:
1. Check the relevant controller/utility file
2. Review database schema in `09_advanced_features.sql`
3. Check JavaScript console for errors
4. Review audit logs at `/admin/management`
