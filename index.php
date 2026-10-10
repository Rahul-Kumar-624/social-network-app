<?php
ini_set('session.cookie_samesite', 'None');
ini_set('session.cookie_secure', 'True');
session_start();
require_once 'config/db.php';

// Auth Guard Check
if (!isset($_SESSION['user_id'])) {
    header("Location: auth/login.php");
    exit();
}

$user_id   = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];

// Logged-in user profile_pic fetch
$user_stmt = $conn->prepare("SELECT profile_pic FROM users WHERE id = ?");
$user_stmt->bind_param("i", $user_id);
$user_stmt->execute();
$my_user_data = $user_stmt->get_result()->fetch_assoc();
$my_profile_pic = ($my_user_data && !empty($my_user_data['profile_pic'])) ? $my_user_data['profile_pic'] : '';
$user_stmt->close();

// ACCEPTED FRIENDS FETCH
$query = "SELECT u.id, u.full_name, u.phone, u.profile_pic 
          FROM users u
          INNER JOIN friend_requests fr 
          ON (fr.sender_id = u.id OR fr.receiver_id = u.id)
          WHERE u.id != ? 
            AND fr.status = 'accepted' 
            AND (fr.sender_id = ? OR fr.receiver_id = ?)";

$stmt = $conn->prepare($query);
$stmt->bind_param("iii", $user_id, $user_id, $user_id);
$stmt->execute();
$friends = $stmt->get_result();

// GROUPS FETCH
$group_query = "SELECT g.id, g.group_name, g.created_by, gm.role 
                FROM chat_groups g
                INNER JOIN group_members gm ON g.id = gm.group_id
                WHERE gm.user_id = ?";
$g_stmt = $conn->prepare($group_query);
$g_stmt->bind_param("i", $user_id);
$g_stmt->execute();
$groups = $g_stmt->get_result();

