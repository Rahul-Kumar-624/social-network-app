<?php
session_start();
header('Content-Type: application/json');
require_once '../config/db.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode([]);
    exit();
}

$user_id = $_SESSION['user_id'];

$query = "SELECT fr.id, fr.status, u.full_name 
          FROM friend_requests fr 
          JOIN users u ON fr.receiver_id = u.id 
          WHERE fr.sender_id = ? AND fr.status IN ('accepted', 'rejected') AND fr.is_notified = 0
          ORDER BY fr.id DESC";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$notifications = [];
while ($row = $result->fetch_assoc()) {
    $notifications[] = [
        'id' => $row['id'],
        'name' => $row['full_name'],
        'status' => $row['status']
    ];
}

echo json_encode($notifications);
$stmt->close();
?>