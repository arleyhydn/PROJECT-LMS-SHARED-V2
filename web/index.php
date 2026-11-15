<?php
require 'inc.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $result = api('auth', 'login', [], $_POST);

    if (!empty($result['token'])) {
        $_SESSION['token'] = $result['token'];
        $role = strtolower((string) ($result['role'] ?? ''));
        $role = $role === 'instructor' ? 'professor' : $role;
        $_SESSION['role'] = $role;
        $_SESSION['uid'] = $result['user_id'];
        $_SESSION['user'] = api('user', 'get', ['id' => $result['user_id']]);

        if (!is_file(__DIR__ . '/' . $role . '/dashboard.php')) {
            $error = 'The ' . $role . ' pages are not built yet. Log in as a student.';
        } else {
            header('Location: ' . BASE . '/web/' . rawurlencode($role) . '/dashboard.php');
            exit;
        }
    } else {
        $error = $result['error'] ?? 'Login failed. Please try again.';
    }
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sign In - FEU Marikina LMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/auth.css">
</head>
<body>
    <div id="pageLoader" aria-hidden="true">
        <div class="loader-logo">🎓</div>
    </div>

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
                <h1 class="brand-title anim-fade-up delay-1">
                    <span class="word-your">Your</span>Academic Journey Starts Here.
                </h1>
                <p class="brand-desc anim-fade-up delay-2">
                    Access your courses, track your grades, connect with professors,
                    and stay on top of everything — all in one place.
                </p>

                <div class="row g-2 feature-row anim-fade-up delay-3">
                    <div class="col-6"><div class="feature-pill"><span class="emoji">📚</span> 6 Active Courses</div></div>
                    <div class="col-6"><div class="feature-pill"><span class="emoji">✅</span> Smart To-Do List</div></div>
                    <div class="col-6"><div class="feature-pill"><span class="emoji">📊</span> Grade Tracking</div></div>
                    <div class="col-6"><div class="feature-pill"><span class="emoji">📅</span> Weekly Calendar</div></div>
                </div>
            </div>

            <footer class="brand-footer anim-fade-up delay-4">
                Far Eastern University — Marikina · College of Information Technology
            </footer>
        </section>

        <section class="form-panel">
            <div class="form-box anim-fade-up delay-2">
                <h2 class="form-title mb-1">Sign in to your account</h2>
                <p class="form-subtitle mb-4">Use your FEU institutional email to access the portal.</p>

                <?php if ($error !== ''): ?>
                    <div class="alert alert-soft-error py-2 px-3 mb-3" role="alert"><?= h($error) ?></div>
                <?php endif; ?>

                <form method="post" action="index.php">
                    <div class="mb-3">
                        <label for="email" class="form-label">FEU Email Address</label>
                        <input
                            class="form-control"
                            id="email"
                            name="email"
                            type="email"
                            autocomplete="username"
                            placeholder="yourname@feu.edu.ph"
                            value="<?= h($_POST['email'] ?? '') ?>"
                            required
                        >
                    </div>

                    <div class="mb-4">
                        <div class="d-flex justify-content-between align-items-baseline mb-1">
                            <label for="password" class="form-label mb-0">Password</label>
                            <a href="forgot-password.php" class="link">Forgot password?</a>
                        </div>
                        <div class="input-group">
                            <input
                                class="form-control"
                                id="password"
                                name="password"
                                type="password"
                                autocomplete="current-password"
                                placeholder="Enter your password"
                                required
                            >
                            <button type="button" class="btn toggle-password" id="togglePassword" aria-label="Show password">👁</button>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-brand w-100">Sign In</button>
                </form>

                <p class="form-footer text-center mt-4 mb-0">
                    Having trouble? Contact
                    <a href="mailto:it.support@feu.edu.ph" class="link">it.support@feu.edu.ph</a>
                </p>
            </div>
        </section>
    </main>

    <script>
        window.addEventListener('load', () => {
            window.setTimeout(() => document.getElementById('pageLoader').classList.add('hide'), 350);
        });

        document.getElementById('togglePassword').addEventListener('click', (event) => {
            const password = document.getElementById('password');
            const button = event.currentTarget;
            const showPassword = password.type === 'password';
            password.type = showPassword ? 'text' : 'password';
            button.textContent = showPassword ? '🙈' : '👁';
            button.setAttribute('aria-label', showPassword ? 'Hide password' : 'Show password');
        });
    </script>
</body>
</html>
