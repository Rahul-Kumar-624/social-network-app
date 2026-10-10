<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['user_id']) || !isset($_POST['friend_id'])) {
    exit('unauthorized');
}

$user_id   = $_SESSION['user_id'];
$friend_id = intval($_POST['friend_id']);

// Dono users ke beech ka friendship / request relationship record remove karein
$stmt = $conn->prepare("DELETE FROM friend_requests WHERE (sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?)");
$stmt->bind_param("iiii", $user_id, $friend_id, $friend_id, $user_id);

if ($stmt->execute()) {
    echo 'success';
} else {
    echo 'error';
}

$stmt->close();
?>