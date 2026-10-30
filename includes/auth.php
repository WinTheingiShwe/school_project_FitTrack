<?php
require_once __DIR__ . '/session.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/db.php';
header('Cache-Control: no-store');

$stmt = $pdo->prepare("
    SELECT id, name, email
    FROM users
    WHERE id = :user_id
");

$stmt->execute([
    ':user_id' => (int) $_SESSION['user_id']
]);

$currentUser = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$currentUser) {
    $_SESSION = [];
    session_regenerate_id(true);

    header('Location: login.php');
    exit;
}

$userId = (int) $currentUser['id'];
