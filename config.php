<?php
require_once __DIR__ . '/vendor/autoload.php';
Dotenv\Dotenv::createImmutable(__DIR__)->safeLoad();

define('DB_HOST', $_ENV['DB_HOST'] ?? '127.0.0.1');
define('DB_PORT', $_ENV['DB_PORT'] ?? '');
define('DB_USER', $_ENV['DB_USER'] ?? 'root');
define('DB_PASS', $_ENV['DB_PASS'] ?? '');

$documentRoot = realpath($_SERVER['DOCUMENT_ROOT'] ?? '');
$projectRoot = realpath(__DIR__);
$basePath = '/lms';

if ($documentRoot !== false && $projectRoot !== false) {
    $normalizedDocumentRoot = rtrim(str_replace('\\', '/', $documentRoot), '/');
    $normalizedProjectRoot = str_replace('\\', '/', $projectRoot);

    if (stripos($normalizedProjectRoot, $normalizedDocumentRoot . '/') === 0) {
        $relativePath = substr($normalizedProjectRoot, strlen($normalizedDocumentRoot));
        $segments = array_filter(explode('/', trim($relativePath, '/')), 'strlen');
        $basePath = '/' . implode('/', array_map('rawurlencode', $segments));
    }
}

$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
define('BASE', 'http://' . $host . $basePath);
const SERVICES = [
    'auth' => 'lms_auth_svc',
    'user' => 'lms_user_svc',
    'course' => 'lms_course_svc',
    'enrollment' => 'lms_enrollment_svc',
    'announcement' => 'lms_announcement_svc',
    'planning' => 'lms_planning_svc',
    'assignment' => 'lms_assignment_svc',
    'grade' => 'lms_grade_svc',
    'attendance' => 'lms_attendance_svc',
    'messaging' => 'lms_messaging_svc',
    'admin' => 'lms_admin_svc',
];

// Secret shared by gateway and services. CHANGE THIS and keep it private.
const INTERNAL_KEY='9382211e66b5ff2d4846c0008138d408d53c498e4a28213d';
