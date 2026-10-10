<?php
session_start();
require_once '../config/db.php';
require_once '../config/security.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = sanitize_input($_POST['full_name'] ?? '');
    $email     = sanitize_input($_POST['email'] ?? '');
    $phone     = sanitize_input($_POST['phone'] ?? '');
    $password  = $_POST['password'] ?? '';

    if (empty($full_name) || empty($phone) || empty($password)) {
        $error = 'Please fill in all required fields!';
    } 
    elseif (!preg_match('/^[0-9]{10}$/', $phone)) {
        $error = 'Mobile number must be exactly 10 digits!';
    } 
    elseif (!preg_match('/^[a-zA-Z0-9]+$/', $password)) {
        $error = 'Password must only contain letters (a-z) and numbers (0-9)!';
    }
    elseif (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address!';
    } 
    else {
        $check_stmt = $conn->prepare("SELECT id FROM users WHERE phone = ? OR (email = ? AND email != '')");
        $check_stmt->bind_param("ss", $phone, $email);
        $check_stmt->execute();
        
        if ($check_stmt->get_result()->num_rows > 0) {
            $error = 'Phone number or Email is already registered!';
        } else {
            $hashed_password = password_hash($password, PASSWORD_BCRYPT);
            $stmt = $conn->prepare("INSERT INTO users (full_name, email, phone, password) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", $full_name, $email, $phone, $hashed_password);

            if ($stmt->execute()) {
                $success = 'Account created successfully!';
            } else {
                $error = 'An error occurred during registration!';
            }
            $stmt->close();
        }
        $check_stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Social Network</title>
    <link rel="stylesheet" href="../assets/css/style.css?v=19.0">
</head>
<body style="background: #0b0f19; display: flex; justify-content: center; align-items: center; min-height: 100vh; font-family: 'Segoe UI', sans-serif; margin: 0; padding: 20px 0; box-sizing: border-box;">

<div style="background: #1e293b; border: 1px solid rgba(255, 255, 255, 0.1); border-radius: 18px; padding: 32px; width: 100%; max-width: 400px; box-shadow: 0 20px 40px rgba(0,0,0,0.5); text-align: center;">
    <h2 style="color: #f8fafc; font-size: 24px; font-weight: 700; margin-bottom: 8px;">Create Account</h2>
    <p style="color: #94a3b8; font-size: 13px; margin-bottom: 24px;">Sign up to get started</p>

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
        <div style="margin-bottom: 14px;">
            <label style="display: block; color: #cbd5e1; font-size: 13px; font-weight: 600; margin-bottom: 6px;">Full Name *</label>
            <input type="text" name="full_name" required placeholder="John Doe" style="width: 100%; padding: 12px 14px; background: #0f172a; border: 1px solid #334155; border-radius: 10px; color: #f8fafc; font-size: 14px; outline: none; box-sizing: border-box;">
        </div>

        <div style="margin-bottom: 14px;">
            <label style="display: block; color: #cbd5e1; font-size: 13px; font-weight: 600; margin-bottom: 6px;">Email Address</label>
            <input type="email" name="email" placeholder="example@mail.com" style="width: 100%; padding: 12px 14px; background: #0f172a; border: 1px solid #334155; border-radius: 10px; color: #f8fafc; font-size: 14px; outline: none; box-sizing: border-box;">
        </div>

        <div style="margin-bottom: 14px;">
            <label style="display: block; color: #cbd5e1; font-size: 13px; font-weight: 600; margin-bottom: 6px;">10-Digit Mobile Number *</label>
            <input type="text" name="phone" maxlength="10" pattern="[0-9]{10}" required placeholder="9876543210" style="width: 100%; padding: 12px 14px; background: #0f172a; border: 1px solid #334155; border-radius: 10px; color: #f8fafc; font-size: 14px; outline: none; box-sizing: border-box;">
        </div>

        <div style="margin-bottom: 20px;">
            <label style="display: block; color: #cbd5e1; font-size: 13px; font-weight: 600; margin-bottom: 6px;">Password (a-z / 0-9) *</label>
            <input type="password" name="password" pattern="[a-zA-Z0-9]+" required placeholder="••••••••" style="width: 100%; padding: 12px 14px; background: #0f172a; border: 1px solid #334155; border-radius: 10px; color: #f8fafc; font-size: 14px; outline: none; box-sizing: border-box;">
        </div>

        <button type="submit" style="width: 100%; padding: 12px; background: linear-gradient(135deg, #6366f1, #4f46e5); color: white; border: none; border-radius: 10px; font-weight: 700; font-size: 15px; cursor: pointer; box-shadow: 0 4px 15px rgba(99, 102, 241, 0.4);">
            Sign Up
        </button>
    </form>

    <div style="margin-top: 20px; font-size: 13px; color: #94a3b8;">
        Already have an account? <a href="login.php" style="color: #818cf8; font-weight: 600; text-decoration: none;">Login here</a>
    </div>
</div>

</body>
</html>