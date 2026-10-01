<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FitTrack - Account</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: Arial, sans-serif;
        }

        header {
            height: 60px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-bottom: 1px solid #ddd;
            position: relative;
        }

        .menu-button {
            position: absolute;
            left: 20px;
            background: none;
            border: none;
            font-size: 28px;
            cursor: pointer;
        }

        .side-nav {
            position: fixed;
            top: 0;
            left: -260px;
            width: 260px;
            height: 100%;
            background: white;
            box-shadow: 2px 0 10px rgba(0, 0, 0, 0.15);
            transition: left 0.3s ease;
            z-index: 1000;
            padding: 20px;
        }

        .side-nav.open {
            left: 0;
        }

        .close-button {
            background: none;
            border: none;
            font-size: 28px;
            cursor: pointer;
            margin-bottom: 30px;
        }

        .side-nav a {
            display: block;
            padding: 15px 10px;
            text-decoration: none;
            color: #333;
            font-size: 18px;
        }

        .side-nav a:hover {
            background: #f2f2f2;
        }

        main {
            padding: 30px;
        }
    </style>
</head>

<body>

    <header>

        <button class="menu-button" onclick="openMenu()">⋮</button>

        <h1>FitTrack</h1>

    </header>


    <nav class="side-nav" id="sideNav">

        <button class="close-button" onclick="closeMenu()">×</button>

        <a href="dashboard.php">Dashboard</a>
        <a href="account.php">Account</a>
        <a href="weight.php">Weight</a>
        <a href="training.php">Training</a>
        <a href="nutrition.php">Food</a>

    </nav>


    <main>

        <h2>Account</h2>

        <section>
            <h3>Profile</h3>

            <p>Name: Sample User</p>
            <p>Email: sample@example.com</p>
        </section>

        <section>
            <h3>Fitness Goal</h3>

            <p>Start Weight: 63 kg</p>
            <p>Goal Weight: 55 kg</p>
            <p>Goal Date: 2027-01-01</p>
        </section>

    </main>


    <script>

        function openMenu() {
            document.getElementById("sideNav").classList.add("open");
        }

        function closeMenu() {
            document.getElementById("sideNav").classList.remove("open");
        }

        document.addEventListener("click", function(event) {

            const sideNav = document.getElementById("sideNav");
            const menuButton = document.querySelector(".menu-button");

            if (
                sideNav.classList.contains("open") &&
                !sideNav.contains(event.target) &&
                !menuButton.contains(event.target)
            ) {
                closeMenu();
            }

        });

    </script>

</body>
</html>