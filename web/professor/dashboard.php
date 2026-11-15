<?php require '../inc.php';guard('professor');$c=api('course','mine');$ids=implode(',',array_column($c,'course_id'));$cc=array_column($c,'course_code','course_id');
$ro=api('enrollment','roster',['ids'=>$ids]);$cnt=array_count_values(array_column($ro,'course_id'));$today=array_filter(api('course','schedule',['ids'=>$ids]),fn($s)=>substr($s['day_of_week'],0,3)===date('D'));
$pd=api('assignment','pending',['ids'=>$ids]);$un=api('messaging','unread')['n']??0;$nm=names(array_column($pd,'student_id'));top('Dashboard','dashboard');?>

<style>
/* Professor Dashboard Styling matching reference design */
.prof-dashboard-container {
    padding: 24px 32px;
    background-color: #f8fafc;
    min-height: 100vh;
    font-family: 'Inter', sans-serif;
    color: #1e293b;
    box-sizing: border-box;
}

.prof-header {
    margin-bottom: 24px;
}

.prof-header h1 {
    font-size: 24px;
    font-weight: 700;
    color: #0f172a;
    margin-bottom: 4px;
}

.prof-header p.sub {
    font-size: 14px;
    color: #64748b;
}

/* Top Stats Grid */
.prof-stats-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 20px;
    margin-bottom: 32px;
}

.prof-stat-card {
    background: #ffffff;
    border-radius: 16px;
    border: 1px solid #f1f5f9;
    padding: 20px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.02);
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    min-height: 120px;
}

.prof-stat-top {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
}

.prof-stat-icon {
    width: 36px;
    height: 36px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.prof-stat-number {
    font-size: 28px;
    font-weight: 700;
    color: #0f172a;
    line-height: 1.1;
    margin-bottom: 2px;
}

.prof-stat-label {
    font-size: 13px;
    color: #64748b;
    font-weight: 500;
}

.prof-stat-subtext {
    font-size: 12px;
    color: #94a3b8;
    margin-top: 6px;
}

/* Main Content Grid */
.prof-main-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 24px;
}

.prof-card {
    background: #ffffff;
    border-radius: 16px;
    border: 1px solid #f1f5f9;
    padding: 24px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.02);
    margin-bottom: 24px;
}

.prof-card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
}

.prof-card-header h3 {
    font-size: 16px;
    font-weight: 700;
    color: #0f172a;
}

.grade-now-link {
    font-size: 13px;
    color: #15803d;
    font-weight: 600;
    text-decoration: none;
}

.grade-now-link:hover {
    text-decoration: underline;
}

/* Course Row Items */
.course-row-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 14px 0;
    border-bottom: 1px solid #f8fafc;
}

.course-row-item:last-child {
    border-bottom: none;
    padding-bottom: 0;
}

.course-info-left {
    display: flex;
    align-items: center;
    gap: 14px;
}

.course-code-number-badge {
    width: 42px;
    height: 36px;
    background: #fdf2f8;
    color: #db2777;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 13px;
    font-weight: 700;
}

.course-details-text h4 {
    font-size: 15px;
    font-weight: 700;
    color: #0f172a;
    margin-bottom: 3px;
}

.course-details-text p {
    font-size: 12px;
    color: #64748b;
}

.student-count-pill {
    background: #fdf2f8;
    color: #db2777;
    font-size: 12px;
    font-weight: 700;
    padding: 4px 10px;
    border-radius: 8px;
}

/* Pending Grading Row Items */
.grading-row-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 12px 0;
    border-bottom: 1px solid #f8fafc;
}

.grading-row-item:last-child {
    border-bottom: none;
    padding-bottom: 0;
}

.grading-info-left {
    display: flex;
    align-items: center;
    gap: 12px;
}

.student-initial-avatar {
    width: 36px;
    height: 36px;
    background: #7c3aed;
    color: #ffffff;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 14px;
    font-weight: 700;
}

.grading-text h4 {
    font-size: 14px;
    font-weight: 600;
    color: #0f172a;
    margin-bottom: 2px;
}

.grading-text p {
    font-size: 12px;
    color: #64748b;
}

.tag-code-badge {
    background: #fef3c7;
    color: #b45309;
    font-size: 11px;
    font-weight: 700;
    padding: 3px 8px;
    border-radius: 6px;
}

@media(max-width: 1024px) {
    .prof-stats-grid { grid-template-columns: repeat(2, 1fr); }
    .prof-main-grid { grid-template-columns: 1fr; }
}
</style>

