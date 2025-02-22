<?php
// site/includes/auth.php

require_once 'config.php';

// Function to hash the password securely
function hashPassword($password)
{
    $salt = bin2hex(random_bytes(16)); // Generate a random salt
    $hash = hash('sha256', $salt . $password); // Hash the password with the salt
    return $salt . $hash; // Store both the salt and the hash
}

// Function to verify a password against a stored hash
function verifyPassword($password, $storedHash)
{
    $salt = substr($storedHash, 0, 32); // Extract the salt from the stored hash
    $hash = substr($storedHash, 32); // Extract the hash from the stored hash
    $testHash = hash('sha256', $salt . $password); // Hash the password with the same salt
    return $testHash === $hash; // Compare the hashes
}

// Function to register a new user
function registerUser($username, $password)
{
    global $conn;

    // Sanitize user input to prevent SQL injection
    $username = mysqli_real_escape_string($conn, $username);

    // Hash the password securely
    $hashedPassword = hashPassword($password);

    $sql = "INSERT INTO users (username, password, created_at) VALUES ('$username', '$hashedPassword', CURRENT_TIMESTAMP)";

    if (mysqli_query($conn, $sql)) {
        return true;
    } else {
        echo "Error: " . $sql . "<br>" . mysqli_error($conn);
        return false;
    }
}

// Function to log in a user
function loginUser($username, $password)
{
    global $conn;

    // Sanitize user input to prevent SQL injection
    $username = mysqli_real_escape_string($conn, $username);

    $sql = "SELECT * FROM users WHERE username = '$username'";
    $result = mysqli_query($conn, $sql);

    if (mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        $storedHash = $row['password'];

        if (verifyPassword($password, $storedHash)) {
            // Set session variables
            session_start();
            $_SESSION['user_id'] = $row['id'];
            $_SESSION['username'] = $row['username'];
            return true;
        } else {
            return false; // Incorrect password
        }
    } else {
        return false; // User not found
    }
}

// Function to log out a user
function logoutUser()
{
    session_start();
    session_unset();
    session_destroy();
}
