<?php include 'functions/activity.php';?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Activity - Expense Management System</title>
    <link href='https://unpkg.com/boxicons@2.0.9/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="functions/css/activity.css">
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
            <li>
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
            <li class="active">
                <a href="my_activity.php">
                    <i class='bx bxs-time'></i>
                    <span class="text">My Activity</span>
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
            <span class="nav-text">My Activity</span>
        </nav>
        <!-- NAVBAR -->

        <!-- MAIN -->
        <main>
            <div class="head-title">
                <div class="left">
                    <h1>My Activity</h1>
                    <ul class="breadcrumb">
                        <li>
                            <a href="dashboard.php">Dashboard</a>
                        </li>
                        <li><i class='bx bx-chevron-right'></i></li>
                        <li>
                            <a class="active" href="#">My Activity</a>
                        </li>
                    </ul>
                </div>
            </div>

            <div class="activity-container">
                <div class="activity-card">
                    <h2><i class='bx bx-receipt'></i> Recent Expense Activities</h2>
                    
                    <?php if (!empty($expense_activities)): ?>
                        <div class="activity-list">
                            <?php foreach ($expense_activities as $activity): ?>
                                <?php $formatted = formatActivityDisplay($activity); ?>
                                <div class="activity-item">
                                    <div class="activity-message activity-<?php echo strtolower($activity['action']); ?>">
                                        <i class='bx bx-<?php 
                                            switch($activity['action']) {
                                                case 'CREATE': echo 'plus'; break;
                                                case 'UPDATE': echo 'edit'; break;
                                                case 'DELETE': echo 'trash'; break;
                                                default: echo 'info-circle';
                                            }
                                        ?>'></i>
                                        <?php echo $formatted['message']; ?>
                                    </div>
                                    <div class="activity-time">
                                        <?php echo $formatted['time']; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class='bx bx-time'></i>
                            <h3>No activity yet</h3>
                            <p>Your expense activities will appear here</p>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="activity-card">
                    <h2><i class='bx bx-shield'></i> Login/Logout History</h2>
                    
                    <?php if (!empty($auth_activities)): ?>
                        <div class="activity-list">
                            <?php foreach ($auth_activities as $activity): ?>
                                <?php $formatted = formatActivityDisplay($activity); ?>
                                <div class="activity-item">
                                    <div class="activity-message activity-<?php echo strtolower($activity['action']); ?>">
                                        <i class='bx bx-<?php echo strtolower($activity['action']) === 'login' ? 'log-in' : 'log-out' ?>'></i>
                                        <?php echo $formatted['message']; ?>
                                    </div>
                                    <div class="activity-time">
                                        <?php echo $formatted['time']; ?>
                                    </div>
                                    <?php if ($activity['action'] === 'LOGIN'): ?>
                                        <div class="activity-details" style="font-size: 12px; color: var(--dark-grey); margin-top: 5px;">
                                            <i class='bx bx-globe'></i> IP: <?php echo json_decode($activity['new_values'], true)['ip_address'] ?? 'Unknown'; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class='bx bx-shield'></i>
                            <h3>No login/logout history</h3>
                            <p>Your authentication activities will appear here</p>
                        </div>
                    <?php endif; ?>
                </div>

            </div>
        </main>
        <!-- MAIN -->
    </section>
    <!-- CONTENT -->

    <script src="../assets/js/script.js"></script>
</body>
</html>
