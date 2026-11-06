<?php
require_once __DIR__ . '/../includes/auth.php';


if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/../includes/db.php';

$userId = (int) $_SESSION['user_id'];

// Logged-in user
$stmt = $pdo->prepare("
    SELECT name
    FROM users
    WHERE id = :user_id
");
$stmt->execute([':user_id' => $userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    http_response_code(403);
    exit('User not found.');
}

// Today's nutrition summary
$stmt = $pdo->prepare("
    SELECT
        SUM(calories) AS total_calories,
        CASE WHEN COUNT(protein) = COUNT(*) THEN SUM(protein) ELSE NULL END AS total_protein,
        COUNT(protein) AS protein_count,
        COUNT(*) AS meal_count
    FROM nutrition_records
    WHERE user_id = :user_id
      AND nutrition_date = :today
");

$stmt->execute([':user_id' => $userId, ':today' => date('Y-m-d')]);
$todayNutrition = $stmt->fetch(PDO::FETCH_ASSOC);

// Nutrition history
$stmt = $pdo->prepare("
    SELECT
        nutrition_date,
        food_name,
        calories,
        protein,
        source,
        store
    FROM nutrition_records
    WHERE user_id = :user_id
    ORDER BY nutrition_date DESC, created_at DESC
");

$stmt->execute([':user_id' => $userId]);
$nutritionHistory = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Group nutrition history by date
$groupedHistory = [];

foreach ($nutritionHistory as $record) {
    $date = $record['nutrition_date'];

    if (!isset($groupedHistory[$date])) {
        $groupedHistory[$date] = [
            'items' => [],
            'total_calories' => 0,
            'total_protein' => 0,
            'protein_complete' => true
        ];
    }

    $groupedHistory[$date]['items'][] = $record;
    if ($record['protein'] === null) $groupedHistory[$date]['protein_complete'] = false;

    $groupedHistory[$date]['total_calories']
        += (float) $record['calories'];

    $groupedHistory[$date]['total_protein']
        += (float) ($record['protein'] ?? 0);
}

$totalCalories = $todayNutrition['total_calories'] !== null ? (float) $todayNutrition['total_calories'] : null;
$totalProtein = $todayNutrition['total_protein'] !== null ? (float) $todayNutrition['total_protein'] : null;
$mealCount = (int) $todayNutrition['meal_count'];

function e($value) {
    return htmlspecialchars(
        (string) $value,
        ENT_QUOTES,
        'UTF-8'
    );
}

$initial = mb_strtoupper(mb_substr($user['name'], 0, 1));
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Nutrition | FitTrack</title>

    <link
        rel="stylesheet"
        href="../public/css/nutrition.css?v=2"
    >
</head>

<body>

<!-- Mobile Header -->
<header class="mobile-header">

    <button
        class="menu-button"
        type="button"
        onclick="openMenu()"
        aria-label="Open navigation"
    >
        ☰
    </button>

    <span class="mobile-logo">✦ FitTrack</span>

</header>

<!-- Sidebar -->
<aside class="sidebar" id="sidebar">

    <div class="sidebar-title">✦ FitTrack</div>

    <p class="sidebar-subtitle">
        Your Wellness Workspace
    </p>

    <nav class="sidebar-nav">

        <a href="dashboard.php">
            ▦ Dashboard
        </a>

        <a href="weight.php">
            ⚖ Weight
        </a>

        <a href="training.php">
            ◆ Training
        </a>

        <a
            href="nutrition.php"
            class="active"
            aria-current="page"
        >
            ◈ Nutrition
        </a>

        <?php if (is_file(__DIR__ . '/group_analytics.php')): ?>
        <a href="group_analytics.php">
            ▤ Group Analytics
        </a>
        <?php endif; ?>

        <a href="profile.php">
            ◉ Profile
        </a>

    </nav>

    <div class="sidebar-footer">

        <span class="avatar">
            <?= e($initial) ?>
        </span>

        <div>
            <strong><?= e($user['name']) ?></strong>
            <small>FitTrack Member</small>
        </div>

    </div>

</aside>

<!-- Mobile Overlay -->
<div
    class="overlay"
    id="overlay"
    onclick="closeMenu()"
></div>

<!-- Main Content -->
<main class="main">

    <!-- Page Header -->
    <div class="page-header">

        <div>
            <span class="eyebrow">
                HEALTH & WELLNESS
            </span>

            <h1>Nutrition</h1>

            <p>
                Track your meals, calories,
                and protein intake.
            </p>
        </div>

        <!-- Only Add Nutrition Button -->
        <a
            href="add_nutrition.php"
            class="primary-button"
        >
            + Add Nutrition
        </a>

    </div>

    <!-- Today's Overview -->
    <section class="section-heading">

        <div>
            <h2>Today's Overview</h2>

            <p>
                <?= date('F j, Y') ?>
                · Your personal nutrition summary
            </p>
        </div>

    </section>

    <div class="summary-grid">

        <!-- Calories -->
        <article class="summary-card">

            <div class="summary-top">

                <span class="summary-icon purple">
                    ◈
                </span>

                <span class="summary-tag">
                    TODAY
                </span>

            </div>

            <p class="summary-label">
                Calories Consumed
            </p>

            <div class="summary-value">

                <?= $totalCalories !== null ? number_format($totalCalories, 2) : '—' ?>

                <small>kcal</small>

            </div>

            <p class="summary-note">
                Total calories recorded today
            </p>

        </article>

        <!-- Protein -->
        <article class="summary-card">

            <div class="summary-top">

                <span class="summary-icon pink">
                    ◆
                </span>

                <span class="summary-tag">
                    TODAY
                </span>

            </div>

            <p class="summary-label">
                Protein Intake
            </p>

            <div class="summary-value">

                <?= $totalProtein !== null ? number_format($totalProtein, 2) : '—' ?>

                <small>g</small>

            </div>

            <p class="summary-note">
                <?= (int) $todayNutrition['protein_count'] ?> of <?= $mealCount ?> entries have protein; total shown only when complete
            </p>

        </article>

        <!-- Meals -->
        <article class="summary-card">

            <div class="summary-top">

                <span class="summary-icon blue">
                    ▤
                </span>

                <span class="summary-tag">
                    TODAY
                </span>

            </div>

            <p class="summary-label">
                Food Entries Recorded
            </p>

            <div class="summary-value">

                <?= $mealCount ?>

                <small>entries</small>

            </div>

            <p class="summary-note">
                Number of food entries today
            </p>

        </article>

    </div>

    <!-- Nutrition History -->
    <section class="history-card">

        <div class="card-heading">

            <div>

                <span class="eyebrow">
                    YOUR FOOD JOURNAL
                </span>

                <h2>Nutrition History</h2>

                <p>
                    Review your recorded meals
                    and daily totals.
                </p>

            </div>

            <!-- Add Meal button removed -->

        </div>

        <?php if (empty($groupedHistory)): ?>

            <div class="empty-history">

                <div class="empty-icon">
                    ◈
                </div>

                <h3>
                    No nutrition records yet
                </h3>

                <p>
                    Add your first meal to start
                    tracking your daily nutrition.
                </p>

                <!-- Duplicate button removed -->

            </div>

        <?php else: ?>

            <div class="history-list">

                <?php foreach ($groupedHistory as $date => $day): ?>

                    <div class="history-day">

                        <!-- Date -->
                        <div class="history-date-row">

                            <div>

                                <span class="date-label">
                                    MEAL LOG
                                </span>

                                <h3>
                                    <?= e(
                                        date(
                                            'F j, Y',
                                            strtotime($date)
                                        )
                                    ) ?>
                                </h3>

                            </div>

                            <span class="meal-count">

                                <?= count($day['items']) ?>
                                items

                            </span>

                        </div>

                        <!-- Food Items -->
                        <div class="meal-list">

                            <?php foreach ($day['items'] as $item): ?>

                                <div class="meal-item">

                                    <div class="meal-info">

                                        <div class="food-icon">
                                            ◈
                                        </div>

                                        <div>

                                            <strong>
                                                <?= e($item['food_name']) ?>
                                            </strong>

                                            <span>

                                                <?php if (
                                                    $item['source'] === 'convenience_store'
                                                ): ?>

                                                    <?= e(
                                                        $item['store']
                                                        ?: 'Convenience Store'
                                                    ) ?>

                                                <?php else: ?>

                                                    Manual Entry

                                                <?php endif; ?>

                                            </span>

                                        </div>

                                    </div>

                                    <div class="meal-values">

                                        <strong>

                                            <?= number_format(
                                                (float) $item['calories'],
                                                2
                                            ) ?>

                                            kcal

                                        </strong>

                                        <span>

                                            P
                                            <?= $item['protein'] !== null ? number_format((float) $item['protein'], 2) : 'Unknown' ?>

                                            g

                                        </span>

                                    </div>

                                </div>

                            <?php endforeach; ?>

                        </div>

                        <!-- Daily Total -->
                        <div class="daily-total">

                            <strong>
                                Daily Total
                            </strong>

                            <div>

                                <span>

                                    <?= number_format(
                                        $day['total_calories'],
                                        2
                                    ) ?>

                                    kcal

                                </span>

                                <span>

                                    P
                                    <?= $day['protein_complete'] ? number_format($day['total_protein'], 2) : 'Incomplete' ?>

                                    g

                                </span>

                            </div>

                        </div>

                    </div>

                <?php endforeach; ?>

            </div>

        <?php endif; ?>

    </section>

</main>

<script>

function openMenu() {

    document
        .getElementById('sidebar')
        .classList
        .add('active');

    document
        .getElementById('overlay')
        .classList
        .add('active');
}

function closeMenu() {

    document
        .getElementById('sidebar')
        .classList
        .remove('active');

    document
        .getElementById('overlay')
        .classList
        .remove('active');
}

</script>

</body>
</html>
