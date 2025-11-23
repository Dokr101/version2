<?php
require_once '../includes/config.php';

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header("Location: /version2/" . ($_SESSION['role'] === 'admin' ? 'admin/admin_dashboard.php' : 
                          ($_SESSION['role'] === 'staff' ? 'hotel staff/staff_dashboard.php' : 'guest/guest_dashboard.php')));
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
            if ($role === 'staff') {
                $_SESSION['success'] = "Registration successful. Status: Awaiting Admin Approval ❗";
            } else {
                $_SESSION['success'] = "Registration successful. You're ready to login!";
            }
            header("Location: login.php");
            exit();
        } else {
            $errors[] = "Something went wrong. Please try again.";
        }
    }
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
                <small class="form-text" id="role-help"></small>
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

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const roleSelect = document.getElementById('role');
            const roleHelp = document.getElementById('role-help');
            
            roleSelect.addEventListener('change', function() {
                if (this.value === 'staff') {
                    roleHelp.textContent = "Status: Awaiting Admin Approval ❗";
                    roleHelp.style.color = '#ffc107';
                } else if (this.value === 'guest') {
                    roleHelp.textContent = "You'll be ready to login immediately";
                    roleHelp.style.color = '#28a745';
                } else {
                    roleHelp.textContent = "";
                }
            });
            
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