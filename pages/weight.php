<?php

require_once __DIR__ . '/../includes/db.php';


/* =========================
   Save / Update Weight
========================= */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $action = $_POST['action'] ?? '';

    /* =========================
       Add Weight
    ========================= */

    if ($action === 'add') {

        $recordDate = $_POST['record_date'] ?? '';
        $weight = $_POST['weight'] ?? '';

        if ($recordDate !== '' && $weight !== '') {

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
                ':user_id' => 2,
                ':record_date' => $recordDate,
                ':weight' => $weight
            ]);
        }
    }


    /* =========================
       Edit Weight
    ========================= */

    if ($action === 'edit') {

        $recordDate = $_POST['record_date'] ?? '';
        $weight = $_POST['weight'] ?? '';

        if ($recordDate !== '' && $weight !== '') {

            $stmt = $pdo->prepare("
                UPDATE daily_records
                SET weight = :weight
                WHERE user_id = :user_id
                  AND record_date = :record_date
            ");

            $stmt->execute([
                ':weight' => $weight,
                ':user_id' => 2,
                ':record_date' => $recordDate
            ]);
        }
    }


    header('Location: weight.php');
    exit;
}


/* =========================
   Get Weight History
========================= */

$stmt = $pdo->prepare("
    SELECT
        record_date,
        weight
    FROM daily_records
    WHERE user_id = 2
      AND weight IS NOT NULL
    ORDER BY record_date DESC
");

$stmt->execute();

$weightRecords = $stmt->fetchAll(PDO::FETCH_ASSOC);

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

</head>


<body>


<!-- =========================
     Header
========================= -->

<header class="header">

    <button
        class="menu-button"
        onclick="openMenu()"
        aria-label="Open menu"
    >
        ☰
    </button>


    <div class="logo">
        FitTrack
    </div>


    <div class="user-name">
        User
    </div>

</header>



<!-- =========================
     Sidebar
========================= -->

<aside
    class="sidebar"
    id="sideNav"
>

    <div class="sidebar-title">
        FitTrack
    </div>


    <nav>

        <a href="dashboard.php">
            Dashboard
        </a>

        <a href="profile.php">
            Profile
        </a>

        <a
            href="weight.php"
            class="active"
        >
            Weight
        </a>

        <a href="training.php">
            Training
        </a>

        <a href="nutrition.php">
            Nutrition
        </a>

    </nav>

</aside>



<!-- =========================
     Overlay
========================= -->

<div
    class="overlay"
    id="overlay"
    onclick="closeMenu()"
></div>



<!-- =========================
     Main
========================= -->

<main class="main">


    <!-- =========================
         Page Header
    ========================= -->

    <div class="page-header">

        <div>

            <h1 class="page-title">
                Weight
            </h1>

            <p class="page-description">
                Record your weight and track your progress over time.
            </p>

        </div>

    </div>



    <!-- =========================
         Record Weight
    ========================= -->

    <section class="weight-card">

        <div class="section-header">

            <h2>
                Record Weight
            </h2>

            <p>
                Add your current weight.
            </p>

        </div>


        <form
            method="POST"
            class="weight-form"
        >

            <input
                type="hidden"
                name="action"
                value="add"
            >


            <div class="form-group">

                <label for="record_date">
                    Date
                </label>

                <input
                    type="date"
                    id="record_date"
                    name="record_date"
                    value="<?= date('Y-m-d') ?>"
                    required
                >

            </div>


            <div class="form-group">

                <label for="weight">
                    Weight (kg)
                </label>

                <input
                    type="number"
                    id="weight"
                    name="weight"
                    min="1"
                    max="300"
                    step="0.1"
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

    </section>



    <!-- =========================
         Weight History
    ========================= -->

    <section class="weight-card history-card">

        <div class="section-header">

            <h2>
                Weight History
            </h2>

            <p>
                Review and edit your previous weight records.
            </p>

        </div>


        <?php if (empty($weightRecords)): ?>

            <div class="empty-history">

                <div class="empty-history-title">
                    No weight records yet
                </div>

                <div class="empty-history-text">
                    Save your first weight record to see it here.
                </div>

            </div>


        <?php else: ?>


            <div class="history-list">


                <?php foreach ($weightRecords as $record): ?>


                    <!-- =========================
                         Normal History Row
                    ========================= -->

                    <div
                        class="history-row"
                        id="row-<?= htmlspecialchars($record['record_date']) ?>"
                    >

                        <span>
                            <?= htmlspecialchars($record['record_date']) ?>
                        </span>


                        <span>
                            <?= htmlspecialchars($record['weight']) ?> kg
                        </span>


                        <button
                            type="button"
                            class="edit-button"
                            onclick="editWeight('<?= htmlspecialchars($record['record_date']) ?>', '<?= htmlspecialchars($record['weight']) ?>')"
                        >
                            Edit
                        </button>

                    </div>


                <?php endforeach; ?>


            </div>


        <?php endif; ?>


    </section>


</main>



<!-- =========================
     JavaScript
========================= -->

<script>

function openMenu() {

    document
        .getElementById("sideNav")
        .classList
        .add("active");

    document
        .getElementById("overlay")
        .classList
        .add("active");
}


function closeMenu() {

    document
        .getElementById("sideNav")
        .classList
        .remove("active");

    document
        .getElementById("overlay")
        .classList
        .remove("active");
}


/* =========================
   Edit Weight
========================= */

function editWeight(date, weight) {

    const row = document.getElementById("row-" + date);

    row.innerHTML = `
        <form method="POST" class="edit-form">

            <input
                type="hidden"
                name="action"
                value="edit"
            >

            <input
                type="hidden"
                name="record_date"
                value="${date}"
            >

            <span class="history-date">
                ${date}
            </span>

            <input
                type="number"
                name="weight"
                value="${weight}"
                min="1"
                max="300"
                step="0.1"
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
                onclick="location.reload()"
            >
                Cancel
            </button>

        </form>
    `;
}

</script>


</body>
</html>