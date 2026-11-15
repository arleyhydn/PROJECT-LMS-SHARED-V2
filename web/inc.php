<?php // Web tier (BFF): talks to services ONLY through the gateway
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
require __DIR__.'/../shared/lib.php';
function h($s){return htmlspecialchars((string)$s);}
function api($svc,$act,$p=[],$post=null){
 $script=basename($_SERVER['SCRIPT_NAME']??'');
 $isOnboardingAction=$svc==='user'&&strpos($act,'onboarding-')===0;
 if(!empty($_SESSION['token'])&&$svc!=='auth'&&!$isOnboardingAction){
    $onboarding=onboarding_status_for_request();
    if(!is_array($onboarding)||isset($onboarding['error']))return ['error'=>'Onboarding status could not be verified.'];
    if(!empty($onboarding['required'])&&($post!==null||!in_array($script,['dashboard.php','index.php'],true)))return ['error'=>'Complete the required walkthrough before using this feature.'];
 }
 $ch=curl_init(BASE.'/gateway/index.php?'.http_build_query(['service'=>$svc,'action'=>$act]+$p));
 $o=[CURLOPT_RETURNTRANSFER=>1,CURLOPT_HTTPHEADER=>['X-Token: '.($_SESSION['token']??'')],CURLOPT_CONNECTTIMEOUT=>4,CURLOPT_TIMEOUT=>10];
 if($post!==null){$o[CURLOPT_POST]=1;$o[CURLOPT_POSTFIELDS]=http_build_query($post);}
 curl_setopt_array($ch,$o);$body=curl_exec($ch);$curlError=curl_error($ch);$status=(int)curl_getinfo($ch,CURLINFO_HTTP_CODE);curl_close($ch);
 if($body===false||$curlError!==''){error_log('LMS API '.$svc.'/'.$act.' failed: '.$curlError);return ['error'=>'The LMS service could not be reached.'];}
 $decoded=json_decode($body,true);
 if(!is_array($decoded)){error_log('LMS API '.$svc.'/'.$act.' returned invalid JSON (HTTP '.$status.').');return ['error'=>'The LMS service returned an invalid response.'];}
 if($status>=400&&!isset($decoded['error']))$decoded['error']='The LMS service returned HTTP '.$status.'.';
 return $decoded;
}
function onboarding_status_for_request(){static $state=null;if($state===null)$state=api('user','onboarding-status');return $state;}
function guard($role)
{
    if (($_SESSION['role'] ?? '') !== $role) {
        header('Location: ' . BASE . '/web/index.php');
        exit;
    }

    $state = onboarding_status_for_request();
    if (!is_array($state) || isset($state['error'])) {
        http_response_code(503);
        exit('The onboarding status could not be verified. Please refresh or contact support.');
    }

    if (!empty($state['required'])) {
        $_SESSION['mandatory_walkthrough'] = true;
        $_SESSION['onboarding_step'] = max(0, (int)($state['current_step'] ?? 0));
        if (basename($_SERVER['SCRIPT_NAME'] ?? '') !== 'dashboard.php') {
            header('Location: ' . BASE . '/web/' . rawurlencode($role) . '/dashboard.php');
            exit;
        }
        return;
    }

    unset($_SESSION['mandatory_walkthrough'], $_SESSION['onboarding_step']);
}

function onboarding_steps($role)
{
    $steps = [
        'admin' => [
            ['title' => 'Dashboard Overview', 'description' => 'Review learner totals, faculty, active courses, and requests from your dashboard.', 'target' => '.admin-stats-grid'],
            ['title' => 'User Management', 'description' => 'Manage student and professor accounts, account status, and profile details.', 'target' => '[data-tour-key="users"]'],
            ['title' => 'Enrollment Management', 'description' => 'Review enrollment requests and manage course rosters from Course Assignment.', 'target' => '[data-tour-key="courses"]'],
            ['title' => 'Course Assignment', 'description' => 'Assign professors to courses and enroll or remove students.', 'target' => '[data-tour-key="courses"]'],
            ['title' => 'Reports & Analytics', 'description' => 'Use the dashboard overview cards and recent activity to monitor LMS activity.', 'target' => '.admin-stats-grid'],
            ['title' => 'Notifications', 'description' => 'Open the notification bell to review new announcements and messages.', 'target' => '#notificationToggle'],
        ],
        'professor' => [
            ['title' => 'Dashboard Overview', 'description' => 'Review today’s classes, submissions waiting for grading, messages, and students.', 'target' => '.prof-stats-grid'],
            ['title' => 'Attendance Management', 'description' => 'Open Attendance to record and review attendance for your courses.', 'target' => '[data-tour-key="attendance"]'],
            ['title' => 'Course Management', 'description' => 'Your assigned courses and class rosters are summarized in My Courses.', 'target' => '.prof-main-grid .prof-card:first-child'],
            ['title' => 'Grade Management', 'description' => 'Open Grades to publish coursework, review submissions, and manage grade records.', 'target' => '[data-tour-key="grades"]'],
            ['title' => 'Announcements', 'description' => 'Post course updates and review announcements from your dashboard or Announcements.', 'target' => '[data-tour-key="announcements"]'],
            ['title' => 'Notifications', 'description' => 'Open the notification bell to review messages, announcements, and student submissions.', 'target' => '#notificationToggle'],
        ],
        'student' => [
            ['title' => 'Dashboard Overview', 'description' => 'Your dashboard brings together today’s classes, tasks, messages, and course progress.', 'target' => '.stats-grid'],
            ['title' => 'Courses', 'description' => 'Open Courses to view enrolled subjects, schedules, and course progress.', 'target' => '[data-tour-key="courses"]'],
            ['title' => 'Attendance', 'description' => 'Check attendance records and your overall attendance rate.', 'target' => '[data-tour-key="attendance"]'],
            ['title' => 'Grades', 'description' => 'Review assignment scores and course grade calculations.', 'target' => '[data-tour-key="grades"]'],
            ['title' => 'Calendar', 'description' => 'Use Calendar to review your schedule by day and month.', 'target' => '[data-tour-key="calendar"]'],
            ['title' => 'To-Do List', 'description' => 'Track upcoming work and update your task list from To-Do.', 'target' => '[data-tour-key="todo"]'],
            ['title' => 'Notifications', 'description' => 'Open the notification bell for new messages, announcements, and assignments.', 'target' => '#notificationToggle'],
        ],
    ];

    return $steps[$role] ?? [];
}

