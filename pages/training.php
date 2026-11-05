<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$userId = (int) $_SESSION['user_id'];

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

/* =========================
   Get User
========================= */

$stmt = $pdo->prepare("
    SELECT name
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
   Save Training
========================= */

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    requireCsrf();
    $trainingDate = formValue('training_date');
    $training = trim(formValue('training'));
    $durationInput = trim(formValue('duration'));

    $dateObject = preg_match('/^\d{4}-\d{2}-\d{2}$/D', $trainingDate) ? DateTimeImmutable::createFromFormat(
        '!Y-m-d',
        $trainingDate
    ) : false;

    $validDate = $dateObject &&
        $dateObject->format('Y-m-d') === $trainingDate &&
        (int) $dateObject->format('Y') >= 1 && $trainingDate <= date('Y-m-d');

    $validDuration = $durationInput === '' ||
        (
            filter_var($durationInput, FILTER_VALIDATE_INT) !== false &&
            (int)$durationInput >= 1 &&
            (int)$durationInput <= 1440
        );

    if (
        !$validDate ||
        $training === '' ||
        mb_strlen($training) > 100 ||
        !$validDuration ||
        (isset($_POST['duration']) && !is_string($_POST['duration'])) ||
        strpos(formValue('training'), chr(0)) !== false
    ) {
        $error = 'Enter a date up to today, a training name (1–100 characters), and optional duration (1–1440 whole minutes).';
    } else {

        $stmt = $pdo->prepare("
            INSERT INTO training_records
                (user_id, training_date, training, duration)
            VALUES
                (:user_id, :training_date, :training, :duration)
        ");

        $stmt->execute([
            ':user_id' => $userId,
            ':training_date' => $trainingDate,
            ':training' => $training,
            ':duration' => $durationInput !== ''
                ? (int)$durationInput
                : null
        ]);

        header('Location: training.php');
        exit;
    }
}

/* =========================
   Training History
========================= */

$stmt = $pdo->prepare("
    SELECT
        id,
        training_date,
        training,
        duration
    FROM training_records
    WHERE user_id = :user_id
    ORDER BY training_date DESC, id DESC
");

$stmt->execute([':user_id' => $userId]);
$trainingRecords = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* =========================
   Summary
========================= */

$totalWorkouts = count($trainingRecords);
$totalMinutes = 0;
$thisMonthWorkouts = 0;
$thisMonthMinutes = 0;
$thisMonthDays = [];
$thisMonthTimedSessions = 0;

$currentMonth = date('Y-m');

$trainingTypes = [];

foreach ($trainingRecords as $record) {

    $duration = $record['duration'] !== null
        ? (int)$record['duration']
        : 0;

    $totalMinutes += $duration;

    if (substr($record['training_date'], 0, 7) === $currentMonth) {
        $thisMonthWorkouts++;
        $thisMonthDays[$record['training_date']] = true;
        $thisMonthTimedSessions += (int) ($record['duration'] !== null);
        $thisMonthMinutes += $duration;
    }

    $type = $record['training'];

    if (!isset($trainingTypes[$type])) {
        $trainingTypes[$type] = 0;
    }

    $trainingTypes[$type]++;
}

$averageDuration = null;

$recordsWithDuration = array_filter(
    $trainingRecords,
    fn($record) => $record['duration'] !== null
);

if (count($recordsWithDuration) > 0) {
    $averageDuration = round(
        $totalMinutes / count($recordsWithDuration)
    );
}

/* =========================
   Monthly Chart (Last 6 Months)
========================= */

$monthKeys = [];
$chartLabels = [];
$chartCounts = [];
$chartDates = [];

for ($i = 5; $i >= 0; $i--) {
    $month = (new DateTimeImmutable('first day of this month'))
        ->modify("-{$i} months");

    $key = $month->format('Y-m');

    $monthKeys[] = $key;
    $chartLabels[] = $month->format('M Y');
    $chartCounts[$key] = 0;
    $chartDates[$key] = [];
}

foreach ($trainingRecords as $record) {
    $key = substr($record['training_date'], 0, 7);

    if (isset($chartCounts[$key])) {
        $chartCounts[$key]++;
        $chartDates[$key][$record['training_date']] = true;
    }
}

$chartValues = array_values($chartCounts);
$chartDays = array_map('count', array_values($chartDates));

/* =========================
   Top Training Types
========================= */

arsort($trainingTypes);
$topTrainingTypes = array_slice(
    $trainingTypes,
    0,
    5,
    true
);

$typeLabels = array_keys($topTrainingTypes);
$typeValues = array_values($topTrainingTypes);

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>FitTrack - Training</title>

    <link
        rel="stylesheet"
        href="../public/css/training.css"
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

    <div class="mobile-logo">
        ✦ FitTrack
    </div>

</header>

<!-- Sidebar -->
<aside class="sidebar" id="sideNav">

    <div class="sidebar-title">
        ✦ FitTrack
    </div>

    <nav>
        <a href="dashboard.php">▦ Dashboard</a>
        <a href="weight.php">⚖ Weight</a>

        <a
            href="training.php"
            class="active"
            aria-current="page"
        >
            ◆ Training
        </a>

        <a href="nutrition.php">◈ Nutrition</a>
        <?php if (is_file(__DIR__ . '/group_analytics.php')): ?>
            <a href="group_analytics.php">▤ Group Analytics</a>
        <?php endif; ?>
        <a href="profile.php">◉ Profile</a>
    </nav>

</aside>

<div class="overlay" id="overlay"></div>

<!-- Top Header -->
<header class="header">

    <div class="logo">
        Training Overview
    </div>

    <div class="user-name">
        <?= e($userName) ?>
    </div>

</header>

<!-- Main -->
<main class="main">

    <!-- Page Header -->
    <div class="page-header">

        <div>
            <h1 class="page-title">
                Training
            </h1>

            <p class="page-description">
                Record your workouts and track your training progress.
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
                This Month
            </div>

            <div class="summary-value">
                <?= $thisMonthWorkouts ?>
            </div>

            <div class="summary-note">
                Sessions across <?= count($thisMonthDays) ?> training days
            </div>
        </article>

        <article class="summary-card">
            <div class="summary-label">
                Monthly Duration
            </div>

            <div class="summary-value">
                <?= $thisMonthTimedSessions > 0 ? $thisMonthMinutes : '—' ?>
                <small>min</small>
            </div>

            <div class="summary-note">
                <?= $thisMonthTimedSessions ?> of <?= $thisMonthWorkouts ?> sessions have duration
            </div>
        </article>

        <article class="summary-card">
            <div class="summary-label">
                Average Duration
            </div>

            <div class="summary-value">
                <?= $averageDuration !== null ? $averageDuration : '—' ?>
                <small>min</small>
            </div>

            <div class="summary-note">
                All-time · <?= count($recordsWithDuration) ?> timed sessions
            </div>
        </article>

        <article class="summary-card">
            <div class="summary-label">
                Total Workouts
            </div>

            <div class="summary-value">
                <?= $totalWorkouts ?>
            </div>

            <div class="summary-note">
                All recorded sessions
            </div>
        </article>

    </section>

    <!-- Chart Grid -->
    <section class="chart-grid">

        <!-- Monthly Workouts -->
        <article class="training-card">

            <div class="card-header">
                <div>
                    <h2>Monthly Workouts</h2>
                    <p>
                        Sessions and distinct training days · last 6 months (current month incomplete)
                    </p>
                </div>

                <span class="card-icon">▥</span>
            </div>

            <div class="chart-area">
                <canvas id="monthlyChart" role="img" aria-label="Monthly sessions and training days. Values are in the table below."></canvas>
            </div>

        </article>

        <!-- Training Types -->
        <article class="training-card">

            <div class="card-header">
                <div>
                    <h2>Training Breakdown</h2>
                    <p>
                        Top 5 names by session count · all time (other names excluded)
                    </p>
                </div>

                <span class="card-icon">◉</span>
            </div>

            <?php if (empty($typeLabels)): ?>
                <div class="empty-chart">
                    No training data yet
                </div>
            <?php else: ?>
                <div class="chart-area">
                    <canvas id="typeChart"></canvas>
                </div>
            <?php endif; ?>

        </article>

    </section>

    <details class="training-card">
        <summary>Monthly data and calculation notes</summary>
        <p>Two workouts on the same date count as two sessions and one training day. Unknown durations are excluded from the average. A month with no records is not proof of no exercise.</p>
        <table><caption>Last six calendar months</caption><thead><tr><th scope="col">Month</th><th scope="col">Sessions</th><th scope="col">Training days</th></tr></thead><tbody>
        <?php foreach ($chartLabels as $i => $label): ?>
            <tr><th scope="row"><?= e($label) ?></th><td><?= $chartValues[$i] ?></td><td><?= $chartDays[$i] ?></td></tr>
        <?php endforeach; ?>
        </tbody></table>
    </details>

    <!-- Record Training -->
    <section class="training-card form-card">

        <div class="card-header">
            <div>
                <h2>Record Training</h2>
                <p>
                    Add your workout information
                </p>
            </div>

            <span class="card-icon">＋</span>
        </div>

        <form method="POST" class="training-form">
            <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">

            <div class="form-group">
                <label for="training_date">
                    Date
                </label>

                <input
                    type="date"
                    id="training_date"
                    name="training_date"
                    value="<?= date('Y-m-d') ?>"
                    max="<?= date('Y-m-d') ?>"
                    required
                >
            </div>

            <div class="form-group">
                <label for="training">
                    Training
                </label>

                <input
                    type="text"
                    id="training"
                    name="training"
                    maxlength="100"
                    placeholder="Example: Leg Day"
                    required
                >
            </div>

            <div class="form-group">
                <label for="duration">
                    Duration (minutes, optional)
                </label>

                <input
                    type="number"
                    id="duration"
                    name="duration"
                    min="1"
                    max="1440"
                    placeholder="Example: 60"
                >
            </div>

            <button
                type="submit"
                class="save-button"
            >
                Save Training
            </button>

        </form>

    </section>

    <!-- Training History -->
    <section class="training-card history-card">

        <div class="card-header">
            <div>
                <h2>Training History</h2>
                <p>
                    Review your previous workouts
                </p>
            </div>

            <span class="record-count">
                <?= $totalWorkouts ?> Records
            </span>
        </div>

        <?php if (empty($trainingRecords)): ?>

            <div class="empty-history">
                <div class="empty-history-title">
                    No training records yet
                </div>

                <div class="empty-history-text">
                    Save your first workout to see your history here.
                </div>
            </div>

        <?php else: ?>

            <div class="history-list">

                <div class="history-header">
                    <div>Date</div>
                    <div>Training</div>
                    <div>Duration</div>
                </div>

                <?php foreach ($trainingRecords as $record): ?>

                    <div class="history-row">

                        <div class="history-date">
                            <?= e($record['training_date']) ?>
                        </div>

                        <div class="history-training">
                            <?= e($record['training']) ?>
                        </div>

                        <div class="history-duration">
                            <?= $record['duration'] !== null
                                ? e($record['duration']) . ' min'
                                : '—' ?>
                        </div>

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

document.addEventListener('keydown', event => {
    if (event.key === 'Escape') {
        closeMenu();
    }
});

/* =========================
   Monthly Workout Chart
========================= */

const monthLabels = <?= json_encode(
    $chartLabels,
    JSON_HEX_TAG | JSON_HEX_AMP |
    JSON_HEX_APOS | JSON_HEX_QUOT
) ?>;

const monthValues = <?= json_encode(
    $chartValues,
    JSON_NUMERIC_CHECK
) ?>;

if (typeof Chart !== 'undefined') {

    new Chart(document.getElementById('monthlyChart'), {
        type: 'bar',
        data: {
            labels: monthLabels,
            datasets: [{
                label: 'Sessions',
                data: monthValues,
                backgroundColor: '#a78bfa',
                borderRadius: 7,
                maxBarThickness: 36
            }, {
                label: 'Training days',
                data: <?= json_encode($chartDays) ?>,
                backgroundColor: '#158577',
                borderRadius: 7,
                maxBarThickness: 36
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: true, position: 'bottom'
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
                    beginAtZero: true,
                    ticks: {
                        stepSize: 1,
                        precision: 0,
                        color: '#817991'
                    },
                    grid: {
                        color: '#f0ebf8'
                    }
                }
            }
        }
    });

    /* Training Type Chart */

    const typeElement = document.getElementById('typeChart');

    if (typeElement) {

        const typeLabels = <?= json_encode(
            $typeLabels,
            JSON_HEX_TAG | JSON_HEX_AMP |
            JSON_HEX_APOS | JSON_HEX_QUOT
        ) ?>;

        const typeValues = <?= json_encode(
            $typeValues,
            JSON_NUMERIC_CHECK
        ) ?>;

        new Chart(typeElement, {
            type: 'doughnut',
            data: {
                labels: typeLabels,
                datasets: [{
                    data: typeValues,
                    backgroundColor: [
                        '#7c3aed',
                        '#a78bfa',
                        '#c4b5fd',
                        '#ddd6fe',
                        '#6d28d9'
                    ],
                    borderWidth: 2,
                    borderColor: '#ffffff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '68%',
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            color: '#6b607b',
                            boxWidth: 12,
                            padding: 14,
                            font: {
                                size: 11
                            }
                        }
                    }
                }
            }
        });
    }
}
</script>

</body>
</html>
