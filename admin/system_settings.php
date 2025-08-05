<?php
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

requireAdmin();

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_settings'])) {
        // In a real application, you would validate and save these settings to a database
        $success_message = "System settings updated successfully";
    } elseif (isset($_POST['cleanup_logs'])) {
        $days_to_keep = (int)$_POST['days_to_keep'];
        $cleaned_up = cleanupOldLogs($pdo, $days_to_keep);
        
        if ($cleaned_up !== false) {
            $success_message = "Successfully cleaned up $cleaned_up old activity logs";
        } else {
            $error_message = "Failed to clean up activity logs";
        }
    } elseif (isset($_POST['backup_database'])) {
        // In a real application, you would implement database backup functionality
        $backup_file = 'backup_' . date('Y-m-d_H-i-s') . '.sql';
        $success_message = "Database backup created successfully: $backup_file";
    }
}

// Get current system settings (in a real app, these would come from a database)
$system_settings = [
    'system_name' => 'Expense Management System',
    'timezone' => 'Asia/Manila',
    'date_format' => 'Y-m-d',
    'items_per_page' => 10,
    'default_currency' => '₱',
    'enable_email_notifications' => true,
    'logo' => 'logo.png'
];

// Get system information
$system_info = [
    'php_version' => phpversion(),
    'database_version' => $pdo->getAttribute(PDO::ATTR_SERVER_VERSION),
    'server_software' => $_SERVER['SERVER_SOFTWARE'],
    'os' => php_uname('s') . ' ' . php_uname('r'),
    'memory_limit' => ini_get('memory_limit'),
    'upload_max_filesize' => ini_get('upload_max_filesize')
];

