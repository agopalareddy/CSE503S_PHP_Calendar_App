<?php
// site/api/events.php
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

// Verify CSRF token for POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Invalid CSRF token']);
        exit();
    }
}

// Handle GET requests (fetch events)
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $year = isset($_GET['year']) ? intval($_GET['year']) : date('Y');
    $month = isset($_GET['month']) ? intval($_GET['month']) : date('m');
    $category = isset($_GET['category']) ? $_GET['category'] : null;
    $sql = "SELECT id, title, description, event_date, TIME_FORMAT(event_time, '%H:%i') as event_time, category
    FROM events
    WHERE user_id = ? 
    AND YEAR(event_date) = ? 
    AND MONTH(event_date) = ?";

    if ($category) {
        $sql .= " AND category = ?";
    }

    $sql .= " ORDER BY event_date, event_time";

    $stmt = $conn->prepare($sql);
    $stmt = $conn->prepare("
        SELECT id, title, description, event_date, TIME_FORMAT(event_time, '%H:%i') as event_time, category
        FROM events
        WHERE user_id = ? 
        AND YEAR(event_date) = ? 
        AND MONTH(event_date) = ?
        ORDER BY event_date, event_time
    ");

    $stmt->bind_param("iii", $_SESSION['user_id'], $year, $month);
    $stmt->execute();
    $result = $stmt->get_result();

    $events = [];
    while ($row = $result->fetch_assoc()) {
        $events[] = $row;
    }

    header('Content-Type: application/json');
    echo json_encode($events);
}

// Handle POST requests (create event)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Prepare an SQL statement to insert a new event into the database
    $stmt = $conn->prepare("
    INSERT INTO events (user_id, title, description, event_date, event_time, category)
    VALUES (?, ?, ?, ?, ?, ?)
    ");
    $user_id = $_SESSION['user_id'];
    $title = sanitizeInput($_POST['title']);
    $description = sanitizeInput($_POST['description']);
    $date = $_POST['date'];
    $time = $_POST['time'];
    $category = sanitizeInput($_POST['category']);

    // Bind the parameters to the SQL query; i=integer s=string
    $stmt->bind_param(
        "isssss",
        $user_id,
        $title,
        $description,
        $date,
        $time,
        $category
    );
    if ($stmt->execute()) {
        $response = ['success' => true, 'id' => $stmt->insert_id];
    } else {
        $response = ['success' => false, 'message' => 'Error creating event'];
    }
    // Set the response header to JSON
    header('Content-Type: application/json');

    // Output the response as a JSON string
    echo json_encode($response);
    exit();
}

// Handle DELETE requests (delete event or delete all past events)
if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    // Check if the request is to delete all past events
    if (isset($_GET['delete_all_past']) && $_GET['delete_all_past'] == 1) {
        $stmt = $conn->prepare("
            DELETE FROM events 
            WHERE user_id = ? AND event_date < CURRENT_DATE
        ");

        $user_id = $_SESSION['user_id'];
        $stmt->bind_param("i", $user_id);

        if ($stmt->execute()) {
            header('Content-Type: application/json');
            echo json_encode(['success' => true]);
        } else {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Error deleting past events']);
        }
    } else {
        // Handle single event deletion
        $event_id = intval($_GET['id']);

        $stmt = $conn->prepare("
            DELETE FROM events 
            WHERE id = ? AND user_id = ?
        ");

        $user_id = $_SESSION['user_id'];
        $stmt->bind_param("ii", $event_id, $user_id);

        if ($stmt->execute()) {
            header('Content-Type: application/json');
            echo json_encode(['success' => true]);
        } else {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Error deleting event']);
        }
    }
}

// Handle PUT requests (update event)
if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    // Get the event ID from the request
    $event_id = intval($_GET['id']);

    // Prepare the SQL statement
    $stmt = $conn->prepare("UPDATE events
    SET title = ?, description = ?, event_date = ?, event_time = ?, category = ?
    WHERE id = ? AND user_id = ?");

    // Bind the parameters
    $title = sanitizeInput($_PUT['title']);
    $description = sanitizeInput($_PUT['description']);
    $date = $_PUT['date'];
    $time = $_PUT['time'];
    $category = sanitizeInput($_PUT['category']);
    $stmt->bind_param("sssssii", $title, $description, $date, $time, $category, $event_id, $_SESSION['user_id']);

    // Execute the statement and send the response
    if ($stmt->execute()) {
        header('Content-Type: application/json');
        echo json_encode(['success' => true]);
    } else {
        header('Content-Type: application/json');
        echo  json_encode([
            'success' => false,
            'message' => 'Error updating event'
        ]);
    }
}

// fetch one event
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['id'])) {
    $event_id = intval($_GET['id']);
    $stmt = $conn->prepare("
        SELECT id, title, description, event_date, event_time, category
        FROM events
        WHERE id = ? AND user_id = ?
    ");
    $stmt->bind_param("ii", $event_id, $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $event = $result->fetch_assoc();
    if ($event) {
        header('Content-Type: application/json');
        echo json_encode($event);
    } else {
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Event not found']);
    }
}
