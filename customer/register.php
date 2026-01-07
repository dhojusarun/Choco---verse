<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Registration - Choco World</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <div class="auth-page customer-bg">
        <div class="auth-container">
            <a href="../index.php" class="back-link">← Back to Home</a>
            
            <div class="auth-header">
                <h2>Customer Registration</h2>
                <p>Join our chocolate community</p>
            </div>
            
            <form class="auth-form" id="registerForm">
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" placeholder="Choose a username" required>
                </div>
                
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" placeholder="your@email.com" required>
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" placeholder="At least 6 characters" required>
                </div>
                
                <div class="form-group">
                    <label for="confirm_password">Confirm Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" placeholder="Confirm your password" required>
                </div>
                
                <button type="submit" class="btn btn-primary">Create Account</button>
            </form>
            
            <div class="auth-footer">
                <p>Already have an account? <a href="login.php">Login here</a></p>
            </div>
        </div>
    </div>
    
    <script src="../js/main.js"></script>
    <script>
        const registerForm = document.getElementById('registerForm');
        handleFormSubmit(registerForm, '../auth/customer_register.php', 'login.php');
    </script>
</body>
</html>
