<?php
use Backend\Core\App;

$db = App::container()->resolve('Core\Database');
$cache = App::container()->resolve('Core\Cache');
$params = getQueryParams();

$commentId = isset($params['id']) ? $params['id'] : null;

$stmt = $db->query("SELECT * FROM comments WHERE id = :id AND isDeleted = 0", [":id" => $commentId]);
$comment = $db->getOne($stmt);

// For parent navigation, we need to find the ancestor comment 5 levels up
$parentNavigationId = null;
$currentCommentId = $commentId;
$currentComment = $comment;
$levelsToNavigate = 5;
$currentLevel = 0;

// Traverse up the comment tree to find the appropriate ancestor
while ($currentLevel < $levelsToNavigate && $currentComment && !empty($currentComment['parentCommentId'])) {
    $stmt = $db->query("SELECT * FROM comments WHERE id = :id AND isDeleted = 0", [":id" => $currentComment['parentCommentId']]);
    $currentComment = $db->getOne($stmt);
    
    if ($currentComment) {
        $currentCommentId = $currentComment['id'];
        $currentLevel++;
        
        // When we've reached the 5th level or there are no more parents, set this as our navigation target
        if ($currentLevel == $levelsToNavigate || empty($currentComment['parentCommentId'])) {
            $parentNavigationId = $currentCommentId;
        }
    } else {
        break;
    }
}

// If we couldn't go back 5 levels (or at all), we'll go to the main thread
$navigateToMainThread = ($parentNavigationId === null);

$stmt = $db->query("SELECT locked, id FROM threads WHERE id = :id", [":id" => $comment['threadId']]);
$thread = $db->getOne($stmt);

view("threads/comment-page.view.php", [
    'thread' => $thread,
    'comment' => $comment,
    'parentNavigationId' => $parentNavigationId,
    'navigateToMainThread' => $navigateToMainThread,
    'levelsNavigated' => $currentLevel
]);