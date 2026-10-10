<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['user_id'])) {
    exit('unauthorized');
}

$user_id    = $_SESSION['user_id'];
$action     = isset($_POST['action']) ? $_POST['action'] : '';
$message_id = isset($_POST['message_id']) ? intval($_POST['message_id']) : 0;

if ($message_id <= 0) {
    exit('invalid_id');
}

if ($action === 'delete') {
    // Sirf sender apne message ko delete kar sakta hai
    $stmt = $conn->prepare("UPDATE messages SET is_deleted = 1, message = 'This message was deleted' WHERE id = ? AND sender_id = ?");
    $stmt->bind_param("ii", $message_id, $user_id);
    if ($stmt->execute() && $stmt->affected_rows > 0) {
        echo 'success';
    } else {
        echo 'unauthorized_or_failed';
    }
    $stmt->close();
} elseif ($action === 'edit') {
    $new_text = isset($_POST['message']) ? trim($_POST['message']) : '';
    if (empty($new_text)) {
        exit('empty_message');
    }

    $stmt = $conn->prepare("UPDATE messages SET message = ?, is_edited = 1 WHERE id = ? AND sender_id = ? AND is_deleted = 0");
    $stmt->bind_param("sii", $new_text, $message_id, $user_id);
    if ($stmt->execute() && $stmt->affected_rows > 0) {
        echo 'success';
    } else {
        echo 'failed';
    }
    $stmt->close();
}
?>