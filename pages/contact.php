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
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .contact-grid {
            display: grid;
            grid-template-columns: 1fr 2fr;
            gap: 3rem;
            margin-top: 2rem;
        }
        .contact-info-card {
            background: rgba(255, 255, 255, 0.05);
            padding: 2rem;
            border-radius: 20px;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        .info-item {
            margin-bottom: 2rem;
        }
        .info-item h4 {
            color: var(--gold);
            margin-bottom: 0.5rem;
            font-size: 1.2rem;
        }
        .contact-form {
            background: rgba(255, 255, 255, 0.05);
            padding: 3rem;
            border-radius: 25px;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5rem;
            margin-bottom: 1.5rem;
        }
        textarea.form-control {
            width: 100%;
            background: rgba(255, 255, 255, 0.1);
            border: 2px solid rgba(212, 175, 55, 0.3);
            border-radius: 15px;
            color: var(--cream);
            padding: 1rem;
            min-height: 150px;
            font-family: var(--font-body);
        }
        @media (max-width: 768px) {
            .contact-grid { grid-template-columns: 1fr; }
            .form-row { grid-template-columns: 1fr; }
        }
    </style>
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
            <p>We'd love to hear from you!</p>
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
                    <h4>📍 Visit Us</h4>
                    <p>123 Chocolate Avenue<br>Sweet District, Kathmandu<br>Nepal</p>
                </div>
                <div class="info-item">
                    <h4>✉️ Email Us</h4>
                    <p>info@chocoworld.com<br>support@chocoworld.com</p>
                </div>
                <div class="info-item">
                    <h4>📞 Call Us</h4>
                    <p>+977 984-1234567<br>Mon-Fri, 9am - 8pm</p>
                </div>
                <div class="info-item">
                    <h4>🌟 Socials</h4>
                    <p>@ChocoWorldOfficial</p>
                </div>
            </div>

            <div class="contact-form">
                <h3 style="color: var(--gold); margin-bottom: 2rem;">Send us a message</h3>
                <form method="POST">
                    <div class="form-row">
                        <div class="form-group" style="margin-bottom:0">
                            <label>Your Name</label>
                            <input type="text" placeholder="John Doe" required>
                        </div>
                        <div class="form-group" style="margin-bottom:0">
                            <label>Email Address</label>
                            <input type="email" placeholder="john@example.com" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Subject</label>
                        <input type="text" placeholder="Inquiry about artisan truffles">
                    </div>
                    <div class="form-group">
                        <label>Message</label>
                        <textarea class="form-control" placeholder="Write your message here..." required></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary" style="width: 100%;">🚀 Send Message</button>
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
