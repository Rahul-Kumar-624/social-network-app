<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['user_id'])) {
    exit('unauthorized');
}

$user_id = $_SESSION['user_id'];
$action  = $_POST['action'] ?? '';

// 1. Create Group
if ($action === 'create_group') {
    $group_name = trim($_POST['group_name'] ?? '');
    if (empty($group_name)) exit('empty_name');

    $conn->begin_transaction();
    try {
        $stmt = $conn->prepare("INSERT INTO chat_groups (group_name, created_by) VALUES (?, ?)");
        $stmt->bind_param("si", $group_name, $user_id);
        $stmt->execute();
        $group_id = $stmt->insert_id;
        $stmt->close();

        // Creator ko Admin role se add karein
        $stmt_mem = $conn->prepare("INSERT INTO group_members (group_id, user_id, role) VALUES (?, ?, 'admin')");
        $stmt_mem->bind_param("ii", $group_id, $user_id);
        $stmt_mem->execute();
        $stmt_mem->close();

        $conn->commit();
        echo 'success';
    } catch (Exception $e) {
        $conn->rollback();
        echo 'db_error';
    }
}

// 2. Add Member to Group
elseif ($action === 'add_member') {
    $group_id  = intval($_POST['group_id'] ?? 0);
    $member_id = intval($_POST['member_id'] ?? 0);

    if ($group_id <= 0 || $member_id <= 0) exit('invalid_params');

    // Admin check
    $check = $conn->prepare("SELECT role FROM group_members WHERE group_id = ? AND user_id = ? AND role = 'admin'");
    $check->bind_param("ii", $group_id, $user_id);
    $check->execute();
    if ($check->get_result()->num_rows === 0) exit('not_admin');
    $check->close();

    $stmt = $conn->prepare("INSERT IGNORE INTO group_members (group_id, user_id, role) VALUES (?, ?, 'member')");
    $stmt->bind_param("ii", $group_id, $member_id);
    if ($stmt->execute()) echo 'success';
    else echo 'error';
    $stmt->close();
}

// 3. Exit Group
elseif ($action === 'exit_group') {
    $group_id = intval($_POST['group_id'] ?? 0);
    if ($group_id <= 0) exit('invalid_id');

    $stmt = $conn->prepare("DELETE FROM group_members WHERE group_id = ? AND user_id = ?");
    $stmt->bind_param("ii", $group_id, $user_id);
    if ($stmt->execute()) echo 'success';
    else echo 'error';
    $stmt->close();
}

// 4. Delete Group (Sirf Admin/Creator)
elseif ($action === 'delete_group') {
    $group_id = intval($_POST['group_id'] ?? 0);
    if ($group_id <= 0) exit('invalid_id');

    $check = $conn->prepare("SELECT id FROM chat_groups WHERE id = ? AND created_by = ?");
    $check->bind_param("ii", $group_id, $user_id);
    $check->execute();
    if ($check->get_result()->num_rows === 0) exit('not_creator');
    $check->close();

    $stmt = $conn->prepare("DELETE FROM chat_groups WHERE id = ?");
    $stmt->bind_param("i", $group_id);
    if ($stmt->execute()) echo 'success';
    else echo 'error';
    $stmt->close();
}

// 5. Fetch Non-Member Friends (Select to add)
elseif ($action === 'get_addable_friends') {
    $group_id = intval($_POST['group_id'] ?? 0);

    $query = "SELECT u.id, u.full_name FROM users u
              INNER JOIN friend_requests fr ON (fr.sender_id = u.id OR fr.receiver_id = u.id)
              WHERE u.id != ? AND fr.status = 'accepted' AND (fr.sender_id = ? OR fr.receiver_id = ?)
              AND u.id NOT IN (SELECT user_id FROM group_members WHERE group_id = ?)";

    $stmt = $conn->prepare($query);
    $stmt->bind_param("iiii", $user_id, $user_id, $user_id, $group_id);
    $stmt->execute();
    $res = $stmt->get_result();

    $friends = [];
    while ($row = $res->fetch_assoc()) {
        $friends[] = $row;
    }
    header('Content-Type: application/json');
    echo json_encode($friends);
    $stmt->close();
}
?>