<?php
session_start();
require 'db_connect.php';

$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    if (empty($email) || empty($password)) {
        $error = "Email and Password are required.";
    } else {
        $stmt = $pdo->prepare("SELECT id, username, password, currency, profile_image FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['currency'] = $user['currency'] ?? 'USD';
            $_SESSION['profile_image'] = $user['profile_image'] ?? 'default.png';
            header("Location: index.php");
            exit;
        } else {
            $error = "Invalid email or password.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Budget Tracker Pro</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }

        body {
            min-height: 100vh;
            background: linear-gradient(135deg, #1e3a5f 0%, #0f172a 50%, #1a1a2e 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .auth-container {
            width: 100%;
            max-width: 420px;
        }

        .auth-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 24px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.4);
            overflow: hidden;
        }

        .auth-header {
            background: var(--primary-gradient);
            padding: 40px;
            text-align: center;
            color: white;
        }

        .auth-header .icon-circle {
            width: 72px;
            height: 72px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 16px;
        }

        .auth-header h2 {
            margin: 0;
            font-weight: 700;
        }

        .auth-header p {
            margin: 8px 0 0;
            opacity: 0.9;
        }

        .auth-body {
            padding: 32px;
        }

        .form-floating>.form-control {
            border-radius: 12px;
            border: 2px solid #e2e8f0;
            padding: 1rem 1rem;
            height: auto;
        }

        .form-floating>.form-control:focus {
            border-color: #764ba2;
            box-shadow: 0 0 0 4px rgba(118, 75, 162, 0.1);
        }

        .btn-auth {
            background: var(--primary-gradient);
            border: none;
            border-radius: 12px;
            padding: 14px;
            font-weight: 600;
            font-size: 1rem;
            transition: all 0.3s;
        }

        .btn-auth:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(118, 75, 162, 0.3);
        }

        .auth-footer {
            text-align: center;
            padding: 0 32px 32px;
        }

        .auth-footer a {
            color: #764ba2;
            font-weight: 600;
            text-decoration: none;
        }

        .auth-footer a:hover {
            text-decoration: underline;
        }
    </style>
</head>

<body>
    <div class="auth-container">
        <div class="auth-card">
            <div class="auth-header">
                <div class="icon-circle">
                    <i class="fas fa-wallet fa-2x"></i>
                </div>
                <h2>Welcome Back</h2>
                <p>Sign in to manage your finances</p>
            </div>
            <div class="auth-body">
                <?php if ($error): ?>
                    <div class="alert alert-danger d-flex align-items-center" role="alert">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        <div><?php echo $error; ?></div>
                    </div>
                <?php endif; ?>

                <form method="POST" action="">
                    <div class="form-floating mb-3">
                        <input type="email" class="form-control" id="email" name="email" placeholder="name@example.com"
                            required>
                        <label for="email"><i class="fas fa-envelope me-2 text-muted"></i>Email Address</label>
                    </div>
                    <div class="form-floating mb-4">
                        <input type="password" class="form-control" id="password" name="password" placeholder="Password"
                            required>
                        <label for="password"><i class="fas fa-lock me-2 text-muted"></i>Password</label>
                    </div>
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary btn-auth">
                            <i class="fas fa-sign-in-alt me-2"></i> Sign In
                        </button>
                    </div>
                </form>
            </div>
            <div class="auth-footer">
                <p class="text-muted mb-0">Don't have an account? <a href="signup.php">Create one</a></p>
            </div>
        </div>
    </div>
</body>

</html>