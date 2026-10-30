<?php
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/db.php';
// Check whether the session user still exists
if (isset($_SESSION['user_id'])) {

    $stmt = $pdo->prepare("
        SELECT id, name
        FROM users
        WHERE id = :user_id
    ");

    $stmt->execute([
        ':user_id' => (int) $_SESSION['user_id']
    ]);

    $sessionUser = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($sessionUser) {
        $_SESSION['user_name'] = $sessionUser['name'];

        header('Location: profile.php');
        exit;
    }

    // Invalid session
    $_SESSION = [];
    session_regenerate_id(true);
}
$errorMessage = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrf();
    $email = trim(formValue('email'));
    $password = formValue('password');

    if ($email === '' || $password === '') {
        $errorMessage = 'Please enter your email and password.';
    } else {
        $stmt = $pdo->prepare("
            SELECT id, name, password
            FROM users
            WHERE email = :email
        ");
        $stmt->execute([':email' => $email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password'])) {
            session_regenerate_id(true);

            $_SESSION['user_id'] = (int)$user['id'];
            $_SESSION['user_name'] = $user['name'];

            header('Location: profile.php');
            exit;
        }

        $errorMessage = 'Invalid email or password.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In | FitTrack</title>
    <link rel="stylesheet" href="../public/css/signup.css">
</head>
<body>
    <main class="auth-container">
        <section class="brand-panel">
            <div class="brand-logo">✦ FitTrack</div>

            <div class="brand-content">
                <span class="brand-label">WELCOME BACK</span>
                <h1>Your progress.<br>Your insights.<br>Your journey.</h1>
                <p>Sign in to explore your fitness progress and discover insights from your data.</p>
            </div>

            <p class="brand-footer">Fitness tracking meets data analytics.</p>
        </section>

        <section class="form-panel">
            <div class="form-wrapper">
                <div class="mobile-logo">✦ FitTrack</div>

                <div class="form-heading">
                    <h2>Welcome back</h2>
                    <p>Sign in to your FitTrack account.</p>
                </div>

                <?php if ($errorMessage !== ''): ?>
                    <div class="error-message">
                        <?= htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8') ?>
                    </div>
                <?php endif; ?>

                <form method="POST" class="signup-form">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrfToken(), ENT_QUOTES, 'UTF-8') ?>">
                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <input
                            type="email"
                            id="email"
                            name="email"
                            placeholder="you@example.com"
                            value="<?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?>"
                            autocomplete="email"
                            required
                        >
                    </div>

                    <div class="form-group">
                        <label for="password">Password</label>
                        <input
                            type="password"
                            id="password"
                            name="password"
                            placeholder="Enter your password"
                            autocomplete="current-password"
                            required
                        >
                    </div>

                    <button type="submit" class="submit-button">
                        Sign In <span>→</span>
                    </button>
                </form>

                <p class="signin-link">
                    Don't have an account?
                    <a href="signup.php">Create Account</a>
                </p>
            </div>
        </section>
    </main>
</body>
</html>
