<?php
// site/includes/config.php
// Load environment variables from .env file
$env = parse_ini_file(dirname(__DIR__) . '/.env');

// Database credentials from environment variables
$servername = $env['DB_SERVER'] ?? 'localhost';
$username = $env['DB_USERNAME'] ?? '';
$password = $env['DB_PASSWORD'] ?? '';
$dbname = $env['DB_NAME'] ?? 'calendar';

// Create connection
$conn = mysqli_connect($servername, $username, $password, $dbname);

// Check connection
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}