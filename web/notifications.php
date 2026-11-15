<?php
require __DIR__ . '/inc.php';
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

if (empty($_SESSION['token']) || empty($_SESSION['role'])) {
    http_response_code(401);
    echo json_encode(['notifications' => [], 'unread_messages' => 0]);
    exit;
}

$role = $_SESSION['role'];
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'read_announcement') {
    $announcementId = (int)($_POST['id'] ?? 0);
    if ($announcementId < 1) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'Invalid announcement']);
        exit;
    }
    $result = api('announcement', 'read', [], ['id' => $announcementId]);
    echo json_encode(['ok' => !empty($result['ok'])]);
    exit;
}

$courseIds = '';
if ($role === 'student') {
    $enrollments = api('enrollment', 'mine');
    $courseIds = implode(',', array_map('intval', array_column(is_array($enrollments) ? $enrollments : [], 'course_id')));
} elseif (in_array($role, ['professor', 'instructor'], true)) {
    $courses = api('course', 'mine');
    $courseIds = implode(',', array_map('intval', array_column(is_array($courses) ? $courses : [], 'course_id')));
}

$announcementRows = api('announcement', 'notifications', $role === 'student' ? ['ids' => $courseIds] : []);
if (!is_array($announcementRows) || isset($announcementRows['error'])) $announcementRows = [];
$announcementIds = array_values(array_filter(array_map(
    static fn($item) => (int)($item['announcement_id'] ?? 0),
    $announcementRows
)));
$meta = $announcementIds
    ? api('announcement', 'feed-meta', ['ids' => implode(',', $announcementIds)])
    : ['states' => []];
$readStates = $meta['states'] ?? [];

$notifications = [];
$addNotification = static function (array $item) use (&$notifications): void {
    $key = ($item['type'] ?? '') . ':' . (int)($item['id'] ?? 0);
    if ((int)($item['id'] ?? 0) > 0) $notifications[$key] = $item;
};

foreach ($announcementRows as $item) {
    if (!is_array($item)) continue;
    $id = (int)($item['announcement_id'] ?? 0);
    $addNotification([
        'type' => 'announcement',
        'id' => $id,
        'title' => (string)($item['title'] ?? 'New announcement'),
        'message' => (string)($item['content'] ?? ''),
        'created_at' => (string)($item['created_at'] ?? ''),
        'read' => !empty($readStates[$id]['read']),
        'target' => 'announcements',
        'target_id' => $id,
    ]);
}

$messageData = api('messaging', 'notifications');
if (!is_array($messageData) || isset($messageData['error'])) $messageData = ['count' => 0, 'items' => []];
foreach ($messageData['items'] ?? [] as $item) {
    if (!is_array($item)) continue;
    $body = trim((string)($item['body'] ?? ''));
    $addNotification([
        'type' => 'message',
        'id' => (int)($item['message_id'] ?? 0),
        'title' => trim((string)($item['subject'] ?? '')) ?: 'New message',
        'message' => trim((string)($item['sender_name'] ?? 'Someone')) . ': ' . $body,
        'created_at' => (string)($item['sent_at'] ?? ''),
        'read' => false,
        'target' => 'inbox',
        'target_id' => (int)($item['sender_id'] ?? 0),
    ]);
}

$assignmentRows = [];
if (in_array($role, ['student', 'professor', 'instructor'], true) && $courseIds !== '') {
    $assignmentRows = api('assignment', 'notifications', ['ids' => $courseIds]);
    if (!is_array($assignmentRows) || isset($assignmentRows['error'])) $assignmentRows = [];
}
foreach ($assignmentRows as $item) {
    if (!is_array($item)) continue;
    if ($role === 'student') {
        $kind = strtolower((string)($item['type'] ?? 'assignment')) === 'project' ? 'Project' : 'Assignment';
        $course = trim((string)($item['course_code'] ?? '') . ' ' . (string)($item['course_name'] ?? ''));
        $message = $course !== '' ? $course : 'A new course activity is available';
        if (!empty($item['due_date'])) $message .= ' · Due ' . (string)$item['due_date'];
        $addNotification([
            'type' => 'assignment',
            'id' => (int)($item['assignment_id'] ?? 0),
            'title' => 'New ' . $kind . ': ' . (string)($item['title'] ?? 'Course activity'),
            'message' => $message,
            'created_at' => (string)($item['created_at'] ?? ''),
            'read' => false,
            'target' => 'assignments',
            'target_id' => (int)($item['assignment_id'] ?? 0),
        ]);
    } else {
        $course = trim((string)($item['course_code'] ?? '') . ' ' . (string)($item['course_name'] ?? ''));
        $message = trim((string)($item['student_name'] ?? 'A student')) . ' submitted ' . (string)($item['assignment_title'] ?? 'an assignment');
        if ($course !== '') $message .= ' · ' . $course;
        $addNotification([
            'type' => 'submission',
            'id' => (int)($item['submission_id'] ?? 0),
            'title' => 'New student submission',
            'message' => $message,
            'created_at' => (string)($item['submitted_at'] ?? ''),
            'read' => false,
            'target' => 'submission',
            'target_id' => (int)($item['submission_id'] ?? 0),
        ]);
    }
}

$notifications = array_values($notifications);
usort($notifications, static function (array $left, array $right): int {
    $leftTime = strtotime((string)($left['created_at'] ?? '')) ?: 0;
    $rightTime = strtotime((string)($right['created_at'] ?? '')) ?: 0;
    if ($leftTime !== $rightTime) return $rightTime <=> $leftTime;
    return strcmp((string)($left['type'] ?? ''), (string)($right['type'] ?? ''))
        ?: ((int)($right['id'] ?? 0) <=> (int)($left['id'] ?? 0));
});

echo json_encode([
    'notifications' => array_slice($notifications, 0, 20),
    'unread_messages' => (int)($messageData['count'] ?? 0),
]);
