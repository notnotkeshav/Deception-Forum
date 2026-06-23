<?php

// GET /admin/invites-dashboard — Invite management dashboard (superadmin only)

if ($_SESSION['accessLevel'] < 5) {
    abort(403, ['message' => 'Superadmin access required']);
}

view("admin/invites-dashboard.view.php", [
    "title" => "Invite Management",
]);
