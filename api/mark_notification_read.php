<?php
session_start();
require_once '../config/db.php';

if (isset($_POST['req_id'])) {
    $req_id = intval($_POST['req_id']);
    $stmt = $conn->prepare("UPDATE friend_requests SET is_notified = 1 WHERE id = ?");
    $stmt->bind_param("i", $req_id);
    if ($stmt->execute()) {
        echo 'success';
    }
    $stmt->close();
}
?>