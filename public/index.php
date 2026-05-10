<?php
// site/public/index.php

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require_once '../includes/functions.php';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <link rel="stylesheet" type="text/css" href="style.css">
    <title>Calendar App</title>
</head>

<body>
    <div class="nav-links">
        <?php if (isset($_SESSION['username'])): ?>
            <a href="profile.php"><?php echo htmlspecialchars($_SESSION['username'], ENT_QUOTES, 'UTF-8'); ?></a>
            <a href="logout.php">Logout</a>
        <?php else: ?>
            <a href="login.php">Login</a>
            <a href="register.php">Register</a>
        <?php endif; ?>
    </div>

    <div class="calendar">
        <div class="calendar-header">
            <button id="prevMonth">&lt; Previous</button>
            <h2 id="currentMonth"></h2>
            <button id="nextMonth">Next &gt;</button>
        </div>
        <div class="calendar-header">
            <button id="todayButton">Today</button>

            <div id="categoryFilter" style="display: flex; gap: 10px; align-items: center;">
                <label><input type="checkbox" name="category" value="work"> Work</label>
                <label><input type="checkbox" name="category" value="personal"> Personal</label>
                <label><input type="checkbox" name="category" value="social"> Social</label>
                <label><input type="checkbox" name="category" value="other"> Other</label>
            </div>
            <div class="picker-container">
                <select id="monthPicker"></select>
                <input type="number" id="yearPicker" placeholder="Enter year">
            </div>
        </div>

        <div class="calendar-grid" id="calendarGrid"></div>
    </div>

    <div id="addEventModal" class="modal">
        <div class="modal-content">
            <span class="close-button">&times;</span>
            <h2>Create Event</h2>
            <form id="addEventForm" class="form-grid">
                <input type="hidden" id="eventDate" name="date">

                <div class="form-group">
                    <label for="eventTitle">Title:</label>
                    <input type="text" id="eventTitle" name="title" required>
                </div>

                <div class="form-group">
                    <label for="eventTime">Time:</label>
                    <input type="time" id="eventTime" name="time" required>
                </div>

                <div class="form-group">
                    <label for="eventCategory">Category:</label>
                    <select id="eventCategory" name="category">
                        <option value="work">Work</option>
                        <option value="personal">Personal</option>
                        <option value="social">Social</option>
                        <option value="other">Other</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="eventDescription">Description:</label>
                    <textarea id="eventDescription" name="description"></textarea>
                </div>

                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">

                <div class="form-group">
                    <input type="submit" value="Create Event">
                </div>
            </form>
        </div>
    </div>

    <script>
        let currentDate = new Date();
        const monthNames = ["January", "February", "March", "April", "May", "June",
            "July", "August", "September", "October", "November", "December"
        ];

        function updateCalendarHeader() {
            document.getElementById('currentMonth').textContent =
                `${monthNames[currentDate.getMonth()]} ${currentDate.getFullYear()}`;
            // Keep month and year pickers in sync
            document.getElementById('monthPicker').value = currentDate.getMonth();
            document.getElementById('yearPicker').value = currentDate.getFullYear();
        }

        // Function to adjust date to the device's timezone
        function adjustToTimezone(date) {
            return new Date(date.getTime() - (date.getTimezoneOffset() * 60000));
        }

        function generateCalendar() {
            const grid = document.getElementById('calendarGrid');
            grid.innerHTML = '';

            // Add loading indicator
            grid.innerHTML = '<div class="loading">Loading calendar...</div>';

            const days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
            days.forEach(day => {
                const dayHeader = document.createElement('div');
                dayHeader.textContent = day;
                dayHeader.style.fontWeight = 'bold';
                dayHeader.style.textAlign = 'center';
                grid.appendChild(dayHeader);
            });

            const firstDay = new Date(currentDate.getFullYear(), currentDate.getMonth(), 1);

            for (let i = 0; i < firstDay.getDay(); i++) {
                grid.appendChild(document.createElement('div'));
            }

            const lastDay = new Date(currentDate.getFullYear(), currentDate.getMonth() + 1, 0);

            for (let day = 1; day <= lastDay.getDate(); day++) {
                const dayCell = document.createElement('div');
                dayCell.className = 'calendar-day';

                // Highlight current day
                const today = new Date();
                if (today.getDate() === day &&
                    today.getMonth() === currentDate.getMonth() &&
                    today.getFullYear() === currentDate.getFullYear()) {
                    dayCell.classList.add('current-day');
                }

                const dayNumber = document.createElement('div');
                dayNumber.className = 'day-number';
                dayNumber.textContent = day;
                dayCell.appendChild(dayNumber);

                <?php if (isset($_SESSION['user_id'])): ?>
                    const addButton = document.createElement('button');
                    addButton.className = 'add-event-button';
                    addButton.textContent = '+';
                    addButton.onclick = () => {
                        const selectedDate = new Date(currentDate.getFullYear(), currentDate.getMonth(), day);
                        document.getElementById('eventDate').value = selectedDate.toISOString().split('T')[0];
                        document.getElementById('addEventModal').style.display = 'block';
                        // Clear form when opening
                        document.getElementById('addEventForm').reset();
                    };
                    dayCell.appendChild(addButton);
                <?php endif; ?>

                grid.appendChild(dayCell);
            }

            // Remove loading indicator before fetching events
            const loadingDiv = grid.querySelector('.loading');
            if (loadingDiv) {
                loadingDiv.remove();
            }

            if (document.cookie.includes('PHPSESSID')) {
                fetchEvents();
            }
        }

        function fetchEvents() {
            const year = currentDate.getFullYear();
            const month = currentDate.getMonth() + 1;

            let categories = [];
            const checkboxes = document.querySelectorAll('#categoryFilter input[name="category"]:checked');
            checkboxes.forEach(checkbox => categories.push(checkbox.value));

            // Include categories in the fetch request
            fetch(`api/events.php?year=${year}&month=${month}&category=${categories.join(',')}`, {
                    credentials: 'same-origin'
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
                })
                .then(events => {
                    // Sort events by time
                    events.sort((a, b) => a.event_time.localeCompare(b.event_time));
                    displayEvents(events);
                })
                .catch(error => {
                    console.error('Error fetching events:', error);
                    // Show error message to user
                    const errorDiv = document.createElement('div');
                    errorDiv.className = 'error-message';
                    errorDiv.textContent = 'Failed to load events. Please try again later.';
                    document.querySelector('.calendar').prepend(errorDiv);

                    // Remove error message after 5 seconds
                    setTimeout(() => errorDiv.remove(), 5000);
                });
        }

        function displayEvents(events) {
            const selectedCategories = Array.from(document.querySelectorAll('#categoryFilter input[name="category"]:checked')).map(checkbox => checkbox.value);

            events.forEach(event => {
                if (selectedCategories.length === 0 || selectedCategories.includes(event.category)) {
                    const [year, month, day] = event.event_date.split('T')[0].split('-');
                    const dayOfMonth = parseInt(day);

                    const dayCells = document.querySelectorAll('.calendar-day');
                    dayCells.forEach(cell => {
                        const dayNumber = cell.querySelector('.day-number');
                        if (dayNumber && parseInt(dayNumber.textContent) === dayOfMonth) {
                            const eventDiv = document.createElement('div');
                            eventDiv.className = 'event ' + event.category;
                            eventDiv.textContent = `${event.title} (${event.event_time.slice(0, 5)})`;
                            eventDiv.onclick = (e) => {
                                e.stopPropagation();
                                window.location.href = `view_event.php?id=${encodeURIComponent(event.id)}`;
                            };
                            cell.appendChild(eventDiv);
                        }
                    });
                }
            });
        }

        // Add event with improved error handling
        document.getElementById('addEventForm').addEventListener('submit', (event) => {
            event.preventDefault();
            const submitButton = event.target.querySelector('input[type="submit"]');
            submitButton.disabled = true; // Prevent double submission

            const formData = new FormData(event.target);

            fetch('api/events.php', {
                    method: 'POST',
                    body: formData,
                    credentials: 'same-origin'
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById('addEventModal').style.display = 'none';
                        event.target.reset(); // Clear form
                        generateCalendar();
                    } else {
                        alert('Error creating event: ' + (data.message || 'Unknown error'));
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Failed to create event. Please try again.');
                })
                .finally(() => {
                    submitButton.disabled = false; // Re-enable submit button
                });
        });

        document.getElementById('prevMonth').addEventListener('click', () => {
            currentDate.setMonth(currentDate.getMonth() - 1);
            updateCalendarHeader();
            generateCalendar();
        });

        document.getElementById('nextMonth').addEventListener('click', () => {
            currentDate.setMonth(currentDate.getMonth() + 1);
            updateCalendarHeader();
            generateCalendar();
        });

        document.getElementById('todayButton').addEventListener('click', () => {
            currentDate = new Date(); // Reset to today's date
            updateCalendarHeader();
            generateCalendar();
        });

        document.querySelector('.close-button').addEventListener('click', () => {
            document.getElementById('addEventModal').style.display = 'none';
        });

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') {
                document.getElementById('addEventModal').style.display = 'none';
            }
        });

        // Populate month picker
        function populateMonthPicker() {
            const monthPicker = document.getElementById('monthPicker');
            monthNames.forEach((month, index) => {
                const option = document.createElement('option');
                option.value = index;
                option.textContent = month;
                monthPicker.appendChild(option);
            });
            monthPicker.value = currentDate.getMonth();
        }

        document.getElementById('monthPicker').addEventListener('change', (event) => {
            currentDate.setMonth(event.target.value);
            updateCalendarHeader();
            generateCalendar();
        });

        // Year picker validation and initialization
        const yearPicker = document.getElementById('yearPicker');
        yearPicker.value = currentDate.getFullYear(); // Initialize with current year
        yearPicker.addEventListener('change', (event) => {
            const year = parseInt(event.target.value);
            if (isNaN(year)) {
                alert('Please enter a valid year');
                event.target.value = currentDate.getFullYear();
                return;
            }
            currentDate.setFullYear(year);
            updateCalendarHeader();
            generateCalendar();
        });

        // Add event listener for category filter checkboxes
        document.querySelectorAll('#categoryFilter input[name="category"]').forEach(checkbox => {
            checkbox.addEventListener('change', generateCalendar);
        });

        populateMonthPicker();
        updateCalendarHeader();
        generateCalendar();
    </script>
</body>

</html>