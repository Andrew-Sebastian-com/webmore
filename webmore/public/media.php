<?php
declare(strict_types=1);
require __DIR__ . '/config.php';

// Public media is served through a controlled endpoint so the actual upload
// directory is outside the document root and cannot execute or list files.
$file = basename((string)($_GET['file'] ?? ''));
if ($file === '' || !preg_match('/^[A-Za-z0-9._-]+$/', $file)) {
    http_response_code(404);
    exit;
}

$path = UPLOAD_DIR . '/' . $file;
if (!is_file($path)) {
    http_response_code(404);
    exit;
}

$finfo = new finfo(FILEINFO_MIME_TYPE);
$mime = $finfo->file($path);
$allowed = ['image/jpeg','image/png','image/gif','image/webp'];
if (!in_array($mime, $allowed, true)) {
    http_response_code(404);
    exit;
}

$size = filesize($path);
if ($size === false) {
    http_response_code(404);
    exit;
}

header('Content-Type: ' . $mime);
header('Content-Length: ' . $size);
header('Content-Disposition: inline');
header('X-Content-Type-Options: nosniff');
header("Content-Security-Policy: default-src 'none'; style-src 'none'; script-src 'none'; object-src 'none'; sandbox");
header('Cross-Origin-Resource-Policy: same-origin');
header('Cache-Control: public, max-age=31536000, immutable');
readfile($path);