// Pending friend requests count
$req_query = "SELECT COUNT(*) as count FROM friend_requests WHERE receiver_id = ? AND status = 'pending'";
$req_stmt = $conn->prepare($req_query);
$req_stmt->bind_param("i", $user_id);
$req_stmt->execute();
$req_count_res = $req_stmt->get_result()->fetch_assoc();
$req_count = $req_count_res ? $req_count_res['count'] : 0;
$req_stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Social Network App</title>
    <link rel="stylesheet" href="assets/css/style.css?v=21.0">
    <script src="https://unpkg.com/html5-qrcode"></script>

    <style>
        .status-container { display: flex; align-items: center; gap: 12px; padding: 10px 15px; overflow-x: auto; border-bottom: 1px solid #e2e8f0; background: #ffffff; white-space: nowrap; }
        .status-item { display: flex; flex-direction: column; align-items: center; cursor: pointer; font-size: 11px; width: 58px; }
        .status-avatar-ring { width: 48px; height: 48px; border-radius: 50%; padding: 2px; border: 2px solid #4f46e5; position: relative; }
        .status-avatar-ring.add-ring { border: 2px dashed #94a3b8; }
        .status-avatar-ring img, .status-avatar-ring div { width: 100%; height: 100%; border-radius: 50%; object-fit: cover; display: flex; align-items: center; justify-content: center; background: #4f46e5; color: white; font-weight: bold; }
        .status-plus-badge { position: absolute; bottom: -2px; right: -2px; background: #4f46e5; color: white; border-radius: 50%; width: 16px; height: 16px; display: flex; align-items: center; justify-content: center; font-size: 12px; border: 2px solid white; }
        .section-header { padding: 8px 15px; background: #f8fafc; font-size: 12px; font-weight: bold; color: #64748b; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center; }
        .btn-create-group { background: #4f46e5; color: white; border: none; padding: 3px 8px; border-radius: 12px; font-size: 11px; cursor: pointer; }

        .mobile-back-btn {
            display: none;
            background: none;
            border: none;
            font-size: 22px;
            cursor: pointer;
            margin-right: 5px;
            color: #4f46e5;
            font-weight: bold;
        }

        /* EMOJI BOX STYLING (High Z-Index for Mobile Overlay) */
        #emojiBox {
            display: none;
            position: absolute;
            bottom: 55px;
            left: 20px;
            background: #ffffff;
            border: 1px solid #cbd5e1;
            border-radius: 10px;
            padding: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
            z-index: 999999 !important;
            font-size: 20px;
            touch-action: manipulation;
        }

        #emojiBox span {
            cursor: pointer;
            padding: 4px;
            display: inline-block;
            user-select: none;
        }

        /* EXACT MOBILE RESPONSIVE FIX (Full Width & No Side Gap) */
        @media screen and (max-width: 768px) {
            html, body {
                width: 100% !important;
                height: 100vh !important;
                overflow-x: hidden !important;
                position: relative !important;
            }

            .app-container {
                width: 100vw !important;
                height: 100vh !important;
                overflow: hidden !important;
                position: relative !important;
            }

            .sidebar {
                width: 100vw !important;
                max-width: 100vw !important;
                height: 100vh !important;
                display: flex !important;
                flex-direction: column !important;
                box-sizing: border-box !important;
            }

            .chat-area {
                position: fixed !important;
                top: 0 !important;
                left: 100vw !important;
                width: 100vw !important;
                height: 100vh !important;
                background: #ffffff !important;
                z-index: 50 !important;
                display: flex !important;
                flex-direction: column !important;
                justify-content: space-between !important;
                transition: left 0.3s ease-in-out !important;
                box-sizing: border-box !important;
            }

            .app-container.mobile-chat-active .chat-area {
                left: 0 !important;
            }

            .mobile-back-btn {
                display: inline-block !important;
            }

            .chat-box {
                flex: 1 !important;
                overflow-y: auto !important;
                padding-bottom: 80px !important;
            }

            /* Normal state me input bar HIDE rahega */
            .chat-input-container {
                display: none !important;
            }

            /* CHAT ACTIVE STATE: Sirf active chat me dikhega */
            .app-container.mobile-chat-active .chat-input-container {
                position: fixed !important;
                bottom: 0 !important;
                left: 0 !important;
                right: 0 !important;
                width: 100vw !important;
                max-width: 100vw !important;
                box-sizing: border-box !important;
                background: #ffffff !important;
                padding: 8px 8px !important;
                box-shadow: 0 -2px 10px rgba(0,0,0,0.12) !important;
                z-index: 99999 !important;
                display: flex !important;
                align-items: center !important;
                gap: 4px !important;
                overflow: hidden !important;
            }

            .chat-input-container button[type="button"] {
                font-size: 16px !important;
                padding: 2px !important;
                flex-shrink: 0 !important;
            }

            .chat-input-container input[type="text"] {
                flex: 1 !important;
                min-width: 0 !important;
                font-size: 14px !important;
                padding: 8px 10px !important;
                border: 1px solid #cbd5e1 !important;
                border-radius: 20px !important;
                outline: none !important;
            }

            .chat-input-container .btn-send {
                flex-shrink: 0 !important;
                background: #4f46e5 !important;
                color: #ffffff !important;
                border: none !important;
                padding: 8px 12px !important;
                border-radius: 18px !important;
                font-size: 13px !important;
                font-weight: bold !important;
                white-space: nowrap !important;
            }
        }
    </style>
</head>
<body>

<div class="app-container">
    <!-- Left Sidebar -->
    <div class="sidebar">
        <!-- Sidebar Header -->
        <div class="sidebar-header">
            <div style="display: flex; align-items: center; gap: 10px;">
                <div class="my-avatar" style="position: relative;">
                    <?php if (!empty($my_profile_pic) && file_exists('uploads/profiles/' . $my_profile_pic)): ?>
                        <img src="uploads/profiles/<?php echo htmlspecialchars($my_profile_pic); ?>" style="width:100%; height:100%; border-radius:50%; object-fit:cover;" alt="Me">
                    <?php else: ?>
                        <?php echo strtoupper(substr($user_name, 0, 1)); ?>
                    <?php endif; ?>
                    <span style="position: absolute; bottom: 0; right: 0; width: 12px; height: 12px; background-color: #22c55e; border: 2px solid white; border-radius: 50%;"></span>
                </div>
                <h3><?php echo htmlspecialchars($user_name); ?></h3>
            </div>
            
            <div style="display: flex; align-items: center; gap: 10px;">
                <div style="position: relative; cursor: pointer;" onclick="toggleNotifications()">
                    <span style="font-size: 18px; color: white;">🔔</span>
                    <span id="notifBadge" style="position: absolute; top: -5px; right: -5px; background: #ef4444; color: white; font-size: 10px; padding: 2px 5px; border-radius: 50%; display: none;">0</span>
                </div>
                <button class="menu-btn" onclick="toggleMenu()">☰</button>
            </div>

            <div id="notifDropdown" style="display: none; position: absolute; top: 50px; right: 40px; background: white; color: #0f172a; width: 240px; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.15); z-index: 150; padding: 10px;">
                <h4 style="margin: 0 0 10px 0; font-size: 14px; border-bottom: 1px solid #eee; padding-bottom: 5px;">Notifications</h4>
                <div id="notifList" style="max-height: 200px; overflow-y: auto; font-size: 12px;">
                    <p style="color: #64748b; margin: 0; text-align: center;">No new notifications</p>
                </div>
            </div>

            <div class="dropdown-menu" id="dropdownMenu">
                <a href="#" onclick="openModal('reqModal')">
                    Friend Requests 
                    <?php if($req_count > 0): ?><span class="req-badge"><?php echo $req_count; ?></span><?php endif; ?>
                </a>
                <a href="#" onclick="openModal('myQrModal')">My QR</a>
                <a href="#" onclick="openScanModal()">Scan QR</a>
                <a href="#" onclick="openModal('settingsModal')">Settings</a>
                <a href="logout.php" style="color: #ef4444;">Logout</a>
            </div>
        </div>

        <!-- 24-Hour Stories Bar -->
        <div class="status-container">
            <div class="status-item" onclick="openModal('addStatusModal')">
                <div class="status-avatar-ring add-ring">
                    <?php if (!empty($my_profile_pic) && file_exists('uploads/profiles/' . $my_profile_pic)): ?>
                        <img src="uploads/profiles/<?php echo htmlspecialchars($my_profile_pic); ?>" alt="Me">
                    <?php else: ?>
                        <div><?php echo strtoupper(substr($user_name, 0, 1)); ?></div>
                    <?php endif; ?>
                    <span class="status-plus-badge">+</span>
                </div>
                <span style="margin-top: 4px; overflow: hidden; text-overflow: ellipsis;">Add Status</span>
            </div>
            <div id="statusList" style="display: flex; gap: 12px;"></div>
        </div>

        <!-- Search Input -->
        <div class="user-search">
            <input type="text" id="searchInput" placeholder="Search friends & groups..." onkeyup="filterUsers()">
        </div>

        <!-- Groups Header -->
        <div class="section-header">
            <span>GROUPS</span>
            <button class="btn-create-group" onclick="openModal('createGroupModal')">+ New Group</button>
        </div>

        <!-- Groups List -->
        <div id="groupsList">
            <?php if ($groups->num_rows > 0): ?>
                <?php while ($g = $groups->fetch_assoc()): ?>
                    <div class="friend-item group-item" onclick="selectGroup(<?php echo $g['id']; ?>, '<?php echo htmlspecialchars($g['group_name'], ENT_QUOTES); ?>', '<?php echo $g['role']; ?>', <?php echo $g['created_by']; ?>)">
                        <div class="friend-avatar" style="background: #0284c7;">👥</div>
                        <div class="friend-info">
                            <h4><?php echo htmlspecialchars($g['group_name']); ?></h4>
                            <p style="font-size: 11px; color: #64748b;"><?php echo ($g['role'] === 'admin') ? 'Role: Admin' : 'Member'; ?></p>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div style="padding: 10px 15px; color: #94a3b8; font-size: 12px;">No groups joined yet.</div>
            <?php endif; ?>
        </div>

        <!-- Direct Friends Section Header -->
        <div class="section-header">
            <span>DIRECT MESSAGES</span>
        </div>

        <!-- Friends List -->
        <div class="friends-list" id="friendsList">
            <?php if ($friends->num_rows > 0): ?>
                <?php while ($row = $friends->fetch_assoc()): 
                    $f_pic = $row['profile_pic'];
                    $has_pic = (!empty($f_pic) && file_exists('uploads/profiles/' . $f_pic));
                    $f_pic_url = $has_pic ? 'uploads/profiles/' . htmlspecialchars($f_pic) : '';
                ?>
                    <div class="friend-item" onclick="selectUser(<?php echo $row['id']; ?>, '<?php echo htmlspecialchars($row['full_name'], ENT_QUOTES); ?>', '<?php echo $f_pic_url; ?>')">
                        <div class="friend-avatar" style="position: relative;">
                            <?php if ($has_pic): ?>
                                <img src="<?php echo $f_pic_url; ?>" style="width:100%; height:100%; border-radius:50%; object-fit:cover;" alt="Pic">
                            <?php else: ?>
                                <?php echo strtoupper(substr($row['full_name'], 0, 1)); ?>
                            <?php endif; ?>
                            <span id="status-dot-<?php echo $row['id']; ?>" style="position: absolute; bottom: 0; right: 0; width: 12px; height: 12px; background-color: #94a3b8; border: 2px solid white; border-radius: 50%;"></span>
                        </div>
                        <div class="friend-info">
                            <h4><?php echo htmlspecialchars($row['full_name']); ?></h4>
                            <p id="status-text-<?php echo $row['id']; ?>">📱 <?php echo htmlspecialchars($row['phone'] ? $row['phone'] : 'No Phone'); ?></p>
                        </div>
                        <button class="btn-delete-friend" title="Unfriend" onclick="deleteFriend(event, <?php echo $row['id']; ?>)">🗑️</button>
                        <span class="unread-badge" id="badge-<?php echo $row['id']; ?>">0</span>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div style="text-align: center; padding: 20px; color: #64748b; font-size: 13px;">No friends added yet.</div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Right Chat Area -->
    <div class="chat-area">
        <!-- Chat Header -->
        <div class="chat-header" style="display: flex; justify-content: space-between; align-items: center;">
            <div style="display:flex; align-items:center; gap:10px;">
                <!-- Mobile Back Button -->
                <button class="mobile-back-btn" onclick="closeMobileChat()" title="Back">←</button>
                <div id="chatHeaderAvatar" style="width:36px; height:36px; border-radius:50%; background:#4f46e5; color:white; display:none; align-items:center; justify-content:center; font-weight:bold; overflow:hidden;"></div>
                <span id="chatTitle">Select a friend or group to start chatting</span>
            </div>
            
            <div id="groupControls" style="display: none; gap: 8px;">
                <button id="addMemberBtn" onclick="openAddMemberModal()" style="background:#0284c7; color:white; border:none; padding:5px 10px; border-radius:6px; font-size:12px; cursor:pointer;">+ Member</button>
                <button onclick="exitGroup()" style="background:#f59e0b; color:white; border:none; padding:5px 10px; border-radius:6px; font-size:12px; cursor:pointer;">Exit</button>
                <button id="deleteGroupBtn" onclick="deleteGroup()" style="background:#ef4444; color:white; border:none; padding:5px 10px; border-radius:6px; font-size:12px; cursor:pointer; display:none;">Delete Group</button>
            </div>
        </div>

        <div class="chat-box" id="chatBox">
            <div style="text-align: center; color: #64748b; margin-top: 50px;">
                No conversation selected.
            </div>
        </div>

        <!-- Input Bar (Fixed for Mobile & Laptop) -->
        <div class="chat-input-container" style="align-items: center; position: relative;">
            <input type="file" id="fileInput" style="display: none;" onchange="handleFileSelect()">
            <button type="button" id="attachBtn" onclick="document.getElementById('fileInput').click()" style="background:none; border:none; font-size:20px; cursor:pointer;" disabled title="Attach File/Photo">📎</button>
            <button type="button" id="micBtn" onclick="toggleVoiceRecording()" style="background:none; border:none; font-size:20px; cursor:pointer;" disabled title="Voice Note">🎙️</button>
            <button type="button" id="emojiBtn" onclick="toggleEmojiBox(event)" style="background:none; border:none; font-size:20px; cursor:pointer;" disabled title="Emojis">😊</button>

            <div id="emojiBox">
                <span onclick="addEmoji('😊')">😊</span>
                <span onclick="addEmoji('😂')">😂</span>
                <span onclick="addEmoji('❤️')">❤️</span>
                <span onclick="addEmoji('👍')">👍</span>
                <span onclick="addEmoji('🔥')">🔥</span>
                <span onclick="addEmoji('🙏')">🙏</span>
                <span onclick="addEmoji('🎉')">🎉</span>
            </div>

            <span id="recordingStatus" style="font-size: 12px; color: #ef4444; display: none; font-weight: bold; margin-right: 5px;">🔴 Recording...</span>
            <span id="filePreview" style="font-size: 12px; color: #4f46e5; display: none; white-space: nowrap; max-width: 120px; overflow: hidden; text-overflow: ellipsis;"></span>
            
            <input type="text" placeholder="Type a message..." id="messageInput" disabled>
            <button class="btn-send" id="sendBtn" onclick="sendMessage()" disabled>Send</button>
        </div>
    </div>
</div>

<!-- MODALS -->

<!-- Create Group Modal -->
<div class="modal" id="createGroupModal">
    <div class="modal-content">
        <span class="close-btn" onclick="closeModal('createGroupModal')">&times;</span>
        <h3>Create New Group</h3>
        <input type="text" id="newGroupName" placeholder="Group Name..." style="width:100%; padding:10px; margin-top:15px; border:1px solid #cbd5e1; border-radius:8px; outline:none;">
        <button onclick="createGroup()" style="background:#4f46e5; color:white; border:none; padding:10px; width:100%; border-radius:8px; margin-top:15px; font-weight:bold; cursor:pointer;">Create Group 👥</button>
    </div>
</div>

<!-- Add Member Modal -->
<div class="modal" id="addMemberModal">
    <div class="modal-content">
        <span class="close-btn" onclick="closeModal('addMemberModal')">&times;</span>
        <h3>Add Friend to Group</h3>
        <div id="addableFriendsList" style="margin-top:15px; max-height:200px; overflow-y:auto;">Loading friends...</div>
    </div>
</div>

<!-- Add Status Modal -->
<div class="modal" id="addStatusModal">
    <div class="modal-content">
        <span class="close-btn" onclick="closeModal('addStatusModal')">&times;</span>
        <h3>Add 24-Hour Status</h3>
        <textarea id="statusText" placeholder="What's on your mind?..." style="width:100%; height:80px; padding:8px; border:1px solid #cbd5e1; border-radius:8px; margin-top:10px; outline:none; font-family:inherit;"></textarea>
        <div style="margin-top:15px;">
            <label style="font-size:13px; font-weight:bold; color:#4f46e5; cursor:pointer;">
                📷 Select Image (Optional):
                <input type="file" id="statusMediaInput" accept="image/*" style="display:block; margin-top:5px;">
            </label>
        </div>
        <button onclick="postStatus()" style="background:#4f46e5; color:white; border:none; padding:10px; width:100%; border-radius:8px; margin-top:15px; font-weight:bold; cursor:pointer;">Post Status 🚀</button>
    </div>
</div>

<!-- View Status Modal -->
<div class="modal" id="viewStatusModal">
    <div class="modal-content" style="text-align: center; background: #0f172a; color: white; max-width: 380px;">
        <span class="close-btn" onclick="closeModal('viewStatusModal')" style="color: white;">&times;</span>
        <h4 id="statusViewerName" style="margin: 0 0 5px 0;"></h4>
        <p id="statusViewerTime" style="font-size: 11px; color: #94a3b8; margin: 0 0 15px 0;"></p>
        <div id="statusViewerMedia" style="margin-bottom: 15px;"></div>
        <p id="statusViewerText" style="font-size: 15px; word-break: break-word; line-height: 1.4;"></p>
    </div>
</div>

<!-- Friend Requests Modal -->
<div class="modal" id="reqModal">
    <div class="modal-content">
        <span class="close-btn" onclick="closeModal('reqModal')">&times;</span>
        <h3>Friend Requests</h3>
        <div id="requestsList">Loading requests...</div>
    </div>
</div>

<!-- My QR Modal -->
<div class="modal" id="myQrModal">
    <div class="modal-content" style="text-align: center;">
        <span class="close-btn" onclick="closeModal('myQrModal')">&times;</span>
        <h3>My QR Code</h3>
        <p>User ID: <?php echo $user_id; ?></p>
        <img src="https://api.qrserver.com/v1/create-qr-code/?size=180x180&data=user_<?php echo $user_id; ?>" alt="QR Code">
    </div>
</div>

<!-- Scan QR Modal -->
<div class="modal" id="scanQrModal">
    <div class="modal-content" style="text-align: center; max-width: 350px;">
        <span class="close-btn" onclick="closeScanModal()">&times;</span>
        <h3>Scan QR Code</h3>
        <div id="qr-reader" style="width: 100%; border-radius: 8px; overflow: hidden; margin-top: 10px;"></div>
        <p id="qr-result" style="margin-top: 10px; font-weight: bold; color: #4f46e5;"></p>
    </div>
</div>

<!-- Settings Modal -->
<div class="modal" id="settingsModal">
    <div class="modal-content" style="text-align: center;">
        <span class="close-btn" onclick="closeModal('settingsModal')">&times;</span>
        <h3>Profile Settings</h3>
        <div style="margin: 20px 0;">
            <div class="my-avatar" style="width:90px; height:90px; margin: 0 auto; font-size:32px;">
                <?php if (!empty($my_profile_pic) && file_exists('uploads/profiles/' .$my_profile_pic)): ?>
                    <img src="uploads/profiles/<?php echo htmlspecialchars($my_profile_pic); ?>" style="width:100%; height:100%; border-radius:50%; object-fit:cover;" alt="Profile">
                <?php else: ?>
                    <?php echo strtoupper(substr($user_name, 0, 1)); ?>
                <?php endif; ?>
            </div>
            <br>
            <input type="file" id="profileInput" accept="image/*" style="display: none;" onchange="uploadProfilePic()">
            <button onclick="document.getElementById('profileInput').click()" style="background: #4f46e5; color: white; border: none; padding: 8px 16px; border-radius: 20px; cursor: pointer; font-weight: 600;">Change Photo 📷</button>
        </div>
        <p><strong>Name:</strong> <?php echo htmlspecialchars($user_name); ?></p>
        <p><strong>Theme:</strong> Modern Indigo</p>

        <hr style="border: 0; border-top: 1px solid #e2e8f0; margin: 20px 0;">

        <button onclick="deleteAccount()" style="background: #fee2e2; color: #ef4444; border: 1px solid #fca5a5; padding: 10px 18px; border-radius: 8px; cursor: pointer; font-weight: bold; width: 100%;">
            🗑️ Delete My Account
        </button>
    </div>
</div>

<script>
let currentMode = 'direct';
let selectedReceiverId = null;
let selectedGroupId = null;
let currentGroupRole = null;
let currentGroupCreator = null;

let chatInterval = null;
let html5QrcodeScanner = null;
let currentChatHtml = ""; 
let selectedFile = null;

let mediaRecorder = null;
let audioChunks = [];
let isRecording = false;

function toggleMenu() {
    const menu = document.getElementById('dropdownMenu');
    menu.style.display = menu.style.display === 'block' ? 'none' : 'block';
}

function openModal(id) {
    document.getElementById(id).style.display = 'flex';
    document.getElementById('dropdownMenu').style.display = 'none';
    if(id === 'reqModal') loadFriendRequests();
}

function closeModal(id) {
    document.getElementById(id).style.display = 'none';
}

function toggleEmojiBox(event) {
    if (event) event.stopPropagation();
    const box = document.getElementById('emojiBox');
    box.style.display = (box.style.display === 'block') ? 'none' : 'block';
}

function addEmoji(emoji) {
    const input = document.getElementById('messageInput');
    input.value += emoji;
    document.getElementById('emojiBox').style.display = 'none';
    input.focus();
}

function handleFileSelect() {
    const fileInput = document.getElementById('fileInput');
    if (fileInput.files[0]) {
        selectedFile = fileInput.files[0];
        const preview = document.getElementById('filePreview');
        preview.innerText = "📎 " + selectedFile.name;
        preview.style.display = 'inline';
    }
}

function closeMobileChat() {
    document.querySelector('.app-container').classList.remove('mobile-chat-active');
}

function deleteAccount() {
    if (!confirm("Are you sure you want to permanently delete your account?\nThis action cannot be undone!")) return;

    fetch('api/delete_account.php', { method: 'POST' })
    .then(res => res.text())
    .then(data => {
        if (data.trim() === 'success') {
            alert("Your account has been successfully deleted.");
            window.location.href = 'auth/login.php';
        } else {
            alert("Account deletion failed!");
        }
    });
}

function selectUser(receiverId, receiverName, profilePicUrl) {
    currentMode = 'direct';
    selectedReceiverId = receiverId;
    selectedGroupId = null;
    currentChatHtml = ""; 

    document.getElementById('chatTitle').innerText = receiverName;
    document.getElementById('groupControls').style.display = 'none';

    const avatarBox = document.getElementById('chatHeaderAvatar');
    avatarBox.style.display = 'flex';
    if (profilePicUrl && profilePicUrl.trim() !== '') {
        avatarBox.innerHTML = `<img src="${profilePicUrl}" style="width:100%; height:100%; object-fit:cover;">`;
    } else {
        avatarBox.innerText = receiverName.charAt(0).toUpperCase();
        avatarBox.style.background = '#4f46e5';
    }

    enableChatInputs();
    loadMessages();

    // Trigger Mobile View Slide-in
    document.querySelector('.app-container').classList.add('mobile-chat-active');

    if (chatInterval) clearInterval(chatInterval);
    chatInterval = setInterval(loadMessages, 3500); 
}

function selectGroup(groupId, groupName, role, creatorId) {
    currentMode = 'group';
    selectedGroupId = groupId;
    selectedReceiverId = null;
    currentGroupRole = role;
    currentGroupCreator = creatorId;

    document.getElementById('chatTitle').innerText = groupName;
    document.getElementById('groupControls').style.display = 'flex';

    const avatarBox = document.getElementById('chatHeaderAvatar');
    avatarBox.style.display = 'flex';
    avatarBox.style.background = '#0284c7';
    avatarBox.innerText = '👥';

    const isCreator = (creatorId == <?php echo $user_id; ?>);
    document.getElementById('addMemberBtn').style.display = (role === 'admin') ? 'inline-block' : 'none';
    document.getElementById('deleteGroupBtn').style.display = isCreator ? 'inline-block' : 'none';

    enableChatInputs();
    loadMessages();

    // Trigger Mobile View Slide-in
    document.querySelector('.app-container').classList.add('mobile-chat-active');

    if (chatInterval) clearInterval(chatInterval);
    chatInterval = setInterval(loadMessages, 3500);
}

function enableChatInputs() {
    document.getElementById('messageInput').disabled = false;
    document.getElementById('sendBtn').disabled = false;
    document.getElementById('attachBtn').disabled = false;
    document.getElementById('micBtn').disabled = false;
    document.getElementById('emojiBtn').disabled = false;
}

function loadMessages() {
    const chatBox = document.getElementById('chatBox');

    if (currentMode === 'direct') {
        if (!selectedReceiverId) return;
        fetch(`api/fetch_messages.php?receiver_id=${selectedReceiverId}`)
            .then(res => res.text())
            .then(data => {
                if (data.trim() !== currentChatHtml.trim()) {
                    currentChatHtml = data.trim();
                    chatBox.innerHTML = data;
                    chatBox.scrollTop = chatBox.scrollHeight;
                }
            });
    } else if (currentMode === 'group') {
        if (!selectedGroupId) return;
        fetch(`api/fetch_group_messages.php?group_id=${selectedGroupId}`)
            .then(res => res.text())
            .then(data => {
                if (data.trim() !== currentChatHtml.trim()) {
                    currentChatHtml = data.trim();
                    chatBox.innerHTML = data;
                    chatBox.scrollTop = chatBox.scrollHeight;
                }
            });
    }
}

function sendMessage() {
    const input = document.getElementById('messageInput');
    const msg = input.value.trim();

    if (!msg && !selectedFile) return;

    const formData = new FormData();
    if (msg) formData.append('message', msg);
    if (selectedFile) formData.append('attachment', selectedFile);

    const endpoint = (currentMode === 'direct') ? 'api/send_message.php' : 'api/send_group_message.php';

    if (currentMode === 'direct') {
        formData.append('receiver_id', selectedReceiverId);
    } else {
        formData.append('group_id', selectedGroupId);
    }

    fetch(endpoint, { method: 'POST', body: formData })
    .then(res => res.text())
    .then(data => {
        if (data.trim() === 'success') {
            input.value = '';
            selectedFile = null;
            document.getElementById('fileInput').value = '';
            document.getElementById('filePreview').style.display = 'none';
            document.getElementById('emojiBox').style.display = 'none';
            loadMessages();
        }
    });
}

function createGroup() {
    const name = document.getElementById('newGroupName').value.trim();
    if (!name) return alert("Please enter a group name!");

    const formData = new FormData();
    formData.append('action', 'create_group');
    formData.append('group_name', name);

    fetch('api/manage_group.php', { method: 'POST', body: formData })
    .then(res => res.text())
    .then(data => {
        if (data.trim() === 'success') location.reload();
    });
}

function openAddMemberModal() {
    if (!selectedGroupId) return;
    openModal('addMemberModal');

    const formData = new FormData();
    formData.append('action', 'get_addable_friends');
    formData.append('group_id', selectedGroupId);

    fetch('api/manage_group.php', { method: 'POST', body: formData })
    .then(res => res.json())
    .then(friends => {
        const container = document.getElementById('addableFriendsList');
        if (friends.length === 0) {
            container.innerHTML = '<p style="font-size:12px; color:#64748b;">No friends available to add.</p>';
            return;
        }

        let html = '';
        friends.forEach(f => {
            html += `
                <div style="display:flex; justify-content:space-between; align-items:center; padding:8px 0; border-bottom:1px solid #f1f5f9;">
                    <span>${f.full_name}</span>
                    <button onclick="addMemberToGroup(${f.id})" style="background:#0284c7; color:white; border:none; padding:4px 10px; border-radius:12px; font-size:12px; cursor:pointer;">Add</button>
                </div>
            `;
        });
        container.innerHTML = html;
    });
}

function addMemberToGroup(memberId) {
    const formData = new FormData();
    formData.append('action', 'add_member');
    formData.append('group_id', selectedGroupId);
    formData.append('member_id', memberId);

    fetch('api/manage_group.php', { method: 'POST', body: formData })
    .then(res => res.text())
    .then(data => {
        if (data.trim() === 'success') {
            alert("Member added successfully!");
            closeModal('addMemberModal');
        }
    });
}

function exitGroup() {
    if (!confirm("Are you sure you want to exit this group?")) return;

    const formData = new FormData();
    formData.append('action', 'exit_group');
    formData.append('group_id', selectedGroupId);

    fetch('api/manage_group.php', { method: 'POST', body: formData })
    .then(res => res.text())
    .then(data => {
        if (data.trim() === 'success') location.reload();
    });
}

function deleteGroup() {
    if (!confirm("Are you sure you want to delete this entire group?")) return;

    const formData = new FormData();
    formData.append('action', 'delete_group');
    formData.append('group_id', selectedGroupId);

    fetch('api/manage_group.php', { method: 'POST', body: formData })
    .then(res => res.text())
    .then(data => {
        if (data.trim() === 'success') location.reload();
    });
}

function toggleVoiceRecording() {
    if (!isRecording) startRecording();
    else stopRecording();
}

function startRecording() {
    navigator.mediaDevices.getUserMedia({ audio: true })
        .then(stream => {
            let options = { mimeType: 'audio/webm' };
            if (!MediaRecorder.isTypeSupported('audio/webm')) options = {};

            mediaRecorder = new MediaRecorder(stream, options);
            audioChunks = [];

            mediaRecorder.ondataavailable = event => {
                if (event.data.size > 0) audioChunks.push(event.data);
            };

            mediaRecorder.onstop = () => {
                const mimeType = mediaRecorder.mimeType || 'audio/webm';
                const audioBlob = new Blob(audioChunks, { type: mimeType });
                sendVoiceMessage(audioBlob);
                stream.getTracks().forEach(track => track.stop());
            };

            mediaRecorder.start(100);
            isRecording = true;
            document.getElementById('recordingStatus').style.display = 'inline';
            document.getElementById('micBtn').innerText = '⏹';
        })
        .catch(err => alert('Microphone access denied!'));
}

function stopRecording() {
    if (mediaRecorder && isRecording) {
        mediaRecorder.stop();
        isRecording = false;
        document.getElementById('recordingStatus').style.display = 'none';
        document.getElementById('micBtn').innerText = '🎙️';
    }
}

function sendVoiceMessage(audioBlob) {
    const formData = new FormData();
    formData.append('voice_note', audioBlob, 'voice.webm');

    const endpoint = (currentMode === 'direct') ? 'api/send_message.php' : 'api/send_group_message.php';

    if (currentMode === 'direct') formData.append('receiver_id', selectedReceiverId);
    else formData.append('group_id', selectedGroupId);

    fetch(endpoint, { method: 'POST', body: formData })
    .then(res => res.text())
    .then(data => {
        if (data.trim() === 'success') loadMessages();
    });
}

function postStatus() {
    const text = document.getElementById('statusText').value.trim();
    const mediaInput = document.getElementById('statusMediaInput');
    if (!text && !mediaInput.files[0]) return alert("Please enter text or choose an image!");

    const formData = new FormData();
    if (text) formData.append('status_text', text);
    if (mediaInput.files[0]) formData.append('status_media', mediaInput.files[0]);

    fetch('api/add_status.php', { method: 'POST', body: formData })
    .then(res => res.text())
    .then(data => {
        if (data.trim() === 'success') {
            document.getElementById('statusText').value = '';
            mediaInput.value = '';
            closeModal('addStatusModal');
            loadStatuses();
        }
    });
}

function loadStatuses() {
    fetch('api/fetch_statuses.php')
        .then(res => res.json())
        .then(data => {
            const list = document.getElementById('statusList');
            let html = '';
            if (data && data.length > 0) {
                data.forEach(item => {
                    const pic = item.profile_pic ? `uploads/profiles/${item.profile_pic}` : null;
                    const initial = item.name.charAt(0).toUpperCase();

                    html += `
                        <div class="status-item" onclick='viewStatus(${JSON.stringify(item)})'>
                            <div class="status-avatar-ring">
                                ${pic ? `<img src="${pic}">` : `<div>${initial}</div>`}
                            </div>
                            <span style="margin-top: 4px; overflow: hidden; text-overflow: ellipsis;">${item.name.split(' ')[0]}</span>
                        </div>
                    `;
                });
            }
            list.innerHTML = html;
        });
}

function viewStatus(statusObj) {
    document.getElementById('statusViewerName').innerText = statusObj.name;
    document.getElementById('statusViewerTime').innerText = statusObj.time;
    document.getElementById('statusViewerText').innerText = statusObj.text ? statusObj.text : '';

    const mediaContainer = document.getElementById('statusViewerMedia');
    if (statusObj.media) {
        mediaContainer.innerHTML = `<img src="uploads/statuses/${statusObj.media}" style="max-width:100%; max-height:260px; border-radius:8px;">`;
    } else mediaContainer.innerHTML = '';

    openModal('viewStatusModal');
}

function editMessage(msgId, oldText) {
    const newText = prompt("Edit message:", oldText);
    if (!newText || newText.trim() === "" || newText === oldText) return;

    const formData = new FormData();
    formData.append('action', 'edit');
    formData.append('message_id', msgId);
    formData.append('message', newText.trim());

    fetch('api/manage_message.php', { method: 'POST', body: formData })
    .then(res => res.text())
    .then(data => {
        if (data.trim() === 'success') loadMessages();
    });
}

function deleteMessage(msgId) {
    if (!confirm("Are you sure you want to delete this message?")) return;

    const formData = new FormData();
    formData.append('action', 'delete');
    formData.append('message_id', msgId);

    fetch('api/manage_message.php', { method: 'POST', body: formData })
    .then(res => res.text())
    .then(data => {
        if (data.trim() === 'success') loadMessages();
    });
}

function uploadProfilePic() {
    const fileInput = document.getElementById('profileInput');
    if (!fileInput.files[0]) return;

    const formData = new FormData();
    formData.append('profile_pic', fileInput.files[0]);

    fetch('api/upload_profile_pic.php', { method: 'POST', body: formData })
    .then(res => res.text())
    .then(data => {
        if (data.trim() === 'success') {
            alert('Profile picture updated successfully!');
            location.reload();
        }
    });
}

function openScanModal() {
    document.getElementById('scanQrModal').style.display = 'flex';
    document.getElementById('dropdownMenu').style.display = 'none';

    if (!html5QrcodeScanner) {
        const qrConfig = { 
            fps: 10, 
            qrbox: 200,
            videoConstraints: { facingMode: "environment" }
        };

        html5QrcodeScanner = new Html5QrcodeScanner("qr-reader", qrConfig);
        html5QrcodeScanner.render(onScanSuccess, function(error) {});
    }
}

function closeScanModal() {
    document.getElementById('scanQrModal').style.display = 'none';
    if (html5QrcodeScanner) {
        html5QrcodeScanner.clear();
        html5QrcodeScanner = null;
    }
}

function onScanSuccess(decodedText) {
    if (decodedText.startsWith("user_")) {
        let scannedUserId = decodedText.replace("user_", "");
        let formData = new FormData();
        formData.append('receiver_id', scannedUserId);

        fetch('api/send_request.php', { method: 'POST', body: formData })
        .then(res => res.text())
        .then(data => {
            alert("Request status: " + data);
            closeScanModal();
        });
    }
}

function deleteFriend(event, friendId) {
    event.stopPropagation();
    if (!confirm("Are you sure you want to unfriend this user?")) return;

    const formData = new FormData();
    formData.append('friend_id', friendId);

    fetch('api/delete_friend.php', { method: 'POST', body: formData })
    .then(res => res.text())
    .then(data => {
        if (data.trim() === 'success') {
            const item = event.target.closest('.friend-item');
            if (item) item.remove();
        }
    });
}

function loadFriendRequests() {
    fetch('api/fetch_requests.php')
        .then(res => res.text())
        .then(data => {
            document.getElementById('requestsList').innerHTML = data;
        });
}

function respondRequest(reqId, status) {
    const formData = new FormData();
    formData.append('request_id', reqId);
    formData.append('status', status);

    fetch('api/respond_request.php', { method: 'POST', body: formData })
        .then(res => res.text())
        .then(data => {
            if(data.trim() === 'success') loadFriendRequests();
        });
}

function toggleNotifications() {
    const notifBox = document.getElementById('notifDropdown');
    notifBox.style.display = notifBox.style.display === 'block' ? 'none' : 'block';
}

function checkNotifications() {
    fetch('api/fetch_notifications.php')
        .then(res => res.json())
        .then(data => {
            const badge = document.getElementById('notifBadge');
            const list = document.getElementById('notifList');

            if (data && data.length > 0) {
                badge.innerText = data.length;
                badge.style.display = 'inline-block';

                let html = '';
                data.forEach(item => {
                    const statusText = item.status === 'accepted' ? 'accepted' : 'rejected';
                    html += `
                        <div style="padding: 8px; border-bottom: 1px solid #f1f5f9; display: flex; justify-content: space-between; align-items: center;">
                            <div><strong>${item.name}</strong> ${statusText} your request.</div>
                            <button onclick="dismissNotif(${item.id})" style="border: none; background: none; color: #94a3b8; cursor: pointer;">✕</button>
                        </div>
                    `;
                });
                list.innerHTML = html;
            } else {
                badge.style.display = 'none';
                list.innerHTML = '<p style="color: #64748b; margin: 0; text-align: center;">No new notifications</p>';
            }
        });
}

function dismissNotif(reqId) {
    const formData = new FormData();
    formData.append('req_id', reqId);

    fetch('api/mark_notification_read.php', { method: 'POST', body: formData })
        .then(res => res.text())
        .then(data => {
            if (data.trim() === 'success') checkNotifications();
        });
}

function filterUsers() {
    let filter = document.getElementById('searchInput').value.toLowerCase();
    let items = document.querySelectorAll('.friend-item');

    items.forEach(item => {
        let name = item.querySelector('h4').innerText.toLowerCase();
        if (name.includes(filter)) item.style.display = "flex";
        else item.style.display = "none";
    });
}

function sendHeartbeat() { fetch('api/update_last_seen.php'); }
setInterval(sendHeartbeat, 5000);
sendHeartbeat();

function checkFriendsStatus() {
    fetch('api/fetch_online_status.php')
        .then(res => res.json())
        .then(data => {
            for (let userId in data) {
                const dot = document.getElementById(`status-dot-${userId}`);
                if (dot) dot.style.backgroundColor = data[userId].is_online ? '#22c55e' : '#94a3b8';
            }
        });
}
setInterval(checkFriendsStatus, 5000);
checkFriendsStatus();

loadStatuses();
setInterval(loadStatuses, 15000);
setInterval(checkNotifications, 6000);
checkNotifications();

document.getElementById('messageInput').addEventListener('keypress', e => {
    if (e.key === 'Enter') sendMessage();
});

window.onclick = function(event) {
    if (!event.target.matches('.menu-btn') && !event.target.closest('#notifDropdown') && !event.target.closest('[onclick="toggleNotifications()"]') && !event.target.closest('#emojiBox') && !event.target.closest('#emojiBtn')) {
        const menu = document.getElementById('dropdownMenu');
        const notif = document.getElementById('notifDropdown');
        const emoji = document.getElementById('emojiBox');
        if (menu && menu.style.display === 'block') menu.style.display = 'none';
        if (notif && notif.style.display === 'block') notif.style.display = 'none';
        if (emoji && emoji.style.display === 'block') emoji.style.display = 'none';
    }
}
</script>

</body>
</html>