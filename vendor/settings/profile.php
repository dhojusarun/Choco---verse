<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'vendor') {
    header('Location: ../login.php');
    exit;
}

require_once '../../config/database.php';

$vendor_id = $_SESSION['user_id'];
$success = $error = '';

// Fetch vendor info
$vendor_stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$vendor_stmt->execute([$vendor_id]);
$vendor = $vendor_stmt->fetch();

// Fetch or create vendor settings
$settings_stmt = $pdo->prepare("SELECT * FROM vendor_settings WHERE vendor_id = ?");
$settings_stmt->execute([$vendor_id]);
$settings = $settings_stmt->fetch();

if (!$settings) {
    // Create default settings
    $create_stmt = $pdo->prepare("INSERT INTO vendor_settings (vendor_id) VALUES (?)");
    $create_stmt->execute([$vendor_id]);
    $settings = [
        'business_description' => '',
        'phone' => '',
        'address' => '',
        'business_hours' => '',
        'logo_url' => '',
        'banner_url' => ''
    ];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $business_description = trim($_POST['business_description'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $business_hours = trim($_POST['business_hours'] ?? '');
    
    try {
        $update_stmt = $pdo->prepare("
            UPDATE vendor_settings 
            SET business_description = ?, phone = ?, address = ?, business_hours = ?
            WHERE vendor_id = ?
        ");
        $update_stmt->execute([$business_description, $phone, $address, $business_hours, $vendor_id]);
        
        $success = 'Settings updated successfully!';
        
        // Refresh settings
        $settings_stmt->execute([$vendor_id]);
        $settings = $settings_stmt->fetch();
        
    } catch (PDOException $e) {
        $error = 'Failed to update settings: ' . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Store Settings - Choco World</title>
    <link rel="stylesheet" href="../../css/common.css">
    <link rel="stylesheet" href="../../css/dashboard.css">
    <link rel="stylesheet" href="../../css/settings.css">
</head>

    <?php 
    $root = $_SERVER['DOCUMENT_ROOT'] . '/project/Choco world';

    include $root . '/includes/vendor_header.php'; ?>

    <div class="dashboard">
        <div class="container">
            <div class="dashboard-header">
                <div class="dashboard-title">
                    <h1>🏪 Store Settings</h1>
                    <p>Manage your business information</p>
                </div>
                <a href="../dashboard.php" class="btn btn-secondary">← Back to Dashboard</a>
            </div>
            
            <div class="settings-container">
                <?php if ($success): ?>
                    <div class="alert alert-success show"><?php echo htmlspecialchars($success); ?></div>
                <?php endif; ?>
                <?php if ($error): ?>
                    <div class="alert alert-error show"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>
                
                <div class="settings-section">
                    <h3>📋 Account Information</h3>
                    <div class="info-card">
                        <div class="info-row">
                            <span class="info-label">Business Name:</span>
                            <span class="info-value"><?php echo htmlspecialchars($vendor['username']); ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Email:</span>
                            <span class="info-value"><?php echo htmlspecialchars($vendor['email']); ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Member Since:</span>
                            <span class="info-value"><?php echo date('F d, Y', strtotime($vendor['created_at'])); ?></span>
                        </div>
                    </div>
                </div>
                
                <form method="POST">
                    <div class="settings-section">
                        <h3>🏢 Business Details</h3>
                        
                        <div class="form-row">
                            <div class="form-group form-full">
                                <label for="business_description">Business Description</label>
                                <textarea id="business_description" name="business_description" 
                                          placeholder="Tell customers about your chocolate business..."><?php echo htmlspecialchars($settings['business_description'] ?? ''); ?></textarea>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="phone">Phone Number</label>
                                <input type="tel" id="phone" name="phone" 
                                       placeholder="+1 (555) 123-4567"
                                       value="<?php echo htmlspecialchars($settings['phone'] ?? ''); ?>">
                            </div>
                            
                            <div class="form-group">
                                <label for="business_hours">Business Hours</label>
                                <input type="text" id="business_hours" name="business_hours" 
                                       placeholder="Mon-Fri: 9AM-6PM"
                                       value="<?php echo htmlspecialchars($settings['business_hours'] ?? ''); ?>">
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group form-full">
                                <label for="address">Business Address</label>
                                <textarea id="address" name="address" 
                                          placeholder="Enter your complete business address..."
                                          style="min-height: 80px;"><?php echo htmlspecialchars($settings['address'] ?? ''); ?></textarea>
                            </div>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-primary" style="width: 100%; font-size: 1.1rem;">
                        💾 Save Settings
                    </button>
                </form>
                
                <div class="settings-section" style="margin-top: 3rem;">
                    <h3>💡 Tips for Success</h3>
                    <div style="background: rgba(255, 255, 255, 0.05); padding: 1.5rem; border-radius: 15px;">
                        <ul style="color: var(--cream); line-height: 2; list-style-position: inside;">
                            <li>Keep your business description engaging and informative</li>
                            <li>Provide accurate contact information for customer trust</li>
                            <li>Add high-quality product images to boost sales</li>
                            <li>Respond promptly to customer inquiries</li>
                            <li>Update your inventory regularly</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php 
    $logo_path = '../../images/logo.png';
    include '../../includes/footer.php'; 
    ?>
</body>
</html>