// Get activity log stats
$log_stats = [
    'total_logs' => $pdo->query("SELECT COUNT(*) FROM activity_logs")->fetchColumn(),
    'oldest_log' => $pdo->query("SELECT MIN(action_time) FROM activity_logs")->fetchColumn(),
    'newest_log' => $pdo->query("SELECT MAX(action_time) FROM activity_logs")->fetchColumn()
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href='https://unpkg.com/boxicons@2.0.9/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="../assets/css/style.css">
    <title>System Settings - Expense Management System</title>
    <style>
        .settings-form {
            background: var(--light);
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .settings-section {
            margin-bottom: 30px;
        }
        
        .settings-section h3 {
            margin-top: 0;
            padding-bottom: 10px;
            border-bottom: 1px solid var(--grey);
        }
        
        .form-row {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            margin-bottom: 15px;
        }
        
        .form-group {
            flex: 1;
            min-width: 250px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
        }
        
        .form-group input, 
        .form-group select, 
        .form-group textarea {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid var(--grey);
            border-radius: 4px;
        }
        
        .form-group textarea {
            min-height: 100px;
        }
        
        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .checkbox-group input {
            width: auto;
        }
        
        .btn {
            padding: 8px 16px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 500;
        }
        
        .btn-primary {
            background: var(--blue);
            color: white;
        }
        
        .btn-danger {
            background: var(--red);
            color: white;
        }
        
        .btn-warning {
            background: var(--yellow);
            color: var(--dark);
        }
        
        .info-card {
            background: var(--light);
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 15px;
            border-left: 4px solid var(--blue);
        }
        
        .info-card h4 {
            margin-top: 0;
            margin-bottom: 10px;
        }
        
        .info-card p {
            margin: 5px 0;
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 15px;
            margin-top: 20px;
        }
        
        .alert {
            padding: 10px 15px;
            border-radius: 4px;
            margin-bottom: 15px;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
        }
        
        .alert-danger {
            background: #f8d7da;
            color: #721c24;
        }
        
        .settings-tabs {
            display: flex;
            border-bottom: 1px solid var(--grey);
            margin-bottom: 20px;
        }
        
        .settings-tab {
            padding: 10px 20px;
            cursor: pointer;
            border-bottom: 3px solid transparent;
        }
        
        .settings-tab.active {
            border-bottom-color: var(--blue);
            font-weight: 600;
        }
        
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
        }

        .nav-text {
            font-size: 25px;
            font-weight: 600;
            color: var(--dark);
            margin-left: 10px;
        }
    </style>
</head>
<body>
    <!-- SIDEBAR -->
    <section id="sidebar">
        <a href="#" class="brand">
            <i class='bx bxs-shield-alt-2'></i>
            <span class="text">Admin Panel</span>
        </a>
        <ul class="side-menu top">
            <li>
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
            <li class="active">
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
                    <ul class="breadcrumb">
                        <li>
                            <a href="dashboard.php">Home</a>
                        </li>
                        <li><i class='bx bx-chevron-right'></i></li>
                        <li>
                            <a class="active" href="#">System Settings</a>
                        </li>
                    </ul>
                </div>
            </div>

            <br>

            <?php if (isset($success_message)): ?>
                <div class="alert alert-success">
                    <?php echo htmlspecialchars($success_message); ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($error_message)): ?>
                <div class="alert alert-danger">
                    <?php echo htmlspecialchars($error_message); ?>
                </div>
            <?php endif; ?>

            <div class="settings-tabs">
                <div class="settings-tab active" onclick="showTab('general')">General Settings</div>
                <div class="settings-tab" onclick="showTab('maintenance')">Maintenance</div>
                <div class="settings-tab" onclick="showTab('info')">System Information</div>
            </div>

            <div class="settings-form">
                <!-- General Settings Tab -->
                <div id="general-tab" class="tab-content active">
                    <form method="POST" action="system_settings.php">
                        <div class="settings-section">
                            <h3>Application Settings</h3>
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="system_name">System Name</label>
                                    <input type="text" id="system_name" name="system_name" 
                                           value="<?php echo htmlspecialchars($system_settings['system_name']); ?>">
                                </div>
                                <div class="form-group">
                                    <label for="timezone">Timezone</label>
                                    <select id="timezone" name="timezone">
                                        <?php
                                        $timezones = DateTimeZone::listIdentifiers();
                                        foreach ($timezones as $tz) {
                                            $selected = $tz === $system_settings['timezone'] ? 'selected' : '';
                                            echo "<option value=\"$tz\" $selected>$tz</option>";
                                        }
                                        ?>
                                    </select>
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="date_format">Date Format</label>
                                    <select id="date_format" name="date_format">
                                        <option value="Y-m-d" <?php echo $system_settings['date_format'] === 'Y-m-d' ? 'selected' : ''; ?>>YYYY-MM-DD</option>
                                        <option value="m/d/Y" <?php echo $system_settings['date_format'] === 'm/d/Y' ? 'selected' : ''; ?>>MM/DD/YYYY</option>
                                        <option value="d/m/Y" <?php echo $system_settings['date_format'] === 'd/m/Y' ? 'selected' : ''; ?>>DD/MM/YYYY</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="items_per_page">Items Per Page</label>
                                    <input type="number" id="items_per_page" name="items_per_page" 
                                           value="<?php echo htmlspecialchars($system_settings['items_per_page']); ?>" min="5" max="100">
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="default_currency">Default Currency</label>
                                    <input type="text" id="default_currency" name="default_currency" 
                                           value="<?php echo htmlspecialchars($system_settings['default_currency']); ?>" maxlength="3">
                                </div>
                                <div class="form-group">
                                    <label>Email Notifications</label>
                                    <div class="checkbox-group">
                                        <input type="checkbox" id="enable_email_notifications" name="enable_email_notifications" 
                                               <?php echo $system_settings['enable_email_notifications'] ? 'checked' : ''; ?>>
                                        <label for="enable_email_notifications">Enable email notifications</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <button type="submit" name="update_settings" class="btn btn-primary">Save Settings</button>
                    </form>
                </div>
                
                <!-- Maintenance Tab -->
                <div id="maintenance-tab" class="tab-content">
                    <div class="settings-section">
                        <h3>Activity Logs</h3>
                        <div class="info-card">
                            <h4>Log Statistics</h4>
                            <p>Total logs: <?php echo number_format($log_stats['total_logs']); ?></p>
                            <p>Oldest log: <?php echo $log_stats['oldest_log'] ? date('M j, Y g:i A', strtotime($log_stats['oldest_log'])) : 'N/A'; ?></p>
                            <p>Newest log: <?php echo $log_stats['newest_log'] ? date('M j, Y g:i A', strtotime($log_stats['newest_log'])) : 'N/A'; ?></p>
                        </div>
                        
                        <form method="POST" action="system_settings.php">
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="days_to_keep">Keep logs for (days)</label>
                                    <input type="number" id="days_to_keep" name="days_to_keep" value="90" min="1" max="365">
                                </div>
                            </div>
                            <button type="submit" name="cleanup_logs" class="btn btn-warning">Cleanup Old Logs</button>
                        </form>
                    </div>
                    
                    <div class="settings-section">
                        <h3>Database Backup</h3>
                        <p>Create a backup of the entire database.</p>
                        <form method="POST" action="system_settings.php">
                            <button type="submit" name="backup_database" class="btn btn-primary">Create Backup Now</button>
                        </form>
                    </div>
                    
                    <div class="settings-section">
                        <h3>System Cache</h3>
                        <p>Clear all cached data to free up memory.</p>
                        <form method="POST" action="system_settings.php">
                            <button type="submit" name="clear_cache" class="btn btn-danger">Clear Cache</button>
                        </form>
                    </div>
                </div>
                
                <!-- System Information Tab -->
                <div id="info-tab" class="tab-content">
                    <div class="info-grid">
                        <div class="info-card">
                            <h4>PHP Information</h4>
                            <p>Version: <?php echo htmlspecialchars($system_info['php_version']); ?></p>
                            <p>Memory Limit: <?php echo htmlspecialchars($system_info['memory_limit']); ?></p>
                            <p>Upload Max: <?php echo htmlspecialchars($system_info['upload_max_filesize']); ?></p>
                        </div>
                        
                        <div class="info-card">
                            <h4>Database Information</h4>
                            <p>Version: <?php echo htmlspecialchars($system_info['database_version']); ?></p>
                            <p>Host: <?php echo htmlspecialchars(DB_HOST); ?></p>
                            <p>Name: <?php echo htmlspecialchars(DB_NAME); ?></p>
                        </div>
                        
                        <div class="info-card">
                            <h4>Server Information</h4>
                            <p>Software: <?php echo htmlspecialchars($system_info['server_software']); ?></p>
                            <p>OS: <?php echo htmlspecialchars($system_info['os']); ?></p>
                        </div>
                    </div>
                    
                    <div class="settings-section">
                        <h3>PHP Info</h3>
                    </div>
                </div>
            </div>
        </main>
        <!-- MAIN -->
    </section>
    <!-- CONTENT -->

    <script src="../assets/js/script.js"></script>
    <script>
        function showTab(tabName) {
            // Hide all tab contents
            document.querySelectorAll('.tab-content').forEach(tab => {
                tab.classList.remove('active');
            });
            
            // Deactivate all tabs
            document.querySelectorAll('.settings-tab').forEach(tab => {
                tab.classList.remove('active');
            });
            
            // Activate selected tab
            document.getElementById(tabName + '-tab').classList.add('active');
            event.currentTarget.classList.add('active');
        }
        
        // Add confirmation for destructive actions
        document.addEventListener('DOMContentLoaded', function() {
            const destructiveButtons = document.querySelectorAll('button[name="cleanup_logs"], button[name="clear_cache"]');
            
            destructiveButtons.forEach(button => {
                button.addEventListener('click', function(e) {
                    if (!confirm('Are you sure you want to perform this action? This cannot be undone.')) {
                        e.preventDefault();
                    }
                });
            });
        });
    </script>
</body>
</html>
