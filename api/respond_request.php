<?php
session_start();
require_once '../config/db.php';

if (isset($_POST['request_id']) && isset($_POST['status'])) {
    $req_id = intval($_POST['request_id']);
    $status = $_POST['status'];

    // Update status and reset is_notified to 0 so the sender gets alerted
    $stmt = $conn->prepare("UPDATE friend_requests SET status = ?, is_notified = 0 WHERE id = ?");
    $stmt->bind_param("si", $status, $req_id);
    if ($stmt->execute()) {
        echo 'success';
    } else {
        echo 'error';
    }
    $stmt->close();
}
?>