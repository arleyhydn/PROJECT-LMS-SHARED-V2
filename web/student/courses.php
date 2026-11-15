<?php require '../inc.php';guard('student');[$enr,$ids]=ctx();$st=stats($enr);$c=api('course','byids',['ids'=>$ids]);$sc=[];foreach(api('course','schedule',['ids'=>$ids]) as $s)$sc[$s['course_id']][]=$s;top('My Courses','courses');?>

<style>
/* My Courses Page Styling matching reference design */
.courses-container {
    padding: 24px 32px;
    background-color: #f8fafc;
    min-height: 100vh;
    font-family: 'Inter', sans-serif;
    color: #1e293b;
    box-sizing: border-box;
}

.courses-header-row {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 24px;
}

.courses-header h1 {
    font-size: 24px;
    font-weight: 700;
    color: #0f172a;
    margin-bottom: 4px;
}

.courses-header p.sub {
    font-size: 14px;
    color: #64748b;
}

.filter-dropdown-btn {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    padding: 8px 16px;
    border-radius: 10px;
    font-size: 13px;
    font-weight: 600;
    color: #334155;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 8px;
}

/* 3-Column Grid */
.courses-grid-3 {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 24px;
}

/* Course Card */
.course-card {
    background: #ffffff;
    border-radius: 16px;
    border: 1px solid #f1f5f9;
    padding: 24px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.02);
    position: relative;
    overflow: hidden;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
}

/* Top colored accent line */
.course-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 6px;
}

.course-card:nth-child(6n+1)::before { background-color: #ec4899; }
.course-card:nth-child(6n+2)::before { background-color: #3b82f6; }
.course-card:nth-child(6n+3)::before { background-color: #22c55e; }
.course-card:nth-child(6n+4)::before { background-color: #f97316; }
.course-card:nth-child(6n+5)::before { background-color: #eab308; }
.course-card:nth-child(6n+0)::before { background-color: #a855f7; }

.course-card-top {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 12px;
}

.course-code-tag {
    background: #f1f5f9;
    color: #334155;
    font-size: 11px;
    font-weight: 700;
    padding: 3px 8px;
    border-radius: 6px;
}

.course-students-count {
    font-size: 12px;
    color: #64748b;
    display: flex;
    align-items: center;
    gap: 4px;
}

.course-title {
    font-size: 18px;
    font-weight: 700;
    color: #0f172a;
    margin-bottom: 4px;
    line-height: 1.3;
}

.course-professor {
    font-size: 13px;
    color: #64748b;
    margin-bottom: 16px;
}

.course-schedule-list {
    margin-bottom: 20px;
    display: flex;
    flex-direction: column;
    gap: 6px;
}

.course-schedule-item {
    font-size: 13px;
    color: #475569;
    display: flex;
    align-items: center;
    gap: 8px;
}

/* Progress Section */
.course-progress-section {
    border-top: 1px solid #f1f5f9;
    padding-top: 16px;
    margin-bottom: 16px;
}

.progress-info-row {
    display: flex;
    justify-content: space-between;
    font-size: 12px;
    font-weight: 600;
    color: #64748b;
    margin-bottom: 6px;
}

.progress-bar-container {
    width: 100%;
    height: 6px;
    background-color: #f1f5f9;
    border-radius: 3px;
    overflow: hidden;
}

.progress-bar-fill {
    height: 100%;
    border-radius: 3px;
    background-color: #15803d;
}

/* Notice Banner inside card */
.course-notice-banner {
    background: #fdf2f8;
    color: #be185d;
    font-size: 12px;
    font-weight: 500;
    padding: 8px 12px;
    border-radius: 8px;
    margin-bottom: 16px;
    display: flex;
    align-items: center;
    gap: 6px;
}

/* Open Course Button */
.open-course-btn {
    width: 100%;
    background-color: #0f172a;
    color: #ffffff;
    border: none;
    border-radius: 10px;
    padding: 12px;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    text-align: center;
    text-decoration: none;
    transition: background-color 0.2s;
    display: block;
}

.open-course-btn:hover {
    background-color: #1e293b;
}

.empty-courses-state {
    grid-column: span 3;
    background: #ffffff;
    padding: 40px;
    text-align: center;
    border-radius: 16px;
    border: 1px solid #f1f5f9;
    color: #64748b;
    font-size: 14px;
}

@media(max-width: 1024px) {
    .courses-grid-3 { grid-template-columns: repeat(1, 1fr); }
    .empty-courses-state { grid-column: span 1; }
}
</style>

<div class="courses-container">
    <div class="courses-header-row">
        <div class="courses-header">
            <h1>My Courses</h1>
            <p class="sub">Second Semester, A.Y. 2026–2027 · <?=count($c)?> enrolled subjects</p>
        </div>
        <button class="filter-dropdown-btn">
            All Courses <span>▼</span>
        </button>
    </div>

    <div class="courses-grid-3">
        <?php if(empty($c)): ?>
            <div class="empty-courses-state">
                <p>You are not currently enrolled in any courses for this semester.</p>
            </div>
        <?php else: ?>
            <?php foreach($c as $x): ?>
            <div class="course-card">
                <div>
                    <div class="course-card-top">
                        <span class="course-code-tag"><?=h($x['course_code'])?></span>
                        <span class="course-students-count"><svg class="ui-icon" viewBox="0 0 24 24"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/></svg> <?=h($x['students_count'] ?? 0)?></span>
                    </div>
                    
                    <h3 class="course-title"><?=h($x['course_name'])?></h3>
                    <div class="course-professor"><?=h(trim((string)($x['professor'] ?? '')) ?: 'No professor assigned yet')?></div>

                    <div class="course-schedule-list">
                        <?php foreach($sc[$x['course_id']]??[] as $s):?>
                        <div class="course-schedule-item">
                            <svg class="ui-icon" viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg> <?=h($s['day_of_week'])?> <?=substr($s['start_time'],0,5)?>–<?=substr($s['end_time'],0,5)?>
                        </div>
                        <div class="course-schedule-item">
                            <svg class="ui-icon" viewBox="0 0 24 24"><path d="M20 10c0 5-8 12-8 12S4 15 4 10a8 8 0 1 1 16 0Z"/><circle cx="12" cy="10" r="2"/></svg> Room <?=h($s['room'])?>
                        </div>
                        <?php endforeach;?>
                    </div>

                    <div class="course-progress-section">
                        <div class="progress-info-row">
                            <span>Course Progress</span>
                            <span><?=h($st['progress'][$x['course_id']]??0)?>%</span>
                        </div>
                        <div class="progress-bar-container">
                            <div class="progress-bar-fill" style="width: <?=h($st['progress'][$x['course_id']]??0)?>%;"></div>
                        </div>
                    </div>

                    <?php if(!empty($x['notice'])): ?>
                    <div class="course-notice-banner">
                        <svg class="ui-icon" viewBox="0 0 24 24"><path d="m16 3 5 5-4 1-4 4-1 4-2-2-5 5-1-1 5-5-2-2 4-1 4-4z"/></svg> <?=h($x['notice'])?>
                    </div>
                    <?php endif; ?>
                </div>

                <a href="course.php?id=<?=$x['course_id']?>" class="open-course-btn">Open Course &rarr;</a>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<?php bottom();?>