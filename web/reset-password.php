<?php
require 'inc.php';

$token = trim($_POST['token'] ?? $_GET['token'] ?? '');
$error = '';
$complete = false;
$requestNewLink = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $token !== '') {
    $password = $_POST['password'] ?? '';
    $confirmation = $_POST['confirm_password'] ?? '';

    if (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';
    } elseif ($password !== $confirmation) {
        $error = 'The passwords do not match.';
    } else {
        $result = api('auth', 'reset-password', [], [
            'token' => $token,
            'password' => $password,
        ]);

        if (!empty($result['ok'])) {
            $complete = true;
        } else {
            $error = $result['error'] ?? 'The reset link may have expired. Request a new one.';
            $requestNewLink = true;
        }
    }
}

$validLink = $token !== '';
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Reset Password - FEU Marikina LMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/auth.css">
</head>
<body>
    <div id="pageLoader" aria-hidden="true"><div class="loader-logo">🎓</div></div>

    <main class="d-flex auth-wrapper">
        <section class="brand-panel">
            <div class="blob-3"></div>
            <div class="brand-header d-flex align-items-center gap-2 anim-fade-up">
                <div class="brand-logo" aria-hidden="true">🎓</div>
                <div>
                    <div class="brand-name">FEU Marikina</div>
                    <div class="brand-sub">TED Learning Portal</div>
                </div>
            </div>
            <div class="brand-hero">
                <div class="gold-badge"></div>
                <h1 class="brand-title anim-fade-up delay-1"><span class="word-your">Almost</span>There.</h1>
                <p class="brand-desc anim-fade-up delay-2">
                    Choose a new password for your account. Make it something
                    secure you haven't used before.
                </p>
            </div>
            <footer class="brand-footer anim-fade-up delay-4">
                Far Eastern University — Marikina · College of Information Technology
            </footer>
        </section>

        <section class="form-panel">
            <div class="form-box anim-fade-up delay-2">
                <?php if ($complete): ?>
                    <div class="success-icon mb-3" aria-hidden="true">🎉</div>
                    <h2 class="form-title mb-1">Password reset!</h2>
                    <p class="form-subtitle mb-4">Your password has been updated. Sign in with your new password.</p>
                    <a href="index.php" class="btn btn-brand w-100 d-block text-center text-decoration-none">Go to Sign In</a>
                <?php elseif (!$validLink): ?>
                    <div class="success-icon mb-3" aria-hidden="true">⚠️</div>
                    <h2 class="form-title mb-1">Invalid or missing link</h2>
                    <p class="form-subtitle mb-4">This password reset link is incomplete. Please request a new one.</p>
                    <a href="forgot-password.php" class="btn btn-brand w-100 d-block text-center text-decoration-none">Request New Link</a>
                <?php else: ?>
                    <h2 class="form-title mb-1">Set a new password</h2>
                    <p class="form-subtitle mb-4">Your new password must be at least 6 characters.</p>

                    <?php if ($error !== ''): ?>
                        <div class="alert alert-soft-error py-2 px-3 mb-3" role="alert"><?= h($error) ?></div>
                    <?php endif; ?>
                    <?php if ($requestNewLink): ?>
                        <p class="mb-3"><a href="forgot-password.php" class="link">Request a new reset link</a></p>
                    <?php endif; ?>

                    <form method="post" action="reset-password.php">
                        <input type="hidden" name="token" value="<?= h($token) ?>">
                        <div class="mb-3">
                            <label for="password" class="form-label">New Password</label>
                            <input
                                class="form-control"
                                id="password"
                                name="password"
                                type="password"
                                autocomplete="new-password"
                                minlength="6"
                                required
                            >
                        </div>
                        <div class="mb-4">
                            <label for="confirm_password" class="form-label">Confirm Password</label>
                            <input
                                class="form-control"
                                id="confirm_password"
                                name="confirm_password"
                                type="password"
                                autocomplete="new-password"
                                minlength="6"
                                required
                            >
                        </div>
                        <button type="submit" class="btn btn-brand w-100">Reset Password</button>
                    </form>
                <?php endif; ?>

                <?php if (!$complete): ?>
                    <p class="form-footer text-center mt-4 mb-0">
                        Remember your password? <a href="index.php" class="link">Sign in</a>
                    </p>
                <?php endif; ?>
            </div>
        </section>
    </main>

    <script>
        window.addEventListener('load', () => {
            window.setTimeout(() => document.getElementById('pageLoader').classList.add('hide'), 350);
        });
    </script>
</body>
</html>
