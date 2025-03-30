<?php
// General Routes
$router->get("/", "home.php"); // Homepage route
// Auth Routes
$router->get("/signup", "auth/signup.php")->only('guest'); // Signup page (only accessible by guests) /signup?invite={inviteCode}
$router->post("/signup", "auth/signup.php")->only('guest'); // Handle signup form submission          /signup?invite={inviteCode}
$router->get("/signin", "auth/signin.php")->only('guest'); // Signin page (only accessible by guests) /signin?code={inviteCode}
$router->post("/signin", "auth/signin.php")->only('guest'); // Handle signin form submission          /signup?invite={inviteCode}
$router->post("/signout", "auth/signout.php")->only('auth'); // Handle signout (only accessible by authenticated users)
$router->get("/generate_invite_code", "auth/invite.php")->only('auth'); // Generate invite code page (only for authenticated users)
$router->post("/generate_invite_code", "auth/invite.php")->only('auth'); // Handle invite code generation
$router->get("/username", "auth/generate_username.php")->only('guest'); // Generate username (only for guests)
// Password Routes
$router->get("/change-password", "auth/password/change.php")->only('auth'); // Change password page (only for authenticated users)
$router->put("/change-password", "auth/password/change.php")->only('auth'); // Handle password change request
$router->get("/forgot-password", "auth/password/forgot.php")->only('guest'); // Forgot password page (only for guests)
$router->post("/forgot-password", "auth/password/forgot.php")->only('guest'); // Handle forgot password form submission
$router->get("/reset-password", "auth/password/reset.php")->only('guest'); // Reset password page (only for guests) /reset-password?token={token}
$router->patch("/reset-password", "auth/password/reset.php")->only('guest'); // Handle password reset request        /reset-password?token={token}
// Thread Routes
$router->get("/threads", "threads/all.php"); // List all threads
$router->get("/threads/new", "threads/create.php")->only('auth'); // Create new thread page (only for authenticated users)
$router->post("/threads", "threads/create.php")->only('auth'); // Handle new thread creation
$router->get("/thread", "threads/thread.php")->only('auth'); // View specific thread page    /thread?id=aaa-bbb-ccc-ddd
$router->get("/thread/edit", "threads/edit.php")->only('auth'); // Edit thread page (only for authenticated users) /thread/edit?id=aaa-bbb-ccc-ddd
$router->put("/thread", "threads/edit.php")->only('auth'); // Handle thread update request   /thread/edit?id=aaa-bbb-ccc-ddd
$router->delete("/thread", "threads/thread.php")->only('auth'); // Handle thread deletion
$router->put("/thread/vote", "threads/vote.php")->only('auth'); // Vote on a thread
// Comment Routes
$router->get("/comments", "comments/comment.php")->only('auth'); // View all comments (only for authenticated users)
$router->post("/comment", "comments/create.php")->only('auth'); // Handle comment creation
$router->put("/comment/edit", "comments/edit.php")->only('auth'); // Edit an existing comment
$router->delete("/comment", "comments/comment.php")->only('auth'); // Delete a comment
$router->put("/comment/vote", "comments/vote.php")->only('auth'); // Vote on a comment
// Notification Routes (to be implemented)
$router->get("/notifications", "notifications/all.php")->only('auth'); // View notifications (only for authenticated users)
$router->post("/notifications/subscribe", "notifications/subscribe.php")->only('auth'); // Subscribe to notifications
$router->post("/notifications/unsubscribe", "notifications/unsubscribe.php")->only('auth'); // Unsubscribe from notifications
$router->get("/private-chat/notifications", "notifications/privateChats.php")->only('auth'); // View notifications for private chats
$router->get("/group-chat/notifications", "notifications/groupChats.php")->only('auth'); // View notifications for group chats
// Moderator Locking Routes
$router->put("/thread/lock", "moderators/lock.php")->only('admin'); // Lock a thread (only for admin)
// Private Chat Routes
$router->get("/private-chats", "chats/private/all.php")->only('auth'); // List all private chats
$router->get("/private-chat/new", "chats/private/create.php")->only('auth'); // Start a new private chat
$router->post("/private-chat/new", "chats/private/create.php")->only('auth'); // Start a new private chat
$router->get("/private-chat", "chats/private/chat.php")->only('auth'); // /private-chat?id=aaa-bbb-ccc-ddd
$router->get("/private-chat/messages", "chats/private/message/get.php")->only('auth'); // /private-chat/messages?id=aaa-bbb-ccc-ddd
$router->get("/private-chat/messages/new", "chats/private/message/poll.php")->only('auth'); // /private-chat/messages/new?id=aaa-bbb-ccc-ddd
$router->post("/private-chat/message", "chats/private/message/create.php")->only('auth'); // Send a message in a private chat
$router->put("/private-chat/message", "chats/private/message/edit.php")->only('auth'); // Edit a private chat message
$router->delete("/private-chat/message", "chats/private/message/delete.php")->only('auth'); // Delete a private chat message
$router->put("/private-chat/vote", "chats/private/vote.php")->only('auth'); // Vote on a private chat message
// Group Chat Routes
$router->get("/group-chats", "chats/group/all.php")->only('auth'); // List all group chats
$router->get("/group-chat", "chats/group/chat.php")->only('auth'); // /group-chat?id=aaa-bbb-ccc-ddd
$router->post("/group-chat", "chats/group/create.php")->only('auth'); // Create a new group chat
$router->post("/group-chat/message", "chats/group/message/create.php")->only('auth'); // Send a message in a group chat
$router->put("/group-chat/message", "chats/group/message/edit.php")->only('auth'); // Edit a group chat message
// $router->delete("/group-chat/message", "chats/group/message/delete.php")->only('auth'); // Delete a group chat message
$router->put("/group-chat/vote", "chats/group/vote.php")->only('auth'); // Vote on a group chat message
$router->post("/group-chat/member", "chats/group/member/add.php")->only('auth'); // Add a member to a group chat
$router->put("/group-chat/member", "chats/group/member/update.php")->only('auth'); // Update a member's role in the group chat
$router->delete("/group-chat/member", "chats/group/member/remove.php")->only('auth'); // Remove a member from a group chat
// Credit Maintaining Routes
// User Profiles Routes
$router->get("/user", "user/details.php")->only('auth');  // View user details (only for authenticated users)
