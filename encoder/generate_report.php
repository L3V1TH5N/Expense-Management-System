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
    
    // Statement of Expenses has different requirements
    if ($report_type === 'statement_of_expenses') {
        // Fund type is optional for Statement of Expenses
        $fund_type = isset($_POST['fund_type']) && !empty($_POST['fund_type']) 
                    ? sanitizeInput($_POST['fund_type']) 
                    : null;
    } else {
        // Other reports require fund type and bank
        if (!isset($_POST['fund_type']) || empty($_POST['fund_type'])) {
            throw new Exception('Fund type is required');
        }
        
        if (!isset($_POST['bank']) || empty($_POST['bank'])) {
            throw new Exception('Bank is required');
        }
        
        $fund_type = sanitizeInput($_POST['fund_type']);
        $bank = sanitizeInput($_POST['bank']);
    }
    
    $expense_type_filter = isset($_POST['expense_type']) && !empty($_POST['expense_type']) 
                          ? sanitizeInput($_POST['expense_type']) 
                          : null;
    
    $period_description = '';
    
    // Handle period calculations
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

    // Handle different report types
    if ($report_type === 'statement_of_expenses') {
        generateStatementOfExpenses($conn, $start_date, $end_date, $fund_type, $period_description, $user_id);
    } elseif ($report_type === 'issued_checks') {
        generateIssuedChecks($conn, $start_date, $end_date, $fund_type, $bank, $expense_type_filter, $period_description, $user_id);
    } elseif ($report_type === 'cash_advances') {
        generateCashAdvances($conn, $start_date, $end_date, $fund_type, $bank, $period_description, $user_id);
    } else {
        throw new Exception('Invalid report type');
    }
    
} catch (Exception $e) {
    header('Location: export.php?error=' . urlencode($e->getMessage()));
    exit;
}


