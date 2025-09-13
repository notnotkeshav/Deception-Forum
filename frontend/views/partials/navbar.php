<style type="text/css">
    @font-face {
        font-family: 'vamp';
        src: url('/public/fonts/ScaryVampire.ttf') format('truetype');
    }

    /* Navigation Styles */
    body {
        margin: 0;
        padding: 0;
        font-family: 'Courier New', monospace;
        background: #000;
    }

    .navbar {
        background-color: #000;
        border-bottom: 1px solid #960d0d;
        padding: 10px 0;
        width: 100%;
        box-shadow: 0 0 10px rgba(255, 0, 0, 0.3);
    }

    .container {
        width: 90%;
        max-width: 1200px;
        margin: 0 auto;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .navbar-brand {
        color: #f03;
        font-weight: bold;
        font-size: 1.5em;
        text-decoration: none;
        font-family: 'vamp', sans-serif;
        letter-spacing: 1px;
        text-shadow: 0 0 5px rgba(255, 0, 0, 0.5);
    }

    .navbar-nav {
        display: flex;
        list-style: none;
        margin: 0;
        padding: 0;
    }

    .nav-item {
        margin-left: 25px;
        position: relative;
    }

    .nav-link {
        color: #fff;
        text-decoration: none;
        font-size: 0.9em;
        padding: 5px 0;
        transition: all 0.3s;
        border-bottom: 1px solid transparent;
        text-transform: uppercase;
        letter-spacing: 1px;
    }

    .nav-link:hover {
        color: #f03;
        border-bottom: 1px solid #f03;
        text-shadow: 0 0 5px rgba(255, 0, 0, 0.5);
    }

    .btn {
        padding: 5px 15px;
        border-radius: 3px;
        font-size: 0.9em;
        cursor: pointer;
        transition: all 0.3s;
        text-decoration: none;
        display: inline-block;
        text-transform: uppercase;
        letter-spacing: 1px;
    }

    .btn-danger {
        background: #960d0d;
        color: #fff;
        border: 1px solid #960d0d;
        font-weight: bold;
    }

    .btn-danger:hover {
        background: #c00;
        border-color: #c00;
        box-shadow: 0 0 10px rgba(255, 0, 0, 0.5);
    }

    .notification-badge {
        background: #f03;
        color: #000;
        border-radius: 50%;
        padding: 2px 5px;
        font-size: 0.7em;
        font-weight: bold;
        position: absolute;
        top: -5px;
        right: -10px;
    }

    /* Active link indicator */
    .nav-link.active {
        color: #f03;
        border-bottom: 1px solid #f03;
    }

    /* Blinking cursor effect */
    @keyframes blink {

        0%,
        100% {
            opacity: 1;
        }

        50% {
            opacity: 0;
        }
    }

    .navbar-brand::after {
        content: "_";
        animation: blink 1s step-end infinite;
        color: #f03;
    }
</style>

<nav class="navbar">
    <div class="container">
        <a class="navbar-brand" href="/">RED_SKULL</a>

        <ul class="navbar-nav">
            <li class="nav-item">
                <a class="nav-link active" href="/threads">Threads</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="/generate_invite_code">Invites</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="/notifications">
                    Notifications
                    <span class="notification-badge">3</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="/private-chats">Private</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="/group-chats">Group</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="/user">Profile</a>
            </li>
            <li class="nav-item">
                <a id="logout" class="btn btn-danger" href="#">Logout</a>
            </li>
        </ul>
    </div>
</nav>

<script type="text/javascript">
    // Logout functionality
    document.getElementById('logout').addEventListener('click', function(e) {
        e.preventDefault();
        if (confirm('Terminate session?')) {
            window.location.href = '/logout';
        }
    });

    // Simulate active link based on current page
    const currentPage = window.location.pathname;
    document.querySelectorAll('.nav-link').forEach(link => {
        if (link.getAttribute('href') === currentPage) {
            link.classList.add('active');
        }
    });

    // Notification count update (would be replaced with real data)
    setInterval(() => {
        const badge = document.querySelector('.notification-badge');
        // badge.textContent = newCount;
    }, 30000);
</script>