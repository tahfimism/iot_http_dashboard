<?php
require_once __DIR__ . '/api/auth_helpers.php';

$isLoggedIn = is_user_logged_in();
$userName = get_logged_in_user_name();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Firefly IoT</title>
    <style>
        :root {
            --bg: #f6f5ef;
            --text: #1a1f36;
            --surface: rgba(255, 255, 255, 0.82);
            --border: rgba(26, 31, 54, 0.14);
            --accent: #00695c;
            --accent-2: #e65100;
        }

        * { box-sizing: border-box; }
        body {
            margin: 0;
            min-height: 100vh;
            font-family: "Trebuchet MS", "Segoe UI", sans-serif;
            color: var(--text);
            background:
                radial-gradient(circle at 10% 10%, rgba(0, 105, 92, 0.2), transparent 44%),
                radial-gradient(circle at 90% 80%, rgba(230, 81, 0, 0.18), transparent 42%),
                linear-gradient(145deg, #f7f6f1, #ecebe2);
            display: grid;
            place-items: center;
            padding: 20px;
        }

        .shell {
            width: min(840px, 100%);
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 24px;
            padding: 30px;
            backdrop-filter: blur(5px);
            box-shadow: 0 20px 50px rgba(11, 16, 36, 0.16);
        }

        h1 {
            margin: 0;
            font-size: clamp(2rem, 6vw, 3.1rem);
            letter-spacing: 0.5px;
        }

        p {
            max-width: 650px;
            line-height: 1.55;
            color: #2c3358;
        }

        .row {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            margin-top: 20px;
        }

        .btn {
            text-decoration: none;
            border-radius: 12px;
            padding: 11px 16px;
            font-weight: 700;
            border: 1px solid transparent;
            transition: transform 0.15s ease, box-shadow 0.15s ease;
        }

        .btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 7px 20px rgba(0, 0, 0, 0.12);
        }

        .btn-primary {
            background: var(--accent);
            color: #fff;
        }

        .btn-alt {
            background: #fff;
            color: var(--text);
            border-color: var(--border);
        }

        .tag {
            display: inline-block;
            margin-top: 10px;
            font-size: 0.9rem;
            font-weight: 700;
            color: #0d4f47;
            background: rgba(0, 105, 92, 0.12);
            border-radius: 999px;
            padding: 6px 10px;
        }
    </style>
</head>
<body>
<main class="shell">
    <h1>Firefly IoT Control</h1>
    <p>Manage your ESP32 devices from one clean dashboard. Create an account, sign in, and keep every device state isolated to your own user profile.</p>

    <?php if ($isLoggedIn): ?>
        <span class="tag">Signed in as <?php echo htmlspecialchars($userName, ENT_QUOTES, 'UTF-8'); ?></span>
        <div class="row">
            <a class="btn btn-primary" href="dashboard.php">Go to Dashboard</a>
            <a class="btn btn-alt" href="logout.php">Log out</a>
        </div>
    <?php else: ?>
        <div class="row">
            <a class="btn btn-primary" href="login.php">Log In</a>
            <a class="btn btn-alt" href="signup.php">Sign Up</a>
        </div>
    <?php endif; ?>
</main>
</body>
</html>