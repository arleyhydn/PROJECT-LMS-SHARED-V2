<?php
require 'inc.php';

$email = '';
$error = '';
$sent = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $result = api('auth', 'forgot-password', [], ['email' => $email]);

    if (!empty($result['ok'])) {
        $sent = true;
    } else {
        $error = $result['error'] ?? 'We could not process that request. Please try again.';
    }
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Forgot Password - FEU Marikina LMS</title>
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
                <h1 class="brand-title anim-fade-up delay-1"><span class="word-your">No</span>Worries, It Happens.</h1>
                <p class="brand-desc anim-fade-up delay-2">
                    Enter your institutional email and we'll send you a link
                    to reset your password securely.
                </p>
            </div>
            <footer class="brand-footer anim-fade-up delay-4">
                Far Eastern University — Marikina · College of Information Technology
            </footer>
        </section>

        <section class="form-panel">
            <div class="form-box anim-fade-up delay-2">
                <?php if ($sent): ?>
                    <div class="success-icon mb-3" aria-hidden="true">✅</div>
                    <h2 class="form-title mb-1">Check your email</h2>
                    <p class="form-subtitle mb-4">
                        If that email exists in our system, password reset instructions have been sent.
                    </p>
                    <a href="index.php" class="btn btn-brand w-100 d-block text-center text-decoration-none">Back to Sign In</a>
                <?php else: ?>
                    <h2 class="form-title mb-1">Forgot your password?</h2>
                    <p class="form-subtitle mb-4">
                        Enter your FEU email and we'll send you reset instructions.
                    </p>

                    <?php if ($error !== ''): ?>
                        <div class="alert alert-soft-error py-2 px-3 mb-3" role="alert"><?= h($error) ?></div>
                    <?php endif; ?>

                    <form method="post" action="forgot-password.php">
                        <div class="mb-4">
                            <label for="email" class="form-label">FEU Email Address</label>
                            <input
                                class="form-control"
                                id="email"
                                name="email"
                                type="email"
                                autocomplete="email"
                                placeholder="yourname@feu.edu.ph"
                                value="<?= h($email) ?>"
                                required
                            >
                        </div>
                        <button type="submit" class="btn btn-brand w-100">Send Reset Link</button>
                    </form>
                <?php endif; ?>

                <p class="form-footer text-center mt-4 mb-0">
                    Remember your password? <a href="index.php" class="link">Sign in</a>
                </p>
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
