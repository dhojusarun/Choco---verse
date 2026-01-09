<?php
session_start();
// This page is accessible by both guests and logged-in users
require_once '../config/database.php';

// Determine if we show customer or vendor header or guest header
$is_logged_in = isset($_SESSION['user_id']);
$role = $_SESSION['role'] ?? 'guest';
$username = $_SESSION['username'] ?? '';
$customer_id = $is_logged_in ? $_SESSION['user_id'] : null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Us - Choco World</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        @media (max-width: 968px) {
            .about-content-grid {
                grid-template-columns: 1fr !important;
                gap: 2rem !important;
                text-align: center;
            }
            .about-content-grid img {
                order: -1;
            }
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
        // Simple guest header
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
                        <a href="../customer/login.php" class="btn btn-primary" style="padding: 0.5rem 1.5rem;">Login</a>
                    </div>
                </div>
            </div>
        </header>
        <?php
    }
    ?>

    <section class="page-hero">
        <div class="container">
            <h1>🍫 Our Story</h1>
            <p>Crafting sweet moments since 2024</p>
        </div>
    </section>

    <div class="container">
        <div class="dashboard-content">
            <div class="about-content-grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 4rem; align-items: center; margin-bottom: 4rem;">
                <div>
                    <h2 style="font-family: var(--font-heading); color: var(--gold); margin-bottom: 1.5rem;">Passion for Perfection</h2>
                    <p style="margin-bottom: 1.5rem; line-height: 1.8;">At Choco World, we believe chocolate is more than just a treat—it's an experience. Founded in the heart of Kathmandu, our mission is to bring together the finest artisan chocolatiers and chocolate lovers in one premium marketplace.</p>
                    <p style="line-height: 1.8;">Every bar, truffle, and praline listed on our platform is vetted for quality, ensuring that you receive only the most exquisite handcrafted creations made with ethically sourced cocoa and premium ingredients.</p>
                </div>
                <div>
                    <img src="../images/about-image.jpg" alt="Chocolate Crafting" style="width: 100%; border-radius: 30px; box-shadow: 0 20px 40px rgba(0,0,0,0.5); border: 2px solid var(--gold);" onerror="this.src='https://images.unsplash.com/photo-1541173109020-9c5d8a48e169?auto=format&fit=crop&w=800&q=80'">
                </div>
            </div>

            <div class="dashboard-grid">
                <div class="dashboard-card">
                    <h3>🌱 Ethically Sourced</h3>
                    <p>We work exclusively with vendors who prioritize fair trade and sustainable farming practices.</p>
                </div>
                <div class="dashboard-card">
                    <h3>🎨 Artisan Made</h3>
                    <p>Every product is handcrafted by skilled chocolatiers using traditional methods.</p>
                </div>
                <div class="dashboard-card">
                    <h3>✨ Premium Quality</h3>
                    <p>Only the finest ingredients make it into our chocolates. No compromises, ever.</p>
                </div>
            </div>
        </div>
    </div>

    <?php 
    $logo_path = '../images/logo.png';
    include '../includes/footer.php'; 
    ?>
</body>
</html>
