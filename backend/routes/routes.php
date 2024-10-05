<?php
$router->get("/","home.php");
$router->get("/signup","auth/signup.php");
$router->post("/signup","auth/signup.php");
$router->get("/signin","auth/signin.php");
$router->post("/signin","auth/signin.php");
$router->get("/generate_invite_code", "auth/invite.php");
$router->post("/generate_invite_code", "auth/invite.php");
$router->post("/get_username", "auth/generate_username.php");