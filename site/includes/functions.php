<?php
// site/includes/functions.php
require_once 'config.php';

// Function to sanitize user input
function sanitizeInput($input)
{
    global $conn;
    $input = trim($input);
    $input = stripslashes($input);
    $input = htmlspecialchars($input);
    $input = mysqli_real_escape_string($conn, $input);
    return $input;
}

// Fetch user's upcoming events
function getUpcomingEvents($userId)
{
    global $conn;
    $query = "
        SELECT 
            id,
            title,
            description,
            DATE_FORMAT(event_date, '%M %d, %Y') as formatted_date,
            TIME_FORMAT(event_time, '%h:%i %p') as formatted_time,
            category
        FROM events WHERE user_id = ? AND event_date >= NOW() ORDER BY event_date ASC
    ";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $events = [];
    while ($row = $result->fetch_assoc()) {
        $events[] = $row;
    }
    $stmt->close();
    return $events;
}

// Fetch user's past events
function getPastEvents($userId)
{
    global $conn;

    $stmt = $conn->prepare("
        SELECT 
            id,
            title,
            description,
            DATE_FORMAT(event_date, '%M %d, %Y') as formatted_date,
            TIME_FORMAT(event_time, '%h:%i %p') as formatted_time,
            category
        FROM events
        WHERE user_id = ? 
        AND event_date < CURRENT_DATE
        ORDER BY event_date DESC, event_time DESC
    ");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();

    $events = [];
    while ($row = $result->fetch_assoc()) {
        $events[] = $row;
    }

    return $events;
}

// Get user statistics
function getUserStats($userId)
{
    global $conn;

    $stats = [
        'total_events' => 0,
        'upcoming_events' => 0,
        'member_since' => '',
    ];

    // Get total events
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM events WHERE user_id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $stats['total_events'] = $result->fetch_assoc()['total'];

    // Get upcoming events count
    $stmt = $conn->prepare("
        SELECT COUNT(*) as upcoming 
        FROM events 
        WHERE user_id = ? AND event_date >= CURRENT_DATE
    ");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $stats['upcoming_events'] = $result->fetch_assoc()['upcoming'];

    // Get member since date
    $stmt = $conn->prepare("
        SELECT DATE_FORMAT(created_at, '%M %d, %Y') as joined_date 
        FROM users 
        WHERE id = ?
    ");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $stats['member_since'] = $result->fetch_assoc()['joined_date'];

    return $stats;
}

// Get event details
function getEvent($eventId, $userId)
{
    global $conn;

    $stmt = $conn->prepare("
        SELECT 
            id,
            title,
            description,
            DATE_FORMAT(event_date, '%M %d, %Y') as formatted_date,
            TIME_FORMAT(event_time, '%h:%i %p') as formatted_time,
            category
        FROM events
        WHERE id = ? AND user_id = ?
    ");
    $stmt->bind_param("ii", $eventId, $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $event = $result->fetch_assoc();
    $stmt->close();
    return $event;
}
