<?php
/**
 * User Profile Page
 * TerraTrade Land Trading System
 */

require_once 'config/config.php';

// Require login
Auth::requireLogin();

$currentUser = Auth::getCurrentUser();
$userId = $currentUser['id'];

// Get full user details from database
$user = fetchOne("
    SELECT 
        id, email, full_name, phone, role, status, 
        kyc_status, profile_image, created_at, updated_at, 
        last_login, email_verified, phone_verified
    FROM users 
    WHERE id = ?
", [$userId]);

if (!$user) {
    header('Location: index.php');
    exit;
}

// Get user statistics
$stats = [];

// Count active listings
$stats['active_listings'] = fetchOne("
    SELECT COUNT(*) as count 
    FROM properties 
    WHERE user_id = ? AND status = 'active'
", [$userId])['count'];

// Count total listings
$stats['total_listings'] = fetchOne("
    SELECT COUNT(*) as count 
    FROM properties 
    WHERE user_id = ?
", [$userId])['count'];

// Count offers made (as buyer)
$stats['offers_made'] = fetchOne("
    SELECT COUNT(*) as count 
    FROM offers 
    WHERE buyer_id = ?
", [$userId])['count'];

// Count offers received (as seller)
$stats['offers_received'] = fetchOne("
    SELECT COUNT(*) as count 
    FROM offers o
    JOIN properties p ON o.property_id = p.id
    WHERE p.user_id = ?
", [$userId])['count'];

// Count active contracts
$stats['active_contracts'] = fetchOne("
    SELECT COUNT(*) as count 
    FROM contracts 
    WHERE (buyer_id = ? OR seller_id = ?) 
    AND status IN ('pending_signatures', 'signed')
", [$userId, $userId])['count'];

// Count completed transactions
$stats['completed_transactions'] = fetchOne("
    SELECT COUNT(*) as count 
    FROM contracts 
    WHERE (buyer_id = ? OR seller_id = ?) 
    AND status = 'completed'
", [$userId, $userId])['count'];

// Get KYC documents
$kycDocuments = fetchAll("
    SELECT id, document_type, front_image, back_image, status, 
           rejection_reason, created_at, reviewed_at
    FROM kyc_documents 
    WHERE user_id = ?
    ORDER BY created_at DESC
", [$userId]);

// Get recent activity from audit logs
$recentActivity = fetchAll("
    SELECT action, table_name, ip_address, created_at
    FROM audit_logs 
    WHERE user_id = ?
    ORDER BY created_at DESC
    LIMIT 10
", [$userId]);

// Get active sessions
$activeSessions = fetchAll("
    SELECT id, ip_address, user_agent, last_activity
    FROM user_sessions 
    WHERE user_id = ?
    ORDER BY last_activity DESC
", [$userId]);

// Generate CSRF token
$csrfToken = generateCSRFToken();

// Handle success/error messages
$successMessage = $_SESSION['success_message'] ?? null;
$errorMessage = $_SESSION['error_message'] ?? null;
unset($_SESSION['success_message'], $_SESSION['error_message']);
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <title>My Profile - <?php echo htmlspecialchars($user['full_name']); ?> | TerraTrade</title>
    
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="styles.css" />
    <link rel="stylesheet" href="css/profile.css" />
</head>
<body>
    <?php include 'includes/profile-header.php'; ?>

    <main class="container profile-container">
        <?php include 'includes/profile-hero.php'; ?>
        
        <?php if ($successMessage): ?>
            <div class="alert alert-success">
                ✓ <?php echo htmlspecialchars($successMessage); ?>
            </div>
        <?php endif; ?>

        <?php if ($errorMessage): ?>
            <div class="alert alert-error">
                ✗ <?php echo htmlspecialchars($errorMessage); ?>
            </div>
        <?php endif; ?>

        <?php include 'includes/profile-stats.php'; ?>
        
        <!-- Tab Navigation -->
        <div class="profile-tabs">
            <button class="tab-btn active" data-tab="overview">Overview</button>
            <button class="tab-btn" data-tab="personal">Personal Info</button>
            <button class="tab-btn" data-tab="kyc">KYC Verification</button>
            <button class="tab-btn" data-tab="security">Security</button>
            <button class="tab-btn" data-tab="activity">Activity</button>
            <button class="tab-btn" data-tab="preferences">Privacy</button>
        </div>

        <!-- Tab Content -->
        <div class="tab-content">
            <?php include 'includes/profile-tabs/overview.php'; ?>
            <?php include 'includes/profile-tabs/personal.php'; ?>
            <?php include 'includes/profile-tabs/kyc.php'; ?>
            <?php include 'includes/profile-tabs/security.php'; ?>
            <?php include 'includes/profile-tabs/activity.php'; ?>
            <?php include 'includes/profile-tabs/preferences.php'; ?>
        </div>
    </main>

    <footer class="site-footer">
        <div class="container footer-inner">
            <div>
                <strong>terratrade</strong>
                <div class="muted small">Full System — Data Privacy (RA 10173) • E-sign (RA 8792)</div>
            </div>
            <div class="muted small">© <?php echo date('Y'); ?> terratrade</div>
        </div>
    </footer>

    <script>
        window.ProfileData = {
            userId: <?php echo $userId; ?>,
            csrfToken: '<?php echo $csrfToken; ?>',
            baseUrl: '<?php echo BASE_URL; ?>'
        };
    </script>
    <script src="js/profile.js"></script>
    <script src="js/notifications.js"></script>
</body>
</html>
