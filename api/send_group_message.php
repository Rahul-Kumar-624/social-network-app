<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['user_id']) || !isset($_POST['group_id'])) {
    exit('unauthorized');
}

$sender_id = $_SESSION['user_id'];
$group_id  = intval($_POST['group_id']);
$message   = isset($_POST['message']) ? trim($_POST['message']) : '';

// Member verification
$mem_check = $conn->prepare("SELECT id FROM group_members WHERE group_id = ? AND user_id = ?");
$mem_check->bind_param("ii", $group_id, $sender_id);
$mem_check->execute();
if ($mem_check->get_result()->num_rows === 0) exit('not_a_member');
$mem_check->close();

$message_type = 'text';
$file_path    = null;

// Voice Note Check
if (isset($_FILES['voice_note']) && $_FILES['voice_note']['error'] === 0) {
    $voice_dir = "../uploads/voice/";
    if (!file_exists($voice_dir)) mkdir($voice_dir, 0777, true);

    $new_name = "group_voice_" . time() . "_" . rand(1000, 9999) . ".webm";
    if (move_uploaded_file($_FILES['voice_note']['tmp_name'], $voice_dir . $new_name)) {
        $message_type = 'audio';
        $file_path    = $new_name;
    }
} 
// Attachment Check
elseif (isset($_FILES['attachment']) && $_FILES['attachment']['error'] === 0) {
    $file     = $_FILES['attachment'];
    $fileExt  = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $img_exts = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    $doc_exts = ['pdf', 'doc', 'docx', 'txt', 'zip'];

    if (in_array($fileExt, $img_exts)) $message_type = 'image';
    elseif (in_array($fileExt, $doc_exts)) $message_type = 'file';
    else exit('invalid_file_type');

    $upload_dir = "../uploads/attachments/";
    if (!file_exists($upload_dir)) mkdir($upload_dir, 0777, true);

    $new_name = "group_file_" . time() . "_" . rand(1000, 9999) . "." . $fileExt;
    if (move_uploaded_file($file['tmp_name'], $upload_dir . $new_name)) {
        $file_path = $new_name;
    }
}

if (!empty($message) || $file_path !== null) {
    $stmt = $conn->prepare("INSERT INTO group_messages (group_id, sender_id, message, message_type, file_path) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("iisss", $group_id, $sender_id, $message, $message_type, $file_path);
    if ($stmt->execute()) echo 'success';
    else echo 'db_error';
    $stmt->close();
} else {
    echo 'empty';
}
?>