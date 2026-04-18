<?php
define('SKIP_API_HEADERS', true);
require_once __DIR__ . '/api/db_config.php';
require_once __DIR__ . '/api/auth_helpers.php';

if (is_user_logged_in()) {
    header('Location: dashboard.php');
    exit;
}

$error = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = (string)($_POST['password'] ?? '');

    if ($email === '' || $password === '') {
        $error = 'Email and password are required.';
    } else {
        $stmt = $conn->prepare('SELECT id, name, password_hash FROM iot_users WHERE email = ? LIMIT 1');
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $stmt->bind_result($id, $name, $passwordHash);

        if ($stmt->fetch() && password_verify($password, $passwordHash)) {
            $_SESSION['user_id'] = (int)$id;
            $_SESSION['user_name'] = (string)$name;
            $_SESSION['user_email'] = $email;
            $stmt->close();
            $conn->close();
            header('Location: dashboard.php');
            exit;
        }

        $error = 'Invalid credentials.';
        $stmt->close();
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login - Firefly IoT</title>
    <style>
        :root {
            --primary: #00e676;
            --bg: #0a0e27;
            --card: #1a1f3a;
            --accent: #2979ff;
            --danger: #ff5252;
            --text: #e0e0e0;
            --text-muted: #a0a0a0;
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            min-height: 100vh;
            font-family: 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, var(--bg) 0%, #1a2847 50%, #0f1d3a 100%);
            color: var(--text);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            overflow-x: hidden;
        }

        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: radial-gradient(ellipse at 20% 50%, rgba(41, 121, 255, 0.1) 0%, transparent 50%),
                        radial-gradient(ellipse at 80% 80%, rgba(0, 230, 118, 0.08) 0%, transparent 50%);
            pointer-events: none;
            z-index: -1;
        }

        .container {
            width: 100%;
            max-width: 420px;
            position: relative;
            z-index: 1;
        }

        .card {
            background: linear-gradient(135deg, #1e2749 0%, #1a1f3a 100%);
            border: 1px solid rgba(41, 121, 255, 0.2);
            border-radius: 24px;
            padding: 40px 32px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5),
                        inset 0 1px 1px rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
        }

        .header {
            text-align: center;
            margin-bottom: 32px;
        }

        .logo {
            display: inline-block;
            width: 48px;
            height: 48px;
            background: linear-gradient(135deg, var(--accent), var(--primary));
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 16px;
            font-weight: 900;
            font-size: 24px;
            box-shadow: 0 8px 20px rgba(41, 121, 255, 0.3);
        }

        h1 {
            margin: 0;
            font-size: 28px;
            font-weight: 700;
            letter-spacing: -0.5px;
            margin-bottom: 8px;
            background: linear-gradient(135deg, #fff, var(--text-muted));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .subtitle {
            color: var(--text-muted);
            font-size: 14px;
            margin: 0;
        }

        .form-group {
            margin-bottom: 16px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            font-size: 13px;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            color: var(--text-muted);
        }

        input {
            width: 100%;
            padding: 12px 16px;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(41, 121, 255, 0.2);
            border-radius: 12px;
            color: var(--text);
            font-size: 14px;
            transition: all 0.3s ease;
            font-family: inherit;
        }

        input:hover {
            border-color: rgba(41, 121, 255, 0.4);
            background: rgba(255, 255, 255, 0.08);
        }

        input:focus {
            outline: none;
            background: rgba(255, 255, 255, 0.1);
            border-color: var(--accent);
            box-shadow: 0 0 0 3px rgba(41, 121, 255, 0.1);
        }

        input::placeholder {
            color: rgba(224, 224, 224, 0.4);
        }

        .btn {
            width: 100%;
            padding: 12px 24px;
            margin-top: 24px;
            border: 0;
            border-radius: 12px;
            background: linear-gradient(135deg, var(--accent), #1e53e5);
            color: #fff;
            font-weight: 700;
            font-size: 14px;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 8px 20px rgba(41, 121, 255, 0.3);
            position: relative;
            overflow: hidden;
        }

        .btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.5s;
        }

        .btn:hover::before {
            left: 100%;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 30px rgba(41, 121, 255, 0.4);
        }

        .btn:active {
            transform: translateY(0);
        }

        .error {
            background: rgba(255, 82, 82, 0.1);
            color: #ff9999;
            padding: 12px 16px;
            border-radius: 12px;
            border-left: 3px solid #ff5252;
            margin-top: 16px;
            font-size: 13px;
            line-height: 1.5;
        }

        .divider {
            display: flex;
            align-items: center;
            gap: 12px;
            margin: 24px 0;
            color: var(--text-muted);
            font-size: 12px;
        }

        .divider::before,
        .divider::after {
            content: '';
            flex: 1;
            height: 1px;
            background: rgba(41, 121, 255, 0.2);
        }

        .footer {
            text-align: center;
            margin-top: 24px;
            padding-top: 24px;
            border-top: 1px solid rgba(41, 121, 255, 0.1);
        }

        .footer-text {
            color: var(--text-muted);
            font-size: 13px;
            margin-bottom: 16px;
        }

        .links {
            display: flex;
            gap: 12px;
            justify-content: center;
            flex-wrap: wrap;
        }

        .links a {
            color: var(--accent);
            text-decoration: none;
            font-size: 13px;
            font-weight: 600;
            padding: 6px 12px;
            border-radius: 8px;
            transition: all 0.3s ease;
            border: 1px solid transparent;
        }

        .links a:hover {
            background: rgba(41, 121, 255, 0.1);
            border-color: rgba(41, 121, 255, 0.3);
            color: #b9d3ff;
        }

        @media (max-width: 480px) {
            .card {
                padding: 28px 20px;
            }

            h1 {
                font-size: 24px;
            }

            .logo {
                width: 40px;
                height: 40px;
                font-size: 20px;
            }
        }
    </style>
</head>
<body>
<div class="container">
    <div class="card">
        <div class="header">
            <div class="logo">⚡</div>
            <h1>Welcome Back</h1>
            <p class="subtitle">Sign in to manage your IoT devices</p>
        </div>

        <form method="post" action="login.php">
            <div class="form-group">
                <label for="email">Email Address</label>
                <input id="email" name="email" type="email" value="<?php echo htmlspecialchars($email, ENT_QUOTES, 'UTF-8'); ?>" placeholder="your@email.com" required>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input id="password" name="password" type="password" placeholder="••••••••" required>
            </div>

            <?php if ($error !== ''): ?>
                <div class="error"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
            <?php endif; ?>

            <button class="btn" type="submit">Sign In</button>

            <div class="divider">or</div>

            <div class="footer">
                <p class="footer-text">Don't have an account?</p>
                <div class="links">
                    <a href="signup.php">Create one now</a>
                </div>
            </div>

            <div style="text-align: center; margin-top: 16px;">
                <a href="index.php" style="color: var(--text-muted); text-decoration: none; font-size: 12px; transition: color 0.3s;">← Back to home</a>
            </div>
        </form>
    </div>
</div>
</body>
</html>