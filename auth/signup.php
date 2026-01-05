<?php
require_once '../includes/config.php';

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header("Location: /version2/" . ($_SESSION['role'] === 'admin' ? 'admin/admin_dashboard.php' : 
                          ($_SESSION['role'] === 'staff' ? 'hotel_staff/staff_dashboard.php' : 'guest/guest_dashboard.php')));
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $role = $_POST['role'];

    // Validation
    $errors = [];
    
    // Name validation (letters only)
    if (empty($name)) {
        $errors[] = "Name is required.";
    } elseif (preg_match('/[0-9]/', $name)) {
        $errors[] = "Name should contain only letters.";
    }
    
    // Username validation
    if (empty($username)) {
        $errors[] = "Username is required.";
    } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
        $errors[] = "Username can only contain letters, numbers, and underscores.";
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
    
    if (empty($role)) {
        $errors[] = "Please select a role.";
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
        $status = ($role === 'staff') ? 'pending' : 'active';
        
        $stmt = $pdo->prepare("INSERT INTO users (name, username, email, phone, password, role, status) VALUES (?, ?, ?, ?, ?, ?, ?)");
        if ($stmt->execute([$name, $username, $email, $phone, $hashed_password, $role, $status])) {
            // Set success registration flag with role info
            $_SESSION['registration_success'] = true;
            $_SESSION['registered_role'] = $role;
            $_SESSION['registered_name'] = $name;
            header("Location: signup.php");
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
    // Clear the session variables
    unset($_SESSION['registration_success']);
    unset($_SESSION['registered_role']);
    unset($_SESSION['registered_name']);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up - HRMS</title>
    <link rel="stylesheet" href="/version2/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        /* Success Modal Styles */
        .success-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.6);
            z-index: 10000;
            justify-content: center;
            align-items: center;
            animation: fadeIn 0.3s ease;
        }
        
        .success-modal.show {
            display: flex;
        }
        
        .success-modal-content {
            background: white;
            padding: 40px;
            border-radius: 12px;
            text-align: center;
            max-width: 400px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
            animation: slideUp 0.4s ease;
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
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
            animation: scaleIn 0.5s ease 0.2s both;
        }
        
        .success-icon.staff {
            background: linear-gradient(135deg, #ffc107, #ff9800);
            color: white;
            animation: pulse 1.5s ease infinite;
        }
        
        .success-modal h2 {
            color: #333;
            margin-bottom: 10px;
            font-size: 1.8rem;
        }
        
        .success-modal p {
            color: #666;
            margin-bottom: 20px;
            font-size: 1rem;
            line-height: 1.5;
        }
        
        .success-modal .redirect-text {
            color: #999;
            font-size: 0.9rem;
            margin-top: 15px;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        @keyframes slideUp {
            from {
                transform: translateY(30px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }
        
        @keyframes scaleIn {
            from {
                transform: scale(0);
            }
            to {
                transform: scale(1);
            }
        }
        
        @keyframes pulse {
            0%, 100% {
                transform: scale(1);
            }
            50% {
                transform: scale(1.05);
            }
        }
    </style>
</head>
<body>
    <div class="auth-container">
        <div class="auth-header">
            <a href="../homepage.php" class="home-btn">
                <i class="fas fa-home"></i>
                Back to Home
            </a>
        </div>
        
        <div class="auth-tabs">
            <a href="login.php" class="auth-tab">Login</a>
            <a href="signup.php" class="auth-tab active">Sign Up</a>
        </div>
        
        <form id="signup-form" class="auth-form active" method="POST" action="">
            <div class="form-group">
                <label for="name">Full Name</label>
                <input type="text" id="name" name="name" class="form-control" 
                       value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>" 
                       required pattern="[A-Za-z\s]+" title="Name should contain only letters">
                <small class="form-text">Only letters and spaces allowed</small>
            </div>

            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" class="form-control" 
                       value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>" 
                       required pattern="[A-Za-z0-9_]+" title="Username can contain letters, numbers, and underscores">
            </div>

            <div class="form-group">
                <label for="phone">Phone Number</label>
                <input type="tel" id="phone" name="phone" class="form-control" 
                       value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>" 
                       required pattern="[0-9]{10}" title="Phone number must be exactly 10 digits">
            </div>

            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" class="form-control" 
                       value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" 
                       required>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" class="form-control" required
                       minlength="6"
                       title="Must contain at least one number and one symbol">
                <small class="form-text">Must be at least 6 characters with one number and one symbol</small>
            </div>

            <div class="form-group">
                <label for="confirm_password">Confirm Password</label>
                <input type="password" id="confirm_password" name="confirm_password" class="form-control" required>
            </div>

            <div class="form-group">
                <label for="role">Role</label>
                <select id="role" name="role" class="form-control" required>
                    <option value="">Select Role</option>
                    <option value="guest" <?php echo (isset($_POST['role']) && $_POST['role'] === 'guest') ? 'selected' : ''; ?>>Guest</option>
                    <option value="staff" <?php echo (isset($_POST['role']) && $_POST['role'] === 'staff') ? 'selected' : ''; ?>>Hotel Staff</option>
                </select>
            </div>

            <?php if (!empty($errors)): ?>
                <div class="alert alert-error">
                    <?php foreach ($errors as $error): ?>
                        <p><?php echo $error; ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <button type="submit" class="btn btn-primary" style="width: 100%;">Create Account</button>
        </form>
    </div>

    <!-- Success Modal -->
    <div class="success-modal <?php echo $show_success_modal ? 'show' : ''; ?>" id="successModal">
        <div class="success-modal-content">
            <?php if ($success_role === 'guest'): ?>
                <div class="success-icon guest">
                    <i class="fas fa-check"></i>
                </div>
                <h2>You're Ready to Go! 🎉</h2>
                <p>Welcome aboard, <strong><?php echo htmlspecialchars($success_name); ?></strong>!<br>
                Your account has been created successfully.</p>
            <?php else: ?>
                <div class="success-icon staff">
                    <i class="fas fa-clock"></i>
                </div>
                <h2>Registration Submitted ⏳</h2>
                <p>Thank you, <strong><?php echo htmlspecialchars($success_name); ?></strong>!<br>
                Your account is awaiting for admin's approval.</p>
            <?php endif; ?>
            <button onclick="window.location.href='login.php'" class="btn btn-primary" style="margin-top: 20px; padding: 12px 40px;">Okay</button>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const roleSelect = document.getElementById('role');

            
            // Trigger change event on page load if role is already selected
            if (roleSelect.value) {
                roleSelect.dispatchEvent(new Event('change'));
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
        });
    </script>
</body>
</html>