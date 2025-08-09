<?php include 'functions/report.php'; ?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href='https://unpkg.com/boxicons@2.0.9/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="../assets/css/style.css">
    <title>Reports - Expense Management System</title>
    <link rel="stylesheet" href="functions/css/report.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <!-- SIDEBAR -->
    <section id="sidebar">
        <a href="#" class="brand">
            <i class='bx bxs-wallet'></i>
            <span class="text"><?php echo htmlspecialchars($_SESSION['full_name']); ?></span>
        </a>
        <ul class="side-menu top">
            <li>
                <a href="dashboard.php">
                    <i class='bx bxs-dashboard'></i>
                    <span class="text">Dashboard</span>
                </a>
            </li>
            <li>
                <a href="encode.php">
                    <i class='bx bxs-plus-circle'></i>
                    <span class="text">Encode Expense</span>
                </a>
            </li>
            <li>
                <a href="my_expenses.php">
                    <i class='bx bxs-receipt'></i>
                    <span class="text">Expenses Records</span>
                </a>
            </li>
			<li class="active">
                <a href="reports.php">
                    <i class='bx bxs-report'></i>
                    <span class="text">Reports</span>
                </a>
            </li>
            <li>
                <a href="export.php">
                    <i class='bx bxs-download'></i>
                    <span class="text">Export Reports</span>
                </a>
            </li>
            <br><br>
            <li>
                <a href="profile.php">
                    <i class='bx bxs-user'></i>
                    <span class="text">Profile</span>
                </a>
            </li>
            <li>
                <a href="../logout.php" class="logout">
                    <i class='bx bxs-log-out-circle'></i>
                    <span class="text">Logout</span>
                </a>
            </li>
        </ul>
    </section>
    <!-- SIDEBAR -->

    <!-- CONTENT -->
    <section id="content">
        <!-- NAVBAR -->
        <nav>
            <i class='bx bx-menu'></i>
            <span class="nav-text">Expense Management System</span>
        </nav>
        <!-- NAVBAR -->

        <!-- MAIN -->
        <main>
            <div class="head-title">
                <div class="left">
                    <ul class="breadcrumb">
                        <li>
                            <a href="dashboard.php">Home</a>
                        </li>
                        <li><i class='bx bx-chevron-right'></i></li>
                        <li>
                            <a class="active" href="#">Reports</a>
                        </li>
                    </ul>
                </div>
            </div>

            <br>
            
            <!-- Report Generator Form -->
            <div class="report-form">
                <form method="GET" action="reports.php" id="reportForm">
                    <div class="report-tabs">
                        <div class="report-tab <?php echo $report_type === 'monthly' ? 'active' : ''; ?>" 
                             onclick="switchReportType('monthly')">
                            <i class='bx bx-calendar'></i> Monthly Report
                        </div>
                        <div class="report-tab <?php echo $report_type === 'annual' ? 'active' : ''; ?>" 
                             onclick="switchReportType('annual')">
                            <i class='bx bx-calendar-alt'></i> Annual Report
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <?php if ($report_type === 'monthly'): ?>
                        <div class="form-group">
                            <label for="month"><i class='bx bx-calendar-week'></i> Select Month</label>
                            <select id="month" name="month">
                                <?php for ($m = 1; $m <= 12; $m++): ?>
                                    <option value="<?php echo str_pad($m, 2, '0', STR_PAD_LEFT); ?>" 
                                        <?php echo $month === str_pad($m, 2, '0', STR_PAD_LEFT) ? 'selected' : ''; ?>>
                                        <?php echo date('F', mktime(0, 0, 0, $m, 1)); ?>
                                    </option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <?php endif; ?>
                        
                        <div class="form-group">
                            <label for="year"><i class='bx bx-calendar-event'></i> Select Year</label>
                            <select id="year" name="year">
                                <?php foreach ($years as $y): ?>
                                    <option value="<?php echo $y; ?>" <?php echo $year == $y ? 'selected' : ''; ?>>
                                        <?php echo $y; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <input type="hidden" name="report_type" value="<?php echo $report_type; ?>">
                    <button type="submit" class="btn btn-primary">
                        <i class='bx bx-refresh'></i> Generate Report
                    </button>
                </form>
            </div>

            <!-- Report Header -->
            <div class="report-header">
                <h2><i class='bxs-report'></i> <?php echo $report_title; ?></h2>
                <?php if (!$is_admin): ?>
                    <div class="user-info">
                        <i class='bx bx-user'></i> Showing your expenses only
                    </div>
                <?php endif; ?>
                <div class="report-meta">
                    <i class='bx bx-calendar'></i> Report period: <?php echo date('M j, Y', strtotime($report_data['start_date'])); ?> to <?php echo date('M j, Y', strtotime($report_data['end_date'])); ?>
                </div>
                
                <?php if ($report_type === 'monthly'): ?>
                    <div class="report-summary">
                        <i class='bx bx-money'></i> Total: <?php echo number_format($report_data['monthly_totals']['count']); ?> expenses • ₱<?php echo number_format($report_data['monthly_totals']['total_amount'], 2); ?>
                    </div>
                <?php endif; ?>
            </div>

            <?php if ($report_type === 'monthly'): ?>
                <!-- Monthly Report Content -->
                
                <!-- Fund Type Analysis -->
                <div class="report-section">
                    <h3><i class='bx bxs-wallet'></i> Expenses by Fund Type</h3>
                    <?php if (!empty($report_data['by_fund_type'])): ?>
                        <div class="chart-container">
                            <div class="chart-wrapper">
                                <canvas id="fundTypeChart"></canvas>
                            </div>
                        </div>
                        <table class="report-table">
                            <thead>
                                <tr>
                                    <th><i class='bx bx-category'></i> Fund Type</th>
                                    <th><i class='bx bx-hash'></i> Count</th>
                                    <th><i class='bx bx-money'></i> Total Amount</th>
                                    <th><i class='bx bx-pie-chart-alt'></i> Percentage</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($report_data['by_fund_type'] as $fund): ?>
                                <tr>
                                    <td>
                                        <span class="badge badge-<?php echo strtolower(str_replace(' ', '-', $fund['fund_type'])); ?>">
                                            <?php echo $fund['fund_type']; ?>
                                        </span>
                                    </td>
                                    <td><?php echo number_format($fund['count']); ?></td>
                                    <td><strong>₱<?php echo number_format($fund['total_amount'], 2); ?></strong></td>
                                    <td>
                                        <?php 
                                        $percentage = $report_data['monthly_totals']['total_amount'] > 0 ? 
                                            ($fund['total_amount'] / $report_data['monthly_totals']['total_amount']) * 100 : 0;
                                        echo number_format($percentage, 1); ?>%
                                        <div class="progress-bar">
                                            <div class="progress-fill" style="width: <?php echo $percentage; ?>%"></div>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class='bx bx-wallet'></i>
                            <p>No fund type data available for this period</p>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Expense Type Analysis -->
                <div class="report-section">
                    <h3><i class='bx bxs-category'></i> Expenses by Type</h3>
                    <?php if (!empty($report_data['by_expense_type'])): ?>
                        <div class="chart-container">
                            <div class="chart-wrapper">
                                <canvas id="expenseTypeChart"></canvas>
                            </div>
                        </div>
                        <table class="report-table">
                            <thead>
                                <tr>
                                    <th><i class='bx bx-list-ul'></i> Expense Type</th>
                                    <th><i class='bx bx-hash'></i> Count</th>
                                    <th><i class='bx bx-money'></i> Total Amount</th>
                                    <th><i class='bx bx-pie-chart-alt'></i> Percentage</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($report_data['by_expense_type'] as $type): ?>
                                <tr>
                                    <td><?php echo $type['expense_type']; ?></td>
                                    <td><?php echo number_format($type['count']); ?></td>
                                    <td><strong>₱<?php echo number_format($type['total_amount'], 2); ?></strong></td>
                                    <td>
                                        <?php 
                                        $percentage = $report_data['monthly_totals']['total_amount'] > 0 ? 
                                            ($type['total_amount'] / $report_data['monthly_totals']['total_amount']) * 100 : 0;
                                        echo number_format($percentage, 1); ?>%
                                        <div class="progress-bar">
                                            <div class="progress-fill" style="width: <?php echo $percentage; ?>%"></div>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class='bx bx-category'></i>
                            <p>No expense type data available for this period</p>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Office Analysis -->
                <div class="report-section">
                    <h3><i class='bx bxs-buildings'></i> Expenses by Office</h3>
                    <?php if (!empty($report_data['by_office'])): ?>
                        <table class="report-table">
                            <thead>
                                <tr>
                                    <th><i class='bx bx-buildings'></i> Office</th>
                                    <th><i class='bx bx-hash'></i> Count</th>
                                    <th><i class='bx bx-money'></i> Total Amount</th>
                                    <th><i class='bx bx-pie-chart-alt'></i> Percentage</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($report_data['by_office'] as $office): ?>
                                <tr>
                                    <td><strong><?php echo $office['office_name'] ?: 'No Office'; ?></strong></td>
                                    <td><?php echo number_format($office['count']); ?></td>
                                    <td><strong>₱<?php echo number_format($office['total_amount'], 2); ?></strong></td>
                                    <td>
                                        <?php 
                                        $percentage = $report_data['monthly_totals']['total_amount'] > 0 ? 
                                            ($office['total_amount'] / $report_data['monthly_totals']['total_amount']) * 100 : 0;
                                        echo number_format($percentage, 1); ?>%
                                        <div class="progress-bar">
                                            <div class="progress-fill" style="width: <?php echo $percentage; ?>%"></div>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class='bx bx-buildings'></i>
                            <p>No office data available for this period</p>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Top Encoders (Admin Only) -->
                <?php if ($is_admin && !empty($report_data['by_encoder'])): ?>
                <div class="report-section">
                    <h3><i class='bx bxs-user-badge'></i> Top Encoders</h3>
                    <table class="report-table">
                        <thead>
                            <tr>
                                <th><i class='bx bx-user'></i> Encoder</th>
                                <th><i class='bx bx-hash'></i> Count</th>
                                <th><i class='bx bx-money'></i> Total Amount</th>
                                <th><i class='bx bx-pie-chart-alt'></i> Percentage</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($report_data['by_encoder'] as $encoder): ?>
                            <tr>
                                <td><strong><?php echo $encoder['encoder_name']; ?></strong></td>
                                <td><?php echo number_format($encoder['count']); ?></td>
                                <td><strong>₱<?php echo number_format($encoder['total_amount'], 2); ?></strong></td>
                                <td>
                                    <?php 
                                    $percentage = $report_data['monthly_totals']['total_amount'] > 0 ? 
                                        ($encoder['total_amount'] / $report_data['monthly_totals']['total_amount']) * 100 : 0;
                                    echo number_format($percentage, 1); ?>%
                                    <div class="progress-bar">
                                        <div class="progress-fill" style="width: <?php echo $percentage; ?>%"></div>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>

            <?php else: ?>
                <!-- Annual Report Content -->
                
                <!-- Monthly Breakdown -->
                <div class="report-section">
                    <h3><i class='bx bxs-calendar'></i> Monthly Breakdown</h3>
                    <?php if (!empty($report_data['monthly_breakdown'])): ?>
                        <div class="chart-container">
                            <div class="chart-wrapper">
                                <canvas id="monthlyBreakdownChart"></canvas>
                            </div>
                        </div>
                        <table class="report-table">
                            <thead>
                                <tr>
                                    <th><i class='bx bx-calendar'></i> Month</th>
                                    <th><i class='bx bx-hash'></i> Count</th>
                                    <th><i class='bx bx-money'></i> Total Amount</th>
                                    <th><i class='bx bx-pie-chart-alt'></i> Percentage</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $annual_total = array_reduce($report_data['monthly_breakdown'], function($carry, $item) {
                                    return $carry + $item['total_amount'];
                                }, 0);
                                
                                foreach ($report_data['monthly_breakdown'] as $month_data): ?>
                                <tr>
                                    <td><strong><?php echo date('F Y', strtotime($month_data['month'] . '-01')); ?></strong></td>
                                    <td><?php echo number_format($month_data['count']); ?></td>
                                    <td><strong>₱<?php echo number_format($month_data['total_amount'], 2); ?></strong></td>
                                    <td>
                                        <?php 
                                        $percentage = $annual_total > 0 ? 
                                            ($month_data['total_amount'] / $annual_total) * 100 : 0;
                                        echo number_format($percentage, 1); ?>%
                                        <div class="progress-bar">
                                            <div class="progress-fill" style="width: <?php echo $percentage; ?>%"></div>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class='bx bx-calendar'></i>
                            <p>No monthly data available for this year</p>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Annual Fund Type Analysis -->
                <div class="report-section">
                    <h3><i class='bx bxs-wallet'></i> Expenses by Fund Type</h3>
                    <?php if (!empty($report_data['by_fund_type'])): ?>
                        <div class="chart-container">
                            <div class="chart-wrapper">
                                <canvas id="annualFundTypeChart"></canvas>
                            </div>
                        </div>
                        <table class="report-table">
                            <thead>
                                <tr>
                                    <th><i class='bx bx-category'></i> Fund Type</th>
                                    <th><i class='bx bx-hash'></i> Count</th>
                                    <th><i class='bx bx-money'></i> Total Amount</th>
                                    <th><i class='bx bx-pie-chart-alt'></i> Percentage</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($report_data['by_fund_type'] as $fund): ?>
                                <tr>
                                    <td>
                                        <span class="badge badge-<?php echo strtolower(str_replace(' ', '-', $fund['fund_type'])); ?>">
                                            <?php echo $fund['fund_type']; ?>
                                        </span>
                                    </td>
                                    <td><?php echo number_format($fund['count']); ?></td>
                                    <td><strong>₱<?php echo number_format($fund['total_amount'], 2); ?></strong></td>
                                    <td>
                                        <?php 
                                        $percentage = $annual_total > 0 ? 
                                            ($fund['total_amount'] / $annual_total) * 100 : 0;
                                        echo number_format($percentage, 1); ?>%
                                        <div class="progress-bar">
                                            <div class="progress-fill" style="width: <?php echo $percentage; ?>%"></div>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class='bx bx-wallet'></i>
                            <p>No fund type data available for this year</p>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Annual Expense Type Analysis -->
                <div class="report-section">
                    <h3><i class='bx bxs-category'></i> Expenses by Type</h3>
                    <?php if (!empty($report_data['by_expense_type'])): ?>
                        <div class="chart-container">
                            <div class="chart-wrapper">
                                <canvas id="annualExpenseTypeChart"></canvas>
                            </div>
                        </div>
                        <table class="report-table">
                            <thead>
                                <tr>
                                    <th><i class='bx bx-list-ul'></i> Expense Type</th>
                                    <th><i class='bx bx-hash'></i> Count</th>
                                    <th><i class='bx bx-money'></i> Total Amount</th>
                                    <th><i class='bx bx-pie-chart-alt'></i> Percentage</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($report_data['by_expense_type'] as $type): ?>
                                <tr>
                                    <td><?php echo $type['expense_type']; ?></td>
                                    <td><?php echo number_format($type['count']); ?></td>
                                    <td><strong>₱<?php echo number_format($type['total_amount'], 2); ?></strong></td>
                                    <td>
                                        <?php 
                                        $percentage = $annual_total > 0 ? 
                                            ($type['total_amount'] / $annual_total) * 100 : 0;
                                        echo number_format($percentage, 1); ?>%
                                        <div class="progress-bar">
                                            <div class="progress-fill" style="width: <?php echo $percentage; ?>%"></div>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class='bx bx-category'></i>
                            <p>No expense type data available for this year</p>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

        </main>
        <!-- MAIN -->
    </section>
    <!-- CONTENT -->

    <script src="../assets/js/script.js"></script>
    <script>
        // Report Type Switching Function
        function switchReportType(type) {
            const currentUrl = new URL(window.location.href);
            currentUrl.searchParams.set('report_type', type);
            if (type === 'annual') {
                currentUrl.searchParams.delete('month');
            }
            window.location.href = currentUrl.toString();
        }

        // Chart.js Configuration
        Chart.defaults.font.family = 'Poppins, sans-serif';
        Chart.defaults.color = '#342E37';

        // Monthly Report Charts
        <?php if ($report_type === 'monthly'): ?>
            <?php if (!empty($report_data['by_fund_type'])): ?>
            // Fund Type Chart
            const fundTypeCtx = document.getElementById('fundTypeChart').getContext('2d');
            const fundTypeChart = new Chart(fundTypeCtx, {
                type: 'doughnut',
                data: {
                    labels: [<?php echo implode(',', array_map(function($item) { return "'" . $item['fund_type'] . "'"; }, $report_data['by_fund_type'])); ?>],
                    datasets: [{
                        data: [<?php echo implode(',', array_map(function($item) { return $item['total_amount']; }, $report_data['by_fund_type'])); ?>],
                        backgroundColor: [
                            '#3C91E6', // General Fund
                            '#FD7238', // SEF  
                            '#FFCE26'  // Trust Fund
                        ],
                        borderWidth: 3,
                        borderColor: '#FFFFFF',
                        hoverBorderWidth: 5
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'right',
                            labels: {
                                padding: 20,
                                usePointStyle: true,
                                font: {
                                    size: 14,
                                    weight: '500'
                                }
                            }
                        },
                        tooltip: {
                            backgroundColor: 'rgba(0, 0, 0, 0.8)',
                            titleColor: '#ffffff',
                            bodyColor: '#ffffff',
                            borderColor: '#3C91E6',
                            borderWidth: 1,
                            callbacks: {
                                label: function(context) {
                                    const label = context.label || '';
                                    const value = context.raw || 0;
                                    const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                    const percentage = Math.round((value / total) * 100);
                                    return `${label}: ₱${value.toLocaleString()} (${percentage}%)`;
                                }
                            }
                        }
                    }
                }
            });
            <?php endif; ?>

            <?php if (!empty($report_data['by_expense_type'])): ?>
            // Expense Type Chart
            const expenseTypeCtx = document.getElementById('expenseTypeChart').getContext('2d');
            const expenseTypeChart = new Chart(expenseTypeCtx, {
                type: 'bar',
                data: {
                    labels: [<?php echo implode(',', array_map(function($item) { return "'" . $item['expense_type'] . "'"; }, $report_data['by_expense_type'])); ?>],
                    datasets: [{
                        label: 'Amount (₱)',
                        data: [<?php echo implode(',', array_map(function($item) { return $item['total_amount']; }, $report_data['by_expense_type'])); ?>],
                        backgroundColor: '#3C91E6',
                        borderColor: '#2980d9',
                        borderWidth: 2,
                        borderRadius: 8,
                        borderSkipped: false,
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            backgroundColor: 'rgba(0, 0, 0, 0.8)',
                            titleColor: '#ffffff',
                            bodyColor: '#ffffff',
                            borderColor: '#3C91E6',
                            borderWidth: 1,
                            callbacks: {
                                label: function(context) {
                                    const label = context.dataset.label || '';
                                    const value = context.raw || 0;
                                    return `${label}: ₱${value.toLocaleString()}`;
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: {
                                color: 'rgba(0, 0, 0, 0.1)'
                            },
                            ticks: {
                                callback: function(value) {
                                    return '₱' + value.toLocaleString();
                                },
                                font: {
                                    size: 12
                                }
                            }
                        },
                        x: {
                            grid: {
                                display: false
                            },
                            ticks: {
                                font: {
                                    size: 12
                                },
                                maxRotation: 45
                            }
                        }
                    }
                }
            });
            <?php endif; ?>

        <?php else: ?>
            // Annual Report Charts
            <?php if (!empty($report_data['monthly_breakdown'])): ?>
            // Monthly Breakdown Chart
            const monthlyBreakdownCtx = document.getElementById('monthlyBreakdownChart').getContext('2d');
            const monthlyBreakdownChart = new Chart(monthlyBreakdownCtx, {
                type: 'line',
                data: {
                    labels: [<?php echo implode(',', array_map(function($item) { return "'" . date('M', strtotime($item['month'] . '-01')) . "'"; }, $report_data['monthly_breakdown'])); ?>],
                    datasets: [{
                        label: 'Amount (₱)',
                        data: [<?php echo implode(',', array_map(function($item) { return $item['total_amount']; }, $report_data['monthly_breakdown'])); ?>],
                        borderColor: '#3C91E6',
                        backgroundColor: 'rgba(60, 145, 230, 0.1)',
                        fill: true,
                        tension: 0.4,
                        borderWidth: 3,
                        pointBackgroundColor: '#3C91E6',
                        pointBorderColor: '#ffffff',
                        pointBorderWidth: 3,
                        pointRadius: 6,
                        pointHoverRadius: 8
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            backgroundColor: 'rgba(0, 0, 0, 0.8)',
                            titleColor: '#ffffff',
                            bodyColor: '#ffffff',
                            borderColor: '#3C91E6',
                            borderWidth: 1,
                            callbacks: {
                                label: function(context) {
                                    const label = context.dataset.label || '';
                                    const value = context.raw || 0;
                                    return `${label}: ₱${value.toLocaleString()}`;
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: {
                                color: 'rgba(0, 0, 0, 0.1)'
                            },
                            ticks: {
                                callback: function(value) {
                                    return '₱' + value.toLocaleString();
                                },
                                font: {
                                    size: 12
                                }
                            }
                        },
                        x: {
                            grid: {
                                display: false
                            },
                            ticks: {
                                font: {
                                    size: 12
                                }
                            }
                        }
                    }
                }
            });
            <?php endif; ?>

            <?php if (!empty($report_data['by_fund_type'])): ?>
            // Annual Fund Type Chart
            const annualFundTypeCtx = document.getElementById('annualFundTypeChart').getContext('2d');
            const annualFundTypeChart = new Chart(annualFundTypeCtx, {
                type: 'doughnut',
                data: {
                    labels: [<?php echo implode(',', array_map(function($item) { return "'" . $item['fund_type'] . "'"; }, $report_data['by_fund_type'])); ?>],
                    datasets: [{
                        data: [<?php echo implode(',', array_map(function($item) { return $item['total_amount']; }, $report_data['by_fund_type'])); ?>],
                        backgroundColor: [
                            '#3C91E6', // General Fund
                            '#FD7238', // SEF
                            '#FFCE26'  // Trust Fund
                        ],
                        borderWidth: 3,
                        borderColor: '#FFFFFF',
                        hoverBorderWidth: 5
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'right',
                            labels: {
                                padding: 20,
                                usePointStyle: true,
                                font: {
                                    size: 14,
                                    weight: '500'for (const element of object) {
                                        
                                    }
                                }
                            }
                        },
                        tooltip: {
                            backgroundColor: 'rgba(0, 0, 0, 0.8)',
                            titleColor: '#ffffff',
                            bodyColor: '#ffffff',
                            borderColor: '#3C91E6',
                            borderWidth: 1,
                            callbacks: {
                                label: function(context) {
                                    const label = context.label || '';
                                    const value = context.raw || 0;
                                    const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                    const percentage = Math.round((value / total) * 100);
                                    return `${label}: ₱${value.toLocaleString()} (${percentage}%)`;
                                }
                            }
                        }
                    }
                }
            });
            <?php endif; ?>

            <?php if (!empty($report_data['by_expense_type'])): ?>
            // Annual Expense Type Chart
            const annualExpenseTypeCtx = document.getElementById('annualExpenseTypeChart').getContext('2d');
            const annualExpenseTypeChart = new Chart(annualExpenseTypeCtx, {
                type: 'bar',
                data: {
                    labels: [<?php echo implode(',', array_map(function($item) { return "'" . $item['expense_type'] . "'"; }, $report_data['by_expense_type'])); ?>],
                    datasets: [{
                        label: 'Amount (₱)',
                        data: [<?php echo implode(',', array_map(function($item) { return $item['total_amount']; }, $report_data['by_expense_type'])); ?>],
                        backgroundColor: '#3C91E6',
                        borderColor: '#2980d9',
                        borderWidth: 2,
                        borderRadius: 8,
                        borderSkipped: false,
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            backgroundColor: 'rgba(0, 0, 0, 0.8)',
                            titleColor: '#ffffff',
                            bodyColor: '#ffffff',
                            borderColor: '#3C91E6',
                            borderWidth: 1,
                            callbacks: {
                                label: function(context) {
                                    const label = context.dataset.label || '';
                                    const value = context.raw || 0;
                                    return `${label}: ₱${value.toLocaleString()}`;
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: {
                                color: 'rgba(0, 0, 0, 0.1)'
                            },
                            ticks: {
                                callback: function(value) {
                                    return '₱' + value.toLocaleString();
                                },
                                font: {
                                    size: 12
                                }
                            }
                        },
                        x: {
                            grid: {
                                display: false
                            },
                            ticks: {
                                font: {
                                    size: 12
                                },
                                maxRotation: 45
                            }
                        }
                    }
                }
            });
            <?php endif; ?>
        <?php endif; ?>

        // Form Enhancement
        document.getElementById('reportForm').addEventListener('submit', function(e) {
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<div class="loading"></div> Generating...';
            submitBtn.disabled = true;
            
            // Re-enable after a delay (in case of errors)
            setTimeout(() => {
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            }, 5000);
        });

        // Animate progress bars on load
        document.addEventListener('DOMContentLoaded', function() {
            const progressBars = document.querySelectorAll('.progress-fill');
            progressBars.forEach((bar, index) => {
                const width = bar.style.width;
                bar.style.width = '0%';
                setTimeout(() => {
                    bar.style.width = width;
                }, index * 100);
            });
        });

        // Export Functions
        function printReport() {
            window.print();
        }

        function exportToPDF() {
            // Simple implementation - you can enhance this with a proper PDF library
            alert('PDF export functionality can be implemented using libraries like jsPDF or by generating server-side PDFs');
        }

        function exportToCSV() {
            // Simple CSV export implementation
            let csvContent = "data:text/csv;charset=utf-8,";
            
            // Add report header
            csvContent += "<?php echo $report_title; ?>\n";
            csvContent += "Report Period: <?php echo date('M j, Y', strtotime($report_data['start_date'])); ?> to <?php echo date('M j, Y', strtotime($report_data['end_date'])); ?>\n\n";
            
            // Add fund type data if available
            <?php if (!empty($report_data['by_fund_type'])): ?>
            csvContent += "Fund Type,Count,Total Amount\n";
            <?php foreach ($report_data['by_fund_type'] as $fund): ?>
            csvContent += "<?php echo $fund['fund_type']; ?>,<?php echo $fund['count']; ?>,<?php echo $fund['total_amount']; ?>\n";
            <?php endforeach; ?>
            csvContent += "\n";
            <?php endif; ?>

            // Add expense type data if available
            <?php if (!empty($report_data['by_expense_type'])): ?>
            csvContent += "Expense Type,Count,Total Amount\n";
            <?php foreach ($report_data['by_expense_type'] as $type): ?>
            csvContent += "<?php echo $type['expense_type']; ?>,<?php echo $type['count']; ?>,<?php echo $type['total_amount']; ?>\n";
            <?php endforeach; ?>
            <?php endif; ?>

            const encodedUri = encodeURI(csvContent);
            const link = document.createElement("a");
            link.setAttribute("href", encodedUri);
            link.setAttribute("download", "expense_report_<?php echo date('Y-m-d'); ?>.csv");
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }
    </script>
</body>
</html>
