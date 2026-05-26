<?php
// site/public/view_event.php

session_start();
require_once '../includes/functions.php';
require_once '../includes/auth.php';

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Check if event ID is provided
if (!isset($_GET['id'])) {
    header("Location: index.php");
    exit();
}

$event = getEvent($_GET['id'], $_SESSION['user_id']);
if (!$event) {
    header("Location: index.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <title>View Event - <?php echo htmlspecialchars($event['title']); ?></title>
    <link rel="stylesheet" type="text/css" href="style.css">
</head>

<body>
    <div class="nav-links">
        <a href="index.php">Calendar</a>
        <a href="profile.php">Profile (<?php echo htmlspecialchars($_SESSION['username']); ?>)</a>
        <a href="logout.php">Logout</a>
    </div>

    <div class="container">
        <div class="event-details" style="animation: fadeIn 0.4s ease-out;">
            <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 24px;">
                <h2 style="border-bottom: none; padding-bottom: 0; margin-top: 0;">
                    <?php echo htmlspecialchars($event['title']); ?>
                </h2>
                <span class="event-category <?php echo htmlspecialchars($event['category'] ?? 'other'); ?>" style="font-size: 0.85rem; padding: 6px 12px;">
                    <?php echo htmlspecialchars($event['category'] ?? 'other'); ?>
                </span>
            </div>

            <p><strong>Date</strong> <?php echo htmlspecialchars($event['formatted_date']); ?></p>
            <p><strong>Time</strong> <?php echo htmlspecialchars($event['formatted_time']); ?></p>
            
            <?php if (!empty($event['description'])): ?>
                <p><strong>Description</strong> <?php echo nl2br(htmlspecialchars($event['description'])); ?></p>
            <?php else: ?>
                <p style="color: var(--text-dark);"><strong>Description</strong> <em>No description provided.</em></p>
            <?php endif; ?>

            <div style="margin-top: 32px; display: flex; gap: 12px;">
                <button class="edit-event" data-event-id="<?php echo $event['id']; ?>">Edit Event</button>
                <button class="delete-event" data-event-id="<?php echo $event['id']; ?>" data-csrf-token="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">Delete Event</button>
            </div>
        </div>
    </div>

    <div id="editEventModal" class="modal">
        <div class="modal-content">
            <span class="close-button">&times;</span>
            <h2>Edit Event</h2>
            <form id="editEventForm" class="form-grid">
                <input type="hidden" id="eventId" name="id">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">
                <input type="hidden" name="action" value="update">

                <div class="form-group">
                    <label for="editTitle">Title:</label>
                    <input type="text" id="editTitle" name="title" required>
                </div>

                <div class="form-group">
                    <label for="editDate">Date:</label>
                    <input type="date" id="editDate" name="date" required>
                </div>

                <div class="form-group">
                    <label for="editTime">Time:</label>
                    <input type="time" id="editTime" name="time" required>
                </div>

                <div class="form-group">
                    <label for="editCategory">Category:</label>
                    <select id="editCategory" name="category">
                        <option value="work">Work</option>
                        <option value="personal">Personal</option>
                        <option value="social">Social</option>
                        <option value="other">Other</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="editDescription">Description:</label>
                    <textarea id="editDescription" name="description"></textarea>
                </div>

                <div class="form-group" style="margin-top: 12px;">
                    <input type="submit" value="Save Changes">
                </div>
            </form>
        </div>
    </div>

    <script>
        // Edit Event Modal controls
        const editButton = document.querySelector('.edit-event');
        const editModal = document.getElementById('editEventModal');
        const editForm = document.getElementById('editEventForm');
        const closeModal = document.querySelector('#editEventModal .close-button');

        editButton.onclick = () => {
            // Populate form fields with current event data
            document.getElementById('eventId').value = editButton.dataset.eventId;
            document.getElementById('editTitle').value = <?php echo json_encode($event['title']); ?>;
            
            // Format date for date input (YYYY-MM-DD)
            const rawDate = new Date(<?php echo json_encode($event['formatted_date']); ?>);
            const formattedDate = rawDate.toISOString().split('T')[0];
            document.getElementById('editDate').value = formattedDate;
            
            // Format time for time input (HH:MM)
            const rawTime = <?php echo json_encode($event['formatted_time']); ?>; // e.g., "10:30 AM"
            let [timeStr, modifier] = rawTime.split(' ');
            let [hours, minutes] = timeStr.split(':');
            if (hours === '12') hours = '00';
            if (modifier === 'PM') hours = parseInt(hours, 10) + 12;
            const formattedTime = `${String(hours).padStart(2, '0')}:${minutes}`;
            document.getElementById('editTime').value = formattedTime;
            
            document.getElementById('editCategory').value = <?php echo json_encode($event['category'] ?? 'other'); ?>;
            document.getElementById('editDescription').value = <?php echo json_encode($event['description'] ?? ''); ?>;

            editModal.style.display = 'block';
        };

        closeModal.onclick = () => {
            editModal.style.display = 'none';
        };

        window.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') {
                editModal.style.display = 'none';
            }
        });

        editForm.addEventListener('submit', (event) => {
            event.preventDefault();
            const submitButton = event.target.querySelector('input[type="submit"]');
            submitButton.disabled = true;

            const formData = new FormData(event.target);

            fetch('api/events.php', {
                    method: 'POST', // Use POST with action=update to bypass PUT form-data parser limits
                    body: formData,
                    credentials: 'same-origin'
                })
                .then(response => {
                    if (!response.ok) throw new Error('Update failed');
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        editModal.style.display = 'none';
                        location.reload();
                    } else {
                        alert('Error editing event: ' + (data.message || 'Unknown error'));
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Failed to edit event. Please check details.');
                })
                .finally(() => {
                    submitButton.disabled = false;
                });
        });

        // Delete Event Flow
        const deleteButton = document.querySelector('.delete-event');
        deleteButton.addEventListener('click', function() {
            if (confirm('Are you sure you want to delete this event?')) {
                const eventId = this.dataset.eventId;
                const csrfToken = this.dataset.csrfToken;

                fetch(`api/events.php?id=${eventId}`, {
                        method: 'DELETE',
                        headers: {
                            'X-CSRF-Token': csrfToken
                        },
                        credentials: 'same-origin'
                    })
                    .then(response => {
                        if (!response.ok) throw new Error('Delete failed');
                        return response.json();
                    })
                    .then(result => {
                        if (result.success) {
                            alert('Event deleted successfully!');
                            window.location.href = 'index.php';
                        } else {
                            alert('Error deleting event');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('Failed to delete event.');
                    });
            }
        });
    </script>
</body>

</html>