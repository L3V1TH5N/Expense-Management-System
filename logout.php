<?php
    require_once 'includes/config.php';
    require_once 'includes/auth.php';

    // Debug: Check if session is active and user is logged in
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit();
    }

    // Debug: Store session data before logout
    $userId = $_SESSION['user_id'];
    $userName = $_SESSION['full_name'];
    $username = $_SESSION['username'];

    // Debug: Check if $pdo is available
    if (!isset($pdo)) {
        error_log("PDO not available in logout.php");
        session_destroy();
        header('Location: login.php');
        exit();
    }

    // Manual logout with explicit logging
    try {
        $logoutData = json_encode([
            'username' => $username,
            'logout_time' => date('Y-m-d H:i:s'),
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'Unknown'
        ]);
        
        // Log the logout activity
        logActivity($pdo, $userId, $userName, 'LOGOUT', 'users', $userId, null, $logoutData);
        
        // Debug: Log success
        error_log("Logout activity logged successfully for user: " . $username);
        
    } catch (Exception $e) {
        // Debug: Log the error
        error_log("Failed to log logout activity: " . $e->getMessage());
    }

    // Destroy session and redirect
    session_destroy();
    header('Location: login.php');
    exit();
?>
