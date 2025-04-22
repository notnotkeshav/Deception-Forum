<?php
use Backend\Core\App;

$db = App::container()->resolve('Core\Database');
$cache = App::container()->resolve('Core\Cache');
$params = getQueryParams();

$commentId = isset($params['id']) ? $params['id'] : null;

$stmt = $db->query("SELECT * FROM comments WHERE id = :id AND isDeleted = 0", [":id" => $commentId]);
$comment = $db->getOne($stmt);

$stmt = $db->query("SELECT locked, id FROM threads WHERE id = :id", [":id" => $comment['threadId']]);
$thread = $db->getOne($stmt);

view("threads/comment-page.view.php", [
    'thread' => $thread,
    'comment' => $comment,
]);
