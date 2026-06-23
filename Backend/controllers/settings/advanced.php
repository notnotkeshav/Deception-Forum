<?php

// GET /settings/advanced — Advanced settings page

if (!isset($_SESSION['userId'])) {
    redirect('/signin');
}

view("settings/advanced.view.php", [
    "title" => "Advanced Settings",
]);
