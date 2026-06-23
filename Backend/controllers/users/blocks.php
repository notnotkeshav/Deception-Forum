<?php
/**
 * Get list of blocked users
 * GET /user/blocks
 * Requires: auth
 */

use Backend\Core\App;

$db = App::container()->resolve('Core\Database');

if ($method === 'GET') {
    try {
        $userId = $_SESSION['userId'];

        $stmt = $db->query(
            "SELECT u.id, u.username, u.name, u.profilePic, bu.reason, bu.createdAt
             FROM blocked_users bu
             JOIN users u ON bu.blockedUserId = u.id
             WHERE bu.userId = :uid
             ORDER BY bu.createdAt DESC",
            [':uid' => $userId]
        );

        $blockedUsers = $db->getAll($stmt);

        view("user/blocked-list.view.php", [
            "blockedUsers" => $blockedUsers,
            "totalBlocked" => count($blockedUsers)
        ]);

    } catch (Exception $e) {
        error_log("Get blocked users error: " . $e->getMessage());
        abort(500);
    }
} else {
    abort(405);
}
