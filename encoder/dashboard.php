<?php include 'functions/dashboard-functions.php' ?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href='https://unpkg.com/boxicons@2.0.9/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="../assets/css/style.css">
    <title>Encoder Dashboard - Expense Management System</title>
    <link rel="stylesheet" href="functions/css/dashboard.css">
</head>
<body>
    <!-- SIDEBAR -->
    <section id="sidebar">
        <a href="#" class="brand">
            <i class='bx bxs-wallet'></i>
            <span class="text"><?php echo htmlspecialchars($_SESSION['full_name']); ?></span>
        </a>
        <ul class="side-menu top">
            <li class="active">
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
                    <h1>Dashboard</h1>
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
            </div> <br>

			<!-- Welcome Banner -->
            <div class="welcome-banner fade-in">
                <div class="welcome-content">
                    <h2>Welcome back, <?php echo htmlspecialchars($full_name); ?>! 👋</h2>
                    <p>Here's what's happening with your expenses today</p>
                </div>
            </div>

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

                <!-- Total Monthly Amount -->
                <li class="total-amount">
                    <i class='bx bxs-dollar-circle'></i>
                    <span class="text">
                        <h3>₱<?php echo number_format($total_monthly_amount, 2); ?></h3>
                        <p>Total Monthly</p>
                    </span>
                </li>
            </ul>

			<br>

			<!-- Monthly Stats Header -->
            <div class="monthly-stats-header">
                <h3>📊 Monthly Statistics for <?php echo $current_month_name; ?></h3>
                <p>All amounts shown are for the current month and will reset at the beginning of each month</p>
            </div>

            <!-- Quick Actions -->
            <div class="table-data">
                <div class="order">
                    <div class="head">
                        <h3>Quick Actions</h3>
                    </div>
                    <div class="quick-actions">
                        <div class="action-card">
                            <a href="encode.php">
                                <i class='bx bx-plus-circle'></i>
                                <h4>Encode New Expense</h4>
                                <p>Add a new expense entry</p>
                            </a>
                        </div>
                        <div class="action-card">
                            <a href="my_expenses.php">
                                <i class='bx bx-list-ul'></i>
                                <h4>View My Expenses</h4>
                                <p>See all expenses you've encoded</p>
                            </a>
                        </div>
                        <div class="action-card">
                            <a href="export.php">
                                <i class='bx bx-download'></i>
                                <h4>Export Reports</h4>
                                <p>Download expense reports</p>
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
</body>
</html>
