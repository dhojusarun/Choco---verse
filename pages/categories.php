<?php
session_start();
require_once '../config/database.php';

$is_logged_in = isset($_SESSION['user_id']);
$role = $_SESSION['role'] ?? 'guest';
$username = $_SESSION['username'] ?? '';
$customer_id = $is_logged_in ? $_SESSION['user_id'] : null;

// Categories for display
$categories = [
    ['name' => 'Artisan Truffles', 'icon' => '🍬', 'count' => 24, 'desc' => 'Handcrafted truffles with exotic fillings.'],
    ['name' => 'Dark Chocolate', 'icon' => '🍫', 'count' => 18, 'desc' => 'Pure, intense cocoa experience.'],
    ['name' => 'Milk Chocolate', 'icon' => '🥛', 'count' => 15, 'desc' => 'Smooth, creamy classics loved by all.'],
    ['name' => 'Assorted Gifts', 'icon' => '🎁', 'count' => 12, 'desc' => 'Perfectly curated sets for any occasion.'],
    ['name' => 'Baking Cocoa', 'icon' => '🧁', 'count' => 8, 'desc' => 'Professional grade ingredients for your kitchen.'],
    ['name' => 'Limited Editions', 'icon' => '✨', 'count' => 5, 'desc' => 'Seasonal specials and rare chocolate find.']
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Product Categories - Choco World</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .category-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 2rem;
            margin-top: 2rem;
        }

        .category-card {
            background: rgba(255, 255, 255, 0.05);
            padding: 2.5rem;
            border-radius: 20px;
            text-decoration: none;
            color: var(--cream);
            border: 1px solid rgba(255, 255, 255, 0.1);
            transition: var(--transition-smooth);
            text-align: center;
        }

        .category-card:hover {
            transform: translateY(-10px);
            border-color: var(--gold);
            background: rgba(255, 255, 255, 0.08);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
        }

        .category-icon {
            font-size: 3.5rem;
            margin-bottom: 1.5rem;
            display: block;
        }

        .category-card h3 {
            color: var(--gold);
            font-size: 1.5rem;
            margin-bottom: 1rem;
        }

        .category-card p {
            opacity: 0.8;
            font-size: 0.95rem;
            line-height: 1.6;
            margin-bottom: 1.5rem;
        }

        .category-count {
            display: inline-block;
            padding: 0.5rem 1rem;
            background: rgba(212, 175, 55, 0.1);
            color: var(--gold);
            border-radius: 50px;
            font-size: 0.85rem;
            font-weight: 600;
        }

        @media (max-width: 768px) {
            .category-card {
                padding: 1.5rem;
            }
            .category-icon {
                font-size: 2.5rem;
            }
            .category-card h3 {
                font-size: 1.2rem;
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
            <h1>📂 Product Categories</h1>
            <p>Explore our world of chocolate variety</p>
        </div>
    </section>

    <div class="container">
        <div class="category-grid">
            <?php foreach ($categories as $cat): ?>
                <a href="../customer/products/browse.php?category=<?php echo urlencode($cat['name']); ?>" class="category-card">
                    <span class="category-icon"><?php echo $cat['icon']; ?></span>
                    <h3><?php echo $cat['name']; ?></h3>
                    <p><?php echo $cat['desc']; ?></p>
                    <span class="category-count"><?php echo $cat['count']; ?> Products</span>
                </a>
            <?php endforeach; ?>
        </div>
    </div>

    <?php 
    $logo_path = '../images/logo.png';
    include '../includes/footer.php'; 
    ?>
</body>
</html>