function generateStatementOfExpenses($conn, $start_date, $end_date, $fund_type, $period_description, $user_id) {
    // Build the query for Statement of Expenses
    $where_conditions = [
        'e.created_by = ?', 
        'e.date >= ?', 
        'e.date <= ?',
        "e.expense_type IN ('Personnel Services', 'Maintenance and Other Operating Expenses', 'Capital Outlay', 'Others')"
    ];
    $params = [$user_id, $start_date, $end_date];
    
    // Add fund type filter if specified
    if ($fund_type) {
        $where_conditions[] = 'e.fund_type = ?';
        $params[] = $fund_type;
    }
    
    $where_clause = implode(' AND ', $where_conditions);
    
    // Get all offices in hierarchical order
    $sql = "SELECT 
                o.id, 
                o.name, 
                o.parent_id,
                p.name as parent_name
            FROM offices o
            LEFT JOIN offices p ON o.parent_id = p.id
            ORDER BY COALESCE(p.name, ''), o.name";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $all_offices = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Prepare Excel data
    $formatted_data = [];
    
    // Header
    $formatted_data[] = []; // Empty row
    $formatted_data[] = ['STATEMENT OF EXPENSES', '', '', '', ''];
    $formatted_data[] = ['FOR THE MONTH OF ' . $period_description, '', '', '', ''];
    $formatted_data[] = []; // Empty row
    $formatted_data[] = []; // Empty row
    
    // Column headers
    $formatted_data[] = [
        'OFFICE',
        'PS',
        'MOOE',
        'CO',
        'Total'
    ];
    
    $grand_total_ps = 0;
    $grand_total_mooe = 0;
    $grand_total_co = 0;
    $grand_total_all = 0;
    
    // Store totals by fund type if we're showing all funds
    $fund_type_totals = [];
    if (!$fund_type) {
        $fund_types = ['Special Education Fund', 'Trust Fund'];
        foreach ($fund_types as $ft) {
            $fund_type_totals[$ft] = [
                'personnel_services' => 0,
                'mooe' => 0,
                'capital_outlay' => 0,
                'total_amount' => 0
            ];
        }
    }
    
    $office_structure = [
        'Office of the Mayor' => [
            'Mayor',
            'GSO',
            'BPLO',
            'MENRO',
            'Tourism',
            'HR',
            'Public Market',
            'PESO',
            'MSWD'
        ],
        'SPA' => [
            'Municipal Development Fund',
            'GAD',
            'LCPC',
            'PWD',
            'SC',
            'DRRMF 70%',
            'DRRMF 30%',
            'Peace and Order'
        ],
        'Other Offices' => [
            'Municipal Civil Registrar',
            'MPDC',
            'Municipal Accounting Office',
            'Municipal Budget Office',
            'Municipal Treasurer\'s Office',
            'Municipal Assessor',
            'Sangguniang Bayan',
            'Municipal Health Office',
            'Municipal Agriculture Office',
            'Municipal Engineering Office'
        ]
    ];
    
    // Process each office group
    foreach ($office_structure as $parent_name => $offices) {
        $group_total_ps = 0;
        $group_total_mooe = 0;
        $group_total_co = 0;
        $group_total_all = 0;
        
        // Add parent office as merged row
        $formatted_data[] = [$parent_name];
        
        foreach ($offices as $office_name) {
            // Initialize with zero values
            $office_data = [
                'personnel_services' => 0,
                'mooe' => 0,
                'capital_outlay' => 0,
                'total_amount' => 0
            ];
            
            // Check if this office exists in our database
            foreach ($all_offices as $office) {
                if ($office['name'] === $office_name) {
                    // Get expense data for this office
                    $sql = "SELECT 
                                SUM(CASE WHEN e.expense_type = 'Personnel Services' THEN e.total ELSE 0 END) as personnel_services,
                                SUM(CASE WHEN e.expense_type = 'Maintenance and Other Operating Expenses' THEN e.total ELSE 0 END) as mooe,
                                SUM(CASE WHEN e.expense_type = 'Capital Outlay' THEN e.total ELSE 0 END) as capital_outlay,
                                SUM(e.total) as total_amount
                            FROM expenses e
                            WHERE {$where_clause} AND (e.office_id = ? OR e.sub_office_id = ?)";
                    
                    $stmt = $conn->prepare($sql);
                    $stmt->execute(array_merge($params, [$office['id'], $office['id']]));
                    $result = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if ($result) {
                        $office_data = $result;
                    }
                    
                    // If showing all funds, get breakdown by fund type
                    if (!$fund_type) {
                        foreach ($fund_types as $ft) {
                            $ft_where = $where_conditions;
                            $ft_where[] = 'e.fund_type = ?';
                            $ft_where_clause = implode(' AND ', $ft_where);
                            
                            $ft_params = array_merge($params, [$ft, $office['id'], $office['id']]);
                            
                            $sql = "SELECT 
                                        SUM(CASE WHEN e.expense_type = 'Personnel Services' THEN e.total ELSE 0 END) as personnel_services,
                                        SUM(CASE WHEN e.expense_type = 'Maintenance and Other Operating Expenses' THEN e.total ELSE 0 END) as mooe,
                                        SUM(CASE WHEN e.expense_type = 'Capital Outlay' THEN e.total ELSE 0 END) as capital_outlay,
                                        SUM(e.total) as total_amount
                                    FROM expenses e
                                    WHERE {$ft_where_clause} AND (e.office_id = ? OR e.sub_office_id = ?)";
                            
                            $stmt = $conn->prepare($sql);
                            $stmt->execute($ft_params);
                            $ft_result = $stmt->fetch(PDO::FETCH_ASSOC);
                            
                            if ($ft_result) {
                                $fund_type_totals[$ft]['personnel_services'] += $ft_result['personnel_services'];
                                $fund_type_totals[$ft]['mooe'] += $ft_result['mooe'];
                                $fund_type_totals[$ft]['capital_outlay'] += $ft_result['capital_outlay'];
                                $fund_type_totals[$ft]['total_amount'] += $ft_result['total_amount'];
                            }
                        }
                    }
                    break;
                }
            }
            
            $prefix = ($parent_name !== 'Other Offices') ? '* ' : '';
            $formatted_data[] = [
                $prefix . $office_name,
                $office_data['personnel_services'] > 0 ? '₱' . number_format($office_data['personnel_services'], 2) : '-',
                $office_data['mooe'] > 0 ? '₱' . number_format($office_data['mooe'], 2) : '-',
                $office_data['capital_outlay'] > 0 ? '₱' . number_format($office_data['capital_outlay'], 2) : '-',
                $office_data['total_amount'] > 0 ? '₱' . number_format($office_data['total_amount'], 2) : '-'
            ];
            
            $group_total_ps += $office_data['personnel_services'];
            $group_total_mooe += $office_data['mooe'];
            $group_total_co += $office_data['capital_outlay'];
            $group_total_all += $office_data['total_amount'];
        }
        
        // Add group subtotal for all groups
        $formatted_data[] = [
            'Sub-Total',
            $group_total_ps > 0 ? '₱' . number_format($group_total_ps, 2) : '-',
            $group_total_mooe > 0 ? '₱' . number_format($group_total_mooe, 2) : '-',
            $group_total_co > 0 ? '₱' . number_format($group_total_co, 2) : '-',
            $group_total_all > 0 ? '₱' . number_format($group_total_all, 2) : '-'
        ];
        $formatted_data[] = []; // Empty row after subtotal
        
        $grand_total_ps += $group_total_ps;
        $grand_total_mooe += $group_total_mooe;
        $grand_total_co += $group_total_co;
        $grand_total_all += $group_total_all;
    }
    
    // Add grand totals
    $formatted_data[] = []; 
    $formatted_data[] = [
        'GRAND TOTAL',
        $grand_total_ps > 0 ? '₱' . number_format($grand_total_ps, 2) : '-',
        $grand_total_mooe > 0 ? '₱' . number_format($grand_total_mooe, 2) : '-',
        $grand_total_co > 0 ? '₱' . number_format($grand_total_co, 2) : '-',
        $grand_total_all > 0 ? '₱' . number_format($grand_total_all, 2) : '-'
    ];
    
    // Add additional offices after grand total
    $additional_offices = ['SEF', 'TRUST', 'Continuing App GEN', 'Continuing App SEF', 'Others'];
    
    $formatted_data[] = []; // Empty row
    $formatted_data[] = ['Additional Offices'];
    
    $additional_total_ps = 0;
    $additional_total_mooe = 0;
    $additional_total_co = 0;
    $additional_total_all = 0;
    
    foreach ($additional_offices as $office_name) {
        // Initialize with zero values
        $office_data = [
            'personnel_services' => 0,
            'mooe' => 0,
            'capital_outlay' => 0,
            'total_amount' => 0
        ];
        
        // Check if this office exists in our database
        foreach ($all_offices as $office) {
            if ($office['name'] === $office_name) {
                // Get expense data for this office
                $sql = "SELECT 
                            SUM(CASE WHEN e.expense_type = 'Personnel Services' THEN e.total ELSE 0 END) as personnel_services,
                            SUM(CASE WHEN e.expense_type = 'Maintenance and Other Operating Expenses' THEN e.total ELSE 0 END) as mooe,
                            SUM(CASE WHEN e.expense_type = 'Capital Outlay' THEN e.total ELSE 0 END) as capital_outlay,
                            SUM(e.total) as total_amount
                        FROM expenses e
                        WHERE {$where_clause} AND (e.office_id = ? OR e.sub_office_id = ?)";
                
                $stmt = $conn->prepare($sql);
                $stmt->execute(array_merge($params, [$office['id'], $office['id']]));
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($result) {
                    $office_data = $result;
                }
                
                // If showing all funds, add to fund type breakdown
                if (!$fund_type) {
                    foreach ($fund_types as $ft) {
                        $ft_where = $where_conditions;
                        $ft_where[] = 'e.fund_type = ?';
                        $ft_where_clause = implode(' AND ', $ft_where);
                        
                        $ft_params = array_merge($params, [$ft, $office['id'], $office['id']]);
                        
                        $sql = "SELECT 
                                    SUM(CASE WHEN e.expense_type = 'Personnel Services' THEN e.total ELSE 0 END) as personnel_services,
                                    SUM(CASE WHEN e.expense_type = 'Maintenance and Other Operating Expenses' THEN e.total ELSE 0 END) as mooe,
                                    SUM(CASE WHEN e.expense_type = 'Capital Outlay' THEN e.total ELSE 0 END) as capital_outlay,
                                    SUM(e.total) as total_amount
                                FROM expenses e
                                WHERE {$ft_where_clause} AND (e.office_id = ? OR e.sub_office_id = ?)";
                        
                        $stmt = $conn->prepare($sql);
                        $stmt->execute($ft_params);
                        $ft_result = $stmt->fetch(PDO::FETCH_ASSOC);
                        
                        if ($ft_result) {
                            $fund_type_totals[$ft]['personnel_services'] += $ft_result['personnel_services'];
                            $fund_type_totals[$ft]['mooe'] += $ft_result['mooe'];
                            $fund_type_totals[$ft]['capital_outlay'] += $ft_result['capital_outlay'];
                            $fund_type_totals[$ft]['total_amount'] += $ft_result['total_amount'];
                        }
                    }
                }
                break;
            }
        }
        
        $formatted_data[] = [
            $office_name,
            $office_data['personnel_services'] > 0 ? '₱' . number_format($office_data['personnel_services'], 2) : '-',
            $office_data['mooe'] > 0 ? '₱' . number_format($office_data['mooe'], 2) : '-',
            $office_data['capital_outlay'] > 0 ? '₱' . number_format($office_data['capital_outlay'], 2) : '-',
            $office_data['total_amount'] > 0 ? '₱' . number_format($office_data['total_amount'], 2) : '-'
        ];
        
        $additional_total_ps += $office_data['personnel_services'];
        $additional_total_mooe += $office_data['mooe'];
        $additional_total_co += $office_data['capital_outlay'];
        $additional_total_all += $office_data['total_amount'];
    }
    
    // Update grand totals to include additional offices
    $grand_total_ps += $additional_total_ps;
    $grand_total_mooe += $additional_total_mooe;
    $grand_total_co += $additional_total_co;
    $grand_total_all += $additional_total_all;
    
    // Generate filename
    $fund_suffix = $fund_type ? '_' . str_replace(' ', '_', $fund_type) : '_AllFunds';
    $filename = 'StatementOfExpenses_' . date('F_Y', strtotime($start_date)) . $fund_suffix . '.xls';
    
    // Create and download Excel
    $excel = new SimpleExcelGenerator('Statement of Expenses');
    $excel->setHeaders([
            'OFFICE/DEPARTMENT',
            'PERSONNEL SERVICES',
            'MAINTENANCE & OTHER OPERATING EXPENSES',
            'CAPITAL OUTLAY',
            'TOTAL'
        ])
        ->setData($formatted_data)
        ->setFilename($filename)
        ->setColumnWidths([200, 120, 120, 120, 120])
        ->download();
}

