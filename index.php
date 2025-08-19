<?php
/**
 * Redirect from the root to the public folder
 */

// Path to public folder
$publicPath = __DIR__ . '/public';
$uri = urldecode(
    parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? ''
);

// If the file exists in public, serve it directly
if ($uri !== '/' && file_exists($publicPath . $uri)) {
    return false;
}

// Otherwise, include the index.php from public
require_once $publicPath . '/index.php';
