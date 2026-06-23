<?php
// General Routes
$router->get("/", "home.php"); // Render the homepage

// Authentication Routes
$router->get("/signup", "auth/signup.php")->middleware('guest');
$router->post("/signup", "auth/signup.php")->middleware('guest');
$router->get("/signin", "auth/signin.php")->middleware('guest');
$router->post("/signin", "auth/signin.php")->middleware('guest');
$router->get("/username", "auth/generate_username.php")->middleware('guest')->middleware('username_rate_limit');

// Updated Routes
$router->post("/signout", "auth/signout.php")->only('auth'); // Allow both full and partial auth users to sign out
$router->get("/generate_invite_code", "auth/invite.php")->middleware('auth'); // Keep as auth - only fully authenticated users
$router->post("/generate_invite_code", "auth/invite.php")->middleware('auth'); // Keep as auth - only fully authenticated users

// TOTP Routes (Updated)
$router->get("/totp-setup", "auth/totp_setup.php"); // Allow both full and partial auth for TOTP setup
$router->post("/totp-setup", "auth/totp_setup.php"); // Allow both full and partial auth for TOTP setup

$router->get("/verify-totp", "auth/verify_totp.php");
$router->post("/verify-totp", "auth/verify_totp.php");

// Session Management Routes
$router->get("/session/check", "auth/session_check.php")->only('auth'); // Check session status
$router->post("/session/renew", "auth/session_renew.php")->only('auth'); // Renew session via TOTP

// Password Management Routes
$router->get("/change-password", "auth/password/change.php")->only('auth'); // Display change password form (authenticated users only)
$router->put("/change-password", "auth/password/change.php")->only('auth'); // Process password change request
$router->get("/forgot-password", "auth/password/forgot.php")->only('guest'); // Display forgot password form (guests only)
$router->post("/forgot-password", "auth/password/forgot.php")->only('guest'); // Process forgot password request
$router->get("/reset-password", "auth/password/reset.php")->only('guest'); // Display password reset form (guests only) - uses reset token
$router->patch("/reset-password", "auth/password/reset.php")->only('guest'); // Process password reset request using token

// Thread Management Routes
$router->get("/threads", "threads/all.php")->only('auth'); // Display all available threads
$router->get("/threads/new", "threads/create.php")->only('auth'); // Display form to create a new thread (authenticated users only)
$router->get("/thread/comments", "threads/comment-page.php")->only('auth'); // Display form to create a new thread (authenticated users only)
$router->post("/threads", "threads/create.php")->only('auth'); // Handle new thread submission
$router->get("/thread", "threads/thread.php")->only('auth'); // View a specific thread by ID (authenticated users only)
$router->get("/thread/edit", "threads/edit.php")->only('auth'); // Display thread edit form (authenticated users only)
$router->put("/thread", "threads/edit.php")->only('auth'); // Process thread update request
$router->delete("/thread", "threads/thread.php")->only('auth'); // Handle thread deletion request
$router->put("/thread/vote", "threads/vote.php")->only('auth'); // Cast a vote on a thread (authenticated users only)

// Comment Management Routes
$router->get("/comments", "comments/comment.php")->only('auth'); // View all comments (authenticated users only)
$router->get("/comments/load-more", "comments/more.php")->only('auth'); // 
$router->post("/comment", "comments/create.php")->only('auth'); // Submit a new comment (authenticated users only)
$router->put("/comment/edit", "comments/edit.php")->only('auth'); // Edit an existing comment (authenticated users only)
$router->delete("/comment", "comments/comment.php")->only('auth'); // Delete a comment (authenticated users only)
$router->put("/comment/vote", "comments/vote.php")->only('auth'); // Cast a vote on a comment (authenticated users only)

// Notification Routes
$router->get("/notifications", "notifications/index.php")->only('auth'); // loads the notification page
$router->post("/notifications", "notifications/index.php")->only('auth'); // Handle AJAX notification actions
$router->get("/notifications/poll", "notifications/poll.php")->only('auth'); // Long polling endpoint
$router->get("/notifications/settings", "notifications/settings.php")->only('auth'); // notification settings page
$router->post("/notifications/settings", "notifications/settings.php")->only('auth'); // update notification settings
$router->put("/notifications/settings", "notifications/settings.php")->only('auth'); // AJAX update individual setting
$router->post("/notifications/mark-read", "notifications/mark-read.php")->only('auth'); // Mark notification as read
$router->get("/notifications/count", "notifications/count.php")->only('auth'); // Get unread count
$router->post("/notifications/subscribe", "notifications/subscribe.php")->only('auth'); // Subscribe to notifications
$router->post("/notifications/unsubscribe", "notifications/unsubscribe.php")->only('auth'); // Unsubscribe from notifications

