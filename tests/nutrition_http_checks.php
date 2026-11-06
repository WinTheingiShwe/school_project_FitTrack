<?php
// Uses only auth_http_test.php's disposable accounts and database schema.
$nutritionGuest = [];
foreach (['nutrition', 'add_nutrition'] as $pageName) {
    checkAuth(requestAuth('/pages/' . $pageName . '.php', $nutritionGuest)['status'] === 302, 'Nutrition requires login');
}
$page = requestAuth('/pages/nutrition.php', $accounts['one']);
checkAuth(str_contains($page['body'], 'No nutrition records yet'), 'Empty nutrition history');
preg_match('/Calories Consumed.*?summary-value">(.*?)<\/div>/s', $page['body'], $metric);
checkAuth(isset($metric[1]) && str_contains($metric[1], '—'), 'Unrecorded calories are not zero');
$meal = ['csrf_token' => tokenAuth(requestAuth('/pages/add_nutrition.php', $accounts['one'])), 'manual_submit' => '1', 'nutrition_date' => date('Y-m-d'), 'food_name' => 'Test lunch', 'calories' => '450.25', 'protein' => '20.50', 'user_id' => '2'];
$bad = $meal; unset($bad['csrf_token']);
checkAuth(requestAuth('/pages/add_nutrition.php', $accounts['one'], $bad)['status'] === 403, 'Nutrition requires CSRF');
checkAuth(requestAuth('/pages/add_nutrition.php', $accounts['one'], $meal)['status'] === 302, 'Valid meal saves');
checkAuth((int) $pdo->query('SELECT user_id FROM nutrition_records')->fetchColumn() === 1, 'Nutrition ownership ignores submitted user ID');
$meal['food_name'] = 'Unknown protein'; $meal['calories'] = '100'; $meal['protein'] = '';
requestAuth('/pages/add_nutrition.php', $accounts['one'], $meal);
$page = requestAuth('/pages/nutrition.php', $accounts['one']);
checkAuth(str_contains($page['body'], '550.25'), 'Daily calories sum multiple foods');
checkAuth(str_contains($page['body'], 'Incomplete') && str_contains($page['body'], 'Unknown'), 'Unknown protein is not a complete daily total');
preg_match('/Protein Intake.*?summary-value">(.*?)<\/div>/s', $page['body'], $metric);
checkAuth(isset($metric[1]) && str_contains($metric[1], '—'), 'Incomplete protein summary is unavailable');
$other = $meal; $other['csrf_token'] = tokenAuth(requestAuth('/pages/add_nutrition.php', $accounts['two']));
$other['food_name'] = 'Private food B'; $other['calories'] = '0'; $other['protein'] = '0';
requestAuth('/pages/add_nutrition.php', $accounts['two'], $other);
checkAuth(!str_contains(requestAuth('/pages/nutrition.php?user_id=2', $accounts['one'])['body'], 'Private food B'), 'Other-user foods are not visible');
$otherPage = requestAuth('/pages/nutrition.php', $accounts['two']);
preg_match('/Protein Intake.*?summary-value">(.*?)<\/div>/s', $otherPage['body'], $metric);
checkAuth(isset($metric[1]) && str_contains($metric[1], '0.00'), 'Recorded zero protein stays zero');
foreach (['nutrition_date' => ['0000-01-01','2024-02-30','2999-01-01',"2024-01-01\0",['x']], 'food_name' => ['',str_repeat('a',201),"x\0y",['x']], 'calories' => ['', '-1', '1000000', '1.234', ['1']], 'protein' => ['-1','1000000','1.234',['1']]] as $field => $invalidValues) {
    foreach ($invalidValues as $invalid) {
        $bad = $meal; $bad[$field] = $invalid;
        $response = requestAuth('/pages/add_nutrition.php', $accounts['one'], $bad);
        checkAuth($response['status'] === 200 && str_contains($response['body'], 'role="alert"'), 'Reject invalid ' . $field);
    }
}
checkAuth((int) $pdo->query('SELECT COUNT(*) FROM nutrition_records WHERE user_id=1')->fetchColumn() === 2, 'Rejected meals do not reach database');
$meal['food_name'] = '<script>alert(1)</script>'; $meal['protein'] = '3.25'; $meal['nutrition_date'] = '2024-01-01';
requestAuth('/pages/add_nutrition.php', $accounts['one'], $meal);
$page = requestAuth('/pages/nutrition.php', $accounts['one']);
checkAuth(!str_contains($page['body'], '<script>alert(1)</script>') && str_contains($page['body'], '&lt;script&gt;'), 'Nutrition names escaped');
checkAuth(str_contains($page['body'], '1 of 2 entries have protein'), 'Today excludes historical records');
$pdo->exec(file_get_contents(__DIR__ . '/../database/migrations/002_nutrition_records.sql'));
checkAuth((int) $pdo->query('SELECT COUNT(*) FROM nutrition_records')->fetchColumn() === 4, 'Nutrition migration preserves records when repeated');