<div class="prof-dashboard-container">
    <div class="prof-header">
        <h1>Good morning, Prof. <?=h($_SESSION['user']['last_name'])?> </h1>
        <p class="sub"><?=date('l, F j, Y')?> · You have <?=count($today)?> classes today.</p>
    </div>

    <!-- Top Metric Summary Cards -->
    <div class="prof-stats-grid">
        <div class="prof-stat-card c1">
            <div class="prof-stat-top">
                <div class="prof-stat-icon" style="background: #dcfce7; color: #16a34a;">
                    <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path></svg>
                </div>
                <span style="font-size:12px; color:#16a34a; font-weight:700;">↗</span>
            </div>
            <div>
                <div class="prof-stat-number"><?=count($today)?></div>
                <div class="prof-stat-label">Today's Classes</div>
            </div>
            <div class="prof-stat-subtext">Active schedule</div>
        </div>

        <div class="prof-stat-card c2">
            <div class="prof-stat-top">
                <div class="prof-stat-icon" style="background: #fef9c3; color: #ca8a04;">
                    <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 20h9"></path><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"></path></svg>
                </div>
            </div>
            <div>
                <div class="prof-stat-number"><?=count($pd)?></div>
                <div class="prof-stat-label">Pending Grading</div>
            </div>
            <div class="prof-stat-subtext">Submissions to review</div>
        </div>

        <div class="prof-stat-card c3">
            <div class="prof-stat-top">
                <div class="prof-stat-icon" style="background: #e0f2fe; color: #0284c7;">
                    <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path></svg>
                </div>
            </div>
            <div>
                <div class="prof-stat-number"><?=$un?></div>
                <div class="prof-stat-label">Unread Messages</div>
            </div>
            <div class="prof-stat-subtext">From students</div>
        </div>

        <div class="prof-stat-card c4">
            <div class="prof-stat-top">
                <div class="prof-stat-icon" style="background: #f3e8ff; color: #9333ea;">
                    <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
                </div>
            </div>
            <div>
                <div class="prof-stat-number"><?=count(array_unique(array_column($ro,'student_id')))?></div>
                <div class="prof-stat-label">Active Students</div>
            </div>
            <div class="prof-stat-subtext">Across all sections</div>
        </div>
    </div>

    <?php $dashboardAnnouncements=api('announcement','list',['ids'=>$ids]); announcement_feed($dashboardAnnouncements,4); ?>

    <!-- Main Content Grid -->
    <div class="prof-main-grid">
        <!-- Left Column: My Courses -->
        <div class="prof-card">
            <div class="prof-card-header">
                <h3>My Courses</h3>
            </div>
            <div>
                <?php foreach($c as $x): 
                    $studentTotal = $cnt[$x['course_id']] ?? 30;
                ?>
                <div class="course-row-item">
                    <div class="course-info-left">
                        <div class="course-code-number-badge"><?=h($x['course_code'])?></div>
                        <div class="course-details-text">
                            <h4><?=h($x['course_name'])?></h4>
                            <p><?=$studentTotal?> students · Mon/Thu 1:15 PM – 3:00 PM</p>
                        </div>
                    </div>
                    <div>
                        <span class="student-count-pill"><?=$studentTotal?></span>
                    </div>
                </div>
                <?php endforeach;?>
            </div>
        </div>

        <!-- Right Column: Pending Grading -->
        <div class="prof-card">
            <div class="prof-card-header">
                <h3>Pending Grading</h3>
                <a href="grades.php" class="grade-now-link">Grade now &rarr;</a>
            </div>
            <div>
                <?php foreach(array_slice($pd,0,4) as $x):
                    $studentFullName = $nm[$x['student_id']] ?? 'Student Name';
                    $initial = strtoupper(substr($studentFullName, 0, 1));
                ?>
                <div class="grading-row-item">
                    <div class="grading-info-left">
                        <div class="student-initial-avatar"><?=$initial?></div>
                        <div class="grading-text">
                            <h4><?=h($studentFullName)?></h4>
                            <p><?=h($x['title'])?> · 2 hours ago</p>
                        </div>
                    </div>
                    <div>
                        <span class="tag-code-badge"><?=h($cc[$x['course_id']] ?? 'IT271')?></span>
                    </div>
                </div>
                <?php endforeach;?>
            </div>
        </div>
    </div>
</div>

<?php bottom();?>