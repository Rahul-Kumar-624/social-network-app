<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['user_id'])) {
    exit(json_encode([]));
}

$receiver_id = $_SESSION['user_id'];

// Har sender ke unread messages count karein
$query = "SELECT sender_id, COUNT(*) as unread_count 
          FROM messages 
          WHERE receiver_id = ? AND is_read = 0 
          GROUP BY sender_id";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $receiver_id);
$stmt->execute();
$result = $stmt->get_result();

$counts = [];
while ($row = $result->fetch_assoc()) {
    $counts[$row['sender_id']] = $row['unread_count'];
}

header('Content-Type: application/json');
echo json_encode($counts);
$stmt->close();
?>