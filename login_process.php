<?php
session_start();
include 'db.php';

$username = trim($_POST['username']);
$password = $_POST['password'];

$sql = "SELECT * FROM users WHERE username = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $username);
$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows == 1) {

    $user = $result->fetch_assoc();

    // Since you're using plain text during development
    if ($password === $user['password']) {

        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];

        header("Location: dashboard.php");
        exit();
    }
}

header("Location: login.php?error=1");
exit();