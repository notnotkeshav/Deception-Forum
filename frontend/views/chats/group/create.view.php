<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>⛧ Create Group Chat ⛧</title>
<link rel="shortcut icon" href="/public/images/favicon.ico" type="image/x-icon">
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

        .chat-container {
            max-width: 600px;
            margin: 3rem auto;
            background: #0a0a0a;
            border: 2px solid #960d0d;
            box-shadow: 0 0 25px rgba(150, 13, 13, 0.3);
            padding: 2.5rem;
        }

        .page-title {
            font-family: 'vamp', sans-serif;
            text-align: center;
            color: #f03;
            font-size: 2.2rem;
            letter-spacing: 3px;
            margin-bottom: 2.5rem;
            text-shadow: 0 0 15px rgba(255, 0, 51, 0.6);
            border-bottom: 2px solid #960d0d;
            padding-bottom: 1rem;
        }

        .form-group {
            margin-bottom: 2rem;
        }

        .form-label {
            display: block;
            color: #f03;
            font-weight: bold;
            font-size: 1rem;
            margin-bottom: 0.6rem;
            letter-spacing: 1.5px;
            text-transform: uppercase;
        }

        .form-input {
            width: 100%;
            background: #111;
            border: 2px solid #960d0d;
            color: #fff;
            padding: 1rem 1.2rem;
            font-size: 1.05rem;
            font-family: 'Courier New', monospace;
            transition: all 0.3s;
        }

        .form-input:focus {
            outline: none;
            border-color: #f03;
            box-shadow: 0 0 15px rgba(255, 0, 51, 0.4);
            background: #1a0000;
        }

        .form-input::placeholder {
            color: #555;
            font-style: italic;
        }

        .submit-btn {
            width: 100%;
            background: #1a0000;
            border: 2px solid #960d0d;
            color: #f03;
            font-family: 'vamp', sans-serif;
            font-size: 1.5rem;
            font-weight: bold;
            padding: 1rem 2rem;
            margin-top: 1.5rem;
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

    <div class="chat-container">
        <h1 class="page-title">⛧ <?= htmlspecialchars($heading) ?> ⛧</h1>

        <form action="" method="POST">
            <div class="form-group">
                <label for="groupName" class="form-label">Group Name:</label>
                <input 
                    type="text" 
                    id="groupName" 
                    name="groupName" 
                    class="form-input" 
                    placeholder="Enter group name..." 
                    required
                    autocomplete="off"
                >
            </div>

            <button type="submit" class="submit-btn">Create Group Chat</button>
        </form>
    </div>
</body>
</html>
