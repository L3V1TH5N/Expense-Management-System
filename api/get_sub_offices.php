<?php
    require_once '../includes/config.php';
    require_once '../includes/db.php';
    require_once '../includes/auth.php';

    // Set JSON content type
    header('Content-Type: application/json');

    // Check if user is logged in
    if (!isLoggedIn()) {
        http_response_code(401);
        echo json_encode(['error' => 'Unauthorized']);
        exit;
    }

    // Validate office_id parameter
    if (!isset($_GET['office_id']) || empty($_GET['office_id'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Office ID is required']);
        exit;
    }

    $office_id = (int)$_GET['office_id'];

    try {
        // Debug: Log the request
        error_log("Getting sub-offices for office_id: " . $office_id);
        
        // Get sub-offices for the given parent office
        $stmt = $conn->prepare("SELECT id, name FROM offices WHERE parent_id = ? ORDER BY name");
        $stmt->execute([$office_id]);
        $sub_offices = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Debug: Log the results
        error_log("Found " . count($sub_offices) . " sub-offices for office_id: " . $office_id);
        foreach ($sub_offices as $office) {
            error_log("Sub-office: ID=" . $office['id'] . ", Name=" . $office['name']);
        }
        
        // Return the sub-offices as JSON (simple array format)
        echo json_encode($sub_offices);
        
    } catch (PDOException $e) {
        error_log("Error fetching sub-offices: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Database error occurred']);
    }
?>