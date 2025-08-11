<?php include 'functions/manage.php'; ?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href='https://unpkg.com/boxicons@2.0.9/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="../assets/css/style.css">
    <title>Manage Users - Expense Management System</title>
    <link rel="stylesheet" href="functions/css/manage.css">
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
            <li class="active">
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
                    <ul class="breadcrumb">
                        <li>
                            <a href="dashboard.php">Home</a>
                        </li>
                        <li><i class='bx bx-chevron-right'></i></li>
                        <li>
                            <a class="active" href="#">Manage Users</a>
                        </li>
                    </ul>
                </div>
            </div>

            <br>

            <!-- Alert Messages -->
            <?php if ($success_message): ?>
                <div class="alert alert-success fade-in">
                    <i class='bx bxs-check-circle'></i>
                    <?php echo htmlspecialchars($success_message); ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger fade-in">
                    <i class='bx bxs-error-circle'></i>
                    <div>
                        <?php foreach ($errors as $error): ?>
                            <p style="margin: 4px 0;"><?php echo htmlspecialchars($error); ?></p>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- User Statistics -->
            <div class="user-stats fade-in">
                <div class="stat-card total-users">
                    <i class='bx bxs-group'></i>
                    <span class="text">
                        <h3><?php echo number_format($total_users); ?></h3>
                        <p>Total System Users</p>
                    </span>
                </div>

                <div class="stat-card total-admins">
                    <i class='bx bxs-shield-alt-2'></i>
                    <span class="text">
                        <h3><?php echo number_format($total_admins); ?></h3>
                        <p>Administrators</p>
                    </span>
                </div>

                <div class="stat-card total-encoders">
                    <i class='bx bxs-user-check'></i>
                    <span class="text">
                        <h3><?php echo number_format($total_encoders); ?></h3>
                        <p>Encoders</p>
                    </span>
                </div>
            </div>

            <!-- Add New User Form -->
            <div class="user-form-container slide-in">
                <div class="form-header">
                    <i class='bx bxs-user-plus'></i>
                    <h3>Add New User</h3>
                </div>
                <form method="POST" action="manage_users.php" id="addUserForm">
                    <input type="hidden" name="action" value="add_user">
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="username">
                                <i class='bx bxs-user'></i> Username
                            </label>
                            <input type="text" id="username" name="username" required 
                                   placeholder="Enter unique username" 
                                   data-tooltip="Username must be unique in the system">
                        </div>
                        <div class="form-group">
                            <label for="password">
                                <i class='bx bxs-lock'></i> Password
                            </label>
                            <input type="password" id="password" name="password" required 
                                   placeholder="Enter secure password"
                                   data-tooltip="Use a strong password with at least 8 characters">
                        </div>
                        <div class="form-group">
                            <label for="full_name">
                                <i class='bx bxs-id-card'></i> Full Name
                            </label>
                            <input type="text" id="full_name" name="full_name" required 
                                   placeholder="Enter complete full name">
                        </div>
                        <div class="form-group">
                            <label for="role">
                                <i class='bx bxs-user-badge'></i> Role
                            </label>
                            <select id="role" name="role" required>
                                <option value="">Select user role</option>
                                <option value="admin">Administrator</option>
                                <option value="encoder">Encoder</option>
                            </select>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary">
                        <i class='bx bxs-user-plus'></i>
                        Create User Account
                    </button>
                </form>
            </div>

            <!-- Users Table -->
            <div class="users-table-container slide-in">
                <div class="table-header">
                    <h3>
                        <i class='bx bxs-group'></i>
                        System Users (<?php echo count($users); ?>)
                    </h3>
                </div>
                
                <?php if (!empty($users)): ?>
                <div style="overflow-x: auto;">
                    <table class="user-table">
                        <thead>
                            <tr>
                                <th><i class='bx bxs-user'></i> User Information</th>
                                <th><i class='bx bxs-user-badge'></i> Role</th>
                                <th><i class='bx bxs-data'></i> Statistics</th>
                                <th><i class='bx bxs-time'></i> Last Activity</th>
                                <th><i class='bx bxs-cog'></i> Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user): ?>
                            <tr>
                                <td>
                                    <div class="user-info">
                                        <div class="user-avatar">
                                            <?php echo strtoupper(substr($user['full_name'], 0, 2)); ?>
                                        </div>
                                        <div class="user-details">
                                            <h4><?php echo htmlspecialchars($user['full_name']); ?></h4>
                                            <p>@<?php echo htmlspecialchars($user['username']); ?></p>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge badge-<?php echo $user['role']; ?>">
                                        <i class='bx <?php echo $user['role'] === 'admin' ? 'bxs-shield-alt-2' : 'bxs-user'; ?>'></i>
                                        <?php echo ucfirst($user['role']); ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="user-stats-mini">
                                        <span class="stat">
                                            <strong><?php echo number_format($user['expense_count']); ?></strong> expenses
                                        </span>
                                        <span class="stat">
                                            <strong>₱<?php echo number_format($user['total_amount'], 2); ?></strong> total
                                        </span>
                                    </div>
                                </td>
                                <td>
                                    <?php if ($user['last_activity']): ?>
                                        <span style="font-size: 13px; color: var(--dark-grey);">
                                            <?php echo date('M j, Y', strtotime($user['last_activity'])); ?>
                                        </span>
                                    <?php else: ?>
                                        <span style="font-size: 13px; color: var(--dark-grey);">
                                            Never
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <button class="btn btn-info edit-user" 
                                            data-user-id="<?php echo $user['id']; ?>"
                                            data-username="<?php echo htmlspecialchars($user['username']); ?>"
                                            data-full-name="<?php echo htmlspecialchars($user['full_name']); ?>"
                                            data-role="<?php echo $user['role']; ?>"
                                            data-tooltip="Edit user information">
                                            <i class='bx bxs-edit'></i>
                                        </button>
                                        <button class="btn btn-warning reset-password" 
                                            data-user-id="<?php echo $user['id']; ?>"
                                            data-full-name="<?php echo htmlspecialchars($user['full_name']); ?>"
                                            data-tooltip="Reset user password">
                                            <i class='bx bxs-key'></i>
                                        </button>
                                        <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                        <button class="btn btn-danger delete-user" 
                                            data-user-id="<?php echo $user['id']; ?>"
                                            data-username="<?php echo htmlspecialchars($user['username']); ?>"
                                            data-tooltip="Delete user account">
                                            <i class='bx bxs-trash'></i>
                                        </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <div class="empty-state">
                    <i class='bx bxs-user-x'></i>
                    <h3>No Users Found</h3>
                    <p>There are no users in the system yet. Create the first user account above.</p>
                </div>
                <?php endif; ?>
            </div>
        </main>
        <!-- MAIN -->
    </section>
    <!-- CONTENT -->

    <!-- Edit User Modal -->
    <div id="editUserModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">
                    <i class='bx bxs-edit'></i>
                    Edit User Account
                </h3>
                <span class="close">&times;</span>
            </div>
            <div class="modal-body">
                <form id="editUserForm" method="POST" action="manage_users.php">
                    <input type="hidden" name="action" value="edit_user">
                    <input type="hidden" id="edit_user_id" name="user_id">
                    <div class="form-group">
                        <label for="edit_username">
                            <i class='bx bxs-user'></i> Username
                        </label>
                        <input type="text" id="edit_username" name="username" required>
                    </div>
                    <div class="form-group">
                        <label for="edit_full_name">
                            <i class='bx bxs-id-card'></i> Full Name
                        </label>
                        <input type="text" id="edit_full_name" name="full_name" required>
                    </div>
                    <div class="form-group">
                        <label for="edit_role">
                            <i class='bx bxs-user-badge'></i> Role
                        </label>
                        <select id="edit_role" name="role" required>
                            <option value="admin">Administrator</option>
                            <option value="encoder">Encoder</option>
                        </select>
                    </div>
                    <div class="modal-actions">
                        <button type="button" class="btn btn-secondary close-modal">
                            <i class='bx bx-x'></i>
                            Cancel
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <i class='bx bxs-save'></i>
                            Save Changes
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Reset Password Modal -->
    <div id="resetPasswordModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">
                    <i class='bx bxs-key'></i>
                    Reset User Password
                </h3>
                <span class="close">&times;</span>
            </div>
            <div class="modal-body">
                <form id="resetPasswordForm" method="POST" action="manage_users.php">
                    <input type="hidden" name="action" value="reset_password">
                    <input type="hidden" id="reset_user_id" name="user_id">
                    <input type="hidden" id="reset_full_name" name="full_name">
                    <div class="form-group">
                        <label for="new_password">
                            <i class='bx bxs-lock'></i> New Password
                        </label>
                        <input type="password" id="new_password" name="new_password" required 
                               placeholder="Enter new secure password">
                    </div>
                    <div style="background: #fff3cd; padding: 12px; border-radius: 8px; margin-bottom: 20px; font-size: 14px; color: #856404;">
                        <strong>Note:</strong> The user will need to use this new password to log in. Make sure to communicate it securely.
                    </div>
                    <div class="modal-actions">
                        <button type="button" class="btn btn-secondary close-modal">
                            <i class='bx bx-x'></i>
                            Cancel
                        </button>
                        <button type="submit" class="btn btn-warning">
                            <i class='bx bxs-key'></i>
                            Reset Password
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete User Modal -->
    <div id="deleteUserModal" class="modal">
        <div class="modal-content">
            <div class="modal-header" style="background: var(--gradient-danger);">
                <h3 class="modal-title">
                    <i class='bx bxs-trash'></i>
                    Delete User Account
                </h3>
                <span class="close">&times;</span>
            </div>
            <div class="modal-body">
                <form id="deleteUserForm" method="POST" action="manage_users.php">
                    <input type="hidden" name="action" value="delete_user">
                    <input type="hidden" id="delete_user_id" name="user_id">
                    
                    <div style="text-align: center; margin-bottom: 24px;">
                        <i class='bx bxs-error-circle' style="font-size: 64px; color: var(--red); margin-bottom: 16px;"></i>
                        <h3 style="color: var(--dark); margin-bottom: 8px;">Are you absolutely sure?</h3>
                        <p style="color: var(--dark-grey); margin-bottom: 16px;">
                            You are about to delete user account: <strong id="delete_username_display"></strong>
                        </p>
                        <div style="background: #f8d7da; padding: 12px; border-radius: 8px; color: #721c24; font-size: 14px;">
                            <strong>Warning:</strong> This action cannot be undone. All user data and associated records will remain but the user will no longer be able to access the system.
                        </div>
                    </div>
                    
                    <div class="modal-actions">
                        <button type="button" class="btn btn-secondary close-modal">
                            <i class='bx bx-x'></i>
                            Cancel
                        </button>
                        <button type="submit" class="btn btn-danger">
                            <i class='bx bxs-trash'></i>
                            Delete User
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="../assets/js/script.js"></script>
    <script src="functions/js/manage.js"></script>
</body>
</html>
