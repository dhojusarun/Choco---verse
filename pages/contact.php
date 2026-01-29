<?php
session_start();
require_once '../config/database.php';

$is_logged_in = isset($_SESSION['user_id']);
$role = $_SESSION['role'] ?? 'guest';
$username = $_SESSION['username'] ?? '';
$customer_id = $is_logged_in ? $_SESSION['user_id'] : null;

$success = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // In a real app, we'd save the message or send an email
    $success = true;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Us - Choco World</title>
    <link rel="stylesheet" href="../css/common.css">
    <link rel="stylesheet" href="../css/pages.css">
    <link rel="stylesheet" href="../css/contact.css">
</head>
<body>
    <?php 
    if ($role === 'customer') {
        include '../includes/customer_header.php';
    } elseif ($role === 'vendor') {
        include '../includes/vendor_header.php';
    } else {
        ?>
        <header class="site-header">
            <div class="container">
                <div class="header-content">
                    <a href="../index.php" class="header-logo">
                        <img src="../images/logo.png" alt="Choco World">
                        <span>Choco World</span>
                    </a>
                    <nav class="main-nav">
                        <a href="../index.php" class="nav-link">🏠 Home</a>
                        <a href="about.php" class="nav-link">ℹ️ About</a>
                        <a href="contact.php" class="nav-link">📞 Contact</a>
                    </nav>
                    <div class="header-actions">
                        <a href="../customer/login.php" class="btn btn-primary">Login</a>
                    </div>
                </div>
            </div>
        </header>
        <?php
    }
    ?>

    <section class="page-hero">
        <div class="container">
            <h1>📞 Get in Touch</h1>
            <p>Have a question or want to share your love for chocolate? We're here to help!</p>
        </div>
    </section>

    <div class="container">
        <?php if ($success): ?>
            <div class="alert alert-success show">
                ✨ Thank you! Your message has been sent successfully. We'll get back to you soon.
            </div>
        <?php endif; ?>

        <div class="contact-grid">
            <div class="contact-info-card">
                <div class="info-item">
                    <div class="info-icon">📍</div>
                    <div class="info-content">
                        <h3>Visit Our Boutique</h3>
                        <p>123 Chocolate Avenue<br>Sweet District, Kathmandu<br>Nepal - 44600</p>
                    </div>
                </div>
                
                <div class="info-item">
                    <div class="info-icon">✉️</div>
                    <div class="info-content">
                        <h3>Email Enquiries</h3>
                        <p>info@chocoworld.com<br>support@chocoworld.com</p>
                    </div>
                </div>
                
                <div class="info-item">
                    <div class="info-icon">📞</div>
                    <div class="info-content">
                        <h3>Direct Hotline</h3>
                        <p>+977 984-1234567<br>Mon-Fri, 9:00 AM - 8:00 PM</p>
                    </div>
                </div>

                <div class="info-item">
                    <div class="info-icon">🌟</div>
                    <div class="info-content">
                        <h3>Connect with Us</h3>
                        <div class="contact-socials">
                            <a href="#" class="social-circle">📘</a>
                            <a href="#" class="social-circle">📷</a>
                            <a href="#" class="social-circle">🐦</a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="contact-form-container">
                <h2>Send us a Message</h2>
                <form method="POST">
                    <div class="form-row">
                        <div class="form-group">
                            <label>Full Name</label>
                            <input type="text" name="name" placeholder="Enter your name" required>
                        </div>
                        <div class="form-group">
                            <label>Email Address</label>
                            <input type="email" name="email" placeholder="Enter your email" required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Subject</label>
                        <input type="text" name="subject" placeholder="What is this regarding?" required>
                    </div>

                    <div class="form-group">
                        <label>Your Message</label>
                        <textarea name="message" placeholder="Tell us more about your inquiry..." required></textarea>
                    </div>

                    <button type="submit" class="btn btn-primary btn-submit">
                        🚀 Send Message
                    </button>
                </form>
            </div>
        </div>
    </div>

    <?php 
    $logo_path = '../images/logo.png';
    include '../includes/footer.php'; 
    ?>
</body>
</html>
