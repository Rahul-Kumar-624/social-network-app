<?php
// Strict XSS Sanitization Function
function sanitize_input($data) {
    if (is_null($data)) return '';
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

// File Upload Security Check
function is_allowed_file($fileName, $allowedExts) {
    $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    return in_array($ext, $allowedExts);
}
?>