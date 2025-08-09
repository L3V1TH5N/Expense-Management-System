<?php
    require_once '../includes/config.php';
    require_once '../includes/db.php';
    require_once '../includes/auth.php';
    require_once '../includes/functions.php';

    requireLogin();

    $user_id = $_SESSION['user_id'];

    $success = '';
    $error = '';

    if (isset($_GET['success'])) {
        $success = $_GET['success'];
    }
    if (isset($_GET['error'])) {
        $error = $_GET['error'];
    }

    $search = $_GET['search'] ?? '';
    $expense_type_filter = $_GET['expense_type'] ?? '';
    $date_from = $_GET['date_from'] ?? '';
    $date_to = $_GET['date_to'] ?? '';
    $sort_by = $_GET['sort_by'] ?? 'created_at';
    $sort_order = $_GET['sort_order'] ?? 'DESC';
    $page = max(1, (int)($_GET['page'] ?? 1));
    $per_page = 20;
    $offset = ($page - 1) * $per_page;

    $where_conditions = ['created_by = ?'];
    $params = [$user_id];

    if (!empty($search)) {
        $where_conditions[] = "(payee LIKE ? OR check_number LIKE ? OR expense_type LIKE ?)";
        $search_param = "%{$search}%";
        $params[] = $search_param;
        $params[] = $search_param;
        $params[] = $search_param;
    }

    if (!empty($expense_type_filter)) {
        $where_conditions[] = "expense_type = ?";
        $params[] = $expense_type_filter;
    }

    if (!empty($date_from)) {
        $where_conditions[] = "date >= ?";
        $params[] = $date_from;
    }

    if (!empty($date_to)) {
        $where_conditions[] = "date <= ?";
        $params[] = $date_to;
    }

    $where_clause = implode(' AND ', $where_conditions);

    $valid_sort_columns = ['date', 'payee', 'expense_type', 'total', 'created_at'];
    $sort_by = in_array($sort_by, $valid_sort_columns) ? $sort_by : 'created_at';
    $sort_order = strtoupper($sort_order) === 'ASC' ? 'ASC' : 'DESC';
    $count_sql = "SELECT COUNT(*) FROM expenses WHERE {$where_clause}";
    $count_stmt = $conn->prepare($count_sql);
    $count_stmt->execute($params);
    $total_records = $count_stmt->fetchColumn();
    $total_pages = ceil($total_records / $per_page);
    $sql = "
        SELECT e.*, o.name as office_name, so.name as sub_office_name 
        FROM expenses e 
        LEFT JOIN offices o ON e.office_id = o.id 
        LEFT JOIN offices so ON e.sub_office_id = so.id 
        WHERE {$where_clause}
        ORDER BY e.{$sort_by} {$sort_order}
        LIMIT {$per_page} OFFSET {$offset}
    ";

    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $expenses = $stmt->fetchAll(PDO::FETCH_ASSOC);

    try {
        $offices_stmt = $conn->prepare("SELECT id, name, parent_id FROM offices ORDER BY parent_id, name");
        $offices_stmt->execute();
        $all_offices = $offices_stmt->fetchAll(PDO::FETCH_ASSOC);
        $offices_by_parent = [];
        foreach ($all_offices as $office) {
            $parent_id = $office['parent_id'] ?? 0;
            if (!isset($offices_by_parent[$parent_id])) {
                $offices_by_parent[$parent_id] = [];
            }
            $offices_by_parent[$parent_id][] = $office;
        }
        
        $main_offices = [];
        if (isset($offices_by_parent[0])) {
            $main_offices = array_merge($main_offices, $offices_by_parent[0]);
        }
        if (isset($offices_by_parent[''])) {
            $main_offices = array_merge($main_offices, $offices_by_parent['']);
        }
        
        foreach ($all_offices as $office) {
            if (is_null($office['parent_id'])) {
                $main_offices[] = $office;
            }
        }

        $unique_offices = [];
        $seen_ids = [];
        foreach ($main_offices as $office) {
            if (!in_array($office['id'], $seen_ids)) {
                $unique_offices[] = $office;
                $seen_ids[] = $office['id'];
            }
        }
        $main_offices = $unique_offices;

        usort($main_offices, function($a, $b) {
            return strcmp($a['name'], $b['name']);
        });

        error_log("Total offices loaded: " . count($all_offices));
        error_log("Main offices count: " . count($main_offices));
        foreach ($main_offices as $office) {
            error_log("Main office: ID={$office['id']}, Name={$office['name']}, Parent={$office['parent_id']}");
        }
        
    } catch (PDOException $e) {
        error_log("Error loading offices: " . $e->getMessage());
        $main_offices = [];
    }


    function buildPaginationUrl($page) {
        $params = $_GET;
        $params['page'] = $page;
        return '?' . http_build_query($params);
    }
?>
