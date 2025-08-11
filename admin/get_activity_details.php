<?php include 'functions/get.php'; ?>

<style>
    .activity-detail-container {
        font-family: var(--poppins);
    }
    
    .detail-header {
        display: flex;
        align-items: center;
        gap: 16px;
        padding: 20px;
        background: linear-gradient(135deg, <?php echo $actionColor; ?>15, <?php echo $actionColor; ?>05);
        border-radius: 12px;
        margin-bottom: 24px;
        border-left: 4px solid <?php echo $actionColor; ?>;
    }
    
    .action-icon {
        width: 60px;
        height: 60px;
        border-radius: 50%;
        background: <?php echo $actionColor; ?>;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 24px;
    }
    
    .header-info h3 {
        font-size: 20px;
        font-weight: 600;
        color: var(--dark);
        margin: 0 0 4px 0;
    }
    
    .header-info .meta {
        font-size: 14px;
        color: var(--dark-grey);
        display: flex;
        align-items: center;
        gap: 16px;
    }
    
    .meta-item {
        display: flex;
        align-items: center;
        gap: 4px;
    }
    
    .details-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 16px;
        margin-bottom: 24px;
    }
    
    .detail-card {
        background: var(--grey);
        padding: 16px;
        border-radius: 8px;
        border-left: 3px solid var(--blue);
    }
    
    .detail-card h4 {
        font-size: 12px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: var(--dark-grey);
        margin: 0 0 8px 0;
    }
    
    .detail-card .value {
        font-size: 14px;
        font-weight: 500;
        color: var(--dark);
    }
    
    .detail-card .bx {
        color: var(--blue);
        margin-right: 6px;
    }
    
    .changes-section {
        margin-top: 24px;
    }
    
    .section-title {
        font-size: 16px;
        font-weight: 600;
        color: var(--dark);
        margin-bottom: 16px;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    
    .section-title .bx {
        color: var(--blue);
    }
    
    .changes-container {
        display: grid;
        gap: 16px;
    }
    
    .changes-card {
        background: var(--light);
        border-radius: 8px;
        overflow: hidden;
        border: 1px solid var(--grey);
    }
    
    .changes-header {
        padding: 12px 16px;
        font-size: 14px;
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    
    .old-values .changes-header {
        background: #e74c3c15;
        color: #e74c3c;
        border-bottom: 1px solid #e74c3c20;
    }
    
    .new-values .changes-header {
        background: #27ae6015;
        color: #27ae60;
        border-bottom: 1px solid #27ae6020;
    }
    
    .changes-content {
        padding: 16px;
        max-height: 300px;
        overflow-y: auto;
    }
    
    .json-display {
        background: #f8f9fa;
        border: 1px solid #e9ecef;
        border-radius: 6px;
        padding: 12px;
        font-family: 'Courier New', monospace;
        font-size: 13px;
        line-height: 1.4;
        color: #495057;
        white-space: pre-wrap;
        word-wrap: break-word;
    }
    
    .key-value-pair {
        display: flex;
        margin-bottom: 8px;
        align-items: flex-start;
    }
    
    .key {
        font-weight: 600;
        color: var(--blue);
        min-width: 120px;
        margin-right: 12px;
    }
    
    .value {
        color: var(--dark);
        flex: 1;
        word-break: break-word;
    }
    
    .no-changes {
        text-align: center;
        padding: 40px 20px;
        color: var(--dark-grey);
    }
    
    .no-changes .bx {
        font-size: 48px;
        margin-bottom: 12px;
        opacity: 0.5;
    }
    
    .timestamp-badge {
        background: var(--blue);
        color: white;
        padding: 4px 8px;
        border-radius: 4px;
        font-size: 11px;
        font-weight: 500;
    }
    
    .table-badge {
        background: var(--orange);
        color: white;
        padding: 4px 8px;
        border-radius: 4px;
        font-size: 11px;
        font-weight: 500;
    }
</style>

<div class="activity-detail-container">
    <!-- Header Section -->
    <div class="detail-header">
        <div class="action-icon">
            <i class='bx <?php echo $actionIcon; ?>'></i>
        </div>
        <div class="header-info">
            <h3><?php echo strtoupper($activity['action']); ?> Activity</h3>
            <div class="meta">
                <div class="meta-item">
                    <i class='bx bx-user'></i>
                    <?php echo htmlspecialchars($activity['full_name']); ?> (@<?php echo htmlspecialchars($activity['username']); ?>)
                </div>
                <div class="meta-item">
                    <i class='bx bx-time'></i>
                    <?php echo date('M j, Y \a\t g:i A', strtotime($activity['action_time'])); ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Details Grid -->
    <div class="details-grid">
        <div class="detail-card">
            <h4><i class='bx bx-table'></i> Target Table</h4>
            <div class="value"><?php echo htmlspecialchars($activity['table_name']); ?></div>
        </div>
        
        <div class="detail-card">
            <h4><i class='bx bx-hash'></i> Record ID</h4>
            <div class="value">#<?php echo htmlspecialchars($activity['record_id']); ?></div>
        </div>
        
        <div class="detail-card">
            <h4><i class='bx bx-calendar'></i> Timestamp</h4>
            <div class="value">
                <span class="timestamp-badge">
                    <?php echo date('M j, Y g:i:s A', strtotime($activity['action_time'])); ?>
                </span>
            </div>
        </div>
        
        <div class="detail-card">
            <h4><i class='bx bx-user-circle'></i> Performed By</h4>
            <div class="value">
                <?php echo htmlspecialchars($activity['full_name']); ?>
                <br>
                <small style="color: var(--dark-grey);">@<?php echo htmlspecialchars($activity['username']); ?></small>
            </div>
        </div>
    </div>

    <!-- Changes Section -->
    <?php if ($old_values || $new_values): ?>
        <div class="changes-section">
            <h3 class="section-title">
                <i class='bx bx-git-compare'></i>
                Data Changes
            </h3>
            
            <div class="changes-container">
                <?php if ($old_values): ?>
                    <div class="changes-card old-values">
                        <div class="changes-header">
                            <i class='bx bx-minus-circle'></i>
                            Previous Values
                        </div>
                        <div class="changes-content">
                            <?php if (is_array($old_values) && !empty($old_values)): ?>
                                <?php foreach ($old_values as $key => $value): ?>
                                    <div class="key-value-pair">
                                        <div class="key"><?php echo htmlspecialchars($key); ?>:</div>
                                        <div class="value"><?php echo htmlspecialchars(is_array($value) ? json_encode($value) : $value); ?></div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="json-display"><?php echo htmlspecialchars(json_encode($old_values, JSON_PRETTY_PRINT)); ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
                
                <?php if ($new_values): ?>
                    <div class="changes-card new-values">
                        <div class="changes-header">
                            <i class='bx bx-plus-circle'></i>
                            New Values
                        </div>
                        <div class="changes-content">
                            <?php if (is_array($new_values) && !empty($new_values)): ?>
                                <?php foreach ($new_values as $key => $value): ?>
                                    <div class="key-value-pair">
                                        <div class="key"><?php echo htmlspecialchars($key); ?>:</div>
                                        <div class="value"><?php echo htmlspecialchars(is_array($value) ? json_encode($value) : $value); ?></div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="json-display"><?php echo htmlspecialchars(json_encode($new_values, JSON_PRETTY_PRINT)); ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php else: ?>
        <div class="changes-section">
            <h3 class="section-title">
                <i class='bx bx-git-compare'></i>
                Data Changes
            </h3>
            <div class="no-changes">
                <i class='bx bx-info-circle'></i>
                <p>No data changes recorded for this activity.</p>
                <small>This activity may not involve data modifications or change tracking was not enabled.</small>
            </div>
        </div>
    <?php endif; ?>

    <!-- Additional Context -->
    <?php if ($activity['action'] == 'LOGIN' || $activity['action'] == 'LOGOUT'): ?>
        <div style="margin-top: 24px; padding: 16px; background: var(--light-blue); border-radius: 8px; border-left: 4px solid var(--blue);">
            <h4 style="margin: 0 0 8px 0; color: var(--blue);">
                <i class='bx bx-info-circle'></i>
                Session Activity
            </h4>
            <p style="margin: 0; font-size: 14px; color: var(--dark);">
                This <?php echo strtolower($activity['action']); ?> activity was recorded for security and audit purposes. 
                <?php if ($activity['action'] == 'LOGIN'): ?>
                    The user successfully authenticated and started a new session.
                <?php else: ?>
                    The user ended their session and logged out of the system.
                <?php endif; ?>
            </p>
        </div>
    <?php endif; ?>
</div>
