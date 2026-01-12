<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vendor Login - Choco World</title>
    <link rel="stylesheet" href="../css/common.css">
    <link rel="stylesheet" href="../css/auth.css">
</head>
<body>
    <div class="auth-page vendor-bg">
        <div class="auth-container">
            <a href="../index.php" class="back-link">← Back to Home</a>
            
            <div class="auth-header">
                <h2>Vendor Login</h2>
                <p>Access your business portal</p>
            </div>
            
            <form class="auth-form" id="loginForm">
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" placeholder="your@business.com" required>
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" placeholder="Enter your password" required>
                </div>
                
                <button type="submit" class="btn btn-primary">Login</button>
            </form>
            
            <div class="auth-footer">
                <p>Don't have a vendor account? <a href="register.php">Register here</a></p>
            </div>
        </div>
    </div>
    
    <script src="../js/main.js"></script>
    <script>
        const loginForm = document.getElementById('loginForm');
        handleFormSubmit(loginForm, '../auth/vendor_login.php', 'dashboard.php');
    </script>
</body>
</html>