function top($title, $active)
{
    $user = $_SESSION['user'] ?? [];
    $role = $_SESSION['role'] ?? 'student';
    $icons = [
        'dashboard' => '<path d="M3 10.5 12 3l9 7.5V20a1 1 0 0 1-1 1h-5v-6H9v6H4a1 1 0 0 1-1-1z"/>',
        'announcements' => '<path d="m3 11 18-5v12L3 14v-3z"/><path d="M11.6 16.8a3 3 0 1 1-5.8-1.6"/>',
        'assignments' => '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6M8 13h8M8 17h8"/>',
        'todo' => '<path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><path d="m9 11 3 3L22 4"/>',
        'calendar' => '<rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/>',
        'courses' => '<path d="M2 4h6a4 4 0 0 1 4 4v13a3 3 0 0 0-3-3H2z"/><path d="M22 4h-6a4 4 0 0 0-4 4v13a3 3 0 0 1 3-3h7z"/>',
        'grades' => '<path d="M12 20V10M18 20V4M6 20v-4"/>',
        'attendance' => '<rect x="8" y="2" width="8" height="4" rx="1"/><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/><path d="M12 11h4M12 16h4M8 11h.01M8 16h.01"/>',
        'inbox' => '<path d="M22 12h-6l-2 3h-4l-2-3H2"/><path d="M5.45 5.11 2 12v6a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-6l-3.45-6.89A2 2 0 0 0 16.76 4H7.24a2 2 0 0 0-1.79 1.11z"/>',
        'users' => '<path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/>',
        'settings' => '<circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33h0A1.65 1.65 0 0 0 14 21v.09a2 2 0 1 1-4 0V21a1.65 1.65 0 0 0-1.08-1.54 1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82A1.65 1.65 0 0 0 3.09 14H3a2 2 0 1 1 0-4h.09a1.65 1.65 0 0 0 1.51-1.08 1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33A1.65 1.65 0 0 0 10 3.09V3a2 2 0 1 1 4 0v.09a1.65 1.65 0 0 0 1.08 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82A1.65 1.65 0 0 0 20.91 10H21a2 2 0 1 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1.08z"/>',
        'logout' => '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path d="m16 17 5-5-5-5M21 12H9"/>',
    ];
    $navByRole = [
        'student' => [
            'dashboard' => 'Dashboard', 'announcements' => 'Announcements', 'assignments' => 'Assignments',
            'calendar' => 'Calendar', 'courses' => 'Courses', 'grades' => 'Grades',
            'attendance' => 'Attendance', 'inbox' => 'Inbox', 'todo' => 'To-Do', 'settings' => 'Settings',
        ],
        'professor' => [
            'dashboard' => 'Dashboard', 'announcements' => 'Announcements', 'grades' => 'Grades',
            'attendance' => 'Attendance', 'inbox' => 'Inbox', 'settings' => 'Settings',
        ],
        'admin' => [
            'dashboard' => 'Dashboard', 'users' => 'Users', 'courses' => 'Courses',
            'announcements' => 'Announcements', 'inbox' => 'Inbox', 'settings' => 'Settings',
        ],
    ];
    $nav = $navByRole[$role] ?? $navByRole['student'];
    $firstName = (string) ($user['first_name'] ?? 'User');
    $lastName = (string) ($user['last_name'] ?? '');
    $initial = strtoupper(substr($firstName, 0, 1));
    $walkthroughRequired = !empty($_SESSION['mandatory_walkthrough']) && basename($_SERVER['SCRIPT_NAME'] ?? '') === 'dashboard.php';
    $walkthroughSteps = $walkthroughRequired ? onboarding_steps($role) : [];
    $walkthroughStep = min((int)($_SESSION['onboarding_step'] ?? 0), max(0, count($walkthroughSteps) - 1));
    ?>
    <!doctype html>
    <html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title><?= h($title) ?> · FEU TED LMS</title>
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet" media="print" onload="this.media='all'">
        <noscript><link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet"></noscript>
        <link rel="stylesheet" href="<?= BASE ?>/web/assets/style.css?v=<?= filemtime(__DIR__ . '/assets/style.css') ?>">
    </head>
    <body class="<?= $walkthroughRequired ? 'onboarding-required' : '' ?>" data-user-id="<?= (int) ($_SESSION['uid'] ?? 0) ?>">
        <div class="page-scroll-indicator" aria-hidden="true"><span id="pageScrollProgress"></span></div>
        <aside class="app-sidebar" id="appSidebar" aria-label="Main navigation">
            <a class="app-brand" href="<?= BASE ?>/web/<?= h($role) ?>/dashboard.php" aria-label="FEU TED LMS home">
                <span class="app-logo" aria-hidden="true">
                    <svg viewBox="0 0 24 24"><path d="m22 10-10-5L2 10l10 5z"/><path d="M6 12v5c3 3 9 3 12 0v-5"/></svg>
                </span>
                <span class="app-brand-copy"><strong>FEU Marikina</strong><small>TED Learning Portal</small></span>
            </a>

            <button class="app-sidebar-toggle" type="button" aria-pressed="false" aria-label="Keep sidebar expanded" title="Keep sidebar expanded">
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m9 18 6-6-6-6"/></svg>
                <span class="app-nav-label">Expand menu</span>
            </button>

            <nav class="app-nav">
                <?php foreach ($nav as $key => $label): ?>
                    <a class="app-nav-link <?= $key === $active ? 'is-active' : '' ?>"
                       href="<?= h($key) ?>.php" <?= $key === $active ? 'aria-current="page"' : '' ?>
                              title="<?= h($label) ?>" data-tour-key="<?= h($key) ?>">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><?= $icons[$key] ?></svg>
                        <span class="app-nav-label"><?= h($label) ?></span>
                    </a>
                <?php endforeach; ?>
            </nav>

            <a class="app-nav-link app-logout" href="../logout.php" title="Log out">
                <svg viewBox="0 0 24 24" aria-hidden="true"><?= $icons['logout'] ?></svg>
                <span class="app-nav-label">Log out</span>
            </a>
        </aside>

        <main class="app-main">
            <header class="app-topbar">
                <div class="app-topbar-heading">
                    <h1><?= h($title) ?></h1>
                    <span>FEU TED Learning Portal</span>
                </div>
                <label class="app-search" role="search">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
                    <input id="globalSearch" type="search" placeholder="Search this page..." aria-label="Search this page" autocomplete="off">
                    <kbd>Ctrl K</kbd>
                </label>
                <div class="topbar-actions">
                    <button class="topbar-icon-button" id="themeToggle" type="button" aria-label="Toggle dark mode" title="Toggle dark mode"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M20.9 13A8.5 8.5 0 0 1 11 3.1 8.5 8.5 0 1 0 20.9 13Z"/></svg></button>
                    <div class="notification-wrap">
                        <button class="topbar-icon-button" id="notificationToggle" type="button" aria-label="Notifications" aria-expanded="false" aria-controls="notificationPanel" title="Notifications">
                            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9"/><path d="M10 21h4"/></svg><span class="notification-count" id="notificationCount" hidden>0</span>
                        </button>
                        <div class="notification-panel" id="notificationPanel" hidden>
                            <strong>Notifications</strong>
                            <div id="notificationItems" role="list" aria-live="polite" aria-relevant="additions text"><p>Loading latest updates…</p></div>
                            <span class="notification-live-status" id="notificationLiveStatus">Checking for updates…</span>
                            <a href="<?= BASE ?>/web/<?= h($role) ?>/announcements.php">View announcements</a>
                            <a href="<?= BASE ?>/web/<?= h($role) ?>/inbox.php">Open inbox</a>
                        </div>
                    </div>
                    <div class="app-profile-wrap">
                        <button class="app-profile" id="profileToggle" type="button" aria-expanded="false" aria-controls="profileMenu" title="Open profile menu">
                            <span class="app-avatar" aria-hidden="true"><?= h($initial) ?></span>
                            <span class="app-profile-copy"><strong><?= h(trim($firstName . ' ' . $lastName)) ?></strong><small><?= h(ucfirst($role)) ?></small></span>
                            <svg class="profile-chevron" viewBox="0 0 24 24" aria-hidden="true"><path d="m7 10 5 5 5-5"/></svg>
                        </button>
                        <div class="profile-menu" id="profileMenu" hidden>
                            <div class="profile-menu-user"><strong><?= h(trim($firstName . ' ' . $lastName)) ?></strong><small><?= h(ucfirst($role)) ?></small></div>
                            <a href="<?= BASE ?>/web/<?= h($role) ?>/settings.php">Settings</a>
                            <a href="<?= BASE ?>/web/help.php">Help Center</a>
                            <a href="<?= BASE ?>/web/logout.php">Log out</a>
                        </div>
                    </div>
                </div>
            </header>
            <section class="app-page">
                <?php if ($walkthroughRequired): ?>
                    <div class="mandatory-tour" id="mandatoryTour" role="dialog" aria-modal="true" aria-labelledby="mandatoryTourTitle" aria-describedby="mandatoryTourDescription" data-current-step="<?= $walkthroughStep ?>" data-steps="<?= h(json_encode($walkthroughSteps, JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP)) ?>">
                        <div class="mandatory-tour-spotlight" id="mandatoryTourSpotlight" aria-hidden="true"></div>
                        <section class="mandatory-tour-card" tabindex="-1">
                            <div class="mandatory-tour-eyebrow"><?= h(ucfirst($role)) ?> onboarding</div>
                            <div class="mandatory-tour-progress" id="mandatoryTourProgress"></div>
                            <h2 id="mandatoryTourTitle"></h2>
                            <p id="mandatoryTourDescription"></p>
                            <p class="mandatory-tour-error" id="mandatoryTourError" role="alert" hidden></p>
                            <div class="mandatory-tour-actions">
                                <button class="mandatory-tour-previous" id="mandatoryTourPrevious" type="button">Previous</button>
                                <button class="mandatory-tour-next" id="mandatoryTourNext" type="button">Next</button>
                                <button class="mandatory-tour-finish" id="mandatoryTourFinish" type="button" hidden>Finish walkthrough</button>
                            </div>
                        </section>
                    </div>
                <?php endif; ?>
<?php
}function names($ids){$r=[];foreach(api('user','byids',['ids'=>implode(',',array_unique($ids))]) as $u)$r[$u['user_id']]=$u['first_name'].' '.$u['last_name'];return $r;}
function stats($enr){$eids=implode(',',array_column($enr,'enrollment_id'));$cids=implode(',',array_column($enr,'course_id'));
 $rows=function($svc,$act,$params){$data=api($svc,$act,$params);if(!is_array($data)||isset($data['error'])){error_log('stats: '.$svc.'/'.$act.' API response: '.(is_array($data)?($data['error']??'invalid response'):'invalid response'));return [];}return $data;};
 $tot=array_column($rows('assignment','counts',['ids'=>$cids]),'n','course_id');$done=[];foreach($rows('grade','mine',['ids'=>$eids]) as $g)if(is_array($g)&&isset($g['enrollment_id'],$g['assignment_id']))$done[$g['enrollment_id']][$g['assignment_id']]=1;
 $pr=[];foreach($enr as $e){$t=$tot[$e['course_id']]??0;$pr[$e['course_id']]=$t?round(count($done[$e['enrollment_id']]??[])/$t*100):0;}
 $T=['present'=>0,'late'=>0,'absent'=>0];foreach($rows('attendance','mine',['ids'=>$eids]) as $a)if(is_array($a)&&isset($a['status'],$a['n']))$T[$a['status']]=($T[$a['status']]??0)+$a['n'];$s=array_sum($T);
 return ['progress'=>$pr,'att'=>$s?round(($T['present']+$T['late'])/$s*100).'%':'—'];}
