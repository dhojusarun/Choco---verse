<?php
session_start();
// Optional login check - allow guests to view cart
$customer_id = $_SESSION['user_id'] ?? null;
$is_logged_in = isset($_SESSION['user_id']) && ($_SESSION['role'] ?? '') === 'customer';

require_once '../../config/database.php';



$wallet_balance = 0;
if ($is_logged_in) {
    // Get customer wallet balance
    $wallet_stmt = $pdo->prepare("SELECT wallet_balance FROM users WHERE id = ?");
    $wallet_stmt->execute([$customer_id]);
    $wallet_balance = $wallet_stmt->fetchColumn();
}

$cart_items = [];
if ($is_logged_in) {
    // Fetch cart items with product details
    $cart_stmt = $pdo->prepare("
        SELECT c.*, p.name, p.price, p.image_url, p.stock, u.username as vendor_name, p.vendor_id
        FROM cart c
        JOIN products p ON c.product_id = p.id
        JOIN users u ON p.vendor_id = u.id
        WHERE c.customer_id = ?
        ORDER BY c.created_at DESC
    ");
    $cart_stmt->execute([$customer_id]);
    $cart_items = $cart_stmt->fetchAll();
}

// Calculate totals
$subtotal = 0;
foreach ($cart_items as $item) {
    $subtotal += $item['price'] * $item['quantity'];
}
$tax = $subtotal * 0.1; // 10% tax
$total = $subtotal + $tax;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shopping Cart - Choco World</title>
    <link rel="stylesheet" href="../../css/style.css">
    <style>
        .cart-container {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 2rem;
            margin-top: 2rem;
        }
        .cart-items {
            background: rgba(255, 255, 255, 0.05);
            padding: 2rem;
            border-radius: 20px;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        .cart-item {
            display: grid;
            grid-template-columns: 120px 1fr auto;
            gap: 1.5rem;
            padding: 1.5rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            align-items: center;
        }
        .cart-item:last-child {
            border-bottom: none;
        }
        .item-image {
            width: 120px;
            height: 120px;
            object-fit: cover;
            border-radius: 15px;
        }
        .item-details h3 {
            color: var(--gold);
            margin-bottom: 0.5rem;
        }
        .item-vendor {
            opacity: 0.7;
            margin-bottom: 0.5rem;
        }
        .item-price {
            font-size: 1.3rem;
            color: var(--gold);
            font-weight: 600;
        }
        .item-actions {
            display: flex;
            flex-direction: column;
            gap: 1rem;
            align-items: flex-end;
        }
        .quantity-control {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            background: rgba(255, 255, 255, 0.1);
            padding: 0.5rem;
            border-radius: 10px;
        }
        .qty-btn {
            width: 35px;
            height: 35px;
            border: none;
            background: var(--gradient-gold);
            color: var(--chocolate-dark);
            border-radius: 8px;
            cursor: pointer;
            font-size: 1.2rem;
            font-weight: bold;
        }
        .qty-input {
            width: 60px;
            text-align: center;
            background: transparent;
            border: none;
            color: var(--cream);
            font-size: 1.1rem;
            font-weight: 600;
        }
        .remove-btn {
            background: rgba(244, 67, 54, 0.2);
            color: #FFCDD2;
            border: 1px solid rgba(244, 67, 54, 0.3);
            padding: 0.5rem 1rem;
            border-radius: 8px;
            cursor: pointer;
            transition: var(--transition-smooth);
        }
        .remove-btn:hover {
            background: rgba(244, 67, 54, 0.3);
        }
        .cart-summary {
            background: rgba(255, 255, 255, 0.05);
            padding: 2rem;
            border-radius: 20px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            height: fit-content;
            position: sticky;
            top: 2rem;
        }
        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 1rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        .summary-row:last-of-type {
            border-bottom: 2px solid var(--gold);
            padding-bottom: 1.5rem;
            margin-bottom: 1.5rem;
        }
        .summary-total {
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--gold);
        }
        .checkout-btn {
            width: 100%;
            padding: 1.2rem;
            font-size: 1.1rem;
            margin-top: 1rem;
        }
        .empty-cart {
            text-align: center;
            padding: 4rem 2rem;
            opacity: 0.7;
        }
        @media (max-width: 968px) {
            .cart-container {
                grid-template-columns: 1fr;
            }
            .cart-item {
                grid-template-columns: 100px 1fr;
            }
            .item-actions {
                grid-column: 1 / -1;
                flex-direction: row;
                justify-content: space-between;
                margin-top: 1rem;
            }
        }
    </style>
</head>
<body>
    <?php 
    $root = $_SERVER['DOCUMENT_ROOT'] . '/project/Choco world';
    if (!$is_logged_in) {
        $customer_id = 0;
        $wallet_balance = 0;
        $username = "Guest";
    }
    include $root . '/includes/customer_header.php'; 
    ?>

    <div class="dashboard">
        <div class="container">
            <div class="dashboard-content">
                <div class="dashboard-header" style="border-bottom: none; margin-bottom: 2rem;">
                    <div class="dashboard-title">
                        <h1>🛒 Shopping Cart</h1>
                        <p>Review your chocolate selections and manage your indulgence</p>
                    </div>
                </div>
            
            <?php if (count($cart_items) > 0): ?>
            <div class="cart-container">
                <div class="cart-items">
                    <h2 style="color: var(--gold); margin-bottom: 1.5rem;">Cart Items (<?php echo count($cart_items); ?>)</h2>
                    
                    <?php foreach ($cart_items as $item): ?>
                    <div class="cart-item" id="item-<?php echo $item['id']; ?>">
                        <img src="../../<?php echo htmlspecialchars($item['image_url']); ?>" 
                             alt="<?php echo htmlspecialchars($item['name']); ?>"
                             class="item-image"
                             onerror="this.src='../../images/products/default-chocolate.jpg'">
                        
                        <div class="item-details">
                            <h3><?php echo htmlspecialchars($item['name']); ?></h3>
                            <div class="item-vendor">by <?php echo htmlspecialchars($item['vendor_name']); ?></div>
                            <div class="item-price">$<?php echo number_format($item['price'], 2); ?> each</div>
                            <small style="opacity: 0.7;">Stock: <?php echo $item['stock']; ?> available</small>
                        </div>
                        
                        <div class="item-actions">
                            <div class="quantity-control">
                                <button class="qty-btn" onclick="updateQuantity(<?php echo $item['id']; ?>, -1, <?php echo $item['stock']; ?>)">-</button>
                                <input type="number" class="qty-input" id="qty-<?php echo $item['id']; ?>" 
                                       value="<?php echo $item['quantity']; ?>" 
                                       min="1" max="<?php echo $item['stock']; ?>" readonly>
                                <button class="qty-btn" onclick="updateQuantity(<?php echo $item['id']; ?>, 1, <?php echo $item['stock']; ?>)">+</button>
                            </div>
                            <button class="remove-btn" onclick="removeItem(<?php echo $item['id']; ?>)">🗑️ Remove</button>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                
                <div class="cart-summary">
                    <h2 style="color: var(--gold); margin-bottom: 1.5rem;">Order Summary</h2>
                    
                    <div class="summary-row">
                        <span>Subtotal:</span>
                        <span id="subtotal">$<?php echo number_format($subtotal, 2); ?></span>
                    </div>
                    
                    <div class="summary-row">
                        <span>Tax (10%):</span>
                        <span id="tax">$<?php echo number_format($tax, 2); ?></span>
                    </div>
                    
                    <div class="summary-row">
                        <span>Shipping:</span>
                        <span style="color: #A5D6A7;">FREE</span>
                    </div>
                    
                    <div class="summary-row">
                        <strong>Total:</strong>
                        <strong class="summary-total" id="total">$<?php echo number_format($total, 2); ?></strong>
                    </div>
                    
                    <div style="background: rgba(33, 150, 243, 0.1); padding: 1rem; border-radius: 10px; margin-bottom: 1rem; border: 1px solid rgba(33, 150, 243, 0.3);">
                        <small style="color: #90CAF9;">💰 Your Wallet Balance: <strong>$<?php echo number_format($wallet_balance, 2); ?></strong></small>
                    </div>
                    
                    <?php if ($wallet_balance < $total): ?>
                    <div style="background: rgba(244, 67, 54, 0.2); padding: 1rem; border-radius: 10px; margin-bottom: 1rem; border: 1px solid rgba(244, 67, 54, 0.3);">
                        <small style="color: #FFCDD2;">⚠️ Insufficient wallet balance! You need $<?php echo number_format($total - $wallet_balance, 2); ?> more to complete this order.</small>
                    </div>
                    <?php endif; ?>
                    
                    <div style="background: rgba(212, 175, 55, 0.1); padding: 1rem; border-radius: 10px; margin-bottom: 1rem;">
                        <small>📦 Estimated delivery: 3-5 business days</small>
                    </div>
                    
                    <button class="btn btn-primary checkout-btn" onclick="proceedToCheckout()" <?php echo $wallet_balance < $total ? 'disabled style="opacity: 0.5; cursor: not-allowed;"' : ''; ?>>
                        Proceed to Checkout 🎁
                    </button>
                    
                    <div style="margin-top: 1rem; text-align: center;">
                        <small style="opacity: 0.7;">Secure payment powered by Choco World</small>
                    </div>
                </div>
            </div>
            <?php else: ?>
            <div class="empty-cart">
                <h2 style="font-size: 3rem;">🛒</h2>
                <h3>Your cart is empty</h3>
                <p>Start adding delicious chocolates to your cart!</p>
                <a href="browse.php" class="btn btn-primary" style="margin-top: 2rem;">Browse Products</a>
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <script>
        function updateQuantity(cartId, change, maxStock) {
            const qtyInput = document.getElementById('qty-' + cartId);
            let newQty = parseInt(qtyInput.value) + change;
            
            if (newQty < 1) newQty = 1;
            if (newQty > maxStock) {
                alert('Maximum stock available: ' + maxStock);
                return;
            }
            
            fetch('update-cart.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'cart_id=' + cartId + '&quantity=' + newQty
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    qtyInput.value = newQty;
                    location.reload(); // Reload to update totals
                } else {
                    alert(data.message);
                }
            });
        }
        
        function removeItem(cartId) {
            if (!confirm('Remove this item from cart?')) return;
            
            fetch('remove-from-cart.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'cart_id=' + cartId
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert(data.message);
                }
            });
        }
        
        function proceedToCheckout() {
            <?php if (!$is_logged_in): ?>
                window.location.href = '../login.php';
                return;
            <?php endif; ?>
            if (!confirm('Proceed with checkout? This will create your order.')) return;
            
            const btn = event.target;
            btn.disabled = true;
            btn.innerHTML = 'Processing...';
            
            fetch('checkout.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'}
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Order placed successfully! Order ID: #' + data.order_id);
                    window.location.href = '../orders/list.php';
                } else {
                    alert('Checkout failed: ' + data.message);
                    btn.disabled = false;
                    btn.innerHTML = 'Proceed to Checkout 🎁';
                }
            });
        }
    </script>

    <?php 
    $logo_path = '../../images/logo.png';
    include '../../includes/footer.php'; 
    ?>
</body>
</html>
