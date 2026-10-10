<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['user_id']) || !isset($_GET['group_id'])) {
    exit();
}

$user_id  = $_SESSION['user_id'];
$group_id = intval($_GET['group_id']);

$query = "SELECT gm.*, u.full_name 
          FROM group_messages gm
          JOIN users u ON gm.sender_id = u.id
          WHERE gm.group_id = ?
          ORDER BY gm.created_at ASC";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $group_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $is_me = ($row['sender_id'] == $user_id);
        $class = $is_me ? 'sent' : 'received';

        echo '<div class="message ' . $class . '" style="position:relative;">';
        
        if (!$is_me) {
            echo '<div style="font-size:11px; font-weight:bold; color:#4f46e5; margin-bottom:3px;">' . htmlspecialchars($row['full_name']) . '</div>';
        }

        if ($row['is_deleted'] == 1) {
            echo '<div style="font-style:italic; opacity:0.7;">🚫 This message was deleted</div>';
        } else {
            // Audio Voice Note
            if ($row['message_type'] === 'audio' && !empty($row['file_path'])) {
                $audio_url = 'uploads/voice/' . htmlspecialchars($row['file_path']);
                echo '<div style="margin-bottom: 5px;">';
                echo '<audio controls preload="metadata" style="max-width: 240px; height: 38px;"><source src="' . $audio_url . '" type="audio/webm"></audio>';
                echo '</div>';
            }
            // File/Image
            elseif (!empty($row['file_path'])) {
                $file_url = 'uploads/attachments/' . htmlspecialchars($row['file_path']);
                if ($row['message_type'] === 'image') {
                    echo '<a href="' . $file_url . '" target="_blank"><img src="' . $file_url . '" style="max-width:220px; max-height:200px; border-radius:8px; display:block; margin-bottom:5px;"></a>';
                } else {
                    echo '<div style="margin-bottom:5px;"><a href="' . $file_url . '" download style="color:inherit; text-decoration:underline; font-weight:bold;">📄 Download Attachment</a></div>';
                }
            }

            if (!empty($row['message'])) {
                echo '<div>' . htmlspecialchars($row['message']) . '</div>';
            }
        }

        echo '<div style="font-size:10px; opacity:0.8; text-align:right; margin-top:3px;">' . date('h:i A', strtotime($row['created_at'])) . '</div>';
        echo '</div>';
    }
} else {
    echo '<div style="text-align: center; color: #64748b; margin-top: 50px;">No messages in group yet. Start conversation!</div>';
}
$stmt->close();
?>