<?php
// Requires local config/database.php and permission to create a disposable schema.
// Existing application tables and accounts are never used or modified.
ob_start();
require_once __DIR__ . '/../includes/db.php';
ob_end_clean();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$schema = 'fittrack_auth_test_' . bin2hex(random_bytes(6));
$server = null;
$log = tempnam(sys_get_temp_dir(), 'fittrack-auth-');
$sessionDir = sys_get_temp_dir() . '/' . $schema;
mkdir($sessionDir);
$checks = 0;
function checkAuth(bool $condition, string $message): void {
    global $checks;
    if (!$condition) throw new RuntimeException($message);
    $checks++;
}
function requestAuth(string $path, array &$cookies, ?array $form = null): array {
    global $port;
    $headers = ['Connection: close'];
    if ($cookies) $headers[] = 'Cookie: ' . implode('; ', array_map(fn($key, $value) => $key . '=' . $value, array_keys($cookies), $cookies));
    if ($form !== null) $headers[] = 'Content-Type: application/x-www-form-urlencoded';
    $context = stream_context_create(['http' => [
        'method' => $form === null ? 'GET' : 'POST', 'header' => implode("\r\n", $headers),
        'content' => $form === null ? '' : http_build_query($form), 'ignore_errors' => true,
        'follow_location' => 0, 'timeout' => 5,
    ]]);
    $body = file_get_contents('http://127.0.0.1:' . $port . $path, false, $context);
    $responseHeaders = $http_response_header;
    foreach ($responseHeaders as $header) {
        if (preg_match('/^Set-Cookie: ([^=]+)=([^;]*)/i', $header, $match)) $cookies[$match[1]] = $match[2];
    }
    preg_match('/\s(\d{3})\s/', $responseHeaders[0], $match);
    checkAuth(!preg_match('/(?:Warning|Fatal error|Notice):/', $body), 'PHP warning in response');
    return ['status' => (int) $match[1], 'body' => $body, 'headers' => implode("\n", $responseHeaders)];
}
function tokenAuth(array $response): string {
    if (!preg_match('/name="csrf_token" value="([a-f0-9]+)"/', $response['body'], $match)) throw new RuntimeException('CSRF token missing');
    return $match[1];
}
try {
    $pdo->exec('CREATE SCHEMA ' . $schema);
    $pdo->exec('SET search_path TO ' . $schema);
    $pdo->exec('CREATE TABLE users (id SERIAL PRIMARY KEY, name VARCHAR(100) NOT NULL, email VARCHAR(255) UNIQUE NOT NULL, password VARCHAR(255) NOT NULL, start_weight DECIMAL(5,2), goal_weight DECIMAL(5,2), start_date DATE, goal_date DATE, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP)');
    $socket = stream_socket_server('tcp://127.0.0.1:0', $errorCode, $errorMessage);
    if (!$socket) throw new RuntimeException('Cannot allocate test port');
    $address = stream_socket_get_name($socket, false);
    $port = (int) substr(strrchr($address, ':'), 1);
    fclose($socket);
    $env = getenv();
    $env['FITTRACK_AUTH_TEST_SCHEMA'] = $schema;
    $server = proc_open([PHP_BINARY, '-d', 'display_errors=1', '-d', 'session.save_path=' . $sessionDir, '-S', '127.0.0.1:' . $port, 'tests/auth_router.php'], [0 => ['pipe', 'r'], 1 => ['file', $log, 'a'], 2 => ['file', $log, 'a']], $pipes, dirname(__DIR__), $env);
    if (!is_resource($server)) throw new RuntimeException('Cannot start test server');
    fclose($pipes[0]);
    $ready = false;
    for ($attempt = 0; $attempt < 50; $attempt++) {
        $probe = @fsockopen('127.0.0.1', $port, $code, $message, 0.1);
        if ($probe) { fclose($probe); $ready = true; break; }
        usleep(100000);
    }
    checkAuth($ready, 'Test server did not start');
    $guest = [];
    checkAuth(requestAuth('/pages/profile.php', $guest)['status'] === 302, 'Guest must be redirected');
    $accounts = [];
    foreach (['one', 'two'] as $name) {
        $cookies = [];
        $token = tokenAuth(requestAuth('/pages/signup.php', $cookies));
        $form = ['csrf_token' => $token, 'name' => 'Test ' . $name, 'email' => $name . '@example.test', 'password' => 'Test-password-123', 'confirm_password' => 'Test-password-123'];
        $bad = $form; $bad['name'] = str_repeat('a', 101);
        checkAuth(str_contains(requestAuth('/pages/signup.php', $cookies, $bad)['body'], '100 characters'), 'Reject oversized names');
        $created = requestAuth('/pages/signup.php', $cookies, $form);
        checkAuth($created['status'] === 302 && str_contains($created['headers'], 'Location: profile.php'), 'Signup should reach profile');
        $accounts[$name] = $cookies;
    }
    $stored = $pdo->query("SELECT password FROM users WHERE email = 'one@example.test'")->fetchColumn();
    checkAuth($stored !== 'Test-password-123' && password_verify('Test-password-123', $stored), 'Password must be hashed');
    $profile = requestAuth('/pages/profile.php', $accounts['one']);
    checkAuth(str_contains($profile['body'], 'one@example.test') && !str_contains($profile['body'], 'two@example.test'), 'Profile must show only the session account');
    $form = ['csrf_token' => tokenAuth($profile), 'save_goals' => '1', 'start_weight' => '70', 'goal_weight' => '65', 'start_date' => '2026-01-01', 'goal_date' => '2026-06-01'];
    $bad = $form; unset($bad['csrf_token']);
    checkAuth(requestAuth('/pages/profile.php', $accounts['one'], $bad)['status'] === 403, 'Reject missing CSRF token');
    $bad = $form; $bad['goal_weight'] = '1000';
    checkAuth(str_contains(requestAuth('/pages/profile.php', $accounts['one'], $bad)['body'], 'must be between'), 'Reject values outside DB range');
    $bad = $form; $bad['goal_date'] = '2026-02-30';
    checkAuth(str_contains(requestAuth('/pages/profile.php', $accounts['one'], $bad)['body'], 'is invalid'), 'Reject impossible dates');
    $bad = $form; $bad['start_date'] = '0000-01-01';
    checkAuth(str_contains(requestAuth('/pages/profile.php', $accounts['one'], $bad)['body'], 'is invalid'), 'Reject year zero before PostgreSQL write');
    $form['user_id'] = '2';
    checkAuth(requestAuth('/pages/profile.php', $accounts['one'], $form)['status'] === 302, 'Valid profile update redirects');
    $rows = $pdo->query('SELECT email, goal_weight FROM users ORDER BY id')->fetchAll(PDO::FETCH_ASSOC);
    checkAuth((float) $rows[0]['goal_weight'] === 65.0 && $rows[1]['goal_weight'] === null, 'Submitted user ID must not update another account');
    checkAuth(requestAuth('/pages/logout.php', $accounts['one'], [])['status'] === 403, 'Logout needs CSRF token');
    checkAuth(requestAuth('/pages/logout.php', $accounts['one'], ['csrf_token' => $form['csrf_token']])['status'] === 302, 'Logout redirects');
    checkAuth(requestAuth('/pages/profile.php', $accounts['one'])['status'] === 302, 'Logged-out session cannot access profile');
    $login = tokenAuth(requestAuth('/pages/login.php', $accounts['one']));
    $credentials = ['csrf_token' => $login, 'email' => 'one@example.test', 'password' => 'wrong'];
    checkAuth(str_contains(requestAuth('/pages/login.php', $accounts['one'], $credentials)['body'], 'Invalid email or password'), 'Wrong password rejected');
    $oldSession = $accounts['one']['PHPSESSID'];
    $credentials['password'] = 'Test-password-123';
    checkAuth(requestAuth('/pages/login.php', $accounts['one'], $credentials)['status'] === 302, 'Correct password accepted');
    checkAuth($accounts['one']['PHPSESSID'] !== $oldSession, 'Login rotates session ID');
    $pdo->exec("DELETE FROM users WHERE email = 'one@example.test'");
    checkAuth(requestAuth('/pages/profile.php', $accounts['one'])['status'] === 302, 'Deleted account session rejected');
    echo "Authentication HTTP checks passed: {$checks}\n";
} finally {
    if (is_resource($server)) { proc_terminate($server); proc_close($server); }
    $pdo->exec('SET search_path TO public');
    $pdo->exec('DROP SCHEMA IF EXISTS ' . $schema . ' CASCADE');
    foreach (glob($sessionDir . '/sess_*') as $sessionFile) unlink($sessionFile);
    rmdir($sessionDir);
    unlink($log);
}