function generateIssuedChecks($conn, $start_date, $end_date, $fund_type, $bank, $expense_type_filter, $period_description, $user_id) {
    $where_conditions = [
        'e.created_by = ?', 
        'e.date >= ?', 
        'e.date <= ?',
        'e.fund_type = ?',
        'e.bank = ?'
    ];
    $params = [$user_id, $start_date, $end_date, $fund_type, $bank];
    
    if ($expense_type_filter) {
        $where_conditions[] = "e.expense_type = ?";
        $params[] = $expense_type_filter;
    }
    
    $where_clause = implode(' AND ', $where_conditions);
    
    $sql = "SELECT e.* FROM expenses e WHERE {$where_clause} ORDER BY e.date ASC, e.check_number ASC";
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $expenses = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($expenses)) {
        throw new Exception("No data found for the selected criteria");
    }
    
    $filename = 'IssuedChecks_' . str_replace(' ', '_', $fund_type) . '_' . date('Ymd_His') . '.xls';

    // Calculate grand total
    $grand_total = 0;
    foreach ($expenses as $expense) {
        $grand_total += $expense['amount'];
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
    
    // Enhanced data rows with visual grouping by date
    $previous_date = '';
    foreach ($expenses as $expense) {
        $current_date = date('m/d/y', strtotime($expense['date']));
        
        // If date is the same as previous, show empty cell for better visual grouping
        $display_date = ($current_date === $previous_date) ? '' : $current_date;
        
        $formatted_data[] = [
            $display_date,
            $expense['check_number'],
            $expense['payee'],
            number_format($expense['amount'], 2)
        ];
        
        $previous_date = $current_date;
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
}

function generateCashAdvances($conn, $start_date, $end_date, $fund_type, $bank, $period_description, $user_id) {
    $where_conditions = [
        'e.created_by = ?', 
        'e.date >= ?', 
        'e.date <= ?',
        'e.fund_type = ?',
        'e.bank = ?',
        "e.expense_type = 'Cash Advance'"
    ];
    $params = [$user_id, $start_date, $end_date, $fund_type, $bank];
    
    $where_clause = implode(' AND ', $where_conditions);
    
    $sql = "SELECT e.* FROM expenses e WHERE {$where_clause} ORDER BY e.date ASC, e.check_number ASC";
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $expenses = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($expenses)) {
        throw new Exception("No data found for the selected criteria");
    }
    
    $filename = 'CashAdvances_' . str_replace(' ', '_', $fund_type) . '_' . date('Ymd_His') . '.xls';
    
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
    
    // Enhanced data rows with visual grouping by date for cash advances too
    $previous_date = '';
    foreach ($expenses as $expense) {
        $current_date = date('m/d/y', strtotime($expense['date']));
        
        // If date is the same as previous, show empty cell for better visual grouping
        $display_date = ($current_date === $previous_date) ? '' : $current_date;
        
        $formatted_data[] = [
            $display_date,
            $expense['check_number'],
            $expense['payee'],
            number_format($expense['amount'], 2),
            '',
            '',
            ''
        ];
        
        $previous_date = $current_date;
    }
    
    $excel = new SimpleExcelGenerator('Cash Advances Report');
    $excel->setHeaders(['DATE', 'CHECK NO.', 'NAME OF THE EMPLOYEE', 'AMOUNT', 'LIQUIDATED', 'RETURNED', 'REMARKS'])
          ->setData($formatted_data)
          ->setFilename($filename)
          ->setColumnWidths([80, 80, 200, 80, 80, 80, 150])
          ->download();
}
?>
