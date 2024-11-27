<?php
// General Routes
$router->get("/", "home.php");
// Auth Routes
$router->get("/signup", "auth/signup.php")->only('guest');
$router->post("/signup", "auth/signup.php")->only('guest');
$router->get("/signin", "auth/signin.php")->only('guest');
$router->post("/signin", "auth/signin.php")->only('guest');
$router->post("/signout", "auth/signout.php")->only('auth');
$router->get("/generate_invite_code", "auth/invite.php")->only('auth');
$router->post("/generate_invite_code", "auth/invite.php")->only('auth');
$router->get("/username", "auth/generate_username.php")->only('guest');
// Thread Routes
$router->get("/threads", "threads/all.php");
$router->get("/threads/new", "threads/create.php")->only('auth');
$router->post("/threads", "threads/create.php")->only('auth');
$router->get("/thread", "threads/thread.php")->only('auth');
$router->get("/thread/edit", "threads/edit.php")->only('auth');
$router->put("/thread", "threads/edit.php")->only('auth');
$router->delete("/thread", "threads/thread.php")->only('auth');
// Comment Routes
$router->get("/comments", "threads/comment.php")->only('auth');
$router->post("/comment", "threads/comment.php")->only('auth');
$router->put("/comment", "threads/comment.php")->only('auth');
$router->delete("/comment", "threads/comment.php")->only('auth');
$router->get("/comments/replies", "threads/comment.php")->only('auth');
$router->post("/comment/vote", "threads/comment.php")->only('auth');
// Notification Routes
$router->get("/notifications", "notifications/all.php")->only('auth');
$router->post("/notifications/subscribe", "notifications/subscribe.php")->only('auth');
$router->post("/notifications/unsubscribe", "notifications/unsubscribe.php")->only('auth');
