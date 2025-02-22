# PHP Calendar Application

## Overview

This is a personal calendar and event management application built using PHP and MySQL. It features user authentication, event management, and a responsive calendar interface. It was developed as part of Washington University in St. Louis's CSE 503S: Rapid Prototyping and Creative Programming course.

## Features

- User authentication (register/login)
- Interactive calendar interface
- Event management (create/edit/delete)
- Event categorization (work/personal/social/other)
- Past events tracking
- User statistics
- Responsive design with CSS
- CSRF protection
- Secure password hashing

## Repository Structure

### Core Files
- [site/includes/auth.php](site/includes/auth.php) - Authentication functions
- [site/includes/functions.php](site/includes/functions.php) - Core functionality
- [site/includes/database.php](site/includes/database.php) - Database connection handler
- [site/public/index.php](site/public/index.php) - Main calendar interface
- [site/public/login.php](site/public/login.php) - User login page
- [site/public/register.php](site/public/register.php) - User registration page
- [site/public/logout.php](site/public/logout.php) - Logout handler
- [site/public/api/events.php](site/public/api/events.php) - Event management API
- [site/public/api/auth.php](site/public/api/auth.php) - Authentication API endpoints
- [site/public/js/calendar.js](site/public/js/calendar.js) - Calendar JavaScript functionality
- [site/public/js/events.js](site/public/js/events.js) - Event management JavaScript
- [site/public/css/style.css](site/public/css/style.css) - Main application styling
- [site/public/css/calendar.css](site/public/css/calendar.css) - Calendar-specific styles

### Configuration
- [site/includes/config.php](site/includes/config.php) - Database configuration
- [site/.gitignore](site/.gitignore) - Git ignore rules

## Getting Started

1. **Set up a MySQL database** and update the credentials in [site/includes/config.php](site/includes/config.php)

2. **Create the required tables**:
```sql
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(255) UNIQUE,
    password VARCHAR(255),
    created_at TIMESTAMP
);

CREATE TABLE events (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    title VARCHAR(255),
    description TEXT,
    event_date DATE,
    event_time TIME,
    category VARCHAR(50),
    FOREIGN KEY (user_id) REFERENCES users(id)
);
```

3. **Configure your web server** to serve from the public directory

4. **Open your browser** and navigate to the application URL

## Usage

- Register for an account or login
- View your calendar and manage events
- Create new events with titles, descriptions, dates, times, and categories
- View past events and user statistics
- Delete individual events or all past events
- Filter events by category

## Technologies

- PHP
- MySQL
- HTML5/CSS3
- JavaScript

## Security Features

- Password hashing with salt
- CSRF protection
- SQL injection prevention
- Input sanitization
- Secure session management

## Acknowledgments

Developed for CSE 503S at Washington University in St. Louis.