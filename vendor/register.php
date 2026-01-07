<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vendor Registration - Choco World</title>
    <link rel="stylesheet" href="../css/style.css">
</head>

    <?php
    //  include $root . '/includes/vendor_header.php'; ?>

    <div class="auth-page vendor-bg">
        <div class="auth-container">
            <a href="../index.php" class="back-link">← Back to Home</a>
            
            <div class="auth-header">
                <h2>Vendor Registration</h2>
                <p>Start selling your chocolate products</p>
            </div>
            
            <form class="auth-form" id="registerForm">
                <div class="form-group">
                    <label for="username">Business Name</label>
                    <input type="text" id="username" name="username" placeholder="Your business name" required>
                </div>
                
                <div class="form-group">
                    <label for="email">Business Email</label>
                    <input type="email" id="email" name="email" placeholder="business@email.com" required>
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" placeholder="At least 6 characters" required>
                </div>
                
                <div class="form-group">
                    <label for="confirm_password">Confirm Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" placeholder="Confirm your password" required>
                </div>
                
                <button type="submit" class="btn btn-primary">Create Vendor Account</button>
            </form>
            
            <div class="auth-footer">
                <p>Already have an account? <a href="login.php">Login here</a></p>
            </div>
        </div>
    </div>
    
    <script src="../js/main.js"></script>
    <script>
        const registerForm = document.getElementById('registerForm');
        handleFormSubmit(registerForm, '../auth/vendor_register.php', 'login.php');
    </script>
</body>
</html>
