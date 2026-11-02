<?php
// Included by auth_http_test.php after creating two isolated test accounts.
$weightGuest = [];
checkAuth(requestAuth('/pages/weight.php', $weightGuest)['status'] === 302, 'Weight requires login');
$weightPage = requestAuth('/pages/weight.php', $accounts['one']);
checkAuth(str_contains($weightPage['body'], 'No weight records yet'), 'Empty history is explained');
$weightForm = ['csrf_token' => tokenAuth($weightPage), 'action' => 'add', 'record_date' => '2024-01-01', 'weight' => '65.25', 'user_id' => '2'];
foreach (['add', 'edit', 'delete'] as $action) {
    $bad = $weightForm; $bad['action'] = $action; unset($bad['csrf_token']);
    checkAuth(requestAuth('/pages/weight.php', $accounts['one'], $bad)['status'] === 403, 'Weight mutations require CSRF: ' . $action);
}
checkAuth(requestAuth('/pages/weight.php', $accounts['one'], $weightForm)['status'] === 302, 'Weight save redirects');
checkAuth((int) $pdo->query('SELECT user_id FROM daily_records')->fetchColumn() === 1, 'Submitted user ID cannot change ownership');
$weightForm['weight'] = '64.75';
requestAuth('/pages/weight.php', $accounts['one'], $weightForm);
checkAuth((int) $pdo->query('SELECT COUNT(*) FROM daily_records')->fetchColumn() === 1, 'Same-day save replaces rather than duplicates');
$weightPage = requestAuth('/pages/weight.php', $accounts['one']);
checkAuth(str_contains($weightPage['body'], '64.75'), 'Saved weight appears in history');
preg_match('/Weight Change.*?summary-value">(.*?)<\/div>/s', $weightPage['body'], $change);
checkAuth(isset($change[1]) && str_contains($change[1], '—'), 'One date has no weight change comparison');

$otherForm = ['csrf_token' => tokenAuth(requestAuth('/pages/weight.php', $accounts['two'])), 'action' => 'add', 'record_date' => '2024-01-01', 'weight' => '88.88'];
requestAuth('/pages/weight.php', $accounts['two'], $otherForm);
$ownHistory = requestAuth('/pages/weight.php?user_id=2', $accounts['one']);
checkAuth(!str_contains($ownHistory['body'], '88.88'), 'URL parameters cannot expose another user history or chart');
$weightForm['action'] = 'edit'; $weightForm['weight'] = '63.50';
requestAuth('/pages/weight.php', $accounts['one'], $weightForm);
checkAuth((float) $pdo->query('SELECT weight FROM daily_records WHERE user_id=1')->fetchColumn() === 63.5, 'Own edit succeeds');
checkAuth((float) $pdo->query('SELECT weight FROM daily_records WHERE user_id=2')->fetchColumn() === 88.88, 'Same-date edit cannot affect another user');
$otherForm['record_date'] = '2024-01-02';
requestAuth('/pages/weight.php', $accounts['two'], $otherForm);
$weightForm['record_date'] = '2024-01-02';
requestAuth('/pages/weight.php', $accounts['one'], $weightForm);
checkAuth((int) $pdo->query("SELECT COUNT(*) FROM daily_records WHERE user_id=1 AND record_date='2024-01-02'")->fetchColumn() === 0, 'Editing a date owned only by another user cannot create a record');
$weightForm['record_date'] = '2024-01-01';
foreach (['0', '301', 'NaN', '65.555', ['65']] as $invalidWeight) {
    $bad = $weightForm; $bad['weight'] = $invalidWeight;
    checkAuth(str_contains(requestAuth('/pages/weight.php', $accounts['one'], $bad)['body'], 'Please enter a date up to today'), 'Invalid weights are rejected');
}
foreach (['2024-02-30', '0000-01-01', '2999-01-01', "2024-01-01\0", ['2024-01-01']] as $invalidDate) {
    $bad = $weightForm; $bad['record_date'] = $invalidDate;
    checkAuth(str_contains(requestAuth('/pages/weight.php', $accounts['one'], $bad)['body'], 'Please enter a date up to today'), 'Invalid dates are rejected without PHP warnings');
}
checkAuth((float) $pdo->query('SELECT weight FROM daily_records WHERE user_id=1')->fetchColumn() === 63.5, 'Rejected requests preserve stored weight');
$pdo->exec("UPDATE daily_records SET calories=1800, training='Walk' WHERE user_id=1");
$weightForm['action'] = 'delete'; unset($weightForm['weight']);
checkAuth(requestAuth('/pages/weight.php', $accounts['one'], $weightForm)['status'] === 302, 'Weight deletion redirects');
$remaining = $pdo->query('SELECT weight, calories, training FROM daily_records WHERE user_id=1')->fetch(PDO::FETCH_ASSOC);
checkAuth($remaining['weight'] === null && (int) $remaining['calories'] === 1800 && $remaining['training'] === 'Walk', 'Weight deletion preserves other daily data');
checkAuth((int) $pdo->query('SELECT COUNT(*) FROM daily_records WHERE user_id=2 AND weight IS NOT NULL')->fetchColumn() === 2, 'Deletion preserves all other-user weights');
checkAuth(str_contains(requestAuth('/pages/weight.php', $accounts['one'])['body'], 'No weight records yet'), 'Deleted weight disappears from history');
$weightForm['action'] = 'add'; $weightForm['weight'] = '64';
requestAuth('/pages/weight.php', $accounts['one'], $weightForm);
$weightForm['record_date'] = '2024-01-02'; $weightForm['weight'] = '63';
requestAuth('/pages/weight.php', $accounts['one'], $weightForm);
$summary = requestAuth('/pages/weight.php', $accounts['one'])['body'];
preg_match('/Weight Change.*?summary-value">(.*?)<\/div>/s', $summary, $change);
checkAuth(isset($change[1]) && str_contains($change[1], '-1.00'), 'Two-date change is latest minus first');
