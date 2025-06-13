<?php
// router.php

if (php_sapi_name() === 'cli-server') {
    $url  = parse_url($_SERVER['REQUEST_URI']);
    $file = __DIR__ . $url['path'];
    if (is_file($file)) {
        return false; // Serve the requested resource as-is.
    }
}
// Fallback to index.php (CodeIgniter front controller)
require_once __DIR__ . '/public/index.php'; // Adjust path if your index.php is elsewhere
