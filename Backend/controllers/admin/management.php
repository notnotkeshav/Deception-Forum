<?php

// GET /admin/management — Admin dashboard

if ($_SESSION['moderator'] !== true) {
    abort(403, ['message' => 'Access denied']);
}

view("admin/management.view.php", [
    "title" => "Admin Management",
]);
