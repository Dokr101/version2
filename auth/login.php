<?php
require_once '../includes/config.php';

$redirect_val = '';
if (isset($_GET['redirect'])) {
    $redirect_val = $_GET['redirect'];
} elseif (isset($_POST['redirect'])) {
    $redirect_val = $_POST['redirect'];
}

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    $roleRedirects = [
        'admin' => '/version2/app/admin/dashboard',
        'staff' => '/version2/app/staff/dashboard',
        'guest' => '/version2/app/guest/dashboard',
    ];

    $defaultRedirect = $roleRedirects[$_SESSION['role']] ?? '/version2/app/';
    if (!empty($redirect_val) && strpos($redirect_val, '/version2/app/') === 0) {
        $defaultRedirect = $redirect_val;
    }

    header("Location: " . $defaultRedirect);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $errors = [];

    // Brute force rate limiting lockout check
    if (($_SESSION['login_attempts'] ?? 0) >= 5) {
        $elapsed = time() - ($_SESSION['login_last_attempt'] ?? 0);
        if ($elapsed < 900) { // 15 minutes = 900 seconds
            $errors[] = 'Too many failed attempts. Try again in ' . ceil((900 - $elapsed) / 60) . ' minutes.';
        } else {
            $_SESSION['login_attempts'] = 0; // reset after 15 min
        }
    }

    $username = trim($_POST['username']);
    $password = $_POST['password'];

    // Validation if not already locked out
    if (empty($errors)) {
        if (empty($username)) {
            $errors[] = "Username, Phone or Email is required.";
        }
        if (empty($password)) {
            $errors[] = "Password is required.";
        }
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? OR email = ? OR phone = ?");
        $stmt->execute([$username, $username, $username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password'])) {
            // Prevent Session Fixation
            session_regenerate_id(true);

            // Check if staff user is approved
            if ($user['role'] === 'staff' && $user['status'] !== 'active') {
                $errors[] = "Your account is pending...\n Wait for admin's approval.";
            } else {
                $_SESSION['login_attempts'] = 0; // Reset counter on successful login
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['name'] = $user['name'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['status'] = $user['status'];

                // Set login success flag for welcome modal
                $_SESSION['login_success'] = true;

                $_SESSION['success'] = "Welcome, " . $user['name'] . "!";

                $roleRedirects = [
                    'admin' => '/version2/app/admin/dashboard',
                    'staff' => '/version2/app/staff/dashboard',
                    'guest' => '/version2/app/guest/dashboard',
                ];
                $redirectTarget = $roleRedirects[$user['role']] ?? '/version2/app/';
                if (!empty($redirect_val) && strpos($redirect_val, '/version2/app/') === 0) {
                    $redirectTarget = $redirect_val;
                }

                header("Location: " . $redirectTarget);
                exit();
            }
        } else {
            $_SESSION['login_attempts'] = ($_SESSION['login_attempts'] ?? 0) + 1;
            $_SESSION['login_last_attempt'] = time();
            $errors[] = "Invalid username or password.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - HRMS</title>
    <link rel="stylesheet" href="/version2/style.css">
    <link rel="stylesheet" href="/version2/assets/fontawesome/css/all.min.css">
    <link href="/version2/assets/fonts/google-fonts-local.css" rel="stylesheet">
</head>

<body class="auth-page-body">
    <!-- Background -->
    <div class="auth-page-bg">
        <div class="hp-shape hp-shape-1"></div>
        <div class="hp-shape hp-shape-2"></div>
        <div class="hp-shape hp-shape-3"></div>
        <div class="hp-shape hp-shape-4"></div>
        <div class="hp-shape hp-shape-5"></div>
    </div>

    <!-- Auth Modal Card -->
    <div class="auth-modal">
        <!-- Header -->
        <div class="auth-modal-header">
            <a href="../homepage.php" class="auth-modal-back">
                <i class="fas fa-arrow-left"></i>
            </a>
            <div class="auth-modal-logo">
                <div class="auth-modal-logo-icon"><i class="fas fa-hotel"></i></div>
                <span>HRMS</span>
            </div>
            <div class="auth-modal-spacer"></div>
        </div>

        <!-- Tabs -->
        <div class="auth-modal-tabs">
            <a href="login.php<?php echo isset($_GET['redirect']) ? '?redirect=' . urlencode($_GET['redirect']) : ''; ?>"
                class="auth-modal-tab active">Login</a>
            <a href="signup.php<?php echo isset($_GET['redirect']) ? '?redirect=' . urlencode($_GET['redirect']) : ''; ?>"
                class="auth-modal-tab">Sign Up</a>
        </div>

        <!-- Form -->
        <form id="login-form" class="auth-modal-form" method="POST" action="">
            <?php
            $redirect_val = '';
            if (isset($_GET['redirect'])) {
                $redirect_val = $_GET['redirect'];
            } elseif (isset($_POST['redirect'])) {
                $redirect_val = $_POST['redirect'];
            }

            if (!empty($redirect_val)):
                ?>
                <input type="hidden" name="redirect" value="<?php echo htmlspecialchars($redirect_val); ?>">
            <?php endif; ?>

            <?php displayMessages(); ?>

            <?php if (!empty($errors)): ?>
                <div class="auth-modal-alert">
                    <?php foreach ($errors as $error): ?>
                        <p><i class="fas fa-exclamation-circle"></i> <?php echo $error; ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <div class="auth-modal-field">
                <label for="username"><i class="fas fa-user"></i> Username, Phone or Email</label>
                <input type="text" id="username" name="username"
                    value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>"
                    placeholder="Enter your username, phone or email" required>
            </div>

            <div class="auth-modal-field">
                <label for="password"><i class="fas fa-lock"></i> Password</label>
                <div style="position: relative;">
                    <input type="password" id="password" name="password" placeholder="Enter your password" required style="padding-right: 40px; box-sizing: border-box; width: 100%;">
                    <span id="togglePassword" style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%); cursor: pointer; color: #a0aec0; padding: 5px;">
                        <i class="fas fa-eye"></i>
                    </span>
                </div>
            </div>

            <button type="submit" class="auth-modal-btn">
                <i class="fas fa-sign-in-alt"></i> Login
            </button>

            <p class="auth-modal-footer-text">
                Don't have an account?
                <a
                    href="signup.php<?php echo isset($_GET['redirect']) ? '?redirect=' . urlencode($_GET['redirect']) : ''; ?>">Create
                    one</a>
            </p>
        </form>
    </div>

    <script>
        const togglePassword = document.querySelector('#togglePassword');
        const password = document.querySelector('#password');

        togglePassword.addEventListener('click', function (e) {
            const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
            password.setAttribute('type', type);
            
            const icon = this.querySelector('i');
            icon.classList.toggle('fa-eye');
            icon.classList.toggle('fa-eye-slash');
        });
    </script>
</body>

</html>