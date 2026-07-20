<?php
require_once '../includes/config.php';

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] === 'guest' && isset($_GET['redirect']) && strpos($_GET['redirect'], '/version2/') === 0) {
        header("Location: " . $_GET['redirect']);
    } else {
        $role = $_SESSION['role'];

header("Location: /version2/app/{$role}/dashboard");
    }
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token before any other logic
    validateCsrfToken();

    $name = strtoupper(trim($_POST['name']));
    $username = strtolower(trim($_POST['username']));
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $role = 'guest'; // Hardcoded role for public signups

    // Validation
    $errors = [];

    // Name validation (letters only)
    if (empty($name)) {
        $errors[] = "Name is required.";
    } elseif (strlen($name) > 30) {
        $errors[] = "Name cannot exceed 30 characters.";
    } elseif (preg_match('/[0-9]/', $name)) {
        $errors[] = "Name should contain only letters.";
    }

    // Username validation
    if (empty($username)) {
        $errors[] = "Username is required.";
    } elseif (strlen($username) > 15) {
        $errors[] = "Username cannot exceed 15 characters.";
    } elseif (!preg_match('/^[a-z0-9_]+$/', $username)) {
        $errors[] = "Username can only contain letters, numbers, and underscores.";
    } elseif (!preg_match('/[a-z]/', $username) || !preg_match('/[0-9_]/', $username)) {
        $errors[] = "Username must contain letters and at least one number or underscore.";
    }

    // Phone validation (exactly 10 digits)
    if (empty($phone)) {
        $errors[] = "Phone number is required.";
    } elseif (!preg_match('/^[0-9]{10}$/', $phone)) {
        $errors[] = "Phone number must be exactly 10 digits.";
    }

    // Email validation
    if (empty($email)) {
        $errors[] = "Email is required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format.";
    }

    // Password validation
    if (empty($password)) {
        $errors[] = "Password is required.";
    } elseif (strlen($password) < 6) {
        $errors[] = "Password must be at least 6 characters.";
    } elseif (!preg_match('/[0-9]/', $password) || !preg_match('/[!@#$%^&*(),.?":{}|<>]/', $password)) {
        $errors[] = "Password must contain at least one number and one symbol.";
    }

    if ($password !== $confirm_password) {
        $errors[] = "Passwords do not match.";
    }

    // Check if email or username already exists
    if (empty($errors)) {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? OR username = ?");
        $stmt->execute([$email, $username]);
        if ($stmt->fetch()) {
            $errors[] = "Email or username already exists.";
        }
    }

    if (empty($errors)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $status = 'active'; // Guests are active by default

        $stmt = $pdo->prepare("INSERT INTO users (name, username, email, phone, password, role, status) VALUES (?, ?, ?, ?, ?, ?, ?)");
        if ($stmt->execute([$name, $username, $email, $phone, $hashed_password, $role, $status])) {
            $user_id = $pdo->lastInsertId();
            $_SESSION['user_id'] = $user_id;
            $_SESSION['name'] = $name;
            $_SESSION['username'] = $username;
            $_SESSION['email'] = $email;
            $_SESSION['role'] = $role;
            $_SESSION['status'] = $status;
            $_SESSION['login_success'] = true;
            $_SESSION['success'] = "Welcome, " . $name . "!";

            header("Location: /version2/app/guest/dashboard");
            exit();
        } else {
            $errors[] = "Something went wrong. Please try again.";
        }
    }
}
?>

