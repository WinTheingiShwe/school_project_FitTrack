<?php
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/db.php';

$errorMessage = '';
$name = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrf();
    $name = trim(formValue('name'));
    $email = trim(formValue('email'));
    $password = formValue('password');
    $confirmPassword = formValue('confirm_password');

    if ($name === '' || $email === '' || $password === '' || $confirmPassword === '') {
        $errorMessage = 'Please fill in all fields.';
    } elseif (mb_strlen($name) > 100) {
        $errorMessage = 'Name must be 100 characters or fewer.';
    } elseif (strlen($email) > 255 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errorMessage = 'Please enter a valid email address.';
    } elseif (strlen($password) < 8) {
        $errorMessage = 'Password must be at least 8 characters.';
    } elseif (strlen($password) > 72 || strpos($password, chr(0)) !== false) {
        $errorMessage = 'Password must be at most 72 bytes and contain no null characters.';
    } elseif ($password !== $confirmPassword) {
        $errorMessage = 'Passwords do not match.';
    } else {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = :email");
        $stmt->execute([':email' => $email]);

        if ($stmt->fetch()) {
            $errorMessage = 'This email address is already registered.';
        } else {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

            try {
                $stmt = $pdo->prepare("
                    INSERT INTO users (name, email, password)
                    VALUES (:name, :email, :password)
                    RETURNING id
                ");

                $stmt->execute([
                    ':name' => $name,
                    ':email' => $email,
                    ':password' => $hashedPassword
                ]);

                $userId = $stmt->fetchColumn();

                session_regenerate_id(true);
                $_SESSION['user_id'] = (int)$userId;
                $_SESSION['user_name'] = $name;

                header('Location: profile.php');
                exit;
            } catch (PDOException $e) {
                if ($e->getCode() === '23505') {
                    $errorMessage = 'This email address is already registered.';
                } else {
                    error_log($e->getMessage());
                    $errorMessage = 'Registration failed. Please try again.';
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up | FitTrack</title>
    <link rel="stylesheet" href="../public/css/signup.css">
</head>
<body>
    <main class="auth-container">
        <section class="brand-panel">
            <div class="brand-logo">✦ FitTrack</div>
            <div class="brand-content">
                <span class="brand-label">YOUR FITNESS JOURNEY</span>
                <h1>Track progress.<br>Discover insights.<br>Grow stronger.</h1>
                <p>Record your fitness journey and understand your progress through meaningful data.</p>
            </div>
            <p class="brand-footer">Fitness tracking meets data analytics.</p>
        </section>

        <section class="form-panel">
            <div class="form-wrapper">
                <div class="mobile-logo">✦ FitTrack</div>

                <div class="form-heading">
                    <h2>Create an account</h2>
                    <p>Start your fitness journey with FitTrack.</p>
                </div>

                <?php if ($errorMessage !== ''): ?>
                    <div class="error-message">
                        <?= htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8') ?>
                    </div>
                <?php endif; ?>

                <form method="POST" class="signup-form">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrfToken(), ENT_QUOTES, 'UTF-8') ?>">
                    <div class="form-group">
                        <label for="name">Full Name</label>
                        <input type="text" id="name" name="name"
                            placeholder="Enter your full name"
                            value="<?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8') ?>"
                            maxlength="100" autocomplete="name" required>
                    </div>

                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <input type="email" id="email" name="email"
                            placeholder="you@example.com"
                            value="<?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?>"
                            autocomplete="email" required>
                    </div>

                    <div class="form-group">
                        <label for="password">Password</label>
                        <input type="password" id="password" name="password"
                            placeholder="At least 8 characters"
                            minlength="8" autocomplete="new-password" required>
                    </div>

                    <div class="form-group">
                        <label for="confirm_password">Confirm Password</label>
                        <input type="password" id="confirm_password" name="confirm_password"
                            placeholder="Confirm your password"
                            minlength="8" autocomplete="new-password" required>
                    </div>

                    <button type="submit" class="submit-button">
                        Create Account <span>→</span>
                    </button>
                </form>

                <p class="signin-link">
                    Already have an account?
                    <a href="login.php">Sign In</a>
                </p>
            </div>
        </section>
    </main>
</body>
</html>
