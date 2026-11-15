<?php
require __DIR__ . '/inc.php';
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Method not allowed']);
    exit;
}
if (empty($_SESSION['token']) || empty($_SESSION['uid']) || empty($_SESSION['role'])) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'error' => 'Not signed in']);
    exit;
}

$action = (string)($_POST['action'] ?? '');
if ($action === 'save_step') {
    $result = api('user', 'onboarding-progress', [], ['step' => (int)($_POST['step'] ?? -1)]);
} elseif ($action === 'complete') {
    $result = api('user', 'onboarding-complete', [], []);
    if (empty($result['error'])) {
        unset($_SESSION['mandatory_walkthrough'], $_SESSION['onboarding_step']);
    }
} elseif ($action === 'restart') {
    $result = api('user', 'onboarding-restart', [], []);
    if (empty($result['error'])) {
        $_SESSION['mandatory_walkthrough'] = true;
        $_SESSION['onboarding_step'] = 0;
    }
} elseif ($action === 'reset_user' && ($_SESSION['role'] ?? '') === 'admin') {
    $result = api('user', 'onboarding-reset-user', [], ['user_id' => (int)($_POST['user_id'] ?? 0)]);
} else {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Invalid action']);
    exit;
}

if (!empty($result['error'])) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => $result['error']]);
    exit;
}

echo json_encode(['ok' => true] + $result);
