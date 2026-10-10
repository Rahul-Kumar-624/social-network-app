<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['user_id'])) {
    exit('unauthorized');
}

$user_id     = $_SESSION['user_id'];
$status_text = isset($_POST['status_text']) ? trim($_POST['status_text']) : null;
$media_path  = null;

// Handle Image Upload
if (isset($_FILES['status_media']) && $_FILES['status_media']['error'] === 0) {
    $file     = $_FILES['status_media'];
    $fileExt  = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowed  = ['jpg', 'jpeg', 'png', 'webp', 'gif'];

    if (in_array($fileExt, $allowed)) {
        $upload_dir = "../uploads/statuses/";
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        $new_name = "status_" . time() . "_" . rand(1000, 9999) . "." . $fileExt;
        if (move_uploaded_file($file['tmp_name'], $upload_dir . $new_name)) {
            $media_path = $new_name;
        }
    }
}

if ($status_text !== null || $media_path !== null) {
    $stmt = $conn->prepare("INSERT INTO statuses (user_id, status_text, media_path) VALUES (?, ?, ?)");
    $stmt->bind_param("iss", $user_id, $status_text, $media_path);

    if ($stmt->execute()) {
        echo 'success';
    } else {
        echo 'db_error';
    }
    $stmt->close();
} else {
    echo 'empty_status';
}
?>