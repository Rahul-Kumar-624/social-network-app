<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['user_id']) || !isset($_POST['receiver_id'])) {
    exit('unauthorized');
}

$sender_id   = $_SESSION['user_id'];
$receiver_id = intval($_POST['receiver_id']);
$message     = isset($_POST['message']) ? trim($_POST['message']) : '';

$message_type = 'text';
$file_path    = null;

// Ensure uploads/voice directory exists
$voice_dir = "../uploads/voice/";
if (!file_exists($voice_dir)) {
    mkdir($voice_dir, 0777, true);
}

// Audio Voice Recording
if (isset($_FILES['voice_note']) && $_FILES['voice_note']['error'] === 0) {
    $file = $_FILES['voice_note'];
    
    // File extension based on uploaded type
    $ext = 'webm';
    $new_name = "voice_" . time() . "_" . rand(1000, 9999) . "." . $ext;

    if (move_uploaded_file($file['tmp_name'], $voice_dir . $new_name)) {
        $message_type = 'audio';
        $file_path = $new_name;
    } else {
        exit('upload_error');
    }
} 
// File / Image Attachment
elseif (isset($_FILES['attachment']) && $_FILES['attachment']['error'] === 0) {
    $file     = $_FILES['attachment'];
    $fileName = $file['name'];
    $fileExt  = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

    $img_exts = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    $doc_exts = ['pdf', 'doc', 'docx', 'txt', 'zip'];

    if (in_array($fileExt, $img_exts)) {
        $message_type = 'image';
    } elseif (in_array($fileExt, $doc_exts)) {
        $message_type = 'file';
    } else {
        exit('invalid_file_type');
    }

    $upload_dir = "../uploads/attachments/";
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    $new_name = "file_" . time() . "_" . rand(1000, 9999) . "." . $fileExt;

    if (move_uploaded_file($file['tmp_name'], $upload_dir . $new_name)) {
        $file_path = $new_name;
    } else {
        exit('upload_error');
    }
}

if (!empty($message) || $file_path !== null) {
    $stmt = $conn->prepare("INSERT INTO messages (sender_id, receiver_id, message, message_type, file_path) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("iisss", $sender_id, $receiver_id, $message, $message_type, $file_path);

    if ($stmt->execute()) {
        echo 'success';
    } else {
        echo 'db_error: ' . $conn->error;
    }
    $stmt->close();
} else {
    echo 'empty_message';
}
?>