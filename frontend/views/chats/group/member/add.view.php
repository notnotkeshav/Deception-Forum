<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>⛧ Add Member ⛧</title>
    <style>
        @font-face {
            font-family: 'vamp';
            src: url('/public/fonts/ScaryVampire.ttf') format('truetype');
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background: #000;
            color: #f8f8f8;
            font-family: 'Courier New', monospace;
            min-height: 100vh;
            padding-top: 80px;
        }

        .add-member-wrapper {
            max-width: 600px;
            margin: 2.5rem auto;
            background: #0a0a0a;
            border: 2px solid #960d0d;
            box-shadow: 0 0 25px rgba(150, 13, 13, 0.3);
            padding: 2rem;
        }

        .page-title {
            font-family: 'vamp', sans-serif;
            text-align: center;
            color: #f03;
            font-size: 2rem;
            letter-spacing: 3px;
            margin-bottom: 2rem;
            text-shadow: 0 0 15px rgba(255, 0, 51, 0.6);
            border-bottom: 2px solid #960d0d;
            padding-bottom: 1rem;
        }

        .group-name{
            font-size: 1.2rem;
            color: #ffa0a0;
            font-family: 'Courier New', Courier, monospace;
        }

        .form-group {
            margin-bottom: 1.8rem;
        }

        .form-label {
            display: block;
            color: #f03;
            font-weight: bold;
            font-size: 0.95rem;
            margin-bottom: 0.5rem;
            letter-spacing: 1.2px;
            text-transform: uppercase;
        }

        .form-select {
            width: 100%;
            background: #111;
            border: 2px solid #960d0d;
            color: #fff;
            padding: 0.9rem 1rem;
            font-size: 1rem;
            font-family: 'Courier New', monospace;
            transition: all 0.3s;
            cursor: pointer;
        }

        .form-select:focus {
            outline: none;
            border-color: #f03;
            box-shadow: 0 0 15px rgba(255, 0, 51, 0.4);
            background: #1a0000;
        }

        .form-select option {
            background: #111;
            color: #fff;
            padding: 0.5rem;
        }

        .form-select option:disabled {
            color: #555;
        }

        .submit-btn {
            width: 100%;
            background: #1a0000;
            border: 2px solid #960d0d;
            color: #f03;
            font-family: 'vamp', sans-serif;
            font-size: 1.4rem;
            font-weight: bold;
            padding: 0.9rem 2rem;
            margin-top: 1rem;
            cursor: pointer;
            letter-spacing: 2px;
            text-transform: uppercase;
            transition: all 0.3s ease;
            box-shadow: 0 0 20px rgba(150, 13, 13, 0.3);
        }

        .submit-btn:hover {
            background: #960d0d;
            color: #fff;
            box-shadow: 0 0 30px rgba(255, 0, 51, 0.6);
            transform: translateY(-2px);
        }

        .submit-btn:active {
            transform: translateY(0);
        }
    </style>
</head>

<body>
    <?php require(base_path("/frontend/views/partials/navbar.php")); ?>

    <div class="add-member-wrapper">
        <h1 class="page-title">
            <span>⛧Add New Member ⛧</span>
            <br>
            <span class="group-name"><?= htmlspecialchars($heading) ?></span>
        </h1>

        <form action="" method="POST">
            <input type="hidden" name="groupId" value="<?= htmlspecialchars($groupId) ?>">

            <div class="form-group">
                <label for="memberId" class="form-label">Select User to Add:</label>
                <select id="memberId" name="memberId" class="form-select" required>
                    <option value="" disabled selected>-- Select User --</option>
                    <?php foreach ($users as $user): ?>
                        <option value="<?= htmlspecialchars($user['id']) ?>">
                            <?= htmlspecialchars($user['username']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <button type="submit" class="submit-btn">Add Member</button>
        </form>
    </div>
</body>

</html>