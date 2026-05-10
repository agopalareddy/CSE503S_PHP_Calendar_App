<?php
// site/public/past_events.php

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once '../includes/functions.php';
require_once '../includes/auth.php';

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Generate CSRF token if not exists
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$pastEvents = getPastEvents($_SESSION['user_id']);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <link rel="stylesheet" type="text/css" href="style.css">
    <title>Past Events - <?php echo htmlspecialchars($_SESSION['username']); ?></title>
</head>

<body>
    <div class="container">
        <div class="nav-links">
            <a href="index.php">Calendar</a>
            <a href="profile.php">Profile</a>
            <a href="logout.php">Logout</a>
        </div>

        <h1>Past Events</h1>

        <?php if (!empty($pastEvents)): ?>
            <form id="deleteAllPastEventsForm">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                <button type="submit" class="delete-button">Delete All Past Events</button>
            </form>
        <?php endif; ?>

        <div class="events-list">
            <?php if (empty($pastEvents)): ?>
                <p>No past events found.</p>
            <?php else: ?>
                <?php foreach ($pastEvents as $event): ?>
                    <div class="event-item">
                        <div>
                            <h3><a href="view_event.php?id=<?php echo htmlspecialchars($event['id'], ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($event['title'], ENT_QUOTES, 'UTF-8'); ?></a></h3>
                            <div class="event-date">
                                <?php echo htmlspecialchars($event['formatted_date'], ENT_QUOTES, 'UTF-8'); ?> at <?php echo htmlspecialchars($event['formatted_time'], ENT_QUOTES, 'UTF-8'); ?>
                            </div>
                            <?php if ($event['description']): ?>
                                <p><?php echo htmlspecialchars($event['description'], ENT_QUOTES, 'UTF-8'); ?></p>
                            <?php endif; ?>
                        </div>
                        <form class="deleteEventForm" data-event-id="<?php echo $event['id']; ?>">
                            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                            <button type="submit" class="delete-button">Delete</button>
                        </form>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Handle delete all past events
        const deleteAllForm = document.getElementById('deleteAllPastEventsForm');
        if (deleteAllForm) {
            deleteAllForm.addEventListener('submit', function(event) {
                event.preventDefault();

                if (confirm('Are you sure you want to delete ALL past events?')) {
                    const csrfToken = this.elements['csrf_token'].value;

                    fetch('api/events.php?delete_all_past=1', {
                            method: 'DELETE',
                            headers: {
                                'X-CSRF-Token': csrfToken
                            },
                            credentials: 'same-origin'
                        })
                        .then(response => response.json())
                        .then(result => {
                            if (result.success) {
                                location.reload();
                            } else {
                                alert('Error deleting past events');
                            }
                        })
                        .catch(error => console.error('Error:', error));
                }
            });
        }

        // Handle delete individual event
        // Handle event deletion
        const deleteEventForms = document.querySelectorAll('.deleteEventForm');
        deleteEventForms.forEach(form => {
            form.addEventListener('submit', function(event) {
                event.preventDefault();
                if (confirm('Are you sure you want to delete this event?')) {
                    const eventId = this.getAttribute('data-event-id');
                    const csrfToken = this.getAttribute('data-csrf-token');

                    fetch(`api/events.php?id=${eventId}`, {
                            method: 'DELETE',
                            headers: {
                                'X-CSRF-Token': csrfToken
                            },
                            credentials: 'same-origin'
                        })
                        .then(response => response.json())
                        .then(result => {
                            if (result.success) {
                                // Remove the event from the DOM
                                this.closest('.event-item').remove();
                                location.reload();
                            } else {
                                alert('Error deleting event');
                            }
                        })
                        .catch(error => console.error('Error:', error));
                }
            });
        });
    </script>
</body>

</html>