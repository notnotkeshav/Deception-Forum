<?php

namespace Backend\Routes;

use Backend\Middleware\Middleware;

class Router
{
   protected $routes = [];
   protected $globalMiddleware = [];
   protected $routeGroups = [];
   protected $currentGroupMiddleware = [];

   public function add($method, $uri, $controller)
   {
      $this->routes[] = [
         "uri" => $uri,
         "method" => $method,
         "controller" => $controller,
         "middleware" => [],
         "group_middleware" => $this->currentGroupMiddleware
      ];

      return $this;
   }

   public function get($uri, $controller)
   {
      return $this->add("GET", $uri, $controller);
   }

   public function post($uri, $controller)
   {
      return $this->add("POST", $uri, $controller);
   }

   public function delete($uri, $controller)
   {
      return $this->add("DELETE", $uri, $controller);
   }

   public function put($uri, $controller)
   {
      return $this->add("PUT", $uri, $controller);
   }

   public function patch($uri, $controller)
   {
      return $this->add("PATCH", $uri, $controller);
   }

   /**
    * Add single middleware to the last route
    * @param string $key Middleware key
    * @return $this
    */
   public function only($key)
   {
      $lastRouteIndex = array_key_last($this->routes);
      if ($lastRouteIndex !== null) {
         $this->routes[$lastRouteIndex]['middleware'][] = $key;
      }
      return $this;
   }

   /**
    * Add multiple middlewares to the last route
    * @param array|string $middlewares Array of middleware keys or single middleware key
    * @return $this
    */
   public function middleware($middlewares)
   {
      $lastRouteIndex = array_key_last($this->routes);
      if ($lastRouteIndex !== null) {
         if (is_string($middlewares)) {
            $middlewares = [$middlewares];
         }

         $this->routes[$lastRouteIndex]['middleware'] = array_merge(
            $this->routes[$lastRouteIndex]['middleware'],
            $middlewares
         );
      }
      return $this;
   }

   /**
    * Add global middleware that applies to all routes
    * @param array|string $middlewares
    * @return $this
    */
   public function globalMiddleware($middlewares)
   {
      if (is_string($middlewares)) {
         $middlewares = [$middlewares];
      }

      $this->globalMiddleware = array_merge($this->globalMiddleware, $middlewares);
      return $this;
   }

   /**
    * Create a route group with shared middleware
    * @param array|string $middlewares
    * @param callable $callback
    * @return $this
    */
   public function group($middlewares, callable $callback)
   {
      if (is_string($middlewares)) {
         $middlewares = [$middlewares];
      }

      // Save current group middleware
      $previousGroupMiddleware = $this->currentGroupMiddleware;

      // Set new group middleware (merge with existing)
      $this->currentGroupMiddleware = array_merge($this->currentGroupMiddleware, $middlewares);

      // Execute the callback (routes defined in the group)
      $callback($this);

      // Restore previous group middleware
      $this->currentGroupMiddleware = $previousGroupMiddleware;

      return $this;
   }

   /**
    * Create a prefix group with middleware
    * @param string $prefix
    * @param array|string $middlewares
    * @param callable $callback
    * @return $this
    */
   public function prefixGroup($prefix, $middlewares, callable $callback)
   {
      if (is_string($middlewares)) {
         $middlewares = [$middlewares];
      }

      // Save current state
      $previousGroupMiddleware = $this->currentGroupMiddleware;
      $routesBeforeGroup = count($this->routes);

      // Set group middleware
      $this->currentGroupMiddleware = array_merge($this->currentGroupMiddleware, $middlewares);

      // Execute callback
      $callback($this);

      // Add prefix to all routes created in this group
      for ($i = $routesBeforeGroup; $i < count($this->routes); $i++) {
         $this->routes[$i]['uri'] = rtrim($prefix, '/') . '/' . ltrim($this->routes[$i]['uri'], '/');
      }

      // Restore previous state
      $this->currentGroupMiddleware = $previousGroupMiddleware;

      return $this;
   }

   /**
    * Apply middleware only to specific HTTP methods
    * @param array $methods
    * @param array|string $middlewares
    * @return $this
    */
   public function onlyMethods($methods, $middlewares)
   {
      if (is_string($methods)) {
         $methods = [$methods];
      }
      if (is_string($middlewares)) {
         $middlewares = [$middlewares];
      }

      $lastRouteIndex = array_key_last($this->routes);
      if ($lastRouteIndex !== null) {
         $route = &$this->routes[$lastRouteIndex];
         if (in_array($route['method'], $methods)) {
            $route['middleware'] = array_merge($route['middleware'], $middlewares);
         }
      }

      return $this;
   }

   /**
    * Skip middleware for the last route
    * @param array|string $middlewares
    * @return $this
    */
   public function without($middlewares)
   {
      if (is_string($middlewares)) {
         $middlewares = [$middlewares];
      }

      $lastRouteIndex = array_key_last($this->routes);
      if ($lastRouteIndex !== null) {
         $route = &$this->routes[$lastRouteIndex];

         // Remove specified middlewares from route middleware
         $route['middleware'] = array_diff($route['middleware'], $middlewares);

         // Remove from group middleware for this route
         $route['skip_middleware'] = isset($route['skip_middleware'])
            ? array_merge($route['skip_middleware'], $middlewares)
            : $middlewares;
      }

      return $this;
   }

