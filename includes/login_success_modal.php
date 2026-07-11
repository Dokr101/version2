<?php
if (isset($_SESSION['login_success']) && $_SESSION['login_success']): 
    unset($_SESSION['login_success']); // Clear the flag so it doesn't show again on refresh
?>
<!-- Login Success Modal -->
<div id="loginSuccessModal" class="login-modal">
    <div class="login-modal-content">
        <div class="login-icon">
            <i class="fas fa-check-circle"></i>
        </div>
        <h2>Welcome!</h2>
        <p>You have successfully logged in.</p>
    </div>
</div>

<style>
.login-modal {
    display: block; /* Hidden by default */
    position: fixed; 
    z-index: 9999; 
    left: 0;
    top: 0;
    width: 100%; 
    height: 100%; 
    background-color: rgba(0,0,0,0.5); 
    animation: fadeIn 0.5s;
}

.login-modal-content {
    background-color: #fefefe;
    margin: 15% auto; 
    padding: 30px;
    border: 1px solid #888;
    width: 300px;
    text-align: center;
    border-radius: 10px;
    box-shadow: 0 4px 8px rgba(0,0,0,0.2);
    position: relative;
    animation: slideDown 0.5s;
}

.login-icon {
    color: #28a745;
    font-size: 50px;
    margin-bottom: 15px;
}

.login-modal h2 {
    color: #333;
    margin-bottom: 10px;
}

.login-modal p {
    color: #666;
}

@keyframes fadeIn {
    from {opacity: 0;}
    to {opacity: 1;}
}

@keyframes slideDown {
    from {transform: translateY(-50px); opacity: 0;}
    to {transform: translateY(0); opacity: 1;}
}

@keyframes fadeOut {
    from {opacity: 1;}
    to {opacity: 0;}
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('loginSuccessModal');
    if (modal) {
        // Auto hide after 5 seconds
        setTimeout(function() {
            modal.style.animation = 'fadeOut 0.5s';
            setTimeout(function() {
                modal.style.display = 'none';
            }, 450); // Wait for animation to almost finish
        }, 1800);
    }
});
</script>
<?php endif; ?>
