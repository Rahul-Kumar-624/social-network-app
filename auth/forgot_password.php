<?php
session_start();
require_once '../config/db.php';
require_once '../config/security.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login_input  = sanitize_input($_POST['login_input'] ?? '');
    $new_password = $_POST['new_password'] ?? '';

    if (empty($login_input) || empty($new_password)) {
        $error = 'Please fill in all required fields!';
    } 
    elseif (!preg_match('/^[a-zA-Z0-9]+$/', $new_password)) {
        $error = 'Password must only contain letters (a-z) and numbers (0-9)!';
    } 
    else {
        // User check based on Email or Phone
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? OR phone = ?");
        $stmt->bind_param("ss", $login_input, $login_input);
        $stmt->execute();
        $res = $stmt->get_result();

        if ($res->num_rows > 0) {
            $user = $res->fetch_assoc();
            $user_id = $user['id'];
            $hashed_password = password_hash($new_password, PASSWORD_BCRYPT);

            // Update Password
            $update_stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
            $update_stmt->bind_param("si", $hashed_password, $user_id);

            if ($update_stmt->execute()) {
                $success = 'Password reset successfully! You can now login.';
            } else {
                $error = 'Failed to update password!';
            }
            $update_stmt->close();
        } else {
            $error = 'No account found with this Email or Phone!';
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - Social Network</title>
    <link rel="stylesheet" href="../assets/css/style.css?v=22.0">
</head>
<body style="background: #0b0f19; display: flex; justify-content: center; align-items: center; min-height: 100vh; font-family: 'Segoe UI', sans-serif; margin: 0;">

<div style="background: #1e293b; border: 1px solid rgba(255, 255, 255, 0.1); border-radius: 18px; padding: 32px; width: 100%; max-width: 400px; box-shadow: 0 20px 40px rgba(0,0,0,0.5); text-align: center;">
    <h2 style="color: #f8fafc; font-size: 24px; font-weight: 700; margin-bottom: 8px;">Forgot Password</h2>
    <p style="color: #94a3b8; font-size: 13px; margin-bottom: 24px;">Reset your account password</p>

    <?php if (!empty($error)): ?>
        <div style="background: rgba(239, 68, 68, 0.15); border: 1px solid #ef4444; color: #fca5a5; padding: 10px; border-radius: 8px; font-size: 13px; margin-bottom: 16px;">
            <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($success)): ?>
        <div style="background: rgba(34, 197, 94, 0.15); border: 1px solid #22c55e; color: #86efac; padding: 10px; border-radius: 8px; font-size: 13px; margin-bottom: 16px;">
            <?php echo htmlspecialchars($success); ?>
        </div>
    <?php endif; ?>

    <form action="" method="POST" style="text-align: left;">
        <div style="margin-bottom: 16px;">
            <label style="display: block; color: #cbd5e1; font-size: 13px; font-weight: 600; margin-bottom: 6px;">Email or Phone Number *</label>
            <input type="text" name="login_input" required placeholder="Registered Email or Phone" style="width: 100%; padding: 12px 14px; background: #0f172a; border: 1px solid #334155; border-radius: 10px; color: #f8fafc; font-size: 14px; outline: none; box-sizing: border-box;">
        </div>

        <div style="margin-bottom: 20px;">
            <label style="display: block; color: #cbd5e1; font-size: 13px; font-weight: 600; margin-bottom: 6px;">New Password (a-z / 0-9) *</label>
            <input type="password" name="new_password" pattern="[a-zA-Z0-9]+" required placeholder="Enter new password" style="width: 100%; padding: 12px 14px; background: #0f172a; border: 1px solid #334155; border-radius: 10px; color: #f8fafc; font-size: 14px; outline: none; box-sizing: border-box;">
        </div>

        <button type="submit" style="width: 100%; padding: 12px; background: linear-gradient(135deg, #6366f1, #4f46e5); color: white; border: none; border-radius: 10px; font-weight: 700; font-size: 15px; cursor: pointer; box-shadow: 0 4px 15px rgba(99, 102, 241, 0.4);">
            Reset Password
        </button>
    </form>

    <div style="margin-top: 20px; font-size: 13px; color: #94a3b8;">
        Remembered your password? <a href="login.php" style="color: #818cf8; font-weight: 600; text-decoration: none;">Login here</a>
    </div>
</div>

</body>
</html>