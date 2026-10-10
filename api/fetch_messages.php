<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['user_id']) || !isset($_GET['receiver_id'])) {
    exit();
}

$sender_id   = $_SESSION['user_id'];
$receiver_id = intval($_GET['receiver_id']);

// Unread messages update
$update_stmt = $conn->prepare("UPDATE messages SET is_read = 1 WHERE sender_id = ? AND receiver_id = ? AND is_read = 0");
$update_stmt->bind_param("ii", $receiver_id, $sender_id);
$update_stmt->execute();
$update_stmt->close();

// Fetch Messages along with user details for Profile Picture Display
$query = "SELECT m.*, u.profile_pic, u.full_name 
          FROM messages m
          JOIN users u ON m.sender_id = u.id
          WHERE (m.sender_id = ? AND m.receiver_id = ?) 
             OR (m.sender_id = ? AND m.receiver_id = ?) 
          ORDER BY m.created_at ASC";

$stmt = $conn->prepare($query);
$stmt->bind_param("iiii", $sender_id, $receiver_id, $receiver_id, $sender_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $is_me = ($row['sender_id'] == $sender_id);
        $class = $is_me ? 'sent' : 'received';
        $msg_id = $row['id'];
        $ticks = '';

        if ($is_me) {
            $ticks = ($row['is_read'] == 1) 
                ? '<span style="color:#60a5fa; font-weight:bold; font-size:11px;"> ✓✓</span>' 
                : '<span style="color:#cbd5e1; font-size:11px;"> ✓</span>';
        }

        // Profile Picture Check
        $p_pic = $row['profile_pic'];
        $has_pic = (!empty($p_pic) && file_exists('../uploads/profiles/' . $p_pic));

        echo '<div class="message ' . $class . '" style="position:relative; display:flex; gap:8px; align-items:flex-start;">';

        // Display Avatar for Received Messages
        if (!$is_me) {
            echo '<div style="width:28px; height:28px; border-radius:50%; background:#4f46e5; color:white; display:flex; align-items:center; justify-content:center; font-size:11px; font-weight:bold; flex-shrink:0; overflow:hidden;">';
            if ($has_pic) {
                echo '<img src="uploads/profiles/' . htmlspecialchars($p_pic) . '" style="width:100%; height:100%; object-fit:cover;">';
            } else {
                echo strtoupper(substr($row['full_name'], 0, 1));
            }
            echo '</div>';
        }

        echo '<div style="flex-grow:1;">';

        if ($row['is_deleted'] == 1) {
            echo '<div style="font-style:italic; opacity:0.7;">🚫 This message was deleted</div>';
        } else {
            if ($is_me) {
                echo '<div style="float:right; margin-left:8px; font-size:11px; opacity:0.7; cursor:pointer;">';
                if ($row['message_type'] === 'text') {
                    echo '<span onclick="editMessage(' . $msg_id . ', \'' . htmlspecialchars(addslashes($row['message'])) . '\')" title="Edit">✏️</span> ';
                }
                echo '<span onclick="deleteMessage(' . $msg_id . ')" title="Delete">🗑️</span>';
                echo '</div>';
            }

            if ($row['message_type'] === 'audio' && !empty($row['file_path'])) {
                $audio_url = 'uploads/voice/' . htmlspecialchars($row['file_path']);
                echo '<div style="margin-bottom: 5px;"><audio controls preload="metadata" style="max-width: 220px; height: 38px;"><source src="' . $audio_url . '" type="audio/webm"></audio></div>';
            } elseif (!empty($row['file_path'])) {
                $file_url = 'uploads/attachments/' . htmlspecialchars($row['file_path']);
                if ($row['message_type'] === 'image') {
                    echo '<a href="' . $file_url . '" target="_blank"><img src="' . $file_url . '" style="max-width:200px; max-height:180px; border-radius:8px; display:block; margin-bottom:5px;"></a>';
                } else {
                    echo '<div style="margin-bottom:5px;"><a href="' . $file_url . '" download style="color:inherit; text-decoration:underline; font-weight:bold;">📄 Download Attachment</a></div>';
                }
            }

            if (!empty($row['message'])) {
                echo '<div>' . htmlspecialchars($row['message']) . '</div>';
            }
        }

        $edited_tag = ($row['is_edited'] == 1 && $row['is_deleted'] == 0) ? ' <span style="font-size:9px; opacity:0.7;">(edited)</span>' : '';
        echo '<div style="font-size:10px; opacity:0.8; text-align:right; margin-top:3px;">' . date('h:i A', strtotime($row['created_at'])) . $edited_tag . $ticks . '</div>';
        
        echo '</div>'; // End Inner Box
        echo '</div>'; // End Message Wrapper
    }
} else {
    echo '<div style="text-align: center; color: #64748b; margin-top: 50px;">No messages yet. Say Hi!</div>';
}

$stmt->close();
?>