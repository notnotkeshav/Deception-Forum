<?php

use Backend\Core\App;

$db = App::container()->resolve('Core\Database');
$cache = App::container()->resolve('Core\Cache');

$params = getQueryParams();
$body = getRequestBody();

if ($method === 'GET') {
    // Fetch comments for a specific thread
    if (!isset($params['threadId'])) {
        http_response_code(400);
        echo json_encode(["success" => false, "error" => "Thread ID is required."]);
        exit();
    }

    $threadId = $params['threadId'];

    // Fetch comments from database
    $stmt = $db->query("SELECT * FROM comments WHERE threadId = :threadId AND deleted = 0 ORDER BY createdAt DESC", [":threadId" => $threadId]);
    $comments = $db->getAll($stmt);

    echo json_encode(["success" => true, "comments" => $comments]);
    exit();
} elseif ($method === 'POST') {
    // Handle comment submission
    if (!isset($_POST['threadId'], $_POST['content'])) {
        http_response_code(400);
        echo json_encode(["success" => false, "error" => "Thread ID and content are required."]);
        exit();
    }

    $userId = $_SESSION['userId'];
    $threadId = $_POST['threadId'];
    $content = $_POST['content'];
    $parentCommentId = isset($_POST['parentCommentId']) ? $_POST['parentCommentId'] : null;

    $stmt = $db->query(
        "INSERT INTO comments (userId, threadId, content, parentCommentId, createdAt) 
         VALUES (:userId, :threadId, :content, :parentCommentId, NOW())",
        [
            ":userId" => $userId,
            ":threadId" => $threadId,
            ":content" => $content,
            ":parentCommentId" => $parentCommentId
        ]
    );

    if ($stmt) {
        $commentId = $db->lastInsertId();
        $cache->delete("thread:" . $threadId);
        header("location: /thread?id=$threadId");
        // echo json_encode(["success" => true, "comment" => ["id" => $commentId, "userId" => $userId, "content" => $content, "createdAt" => date('Y-m-d H:i:s')]]);
        exit();
    } else {
        http_response_code(500);
        echo json_encode(["success" => false, "error" => "Failed to add comment."]);
    }
} elseif ($method === 'DELETE') {
    // Handle comment deletion (soft delete)
    if (!isset($body['commentId'])) {
        http_response_code(400);
        echo json_encode(["success" => false, "error" => "Comment ID is required."]);
        exit();
    }

    $userId = $_SESSION['userId'];
    $commentId = $body['commentId'];

    $stmt = $db->query("SELECT userId, threadId FROM comments WHERE id = :id AND deleted = 0", [":id" => $commentId]);
    $existingComment = $db->getOne($stmt);

    if (!$existingComment) {
        http_response_code(404);
        echo json_encode(["success" => false, "error" => "Comment not found or already deleted."]);
        exit();
    }

    if ($existingComment['userId'] !== $userId) {
        http_response_code(403);
        echo json_encode(["success" => false, "error" => "You do not have permission to delete this comment."]);
        exit();
    }

    $stmt = $db->query(
        "UPDATE comments SET deleted = 1 WHERE id = :id",
        [":id" => $commentId]
    );

    if ($stmt) {
        $cache->delete("thread:" . $existingComment['threadId']);
        echo json_encode(["success" => true]);
        exit();
    } else {
        http_response_code(500);
        echo json_encode(["success" => false, "error" => "Failed to delete comment."]);
    }
} elseif ($method === 'POST' && isset($_POST['action']) && $_POST['action'] === 'vote') {
    if (!isset($_POST['commentId'], $_POST['voteType'])) {
        http_response_code(400);
        echo json_encode(["success" => false, "error" => "Comment ID and vote type (upvote/downvote) are required."]);
        exit();
    }

    $commentId = $_POST['commentId'];
    $voteType = $_POST['voteType'];

    if (!in_array($voteType, ['upvote', 'downvote'])) {
        http_response_code(400);
        echo json_encode(["success" => false, "error" => "Invalid vote type."]);
        exit();
    }

    $stmt = $db->query("SELECT upvoteCount, downvoteCount FROM comments WHERE id = :id", [":id" => $commentId]);
    $comment = $db->getOne($stmt);

    if (!$comment) {
        http_response_code(404);
        echo json_encode(["success" => false, "error" => "Comment not found."]);
        exit();
    }

    if ($voteType === 'upvote') {
        $stmt = $db->query(
            "UPDATE comments SET upvoteCount = upvoteCount + 1 WHERE id = :id",
            [":id" => $commentId]
        );
    } else {
        $stmt = $db->query(
            "UPDATE comments SET downvoteCount = downvoteCount + 1 WHERE id = :id",
            [":id" => $commentId]
        );
    }

    if ($stmt) {
        $cache->delete("thread:" . $comment['threadId']);
        echo json_encode(["success" => true, "message" => ucfirst($voteType) . " successful."]);
        exit();
    } else {
        http_response_code(500);
        echo json_encode(["success" => false, "error" => "Failed to update vote."]);
        exit();
    }
} else {
    http_response_code(405);
    echo json_encode(["success" => false, "error" => "Invalid HTTP method."]);
    exit();
}
