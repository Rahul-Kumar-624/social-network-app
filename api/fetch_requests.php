<?php
session_start();
require_once '../config/db.php';

$user_id = $_SESSION['user_id'];

$query = "SELECT fr.id as req_id, u.full_name, u.email 
          FROM friend_requests fr 
          JOIN users u ON fr.sender_id = u.id 
          WHERE fr.receiver_id = ? AND fr.status = 'pending'";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        echo '<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px; padding: 8px; border-bottom: 1px solid #eee;">';
        echo '<div><strong>' . htmlspecialchars($row['full_name']) . '</strong><br><small>' . htmlspecialchars($row['email']) . '</small></div>';
        echo '<div>';
        echo '<button onclick="respondRequest(' . $row['req_id'] . ', \'accepted\')" style="background: #22c55e; color: white; border: none; padding: 5px 10px; border-radius: 4px; margin-right: 5px; cursor: pointer;">Accept</button>';
        echo '<button onclick="respondRequest(' . $row['req_id'] . ', \'rejected\')" style="background: #ef4444; color: white; border: none; padding: 5px 10px; border-radius: 4px; cursor: pointer;">Reject</button>';
        echo '</div>';
        echo '</div>';
    }
} else {
    echo '<p style="color: #64748b;">No pending friend requests.</p>';
}
?>