   /**
    * Route the request
    * @param string $uri
    * @param string $method
    */
   public function route($uri, $method)
   {
      foreach ($this->routes as $route) {
         if ($this->matchRoute($route['uri'], $uri) && $route['method'] === $method) {

            // Collect all middleware that should be applied
            $allMiddleware = $this->collectMiddleware($route);

            // Apply middleware in order
            $this->applyMiddleware($allMiddleware);

            // If all middleware passes, execute the controller
            return require base_path('Backend/controllers/' . $route['controller']);
         }
      }

      $this->abort();
   }

   /**
    * Check if route pattern matches the URI
    * @param string $routePattern
    * @param string $uri
    * @return bool
    */
   protected function matchRoute($routePattern, $uri)
   {
      // Simple exact match for now
      // You can enhance this to support route parameters like /user/{id}
      return $routePattern === $uri;
   }

   /**
    * Collect all middleware for a route in proper order
    * @param array $route
    * @return array
    */
   protected function collectMiddleware($route)
   {
      $allMiddleware = [];

      // 1. Global middleware (applied to all routes)
      $allMiddleware = array_merge($allMiddleware, $this->globalMiddleware);

      // 2. Group middleware (from route groups)
      $allMiddleware = array_merge($allMiddleware, $route['group_middleware']);

      // 3. Route-specific middleware
      $allMiddleware = array_merge($allMiddleware, $route['middleware']);

      // 4. Remove skipped middleware
      if (isset($route['skip_middleware'])) {
         $allMiddleware = array_diff($allMiddleware, $route['skip_middleware']);
      }

      // Remove duplicates while preserving order
      return array_values(array_unique($allMiddleware));
   }

   /**
    * Apply middleware in sequence
    * @param array $middlewares
    */
   protected function applyMiddleware($middlewares)
   {
      foreach ($middlewares as $middleware) {
         try {
            Middleware::resolve($middleware);
         } catch (\Exception $e) {
            // Log middleware error
            error_log("Middleware '$middleware' failed: " . $e->getMessage());

            // Handle middleware failure based on type
            if ($this->isAuthMiddleware($middleware)) {
               $this->abort(401); // Unauthorized
            } elseif ($this->isPermissionMiddleware($middleware)) {
               $this->abort(403); // Forbidden
            } else {
               $this->abort(500); // Internal server error
            }
         }
      }
   }

   /**
    * Check if middleware is authentication-related
    * @param string $middleware
    * @return bool
    */
   protected function isAuthMiddleware($middleware)
   {
      $authMiddlewares = ['auth', 'guest', 'session', 'totp'];
      return in_array($middleware, $authMiddlewares);
   }

   /**
    * Check if middleware is permission-related
    * @param string $middleware
    * @return bool
    */
   protected function isPermissionMiddleware($middleware)
   {
      $permissionMiddlewares = ['admin', 'moderator', 'user', 'verified'];
      return in_array($middleware, $permissionMiddlewares);
   }

   /**
    * Get all routes with their middleware
    * @return array
    */
   public function getRoutes()
   {
      return array_map(function ($route) {
         $route['all_middleware'] = $this->collectMiddleware($route);
         return $route;
      }, $this->routes);
   }

   /**
    * Get routes by middleware
    * @param string $middleware
    * @return array
    */
   public function getRoutesByMiddleware($middleware)
   {
      return array_filter($this->routes, function ($route) use ($middleware) {
         $allMiddleware = $this->collectMiddleware($route);
         return in_array($middleware, $allMiddleware);
      });
   }

   /**
    * Debug: Print all routes with their middleware
    */
   public function printRoutesWithMiddleware()
   {
      echo "<h3>Routes with Middleware:</h3>";
      foreach ($this->routes as $index => $route) {
         $allMiddleware = $this->collectMiddleware($route);
         echo "<div style='margin-bottom: 10px; padding: 10px; border: 1px solid #ccc;'>";
         echo "<strong>{$route['method']} {$route['uri']}</strong><br>";
         echo "Controller: {$route['controller']}<br>";
         echo "Middleware: " . (empty($allMiddleware) ? 'None' : implode(', ', $allMiddleware)) . "<br>";
         echo "</div>";
      }
      die();
   }

   public function previousURL()
   {
      return $_SERVER['HTTP_REFERER'] ?? '/';
   }

   public function printRoutesUri()
   {
      foreach ($this->routes as $route) {
         echo $route['uri'] . "<br>";
      }
      die();
   }

   protected function abort($code = 404)
   {
      http_response_code($code);
      view("errors/{$code}.php");
      die();
   }

   /**
    * Register routes from a file or closure
    * @param string|callable $routes
    * @return $this
    */
   public function register($routes)
   {
      if (is_string($routes) && file_exists($routes)) {
         require $routes;
      } elseif (is_callable($routes)) {
         $routes($this);
      }

      return $this;
   }

   /**
    * Clear all routes (useful for testing)
    */
   public function clearRoutes()
   {
      $this->routes = [];
      $this->globalMiddleware = [];
      $this->currentGroupMiddleware = [];
   }
}
