<?php include 'functions/profile.php'; ?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href='https://unpkg.com/boxicons@2.0.9/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="../assets/css/style.css">
    <title>My Profile - Expense Management System</title>
    <link rel="stylesheet" href="functions/css/profile.css">
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
            <li>
                <a href="system_settings.php">
                    <i class='bx bxs-cog'></i>
                    <span class="text">System Settings</span>
                </a>
            </li>
            <br><br>
            <li class="active">
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
                            <a class="active" href="#">My Profile</a>
                        </li>
                    </ul>
                </div>
            </div>

            <br>

            <?php if ($success_message): ?>
                <div class="alert alert-success">
                    <?php echo htmlspecialchars($success_message); ?>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <?php foreach ($errors as $error): ?>
                        <p><?php echo htmlspecialchars($error); ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <div class="profile-header">
                <div class="profile-avatar">
                    <?php echo strtoupper(substr($full_name, 0, 1)); ?>
                </div>
                <div class="profile-info">
                    <h2><?php echo htmlspecialchars($full_name); ?></h2>
                    <p>@<?php echo htmlspecialchars($username); ?></p>
                    <span class="badge badge-<?php echo $role; ?>">
                        <?php echo ucfirst($role); ?>
                    </span>
                </div>
            </div>

            <div class="profile-tabs">
                <div class="profile-tab active" onclick="showTab('profile')">Profile</div>
                <div class="profile-tab" onclick="showTab('security')">Security</div>
            </div>

            <!-- Profile Tab -->
            <div id="profile-tab" class="tab-content active">
                <div class="profile-section">
                    <h3>Profile Information</h3>
                    <form method="POST" action="profile.php">
                        <div class="form-row">
                            <div class="form-group">
                                <label for="username">Username</label>
                                <input type="text" id="username" value="<?php echo htmlspecialchars($username); ?>" readonly>
                            </div>
                            <div class="form-group">
                                <label for="role">Role</label>
                                <input type="text" id="role" value="<?php echo ucfirst($role); ?>" readonly>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="full_name">Full Name</label>
                                <input type="text" id="full_name" name="full_name" value="<?php echo htmlspecialchars($full_name); ?>" required>
                            </div>
                        </div>
                        <button type="submit" name="update_profile" class="btn btn-primary">Update Profile</button>
                    </form>
                </div>
            </div>

            <!-- Security Tab -->
            <div id="security-tab" class="tab-content">
                <div class="profile-section">
                    <h3>Change Password</h3>
                    <form method="POST" action="profile.php">
                        <div class="form-row">
                            <div class="form-group">
                                <label for="current_password">Current Password</label>
                                <input type="password" id="current_password" name="current_password" required>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="new_password">New Password</label>
                                <input type="password" id="new_password" name="new_password" required>
                            </div>
                            <div class="form-group">
                                <label for="confirm_password">Confirm New Password</label>
                                <input type="password" id="confirm_password" name="confirm_password" required>
                            </div>
                        </div>
                        <button type="submit" name="change_password" class="btn btn-primary">Change Password</button>
                    </form>
                </div>

                <?php if (!empty($password_history)): ?>
                <div class="profile-section">
                    <h3>Password Change History</h3>
                    <?php foreach ($password_history as $activity): ?>
                    <div class="activity-item">
                        <div class="activity-icon password">
                            <i class='bx bx-key'></i>
                        </div>
                        <div class="activity-content">
                            <p>Password changed</p>
                            <div class="activity-time">
                                <?php echo date('M j, Y g:i A', strtotime($activity['action_time'])); ?>
                                <?php if (isset($activity['new_values'])): 
                                    $data = json_decode($activity['new_values'], true);
                                    if (isset($data['ip_address'])): ?>
                                        | IP: <?php echo htmlspecialchars($data['ip_address']); ?>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </main>
        <!-- MAIN -->
    </section>
    <!-- CONTENT -->

    <script src="../assets/js/script.js"></script>
    <script src="functions/js/profile.js"></script>
</body>
</html>
