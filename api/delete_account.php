<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['user_id'])) {
    exit('unauthorized');
}

$user_id = $_SESSION['user_id'];

// Database transaction shuru karein taaki user ka saara data safe tarike se remove ho sake
$conn->begin_transaction();

try {
    // 1. User ke saare direct messages delete karein
    $stmt1 = $conn->prepare("DELETE FROM messages WHERE sender_id = ? OR receiver_id = ?");
    $stmt1->bind_param("ii", $user_id, $user_id);
    $stmt1->execute();
    $stmt1->close();

    // 2. User ke friend requests delete karein
    $stmt2 = $conn->prepare("DELETE FROM friend_requests WHERE sender_id = ? OR receiver_id = ?");
    $stmt2->bind_param("ii", $user_id, $user_id);
    $stmt2->execute();
    $stmt2->close();

    // 3. Statuses remove karein
    $stmt3 = $conn->prepare("DELETE FROM statuses WHERE user_id = ?");
    $stmt3->bind_param("i", $user_id);
    $stmt3->execute();
    $stmt3->close();

    // 4. Group memberships delete karein
    $stmt4 = $conn->prepare("DELETE FROM group_members WHERE user_id = ?");
    $stmt4->bind_param("i", $user_id);
    $stmt4->execute();
    $stmt4->close();

    // 5. Main user account delete karein
    $stmt5 = $conn->prepare("DELETE FROM users WHERE id = ?");
    $stmt5->bind_param("i", $user_id);
    $stmt5->execute();
    $stmt5->close();

    $conn->commit();

    // Session destroy karke logout redirect karayein
    session_unset();
    session_destroy();

    echo 'success';
} catch (Exception $e) {
    $conn->rollback();
    echo 'error';
}
?>