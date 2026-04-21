<?php
// activity_actions.php
// Simple endpoints to add and list user activities for the dashboard
require_once 'config.php';

header('Content-Type: application/json; charset=utf-8');

try {
    $database = new Database();
    $db = $database->getConnection();
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'db_connection', 'message' => $e->getMessage()]);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? $_POST['action'] ?? '';

if ($method === 'POST' && $action === 'add') {
    // Add a new activity
    $user_id = $_POST['user_id'] ?? null;
    $activity_type = $_POST['activity_type'] ?? '';
    $activity_description = $_POST['activity_description'] ?? '';

    if (!$user_id) {
        echo json_encode(['success' => false, 'error' => 'missing_user']);
        exit;
    }

    try {
        $stmt = $db->prepare("INSERT INTO user_activities (user_id, activity_type, activity_description, activity_date) VALUES (?, ?, ?, NOW())");
        $stmt->execute([$user_id, $activity_type, $activity_description]);
        $activity_id = $db->lastInsertId();

        // Fetch the inserted row with a formatted date
        $stmt2 = $db->prepare("SELECT activity_id, user_id, activity_type, activity_description, activity_date, DATE_FORMAT(activity_date, '%b %d, %Y %l:%i %p') as nice_date FROM user_activities WHERE activity_id = ?");
        $stmt2->execute([$activity_id]);
        $activity = $stmt2->fetch(PDO::FETCH_ASSOC);

        echo json_encode(['success' => true, 'activity' => $activity]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => 'insert_failed', 'message' => $e->getMessage()]);
    }
    exit;
}

if ($method === 'GET' && ($action === 'list' || $action === 'recent')) {
    $user_id = $_GET['user_id'] ?? null;
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;

    if (!$user_id) {
        echo json_encode(['success' => false, 'error' => 'missing_user']);
        exit;
    }

    try {
        $stmt = $db->prepare("SELECT activity_id, user_id, activity_type, activity_description, activity_date, DATE_FORMAT(activity_date, '%b %d, %Y %l:%i %p') as nice_date FROM user_activities WHERE user_id = ? ORDER BY activity_date DESC LIMIT ?");
        $stmt->bindParam(1, $user_id, PDO::PARAM_INT);
        $stmt->bindParam(2, $limit, PDO::PARAM_INT);
        $stmt->execute();
        $activities = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode(['success' => true, 'activities' => $activities]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => 'query_failed', 'message' => $e->getMessage()]);
    }
    exit;
}

// Unknown action
echo json_encode(['success' => false, 'error' => 'unknown_action']);