function ctx(){$e=api('enrollment','mine');return [$e,implode(',',array_column($e,'course_id'))];}
function bottom()
{
    echo '</section></main>
    <script>
        const themeKey = "lmsTheme";
        const savedTheme = localStorage.getItem(themeKey);
        if (savedTheme === "dark" || (!savedTheme && localStorage.getItem("dark"))) document.body.classList.add("dark");

        // Lightweight global page search: filters common content rows/cards without a network request.
        const globalSearch = document.getElementById("globalSearch");
        let searchTimer;
        if (globalSearch) {
            const searchable = Array.from(document.querySelectorAll(".card, .feed-announcement-card, .announcement-card, .assignment-card, .course-card, .content-card, .grades-table tbody tr, table tbody tr, .message-row"));
            globalSearch.addEventListener("input", () => {
                window.clearTimeout(searchTimer);
                searchTimer = window.setTimeout(() => {
                    const q = globalSearch.value.trim().toLocaleLowerCase();
                    searchable.forEach((item) => {
                        if (item.dataset.searchHidden === "true") return;
                        item.hidden = !!q && !item.textContent.toLocaleLowerCase().includes(q);
                    });
                }, 120);
            });
            document.addEventListener("keydown", (event) => {
                if ((event.ctrlKey || event.metaKey) && event.key.toLowerCase() === "k") {
                    event.preventDefault(); globalSearch.focus(); globalSearch.select();
                }
                if (event.key === "Escape" && document.activeElement === globalSearch) {
                    globalSearch.value = ""; globalSearch.dispatchEvent(new Event("input")); globalSearch.blur();
                }
            });
        }

        function wirePopover(buttonId, panelId) {
            const button = document.getElementById(buttonId), panel = document.getElementById(panelId);
            if (!button || !panel) return;
            const close = () => { panel.hidden = true; button.setAttribute("aria-expanded", "false"); };
            button.addEventListener("click", (event) => {
                event.stopPropagation(); const opening = panel.hidden; panel.hidden = !opening;
                button.setAttribute("aria-expanded", String(opening));
            });
            document.addEventListener("click", (event) => { if (!panel.contains(event.target) && !button.contains(event.target)) close(); });
            document.addEventListener("keydown", (event) => { if (event.key === "Escape") close(); });
        }
        wirePopover("notificationToggle", "notificationPanel");
        wirePopover("profileToggle", "profileMenu");
        const themeToggle = document.getElementById("themeToggle");
        function applyTheme(isDark) {
            document.body.classList.toggle("dark", isDark);
            localStorage.setItem(themeKey, isDark ? "dark" : "light");
            if (isDark) localStorage.setItem("dark", "1"); else localStorage.removeItem("dark");
            if (themeToggle) { themeToggle.setAttribute("aria-pressed", String(isDark)); themeToggle.title = isDark ? "Switch to light mode" : "Switch to dark mode"; }
        }
        if (themeToggle) {
            applyTheme(document.body.classList.contains("dark"));
            themeToggle.addEventListener("click", () => applyTheme(!document.body.classList.contains("dark")));
        }
        // Profile menu is click-only on desktop and mobile. The shared popover handler above
        // toggles it and closes it on outside click or Escape.

        const scrollProgress = document.getElementById("pageScrollProgress");
        let scrollTicking = false;
        function updateScrollProgress() {
            const maxScroll = document.documentElement.scrollHeight - window.innerHeight;
            const progress = maxScroll > 0 ? (window.scrollY / maxScroll) * 100 : 0;
            if (scrollProgress) scrollProgress.style.height = progress + "%";
            scrollTicking = false;
        }
        window.addEventListener("scroll", () => { if (!scrollTicking) { window.requestAnimationFrame(updateScrollProgress); scrollTicking = true; } }, { passive: true });
        window.addEventListener("resize", updateScrollProgress, { passive: true });
        updateScrollProgress();

        // Resolve the app/web/role paths from the current PHP page URL. Avoid PHP template
        // fragments inside JavaScript strings, which can otherwise leak into generated links.
        const currentPathParts = window.location.pathname.split("/").filter(Boolean);
        const webPathIndex = currentPathParts.indexOf("web");
        const webBasePath = webPathIndex >= 0
            ? "/" + currentPathParts.slice(0, webPathIndex + 1).join("/")
            : "/lms/web";
        const currentRole = webPathIndex >= 0 ? (currentPathParts[webPathIndex + 1] || "student") : "student";
        const notificationsUrl = webBasePath + "/notifications.php";
        const inboxUrl = webBasePath + "/" + encodeURIComponent(currentRole) + "/inbox.php";
        const announcementsUrl = webBasePath + "/" + encodeURIComponent(currentRole) + "/announcements.php";
        const notificationItems = document.getElementById("notificationItems");
        const notificationCount = document.getElementById("notificationCount");
        const notificationLiveStatus = document.getElementById("notificationLiveStatus");
        let notificationSnapshot = "";
        let notificationAudioContext = null;
        let notificationInitialized = false;
        let previousNotificationKeys = new Set();
        const localReadNotificationKey = "lmsReadNotifications:" + (document.body.dataset.userId || currentRole);
        function notificationIcon(name) {
            const paths = {
                message: `<path d="M21 11.5a8.4 8.4 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.4 8.4 0 0 1-3.8-.9L3 21l1.9-5.7a8.4 8.4 0 0 1-.9-3.8A8.5 8.5 0 0 1 8.7 3.9a8.4 8.4 0 0 1 3.8-.9h.5a8.5 8.5 0 0 1 8 8z"/>`,
                announcement: `<path d="m3 11 18-5v12L3 13v-2z"/><path d="M11.6 16.8a3 3 0 0 1-5.8-1.6"/>`,
                assignment: `<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6M8 13h8M8 17h5"/>`,
                submission: `<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6M8 13h8M8 17h5"/>`,
                check: `<path d="m5 12 4 4L19 6"/>`,
                close: `<path d="m18 6-12 12M6 6l12 12"/>`
            };
            const pathMarkup = paths[name] || paths.message;
            const wrap = document.createElement("span"); wrap.className="notification-item-icon"; wrap.innerHTML=`<svg viewBox="0 0 24 24" aria-hidden="true">${pathMarkup}</svg>`;
            return wrap;
        }
        function formatNotificationTime(value) {
            const date = new Date(String(value || "").replace(" ", "T"));
            if (Number.isNaN(date.getTime())) return "Time unavailable";
            const now = new Date();
            const minutes = Math.max(0, Math.floor((now.getTime() - date.getTime()) / 60000));
            if (minutes < 1) return "Just now";
            if (minutes < 60) return `${minutes} minute${minutes === 1 ? "" : "s"} ago`;
            const time = new Intl.DateTimeFormat(undefined, { hour: "numeric", minute: "2-digit" }).format(date);
            if (date.toDateString() === now.toDateString()) return `Today at ${time}`;
            const yesterday = new Date(now);
            yesterday.setDate(now.getDate() - 1);
            if (date.toDateString() === yesterday.toDateString()) return `Yesterday at ${time}`;
            return `${new Intl.DateTimeFormat(undefined, { month: "short", day: "numeric" }).format(date)} at ${time}`;
        }
        function readLocalNotificationIds() {
            try { return new Set(JSON.parse(localStorage.getItem(localReadNotificationKey) || "[]").map(String)); }
            catch (error) { return new Set(); }
        }
        function saveReadNotification(type, id, readNotifications) {
            readNotifications.add(`${type}:${id}`);
            try { localStorage.setItem(localReadNotificationKey, JSON.stringify(Array.from(readNotifications).slice(-200))); }
            catch (error) { /* Read state still updates for this refresh if browser storage is unavailable. */ }
        }
        function notificationHref(item) {
            if (item.type === "message") return inboxUrl + "?with=" + encodeURIComponent(item.target_id || "");
            if (item.type === "assignment") return webBasePath + "/student/assignments.php";
            if (item.type === "submission") return webBasePath + "/professor/submission.php?id=" + encodeURIComponent(item.target_id || "");
            return announcementsUrl;
        }
        function notificationActionLabel(type) {
            if (type === "message") return "Open inbox";
            if (type === "assignment") return "View assignments";
            if (type === "submission") return "Review submission";
            return "View announcement";
        }
        function playNotificationSound() {
            try {
                const AudioContextClass = window.AudioContext || window.webkitAudioContext;
                if (!AudioContextClass) return;
                if (!notificationAudioContext) notificationAudioContext = new AudioContextClass();
                if (notificationAudioContext.state === "suspended") notificationAudioContext.resume();
                const oscillator = notificationAudioContext.createOscillator();
                const gain = notificationAudioContext.createGain();
                oscillator.type = "sine"; oscillator.frequency.setValueAtTime(880, notificationAudioContext.currentTime);
                oscillator.frequency.setValueAtTime(1175, notificationAudioContext.currentTime + 0.09);
                gain.gain.setValueAtTime(0.0001, notificationAudioContext.currentTime);
                gain.gain.exponentialRampToValueAtTime(0.12, notificationAudioContext.currentTime + 0.02);
                gain.gain.exponentialRampToValueAtTime(0.0001, notificationAudioContext.currentTime + 0.22);
                oscillator.connect(gain); gain.connect(notificationAudioContext.destination);
                oscillator.start(); oscillator.stop(notificationAudioContext.currentTime + 0.23);
            } catch (error) { /* Sound is optional; notifications still work without it. */ }
        }
        let notificationRefreshInProgress = false;
        async function refreshNotifications() {
            if (!notificationItems || document.visibilityState !== "visible" || notificationRefreshInProgress) return;
            notificationRefreshInProgress = true;
            try {
                const response = await fetch(notificationsUrl, { credentials: "same-origin", headers: { "Accept": "application/json" }, cache: "no-store" });
                if (!response.ok) throw new Error("Notification request failed");
                const data = await response.json();
                const readNotifications = readLocalNotificationIds();
                const feed = Array.isArray(data.notifications) ? data.notifications : [];
                const uniqueNotifications = new Map();
                feed.forEach((item) => {
                    if (!item || !item.id || !item.type) return;
                    const key = `${item.type}:${item.id}`;
                    const isRead = Boolean(item.read) || ((item.type === "assignment" || item.type === "submission") && readNotifications.has(`${item.type}:${item.id}`));
                    uniqueNotifications.set(key, { ...item, read: isRead });
                });
                const notifications = Array.from(uniqueNotifications.values()).sort((left, right) => {
                    const leftTime = new Date(String(left.created_at || "").replace(" ", "T")).getTime() || 0;
                    const rightTime = new Date(String(right.created_at || "").replace(" ", "T")).getTime() || 0;
                    return rightTime - leftTime || Number(right.id) - Number(left.id);
                });
                const currentKeys = new Set(notifications.map(item => `${item.type}:${item.id}`));
                if (notificationInitialized && [...currentKeys].some(key => !previousNotificationKeys.has(key))) playNotificationSound();
                previousNotificationKeys = currentKeys;
                notificationInitialized = true;
                const unreadEntries = notifications.filter(item => !item.read).length;
                const messageEntries = notifications.filter(item => item.type === "message" && !item.read).length;
                const unreadCount = unreadEntries + Math.max(0, Number(data.unread_messages || 0) - messageEntries);
                notificationCount.textContent = unreadCount > 99 ? "99+" : String(unreadCount);
                notificationCount.hidden = unreadCount < 1;
                const notificationToggleButton = document.getElementById("notificationToggle");
                if (notificationToggleButton) notificationToggleButton.setAttribute("aria-label", unreadCount ? `${unreadCount} unread notifications` : "Notifications");
                const snapshot = JSON.stringify({ notifications, unreadCount });
                if (snapshot !== notificationSnapshot) {
                    notificationSnapshot = snapshot;
                    notificationItems.replaceChildren();
                    if (!notifications.length) {
                        const empty = document.createElement("p");
                        empty.className = "notification-empty-state";
                        empty.textContent = "You are all caught up. No unread messages, submissions, assignments, or announcements.";
                        notificationItems.appendChild(empty);
                    }
                    notifications.forEach((item) => {
                        const key = `${item.type}:${item.id}`;
                        const row = document.createElement("article");
                        row.className = `notification-item ${item.read ? "is-read" : "is-unread"}`;
                        row.setAttribute("role", "listitem");
                        const link = document.createElement("a");
                        link.href = notificationHref(item);
                        link.className = "notification-link";
                        link.setAttribute("aria-label", `${item.title || "Notification"}. ${notificationActionLabel(item.type)}.`);
                        link.appendChild(notificationIcon(item.type));
                        const copy = document.createElement("span");
                        copy.className = "notification-copy";
                        const title = document.createElement("strong");
                        title.className = "notification-title";
                        title.textContent = item.title || "Notification";
                        const description = document.createElement("span");
                        description.className = "notification-description";
                        const message = String(item.message || "").trim();
                        description.textContent = message.length > 220 ? message.slice(0, 217) + "..." : message;
                        const meta = document.createElement("span");
                        meta.className = "notification-meta";
                        const time = document.createElement("time");
                        time.dateTime = item.created_at || "";
                        time.textContent = formatNotificationTime(item.created_at);
                        const status = document.createElement("span");
                        status.className = `notification-status ${item.read ? "is-read" : "is-unread"}`;
                        status.textContent = item.read ? "Read" : "Unread";
                        meta.append(time, status);
                        copy.append(title, description, meta);
                        link.appendChild(copy);
                        row.appendChild(link);

                        const actions = document.createElement("div");
                        actions.className = "notification-row-actions";
                        const openLink = document.createElement("a");
                        openLink.className = "notification-open-link";
                        openLink.href = link.href;
                        openLink.textContent = notificationActionLabel(item.type);
                        if (item.type === "assignment" || item.type === "submission") {
                            openLink.addEventListener("click", () => saveReadNotification(item.type, item.id, readNotifications));
                        }
                        actions.appendChild(openLink);

                        if (!item.read && (item.type === "announcement" || item.type === "assignment" || item.type === "submission")) {
                            const readButton = document.createElement("button");
                            readButton.type = "button";
                            readButton.className = "notification-dismiss";
                            readButton.textContent = "Mark as read";
                            readButton.addEventListener("click", async () => {
                                readButton.disabled = true;
                                try {
                                    if (item.type === "announcement") {
                                        const body = new URLSearchParams({ action: "read_announcement", id: String(item.id) });
                                        const result = await fetch(notificationsUrl, { method: "POST", credentials: "same-origin", headers: { "Content-Type": "application/x-www-form-urlencoded", "Accept": "application/json" }, body });
                                        const resultData = await result.json().catch(() => ({}));
                                        if (!result.ok || !resultData.ok) throw new Error(resultData.error || "Could not mark announcement as read");
                                    } else {
                                        saveReadNotification(item.type, item.id, readNotifications);
                                    }
                                    notificationSnapshot = "";
                                    await refreshNotifications();
                                } catch (error) {
                                    readButton.disabled = false;
                                    if (notificationLiveStatus) notificationLiveStatus.textContent = error.message || "Could not update read status.";
                                }
                            });
                            actions.appendChild(readButton);
                        }
                        row.appendChild(actions);
                        notificationItems.appendChild(row);
                    });
                }
                if (notificationLiveStatus) notificationLiveStatus.textContent = "Live updates · checked just now";
            } catch (error) {
                if (notificationLiveStatus) notificationLiveStatus.textContent = "Updates temporarily unavailable";
            } finally { notificationRefreshInProgress = false; }
        }
        function unlockNotificationAudio() {
            try {
                const AudioContextClass = window.AudioContext || window.webkitAudioContext;
                if (AudioContextClass && !notificationAudioContext) notificationAudioContext = new AudioContextClass();
                if (notificationAudioContext && notificationAudioContext.state === "suspended") notificationAudioContext.resume();
            } catch (error) { /* Browsers may block sound until a user gesture. */ }
        }
        document.addEventListener("pointerdown", unlockNotificationAudio, { once: true });
        document.addEventListener("keydown", unlockNotificationAudio, { once: true });
        const notificationToggle = document.getElementById("notificationToggle");
        if (notificationToggle) notificationToggle.addEventListener("click", () => {
            try {
                const AudioContextClass = window.AudioContext || window.webkitAudioContext;
                if (AudioContextClass && !notificationAudioContext) notificationAudioContext = new AudioContextClass();
                if (notificationAudioContext && notificationAudioContext.state === "suspended") notificationAudioContext.resume();
            } catch (error) { /* Browser may block audio; the panel remains usable. */ }
        });
        refreshNotifications();
        // Reduce repeated service/database load; refresh immediately when the tab becomes visible.
        window.setInterval(refreshNotifications, 5000);
        document.addEventListener("visibilitychange", () => { if (document.visibilityState === "visible") refreshNotifications(); });

        const sidebarToggle = document.querySelector(".app-sidebar-toggle");
        const sidebarExpandedKey = "lmsSidebarExpanded";

        if (localStorage.getItem(sidebarExpandedKey) === "true") {
            document.body.classList.add("sidebar-expanded");
        }

        function updateSidebarToggle() {
            const isPinned = document.body.classList.contains("sidebar-expanded");
            sidebarToggle.setAttribute("aria-pressed", String(isPinned));
            sidebarToggle.setAttribute("aria-label", isPinned ? "Use hover to expand sidebar" : "Keep sidebar expanded");
            sidebarToggle.setAttribute("title", isPinned ? "Use hover to expand sidebar" : "Keep sidebar expanded");
            sidebarToggle.querySelector(".app-nav-label").textContent = isPinned ? "Minimize menu" : "Expand menu";
            sidebarToggle.querySelector("svg").classList.toggle("is-flipped", isPinned);
        }

        sidebarToggle.addEventListener("click", () => {
            const isPinned = document.body.classList.toggle("sidebar-expanded");
            localStorage.setItem(sidebarExpandedKey, String(isPinned));
            updateSidebarToggle();
        });

        updateSidebarToggle();

        const mandatoryTour = document.getElementById("mandatoryTour");
        if (mandatoryTour) {
            const steps = JSON.parse(mandatoryTour.dataset.steps || "[]");
            const spotlight = document.getElementById("mandatoryTourSpotlight");
            const title = document.getElementById("mandatoryTourTitle");
            const description = document.getElementById("mandatoryTourDescription");
            const progress = document.getElementById("mandatoryTourProgress");
            const error = document.getElementById("mandatoryTourError");
            const previousButton = document.getElementById("mandatoryTourPrevious");
            const nextButton = document.getElementById("mandatoryTourNext");
            const finishButton = document.getElementById("mandatoryTourFinish");
            const actionUrl = webBasePath + "/onboarding_action.php";
            let currentStep = Math.min(Number(mandatoryTour.dataset.currentStep) || 0, steps.length - 1);
            let saving = false;

            async function saveProgress(action, step) {
                const body = new URLSearchParams({ action });
                if (Number.isInteger(step)) body.set("step", String(step));
                const response = await fetch(actionUrl, {
                    method: "POST",
                    credentials: "same-origin",
                    headers: { "Content-Type": "application/x-www-form-urlencoded", "Accept": "application/json" },
                    body
                });
                const result = await response.json().catch(() => ({}));
                if (!response.ok || !result.ok) throw new Error(result.error || "Could not save walkthrough progress.");
                return result;
            }

            function positionSpotlight(target) {
                const rect = target.getBoundingClientRect();
                const padding = 8;
                spotlight.style.top = `${Math.max(4, rect.top - padding)}px`;
                spotlight.style.left = `${Math.max(4, rect.left - padding)}px`;
                spotlight.style.width = `${Math.max(24, rect.width + padding * 2)}px`;
                spotlight.style.height = `${Math.max(24, rect.height + padding * 2)}px`;
                spotlight.hidden = false;
            }

            function renderStep() {
                const step = steps[currentStep];
                if (!step) return;
                error.hidden = true;
                title.textContent = step.title;
                description.textContent = step.description;
                progress.textContent = `Step ${currentStep + 1} of ${steps.length}`;
                previousButton.disabled = currentStep === 0 || saving;
                nextButton.hidden = currentStep === steps.length - 1;
                nextButton.disabled = saving;
                finishButton.hidden = currentStep !== steps.length - 1;
                finishButton.disabled = saving;

                const target = document.querySelector(step.target) || document.querySelector(".app-sidebar");
                if (!target) {
                    spotlight.hidden = true;
                    return;
                }
                target.scrollIntoView({ behavior: "smooth", block: "center", inline: "nearest" });
                requestAnimationFrame(() => positionSpotlight(target));
            }

            async function moveTo(stepIndex) {
                if (saving || stepIndex < 0 || stepIndex >= steps.length || Math.abs(stepIndex - currentStep) > 1) return;
                saving = true;
                previousButton.disabled = true;
                nextButton.disabled = true;
                finishButton.disabled = true;
                error.hidden = true;
                try {
                    await saveProgress("save_step", stepIndex);
                    currentStep = stepIndex;
                    renderStep();
                } catch (requestError) {
                    error.textContent = requestError.message;
                    error.hidden = false;
                } finally {
                    saving = false;
                    previousButton.disabled = currentStep === 0;
                    nextButton.disabled = false;
                    finishButton.disabled = false;
                }
            }

            previousButton.addEventListener("click", () => moveTo(currentStep - 1));
            nextButton.addEventListener("click", () => moveTo(currentStep + 1));
            finishButton.addEventListener("click", async () => {
                if (saving || currentStep !== steps.length - 1) return;
                saving = true;
                finishButton.disabled = true;
                error.hidden = true;
                try {
                    await saveProgress("complete");
                    window.location.reload();
                } catch (requestError) {
                    error.textContent = requestError.message;
                    error.hidden = false;
                    finishButton.disabled = false;
                    saving = false;
                }
            });
            document.addEventListener("keydown", (event) => {
                if (event.key === "Escape") event.preventDefault();
                if (event.key === "Tab") {
                    const focusable = Array.from(mandatoryTour.querySelectorAll("button:not([hidden]):not(:disabled)"));
                    if (!focusable.length) return;
                    const first = focusable[0];
                    const last = focusable[focusable.length - 1];
                    if (event.shiftKey && document.activeElement === first) { event.preventDefault(); last.focus(); }
                    else if (!event.shiftKey && document.activeElement === last) { event.preventDefault(); first.focus(); }
                }
            });
            window.addEventListener("resize", renderStep, { passive: true });
            document.body.style.overflow = "hidden";
            renderStep();
            (currentStep === steps.length - 1 ? finishButton : nextButton).focus();
        }
    </script>';

    echo '</body></html>';
}
function ui_icon($name){
 $paths=[
 'megaphone'=>'<path d="m3 11 18-5v12L3 13v-2Z"/><path d="M11.6 16.8a3 3 0 0 1-5.8-1.6"/><path d="M5 10v4"/>',
 'pin'=>'<path d="m16 3 5 5-4 1-4 4-1 4-2-2-5 5-1-1 5-5-2-2 4-1 4-4z"/>',
 'user'=>'<circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/>',
 'like'=>'<path d="M7 10v11H3V10zM7 21h10a2 2 0 0 0 2-1.6l2-8A2 2 0 0 0 19 9h-6l1-5-2-1-5 7"/>',
 'dislike'=>'<path d="M7 14V3H3v11zM7 3h10a2 2 0 0 1 2 1.6l2 8A2 2 0 0 1 19 15h-6l1 5-2 1-5-7"/>',
 'check'=>'<path d="m5 12 4 4L19 6"/>', 'clock'=>'<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>'];
 return '<svg class="ui-icon" viewBox="0 0 24 24" aria-hidden="true">'.($paths[$name]??$paths['megaphone']).'</svg>';
}
function announcement_feed($items,$limit=4,$heading='Latest Announcements'){
 if(!$items){echo '<section class="announcement-feed"><div class="feed-heading"><h2>'.h($heading).'</h2></div><div class="announcement-empty">No announcements available yet.</div></section>';return;}
 $items=array_slice($items,0,$limit);$authorIds=array_column($items,'posted_by');$authorNames=names($authorIds);
 $feedMeta=api('announcement','feed-meta',['ids'=>implode(',',array_column($items,'announcement_id'))]);
 $feedCounts=$feedMeta['counts']??[];$feedStates=$feedMeta['states']??[];
 echo '<section class="announcement-feed"><div class="feed-heading"><div><h2>'.h($heading).'</h2><p>Updates from your courses and school</p></div><a href="announcements.php" class="feed-view-all">View all <span aria-hidden="true">→</span></a></div><div class="announcement-feed-grid">';
 foreach($items as $a){$id=(int)$a['announcement_id'];$state=$feedStates[$id]??[];$counts=$feedCounts[$id]??[];$read=!empty($state['read']);$type=ucfirst($a['announcement_type']??'Announcement');
 echo '<article class="feed-announcement-card'.($read?' is-read':'').'">';
 echo '<div class="feed-card-top"><span class="feed-type">'.ui_icon('megaphone').' '.h($type).'</span>'.(!empty($a['is_pinned'])?'<span class="feed-pinned">'.ui_icon('pin').' Pinned</span>':'').'</div>';
 echo '<h3 class="feed-announcement-title">'.h($a['title']??'Announcement').'</h3><p class="feed-announcement-content">'.nl2br(h($a['content']??'')).'</p>';
 echo '<div class="feed-author-row"><span class="feed-avatar">'.ui_icon('user').'</span><span><b>'.h($authorNames[$a['posted_by']]??'LMS Staff').'</b><small>Author · '.h(substr($a['created_at']??'',0,16)).'</small></span></div>';
 echo '<div class="feed-actions">';
 foreach(['like'=>'Like','dislike'=>'Dislike'] as $reaction=>$label){$selected=($state['reaction']??'')===$reaction?' selected':'';$count=(int)($counts[$reaction.'s']??0);$returnTo=basename($_SERVER['SCRIPT_NAME']??'')==='dashboard.php'?'dashboard':'announcements';$actionUrl=BASE.'/web/announcement_action.php';echo '<form method="post" action="'.h($actionUrl).'"><input type="hidden" name="return_to" value="'.$returnTo.'"><input type="hidden" name="id" value="'.$id.'"><input type="hidden" name="action" value="'.$reaction.'"><button class="feed-action'.$selected.'" type="submit" aria-label="'.$label.' announcement">'.ui_icon($reaction).'<span>'.$label.'</span><b>'.$count.'</b></button></form>';}
 echo '<form method="post" action="'.h($actionUrl).'"><input type="hidden" name="return_to" value="'.$returnTo.'"><input type="hidden" name="id" value="'.$id.'"><input type="hidden" name="action" value="read"><button class="feed-action feed-read-action" type="submit">'.ui_icon('check').'<span>'.($read?'Read':'Mark as read').'</span></button></form>';
 echo '</div></article>'; }
 echo '</div></section>';
}