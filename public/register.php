<?php
// site/public/register.php

require_once '../includes/auth.php';
require_once '../includes/functions.php';

// Check if the user is already logged in
session_start();
if (isset($_SESSION['username'])) {
    header("Location: index.php");
    exit();
}

// Generate CSRF token if not exists
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$error = ""; // Initialize error message variable

// Process registration form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = sanitizeInput($_POST["username"]);
    $password = $_POST["password"]; // Don't sanitize before hashing
    $confirm_password = $_POST["confirm_password"];

    // Basic validation
    if (empty($username) || empty($password) || empty($confirm_password)) {
        $error = "Please fill in all fields.";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match.";
    } else {
        // Attempt to register the user
        if (registerUser($username, $password)) {
            header("Location: login.php"); // Redirect to login after successful registration
            exit();
        } else {
            $error = "Registration failed. Please try again."; // Generic error message for now
        }
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <title>Register</title>
    <link rel="stylesheet" href="css/style.css">
</head>

<body>
    <p><a href="index.php">Home</a></p>

    <h1>Register</h1>

    <?php if ($error): ?>
        <p class="error"><?php echo $error; ?></p>
    <?php endif; ?>

    <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
        <label for="username">Username:</label>
        <input type="text" id="username" name="username" required><br><br>

        <label for="password">Password:</label>
        <input type="password" id="password" name="password" required><br><br>

        <label for="confirm_password">Confirm Password:</label>
        <input type="password" id="confirm_password" name="confirm_password" required><br><br>

        <input type="submit" value="Register">
    </form>

    <p>Already have an account? <a href="login.php">Login here</a>.</p>

</body>

</html>