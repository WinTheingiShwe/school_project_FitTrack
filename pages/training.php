<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FitTrack - Training</title>
</head>
<body>

    <header>
        <a href="dashboard.php">←</a>
        <h1>Training</h1>
    </header>

    <main>

        <section>
            <h2>Record Training</h2>

            <form method="POST">

                <label for="training_date">Date</label>
                <input type="date" id="training_date" name="training_date" required>

                <br><br>

                <label for="training">Training</label>
                <input type="text" id="training" name="training"
                       placeholder="Example: Leg Day" required>

                <br><br>

                <label for="duration">Duration (minutes)</label>
                <input type="number" id="duration" name="duration"
                       min="1">

                <br><br>

                <button type="submit">Save Training</button>

            </form>
        </section>

        <section>
            <h2>Training History</h2>

            <p>No training records yet.</p>
        </section>

    </main>

</body>
</html>