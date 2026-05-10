<?php
// site/public/profile.php

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

$upcomingEvents = getUpcomingEvents($_SESSION['user_id']);
$userStats = getUserStats($_SESSION['user_id']);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <link rel="stylesheet" type="text/css" href="style.css">
    <title>Profile - <?php echo htmlspecialchars($_SESSION['username']); ?></title>
</head>

<body>
    <div class="container">
        <div class="nav-links">
            <a href="index.php">Calendar</a>
            <a href="past_events.php">Past Events</a>
            <a href="logout.php">Logout</a>
        </div>

        <h1>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?>!</h1>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?php echo $userStats['total_events']; ?></div>
                <div>Total Events</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $userStats['upcoming_events']; ?></div>
                <div>Upcoming Events</div>
            </div>
            <div class="stat-card">
                <div class="stat-number">
                    <span style="font-size: 0.7em">Joined</span>
                </div>
                <div><?php echo $userStats['member_since']; ?></div>
            </div>
        </div>
        <div id="categoryFilter">
            <h3>Categories</h3>
            <label><input type="checkbox" name="category" value="work"> Work</label>
            <label><input type="checkbox" name="category" value="personal"> Personal</label>
            <label><input type="checkbox" name="category" value="social"> Social</label>
        </div>

        <h2>Upcoming Events</h2>
        <div class="events-list">
            <?php if (empty($upcomingEvents)): ?>
                <p>No upcoming events scheduled.</p>
            <?php else: ?>
                <?php foreach ($upcomingEvents as $event): ?>
                    <div class="event-item">
                        <h3><a href="view_event.php?id=<?php echo $event['id']; ?>"><?php echo htmlspecialchars($event['title']); ?></a></h3>
                        <div class="event-date">
                            <?php echo htmlspecialchars($event['formatted_date'], ENT_QUOTES, 'UTF-8'); ?> at <?php echo htmlspecialchars($event['formatted_time'], ENT_QUOTES, 'UTF-8'); ?>
                        </div>
                        <div class="event-category">
                            Category: <?php echo htmlspecialchars($event['category'] ?? 'N/A', ENT_QUOTES, 'UTF-8'); ?>
                        </div>
                        <?php if ($event['description']): ?>
                            <p><?php echo htmlspecialchars($event['description']); ?></p>
                        <?php endif; ?>
                        <button class="delete-button"
                            data-event-id="<?php echo $event['id']; ?>"
                            data-csrf-token="<?php echo $_SESSION['csrf_token']; ?>">
                            Delete Event
                        </button>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Handle event deletion
        document.querySelectorAll('.delete-button').forEach(button => {
            button.addEventListener('click', function() {
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

                                // Update stats
                                const totalEvents = document.querySelectorAll('.stat-number')[0];
                                const upcomingEvents = document.querySelectorAll('.stat-number')[1];
                                totalEvents.textContent = parseInt(totalEvents.textContent) - 1;
                                upcomingEvents.textContent = parseInt(upcomingEvents.textContent) - 1;

                                // Show "No upcoming events" message if needed
                                if (document.querySelectorAll('.event-item').length === 0) {
                                    document.querySelector('.events-list').innerHTML =
                                        '<p>No upcoming events scheduled.</p>';
                                }
                            } else {
                                alert('Error deleting event');
                            }
                        })
                        .catch(error => console.error('Error:', error));
                }
            });
        });
        const categoryFilter = document.getElementById('categoryFilter');
        categoryFilter.addEventListener('change', () => {

            const selectedCategories = [];
            const checkboxes = categoryFilter.querySelectorAll('input[name="category"]:checked');
            checkboxes.forEach(checkbox => selectedCategories.push(checkbox.value));

            const events = document.querySelectorAll('#eventList .event');
            events.forEach(event => {
                if (selectedCategories.length === 0 || selectedCategories.includes(event.classList[1])) {
                    event.style.display = 'block';
                } else {
                    event.style.display = 'none';
                }
            });
        });
    </script>
</body>

</html>