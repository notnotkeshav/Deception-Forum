<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($heading) ?></title>
</head>
<body>
    <h1><?= htmlspecialchars($heading) ?></h1>

    <form action="/private-chat/new" method="POST">
        <label for="recipientId">Select a User:</label>
        <select id="recipientId" name="recipientId" required>
            <option value="" disabled selected>Choose a user</option>
            <?php foreach ($users as $user): ?>
                <option value="<?= htmlspecialchars($user['id']) ?>">
                    <?= htmlspecialchars($user['username']) ?>
                </option>
            <?php endforeach; ?>
        </select>
        <button type="submit">Start Chat</button>
    </form>
</body>
</html>
