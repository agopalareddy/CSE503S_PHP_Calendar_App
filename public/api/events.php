<?php
// site/public/api/events.php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once '../../includes/functions.php';
require_once '../../includes/auth.php';

// Ensure user is logged in
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

// Function to validate CSRF token
function validateCSRF() {
    $token = null;
    if (isset($_POST['csrf_token'])) {
        $token = $_POST['csrf_token'];
    } elseif (isset($_SERVER['HTTP_X_CSRF_TOKEN'])) {
        $token = $_SERVER['HTTP_X_CSRF_TOKEN'];
    }
    
    if (!$token || !isset($_SESSION['csrf_token']) || $token !== $_SESSION['csrf_token']) {
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Invalid CSRF token']);
        exit();
    }
}

// Handle GET requests
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    header('Content-Type: application/json');
    
    if (isset($_GET['id'])) {
        // Fetch single event details
        $event_id = intval($_GET['id']);
        $stmt = $conn->prepare("
            SELECT id, title, description, event_date, TIME_FORMAT(event_time, '%H:%i') as event_time, category
            FROM events
            WHERE id = ? AND user_id = ?
        ");
        $stmt->bind_param("ii", $event_id, $_SESSION['user_id']);
        $stmt->execute();
        $result = $stmt->get_result();
        $event = $result->fetch_assoc();
        
        if ($event) {
            echo json_encode($event);
        } else {
            echo json_encode(['error' => 'Event not found']);
        }
        $stmt->close();
        exit();
    } else {
        // Fetch list of events for the selected month/year
        $year = isset($_GET['year']) ? intval($_GET['year']) : date('Y');
        $month = isset($_GET['month']) ? intval($_GET['month']) : date('m');
        $category = isset($_GET['category']) ? $_GET['category'] : null;
        
        $sql = "SELECT id, title, description, event_date, TIME_FORMAT(event_time, '%H:%i') as event_time, category
                FROM events
                WHERE user_id = ? 
                AND YEAR(event_date) = ? 
                AND MONTH(event_date) = ?";
        
        $categories = [];
        if ($category && trim($category) !== '') {
            $categories = array_filter(explode(',', $category), function($val) {
                return trim($val) !== '';
            });
        }
        
        if (!empty($categories)) {
            $placeholders = implode(',', array_fill(0, count($categories), '?'));
            $sql .= " AND category IN ($placeholders)";
        }
        
        $sql .= " ORDER BY event_date, event_time";
        
        $stmt = $conn->prepare($sql);
        
        if (!empty($categories)) {
            $types = "iii" . str_repeat("s", count($categories));
            $params = array_merge([$_SESSION['user_id'], $year, $month], $categories);
            $stmt->bind_param($types, ...$params);
        } else {
            $stmt->bind_param("iii", $_SESSION['user_id'], $year, $month);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        
        $events = [];
        while ($row = $result->fetch_assoc()) {
            $events[] = $row;
        }
        
        echo json_encode($events);
        $stmt->close();
        exit();
    }
}

// Handle POST requests (Create OR Update via action parameter)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validateCSRF();
    header('Content-Type: application/json');
    
    $action = $_POST['action'] ?? 'create';
    
    if ($action === 'update' || isset($_POST['id'])) {
        // Update Event Flow
        $event_id = intval($_POST['id']);
        $title = sanitizeInput($_POST['title']);
        $description = sanitizeInput($_POST['description']);
        $date = $_POST['date'];
        $time = $_POST['time'];
        $category = sanitizeInput($_POST['category'] ?? 'other');
        
        $stmt = $conn->prepare("
            UPDATE events
            SET title = ?, description = ?, event_date = ?, event_time = ?, category = ?
            WHERE id = ? AND user_id = ?
        ");
        $stmt->bind_param("sssssii", $title, $description, $date, $time, $category, $event_id, $_SESSION['user_id']);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error updating event']);
        }
        $stmt->close();
        exit();
    } else {
        // Create Event Flow
        $title = sanitizeInput($_POST['title']);
        $description = sanitizeInput($_POST['description']);
        $date = $_POST['date'];
        $time = $_POST['time'];
        $category = sanitizeInput($_POST['category'] ?? 'other');
        
        $stmt = $conn->prepare("
            INSERT INTO events (user_id, title, description, event_date, event_time, category)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->bind_param("isssss", $_SESSION['user_id'], $title, $description, $date, $time, $category);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'id' => $stmt->insert_id]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error creating event']);
        }
        $stmt->close();
        exit();
    }
}

// Handle DELETE requests
if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    validateCSRF();
    header('Content-Type: application/json');
    
    if (isset($_GET['delete_all_past']) && $_GET['delete_all_past'] == 1) {
        $stmt = $conn->prepare("
            DELETE FROM events 
            WHERE user_id = ? AND event_date < CURRENT_DATE
        ");
        $stmt->bind_param("i", $_SESSION['user_id']);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error deleting past events']);
        }
        $stmt->close();
        exit();
    } else {
        $event_id = intval($_GET['id']);
        $stmt = $conn->prepare("
            DELETE FROM events 
            WHERE id = ? AND user_id = ?
        ");
        $stmt->bind_param("ii", $event_id, $_SESSION['user_id']);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error deleting event']);
        }
        $stmt->close();
        exit();
    }
}

// Support fallback raw PUT updates (e.g. if front-end can't POST with action=update)
if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    validateCSRF();
    header('Content-Type: application/json');
    
    // Parse form-urlencoded PUT body manually if needed
    parse_str(file_get_contents("php://input"), $_PUT);
    
    $event_id = isset($_GET['id']) ? intval($_GET['id']) : intval($_PUT['id'] ?? 0);
    $title = sanitizeInput($_PUT['title'] ?? '');
    $description = sanitizeInput($_PUT['description'] ?? '');
    $date = $_PUT['date'] ?? '';
    $time = $_PUT['time'] ?? '';
    $category = sanitizeInput($_PUT['category'] ?? 'other');
    
    if ($event_id > 0) {
        $stmt = $conn->prepare("
            UPDATE events
            SET title = ?, description = ?, event_date = ?, event_time = ?, category = ?
            WHERE id = ? AND user_id = ?
        ");
        $stmt->bind_param("sssssii", $title, $description, $date, $time, $category, $event_id, $_SESSION['user_id']);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error updating event']);
        }
        $stmt->close();
    } else {
        echo json_encode(['success' => false, 'message' => 'Missing event ID']);
    }
    exit();
}
