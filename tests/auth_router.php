<?php
// Only used by auth_http_test.php with a disposable PostgreSQL schema.
$schema = getenv('FITTRACK_AUTH_TEST_SCHEMA');
if (!$schema || !preg_match('/^fittrack_auth_test_[a-f0-9]{12}$/D', $schema)) {
    http_response_code(404);
    exit;
}
ob_start();
require_once __DIR__ . '/../includes/db.php';
ob_end_clean();
$pdo->exec('SET search_path TO ' . $schema);
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$routes = ['/', '/index.php', '/pages/account.php', '/pages/login.php', '/pages/signup.php', '/pages/profile.php', '/pages/logout.php'];
if (!in_array($path, $routes, true)) {
    http_response_code(404);
    exit;
}
require __DIR__ . '/..' . ($path === '/' ? '/index.php' : $path);
