<?php
session_start();
require_once '../config/db.php';

// Expired media files fetch karein (Older than 24 Hours)
$select_stmt = $conn->prepare("SELECT media_path FROM statuses WHERE created_at < NOW() - INTERVAL 24 HOUR AND media_path IS NOT NULL");
$select_stmt->execute();
$result = $select_stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $file = "../uploads/statuses/" . $row['media_path'];
    if (file_exists($file)) {
        unlink($file); // File server se delete karein
    }
}
$select_stmt->close();

// Expired database rows cleanup
$conn->query("DELETE FROM statuses WHERE created_at < NOW() - INTERVAL 24 HOUR");
?>