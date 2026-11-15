<?php
require '../shared/lib.php';

$service = $_GET['service'] ?? '';
$action = $_GET['action'] ?? '';

if (!isset(SERVICES[$service])) {
    out(['error' => 'Unknown service.'], 404);
}

if ($service === 'user' && $action === 'set-password') {
    out(['error' => 'Forbidden.'], 403);
}

$publicAuthActions = ['login', 'forgot-password', 'reset-password'];
$isPublicAuthAction = $service === 'auth' && in_array($action, $publicAuthActions, true);
$headers = [];

if (!$isPublicAuthAction) {
    $session = http(
        'auth',
        'action=verify&token=' . urlencode($_SERVER['HTTP_X_TOKEN'] ?? '')
    );

    if (empty($session['user_id'])) {
        out(['error' => 'Unauthorized.'], 401);
    }

    $headers = [
        'X-User: ' . $session['user_id'],
        'X-Role: ' . $session['role'],
    ];
}

$query = http_build_query(array_diff_key($_GET, ['service' => true]));
$response = http($service, $query, $headers, $_POST ?: null);

if ($response === null) {
    out(['error' => 'The requested service is unavailable.'], 503);
}

out($response);
