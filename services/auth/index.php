<?php
require __DIR__ . '/../../shared/lib.php';
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../shared/MailService.php';

function ensurePasswordResetsTable(): void
{
    static $ready = false;

    if ($ready) {
        return;
    }

    q(
        'auth',
        'CREATE TABLE IF NOT EXISTS password_resets (
            reset_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            token_hash CHAR(64) NOT NULL UNIQUE,
            expires_at DATETIME NOT NULL,
            used_at DATETIME NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            KEY idx_password_resets_user (user_id),
            KEY idx_password_resets_expiration (expires_at)
        ) ENGINE=InnoDB'
    );

    $ready = true;
}

switch ($_GET['action'] ?? '') {
    case 'login':
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $user = http('user', 'action=credentials&email=' . urlencode($email));
        $valid = !empty($user['user_id'])
            && ($user['status'] ?? '') === 'active'
            && (password_verify($password, $user['password_hash'])
                || hash_equals($user['password_hash'], $password));

        q(
            'auth',
            'INSERT INTO login_logs (user_id, email_attempted, status) VALUES (?, ?, ?)',
            [$user['user_id'] ?? null, $email, $valid ? 'success' : 'failed']
        );

        if (!$valid) {
            out(['error' => 'Invalid email or password.'], 401);
        }

        $token = bin2hex(random_bytes(32));
        q(
            'auth',
            'INSERT INTO sessions (session_id, user_id, expires_at)
             VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 8 HOUR))',
            [$token, $user['user_id']]
        );

        out([
            'token' => $token,
            'user_id' => $user['user_id'],
            'role' => $user['role'],
        ]);

    case 'verify':
        $session = q(
            'auth',
            'SELECT user_id FROM sessions WHERE session_id = ? AND expires_at > NOW()',
            [$_GET['token'] ?? '']
        )->fetch();

        if (!$session) {
            out([], 401);
        }

        $user = http('user', 'action=get&id=' . (int) $session['user_id']);
        out(['user_id' => $user['user_id'], 'role' => $user['role']]);

    case 'logout':
        q('auth', 'DELETE FROM sessions WHERE session_id = ?', [$_GET['token'] ?? '']);
        out(['ok' => 1]);

    case 'forgot-password':
        $email = trim($_POST['email'] ?? '');

        if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            out(['error' => 'Enter a valid email address.'], 422);
        }

        ensurePasswordResetsTable();
        $user = http('user', 'action=credentials&email=' . urlencode($email));

        if ($user === null) {
            out(['error' => 'The account service is unavailable. Please try again.'], 503);
        }

        if (!empty($user['user_id']) && ($user['status'] ?? '') === 'active') {
            $token = bin2hex(random_bytes(32));
            $tokenHash = hash('sha256', $token);

            q(
                'auth',
                'DELETE FROM password_resets WHERE user_id = ? AND used_at IS NULL',
                [(int) $user['user_id']]
            );
            q(
                'auth',
                'INSERT INTO password_resets (user_id, token_hash, expires_at)
                 VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 1 HOUR))',
                [(int) $user['user_id'], $tokenHash]
            );

            $frontendUrl = rtrim($_ENV['FRONTEND_URL'] ?? BASE . '/web', '/');
            $resetLink = $frontendUrl . '/reset-password.php?token=' . rawurlencode($token);
            $mailer = new MailService();

            if (!$mailer->sendPasswordReset($email, $resetLink)) {
                q('auth', 'DELETE FROM password_resets WHERE token_hash = ?', [$tokenHash]);
            }
        }

        out([
            'ok' => true,
            'message' => 'If that email exists in our system, password reset instructions have been sent.',
        ]);

    case 'reset-password':
        $token = trim($_POST['token'] ?? '');
        $password = $_POST['password'] ?? '';

        if ($token === '' || $password === '') {
            out(['error' => 'The reset token and new password are required.'], 422);
        }

        if (strlen($password) < 6) {
            out(['error' => 'Password must be at least 6 characters.'], 422);
        }

        if (!ctype_xdigit($token) || strlen($token) !== 64) {
            out(['error' => 'This reset link is invalid or expired. Request a new one.'], 400);
        }

        ensurePasswordResetsTable();
        $tokenHash = hash('sha256', $token);
        $reset = q(
            'auth',
            'SELECT reset_id, user_id FROM password_resets
             WHERE token_hash = ? AND expires_at > NOW() AND used_at IS NULL',
            [$tokenHash]
        )->fetch();

        if (!$reset) {
            out(['error' => 'This reset link is invalid or expired. Request a new one.'], 400);
        }

        $claimed = q(
            'auth',
            'UPDATE password_resets SET used_at = NOW()
             WHERE reset_id = ? AND expires_at > NOW() AND used_at IS NULL',
            [(int) $reset['reset_id']]
        );

        if ($claimed->rowCount() !== 1) {
            out(['error' => 'This reset link is invalid or expired. Request a new one.'], 400);
        }

        $updatedUser = http(
            'user',
            'action=set-password',
            [],
            [
                'user_id' => (int) $reset['user_id'],
                'password' => $password,
            ]
        );

        if ($updatedUser === null || empty($updatedUser['ok'])) {
            error_log('Password reset failed while updating the user account.');
            q(
                'auth',
                'UPDATE password_resets SET used_at = NULL WHERE reset_id = ?',
                [(int) $reset['reset_id']]
            );
            out(['error' => 'The password could not be updated. Please try again.'], 503);
        }

        out(['ok' => true, 'message' => 'Your password has been updated.']);
}

out(['error' => 'Unknown authentication action.'], 400);
