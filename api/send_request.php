<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['user_id']) || !isset($_POST['receiver_id'])) {
    exit('unauthorized');
}

$sender_id   = $_SESSION['user_id'];
$receiver_id = intval($_POST['receiver_id']);

if ($sender_id === $receiver_id) {
    exit('self_request');
}

// Check existing request
$check_query = "SELECT id, status FROM friend_requests 
                WHERE (sender_id = ? AND receiver_id = ?) 
                   OR (sender_id = ? AND receiver_id = ?)";
$check_stmt = $conn->prepare($check_query);
$check_stmt->bind_param("iiii", $sender_id, $receiver_id, $receiver_id, $sender_id);
$check_stmt->execute();
$result = $check_stmt->get_result();

if ($result->num_rows > 0) {
    echo 'already_sent';
} else {
    // Check if is_notified column exists or insert simple query
    $insert_query = "INSERT INTO friend_requests (sender_id, receiver_id, status) VALUES (?, ?, 'pending')";
    $insert_stmt = $conn->prepare($insert_query);
    $insert_stmt->bind_param("ii", $sender_id, $receiver_id);
    if ($insert_stmt->execute()) {
        echo 'success';
    } else {
        echo 'db_error: ' . $conn->error;
    }
    $insert_stmt->close();
}
$check_stmt->close();
?>