<?php
session_start();
include_once 'config.php';
include_once 'user.php';

// Password validation function (server-side)
function validatePasswordStrength($password) {
    $errors = [];
    
    // Check minimum length
    if (strlen($password) < 10) {
        $errors[] = "Password must be at least 10 characters";
    }
    
    // Check for alphanumeric (letters and numbers)
    if (!preg_match('/[a-zA-Z]/', $password) || !preg_match('/[0-9]/', $password)) {
        $errors[] = "Password must contain both letters and numbers";
    }
    
    // Check for special character
    if (!preg_match('/[!@#$%^&*()_\-=+\[\]{};:\'\",.\/\<>?\\|`~]/', $password)) {
        $errors[] = "Password must contain at least 1 special character";
    }
    
    return $errors;
}

if($_POST) {
    $database = new Database();
    $db = $database->getConnection();
    $user = new User($db);

    $user->email = $_POST['email'];
    $password = $_POST['password'];

    // Validate password strength (server-side)
    $passwordErrors = validatePasswordStrength($password);
    if (!empty($passwordErrors)) {
        $_SESSION['error'] = implode(", ", $passwordErrors);
        header("Location: login.php");
        exit();
    }

    // Basic validation
    if(empty($user->email) || empty($password)) {
        $_SESSION['error'] = "Email and password are required";
        header("Location: login.php");
        exit();
    }

    // Check if email exists
    if($user->emailExists() && password_verify($password, $user->password_hash)) {
        // Update last login
        $user->updateLastLogin();
        
        // refresh user data to get the updated last login
        $user->emailExists(); // re-fetch user data including last login

        // Set session variables
        $_SESSION['user_id'] = $user->user_id;
        $_SESSION['full_name'] = $user->full_name;
        $_SESSION['email'] = $user->email;
        $_SESSION['profile_picture'] = $user->profile_picture;
        $_SESSION['last_login'] = $user->last_login;
        $_SESSION['loggedin'] = true;

        $_SESSION['success'] = "Login successful!";
        header("Location: dashboard.php");
        exit();
    } else {
        $_SESSION['error'] = "Invalid email or password";
        header("Location: login.php");
        exit();
    }
} else {
    header("Location: login.php");
    exit();
}
?>