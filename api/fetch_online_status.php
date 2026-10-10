<?php
session_start();
header('Content-Type: application/json');
require_once '../config/db.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode([]);
    exit();
}

// Agar user pichhle 10 seconds me active tha, to usko online manenge
$query = "SELECT id, last_activity, 
          (last_activity >= NOW() - INTERVAL 10 SECOND) as is_online 
          FROM users";

$result = $conn->query($query);
$status_data = [];

while ($row = $result->fetch_assoc()) {
    $status_data[$row['id']] = [
        'is_online' => (bool)$row['is_online'],
        'last_seen' => date('h:i A', strtotime($row['last_activity']))
    ];
}

echo json_encode($status_data);
?>