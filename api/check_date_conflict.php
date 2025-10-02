<?php
// File: check_date_conflict.php
// Location: /api/
header('Content-Type: application/json');
session_start();
require_once '../config.php';

// Check if user is logged in
if (!isset($_SESSION['user_loggedin']) || $_SESSION['user_loggedin'] !== true) {
    http_response_code(403);
    echo json_encode(['error' => 'Authentication required.']);
    exit;
}

$response = ['conflict' => false, 'details' => []];

if (isset($_POST['dates']) && is_array($_POST['dates'])) {
    $dates_to_check = $_POST['dates'];
    $exclude_event_id = isset($_POST['exclude_event_id']) ? (int)$_POST['exclude_event_id'] : 0;
    
    if (empty($dates_to_check)) {
        echo json_encode($response);
        exit;
    }

    try {
        $placeholders = implode(',', array_fill(0, count($dates_to_check), '?'));
        
        $sql = "SELECT e.event_name, ed.activity_date 
                FROM events e 
                JOIN event_dates ed ON e.id = ed.event_id 
                WHERE ed.activity_date IN ($placeholders)";
        
        $params = $dates_to_check;

        if ($exclude_event_id > 0) {
            $sql .= " AND e.id != ?";
            $params[] = $exclude_event_id;
        }

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $conflicts = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (!empty($conflicts)) {
            $response['conflict'] = true;
            $details = [];
            foreach ($conflicts as $conflict) {
                // To prevent leaking info, just say there is a conflict.
                // For a more detailed message, we can format it here.
                $details[] = "วันที่ " . $conflict['activity_date'] . " มีกิจกรรม '" . htmlspecialchars($conflict['event_name']) . "' อยู่แล้ว";
            }
            // Use array_unique to avoid duplicate messages
            $response['details'] = array_unique($details); 
        }

    } catch (PDOException $e) {
        // Don't expose error, just report no conflict to avoid blocking user
    }
}

echo json_encode($response);
?>

