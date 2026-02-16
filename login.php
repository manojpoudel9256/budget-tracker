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

// Language Logic
if (isset($_GET['lang'])) {
    $_SESSION['lang'] = $_GET['lang'];
}

if (!isset($_SESSION['lang'])) {
    $_SESSION['lang'] = 'en'; // Default
}

// Load Language File
$lang_file = __DIR__ . "/includes/lang_" . $_SESSION['lang'] . ".php";
if (file_exists($lang_file)) {
    include $lang_file;
} else {
    include __DIR__ . "/includes/lang_en.php";
}
?>
<!DOCTYPE html>
<html lang="<?php echo $_SESSION['lang']; ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $lang['login_title']; ?></title>
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
        rel="stylesheet">
    <!-- Bootstrap & Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --glass-bg: rgba(255, 255, 255, 0.1);
            --glass-border: 1px solid rgba(255, 255, 255, 0.2);
            --glass-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.37);
        }

        body {
            min-height: 100vh;
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(-45deg, #ee7752, #e73c7e, #23a6d5, #23d5ab);
            background-size: 400% 400%;
            animation: gradientBG 15s ease infinite;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            margin: 0;
        }

        @keyframes gradientBG {
            0% {
                background-position: 0% 50%;
            }

            50% {
                background-position: 100% 50%;
            }

            100% {
                background-position: 0% 50%;
            }
        }

        .auth-container {
            width: 100%;
            max-width: 450px;
            position: relative;
            z-index: 1;
        }

        /* Accessibilty/Language Toggle */
        .lang-toggle {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1000;
        }

        .lang-toggle .btn {
            background: rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(5px);
            border: 1px solid rgba(255, 255, 255, 0.3);
            color: white;
            transition: all 0.3s;
        }

        .lang-toggle .btn:hover,
        .lang-toggle .btn.active {
            background: rgba(255, 255, 255, 0.4);
            transform: translateY(-2px);
        }

        .auth-card {
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border-radius: 24px;
            border: var(--glass-border);
            box-shadow: var(--glass-shadow);
            overflow: hidden;
            animation: fadeInUp 0.8s ease-out;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .auth-header {
            padding: 40px 40px 20px;
            text-align: center;
            color: white;
        }

        .auth-header .icon-circle {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.4), rgba(255, 255, 255, 0.1));
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 20px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            font-size: 2rem;
            color: white;
            border: 1px solid rgba(255, 255, 255, 0.3);
        }

        .auth-header h2 {
            font-weight: 700;
            margin-bottom: 10px;
            letter-spacing: -0.5px;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .auth-header p {
            font-weight: 300;
            opacity: 0.9;
            font-size: 1.1rem;
        }

        .auth-body {
            padding: 20px 40px 40px;
        }

        .form-floating>.form-control {
            background: rgba(255, 255, 255, 0.8);
            border: none;
            border-radius: 15px;
            height: 55px;
            padding-top: 1.625rem;
            padding-bottom: 0.625rem;
            box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.05);
            transition: all 0.3s;
        }

        .form-floating>.form-control:focus {
            background: #fff;
            box-shadow: 0 0 0 4px rgba(255, 255, 255, 0.3);
            transform: scale(1.01);
        }

        .form-floating>label {
            padding-top: 0.8rem;
            padding-left: 1rem;
            color: #666;
        }

        .btn-auth {
            background: white;
            color: #667eea;
            border: none;
            border-radius: 15px;
            padding: 16px;
            font-weight: 700;
            font-size: 1.1rem;
            letter-spacing: 0.5px;
            transition: all 0.3s;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            text-transform: uppercase;
        }

        .btn-auth:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
            background: white;
            color: #764ba2;
        }

        .auth-footer {
            text-align: center;
            padding: 0 40px 30px;
        }

        .auth-footer p {
            color: rgba(255, 255, 255, 0.9);
            font-weight: 500;
        }

        .auth-footer a {
            color: white;
            font-weight: 700;
            text-decoration: none;
            background: rgba(255, 255, 255, 0.2);
            padding: 5px 12px;
            border-radius: 20px;
            transition: all 0.3s;
            margin-left: 5px;
        }

        .auth-footer a:hover {
            background: white;
            color: #667eea;
        }

        .alert-custom {
            background: rgba(255, 255, 255, 0.9);
            border: none;
            border-radius: 12px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
            backdrop-filter: blur(5px);
        }

        /* Mobile Responsiveness */
        @media (max-width: 576px) {
            body {
                padding: 15px;
                align-items: flex-start;
                padding-top: 80px;
                /* Space for fixed toggle */
            }

            .auth-card {
                border-radius: 20px;
            }

            .auth-header {
                padding: 30px 20px 10px;
                border-radius: 20px 20px 0 0;
            }

            .auth-header .icon-circle {
                width: 60px;
                height: 60px;
                font-size: 1.5rem;
                margin-bottom: 15px;
            }

            .auth-header h2 {
                font-size: 1.5rem;
            }

            .auth-body {
                padding: 15px 20px 30px;
            }

            .lang-toggle {
                top: 15px;
                right: 15px;
            }

            .lang-toggle .btn {
                padding: 4px 10px;
                font-size: 0.85rem;
            }
        }
    </style>
</head>

<body>
    <div class="auth-container">
        <!-- Language Toggle -->
        <div class="lang-toggle">
            <div class="btn-group shadow">
                <a href="?lang=en" class="btn <?php echo $_SESSION['lang'] == 'en' ? 'active fw-bold' : ''; ?>">🇺🇸
                    EN</a>
                <a href="?lang=jp" class="btn <?php echo $_SESSION['lang'] == 'jp' ? 'active fw-bold' : ''; ?>">🇯🇵
                    JP</a>
            </div>
        </div>

        <div class="auth-card">
            <div class="auth-header">
                <div class="icon-circle">
                    <i class="fas fa-wallet"></i>
                </div>
                <h2><?php echo $lang['welcome_back']; ?></h2>
                <p><?php echo $lang['sign_in_desc']; ?></p>
            </div>
            <div class="auth-body">
                <?php if ($error): ?>
                    <div class="alert alert-danger alert-custom d-flex align-items-center" role="alert">
                        <i class="fas fa-exclamation-circle me-2 text-danger"></i>
                        <div class="text-danger fw-semibold"><?php echo $error; ?></div>
                    </div>
                <?php endif; ?>

                <form method="POST" action="">
                    <div class="form-floating mb-3">
                        <input type="email" class="form-control" id="email" name="email" placeholder="name@example.com"
                            required>
                        <label for="email"><i
                                class="fas fa-envelope me-2 text-muted"></i><?php echo $lang['email_address']; ?></label>
                    </div>
                    <div class="form-floating mb-4">
                        <input type="password" class="form-control" id="password" name="password" placeholder="Password"
                            required>
                        <label for="password"><i
                                class="fas fa-lock me-2 text-muted"></i><?php echo $lang['password']; ?></label>
                    </div>
                    <div class="d-grid">
                        <button type="submit" class="btn btn-auth">
                            <?php echo $lang['sign_in']; ?> <i class="fas fa-arrow-right ms-2"></i>
                        </button>
                    </div>
                </form>
            </div>
            <div class="auth-footer">
                <p class="mb-0"><?php echo $lang['no_account']; ?> <a
                        href="signup.php"><?php echo $lang['create_one']; ?></a></p>
            </div>
        </div>
    </div>
</body>

</html>