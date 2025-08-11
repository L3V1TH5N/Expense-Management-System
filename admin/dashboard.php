<?php include 'functions/dashboard.php'; ?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href='https://unpkg.com/boxicons@2.0.9/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="../assets/css/style.css">
    <title>Admin Dashboard - Expense Management System</title>
    <link rel="stylesheet" href="functions/css/dashboard.css">
</head>
<body>
    <!-- SIDEBAR -->
    <section id="sidebar">
        <a href="#" class="brand">
            <i class='bx bxs-shield-alt-2'></i>
            <span class="text">Admin Panel</span>
        </a>
        <ul class="side-menu top">
            <li class="active">
                <a href="dashboard.php">
                    <i class='bx bxs-dashboard'></i>
                    <span class="text">Dashboard</span>
                </a>
            </li>
            <li>
                <a href="manage_users.php">
                    <i class='bx bxs-user-account'></i>
                    <span class="text">Manage Users</span>
                </a>
            </li>
            <li>
                <a href="all_expenses.php">
                    <i class='bx bxs-receipt'></i>
                    <span class="text">All Expenses</span>
                </a>
            </li>
            <li>
                <a href="reports.php">
                    <i class='bx bxs-report'></i>
                    <span class="text">Reports</span>
                </a>
            </li>
            <li>
                <a href="activity_logs.php">
                    <i class='bx bxs-time'></i>
                    <span class="text">Activity Logs</span>
                </a>
            </li>
            <li>
                <a href="system_settings.php">
                    <i class='bx bxs-cog'></i>
                    <span class="text">System Settings</span>
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
                    <h1>Admin Dashboard</h1>
                    <ul class="breadcrumb">
                        <li>
                            <a href="dashboard.php">Home</a>
                        </li>
                        <li><i class='bx bx-chevron-right'></i></li>
                        <li>
                            <a class="active" href="#">Dashboard</a>
                        </li>
                    </ul>
                </div>
            </div>

            <br>

            <!-- Welcome Banner -->
            <div class="admin-welcome-banner fade-in">
                <div class="welcome-content">
                    <h2>
                        Welcome back, <?php echo htmlspecialchars($full_name); ?>! 
                        <span class="admin-badge">
                            <i class='bx bxs-shield-alt-2'></i>
                            Administrator
                        </span>
                    </h2>
                    <p>Monitor today's system performance and manage the expense management system</p>
                </div>
            </div>

            <!-- System Alerts -->
            <?php if (!empty($system_alerts)): ?>
            <div class="system-alerts">
                <h4><i class='bx bxs-error-circle'></i> System Alerts</h4>
                <ul>
                    <?php foreach ($system_alerts as $alert): ?>
                        <li><?php echo htmlspecialchars($alert); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>

            <!-- Fund Distribution Stats -->
            <ul class="box-info">
                <!-- General Fund -->
                <li class="general-fund">
                    <i class='bx bxs-bank'></i>
                    <span class="text">
                        <h3>₱<?php echo number_format($general_fund, 2); ?></h3>
                        <p>General Fund</p>
                    </span>
                </li>

                <!-- Special Education Fund -->
                <li class="sef-fund">
                    <i class='bx bxs-graduation'></i>
                    <span class="text">
                        <h3>₱<?php echo number_format($sef_fund, 2); ?></h3>
                        <p>Special Education Fund (SEF)</p>
                    </span>
                </li>

                <!-- Trust Fund -->
                <li class="trust-fund">
                    <i class='bx bxs-shield-alt-2'></i>
                    <span class="text">
                        <h3>₱<?php echo number_format($trust_fund, 2); ?></h3>
                        <p>Trust Fund</p>
                    </span>
                </li>

                <!-- Total Daily Amount -->
                <li class="total-amount">
                    <i class='bx bxs-dollar-circle'></i>
                    <span class="text">
                        <h3>₱<?php echo number_format($total_daily_amount, 2); ?></h3>
                        <p>Total Today</p>
                    </span>
                </li>
            </ul>

            <!-- System Overview Stats -->
            <div class="admin-stats">
                <div class="stat-card total-expenses">
                    <i class='bx bxs-receipt'></i>
                    <span class="text">
                        <h3><?php echo number_format($total_expenses); ?></h3>
                        <p>Total Expenses Today</p>
                    </span>
                </div>

                <div class="stat-card total-users">
                    <i class='bx bxs-user-account'></i>
                    <span class="text">
                        <h3><?php echo number_format($total_users); ?></h3>
                        <p>System Users</p>
                    </span>
                </div>

                <div class="stat-card active-encoders">
                    <i class='bx bxs-user-check'></i>
                    <span class="text">
                        <h3><?php echo number_format($active_encoders); ?></h3>
                        <p>Active Encoders Today</p>
                    </span>
                </div>

                <div class="stat-card cash-advances">
                    <i class='bx bxs-credit-card'></i>
                    <span class="text">
                        <h3><?php echo number_format($cash_advances); ?></h3>
                        <p>Cash Advances Today</p>
                    </span>
                </div>
            </div>

            <!-- Daily Stats Header -->
            <div class="daily-stats-header">
                <h3>💰 Daily Fund Allocation for <?php echo $today_formatted; ?></h3>
                <p>System-wide fund distribution and totals for today</p>
            </div>

            <!-- Admin Actions & Data Tables -->
            <div class="table-data">

                <!-- Top Encoders Today -->
                <div class="order">
                    <div class="head">
                        <h3>Top Encoders - Today</h3>
                        <a href="manage_users.php" class="btn-link">View All Users</a>
                    </div>
                    <?php if (!empty($top_encoders)): ?>
                    <table class="top-encoders-table">
                        <thead>
                            <tr>
                                <th>Rank</th>
                                <th>Encoder</th>
                                <th>Expenses</th>
                                <th>Total Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $rank = 1;
                            foreach ($top_encoders as $encoder): 
                            ?>
                            <tr>
                                <td>
                                    <span class="encoder-rank rank-<?php echo $rank <= 3 ? $rank : 'other'; ?>">
                                        <?php echo $rank; ?>
                                    </span>
                                </td>
                                <td>
                                    <strong><?php echo htmlspecialchars($encoder['full_name']); ?></strong><br>
                                    <small>@<?php echo htmlspecialchars($encoder['username']); ?></small>
                                </td>
                                <td><?php echo number_format($encoder['expense_count']); ?></td>
                                <td>₱<?php echo number_format($encoder['total_amount'], 2); ?></td>
                            </tr>
                            <?php 
                            $rank++;
                            endforeach; ?>
                        </tbody>
                    </table>
                    <?php else: ?>
                    <p style="text-align: center; color: var(--dark-grey); padding: 20px;">
                        No encoder activity found today.
                    </p>
                    <?php endif; ?>
                </div>

                <!-- Recent System Activity Today -->
                <div class="order">
                    <div class="head">
                        <h3>Recent System Activity - Today</h3>
                        <a href="activity_logs.php" class="btn-link">View All Activities</a>
                    </div>
                    <div style="max-height: 400px; overflow-y: auto;">
                        <?php if (!empty($recent_activities)): ?>
                            <?php foreach ($recent_activities as $activity): ?>
                            <div class="activity-item">
                                <div class="activity-icon <?php echo strtolower($activity['action']); ?>">
                                    <?php
                                    $icons = [
                                        'LOGIN' => 'bx-log-in',
                                        'LOGOUT' => 'bx-log-out',
                                        'CREATE' => 'bx-plus-circle',
                                        'UPDATE' => 'bx-edit',
                                        'DELETE' => 'bx-trash',
                                        'PASSWORD_CHANGE' => 'bx-key'
                                    ];
                                    $icon = $icons[$activity['action']] ?? 'bx-info-circle';
                                    ?>
                                    <i class='bx <?php echo $icon; ?>'></i>
                                </div>
                                <div class="activity-content">
                                    <div class="activity-title">
                                        <?php
                                        $action = strtolower($activity['action']);
                                        $table = $activity['table_name'];
                                        $userName = htmlspecialchars($activity['full_name']);
                                        
                                        switch ($activity['action']) {
                                            case 'LOGIN':
                                                echo "{$userName} logged into the system";
                                                break;
                                            case 'LOGOUT':
                                                echo "{$userName} logged out of the system";
                                                break;
                                            case 'CREATE':
                                                if ($table === 'expenses') {
                                                    echo "{$userName} created a new expense record";
                                                } elseif ($table === 'users') {
                                                    echo "{$userName} created a new user account";
                                                } else {
                                                    echo "{$userName} created a new {$table} record";
                                                }
                                                break;
                                            case 'UPDATE':
                                                if ($table === 'expenses') {
                                                    echo "{$userName} updated an expense record";
                                                } elseif ($table === 'users') {
                                                    echo "{$userName} updated a user profile";
                                                } else {
                                                    echo "{$userName} updated a {$table} record";
                                                }
                                                break;
                                            case 'DELETE':
                                                if ($table === 'expenses') {
                                                    echo "{$userName} deleted an expense record";
                                                } elseif ($table === 'users') {
                                                    echo "{$userName} deleted a user account";
                                                } else {
                                                    echo "{$userName} deleted a {$table} record";
                                                }
                                                break;
                                            case 'PASSWORD_CHANGE':
                                                echo "{$userName} changed their password";
                                                break;
                                            default:
                                                echo "{$userName} performed {$action} on {$table}";
                                        }
                                        ?>
                                    </div>
                                    <div class="activity-time">
                                        <?php echo date('M j, Y g:i A', strtotime($activity['action_time'])); ?>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                        <p style="text-align: center; color: var(--dark-grey); padding: 20px;">
                            No recent system activity found today.
                        </p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="table-data">
                <!-- Recent Expenses Today (All Users) -->
                <div class="order">
                    <div class="head">
                        <h3>Recent Expenses (All Users) - Today</h3>
                        <a href="all_expenses.php" class="btn-link">View All Expenses</a>
                    </div>
                    <?php if (!empty($recent_expenses)): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Payee</th>
                                <th>Amount</th>
                                <th>Fund Type</th>
                                <th>Office</th>
                                <th>Encoder</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_expenses as $expense): ?>
                            <tr>
                                <td><?php echo date('M j, Y', strtotime($expense['date'])); ?></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($expense['payee']); ?></strong><br>
                                    <small><?php echo htmlspecialchars($expense['expense_type']); ?></small>
                                </td>
                                <td>₱<?php echo number_format($expense['calculated_total'], 2); ?></td>
                                <td>
                                    <span class="status 
                                        <?php 
                                        echo $expense['fund_type'] === 'General Fund' ? 'completed' : 
                                            ($expense['fund_type'] === 'Special Education Fund' ? 'process' : 'pending'); 
                                        ?>">
                                        <?php echo htmlspecialchars($expense['fund_type']); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php 
                                    if ($expense['full_office_path']) {
                                        echo htmlspecialchars($expense['full_office_path']);
                                    } else {
                                        echo htmlspecialchars($expense['office_name']);
                                    }
                                    ?>
                                </td>
                                <td><?php echo htmlspecialchars($expense['encoder_name']); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php else: ?>
                    <p style="text-align: center; color: var(--dark-grey); padding: 20px;">
                        No recent expenses found today.
                    </p>
                    <?php endif; ?>
                </div>

                <!-- Expense Type Breakdown Today -->
                <div class="order">
                    <div class="head">
                        <h3>Expense Type Breakdown - Today</h3>
                        <a href="reports.php" class="btn-link">Detailed Reports</a>
                    </div>
                    <?php if (!empty($expense_types)): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Expense Type</th>
                                <th>Count</th>
                                <th>Total Amount</th>
                                <th>Percentage</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($expense_types as $type): ?>
                            <?php 
                            $percentage = $total_daily_amount > 0 ? ($type['total_amount'] / $total_daily_amount) * 100 : 0;
                            ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($type['expense_type']); ?></strong>
                                </td>
                                <td><?php echo number_format($type['count']); ?></td>
                                <td>₱<?php echo number_format($type['total_amount'], 2); ?></td>
                                <td>
                                    <div style="display: flex; align-items: center; gap: 8px;">
                                        <div style="flex-grow: 1; height: 6px; background: var(--grey); border-radius: 3px; overflow: hidden;">
                                            <div style="height: 100%; background: var(--blue); width: <?php echo $percentage; ?>%; transition: width 0.3s ease;"></div>
                                        </div>
                                        <span style="font-size: 12px; color: var(--dark-grey); min-width: 40px;">
                                            <?php echo number_format($percentage, 1); ?>%
                                        </span>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php else: ?>
                    <p style="text-align: center; color: var(--dark-grey); padding: 20px;">
                        No expense data found today.
                    </p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Quick Actions Section -->
            <div class="daily-stats-header" style="margin-top: 2rem;">
                <h3>🚀 Quick Actions</h3>
                <p>Common administrative tasks and system management</p>
            </div>

            <div class="table-data">
                <!-- System Summary -->
                <div class="order">
                    <div class="head">
                        <h3>System Summary</h3>
                        <a href="reports.php" class="btn-link">Full Reports</a>
                    </div>
                    <div style="padding: 20px;">
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px;">
                            <div style="text-align: center; padding: 16px; background: var(--grey); border-radius: 8px;">
                                <h4 style="margin: 0; color: var(--blue);">Today's Total</h4>
                                <p style="font-size: 24px; font-weight: bold; margin: 8px 0; color: var(--dark);">
                                    ₱<?php echo number_format($total_daily_amount, 2); ?>
                                </p>
                                <small style="color: var(--dark-grey);"><?php echo $total_expenses; ?> transactions</small>
                            </div>
                            
                            <div style="text-align: center; padding: 16px; background: var(--grey); border-radius: 8px;">
                                <h4 style="margin: 0; color: var(--green);">Active Encoders</h4>
                                <p style="font-size: 24px; font-weight: bold; margin: 8px 0; color: var(--dark);">
                                    <?php echo $active_encoders; ?>
                                </p>
                                <small style="color: var(--dark-grey);">out of <?php echo $total_encoders; ?> encoders</small>
                            </div>

                            <?php if ($cash_advances > 0): ?>
                            <div style="text-align: center; padding: 16px; background: var(--grey); border-radius: 8px;">
                                <h4 style="margin: 0; color: var(--orange);">Cash Advances</h4>
                                <p style="font-size: 24px; font-weight: bold; margin: 8px 0; color: var(--dark);">
                                    <?php echo $cash_advances; ?>
                                </p>
                                <small style="color: var(--dark-grey);">requires attention</small>
                            </div>
                            <?php endif; ?>

                            <div style="text-align: center; padding: 16px; background: var(--grey); border-radius: 8px;">
                                <h4 style="margin: 0; color: var(--purple);">System Health</h4>
                                <p style="font-size: 24px; font-weight: bold; margin: 8px 0; color: var(--dark);">
                                    <?php echo empty($system_alerts) ? '✓' : '⚠'; ?>
                                </p>
                                <small style="color: var(--dark-grey);">
                                    <?php echo empty($system_alerts) ? 'All Good' : count($system_alerts) . ' alerts'; ?>
                                </small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Quick Links -->
                <div class="order">
                    <div class="head">
                        <h3>Quick Navigation</h3>
                        <small style="color: var(--dark-grey);">Frequently used admin functions</small>
                    </div>
                    <div style="padding: 20px;">
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 12px;">
                            <a href="manage_users.php" style="display: flex; align-items: center; gap: 8px; padding: 12px; background: var(--light); border-radius: 8px; text-decoration: none; color: var(--dark); border: 1px solid var(--grey); transition: all 0.2s;">
                                <i class='bx bxs-user-account' style="color: var(--blue);"></i>
                                <span>Manage Users</span>
                            </a>
                            
                            <a href="all_expenses.php" style="display: flex; align-items: center; gap: 8px; padding: 12px; background: var(--light); border-radius: 8px; text-decoration: none; color: var(--dark); border: 1px solid var(--grey); transition: all 0.2s;">
                                <i class='bx bxs-receipt' style="color: var(--green);"></i>
                                <span>All Expenses</span>
                            </a>
                            
                            <a href="reports.php" style="display: flex; align-items: center; gap: 8px; padding: 12px; background: var(--light); border-radius: 8px; text-decoration: none; color: var(--dark); border: 1px solid var(--grey); transition: all 0.2s;">
                                <i class='bx bxs-report' style="color: var(--orange);"></i>
                                <span>Reports</span>
                            </a>
                            
                            <a href="activity_logs.php" style="display: flex; align-items: center; gap: 8px; padding: 12px; background: var(--light); border-radius: 8px; text-decoration: none; color: var(--dark); border: 1px solid var(--grey); transition: all 0.2s;">
                                <i class='bx bxs-time' style="color: var(--purple);"></i>
                                <span>Activity Logs</span>
                            </a>
                            
                            <a href="system_settings.php" style="display: flex; align-items: center; gap: 8px; padding: 12px; background: var(--light); border-radius: 8px; text-decoration: none; color: var(--dark); border: 1px solid var(--grey); transition: all 0.2s;">
                                <i class='bx bxs-cog' style="color: var(--dark-grey);"></i>
                                <span>Settings</span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </main>
        <!-- MAIN -->
    </section>
    <!-- CONTENT -->
    
    <script src="../assets/js/script.js"></script>
    <script src="functions/js/dashboard.js"></script>
</body>
</html>
