<?php include 'functions/logs.php'; ?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href='https://unpkg.com/boxicons@2.0.9/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="../assets/css/style.css">
    <title>Activity Logs - Expense Management System</title>
    <link rel="stylesheet" href="functions/css/logs.css">
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
            <li class="active">
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
                    <ul class="breadcrumb">
                        <li>
                            <a href="dashboard.php">Home</a>
                        </li>
                        <li><i class='bx bx-chevron-right'></i></li>
                        <li>
                            <a class="active" href="#">Activity Logs</a>
                        </li>
                    </ul>
                </div>
            </div>

            <br>

            <!-- Filter Form -->
            <div class="filter-container">
                <div class="filter-header">
                    <i class='bx bx-filter-alt'></i>
                    <h3>Filter Activities</h3>
                </div>
                <form method="GET" action="activity_logs.php">
                    <div class="filter-grid">
                        <div class="filter-group">
                            <label for="user_id">
                                <i class='bx bx-user'></i> User
                            </label>
                            <select id="user_id" name="user_id">
                                <option value="">All Users</option>
                                <?php foreach ($users as $user): ?>
                                    <option value="<?php echo $user['id']; ?>" <?php echo $user_id == $user['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($user['full_name']); ?> (<?php echo htmlspecialchars($user['username']); ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="filter-group">
                            <label for="action">
                                <i class='bx bx-cog'></i> Action Type
                            </label>
                            <select id="action" name="action">
                                <option value="">All Actions</option>
                                <?php foreach ($action_types as $type): ?>
                                    <option value="<?php echo $type; ?>" <?php echo $action === $type ? 'selected' : ''; ?>>
                                        <?php echo $type; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="filter-group">
                            <label for="date_from">
                                <i class='bx bx-calendar'></i> Date From
                            </label>
                            <input type="date" id="date_from" name="date_from" value="<?php echo htmlspecialchars($date_from); ?>">
                        </div>
                        <div class="filter-group">
                            <label for="date_to">
                                <i class='bx bx-calendar'></i> Date To
                            </label>
                            <input type="date" id="date_to" name="date_to" value="<?php echo htmlspecialchars($date_to); ?>">
                        </div>
                    </div>
                    <div class="filter-actions">
                        <button type="submit" class="btn-filter btn-primary">
                            <i class='bx bx-search'></i> Apply Filters
                        </button>
                        <a href="activity_logs.php" class="btn-filter btn-secondary">
                            <i class='bx bx-refresh'></i> Reset
                        </a>
                    </div>
                </form>
            </div>

            <!-- Activity Cards -->
            <?php if (!empty($activities)): ?>
                <div class="activity-cards">
                    <?php foreach ($activities as $activity): ?>
                        <div class="activity-card <?php echo strtolower($activity['action']); ?>">
                            <div class="activity-header">
                                <div class="activity-user">
                                    <div class="user-avatar">
                                        <?php echo strtoupper(substr($activity['full_name'], 0, 2)); ?>
                                    </div>
                                    <div class="user-info">
                                        <h4><?php echo htmlspecialchars($activity['full_name']); ?></h4>
                                        <div class="username">@<?php echo htmlspecialchars($activity['username']); ?></div>
                                    </div>
                                </div>
                                <div class="activity-time">
                                    <div><?php echo date('M j, Y', strtotime($activity['action_time'])); ?></div>
                                    <div><?php echo date('g:i A', strtotime($activity['action_time'])); ?></div>
                                </div>
                            </div>
                            
                            <div class="activity-content">
                                <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 8px;">
                                    <span class="activity-badge badge-<?php echo strtolower($activity['action']); ?>">
                                        <?php echo $activity['action']; ?>
                                    </span>
                                </div>
                                
                                <div class="activity-message">
                                    <?php echo generateActivityMessage($activity); ?>
                                </div>
                                
                                <?php 
                                $details = getActivityDetails($activity);
                                if (!empty($details)): 
                                ?>
                                <div class="activity-details">
                                    <i class='bx bx-info-circle'></i>
                                    <?php echo implode(' • ', $details); ?>
                                </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="activity-footer">
                                <div class="table-info">
                                    <i class='bx bx-table'></i>
                                    <?php echo htmlspecialchars($activity['table_name']); ?> 
                                    (ID: <?php echo $activity['record_id']; ?>)
                                </div>
                                <button class="view-details-btn" data-activity-id="<?php echo $activity['id']; ?>">
                                    <i class='bx bx-show'></i>
                                    View Details
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <!-- Pagination -->
                <div class="pagination-container">
                    <div class="pagination-info">
                        Showing <?php echo count($activities); ?> of <?php echo $total_activities; ?> records
                    </div>
                    
                    <?php if ($total_pages > 1): ?>
                        <?php if ($page > 1): ?>
                            <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>" class="pagination-btn">
                                <i class='bx bx-chevron-left'></i> Previous
                            </a>
                        <?php endif; ?>
                        
                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <?php if ($i == $page): ?>
                                <span class="pagination-btn current"><?php echo $i; ?></span>
                            <?php else: ?>
                                <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>" class="pagination-btn"><?php echo $i; ?></a>
                            <?php endif; ?>
                        <?php endfor; ?>
                        
                        <?php if ($page < $total_pages): ?>
                            <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>" class="pagination-btn">
                                Next <i class='bx bx-chevron-right'></i>
                            </a>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <i class='bx bx-search-alt-2'></i>
                    <h3>No Activity Logs Found</h3>
                    <p>No activities match your current filter criteria. Try adjusting your filters or check back later.</p>
                </div>
            <?php endif; ?>
        </main>
        <!-- MAIN -->
    </section>
    <!-- CONTENT -->

    <!-- Activity Details Modal -->
    <div id="activityDetailsModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">
                    <i class='bx bx-detail'></i> Activity Details
                </h3>
                <button class="close-btn">&times;</button>
            </div>
            <div class="modal-body" id="modalActivityContent">
                <!-- Content will be loaded via AJAX -->
            </div>
        </div>
    </div>

    <script src="../assets/js/script.js"></script>
    <script src="functions/js/logs.js"></script>
</body>
</html>
