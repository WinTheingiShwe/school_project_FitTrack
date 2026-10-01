<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FitTrack - Food</title>
</head>
<body>

    <header>
        <a href="dashboard.php">←</a>
        <h1>Food</h1>
    </header>

    <main>

        <section>
            <h2>Record Daily Nutrition</h2>

            <form method="POST">

                <label for="food_date">Date</label>
                <input type="date" id="food_date" name="food_date" required>

                <br><br>

                <label for="calories">Calories (kcal)</label>
                <input type="number" id="calories" name="calories"
                       min="0" required>

                <br><br>

                <label for="protein">Protein (g)</label>
                <input type="number" id="protein" name="protein"
                       min="0" step="0.01" required>

                <br><br>

                <button type="submit">Save Nutrition</button>

            </form>
        </section>

        <section>
            <h2>Nutrition History</h2>

            <p>No nutrition records yet.</p>
        </section>

    </main>

</body>
</html>