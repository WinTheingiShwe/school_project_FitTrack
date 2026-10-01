<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FitTrack - Weight</title>
</head>
<body>

    <header>
        <a href="dashboard.php">←</a>
        <h1>Weight</h1>
    </header>

    <main>

        <section>
            <h2>Record Today's Weight</h2>

            <form method="POST">

                <label for="record_date">Date</label>
                <input type="date" id="record_date" name="record_date" required>

                <br><br>

                <label for="weight">Weight (kg)</label>
                <input type="number" id="weight" name="weight" step="0.01" required>

                <br><br>

                <button type="submit">Save Weight</button>

            </form>
        </section>

        <section>
            <h2>Weight History</h2>

            <p>No weight records yet.</p>
        </section>

    </main>

</body>
</html>