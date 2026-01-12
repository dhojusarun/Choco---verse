<?php
session_start();
// Optional login check - allow guests to view cart
$customer_id = $_SESSION['user_id'] ?? null;
$is_logged_in = isset($_SESSION['user_id']) && ($_SESSION['role'] ?? '') === 'customer';

require_once '../../config/database.php';


// Handle count request for navigation badge
if (isset($_GET['count'])) {
    header('Content-Type: application/json');
    $count = 0;
    if ($is_logged_in) {
        $count_stmt = $pdo->prepare("SELECT SUM(quantity) FROM cart WHERE customer_id = ?");
        $count_stmt->execute([$customer_id]);
        $count = (int)$count_stmt->fetchColumn();
    }
    echo json_encode(['count' => $count]);
    exit;
}
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
    <link rel="stylesheet" href="../../css/common.css">
    <link rel="stylesheet" href="../../css/dashboard.css">
    <link rel="stylesheet" href="../../css/cart.css">
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
                        <a href="details.php?id=<?php echo $item['product_id']; ?>">
                            <img src="../../<?php echo htmlspecialchars($item['image_url']); ?>" 
                                 alt="<?php echo htmlspecialchars($item['name']); ?>"
                                 class="item-image"
                                 onerror="this.src='../../images/products/default-chocolate.jpg'">
                        </a>
                        
                        <div class="item-details">
                            <a href="details.php?id=<?php echo $item['product_id']; ?>" style="text-decoration: none;">
                                <h3><?php echo htmlspecialchars($item['name']); ?></h3>
                            </a>
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
                    
                    <div class="payment-methods">
                        <h3 style="color: var(--gold); margin-bottom: 1rem; font-size: 1.2rem;">Select Payment Method</h3>
                        
                        <!-- Online Payment -->
                        <div class="payment-option active" onclick="selectPayment('online')">
                            <div class="option-header">
                                <input type="radio" name="payment_method" value="online" checked id="pay_online">
                                <span class="option-icon">💳</span>
                                <div>
                                    <div style="font-weight: 600;">Online Payment</div>
                                    <small style="opacity: 0.7;">Card, eSewa, or Mobile Banking</small>
                                </div>
                            </div>
                            <div class="sub-options" id="online_sub_options">
                                <!-- Card Option -->
                                <div class="sub-option">
                                    <label class="sub-option-label">
                                        <input type="radio" name="payment_sub_method" value="card" checked onchange="togglePaymentForms()">
                                        <span>Credit / Debit Card</span>
                                    </label>
                                    <div id="card_form" class="payment-form" style="display: block;">
                                        <div class="form-group">
                                            <label>Card Number</label>
                                            <input type="text" id="card_number" placeholder="0000 0000 0000 0000" maxlength="19">
                                        </div>
                                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                                            <div class="form-group">
                                                <label>Expiry Date</label>
                                                <input type="text" id="card_expiry" placeholder="MM/YY" maxlength="5">
                                            </div>
                                            <div class="form-group">
                                                <label>CVV</label>
                                                <input type="password" id="card_cvv" placeholder="***" maxlength="3">
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- eSewa Option -->
                                <div class="sub-option">
                                    <label class="sub-option-label">
                                        <input type="radio" name="payment_sub_method" value="esewa" onchange="togglePaymentForms()">
                                        <span>eSewa</span>
                                    </label>
                                    <div id="esewa_form" class="payment-form">
                                        <div style="text-align: center; margin-bottom: 1rem;">
                                            <img src="https://esewa.com.np/common/images/esewa_logo.png" alt="eSewa" style="height: 30px; filter: brightness(1.5);">
                                        </div>
                                        <div class="form-group">
                                            <label>eSewa ID (Mobile Number / Email)</label>
                                            <input type="text" id="esewa_id" placeholder="98XXXXXXXX">
                                        </div>
                                        <div class="form-group">
                                            <label>eSewa Password / PIN</label>
                                            <input type="password" id="esewa_password" placeholder="******">
                                        </div>
                                    </div>
                                </div>

                                <!-- Mobile Banking Option -->
                                <div class="sub-option">
                                    <label class="sub-option-label">
                                        <input type="radio" name="payment_sub_method" value="mobile_banking" onchange="togglePaymentForms()">
                                        <span>Mobile Banking</span>
                                    </label>
                                    <div id="mobile_banking_form" class="payment-form">
                                        <div class="form-group">
                                            <label>Mobile Number</label>
                                            <input type="text" id="bank_mobile" placeholder="98XXXXXXXX">
                                        </div>
                                        <div class="form-group">
                                            <label>Transaction PIN</label>
                                            <input type="password" id="bank_pin" placeholder="****">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Cash on Delivery -->
                        <div class="payment-option" onclick="selectPayment('cod')">
                            <div class="option-header">
                                <input type="radio" name="payment_method" value="cod" id="pay_cod">
                                <span class="option-icon">💵</span>
                                <div>
                                    <div style="font-weight: 600;">Cash on Delivery</div>
                                    <small style="opacity: 0.7;">Pay when you receive</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <button class="btn btn-primary checkout-btn" id="checkoutBtn" onclick="proceedToCheckout()" <?php echo $wallet_balance < $total ? 'disabled style="opacity: 0.5; cursor: not-allowed;"' : ''; ?>>
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
        
        function selectPayment(method) {
            document.querySelectorAll('.payment-option').forEach(opt => opt.classList.remove('active'));
            const clickedOpt = event.currentTarget;
            clickedOpt.classList.add('active');
            
            const radio = clickedOpt.querySelector('input[name="payment_method"]');
            radio.checked = true;
            
            // Show/hide online forms
            const onlineSub = document.getElementById('online_sub_options');
            if (method === 'online') {
                onlineSub.style.display = 'block';
                togglePaymentForms();
            } else {
                onlineSub.style.display = 'none';
            }

            // Update checkout button state if COD is selected (bypass wallet check visually)
            const checkoutBtn = document.getElementById('checkoutBtn');
            const total = parseFloat(document.getElementById('total').innerText.replace('$', ''));
            const wallet = <?php echo $wallet_balance; ?>;
            
            if (method === 'cod') {
                checkoutBtn.disabled = false;
                checkoutBtn.style.opacity = '1';
                checkoutBtn.style.cursor = 'pointer';
            } else {
                if (wallet < total) {
                    checkoutBtn.disabled = true;
                    checkoutBtn.style.opacity = '0.5';
                    checkoutBtn.style.cursor = 'not-allowed';
                }
            }
        }

        function togglePaymentForms() {
            const subMethod = document.querySelector('input[name="payment_sub_method"]:checked').value;
            document.querySelectorAll('.payment-form').forEach(form => form.style.display = 'none');
            
            if (subMethod === 'card') {
                document.getElementById('card_form').style.display = 'block';
            } else if (subMethod === 'esewa') {
                document.getElementById('esewa_form').style.display = 'block';
            } else if (subMethod === 'mobile_banking') {
                document.getElementById('mobile_banking_form').style.display = 'block';
            }
        }

        function proceedToCheckout() {
            <?php if (!$is_logged_in): ?>
                window.location.href = '../login.php';
                return;
            <?php endif; ?>
            
            const paymentMethod = document.querySelector('input[name="payment_method"]:checked').value;
            let subMethod = 'cod';
            
            if (paymentMethod === 'online') {
                subMethod = document.querySelector('input[name="payment_sub_method"]:checked').value;
                
                // Validate fields
                if (subMethod === 'card') {
                    if (!document.getElementById('card_number').value || 
                        !document.getElementById('card_expiry').value || 
                        !document.getElementById('card_cvv').value) {
                        alert('Please fill in all card details.');
                        return;
                    }
                } else if (subMethod === 'esewa') {
                    if (!document.getElementById('esewa_id').value || 
                        !document.getElementById('esewa_password').value) {
                        alert('Please fill in your eSewa login details.');
                        return;
                    }
                } else if (subMethod === 'mobile_banking') {
                    if (!document.getElementById('bank_mobile').value || 
                        !document.getElementById('bank_pin').value) {
                        alert('Please fill in your Mobile Banking details.');
                        return;
                    }
                }
            }

            if (!confirm(`Proceed with checkout using ${paymentMethod === 'cod' ? 'Cash on Delivery' : subMethod.replace('_', ' ')}?`)) return;
            
            const btn = document.getElementById('checkoutBtn');
            btn.disabled = true;
            btn.innerHTML = 'Processing...';
            
            const formData = new URLSearchParams();
            formData.append('payment_method', paymentMethod);
            formData.append('payment_sub_method', subMethod);

            fetch('checkout.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: formData.toString()
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    if (paymentMethod === 'cod') {
                        alert('Order placed successfully! 📦 Your order has been placed and will be delivered soon.');
                    } else {
                        alert('Payment Verified! Order placed successfully! Order ID: #' + data.order_id);
                    }
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
