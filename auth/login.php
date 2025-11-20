<?php
require_once '../includes/config.php';

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header("Location: " . ($_SESSION['role'] === 'admin' ? '../admin_dashboard.php' : '../dashboard.php'));
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    
    // Validation
    $errors = [];
    if (empty($email)) {
        $errors[] = "Email is required.";
    }
    if (empty($password)) {
        $errors[] = "Password is required.";
    }
    
    if (empty($errors)) {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['name'] = $user['name'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['role'] = $user['role'];
            
            $_SESSION['success'] = "Welcome back, " . $user['name'] . "!";
            header("Location: " . ($user['role'] === 'admin' ? '../admin_dashboard.php' : '../dashboard.php'));
            exit();
        } else {
            $errors[] = "Invalid email or password.";
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
    <link rel="stylesheet" href="../style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    
</head>
<body>
    <div class="auth-container">
        <!-- Home Button Section -->
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
        
        <!-- Login Form -->
        <form id="login-form" class="auth-form active" method="POST" action="">
            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" class="form-control" 
                       value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" 
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