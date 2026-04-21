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

    // Get form data
    $user->full_name = $_POST['full_name'];
    $user->email = $_POST['email'];
    $user->password_hash = $_POST['password'];

    // Validate password strength (server-side)
    $passwordErrors = validatePasswordStrength($user->password_hash);
    if (!empty($passwordErrors)) {
        $_SESSION['error'] = implode(", ", $passwordErrors);
        header("Location: signup.php");
        exit();
    }

    // Handle file upload
    if(isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === 0) {
        $uploadDir = 'uploads/profiles/';
        if(!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $fileName = uniqid() . '_' . $_FILES['profile_picture']['name'];
        $uploadFile = $uploadDir . $fileName;

        if(move_uploaded_file($_FILES['profile_picture']['tmp_name'], $uploadFile)) {
            $user->profile_picture = $uploadFile;
        }
    }

    // Validate
    if(empty($user->full_name) || empty($user->email) || empty($user->password_hash)) {
        $_SESSION['error'] = "All fields are required";
        header("Location: signup.php");
        exit();
    }

    if(!filter_var($user->email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['error'] = "Invalid email format";
        header("Location: signup.php");
        exit();
    }

    // Check if email exists
    if($user->emailExists()) {
        $_SESSION['error'] = "Email already exists";
        header("Location: signup.php");
        exit();
    }

    // Create user
    if($user->create()) {
        $_SESSION['success'] = "Registration successful! Please login.";
        header("Location: login.php");
        exit();
    } else {
        $_SESSION['error'] = "Registration failed";
        header("Location: signup.php");
        exit();
    }
} else {
    header("Location: signup.php");
    exit();
}
?>