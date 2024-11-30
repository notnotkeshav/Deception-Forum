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
$router->get("/comments", "comments/comment.php")->only('auth');
$router->post("/comment", "comments/create.php")->only('auth');
$router->put("/comment/edit", "comments/edit.php")->only('auth');
$router->delete("/comment", "comments/comment.php")->only('auth');
$router->put("/comment/vote", "comments/vote.php")->only('auth');
// Notification Routes
$router->get("/notifications", "notifications/all.php")->only('auth');
$router->post("/notifications/subscribe", "notifications/subscribe.php")->only('auth');
$router->post("/notifications/unsubscribe", "notifications/unsubscribe.php")->only('auth');
// Moderator Locking Routes
$router->put("/thread/lock", "moderators/lock.php")->only('admin');
// Private Message Routes
// Group Message Routes
// Credit Maintaining Routes
// User Profiles Routes
