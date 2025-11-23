<?php
require_once '../includes/config.php';

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header("Location: /version2/" . ($_SESSION['role'] === 'admin' ? 'admin/admin_dashboard.php' : 
                          ($_SESSION['role'] === 'staff' ? 'hotel staff/staff_dashboard.php' : 'guest/guest_dashboard.php')));
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    
    // Validation
    $errors = [];
    if (empty($username)) {
        $errors[] = "Username is required.";
    }
    if (empty($password)) {
        $errors[] = "Password is required.";
    }
    
    if (empty($errors)) {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user && password_verify($password, $user['password'])) {
            // Check if staff user is approved
            if ($user['role'] === 'staff' && $user['status'] !== 'active') {
                $errors[] = "Your account is pending admin approval.";
            } else {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['name'] = $user['name'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['status'] = $user['status'];
                
                $_SESSION['success'] = "Welcome back, " . $user['name'] . "!";
                
                // Redirect based on role
                if ($user['role'] === 'admin') {
                    header("Location: /version2/admin/admin_dashboard.php");
                } elseif ($user['role'] === 'staff') {
                    header("Location: /version2/hotel staff/staff_dashboard.php");
                } else {
                    header("Location: /version2/guest/guest_dashboard.php");
                }
                exit();
            }
        } else {
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
            <a href="login.php" class="auth-tab active">Login</a>
            <a href="signup.php" class="auth-tab">Sign Up</a>
        </div>
        
        <form id="login-form" class="auth-form active" method="POST" action="">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" class="form-control" 
                       value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>" 
                       required>
            </div>
            
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" class="form-control" required>
            </div>
            
            <?php if (!empty($errors)): ?>
                <div class="alert alert-error">
                    <?php foreach ($errors as $error): ?>
                        <p><?php echo $error; ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            
            <button type="submit" class="btn btn-primary" style="width: 100%;">Login</button>
        </form>
    </div>
</body>
</html>