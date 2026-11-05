<?php
// Runs inside auth_http_test.php's disposable schema with two test accounts.
$guestTraining = [];
checkAuth(requestAuth('/pages/training.php', $guestTraining)['status'] === 302, 'Training requires login');
$page = requestAuth('/pages/training.php', $accounts['one']);
checkAuth(str_contains($page['body'], 'No training records yet'), 'Training empty state');
$trainingForm = ['csrf_token' => tokenAuth($page), 'training_date' => date('Y-m-d'), 'training' => 'Walk', 'duration' => '', 'user_id' => '2'];
$bad = $trainingForm; unset($bad['csrf_token']);
checkAuth(requestAuth('/pages/training.php', $accounts['one'], $bad)['status'] === 403, 'Training requires CSRF');
checkAuth(requestAuth('/pages/training.php', $accounts['one'], $trainingForm)['status'] === 302, 'Optional duration accepted');
checkAuth((int) $pdo->query('SELECT user_id FROM training_records')->fetchColumn() === 1, 'Training owner comes from session');
$page = requestAuth('/pages/training.php', $accounts['one']);
foreach (['Monthly Duration', 'Average Duration'] as $label) {
    preg_match('/' . $label . '.*?summary-value">(.*?)<\/div>/s', $page['body'], $metric);
    checkAuth(isset($metric[1]) && str_contains($metric[1], '—'), 'Missing duration is not zero: ' . $label);
}
$trainingForm['duration'] = '60';
requestAuth('/pages/training.php', $accounts['one'], $trainingForm);
$page = requestAuth('/pages/training.php', $accounts['one']);
checkAuth(str_contains($page['body'], 'Sessions across 1 training days'), 'Two sessions on one day count as one day');
preg_match('/Average Duration.*?summary-value">(.*?)<\/div>/s', $page['body'], $metric);
checkAuth(isset($metric[1]) && preg_replace('/\s+/', ' ', trim(strip_tags($metric[1]))) === '60 min', 'Average excludes unknown duration');
checkAuth(str_contains($page['body'], '<td>2</td><td>1</td>'), 'Monthly source table separates sessions and days');
$other = $trainingForm; $other['csrf_token'] = tokenAuth(requestAuth('/pages/training.php', $accounts['two']));
$other['training'] = 'Private workout B';
requestAuth('/pages/training.php', $accounts['two'], $other);
checkAuth(!str_contains(requestAuth('/pages/training.php?user_id=2', $accounts['one'])['body'], 'Private workout B'), 'Other-user data excluded from history and charts');
foreach (['0', '-1', '1441', '1.5', ['60']] as $invalid) {
    $bad = $trainingForm; $bad['duration'] = $invalid;
    checkAuth(str_contains(requestAuth('/pages/training.php', $accounts['one'], $bad)['body'], 'Enter a date up to today'), 'Reject invalid duration');
}
foreach (['0000-01-01', '2024-02-30', '2999-01-01', "2024-01-01\0", ['2024-01-01']] as $invalid) {
    $bad = $trainingForm; $bad['training_date'] = $invalid;
    checkAuth(str_contains(requestAuth('/pages/training.php', $accounts['one'], $bad)['body'], 'Enter a date up to today'), 'Reject invalid date');
}
foreach (['', str_repeat('a', 101), "walk\0", ['Walk']] as $invalid) {
    $bad = $trainingForm; $bad['training'] = $invalid;
    checkAuth(str_contains(requestAuth('/pages/training.php', $accounts['one'], $bad)['body'], 'Enter a date up to today'), 'Reject invalid training name');
}
checkAuth((int) $pdo->query('SELECT COUNT(*) FROM training_records WHERE user_id=1')->fetchColumn() === 2, 'Rejected submissions do not insert records');
$trainingForm['training'] = '<script>alert(1)</script>';
$trainingForm['training_date'] = (new DateTimeImmutable('first day of last month'))->format('Y-m-d');
requestAuth('/pages/training.php', $accounts['one'], $trainingForm);
$page = requestAuth('/pages/training.php', $accounts['one']);
checkAuth(!str_contains($page['body'], '<script>alert(1)</script>') && str_contains($page['body'], '&lt;script&gt;'), 'Training names are escaped');
checkAuth(str_contains($page['body'], '2 of 2 sessions have duration') === false && str_contains($page['body'], '1 of 2 sessions have duration'), 'Previous-month session excluded from current month');
$pdo->exec(file_get_contents(__DIR__ . '/../database/migrations/001_training_records.sql'));
checkAuth((int) $pdo->query('SELECT COUNT(*) FROM training_records')->fetchColumn() === 4, 'Repeated migration preserves records');
