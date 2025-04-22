<?php

use Backend\Core\App;

$db = App::container()->resolve('Core\Database');
$cache = App::container()->resolve('Core\Cache');

$params = getQueryParams();

if ($method === 'GET' && isset($params['parentCommentId'])) {
    $parentCommentId = $params['parentCommentId'];
    $page = isset($params['page']) ? (int) $params['page'] : 1; // Page number for pagination
    $perPage = 5; // Number of replies to show per page

    try {
        $stmt = $db->query(
            "SELECT * FROM comments WHERE parentCommentId = :parentCommentId AND isDeleted = 0 ORDER BY createdAt DESC LIMIT :limit OFFSET :offset",
            [
                ":parentCommentId" => $parentCommentId,
                ":limit" => $perPage,
                ":offset" => ($page - 1) * $perPage
            ]
        );
        $replies = $db->getAll($stmt);

        function getReplies($parentId, $db, $depth = 1, $page = 1)
        {
            $perPage = 5; // Number of replies to show per page
            $stmt = $db->query(
                "SELECT * FROM comments WHERE parentCommentId = :parentCommentId AND isDeleted = 0 ORDER BY createdAt DESC LIMIT :limit OFFSET :offset",
                [
                    ":parentCommentId" => $parentId,
                    ":limit" => $perPage,
                    ":offset" => ($page - 1) * $perPage
                ]
            );
            $replies = $db->getAll($stmt);

            foreach ($replies as &$reply) {
                if ($depth < 5) {
                    $reply['replies'] = getReplies($reply['id'], $db, $depth + 1, $page); // Recursive call for deeper replies
                } else {
                    // Instead of fetching more, just indicate that more replies exist
                    $stmtCount = $db->query(
                        "SELECT COUNT(*) as replyCount FROM comments WHERE parentCommentId = :parentCommentId AND isDeleted = 0",
                        [":parentCommentId" => $reply['id']]
                    );
                    $countResult = $db->getOne($stmtCount);
                    $reply['hasMoreReplies'] = $countResult && $countResult['replyCount'] > 0;
                }
            }

            return $replies;
        }

        foreach ($replies as &$reply) {
            $reply['replies'] = getReplies($reply['id'], $db, 1, $page); // Load replies starting from depth 1
        }

        sendJsonResponse(true, "Replies fetched successfully.", ["replies" => $replies], 200);
    } catch (Exception $e) {
        sendJsonResponse(false, "Failed to fetch replies: " . $e->getMessage(), [], 500);
    }
}