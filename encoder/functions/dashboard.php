<?php
    require_once '../includes/config.php';
    require_once '../includes/db.php';
    require_once '../includes/auth.php';
    require_once '../includes/functions.php';

    requireLogin();

    $user_id = $_SESSION['user_id'];
    $full_name = isset($_SESSION['full_name']) ? $_SESSION['full_name'] : 'User';

    $current_month = date('Y-m');

    $stmt = $conn->prepare("
        SELECT COUNT(*) as my_expenses 
        FROM expenses 
        WHERE created_by = ? 
        AND DATE_FORMAT(date, '%Y-%m') = ?
    ");
    $stmt->execute([$user_id, $current_month]);
    $my_expenses = $stmt->fetch()['my_expenses'];

    $stmt = $conn->prepare("
        SELECT 
            fund_type,
            SUM(total) as fund_total,
            COUNT(*) as fund_count
        FROM expenses 
        WHERE created_by = ? 
        AND DATE_FORMAT(date, '%Y-%m') = ?
        GROUP BY fund_type
    ");
    $stmt->execute([$user_id, $current_month]);
    $fund_totals = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $general_fund = 0;
    $sef_fund = 0;
    $trust_fund = 0;
    $total_monthly_amount = 0;

    foreach ($fund_totals as $fund) {
        switch ($fund['fund_type']) {
            case 'General Fund':
                $general_fund = $fund['fund_total'];
                break;
            case 'Special Education Fund':
                $sef_fund = $fund['fund_total'];
                break;
            case 'Trust Fund':
                $trust_fund = $fund['fund_total'];
                break;
        }
        $total_monthly_amount += $fund['fund_total'];
    }

    $stmt = $conn->prepare("
        SELECT COUNT(*) as my_cash_advances 
        FROM expenses 
        WHERE created_by = ? 
        AND expense_type = 'Cash Advance'
        AND DATE_FORMAT(date, '%Y-%m') = ?
    ");
    $stmt->execute([$user_id, $current_month]);
    $my_cash_advances = $stmt->fetch()['my_cash_advances'];

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
    $recent_expenses = $stmt->fetchAll();

    $stmt = $pdo->prepare("
        SELECT al.*, u.full_name 
        FROM activity_logs al 
        JOIN users u ON al.user_id = u.id 
        WHERE al.user_id = ?
        ORDER BY al.action_time DESC 
        LIMIT 10
    ");
    $stmt->execute([$user_id]);
    $my_logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $current_month_name = date('F Y');
?>
