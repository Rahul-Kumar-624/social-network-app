<?php
session_start();
require_once '../config/db.php';
require_once '../config/security.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login_input = sanitize_input($_POST['login_input'] ?? '');
    $password    = $_POST['password'] ?? '';

    if (!empty($login_input) && !empty($password)) {
        $stmt = $conn->prepare("SELECT * FROM users WHERE email = ? OR phone = ?");
        $stmt->bind_param("ss", $login_input, $login_input);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id']   = $user['id'];
            $_SESSION['user_name'] = $user['full_name'];
            header("Location: ../index.php");
            exit();
        } else {
            $error = 'Invalid Email/Phone or Password!';
        }
        $stmt->close();
    } else {
        $error = 'Please fill in all fields!';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Social Network</title>
    <link rel="stylesheet" href="../assets/css/style.css?v=22.0">
</head>
<body style="background: #0b0f19; display: flex; justify-content: center; align-items: center; height: 100vh; font-family: 'Segoe UI', sans-serif; margin: 0;">

<div style="background: #1e293b; border: 1px solid rgba(255, 255, 255, 0.1); border-radius: 18px; padding: 32px; width: 100%; max-width: 400px; box-shadow: 0 20px 40px rgba(0,0,0,0.5); text-align: center;">
    <h2 style="color: #f8fafc; font-size: 24px; font-weight: 700; margin-bottom: 8px;">Login Here</h2>
    <p style="color: #94a3b8; font-size: 13px; margin-bottom: 24px;">Sign in to your account</p>

    <?php if (!empty($error)): ?>
        <div style="background: rgba(239, 68, 68, 0.15); border: 1px solid #ef4444; color: #fca5a5; padding: 10px; border-radius: 8px; font-size: 13px; margin-bottom: 16px;">
            <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <form action="" method="POST" style="text-align: left;">
        <div style="margin-bottom: 16px;">
            <label style="display: block; color: #cbd5e1; font-size: 13px; font-weight: 600; margin-bottom: 6px;">Email or Phone</label>
            <input type="text" name="login_input" required placeholder="example@mail.com" style="width: 100%; padding: 12px 14px; background: #0f172a; border: 1px solid #334155; border-radius: 10px; color: #f8fafc; font-size: 14px; outline: none; box-sizing: border-box;">
        </div>

        <div style="margin-bottom: 8px;">
            <label style="display: block; color: #cbd5e1; font-size: 13px; font-weight: 600; margin-bottom: 6px;">Password</label>
            <input type="password" name="password" required placeholder="••••••••" style="width: 100%; padding: 12px 14px; background: #0f172a; border: 1px solid #334155; border-radius: 10px; color: #f8fafc; font-size: 14px; outline: none; box-sizing: border-box;">
        </div>

        <div style="text-align: right; margin-bottom: 20px;">
            <a href="forgot_password.php" style="color: #818cf8; font-size: 12px; text-decoration: none;">Forgot password?</a>
        </div>

        <button type="submit" style="width: 100%; padding: 12px; background: linear-gradient(135deg, #6366f1, #4f46e5); color: white; border: none; border-radius: 10px; font-weight: 700; font-size: 15px; cursor: pointer; box-shadow: 0 4px 15px rgba(99, 102, 241, 0.4);">
            Login
        </button>
    </form>

    <div style="margin-top: 20px; font-size: 13px; color: #94a3b8;">
        Don't have an account? <a href="register.php" style="color: #818cf8; font-weight: 600; text-decoration: none;">Create a new account</a>
    </div>
</div>

</body>
</html>