<?php
// Check if registration was successful
$show_success_modal = false;
$success_role = '';
$success_name = '';
if (isset($_SESSION['registration_success'])) {
    $show_success_modal = true;
    $success_role = $_SESSION['registered_role'];
    $success_name = $_SESSION['registered_name'];
    $redirect_param = isset($_SESSION['redirect_after_signup']) ? '?redirect=' . urlencode($_SESSION['redirect_after_signup']) : '';

    // Clear the session variables
    unset($_SESSION['registration_success']);
    unset($_SESSION['registered_role']);
    unset($_SESSION['registered_name']);
    unset($_SESSION['redirect_after_signup']);
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up - HRMS</title>
    <link rel="stylesheet" href="/version2/style.css">
    <link rel="stylesheet" href="/version2/assets/fontawesome/css/all.min.css">
    <link
        href="/version2/assets/fonts/google-fonts-local.css"
        rel="stylesheet">
    <style>
        /* Success Modal Styles */
        .success-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.7);
            z-index: 10000;
            justify-content: center;
            align-items: center;
        }

        .success-modal.show {
            display: flex;
        }

        .success-modal-content {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            padding: 40px;
            border-radius: 16px;
            text-align: center;
            max-width: 400px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            animation: authSlideUp 0.4s ease;
            border: 1px solid rgba(255, 255, 255, 0.3);
        }

        .success-icon {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            margin: 0 auto 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.5rem;
        }

        .success-icon.guest {
            background: linear-gradient(135deg, #48BB78, #38A169);
            color: white;
            animation: authScaleIn 0.5s ease 0.2s both;
        }

        .success-icon.staff {
            background: linear-gradient(135deg, #D4AF37, #B8860B);
            color: white;
            animation: authPulse 1.5s ease infinite;
        }

        .success-modal h2 {
            color: #2b5455;
            margin-bottom: 10px;
            font-size: 1.6rem;
            font-family: 'Playfair Display', serif;
        }

        .success-modal p {
            color: #4A5568;
            margin-bottom: 20px;
            font-size: 0.95rem;
            line-height: 1.6;
        }

        @keyframes authSlideUp {
            from {
                transform: translateY(30px);
                opacity: 0;
            }

            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        @keyframes authScaleIn {
            from {
                transform: scale(0);
            }

            to {
                transform: scale(1);
            }
        }

        @keyframes authPulse {

            0%,
            100% {
                transform: scale(1);
            }

            50% {
                transform: scale(1.05);
            }
        }
    </style>
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
                class="auth-modal-tab">Login</a>
            <a href="signup.php<?php echo isset($_GET['redirect']) ? '?redirect=' . urlencode($_GET['redirect']) : ''; ?>"
                class="auth-modal-tab active">Sign Up</a>
        </div>

        <!-- Form -->
        <form id="signup-form" class="auth-modal-form" method="POST" action="">
            <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
            <?php if (isset($_GET['redirect'])): ?>
                <input type="hidden" name="redirect" value="<?php echo htmlspecialchars($_GET['redirect']); ?>">
            <?php elseif (isset($_POST['redirect'])): ?>
                <input type="hidden" name="redirect" value="<?php echo htmlspecialchars($_POST['redirect']); ?>">
            <?php endif; ?>

            <?php if (!empty($errors)): ?>
                <div class="auth-modal-alert">
                    <?php foreach ($errors as $error): ?>
                        <p><i class="fas fa-exclamation-circle"></i> <?php echo $error; ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <div class="auth-modal-field">
                <label for="name"><i class="fas fa-user"></i> Full Name</label>
                <input type="text" id="name" name="name"
                    value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>"
                    placeholder="Enter your full name" required pattern="[A-Za-z\s]+"
                    title="Name should contain only letters" maxlength="30" oninput="this.value = this.value.toUpperCase()">
            </div>

            <div class="auth-modal-row">
                <div class="auth-modal-field">
                    <label for="username"><i class="fas fa-at"></i> Username</label>
                    <input type="text" id="username" name="username"
                        value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>"
                        placeholder="Choose username" required maxlength="15">
                    <div id="username-feedback" style="color: #e53e3e; font-size: 0.8rem; margin-top: 4px; display: none;"></div>
                </div>
                <div class="auth-modal-field">
                    <label for="phone"><i class="fas fa-phone"></i> Phone</label>
                    <input type="tel" id="phone" name="phone"
                        value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>"
                        placeholder="10-digit number" required pattern="[0-9]{10}"
                        title="Phone number must be exactly 10 digits">
                    <div id="phone-feedback" style="color: #e53e3e; font-size: 0.8rem; margin-top: 4px; display: none;"></div>
                </div>
            </div>

            <div class="auth-modal-field">
                <label for="email"><i class="fas fa-envelope"></i> Email Address</label>
                <input type="email" id="email" name="email"
                    value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"
                    placeholder="Enter your email" required>
            </div>

            <div class="auth-modal-row">
                <div class="auth-modal-field">
                    <label for="password"><i class="fas fa-lock"></i> Password</label>
                    <div style="position: relative;">
                        <input type="password" id="password" name="password" placeholder="Min 6 characters" required
                            minlength="6" title="Must contain at least one number and one symbol" style="padding-right: 40px; box-sizing: border-box; width: 100%;">
                        <span id="togglePassword" style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%); cursor: pointer; color: #a0aec0; padding: 5px;">
                            <i class="fas fa-eye"></i>
                        </span>
                    </div>
                </div>
                <div class="auth-modal-field">
                    <label for="confirm_password"><i class="fas fa-check-circle"></i> Confirm</label>
                    <div style="position: relative;">
                        <input type="password" id="confirm_password" name="confirm_password" placeholder="Re-enter password"
                            required style="padding-right: 40px; box-sizing: border-box; width: 100%;">
                        <span id="toggleConfirmPassword" style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%); cursor: pointer; color: #a0aec0; padding: 5px;">
                            <i class="fas fa-eye"></i>
                        </span>
                    </div>
                </div>
            </div>



            <button type="submit" class="auth-modal-btn">
                <i class="fas fa-user-plus"></i> Create Account
            </button>

            <p class="auth-modal-footer-text">
                Already have an account?
                <a
                    href="login.php<?php echo isset($_GET['redirect']) ? '?redirect=' . urlencode($_GET['redirect']) : ''; ?>">Login
                    here</a>
            </p>
        </form>
    </div>

    <!-- Success Modal -->
    <div class="success-modal <?php echo $show_success_modal ? 'show' : ''; ?>" id="successModal">
        <div class="success-modal-content">
            <div class="success-icon guest">
                <i class="fas fa-check"></i>
            </div>
            <h2>You're Ready to Go! 🎉</h2>
            <p>Welcome aboard, <strong><?php echo htmlspecialchars($success_name); ?></strong>!<br>
                Your account has been created successfully.</p>
            <button onclick="window.location.href='login.php<?php echo $redirect_param ?? ''; ?>'"
                class="auth-modal-btn" style="margin-top: 10px;">Okay</button>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {

            // Realtime feedback for username
            const usernameInput = document.getElementById('username');
            const usernameFeedback = document.getElementById('username-feedback');

            if (usernameInput && usernameFeedback) {
                usernameInput.addEventListener('input', function() {
                    this.value = this.value.toLowerCase().replace(/[^a-z0-9_]/g, '');
                    const val = this.value;
                    if (val.length === 0) {
                        usernameFeedback.style.display = 'none';
                    } else if (!/[a-z]/.test(val) || !/[0-9_]/.test(val)) {
                        usernameFeedback.textContent = 'Must contain letters and at least one number or underscore.';
                        usernameFeedback.style.display = 'block';
                    } else {
                        usernameFeedback.style.display = 'none';
                    }
                });
            }

            // Realtime feedback for phone
            const phoneInput = document.getElementById('phone');
            const phoneFeedback = document.getElementById('phone-feedback');

            if (phoneInput && phoneFeedback) {
                phoneInput.addEventListener('input', function() {
                    this.value = this.value.replace(/[^0-9]/g, '');
                    const val = this.value;
                    if (val.length === 0) {
                        phoneFeedback.style.display = 'none';
                    } else if (val.length !== 10) {
                        phoneFeedback.textContent = 'Phone number must be exactly 10 digits.';
                        phoneFeedback.style.display = 'block';
                    } else {
                        phoneFeedback.style.display = 'none';
                    }
                });
            }


            // Password confirmation validation
            const password = document.getElementById('password');
            const confirmPassword = document.getElementById('confirm_password');

            function validatePassword() {
                if (password.value !== confirmPassword.value) {
                    confirmPassword.setCustomValidity("Passwords don't match");
                } else {
                    confirmPassword.setCustomValidity('');
                }
            }

            password.addEventListener('change', validatePassword);
            confirmPassword.addEventListener('keyup', validatePassword);

            // Toggle Password Visibility
            const togglePassword = document.querySelector('#togglePassword');
            const toggleConfirmPassword = document.querySelector('#toggleConfirmPassword');

            togglePassword.addEventListener('click', function () {
                const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
                password.setAttribute('type', type);
                const icon = this.querySelector('i');
                icon.classList.toggle('fa-eye');
                icon.classList.toggle('fa-eye-slash');
            });

            toggleConfirmPassword.addEventListener('click', function () {
                const type = confirmPassword.getAttribute('type') === 'password' ? 'text' : 'password';
                confirmPassword.setAttribute('type', type);
                const icon = this.querySelector('i');
                icon.classList.toggle('fa-eye');
                icon.classList.toggle('fa-eye-slash');
            });
        });
    </script>
</body>

</html>