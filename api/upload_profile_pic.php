<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['user_id'])) {
    exit('unauthorized');
}

$user_id = $_SESSION['user_id'];

if (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] === 0) {
    $file = $_FILES['profile_pic'];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowed = ['jpg', 'jpeg', 'png', 'webp'];

    if (in_array($ext, $allowed)) {
        // Unique filename create karein
        $new_name = "profile_" . $user_id . "_" . time() . "." . $ext;
        $upload_path = "../uploads/profiles/" . $new_name;

        if (move_uploaded_file($file['tmp_name'], $upload_path)) {
            // Database me image path update karein
            $stmt = $conn->prepare("UPDATE users SET profile_pic = ? WHERE id = ?");
            $stmt->bind_param("si", $new_name, $user_id);
            if ($stmt->execute()) {
                echo 'success';
            } else {
                echo 'db_error';
            }
            $stmt->close();
        } else {
            echo 'upload_failed';
        }
    } else {
        echo 'invalid_format';
    }
} else {
    echo 'no_file';
}
?>