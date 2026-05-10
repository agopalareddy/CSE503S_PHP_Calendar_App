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
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <title>View Event - <?php echo htmlspecialchars($event['title']); ?></title>
    <link rel="stylesheet" type="text/css" href="style.css">
</head>

<body>
    <div class="container">
        <div class="nav-links">
            <a href="index.php">Calendar</a>
            <a href="profile.php"><?php echo htmlspecialchars($_SESSION['username']); ?></a>
            <a href="logout.php">Logout</a>
        </div>

        <h2><?php echo htmlspecialchars($event['title']); ?></h2>

        <div class="event-details">
            <p><strong>Date:</strong> <?php echo $event['formatted_date']; ?></p>
            <p><strong>Time:</strong> <?php echo $event['formatted_time']; ?></p>
            <?php if ($event['description']): ?>
                <p><strong>Description:</strong> <?php echo htmlspecialchars($event['description']); ?></p>
            <?php endif; ?>
            <p><strong>Category:</strong> <?php echo $event['category']; ?></p>

            <button class="edit-event" data-event-id="<?php echo $event['id']; ?>">Edit Event</button>
            <button class="delete-event" data-event-id="<?php echo $event['id']; ?>" data-csrf-token="<?php echo $_SESSION['csrf_token']; ?>">Delete Event</button>
        </div>
    </div>

    <div id="editEventModal" class="modal">
        <div class="modal-content">
            <span class="close-button">&times;</span>
            <h2>Edit Event</h2>
            <form id="editEventForm" class="form-grid">
                <input type="hidden" id="eventId" name="id">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">

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
                    <label for="editDescription">Description:</label>
                    <textarea id="editDescription" name="description"></textarea>
                </div>

                <div class="form-group">
                    <input type="submit" value="Save Changes">
                </div>
            </form>
        </div>
    </div>

    <script>
        // Edit Event
        const editButton = document.querySelector('.edit-event');
        const editModal = document.getElementById('editEventModal');
        const editForm = document.getElementById('editEventForm');
        const closeModal = document.querySelector('#editEventModal .close-button');

        editButton.onclick = () => {
            // Populate the form fields with event data
            document.getElementById('eventId').value = editButton.dataset.eventId;
            document.getElementById('editTitle').value = "<?php echo htmlspecialchars($event['title']); ?>";
            document.getElementById('editDate').value = "<?php echo $event['formatted_date']; ?>";
            document.getElementById('editTime').value = "<?php echo $event['formatted_time']; ?>";
            document.getElementById('editDescription').value = "<?php echo htmlspecialchars($event['description']); ?>";

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

            const formData = new FormData(event.target);

            fetch('api/events.php', {
                    method: 'PUT', // Or 'POST' with an '_method' field for PUT
                    body: formData,
                    credentials: 'same-origin'
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        editModal.style.display = 'none';
                        // Optionally update event details on the page dynamically
                        location.reload(); // Or update specific elements
                    } else {
                        alert('Error editing event: ' + data.message);
                    }
                })
                .catch(error => console.error('Error:', error));
        });

        // Delete Event
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
                    .then(response => response.json())
                    .then(result => {
                        if (result.success) {
                            alert('Event deleted successfully!');
                            window.location.href = 'index.php'; // Redirect to calendar
                        } else {
                            alert('Error deleting event');
                        }
                    })
                    .catch(error => console.error('Error:', error));
            }
        });
    </script>
</body>

</html>