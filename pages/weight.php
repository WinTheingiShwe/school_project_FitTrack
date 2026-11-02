<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$userId = (int) $_SESSION['user_id'];

/* =========================
   Logged-in User
========================= */

$stmt = $pdo->prepare("
    SELECT name, start_weight, goal_weight
    FROM users
    WHERE id = :user_id
");
$stmt->execute([':user_id' => $userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    session_unset();
    session_destroy();
    header('Location: login.php');
    exit;
}

$userName = $user['name'];

/* =========================
   Save / Update Weight
========================= */

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrf();
    $action = formValue('action');
    $recordDate = formValue('record_date');
    $weightInput = trim(formValue('weight'));

    $validDate = false;
    $dateObject = preg_match('/^\d{4}-\d{2}-\d{2}$/D', $recordDate) ? DateTimeImmutable::createFromFormat(
        '!Y-m-d',
        $recordDate
    ) : false;

    if (
        $dateObject &&
        $dateObject->format('Y-m-d') === $recordDate &&
        (int) $dateObject->format('Y') >= 1 &&
        $recordDate <= date('Y-m-d')
    ) {
        $validDate = true;
    }

    $validWeight = filter_var(
        $weightInput,
        FILTER_VALIDATE_FLOAT
    );

    if (
        !in_array($action, ['add', 'edit', 'delete'], true) ||
        !$validDate ||
        ($action !== 'delete' && (
            !preg_match('/^\d{1,3}(?:\.\d{1,2})?$/D', $weightInput) ||
            $validWeight === false || $validWeight < 1 || $validWeight > 300
        ))
    ) {
        $error = 'Please enter a date up to today and weight from 1–300 kg (up to two decimal places).';
    } else {
        if ($action === 'add') {
            $stmt = $pdo->prepare("
                INSERT INTO daily_records
                    (user_id, record_date, weight)
                VALUES
                    (:user_id, :record_date, :weight)
                ON CONFLICT (user_id, record_date)
                DO UPDATE SET
                    weight = EXCLUDED.weight
            ");

            $stmt->execute([
                ':user_id' => $userId,
                ':record_date' => $recordDate,
                ':weight' => $validWeight
            ]);
        }

        if ($action === 'edit') {
            $stmt = $pdo->prepare("
                UPDATE daily_records
                SET weight = :weight
                WHERE user_id = :user_id
                  AND record_date = :record_date
            ");

            $stmt->execute([
                ':weight' => $validWeight,
                ':user_id' => $userId,
                ':record_date' => $recordDate
            ]);
        }

        if ($action === 'delete') {
            // daily_records also holds legacy nutrition/training values: remove only weight.
            $stmt = $pdo->prepare('UPDATE daily_records SET weight = NULL WHERE user_id = :user_id AND record_date = :record_date');
            $stmt->execute([':user_id' => $userId, ':record_date' => $recordDate]);
        }

        header('Location: weight.php');
        exit;
    }
}

/* =========================
   Weight History
========================= */

$stmt = $pdo->prepare("
    SELECT record_date, weight
    FROM daily_records
    WHERE user_id = :user_id
      AND weight IS NOT NULL
    ORDER BY record_date DESC
");

$stmt->execute([':user_id' => $userId]);
$weightRecords = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* =========================
   Summary
========================= */

$currentWeight = null;
$firstWeight = null;
$weightChange = null;
$goalWeight = $user['goal_weight'] !== null
    ? (float) $user['goal_weight']
    : null;

if (!empty($weightRecords)) {
    $currentWeight = (float) $weightRecords[0]['weight'];
    $firstWeight = (float) end($weightRecords)['weight'];
    $weightChange = count($weightRecords) >= 2 ? $currentWeight - $firstWeight : null;
}

/* =========================
   Chart Data
========================= */

$chartRecords = array_reverse($weightRecords);

$chartLabels = [];
$chartWeights = [];

foreach ($chartRecords as $record) {
    $chartLabels[] = date(
        'Y-m-d',
        strtotime($record['record_date'])
    );
    $chartWeights[] = (float) $record['weight'];
}

function e($value) {
    return htmlspecialchars(
        (string) $value,
        ENT_QUOTES,
        'UTF-8'
    );
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>FitTrack - Weight</title>

    <link
        rel="stylesheet"
        href="../public/css/weight.css"
    >

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>

<body>

<!-- Mobile Header -->
<header class="mobile-header">
    <button
        type="button"
        class="menu-button"
        id="menuButton"
        aria-label="Open navigation"
        aria-controls="sideNav"
        aria-expanded="false"
    >
        ☰
    </button>

    <div class="mobile-logo">✦ FitTrack</div>
</header>

<!-- Sidebar -->
<aside class="sidebar" id="sideNav">
    <div class="sidebar-title">✦ FitTrack</div>

    <nav>

        <a
            href="weight.php"
            class="active"
            aria-current="page"
        >
            ⚖ Weight
        </a>

        <a href="profile.php">◉ Profile</a>
    </nav>
</aside>

<div class="overlay" id="overlay"></div>

<!-- Top Header -->
<header class="header">
    <div class="logo">Weight Tracking</div>

    <div class="user-name">
        <?= e($userName) ?>
    </div>
</header>

<!-- Main -->
<main class="main">

    <div class="page-header">
        <div>
            <h1 class="page-title">Weight Tracking</h1>
            <p class="page-description">
                Monitor your progress and stay on track
                with your fitness goals.
            </p>
        </div>

        <div class="page-date">
            <?= date('F Y') ?>
        </div>
    </div>

    <?php if ($error !== ''): ?>
        <div class="error-message" role="alert">
            <?= e($error) ?>
        </div>
    <?php endif; ?>

    <!-- Summary Cards -->
    <section class="summary-grid">

        <article class="summary-card">
            <div class="summary-label">
                Current Weight
            </div>

            <div class="summary-value">
                <?= $currentWeight !== null
                    ? number_format($currentWeight, 2)
                    : '—' ?>
                <small>kg</small>
            </div>

            <div class="summary-note">
                Latest recorded weight
            </div>
        </article>

        <article class="summary-card">
            <div class="summary-label">
                Goal Weight
            </div>

            <div class="summary-value">
                <?= $goalWeight !== null
                    ? number_format($goalWeight, 2)
                    : '—' ?>
                <small>kg</small>
            </div>

            <div class="summary-note">
                Your target weight
            </div>
        </article>

        <article class="summary-card">
            <div class="summary-label">
                Weight Change
            </div>

            <div class="summary-value">
                <?php if ($weightChange !== null): ?>
                    <?= $weightChange > 0 ? '+' : '' ?>
                    <?= number_format($weightChange, 2) ?>
                <?php else: ?>
                    —
                <?php endif; ?>
                <small>kg</small>
            </div>

            <div class="summary-note">
                From first to latest record · requires two dates
            </div>
        </article>

        <article class="summary-card">
            <div class="summary-label">
                Total Records
            </div>

            <div class="summary-value">
                <?= count($weightRecords) ?>
            </div>

            <div class="summary-note">
                Weight entries saved
            </div>
        </article>

    </section>

    <!-- Chart + Form -->
    <section class="content-grid">

        <!-- Chart -->
        <article class="weight-card chart-card">
            <div class="section-header">
                <div>
                    <h2>Weight Progress</h2>
                    <p>
                        Your weight trend over time
                    </p>
                </div>

                <span class="card-icon">↗</span>
            </div>

            <?php if (!empty($weightRecords)): ?>
                <div class="chart-area">
                    <canvas id="weightChart" role="img" aria-label="Your weight over time. Exact dates and weights are listed in Weight History below."></canvas>
                </div>
            <?php else: ?>
                <div class="empty-history">
                    <div class="empty-history-title">
                        No weight data yet
                    </div>

                    <div class="empty-history-text">
                        Add your first weight record
                        to display the graph.
                    </div>
                </div>
            <?php endif; ?>
        </article>

        <!-- Form -->
        <article class="weight-card form-card">
            <div class="section-header">
                <div>
                    <h2>Record Weight</h2>
                    <p>One entry per day. Saving the same date replaces its weight.</p>
                </div>

                <span class="card-icon">＋</span>
            </div>

            <form method="POST" class="weight-form">
                <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                <input
                    type="hidden"
                    name="action"
                    value="add"
                >

                <div class="form-group">
                    <label for="record_date">Date</label>

                    <input
                        type="date"
                        id="record_date"
                        name="record_date"
                        value="<?= date('Y-m-d') ?>"
                        max="<?= date('Y-m-d') ?>"
                        required
                    >
                </div>

                <div class="form-group">
                    <label for="weight">Weight (kg)</label>

                    <input
                        type="number"
                        id="weight"
                        name="weight"
                        min="1"
                        max="300"
                        step="0.01"
                        placeholder="Example: 63.2"
                        required
                    >
                </div>

                <button
                    type="submit"
                    class="save-button"
                >
                    Save Weight
                </button>
            </form>
        </article>

    </section>

    <!-- Weight History -->
    <section class="weight-card history-card">

        <div class="section-header">
            <div>
                <h2>Weight History</h2>
                <p>
                    Review and edit your previous records
                </p>
            </div>

            <span class="record-count">
                <?= count($weightRecords) ?> Records
            </span>
        </div>

        <?php if (empty($weightRecords)): ?>

            <div class="empty-history">
                <div class="empty-history-title">
                    No weight records yet
                </div>

                <div class="empty-history-text">
                    Your saved weights will appear here.
                </div>
            </div>

        <?php else: ?>

            <div class="history-list">
                <div class="history-header">
                    <div>Date</div>
                    <div>Weight</div>
                    <div>Action</div>
                </div>

                <?php foreach ($weightRecords as $record): ?>
                    <?php
                        $date = $record['record_date'];
                        $weight = $record['weight'];
                    ?>

                    <div class="history-row">

                        <span class="history-date">
                            <?= e($date) ?>
                        </span>

                        <div class="history-weight">
                            <?= number_format((float)$weight, 2) ?>
                            kg
                        </div>

                        <div class="history-action">
                            <button
                                type="button"
                                class="edit-button"
                                data-date="<?= e($date) ?>"
                                data-weight="<?= e($weight) ?>"
                            >
                                Edit
                            </button>
                            <form method="POST" class="delete-form" onsubmit="return confirm('Delete this weight entry?');">
                                <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="record_date" value="<?= e($date) ?>">
                                <button type="submit" class="delete-button" aria-label="Delete weight for <?= e($date) ?>">Delete</button>
                            </form>
                        </div>
                    </div>

                    <div
                        class="edit-panel"
                        id="edit-<?= e($date) ?>"
                        hidden
                    >
                        <form method="POST" class="edit-form">
                            <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                            <input
                                type="hidden"
                                name="action"
                                value="edit"
                            >

                            <input
                                type="hidden"
                                name="record_date"
                                value="<?= e($date) ?>"
                            >

                            <span class="edit-date">
                                <?= e($date) ?>
                            </span>

                            <input
                                type="number"
                                name="weight"
                                value="<?= e($weight) ?>"
                                min="1"
                                max="300"
                                step="0.01"
                                class="edit-weight-input"
                                required
                            >

                            <span>kg</span>

                            <button
                                type="submit"
                                class="edit-save-button"
                            >
                                Save
                            </button>

                            <button
                                type="button"
                                class="edit-cancel-button"
                            >
                                Cancel
                            </button>
                        </form>
                    </div>

                <?php endforeach; ?>
            </div>

        <?php endif; ?>

    </section>

</main>

<script>
/* =========================
   Mobile Sidebar
========================= */

const menuButton = document.getElementById('menuButton');
const sidebar = document.getElementById('sideNav');
const overlay = document.getElementById('overlay');

function openMenu() {
    sidebar.classList.add('active');
    overlay.classList.add('active');
    menuButton.setAttribute('aria-expanded', 'true');
}

function closeMenu() {
    sidebar.classList.remove('active');
    overlay.classList.remove('active');
    menuButton.setAttribute('aria-expanded', 'false');
}

menuButton.addEventListener('click', () => {
    if (sidebar.classList.contains('active')) {
        closeMenu();
    } else {
        openMenu();
    }
});

overlay.addEventListener('click', closeMenu);

document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape') {
        closeMenu();
    }
});

/* =========================
   Edit Weight
========================= */

document.querySelectorAll('.edit-button').forEach(button => {
    button.addEventListener('click', () => {
        const date = button.dataset.date;
        const panel = document.getElementById('edit-' + date);

        document.querySelectorAll('.edit-panel').forEach(item => {
            item.hidden = true;
        });

        panel.hidden = false;
    });
});

document.querySelectorAll('.edit-cancel-button').forEach(button => {
    button.addEventListener('click', () => {
        button.closest('.edit-panel').hidden = true;
    });
});

/* =========================
   Weight Chart
========================= */

const chartElement = document.getElementById('weightChart');

if (chartElement && typeof Chart !== 'undefined') {
    const labels = <?= json_encode(
        $chartLabels,
        JSON_HEX_TAG | JSON_HEX_AMP |
        JSON_HEX_APOS | JSON_HEX_QUOT
    ) ?>;

    const weights = <?= json_encode(
        $chartWeights,
        JSON_NUMERIC_CHECK
    ) ?>;

    new Chart(chartElement, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [{
                label: 'Weight (kg)',
                data: weights,
                borderColor: '#7c3aed',
                backgroundColor: 'rgba(124, 58, 237, 0.10)',
                fill: true,
                tension: 0.35,
                pointRadius: 4,
                pointHoverRadius: 6,
                pointBackgroundColor: '#7c3aed',
                borderWidth: 3
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    callbacks: {
                        label: context =>
                            context.parsed.y + ' kg'
                    }
                }
            },
            scales: {
                x: {
                    grid: {
                        display: false
                    },
                    ticks: {
                        color: '#817991'
                    }
                },
                y: {
                    ticks: {
                        color: '#817991',
                        callback: value => value + ' kg'
                    },
                    grid: {
                        color: '#f0ebf8'
                    }
                }
            }
        }
    });
}
</script>

</body>
</html>
