# Architecture

## Overview

Deception Forum is a custom PHP MVC application with no third-party framework. Every layer — routing, middleware, DI container, caching — is hand-built.

```
Browser
  │
  ▼
index.php          ← single entry point; session init, security logging, expiry check
  │
  ▼
Router             ← matches URI + method → requires controller file, runs middleware chain
  │
  ├── Middleware   ← auth, guest, partial_auth, admin, username_rate_limit
  │
  ▼
Controller         ← plain PHP script in Backend/controllers/**
  │
  ├── Database     ← PDO wrapper with connection pool
  ├── Cache        ← file-based key/value store
  ├── Mailer       ← PHPMailer wrapper; sends from email_queue/
  └── view()       ← renders frontend/views/**
```

---

## Entry Point (`index.php`)

Responsibilities in order:

1. Define `SESSION_LIFETIME_SECONDS` constant (150 min), set `gc_maxlifetime` and `session_set_cookie_params` (HttpOnly, Secure, SameSite=Strict), start session.
2. Register the PSR-4-style autoloader (`namespace\Sub\Class` → `namespace/Sub/Class.php`).
3. Bootstrap the DI container (`Backend/Core/bootstrap.php`) — binds `Core\Database`, `Core\Cache`, `Core\Mailer`, `Core\TemplateLoader`.
4. Log every request to `logs/security_YYYY-MM-DD_HH.log`; suspicious patterns also go to `logs/threats_YYYY-MM-DD.log`.
5. Check session expiry for authenticated users; redirect to `/verify-totp?action=renew` on expiry.
6. Instantiate `Router`, require `Backend/Routes/routes.php`, dispatch.

---

## Routing

`Backend/Routes/Router.php` stores routes as an array of `[uri, method, controller, middleware[]]`.

```php
$router->get('/threads', 'threads/all.php')->only('auth');
$router->post('/comment', 'comments/create.php')->only('auth');
```

- Route matching is **exact string** only — no dynamic segments.
- Middleware is applied in order: global → group → route-specific.
- `->only($key)` and `->middleware($key)` both push onto the route's middleware array.
- On match, `require base_path('Backend/controllers/' . $controller)` executes the file.
- No match → `abort(404)`.

---

## Dependency Injection

`Backend/Core/Container.php` is a simple closure-based DI container.

```php
App::resolve('Core\Database')  // returns singleton-per-request Database instance
App::resolve('Core\Cache')
App::resolve('Core\Mailer')
App::resolve('Core\TemplateLoader')
```

Bindings are registered in `Backend/Core/bootstrap.php`. `.env` is parsed there (not via `loadEnv()`).

---

## Controllers

Each controller is a plain PHP file `require`d inside `Router::route()`. It has access to:
- All globals set in `index.php` (`$router`, `$uri`, `$method`, `$sessionLifetime`, etc.)
- All functions from `Backend/Utils/functions.php`
- Services via `App::resolve()`
- Session via `$_SESSION`

Controllers call `view()` to render HTML or `sendJsonResponse()` for AJAX endpoints.

---

## Views

`view(string $path, array $args)` maps to `frontend/views/$path`. The `$args` array is `extract()`ed into local variables before the view is required.

Views compose partials via `require`:
```
frontend/views/
  partials/
    navbar.php     ← nav + notification polling (accessLevel >= 5 only)
    footer.php     ← NotificationClient init (accessLevel >= 5 only), JS includes
  errors/
    404.php, 401.php, ...
  auth/
  threads/
  chats/
  ...
```

---

## Background Workers

Both are standalone PHP scripts that bootstrap the app manually (no web server).

| Script | Purpose | How to run |
|--------|---------|------------|
| `mail-worker.php` | Process `Backend/Core/email_queue/*.json` and send via Mailer | `php mail-worker.php` (cron or manual) |
| `notification-cleanup.php` | Delete notifications older than N days | `php notification-cleanup.php` |

---

## File-based Cache (`Backend/Core/Cache.php`)

Stores serialized PHP values in `Backend/Core/cache/` as `md5($key).cache` files. Each file contains `['value' => ..., 'expiration' => unix_timestamp]`.

Used for: username-generation rate limiting per IP (7 requests/hour window).

---

## Email Queue

`queueEmail($to, $subject, $body)` writes a JSON job to `Backend/Core/email_queue/`. The mail worker processes and deletes jobs. Emails are never sent synchronously in the request path.
