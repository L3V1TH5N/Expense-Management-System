<?php
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

requireAdmin();

// Pagination variables
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 20;
$offset = ($page - 1) * $per_page;

// Filter variables
$user_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : null;
$action = isset($_GET['action']) ? sanitizeInput($_GET['action']) : '';
$date_from = isset($_GET['date_from']) ? sanitizeInput($_GET['date_from']) : '';
$date_to = isset($_GET['date_to']) ? sanitizeInput($_GET['date_to']) : '';

// Build base query
$query = "
    SELECT 
        al.*, 
        u.username,
        u.full_name
    FROM activity_logs al
    LEFT JOIN users u ON al.user_id = u.id
";

// Build where conditions
$conditions = [];
$params = [];

if ($user_id) {
    $conditions[] = "al.user_id = ?";
    $params[] = $user_id;
}

if (!empty($action)) {
    $conditions[] = "al.action = ?";
    $params[] = $action;
}

if (!empty($date_from) && validateDate($date_from)) {
    $conditions[] = "DATE(al.action_time) >= ?";
    $params[] = $date_from;
}

if (!empty($date_to) && validateDate($date_to)) {
    $conditions[] = "DATE(al.action_time) <= ?";
    $params[] = $date_to;
}

// Add conditions to query if any
if (!empty($conditions)) {
    $query .= " WHERE " . implode(" AND ", $conditions);
}

// Add sorting and pagination
$query .= " ORDER BY al.action_time DESC LIMIT $offset, $per_page";

// Prepare and execute the query
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$activities = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get total count for pagination
$count_query = "SELECT COUNT(*) as total FROM activity_logs al";
if (!empty($conditions)) {
    $count_query .= " WHERE " . implode(" AND ", $conditions);
}
$count_stmt = $pdo->prepare($count_query);
$count_stmt->execute($params);
$total_activities = $count_stmt->fetch()['total'];
$total_pages = ceil($total_activities / $per_page);

// Get all users for filter dropdown
$stmt = $conn->prepare("SELECT id, username, full_name FROM users ORDER BY full_name");
$stmt->execute();
$users = $stmt->fetchAll();

