<?php
    require_once '../includes/config.php';
    require_once '../includes/db.php';
    require_once '../includes/auth.php';
    require_once '../includes/functions.php';

    requireLogin();

    $user_id = $_SESSION['user_id'];

    // Get recent expense activities (last 20)
    $expense_activities = getRecentActivities($pdo, 20, $user_id);

    // Get login/logout activities (last 20)
    $stmt = $pdo->prepare("
        SELECT * FROM activity_logs 
        WHERE user_id = ? AND action IN ('LOGIN', 'LOGOUT')
        ORDER BY action_time DESC 
        LIMIT 20
    ");
    $stmt->execute([$user_id]);
    $auth_activities = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get recent expenses (last 10)
    $stmt = $conn->prepare("
        SELECT e.*, o.name as office_name, so.name as sub_office_name 
        FROM expenses e 
        LEFT JOIN offices o ON e.office_id = o.id 
        LEFT JOIN offices so ON e.sub_office_id = so.id 
        WHERE e.created_by = ?
        ORDER BY e.created_at DESC 
        LIMIT 10
    ");
    $stmt->execute([$user_id]);
    $recent_expenses = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
