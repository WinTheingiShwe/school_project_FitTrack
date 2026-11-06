<?php
require_once __DIR__ . '/../includes/auth.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/../includes/db.php';

$userId = (int) $_SESSION['user_id'];
$errorMessage = '';

function e($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

// Login user
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

$initial = mb_strtoupper(mb_substr($user['name'], 0, 1));

// Form values
$nutritionDate = $_SERVER['REQUEST_METHOD'] === 'POST' ? formValue('nutrition_date') : date('Y-m-d');
$foodName = trim(formValue('food_name'));
$calories = trim(formValue('calories'));
$protein = trim(formValue('protein'));

// Manual nutrition input
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrf();

    $date = preg_match('/^\d{4}-\d{2}-\d{2}$/D', $nutritionDate) ? DateTimeImmutable::createFromFormat('!Y-m-d', $nutritionDate) : false;

    if (
        !$date ||
        $date->format('Y-m-d') !== $nutritionDate ||
        (int) $date->format('Y') < 1 || $nutritionDate > date('Y-m-d')
    ) {
        $errorMessage = 'Please enter a valid date.';

    } elseif ($foodName === '' || $calories === '') {
        $errorMessage = 'Please enter all required fields.';

    } elseif (mb_strlen($foodName) > 200 || strpos(formValue('food_name'), chr(0)) !== false) {
        $errorMessage = 'Food name is too long.';

    } elseif (
        !preg_match('/^\d{1,6}(?:\.\d{1,2})?$/D', $calories) ||
        (float) $calories < 0 ||
        (float) $calories > 999999.99
    ) {
        $errorMessage = 'Please enter valid calories.';

    } elseif (
        (isset($_POST['protein']) && !is_string($_POST['protein'])) ||
        $protein !== '' &&
        (
            !preg_match('/^\d{1,6}(?:\.\d{1,2})?$/D', $protein) ||
            (float) $protein < 0 ||
            (float) $protein > 999999.99
        )
    ) {
        $errorMessage = 'Please enter valid protein.';

    } else {

        $stmt = $pdo->prepare("
            INSERT INTO nutrition_records
                (
                    user_id,
                    nutrition_date,
                    food_name,
                    calories,
                    protein,
                    source,
                    store,
                    product_id
                )
            VALUES
                (
                    :user_id,
                    :nutrition_date,
                    :food_name,
                    :calories,
                    :protein,
                    'manual',
                    NULL,
                    NULL
                )
        ");

        $stmt->execute([
            ':user_id' => $userId,
            ':nutrition_date' => $nutritionDate,
            ':food_name' => $foodName,
            ':calories' => $calories,
            ':protein' => $protein !== '' ? $protein : null
        ]);

        header('Location: nutrition.php');
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Add Nutrition | FitTrack</title>

    <link rel="stylesheet" href="../public/css/add_nutrition.css?v=3">
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

        <a href="nutrition.php" class="active">
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

        <span class="sidebar-avatar">
            <?= e($initial) ?>
        </span>

        <div>
            <strong><?= e($user['name']) ?></strong>
            <small>FitTrack Member</small>
        </div>

    </div>

</aside>

<!-- Overlay -->
<div
    class="overlay"
    id="overlay"
    onclick="closeMenu()"
></div>

<!-- Main Content -->
<main class="main">

    <!-- Page Header -->
    <div class="page-header">

        <div class="page-header-text">

            <span class="eyebrow">
                FOOD JOURNAL
            </span>

            <h1>Add Nutrition</h1>

            <p>
                Record your meals manually or explore
                convenience store products.
            </p>

        </div>

        <!-- Back button on the right -->
        <a
            href="nutrition.php"
            class="back-button"
        >
            ← Back to Nutrition
        </a>

    </div>

    <!-- Error Message -->
    <?php if ($errorMessage !== ''): ?>

        <div class="error-message" role="alert">
            <?= e($errorMessage) ?>
        </div>

    <?php endif; ?>

    <!-- Manual Entry -->
    <section class="nutrition-card">

        <div class="card-heading">

            <div class="heading-icon">
                ✎
            </div>

            <div>
                <h2>Manual Entry</h2>

                <p>
                    Enter totals for the amount eaten. Leave protein blank if unknown; enter 0 only when it is known to be zero.
                </p>
            </div>

        </div>

        <form method="POST" class="nutrition-form">
            <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">

            <div class="form-grid">

                <!-- Date -->
                <div class="form-group">

                    <label for="nutrition_date">
                        Date <span class="required">*</span>
                    </label>

                    <input
                        type="date"
                        id="nutrition_date"
                        name="nutrition_date"
                        value="<?= e($nutritionDate) ?>"
                        max="<?= date('Y-m-d') ?>"
                        required
                    >

                </div>

                <!-- Food Name -->
                <div class="form-group">

                    <label for="food_name">
                        Food Name <span class="required">*</span>
                    </label>

                    <input
                        type="text"
                        id="food_name"
                        name="food_name"
                        maxlength="200"
                        value="<?= e($foodName) ?>"
                        placeholder="e.g. Chicken salad"
                        required
                    >

                </div>

                <!-- Calories -->
                <div class="form-group">

                    <label for="calories">
                        Calories (kcal) <span class="required">*</span>
                    </label>

                    <div class="input-wrapper">

                        <input
                            type="number"
                            id="calories"
                            name="calories"
                            min="0"
                            max="999999.99"
                            step="0.01"
                            value="<?= e($calories) ?>"
                            placeholder="e.g. 450"
                            required
                        >

                        <span class="input-unit">
                            kcal
                        </span>

                    </div>

                </div>

                <!-- Protein -->
                <div class="form-group">

                    <label for="protein">
                        Protein (g)
                    </label>

                    <div class="input-wrapper">

                        <input
                            type="number"
                            id="protein"
                            name="protein"
                            min="0"
                            max="999999.99"
                            step="0.01"
                            value="<?= e($protein) ?>"
                            placeholder="e.g. 25"
                        >

                        <span class="input-unit">
                            g
                        </span>

                    </div>

                </div>

            </div>

            <!-- Save Button -->
            <div class="form-footer">

                <p>
                    <span class="required">*</span>
                    Required fields
                </p>

                <button
                    type="submit"
                    name="manual_submit"
                    value="1"
                    class="save-button"
                >
                    + Save Nutrition
                </button>

            </div>

        </form>

    </section>

    <?php if (is_file(__DIR__ . '/convenience_store.php')): ?>
    <!-- Convenience Stores -->
    <section class="nutrition-card">

        <div class="card-heading">

            <div class="heading-icon">
                ▤
            </div>

            <div>
                <h2>Convenience Store Products</h2>

                <p>
                    Select a store to explore food products
                    and their nutrition information.
                </p>
            </div>

        </div>

        <div class="store-grid">

            <!-- FamilyMart -->
            <a
                href="convenience_store.php?store=FamilyMart"
                class="store-card"
            >

                <div class="store-icon familymart">
                    F
                </div>

                <div class="store-info">
                    <h3>FamilyMart</h3>
                    <p>Browse food products</p>
                </div>

                <span class="store-arrow">
                    →
                </span>

            </a>

            <!-- LAWSON -->
            <a
                href="convenience_store.php?store=LAWSON"
                class="store-card"
            >

                <div class="store-icon lawson">
                    L
                </div>

                <div class="store-info">
                    <h3>LAWSON</h3>
                    <p>Browse food products</p>
                </div>

                <span class="store-arrow">
                    →
                </span>

            </a>

            <!-- 7-Eleven -->
            <a
                href="convenience_store.php?store=7-Eleven"
                class="store-card"
            >

                <div class="store-icon seven">
                    7
                </div>

                <div class="store-info">
                    <h3>7-Eleven</h3>
                    <p>Browse food products</p>
                </div>

                <span class="store-arrow">
                    →
                </span>

            </a>

        </div>

    </section>

    <?php endif; ?>
    <div class="bottom-note">
        <span>✦</span>
        Your nutrition entries are saved to your personal FitTrack account.
    </div>

</main>

<script>
function openMenu() {
    document.getElementById('sidebar').classList.add('active');
    document.getElementById('overlay').classList.add('active');
}

function closeMenu() {
    document.getElementById('sidebar').classList.remove('active');
    document.getElementById('overlay').classList.remove('active');
}
</script>

</body>
</html>
