<?php
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

requireLogin();

require_once '../includes/SimpleExcelGenerator.php';

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['full_name'];
$is_admin = isAdmin();

try {
    if (!isset($_POST['report_type']) || !isset($_POST['period_type'])) {
        throw new Exception('Missing required parameters');
    }
    
    $report_type = $_POST['report_type'];
    $period_type = $_POST['period_type'];
    
    if (!isset($_POST['fund_type']) || empty($_POST['fund_type'])) {
        throw new Exception('Fund type is required');
    }
    
    if (!isset($_POST['bank']) || empty($_POST['bank'])) {
        throw new Exception('Bank is required');
    }
    
    $fund_type = sanitizeInput($_POST['fund_type']);
    $bank = sanitizeInput($_POST['bank']);
    $expense_type_filter = isset($_POST['expense_type']) && !empty($_POST['expense_type']) 
                          ? sanitizeInput($_POST['expense_type']) 
                          : null;
    
    // Admin-only filters
    $office_id_filter = $is_admin && isset($_POST['office_id']) && !empty($_POST['office_id']) 
                      ? (int)$_POST['office_id'] 
                      : null;
    
    $sub_office_id_filter = $is_admin && isset($_POST['sub_office_id']) && !empty($_POST['sub_office_id']) 
                          ? (int)$_POST['sub_office_id'] 
                          : null;
    
    $created_by_filter = $is_admin && isset($_POST['created_by']) && !empty($_POST['created_by']) 
                       ? (int)$_POST['created_by'] 
                       : null;
    
    $period_description = '';
    
    switch ($period_type) {
        case 'monthly':
            if (!isset($_POST['month']) || !isset($_POST['year']) || empty($_POST['month']) || empty($_POST['year'])) {
                throw new Exception('Month and year are required for monthly reports');
            }
            
            $month = (int)$_POST['month'];
            $year = (int)$_POST['year'];
            
            $start_date = sprintf('%04d-%02d-01', $year, $month);
            $end_date = date('Y-m-t', strtotime($start_date));
            
            $month_names = [
                1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
                5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
                9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December'
            ];
            
            $period_description = $month_names[$month] . ' ' . $year;
            break;
            
        case 'quarterly':
            if (!isset($_POST['quarter']) || !isset($_POST['quarter_year']) || empty($_POST['quarter']) || empty($_POST['quarter_year'])) {
                throw new Exception('Quarter and year are required for quarterly reports');
            }
            
            $quarter = (int)$_POST['quarter'];
            $year = (int)$_POST['quarter_year'];
            
            $quarter_months = [
                1 => ['start' => 1, 'end' => 3],
                2 => ['start' => 4, 'end' => 6],
                3 => ['start' => 7, 'end' => 9],
                4 => ['start' => 10, 'end' => 12]
            ];
            
            $start_date = sprintf('%04d-%02d-01', $year, $quarter_months[$quarter]['start']);
            $end_date = date('Y-m-t', strtotime(sprintf('%04d-%02d-01', $year, $quarter_months[$quarter]['end'])));
            $period_description = 'Q' . $quarter . ' ' . $year;
            break;
            
        case 'yearly':
            $year_field = 'yearly_year';
            if (!isset($_POST[$year_field]) || empty($_POST[$year_field])) {
                throw new Exception('Year is required for yearly reports');
            }
            
            $year = (int)$_POST[$year_field];
            $start_date = $year . '-01-01';
            $end_date = $year . '-12-31';
            $period_description = 'Year ' . $year;
            break;
            
        case 'custom':
            if (!isset($_POST['date_from']) || !isset($_POST['date_to']) || empty($_POST['date_from']) || empty($_POST['date_to'])) {
                throw new Exception('From date and to date are required for custom range reports');
            }
            
            $start_date = $_POST['date_from'];
            $end_date = $_POST['date_to'];
            
            if (strtotime($start_date) > strtotime($end_date)) {
                throw new Exception('From date cannot be later than to date');
            }
            
            $period_description = date('M j, Y', strtotime($start_date)) . ' to ' . date('M j, Y', strtotime($end_date));
            break;
            
        default:
            throw new Exception('Invalid period type');
    }
    
    $where_conditions = [
        'e.date >= ?', 
        'e.date <= ?',
        'e.fund_type = ?',
        'e.bank = ?'
    ];
    $params = [$start_date, $end_date, $fund_type, $bank];
    
    // For non-admin users, only show their own expenses
    if (!$is_admin) {
        $where_conditions[] = 'e.created_by = ?';
        $params[] = $user_id;
    }
    
    if ($report_type === 'issued_checks') {
        $where_conditions[] = "e.expense_type IN ('Personnel Services', 'Maintenance and Other Operating Expenses', 'Capital Outlay')";
        
        if ($expense_type_filter) {
            $where_conditions[] = "e.expense_type = ?";
            $params[] = $expense_type_filter;
        }
    } else if ($report_type === 'cash_advances') {
        $where_conditions[] = "e.expense_type = 'Cash Advance'";
    } else {
        throw new Exception('Invalid report type');
    }
    
    // Add admin filters if they exist
    if ($office_id_filter) {
        $where_conditions[] = "e.office_id = ?";
        $params[] = $office_id_filter;
    }
    
    if ($sub_office_id_filter) {
        $where_conditions[] = "e.sub_office_id = ?";
        $params[] = $sub_office_id_filter;
    }
    
    if ($created_by_filter) {
        $where_conditions[] = "e.created_by = ?";
        $params[] = $created_by_filter;
    }
    
    $where_clause = implode(' AND ', $where_conditions);
    
    // Include user information for admin exports
    $select_fields = "e.*";
    $join_clause = "";
    
    if ($is_admin) {
        $select_fields .= ", u.full_name as creator_name";
        $join_clause = "LEFT JOIN users u ON e.created_by = u.id";
    }
    
    $sql = "SELECT {$select_fields} FROM expenses e {$join_clause} WHERE {$where_clause} ORDER BY e.date ASC, e.created_at ASC";
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $expenses = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($expenses)) {
        $error_message = "No data found for the selected criteria";
        header('Location: export.php?error=' . urlencode($error_message));
        exit;
    }
    
    $filename = ($report_type === 'issued_checks' ? 'IssuedChecks' : 'CashAdvances') . 
                '_' . str_replace(' ', '_', $fund_type) . 
                '_' . date('Ymd_His') . '.xls';

    if ($report_type === 'issued_checks') {
        // Calculate grand total
        $grand_total = 0;
        foreach ($expenses as $expense) {
            $grand_total += $expense['total'];
        }
        
        $formatted_data = [];

        $formatted_data[] = [];
        $formatted_data[] = ['LIST OF ISSUED CHECKS', '', '', ''];
        $formatted_data[] = ['FOR THE MONTH OF ' . $period_description, '', '', ''];
        $formatted_data[] = []; 
        $formatted_data[] = [$bank, '', '', '']; 
        $formatted_data[] = []; 
        $formatted_data[] = [$fund_type]; 
        $formatted_data[] = []; 
        
        // Header row
        $headers = ['DATE', 'CHECK NUMBER', 'PAYEE', 'AMOUNT'];
        if ($is_admin) {
            $headers[] = 'OFFICE';
            $headers[] = 'SUB-OFFICE';
            $headers[] = 'ENCODED BY';
        }
        $formatted_data[] = $headers;
        
        // Data rows
        foreach ($expenses as $expense) {
            $row = [
                date('m/d/y', strtotime($expense['date'])),
                $expense['check_number'],
                $expense['payee'],
                number_format($expense['total'], 2)
            ];
            
            if ($is_admin) {
                $row[] = getOfficeName($conn, $expense['office_id']);
                $row[] = getOfficeName($conn, $expense['sub_office_id']);
                $row[] = $expense['creator_name'] ?? 'Unknown';
            }
            
            $formatted_data[] = $row;
        }
        
        // Grand total row
        $formatted_data[] = []; 
        $formatted_data[] = []; 
        $formatted_data[] = []; 
        
        $total_row = [
            '',
            '',
            'GRAND TOTAL',
            number_format($grand_total, 2)
        ];
        
        if ($is_admin) {
            $total_row = array_merge($total_row, ['', '', '']);
        }
        
        $formatted_data[] = $total_row;
        
        $excel = new SimpleExcelGenerator('Issued Checks Report');
        $excel->setHeaders($headers)
              ->setData($formatted_data)
              ->setFilename($filename)
              ->setColumnWidths($is_admin ? [80, 100, 250, 100, 150, 150, 150] : [80, 100, 250, 100])
              ->download();
              
    } else if ($report_type === 'cash_advances') {
        $formatted_data = [];
        $formatted_data[] = [];
        $formatted_data[] = ['LIST OF CASH ADVANCES', '', '', '', '', '', ''];
        $formatted_data[] = ['FOR THE MONTH OF ' . $period_description, '', '', '', '', '', ''];
        $formatted_data[] = [];
        $formatted_data[] = [$bank, '', '', '', '', '', ''];
        $formatted_data[] = [];
        $formatted_data[] = [$fund_type, '', '', '', '', '', ''];
        $formatted_data[] = [];
        
        // Header row
        $headers = ['DATE', 'CHECK NO.', 'NAME OF THE EMPLOYEE', 'AMOUNT', 'LIQUIDATED', 'RETURNED', 'REMARKS'];
        if ($is_admin) {
            $headers[] = 'OFFICE';
            $headers[] = 'SUB-OFFICE';
            $headers[] = 'ENCODED BY';
        }
        $formatted_data[] = $headers;
        
        foreach ($expenses as $expense) {
            $row = [
                date('m/d/y', strtotime($expense['date'])),
                $expense['check_number'],
                $expense['payee'],
                number_format($expense['total'], 2),
                '',
                '',
                ''
            ];
            
            if ($is_admin) {
                $row[] = getOfficeName($conn, $expense['office_id']);
                $row[] = getOfficeName($conn, $expense['sub_office_id']);
                $row[] = $expense['creator_name'] ?? 'Unknown';
            }
            
            $formatted_data[] = $row;
        }
        
        $excel = new SimpleExcelGenerator('Cash Advances Report');
        $excel->setHeaders($headers)
              ->setData($formatted_data)
              ->setFilename($filename)
              ->setColumnWidths($is_admin ? [80, 80, 200, 80, 80, 80, 150, 150, 150, 150] : [80, 80, 200, 80, 80, 80, 150])
              ->download();
    }
    
} catch (Exception $e) {
    header('Location: export.php?error=' . urlencode($e->getMessage()));
    exit;
}
