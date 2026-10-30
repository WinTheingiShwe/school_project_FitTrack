<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$userId = (int) $_SESSION['user_id'];
$errors = [];

function e($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_goals'])) {
    requireCsrf();
    $startWeight = trim(formValue('start_weight'));
    $goalWeight = trim(formValue('goal_weight'));
    $startDate = trim(formValue('start_date'));
    $goalDate = trim(formValue('goal_date'));

    foreach ([
        'Starting Weight' => $startWeight,
        'Goal Weight' => $goalWeight
    ] as $label => $value) {
        if ($value !== '' &&
            (!preg_match('/^\d{1,3}(?:\.\d{1,2})?$/D', $value) || (float) $value < 0.01 || (float) $value > 999.99)) {
            $errors[] = "$label must be between 0.01 and 999.99.";
        }
    }

    foreach ([
        'Start Date' => $startDate,
        'Goal Date' => $goalDate
    ] as $label => $value) {
        if ($value !== '') {
            $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
            if (!$date || $date->format('Y-m-d') !== $value || (int) $date->format('Y') < 1) {
                $errors[] = "$label is invalid.";
            }
        }
    }

    if ($startDate !== '' && $goalDate !== '' && $goalDate < $startDate) {
        $errors[] = 'Goal Date must be on or after Start Date.';
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare("
            UPDATE users
            SET start_weight = :start_weight,
                goal_weight = :goal_weight,
                start_date = :start_date,
                goal_date = :goal_date
            WHERE id = :user_id
        ");

        $stmt->execute([
            ':start_weight' => $startWeight !== '' ? $startWeight : null,
            ':goal_weight' => $goalWeight !== '' ? $goalWeight : null,
            ':start_date' => $startDate !== '' ? $startDate : null,
            ':goal_date' => $goalDate !== '' ? $goalDate : null,
            ':user_id' => $userId
        ]);

        header('Location: profile.php?updated=1');
        exit;
    }
}

$stmt = $pdo->prepare("
    SELECT id, name, email, start_weight, goal_weight,
           start_date, goal_date, created_at
    FROM users
    WHERE id = :user_id
");
$stmt->execute([':user_id' => $userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    header('Location: logout.php');
    exit;
}

$initial = mb_strtoupper(mb_substr($user['name'], 0, 1));

$startWeightValue = $startWeight ?? $user['start_weight'] ?? '';
$goalWeightValue = $goalWeight ?? $user['goal_weight'] ?? '';
$startDateValue = $startDate ?? $user['start_date'] ?? '';
$goalDateValue = $goalDate ?? $user['goal_date'] ?? '';

$memberSince = !empty($user['created_at'])
    ? date('M d, Y', strtotime($user['created_at']))
    : 'Not available';

$weightDifference = null;

if ($user['start_weight'] !== null && $user['goal_weight'] !== null) {
    $weightDifference =
        (float) $user['start_weight'] - (float) $user['goal_weight'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile | FitTrack</title>
    <link rel="stylesheet" href="../public/css/profile.css?v=4">
</head>

<body>

<header class="mobile-header">
    <button type="button" class="menu-button"
            onclick="openMenu()" aria-label="Open navigation">
        ☰
    </button>
    <div class="mobile-logo">✦ FitTrack</div>
</header>

<aside class="sidebar" id="sidebar">
    <div class="sidebar-title">✦ FitTrack</div>
    <p class="sidebar-subtitle">Your Wellness Workspace</p>

    <nav class="sidebar-nav">
        <a href="profile.php" class="active" aria-current="page">◉ Profile</a>
    </nav>

    <div class="sidebar-footer">
        <span class="sidebar-avatar"><?= e($initial) ?></span>
        <div>
            <strong><?= e($user['name']) ?></strong>
            <small>FitTrack Member</small>
        </div>
    </div>
</aside>

<div class="overlay" id="overlay" onclick="closeMenu()"></div>

<main class="main">

    <div class="page-header">
        <div>
            <span class="eyebrow">ACCOUNT SETTINGS</span>
            <h1>My Profile</h1>
            <p>Manage your personal information and fitness goals.</p>
        </div>
        <span class="profile-status">
            <span class="status-dot"></span>
            Active Member
        </span>
    </div>

    <section class="profile-hero">
        <div class="hero-avatar"><?= e($initial) ?></div>
        <div class="profile-identity">
            <span class="hero-eyebrow">YOUR FITTRACK ACCOUNT</span>
            <h2><?= e($user['name']) ?></h2>
            <p><?= e($user['email']) ?></p>
            <span class="member-label">✦ FitTrack Member</span>
        </div>
    </section>

    <section class="profile-card">
        <div class="card-heading">
            <div class="heading-icon">◉</div>
            <div>
                <h2>Account Information</h2>
                <p>Your personal account details.</p>
            </div>
        </div>

        <div class="info-grid">
            <div class="info-item">
                <span class="info-label">FULL NAME</span>
                <strong><?= e($user['name']) ?></strong>
            </div>
            <div class="info-item">
                <span class="info-label">EMAIL ADDRESS</span>
                <strong><?= e($user['email']) ?></strong>
            </div>
            <div class="info-item">
                <span class="info-label">MEMBER SINCE</span>
                <strong><?= e($memberSince) ?></strong>
            </div>
        </div>
    </section>

    <section class="profile-card">
        <div class="card-heading">
            <div class="heading-icon">⚖</div>
            <div>
                <h2>Fitness Goals</h2>
                <p>Set your starting weight, target weight, and goal dates.</p>
            </div>
        </div>

        <div class="goal-overview">
            <div class="goal-stat">
                <span>STARTING WEIGHT</span>
                <strong>
                    <?= $user['start_weight'] !== null
                        ? number_format((float) $user['start_weight'], 1)
                        : '—' ?>
                    <small>kg</small>
                </strong>
            </div>

            <div class="goal-stat">
                <span>GOAL WEIGHT</span>
                <strong>
                    <?= $user['goal_weight'] !== null
                        ? number_format((float) $user['goal_weight'], 1)
                        : '—' ?>
                    <small>kg</small>
                </strong>
            </div>

            <div class="goal-stat">
                <span>WEIGHT DIFFERENCE</span>
                <strong>
                    <?= $weightDifference !== null
                        ? number_format(abs($weightDifference), 1)
                        : '—' ?>
                    <small>kg</small>
                </strong>
                <small class="stat-description">
                    <?= $weightDifference === null
                        ? 'Set both weights'
                        : ($weightDifference > 0
                            ? 'Planned weight loss'
                            : ($weightDifference < 0
                                ? 'Planned weight gain'
                                : 'Maintain weight')) ?>
                </small>
            </div>
        </div>

        <?php if (isset($_GET['updated']) && $_GET['updated'] === '1'): ?>
            <div class="success-message" role="status">
                ✓ Your fitness goals have been updated successfully.
            </div>
        <?php endif; ?>

        <?php if (!empty($errors)): ?>
            <div class="error-message" role="alert">
                <?php foreach ($errors as $error): ?>
                    <p><?= e($error) ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <form method="POST" class="goals-form">
            <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
            <div class="form-heading">
                <h3>Edit Fitness Goals</h3>
                <p>Changes will be saved to your personal account.</p>
            </div>

            <div class="form-grid">
                <div class="form-group">
                    <label for="start_weight">Starting Weight (kg)</label>
                    <input
                        type="number"
                        id="start_weight"
                        name="start_weight"
                        min="0.01"
                        max="999.99"
                        step="0.01"
                        value="<?= e($startWeightValue) ?>"
                        placeholder="e.g. 65"
                    >
                </div>

                <div class="form-group">
                    <label for="goal_weight">Goal Weight (kg)</label>
                    <input
                        type="number"
                        id="goal_weight"
                        name="goal_weight"
                        min="0.01"
                        max="999.99"
                        step="0.01"
                        value="<?= e($goalWeightValue) ?>"
                        placeholder="e.g. 55"
                    >
                </div>

                <div class="form-group">
                    <label for="start_date">Start Date</label>
                    <input
                        type="date"
                        id="start_date"
                        name="start_date"
                        value="<?= e($startDateValue) ?>"
                    >
                </div>

                <div class="form-group">
                    <label for="goal_date">Goal Date</label>
                    <input
                        type="date"
                        id="goal_date"
                        name="goal_date"
                        value="<?= e($goalDateValue) ?>"
                    >
                </div>
            </div>

            <div class="form-actions">
                <span>Only your account's fitness goals will be updated.</span>
                <button type="submit" name="save_goals"
                        value="1" class="save-button">
                    Save Changes →
                </button>
            </div>
        </form>
    </section>

    <section class="profile-card account-actions">
        <div class="account-content">
            <div class="heading-icon logout-icon">↗</div>
            <div>
                <h2>Account</h2>
                <p>Sign out of your FitTrack account securely.</p>
            </div>
        </div>

        <form action="logout.php" method="POST">
            <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
            <button type="submit" class="logout-button">
                Sign Out →
            </button>
        </form>
    </section>

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
