<?php
require __DIR__ . '/../../shared/lib.php';

function onboardingStepCounts(): array
{
    return ['admin' => 6, 'professor' => 6, 'instructor' => 6, 'student' => 7];
}

function ensureOnboardingStorage(): int
{
    static $launchUserId = null;
    if ($launchUserId !== null) return $launchUserId;

    q('user', 'CREATE TABLE IF NOT EXISTS onboarding_settings (
        setting_key VARCHAR(64) NOT NULL PRIMARY KEY,
        setting_value VARCHAR(255) NOT NULL
    ) ENGINE=InnoDB');
    q('user', 'CREATE TABLE IF NOT EXISTS onboarding_progress (
        user_id INT NOT NULL PRIMARY KEY,
        role VARCHAR(20) NOT NULL,
        current_step INT UNSIGNED NOT NULL DEFAULT 0,
        completed_at DATETIME NULL,
        updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB');
    q('user', "INSERT IGNORE INTO onboarding_settings (setting_key, setting_value) VALUES ('launch_user_id', COALESCE((SELECT MAX(user_id) FROM users), 0))");
    $launchUserId = (int) q('user', "SELECT setting_value FROM onboarding_settings WHERE setting_key='launch_user_id'")->fetchColumn();
    $backfillAt = q('user', "SELECT setting_value FROM onboarding_settings WHERE setting_key='existing_users_backfilled'")->fetchColumn();
    if (!$backfillAt) {
        q(
            'user',
            'INSERT IGNORE INTO onboarding_progress (user_id, role, current_step, completed_at)
             SELECT u.user_id, LOWER(r.role_name), 0, NOW()
             FROM users u JOIN roles r ON r.role_id=u.role_id
             WHERE u.user_id <= ?',
            [$launchUserId]
        );
        q('user', "INSERT IGNORE INTO onboarding_settings (setting_key, setting_value) VALUES ('existing_users_backfilled', NOW())");
    }

    return $launchUserId;
}

$userSelection = '
    SELECT
        u.user_id,
        u.first_name,
        u.last_name,
        u.email,
        u.status,
        LOWER(r.role_name) AS role
    FROM users u
    JOIN roles r ON r.role_id = u.role_id
';

switch ($_GET['action'] ?? '') {
    case 'credentials':
        $user = q(
            'user',
            'SELECT u.user_id, u.password_hash, u.status, LOWER(r.role_name) AS role
             FROM users u
             JOIN roles r ON r.role_id = u.role_id
             WHERE u.email = ?',
            [$_GET['email'] ?? '']
        )->fetch();
        out($user ?: []);

    case 'byids':
        out(q('user', $userSelection . ' WHERE u.user_id IN (' . idlist() . ')')->fetchAll());

    case 'get':
        $user = q(
            'user',
            $userSelection . ' WHERE u.user_id = ?',
            [(int) ($_GET['id'] ?? 0)]
        )->fetch();
        out($user ?: []);

    case 'list':
        $role = $_GET['role'] ?? '';
        $alternateRole = $role === 'professor' ? 'instructor' : 'x';
        out(q(
            'user',
            $userSelection . ' WHERE LOWER(r.role_name) IN (?, ?) ORDER BY u.created_at DESC',
            [$role, $alternateRole]
        )->fetchAll());
}

function requireAdmin(): void
{
    if (($_SERVER['HTTP_X_ROLE'] ?? '') !== 'admin') {
        out(['error' => 'Forbidden.'], 403);
    }
}

switch ($_GET['action'] ?? '') {
    case 'onboarding-status':
        $userId = (int)($_SERVER['HTTP_X_USER'] ?? 0);
        $role = strtolower((string)($_SERVER['HTTP_X_ROLE'] ?? ''));
        if ($role === 'instructor') $role = 'professor';
        $stepCounts = onboardingStepCounts();
        if ($userId < 1 || !isset($stepCounts[$role])) out(['error' => 'Forbidden.'], 403);
        ensureOnboardingStorage();
        q('user', 'INSERT IGNORE INTO onboarding_progress (user_id, role, current_step) VALUES (?, ?, 0)', [$userId, $role]);
        $progress = q('user', 'SELECT role, current_step, completed_at FROM onboarding_progress WHERE user_id=?', [$userId])->fetch();
        if (!$progress) out(['error' => 'Onboarding status unavailable.'], 500);
        $storedRole = strtolower((string)$progress['role']);
        if ($storedRole === 'instructor') $storedRole = 'professor';
        if ($storedRole !== $role) {
            q('user', 'UPDATE onboarding_progress SET role=?, current_step=0, completed_at=NULL WHERE user_id=?', [$role, $userId]);
            $progress = ['role' => $role, 'current_step' => 0, 'completed_at' => null];
        }
        out([
            'required' => empty($progress['completed_at']),
            'current_step' => min((int)$progress['current_step'], $stepCounts[$role] - 1),
            'total_steps' => $stepCounts[$role],
            'completed_at' => $progress['completed_at'],
        ]);

    case 'onboarding-progress':
        $userId = (int)($_SERVER['HTTP_X_USER'] ?? 0);
        $role = strtolower((string)($_SERVER['HTTP_X_ROLE'] ?? ''));
        if ($role === 'instructor') $role = 'professor';
        $stepCounts = onboardingStepCounts();
        if ($userId < 1 || !isset($stepCounts[$role])) out(['error' => 'Forbidden.'], 403);
        ensureOnboardingStorage();
        q('user', 'INSERT IGNORE INTO onboarding_progress (user_id, role, current_step) VALUES (?, ?, 0)', [$userId, $role]);
        $progress = q('user', 'SELECT current_step, completed_at FROM onboarding_progress WHERE user_id=?', [$userId])->fetch();
        if (!$progress || !empty($progress['completed_at'])) out(['error' => 'Walkthrough is already complete.'], 409);
        $currentStep = (int)$progress['current_step'];
        $nextStep = filter_var($_POST['step'] ?? null, FILTER_VALIDATE_INT);
        if ($nextStep === false || $nextStep < 0 || $nextStep >= $stepCounts[$role] || abs($nextStep - $currentStep) > 1) {
            out(['error' => 'Invalid walkthrough step.'], 422);
        }
        q('user', 'UPDATE onboarding_progress SET role=?, current_step=? WHERE user_id=? AND completed_at IS NULL', [$role, $nextStep, $userId]);
        out(['ok' => true, 'current_step' => $nextStep]);

    case 'onboarding-complete':
        $userId = (int)($_SERVER['HTTP_X_USER'] ?? 0);
        $role = strtolower((string)($_SERVER['HTTP_X_ROLE'] ?? ''));
        if ($role === 'instructor') $role = 'professor';
        $stepCounts = onboardingStepCounts();
        if ($userId < 1 || !isset($stepCounts[$role])) out(['error' => 'Forbidden.'], 403);
        ensureOnboardingStorage();
        $progress = q('user', 'SELECT current_step, completed_at FROM onboarding_progress WHERE user_id=?', [$userId])->fetch();
        if (!$progress) out(['error' => 'Walkthrough progress not found.'], 404);
        if ((int)$progress['current_step'] !== $stepCounts[$role] - 1) out(['error' => 'Complete every walkthrough step before finishing.'], 409);
        q('user', 'UPDATE onboarding_progress SET role=?, completed_at=NOW() WHERE user_id=? AND completed_at IS NULL', [$role, $userId]);
        out(['ok' => true]);

    case 'onboarding-restart':
        $userId = (int)($_SERVER['HTTP_X_USER'] ?? 0);
        $role = strtolower((string)($_SERVER['HTTP_X_ROLE'] ?? ''));
        if ($role === 'instructor') $role = 'professor';
        if ($userId < 1 || !isset(onboardingStepCounts()[$role])) out(['error' => 'Forbidden.'], 403);
        ensureOnboardingStorage();
        q('user', 'INSERT INTO onboarding_progress (user_id, role, current_step, completed_at) VALUES (?, ?, 0, NULL) ON DUPLICATE KEY UPDATE role=VALUES(role), current_step=0, completed_at=NULL', [$userId, $role]);
        out(['ok' => true]);

    case 'onboarding-reset-user':
        requireAdmin();
        $targetId = (int)($_POST['user_id'] ?? 0);
        if ($targetId < 1) out(['error' => 'Select a valid user.'], 422);
        ensureOnboardingStorage();
        $target = q('user', 'SELECT LOWER(r.role_name) AS role FROM users u JOIN roles r ON r.role_id=u.role_id WHERE u.user_id=?', [$targetId])->fetch();
        if (!$target) out(['error' => 'User not found.'], 404);
        $targetRole = $target['role'] === 'instructor' ? 'professor' : $target['role'];
        if (!isset(onboardingStepCounts()[$targetRole])) out(['error' => 'This role does not have a walkthrough.'], 422);
        q('user', 'INSERT INTO onboarding_progress (user_id, role, current_step, completed_at) VALUES (?, ?, 0, NULL) ON DUPLICATE KEY UPDATE role=VALUES(role), current_step=0, completed_at=NULL', [$targetId, $targetRole]);
        out(['ok' => true]);

    case 'create':
        requireAdmin();
        $role = $_POST['role'] ?? '';
        $roleNames = $role === 'professor' ? ['professor', 'instructor'] : [$role, 'x'];
        $password = $_POST['password'] ?? '';

        q(
            'user',
            'INSERT INTO users (
                first_name, last_name, email, password_hash, role_id, program_id, status
            ) VALUES (
                ?, ?, ?, ?, (SELECT role_id FROM roles WHERE LOWER(role_name) IN (?, ?) LIMIT 1), 1, \'active\'
            )',
            [
                $_POST['first_name'] ?? '',
                $_POST['last_name'] ?? '',
                $_POST['email'] ?? '',
                password_hash($password !== '' ? $password : 'password123', PASSWORD_DEFAULT),
                $roleNames[0],
                $roleNames[1],
            ]
        );
        out(['ok' => 1]);

    case 'update':
        requireAdmin();
        q(
            'user',
            'UPDATE users
             SET first_name = ?, last_name = ?, email = ?, status = ?, updated_at = NOW()
             WHERE user_id = ?',
            [
                $_POST['first_name'] ?? '',
                $_POST['last_name'] ?? '',
                $_POST['email'] ?? '',
                $_POST['status'] ?? '',
                (int) ($_POST['user_id'] ?? 0),
            ]
        );
        out(['ok' => 1]);

    case 'delete':
        requireAdmin();
        q('user', 'DELETE FROM users WHERE user_id = ?', [(int) ($_POST['user_id'] ?? 0)]);
        out(['ok' => 1]);

    case 'set-password':
        $userId = (int) ($_POST['user_id'] ?? 0);
        $password = $_POST['password'] ?? '';

        if ($userId < 1 || strlen($password) < 6) {
            out(['error' => 'Invalid password update request.'], 422);
        }

        q(
            'user',
            'UPDATE users SET password_hash = ?, updated_at = NOW() WHERE user_id = ?',
            [password_hash($password, PASSWORD_DEFAULT), $userId]
        );
        out(['ok' => 1]);
}

out(['error' => 'Unknown user action.'], 400);
