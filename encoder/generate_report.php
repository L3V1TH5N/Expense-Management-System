<?php
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

requireLogin();

require_once '../includes/SimpleExcelGenerator.php';

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['full_name'];

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
        'e.created_by = ?', 
        'e.date >= ?', 
        'e.date <= ?',
        'e.fund_type = ?',
        'e.bank = ?'
    ];
    $params = [$user_id, $start_date, $end_date, $fund_type, $bank];
    
    if ($report_type === 'issued_checks') {
        $where_conditions[] = "e.expense_type IN ('Personal Services', 'Maintenance and Other Operating Expenses', 'Capital Outlay')";
        
        if ($expense_type_filter) {
            $where_conditions[] = "e.expense_type = ?";
            $params[] = $expense_type_filter;
        }
    } else if ($report_type === 'cash_advances') {
        $where_conditions[] = "e.expense_type = 'Cash Advance'";
    } else {
        throw new Exception('Invalid report type');
    }
    
    $where_clause = implode(' AND ', $where_conditions);
    
    $sql = "SELECT e.* FROM expenses e WHERE {$where_clause} ORDER BY e.date ASC, e.created_at ASC";
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
        $formatted_data[] = ['DATE', 'CHECK NUMBER', 'PAYEE', 'AMOUNT'];
        
        // Data rows
        foreach ($expenses as $expense) {
            $formatted_data[] = [
                date('m/d/y', strtotime($expense['date'])),
                $expense['check_number'],
                $expense['payee'],
                number_format($expense['amount'], 2)
            ];
        }
        
        // Grand total row
        $formatted_data[] = []; 
        $formatted_data[] = []; 
        $formatted_data[] = []; 
        $formatted_data[] = [
            '',
            '',
            'GRAND TOTAL',
            number_format($grand_total, 2)
        ];
        
        $excel = new SimpleExcelGenerator('Issued Checks Report');
        $excel->setHeaders(['DATE', 'CHECK NUMBER', 'PAYEE', 'AMOUNT'])
              ->setData($formatted_data)
              ->setFilename($filename)
              ->setColumnWidths([80, 100, 250, 100])
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
        $formatted_data[] = ['DATE', 'CHECK NO.', 'NAME OF THE EMPLOYEE', 'AMOUNT', 'LIQUIDATED', 'RETURNED', 'REMARKS'];
        
        foreach ($expenses as $expense) {
            $formatted_data[] = [
                date('m/d/y', strtotime($expense['date'])),
                $expense['check_number'],
                $expense['payee'],
                number_format($expense['total'], 2),
                '',
                '',
                ''
            ];
        }
        
        $excel = new SimpleExcelGenerator('Cash Advances Report');
        $excel->setHeaders(['DATE', 'CHECK NO.', 'NAME OF THE EMPLOYEE', 'AMOUNT', 'LIQUIDATED', 'RETURNED', 'REMARKS'])
              ->setData($formatted_data)
              ->setFilename($filename)
              ->setColumnWidths([80, 80, 200, 80, 80, 80, 150])
              ->download();
    }
    
} catch (Exception $e) {
    header('Location: export.php?error=' . urlencode($e->getMessage()));
    exit;
}