<?php

require_once '../config/session_config.php';
boycold_start_session();
header('Content-Type: application/json');

// ── 1. Auth guard ──────────────────────────────────────────
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not logged in.']);
    exit;
}
$userId = $_SESSION['user_id'];
// ── 2. Include DB config ──────────────────────────────────
require_once '../config/db_config.php';

// ── 3. Check upload error ──────────────────────────────────
if (!isset($_FILES['avatar']) || $_FILES['avatar']['error'] !== UPLOAD_ERR_OK) {
    $errorMap = [
        UPLOAD_ERR_INI_SIZE   => 'File too large (server limit).',
        UPLOAD_ERR_FORM_SIZE  => 'File too large (form limit).',
        UPLOAD_ERR_PARTIAL    => 'File only partially uploaded.',
        UPLOAD_ERR_NO_FILE    => 'No file was uploaded.',
        UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder.',
        UPLOAD_ERR_CANT_WRITE => 'Failed to write file.',
        UPLOAD_ERR_EXTENSION  => 'Upload stopped by extension.',
    ];
    $code = $_FILES['avatar']['error'] ?? UPLOAD_ERR_NO_FILE;
    echo json_encode(['success' => false, 'error' => $errorMap[$code] ?? 'Unknown upload error.']);
    exit;
}

$file = $_FILES['avatar'];

// ── 4. Validate file type ──────────────────────────────────
$allowedMime = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
$allowedExt  = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

$actualMime = mime_content_type($file['tmp_name']);
$ext        = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

if (!in_array($actualMime, $allowedMime) || !in_array($ext, $allowedExt)) {
    echo json_encode(['success' => false, 'error' => 'Only JPG, PNG, GIF, or WebP images are allowed.']);
    exit;
}

// ── 5. Validate file size (5 MB max) ───────────────────────
$maxSize = 5 * 1024 * 1024; // 5 MB
if ($file['size'] > $maxSize) {
    echo json_encode(['success' => false, 'error' => 'File too large. Maximum size is 5 MB.']);
    exit;
}

// ── 6. Ensure upload directory exists ──────────────────────
$uploadDir = __DIR__ . '/uploads/avatars/';
if (!is_dir($uploadDir)) {
    if (!mkdir($uploadDir, 0755, true) && !is_dir($uploadDir)) {
        echo json_encode(['success' => false, 'error' => 'Upload directory could not be created. Check Hostinger folder permissions.']);
        exit;
    }
}

if (!is_writable($uploadDir)) {
    echo json_encode(['success' => false, 'error' => 'Upload directory is not writable. Set the User/uploads/avatars folder permission to 755 or 775.']);
    exit;
}

// ── 7. Generate safe unique filename ───────────────────────
$filename = 'avatar_' . $userId . '_' . time() . '.' . $ext;
$destPath = $uploadDir . $filename;

// ── 8. Move uploaded file ──────────────────────────────────
if (!move_uploaded_file($file['tmp_name'], $destPath)) {
    echo json_encode(['success' => false, 'error' => 'Failed to save uploaded file.']);
    exit;
}

// ── 9. Save path to database ───────────────────────────────
// Build a web-relative path (adjust if your public root differs)
$webPath = 'uploads/avatars/' . $filename;

$stmt = $connect->prepare("UPDATE users SET avatar = ? WHERE id = ?");
if (!$stmt) {
    @unlink($destPath);
    echo json_encode(['success' => false, 'error' => 'Could not prepare the profile update.']);
    exit;
}
$stmt->bind_param("si", $webPath, $userId);
$success = $stmt->execute();

if (!$success) {
    // DB failed — clean up the file
    @unlink($destPath);
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $connect->error]);
    exit;
}

// Update session so the UI stays in sync
$_SESSION['user_avatar'] = $webPath;

// ── 10. Return success ─────────────────────────────────────
echo json_encode([
    'success' => true,
    'path'    => $webPath,
    'message' => 'Upload successful! Photo updated.'
]);
exit;
?>