// Get all action types for filter dropdown
$action_types = ['LOGIN', 'LOGOUT', 'CREATE', 'UPDATE', 'DELETE', 'PASSWORD_CHANGE'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href='https://unpkg.com/boxicons@2.0.9/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="../assets/css/style.css">
    <title>Activity Logs - Expense Management System</title>
    <style>
        /* Enhanced Filter Form */
        .filter-container {
            background: var(--light);
            border-radius: 20px;
            padding: 24px;
            margin-bottom: 24px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
        }
        
        .filter-header {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid var(--grey);
        }
        
        .filter-header h3 {
            font-size: 18px;
            font-weight: 600;
            color: var(--dark);
            margin: 0;
        }
        
        .filter-header .bx {
            font-size: 20px;
            color: var(--blue);
            margin-right: 10px;
        }
        
        .filter-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .filter-group {
            display: flex;
            flex-direction: column;
        }
        
        .filter-group label {
            font-size: 14px;
            font-weight: 500;
            color: var(--dark);
            margin-bottom: 8px;
        }
        
        .filter-group select,
        .filter-group input {
            height: 40px;
            padding: 0 12px;
            border: 1px solid var(--grey);
            border-radius: 8px;
            background: var(--grey);
            color: var(--dark);
            font-size: 14px;
            transition: all 0.3s ease;
        }
        
        .filter-group select:focus,
        .filter-group input:focus {
            outline: none;
            border-color: var(--blue);
            box-shadow: 0 0 0 3px rgba(60, 145, 230, 0.1);
        }
        
        .filter-actions {
            display: flex;
            gap: 12px;
            justify-content: flex-end;
        }
        
        .btn-filter {
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .btn-primary {
            background: var(--blue);
            color: var(--light);
        }
        
        .btn-primary:hover {
            background: #2980b9;
            transform: translateY(-1px);
        }
        
        .btn-secondary {
            background: var(--dark-grey);
            color: var(--light);
        }
        
        .btn-secondary:hover {
            background: #95a5a6;
            transform: translateY(-1px);
        }
        
        /* Enhanced Activity Cards Layout */
        .activity-cards {
            display: grid;
            gap: 16px;
            margin-top: 24px;
        }
        
        .activity-card {
            background: var(--light);
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            transition: all 0.3s ease;
            border-left: 4px solid transparent;
        }
        
        .activity-card:hover {
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            transform: translateY(-2px);
        }
        
        .activity-card.login { border-left-color: #27ae60; }
        .activity-card.logout { border-left-color: #7f8c8d; }
        .activity-card.create { border-left-color: #3498db; }
        .activity-card.update { border-left-color: #f39c12; }
        .activity-card.delete { border-left-color: #e74c3c; }
        .activity-card.password_change { border-left-color: #9b59b6; }
        
        .activity-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 12px;
        }
        
        .activity-user {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: var(--blue);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--light);
            font-weight: 600;
            font-size: 14px;
        }
        
        .user-info h4 {
            font-size: 14px;
            font-weight: 600;
            color: var(--dark);
            margin: 0 0 4px 0;
        }
        
        .user-info .username {
            font-size: 12px;
            color: var(--dark-grey);
        }
        
        .activity-time {
            font-size: 12px;
            color: var(--dark-grey);
            text-align: right;
        }
        
        .activity-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .badge-login { background: rgba(39, 174, 96, 0.1); color: #27ae60; }
        .badge-logout { background: rgba(127, 140, 141, 0.1); color: #7f8c8d; }
        .badge-create { background: rgba(52, 152, 219, 0.1); color: #3498db; }
        .badge-update { background: rgba(243, 156, 18, 0.1); color: #f39c12; }
        .badge-delete { background: rgba(231, 76, 60, 0.1); color: #e74c3c; }
        .badge-password_change { background: rgba(155, 89, 182, 0.1); color: #9b59b6; }
        
        .activity-content {
            margin: 12px 0;
        }
        
        .activity-message {
            font-size: 14px;
            color: var(--dark);
            margin-bottom: 8px;
        }
        
        .activity-details {
            font-size: 12px;
            color: var(--dark-grey);
            background: var(--grey);
            padding: 8px 12px;
            border-radius: 6px;
        }
        
        .activity-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 12px;
            padding-top: 12px;
            border-top: 1px solid var(--grey);
        }
        
        .table-info {
            font-size: 12px;
            color: var(--dark-grey);
            background: var(--grey);
            padding: 4px 8px;
            border-radius: 4px;
        }
        
        .view-details-btn {
            background: var(--blue);
            color: var(--light);
            border: none;
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 12px;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 4px;
        }
        
        .view-details-btn:hover {
            background: #2980b9;
            transform: translateY(-1px);
        }
        
        /* Enhanced Pagination */
        .pagination-container {
            display: flex;
            justify-content: center;
            align-items: center;
            margin-top: 32px;
            gap: 8px;
        }
        
        .pagination-info {
            background: var(--light);
            padding: 12px 16px;
            border-radius: 8px;
            font-size: 14px;
            color: var(--dark);
            margin-right: 16px;
        }
        
        .pagination-btn {
            padding: 8px 12px;
            border: 1px solid var(--grey);
            border-radius: 6px;
            background: var(--light);
            color: var(--dark);
            text-decoration: none;
            font-size: 14px;
            transition: all 0.3s ease;
        }
        
        .pagination-btn:hover {
            background: var(--blue);
            color: var(--light);
            border-color: var(--blue);
        }
        
        .pagination-btn.current {
            background: var(--blue);
            color: var(--light);
            border-color: var(--blue);
        }
        
        /* Enhanced Modal */
        .modal {
            display: none;
            position: fixed;
            z-index: 2000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.6);
            backdrop-filter: blur(4px);
        }
        
        .modal-content {
            background: var(--light);
            margin: 5% auto;
            padding: 0;
            border-radius: 16px;
            width: 90%;
            max-width: 800px;
            max-height: 80vh;
            overflow: hidden;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            animation: modalSlideIn 0.3s ease-out;
        }
        
        @keyframes modalSlideIn {
            from {
                opacity: 0;
                transform: translateY(-50px) scale(0.95);
            }
            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }
        
        .modal-header {
            background: var(--blue);
            color: var(--light);
            padding: 20px 24px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .modal-title {
            font-size: 18px;
            font-weight: 600;
            margin: 0;
        }
        
        .close-btn {
            background: none;
            border: none;
            color: var(--light);
            font-size: 24px;
            cursor: pointer;
            padding: 4px;
            border-radius: 4px;
            transition: background 0.3s ease;
        }
        
        .close-btn:hover {
            background: rgba(255,255,255,0.1);
        }
        
        .modal-body {
            padding: 24px;
            max-height: calc(80vh - 80px);
            overflow-y: auto;
        }
        
        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            background: var(--light);
            border-radius: 20px;
            margin-top: 24px;
        }
        
        .empty-state .bx {
            font-size: 64px;
            color: var(--dark-grey);
            margin-bottom: 16px;
        }
        
        .empty-state h3 {
            font-size: 18px;
            color: var(--dark);
            margin-bottom: 8px;
        }
        
        .empty-state p {
            color: var(--dark-grey);
            font-size: 14px;
        }
        
        /* Responsive */
        @media screen and (max-width: 768px) {
            .filter-grid {
                grid-template-columns: 1fr;
            }
            
            .filter-actions {
                justify-content: stretch;
            }
            
            .btn-filter {
                flex: 1;
                justify-content: center;
            }
            
            .activity-header {
                flex-direction: column;
                gap: 8px;
                align-items: flex-start;
            }
            
            .activity-time {
                text-align: left;
            }
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
                            <a href="dashboard.php">Dashboard</a>
                        </li>
                        <li><i class='bx bx-chevron-right'></i></li>
                        <li>
                            <a class="active" href="#">Activity Logs</a>
                        </li>
                    </ul>
                </div>
            </div>

            <br>

            <!-- Enhanced Filter Form -->
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
                
                <!-- Enhanced Pagination -->
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

    <!-- Enhanced Activity Details Modal -->
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
    <script>
        // View Activity Details
        const viewButtons = document.querySelectorAll('.view-details-btn');
        const modal = document.getElementById('activityDetailsModal');
        const modalContent = document.getElementById('modalActivityContent');
        
        viewButtons.forEach(button => {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                const activityId = this.dataset.activityId;
                
                // Show loading state
                modalContent.innerHTML = `
                    <div style="text-align: center; padding: 40px;">
                        <i class='bx bx-loader-circle bx-spin' style="font-size: 32px; color: var(--blue);"></i>
                        <p style="margin-top: 12px; color: var(--dark-grey);">Loading activity details...</p>
                    </div>
                `;
                
                // Show modal
                modal.style.display = 'block';
                document.body.style.overflow = 'hidden';
                
                // Fetch activity details via AJAX
                fetch(`get_activity_details.php?id=${activityId}`)
                    .then(response => {
                        if (!response.ok) {
                            throw new Error('Network response was not ok');
                        }
                        return response.text();
                    })
                    .then(data => {
                        modalContent.innerHTML = data;
                    })
                    .catch(error => {
                        modalContent.innerHTML = `
                            <div style="text-align: center; padding: 40px; color: var(--red);">
                                <i class='bx bx-error-circle' style="font-size: 32px;"></i>
                                <h4 style="margin: 12px 0 8px 0;">Error Loading Details</h4>
                                <p style="color: var(--dark-grey); font-size: 14px;">Please try again later.</p>
                            </div>
                        `;
                        console.error('Error:', error);
                    });
            });
        });
        
        // Close modal
        const closeButton = document.querySelector('.close-btn');
        closeButton.addEventListener('click', function() {
            modal.style.display = 'none';
            document.body.style.overflow = 'auto';
        });
        
        // Close when clicking outside modal
        window.addEventListener('click', function(event) {
            if (event.target === modal) {
                modal.style.display = 'none';
                document.body.style.overflow = 'auto';
            }
        });
        
        // Close with Escape key
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape' && modal.style.display === 'block') {
                modal.style.display = 'none';
                document.body.style.overflow = 'auto';
            }
        });
    </script>
</body>
</html>