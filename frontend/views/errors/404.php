<!DOCTYPE html>
<html lang="en">
  
  <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>⛧ 404 - Not Found ⛧</title>
    <link rel="shortcut icon" href="/public/images/favicon.ico" type="image/x-icon">
    <style>
        @font-face {
          font-family: 'vamp';
            src: url('/public/fonts/ScaryVampire.ttf') format('truetype');
            font-weight: normal;
            font-style: normal;
          }
          
          * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
          }
          
          body {
            font-family: 'Arial', sans-serif;
            background-color: #000;
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            overflow: hidden;
          }
          
          main {
            width: 100vw;
            height: 100vh;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-direction: column;
            text-align: center;
          }
          
          .error-container {
            font-family: 'Courier New', monospace;
            padding: 2rem;
            background-color: rgba(0, 0, 0, 0.8);
            border: 0.125rem solid #ff0033;
            border-radius: 0.5rem;
            box-shadow: 0 0 1rem rgba(255, 0, 0, 0.3);
          }
          
          .error-heading {
            font-size: 4rem;
            font-weight: bolder;
            font-family: 'vamp', sans-serif;
            color: #ff0033;
            text-transform: uppercase;
            letter-spacing: 0.2em;
          }
          
          .error-text {
            font-size: 1.5rem;
            color: #d00000;
            margin: 1.5rem 0;
          }
          
          .no-turn-back {
            color: #faa307;
            font-size: 1.2rem;
            margin-top: 1rem;
          }
          
          .error-links {
            font-size: 1rem;
            color: #ff0033;
            margin-top: 1rem;
          }
          
          .error-links a {
            color: #ff0033;
            text-decoration: none;
            font-weight: bold;
            transition: color 0.3s;
          }
          
          .error-links a:hover {
            color: #ff0000;
          }
          
          .error-message {
            font-size: 1.1rem;
            color: #ffe600ff;
            margin-top: 2rem;
          }
          
          .skull-icon {
            margin: 0 auto;
            border-radius: 50%;
            overflow: hidden;
            width: 100px;
            height: 100px;
          }

          .skull-icon img{
            object-fit: cover;
            width: 100px;
            height: 100px;
          }
          </style>
</head>

<body>
  <main>
    <div class="error-container">
      <div class="error-heading">⛧ 404 - Not Found ⛧</div>
      <p class="error-text">
        The domain you seek does not exist. <br> The path is lost within the void.
      </p>
      <div class="skull-icon">
        <img src="/public/images/favicon.ico" alt="skull">
      </div>
      <p class="no-turn-back">
        Return from whence you came. There is no turning back from this dimension.
      </p>

            <div class="error-links">
                <a href="/">⛧ Return to the Shadows ⛧</a>
            </div>

            <p class="error-message">
                Unauthorized access is remembered... Beware the Thirteen Veins.
            </p>
        </div>
    </main>
</body>

</html>