// Moderator Controls
$router->put("/thread/lock", "moderators/lock.php")->only('admin'); // Lock a thread (admin access only)

// Private Chat Routes
$router->get("/private-chats", "chats/private/all.php")->only('auth'); // Display all private chats
$router->get("/private-chat/new", "chats/private/create.php")->only('auth'); // Display form to initiate a private chat
$router->post("/private-chat/new", "chats/private/create.php")->only('auth'); // Start a new private chat
$router->get("/private-chat", "chats/private/chat.php")->only('auth'); // View a private chat session by ID
$router->get("/private-chat/messages", "chats/private/message/get.php")->only('auth'); // Retrieve messages for a private chat
$router->get("/private-chat/messages/new", "chats/private/message/poll.php")->only('auth'); // Poll for new messages in a private chat
$router->post("/private-chat/message", "chats/private/message/create.php")->only('auth'); // Send a new message in a private chat
$router->put("/private-chat/message", "chats/private/message/edit.php")->only('auth'); // Edit a message in a private chat
$router->delete("/private-chat/message", "chats/private/message/delete.php")->only('auth'); // Delete a message from a private chat
$router->put("/private-chat/message/vote", "chats/private/vote.php")->only('auth'); // Cast a vote on a private chat message

// Group Chat Routes
$router->get("/group-chats", "chats/group/all.php")->only('auth'); // Display all group chats
$router->get("/group-chat/new", "chats/group/create.php")->only('auth'); // Create a new group chat
$router->post("/group-chat/new", "chats/group/create.php")->only('auth'); // Create a new group chat
$router->get("/group-chat", "chats/group/chat.php")->only('auth'); // View a specific group chat by ID
$router->get("/group-chat/messages", "chats/group/message/get.php")->only('auth'); // Retrieve messages for a private chat
$router->get("/group-chat/messages/new", "chats/group/message/poll.php")->only('auth'); // Poll for new messages in a private chat
$router->post("/group-chat/message", "chats/group/message/create.php")->only('auth'); // Send a message in a group chat
$router->put("/group-chat/message", "chats/group/message/edit.php")->only('auth'); // Edit a message in a group chat
$router->delete("/group-chat/message", "chats/group/message/delete.php")->only('auth'); // [Optional] Delete a message in a group chat
$router->put("/group-chat/message/vote", "chats/group/message/vote.php")->only('auth'); // Vote on a group chat message
$router->get("/group-chat/member/add", "chats/group/member/add.php")->only('auth'); // Add a member to a group chat
$router->post("/group-chat/member/add", "chats/group/member/add.php")->only('auth'); // Add a member to a group chat
// $router->put("/group-chat/member/update", "chats/group/member/update.php")->only('auth'); // Update a group chat member's role
// $router->delete("/group-chat/member", "chats/group/member/remove.php")->only('auth'); // Remove a member from a group chat

// User Profile Routes
$router->get("/user", "user/details.php")->only('auth'); // View authenticated user's profile details
$router->get("/profile", "profile/index.php"); // View any user's public profile by username
$router->get("/profile/settings", "profile/settings.php")->only('auth'); // Privacy settings page
$router->post("/profile/settings", "profile/settings.php")->only('auth'); // Update privacy settings
$router->put("/profile/settings", "profile/settings.php")->only('auth'); // AJAX update individual setting

// Captcha Routes
$router->get('/captcha', 'captcha/index.php');
$router->post('/captcha', 'captcha/index.php');

// ============================================
// NEW FEATURES (Reactions, Delete, Edit, Block)
// ============================================

// Reaction Routes
$router->post('/reactions/add', 'reactions/add.php')->only('auth'); // Add/remove reaction to thread or comment

// Comment Routes (Enhanced)
$router->delete('/comment/delete', 'comments/delete.php')->only('auth'); // Delete a comment
$router->post('/comment/delete', 'comments/delete.php')->only('auth'); // Delete a comment (fallback for non-DELETE requests)

// User Blocking Routes
$router->post('/user/block', 'users/block.php')->only('auth'); // Block or unblock a user
$router->get('/user/blocks', 'users/blocks.php')->only('auth'); // List blocked users
