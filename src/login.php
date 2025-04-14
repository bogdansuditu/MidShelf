<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/auth/auth.php';

$auth = new Auth();

// Redirect if already logged in
if ($auth->isLoggedIn()) {
    header('Location: /');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    if ($auth->login($username, $password)) {
        header('Location: /');
        exit;
    } else {
        $error = 'Invalid username or password';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="/assets/css/style.css">
    <style>
        .login-container {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: var(--spacing-md);
            background: radial-gradient(circle at center, var(--color-surface) 0%, var(--color-background) 100%);
            position: relative;
            overflow: hidden;
        }

        .login-container::before {
            content: '';
            position: absolute;
            width: 200%;
            height: 200%;
            background: linear-gradient(45deg, transparent 45%, var(--color-primary) 49%, transparent 51%);
            opacity: 0.05;
            animation: gradient-move 8s linear infinite;
        }

        @keyframes gradient-move {
            0% { transform: translate(-50%, -50%) rotate(0deg); }
            100% { transform: translate(-50%, -50%) rotate(360deg); }
        }

        .login-card {
            background: linear-gradient(145deg, var(--color-surface), var(--color-surface-raised));
            border-radius: var(--radius-lg);
            padding: var(--spacing-xl);
            width: 100%;
            max-width: 400px;
            box-shadow: var(--shadow-lg);
            border: 1px solid var(--color-border);
            position: relative;
            z-index: 1;
            backdrop-filter: blur(12px);
        }

        .login-header {
            text-align: center;
            margin-bottom: var(--spacing-xl);
        }

        .login-header i {
            font-size: 3em;
            background: linear-gradient(45deg, var(--color-primary), var(--color-accent));
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: var(--spacing-md);
            animation: pulse 2s ease-in-out infinite;
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }

        .login-header h1 {
            font-size: 2em;
            font-weight: 700;
            background: linear-gradient(to right, var(--color-text), var(--color-text-secondary));
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: var(--spacing-xs);
        }

        .login-header p {
            color: var(--color-text-secondary);
            font-size: 0.9em;
        }

        .error-message {
            background: linear-gradient(to right, var(--color-error), #dc2626);
            color: var(--color-text);
            padding: var(--spacing-md);
            border-radius: var(--radius-md);
            margin-bottom: var(--spacing-lg);
            text-align: center;
            font-weight: 500;
            animation: shake 0.5s cubic-bezier(0.36, 0.07, 0.19, 0.97) both;
        }

        @keyframes shake {
            10%, 90% { transform: translateX(-1px); }
            20%, 80% { transform: translateX(2px); }
            30%, 50%, 70% { transform: translateX(-4px); }
            40%, 60% { transform: translateX(4px); }
        }

        .login-form .form-group {
            margin-bottom: var(--spacing-lg);
        }

        .login-form .btn {
            width: 100%;
            padding: var(--spacing-md);
            margin-top: var(--spacing-xl);
            font-size: 1rem;
            height: 48px;
            position: relative;
            overflow: hidden;
            transition: all 0.3s ease;
        }

        .login-form .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 0 20px var(--color-primary-dark);
        }

        .login-form .btn:active {
            transform: translateY(0);
        }
    </style>
</head>
<body>
    <div class="login-container fade-in">
        <div class="login-card">
            <div class="login-header">
                <i class="fas fa-compact-disc"></i>
                <h1><?php echo APP_NAME; ?></h1>
                <p>Sign in to your account</p>
            </div>

            <?php if ($error): ?>
                <div class="error-message">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <form method="POST" class="login-form">
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" class="form-control" required autofocus>
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" class="form-control" required>
                </div>

                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-sign-in-alt"></i> Sign In
                </button>
            </form>
        </div>
    </div>

    <script>
        // Add subtle animation to form fields
        document.querySelectorAll('.form-control').forEach(input => {
            input.addEventListener('focus', () => {
                input.parentElement.style.transform = 'translateY(-2px)';
            });

            input.addEventListener('blur', () => {
                input.parentElement.style.transform = 'translateY(0)';
            });
        });
    </script>
</body>
</html>
