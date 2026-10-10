<?php
session_start();
header('Content-Type: application/json');
require_once '../config/db.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode([]);
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch active statuses from self & accepted friends (pichhle 24 ghante ke)
$query = "SELECT s.id, s.user_id, s.status_text, s.media_path, s.created_at, u.full_name, u.profile_pic
          FROM statuses s
          JOIN users u ON s.user_id = u.id
          WHERE s.created_at >= NOW() - INTERVAL 24 HOUR
            AND (s.user_id = ? OR s.user_id IN (
                SELECT CASE 
                    WHEN sender_id = ? THEN receiver_id 
                    ELSE sender_id 
                END 
                FROM friend_requests 
                WHERE (sender_id = ? OR receiver_id = ?) AND status = 'accepted'
            ))
          ORDER BY s.created_at DESC";

$stmt = $conn->prepare($query);
$stmt->bind_param("iiii", $user_id, $user_id, $user_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

$statuses = [];
while ($row = $result->fetch_assoc()) {
    $statuses[] = [
        'id'          => $row['id'],
        'user_id'     => $row['user_id'],
        'name'        => $row['full_name'],
        'profile_pic' => $row['profile_pic'],
        'text'        => $row['status_text'],
        'media'       => $row['media_path'],
        'time'        => date('h:i A', strtotime($row['created_at']))
    ];
}

echo json_encode($statuses);
$stmt->close();
?>