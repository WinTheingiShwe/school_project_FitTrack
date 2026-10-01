<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FitTrack - Daily Record</title>
</head>
<body>

    <h1>FitTrack</h1>
    <h2>Daily Fitness Record</h2>

    <form method="POST">

        <label for="record_date">Date</label>
        <input type="date" id="record_date" name="record_date" required>

        <br><br>

        <label for="weight">Weight (kg)</label>
        <input type="number" id="weight" name="weight" step="0.01">

        <br><br>

        <label for="training">Training</label>
        <input type="text" id="training" name="training">

        <br><br>

        <label for="calories">Calories (kcal)</label>
        <input type="number" id="calories" name="calories">

        <br><br>

        <label for="protein">Protein (g)</label>
        <input type="number" id="protein" name="protein" step="0.01">

        <br><br>

        <button type="submit">Save Record</button>

    </form>

</body>
</html>