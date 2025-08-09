<?php
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

requireLogin(); // Allow both admin and encoder

// Get current user info
$current_user = getCurrentUser();
$is_admin = isAdmin();

// Default report period - current month
$current_month = date('Y-m');
$current_month_name = date('F Y');
$month = isset($_GET['month']) ? $_GET['month'] : $current_month;
$year = isset($_GET['year']) ? (int)$_GET['year'] : (int)date('Y');

// Get available years for dropdown - filtered by user if encoder
if ($is_admin) {
    $stmt = $conn->prepare("SELECT DISTINCT YEAR(date) as year FROM expenses ORDER BY year DESC");
    $stmt->execute();
} else {
    $stmt = $conn->prepare("SELECT DISTINCT YEAR(date) as year FROM expenses WHERE created_by = ? ORDER BY year DESC");
    $stmt->execute([$current_user['id']]);
}
$years = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);

// Get fund types
$fund_types = ['General Fund', 'Special Education Fund', 'Trust Fund'];

// Get expense types
$expense_types = [
    'Personnel Services',
    'Maintenance and Other Operating Expenses',
    'Capital Outlay',
    'Cash Advance'
];

// Generate monthly report data (modified for encoder filtering)
function generateMonthlyReport($conn, $month, $year, $user_id = null) {
    $start_date = "$year-$month-01";
    $end_date = date('Y-m-t', strtotime($start_date));
    
    // Base query conditions
    $where_condition = "WHERE date BETWEEN ? AND ?";
    $params = [$start_date, $end_date];
    
    // Add user filter for encoders
    if ($user_id) {
        $where_condition .= " AND created_by = ?";
        $params[] = $user_id;
    }
    
    // Total expenses for the month
    $stmt = $conn->prepare("
        SELECT 
            COUNT(*) as count,
            COALESCE(SUM(total), 0) as total_amount
        FROM expenses
        $where_condition
    ");
    $stmt->execute($params);
    $monthly_totals = $stmt->fetch();
    
    // By fund type
    $stmt = $conn->prepare("
        SELECT 
            fund_type,
            COUNT(*) as count,
            COALESCE(SUM(total), 0) as total_amount
        FROM expenses
        $where_condition
        GROUP BY fund_type
        ORDER BY total_amount DESC
    ");
    $stmt->execute($params);
    $by_fund_type = $stmt->fetchAll();
    
    // By expense type
    $stmt = $conn->prepare("
        SELECT 
            expense_type,
            COUNT(*) as count,
            COALESCE(SUM(total), 0) as total_amount
        FROM expenses
        $where_condition
        GROUP BY expense_type
        ORDER BY total_amount DESC
    ");
    $stmt->execute($params);
    $by_expense_type = $stmt->fetchAll();
    
    // By office (only for admin or show encoder's offices)
    $office_query = "
        SELECT 
            o.name as office_name,
            COUNT(*) as count,
            COALESCE(SUM(e.total), 0) as total_amount
        FROM expenses e
        LEFT JOIN offices o ON e.office_id = o.id
        $where_condition
        GROUP BY o.name
        ORDER BY total_amount DESC
        LIMIT 10
    ";
    $stmt = $conn->prepare($office_query);
    $stmt->execute($params);
    $by_office = $stmt->fetchAll();
    
    // By encoder (only show if admin, or show current encoder stats)
    $by_encoder = [];
    if (!$user_id) { // Admin view - show all encoders
        $stmt = $conn->prepare("
            SELECT 
                u.full_name as encoder_name,
                COUNT(*) as count,
                COALESCE(SUM(e.total), 0) as total_amount
            FROM expenses e
            LEFT JOIN users u ON e.created_by = u.id
            $where_condition
            GROUP BY u.full_name
            ORDER BY total_amount DESC
            LIMIT 10
        ");
        $stmt->execute($params);
        $by_encoder = $stmt->fetchAll();
    }
    
    return [
        'monthly_totals' => $monthly_totals,
        'by_fund_type' => $by_fund_type,
        'by_expense_type' => $by_expense_type,
        'by_office' => $by_office,
        'by_encoder' => $by_encoder,
        'start_date' => $start_date,
        'end_date' => $end_date
    ];
}

// Generate annual report data (modified for encoder filtering)
function generateAnnualReport($conn, $year, $user_id = null) {
    $start_date = "$year-01-01";
    $end_date = "$year-12-31";
    
    // Base query conditions
    $where_condition = "WHERE date BETWEEN ? AND ?";
    $params = [$start_date, $end_date];
    
    // Add user filter for encoders
    if ($user_id) {
        $where_condition .= " AND created_by = ?";
        $params[] = $user_id;
    }
    
    // Monthly breakdown
    $stmt = $conn->prepare("
        SELECT 
            DATE_FORMAT(date, '%Y-%m') as month,
            COUNT(*) as count,
            COALESCE(SUM(total), 0) as total_amount
        FROM expenses
        $where_condition
        GROUP BY DATE_FORMAT(date, '%Y-%m')
        ORDER BY month
    ");
    $stmt->execute($params);
    $monthly_breakdown = $stmt->fetchAll();
    
    // By fund type
    $stmt = $conn->prepare("
        SELECT 
            fund_type,
            COUNT(*) as count,
            COALESCE(SUM(total), 0) as total_amount
        FROM expenses
        $where_condition
        GROUP BY fund_type
        ORDER BY total_amount DESC
    ");
    $stmt->execute($params);
    $by_fund_type = $stmt->fetchAll();
    
    // By expense type
    $stmt = $conn->prepare("
        SELECT 
            expense_type,
            COUNT(*) as count,
            COALESCE(SUM(total), 0) as total_amount
        FROM expenses
        $where_condition
        GROUP BY expense_type
        ORDER BY total_amount DESC
    ");
    $stmt->execute($params);
    $by_expense_type = $stmt->fetchAll();
    
    return [
        'monthly_breakdown' => $monthly_breakdown,
        'by_fund_type' => $by_fund_type,
        'by_expense_type' => $by_expense_type,
        'start_date' => $start_date,
        'end_date' => $end_date
    ];
}

// Determine which report to show
$report_type = isset($_GET['report_type']) ? $_GET['report_type'] : 'monthly';

// Pass user ID for filtering if encoder
$filter_user_id = $is_admin ? null : $current_user['id'];

if ($report_type === 'monthly') {
    $report_data = generateMonthlyReport($conn, $month, $year, $filter_user_id);
    $report_title = "Monthly Report for " . date('F Y', strtotime($report_data['start_date']));
    if (!$is_admin) {
        $report_title .= " - " . $current_user['full_name'];
    }
} else {
    $report_data = generateAnnualReport($conn, $year, $filter_user_id);
    $report_title = "Annual Report for $year";
    if (!$is_admin) {
        $report_title .= " - " . $current_user['full_name'];
    }
}
?>
