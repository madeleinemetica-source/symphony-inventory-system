<?php
session_start();

function isLoggedIn() {
    return isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true;
}

function requireLogin() {
    if (!isLoggedIn()) {
        header("Location: ../login.php");
        exit();
    }
}

function getUserInfo() {
    if (isLoggedIn()) {
        return [
            'user_id' => $_SESSION['user_id'],
            'full_name' => $_SESSION['full_name'],
            'email' => $_SESSION['email'],
            'profile_picture' => $_SESSION['profile_picture']
        ];
    }
    return null;
}
?>