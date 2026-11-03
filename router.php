<?php
/**
 * Router script for PHP Built-in Server (php -S localhost:8000 router.php)
 */

$uri = urldecode(parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH));
$cleanUri = rtrim($uri, '.');

// If static file exists in workspace, serve it directly
$file = __DIR__ . $cleanUri;
if (!empty($cleanUri) && $cleanUri !== '/' && is_file($file)) {
    $ext = pathinfo($file, PATHINFO_EXTENSION);
    if ($ext !== 'php') {
        return false; // Built-in server serves css, js, images, etc.
    }
}

// Check public/ directory for static files as well
$publicFile = __DIR__ . '/public' . $cleanUri;
if (!empty($cleanUri) && is_file($publicFile)) {
    return false;
}

// Everything else routes through the MVC Front Controller
require __DIR__ . '/index.php';
return true;
