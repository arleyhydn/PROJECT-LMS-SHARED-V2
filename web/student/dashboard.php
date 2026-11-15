<?php require '../inc.php';guard('student');
$enr=api('enrollment','mine');if(!is_array($enr))$enr=[];$ids=implode(',',array_column($enr,'course_id'));$cnames=array_column(api('course','byids',['ids'=>$ids]),'course_name','course_id');$st=stats($enr);
$allSchedule=api('course','schedule',['ids'=>$ids]);if(!is_array($allSchedule)||isset($allSchedule['error']))$allSchedule=[];
$sched=array_values(array_filter($allSchedule,fn($s)=>substr((string)($s['day_of_week']??''),0,3)===date('D')));
$ann=api('announcement','list',['ids'=>$ids]);if(!is_array($ann)||isset($ann['error']))$ann=[];
$allTodos=api('planning','todos');if(!is_array($allTodos)||isset($allTodos['error']))$allTodos=[];
$todos=array_values(array_filter($allTodos,fn($t)=>strtolower((string)($t['status']??''))!=='completed'));
$nowTime=date('H:i:s');$upcomingCount=count(array_filter($sched,fn($s)=>substr((string)($s['start_time']??''),0,8)>=$nowTime));
$dueSoonCount=0;$todayDate=new DateTimeImmutable('today');$weekEnd=(new DateTimeImmutable('now'))->modify('+7 days');
foreach($todos as $todo){$due=trim((string)($todo['due_date']??''));if($due==='')continue;try{$dueDate=new DateTimeImmutable($due);if($dueDate>=$todayDate&&$dueDate<=$weekEnd)$dueSoonCount++;}catch(Throwable $e){}}
$unreadData=api('messaging','unread');$unreadCount=is_array($unreadData)&&!isset($unreadData['error'])?(int)($unreadData['n']??0):0;
top('Dashboard','dashboard');?>

<style>
/* Dashboard Specific Layout & Styling matching reference design */
.dashboard-container {
    padding: 24px 32px;
    background-color: #f8fafc;
    min-height: 100vh;
    font-family: 'Inter', sans-serif;
    color: #1e293b;
}

.welcome-header {
    margin-bottom: 24px;
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
}

.welcome-header h1 {
    font-size: 24px;
    font-weight: 700;
    color: #0f172a;
    margin-bottom: 4px;
}

.welcome-header h1 em {
    color: #15803d;
    font-style: normal;
}

.welcome-header p.sub {
    font-size: 14px;
    color: #64748b;
}

.system-status {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 13px;
    color: #64748b;
    font-weight: 500;
}

.status-dot {
    width: 8px;
    height: 8px;
    background-color: #22c55e;
    border-radius: 50%;
}

/* Top Stat Cards Grid */
.stats-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 20px;
    margin-bottom: 32px;
}

.stat-card {
    background: #ffffff;
    border-radius: 16px;
    padding: 20px;
    border: 1px solid #f1f5f9;
    box-shadow: 0 1px 3px rgba(0,0,0,0.02);
    position: relative;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    min-height: 130px;
}

.stat-card-top {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
}

.stat-icon {
    width: 36px;
    height: 36px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 16px;
}

.stat-arrow {
    color: #cbd5e1;
    font-size: 14px;
}

.stat-content .number {
    font-size: 28px;
    font-weight: 700;
    color: #0f172a;
    line-height: 1.1;
    margin-bottom: 2px;
}

.stat-content .label {
    font-size: 13px;
    color: #64748b;
    font-weight: 500;
}

.stat-footer {
    font-size: 12px;
    color: #94a3b8;
    margin-top: 8px;
}

/* Main Content Grid */
.main-grid {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 24px;
}

.section-title-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 16px;
}

.section-title-row h3 {
    font-size: 18px;
    font-weight: 700;
    color: #0f172a;
}

.view-all {
    font-size: 13px;
    color: #15803d;
    font-weight: 600;
    text-decoration: none;
}

.view-all:hover {
    text-decoration: underline;
}

/* Cards */
.custom-card {
    background: #ffffff;
    border-radius: 16px;
    border: 1px solid #f1f5f9;
    padding: 20px;
    margin-bottom: 16px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.02);
}

/* Announcement Card Styling */
.announcement-card {
    margin-bottom: 16px;
}

.announcement-header-tags {
    display: flex;
    gap: 8px;
    margin-bottom: 8px;
    align-items: center;
}

.badge-tag {
    background: #f1f5f9;
    color: #475569;
    font-size: 11px;
    font-weight: 600;
    padding: 3px 8px;
    border-radius: 6px;
}

.badge-tag.course-code {
    background: #dcfce7;
    color: #166534;
}

.badge-pinned {
    background: #fef3c7;
    color: #b45309;
    font-size: 11px;
    font-weight: 600;
    padding: 3px 8px;
    border-radius: 6px;
}

.announcement-card h4 {
    font-size: 16px;
    font-weight: 700;
    color: #0f172a;
    margin-bottom: 8px;
}

.announcement-card p {
    font-size: 13px;
    color: #475569;
    line-height: 1.5;
    margin-bottom: 16px;
}

.announcement-meta-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: 12px;
    color: #64748b;
    border-top: 1px solid #f8fafc;
    padding-top: 12px;
}

.author-info {
    display: flex;
    align-items: center;
    gap: 6px;
}

.author-avatar {
    width: 20px;
    height: 20px;
    background: #15803d;
    color: #fff;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 10px;
    font-weight: 700;
}

.announcement-stats {
    display: flex;
    gap: 12px;
}

/* Sidebar Widgets */
.widget-card {
    background: #ffffff;
    border-radius: 16px;
    border: 1px solid #f1f5f9;
    padding: 20px;
    margin-bottom: 20px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.02);
}

.widget-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 14px;
}

.widget-header h3 {
    font-size: 16px;
    font-weight: 700;
    color: #0f172a;
}

/* Schedule Slots */
.schedule-slot {
    padding: 12px;
    border-radius: 12px;
    margin-bottom: 10px;
    border-left: 4px solid transparent;
}

.schedule-slot.theme-orange {
    background-color: #fff7ed;
    border-left-color: #f97316;
}

.schedule-slot.theme-pink {
    background-color: #fdf2f8;
    border-left-color: #ec4899;
}

.schedule-slot b {
    display: block;
    font-size: 14px;
    color: #0f172a;
    margin-bottom: 2px;
}

.schedule-slot small {
    display: block;
    font-size: 12px;
    color: #64748b;
}

.schedule-slot .room {
    color: #64748b;
    margin-top: 2px;
}

/* To-Do Items */
.todo-item {
    display: flex;
    align-items: flex-start;
    gap: 10px;
    padding: 10px 0;
    border-bottom: 1px solid #f8fafc;
}

.todo-item:last-child {
    border-bottom: none;
    padding-bottom: 0;
}

.todo-item input[type="checkbox"] {
    margin-top: 3px;
    width: 16px;
    height: 16px;
    accent-color: #15803d;
    cursor: pointer;
}

.todo-content .todo-title {
    font-size: 13px;
    font-weight: 600;
    color: #1e293b;
    display: block;
    margin-bottom: 4px;
}

.todo-meta-row {
    display: flex;
    gap: 8px;
    align-items: center;
}

.badge-priority {
    font-size: 10px;
    font-weight: 700;
    padding: 1px 6px;
    border-radius: 4px;
    text-transform: uppercase;
}

.badge-priority.high {
    background: #fee2e2;
    color: #991b1b;
}

.badge-priority.medium {
    background: #fef3c7;
    color: #92400e;
}

.todo-due {
    font-size: 11px;
    color: #94a3b8;
}

/* Course Progress Widget */
.course-progress-item {
    margin-bottom: 16px;
}

.course-progress-item:last-child {
    margin-bottom: 0;
}

.course-progress-info {
    display: flex;
    justify-content: space-between;
    font-size: 13px;
    font-weight: 600;
    color: #1e293b;
    margin-bottom: 6px;
}

.course-progress-bar-bg {
    width: 100%;
    height: 6px;
    background-color: #f1f5f9;
    border-radius: 3px;
    overflow: hidden;
}

.course-progress-bar-fill {
    height: 100%;
    border-radius: 3px;
}


.todo-item.todo-completed .todo-title { text-decoration: line-through; color: #94a3b8; }
.todo-item.todo-completed { opacity: .72; }

@media(max-width: 1024px) {
    .stats-grid { grid-template-columns: repeat(2, 1fr); }
    .main-grid { grid-template-columns: 1fr; }
}

body.dark .dashboard-container .widget-card {
    background: #111c2e !important;
    border-color: #334155 !important;
    color: #e5e7eb !important;
}

body.dark .dashboard-container .widget-card .widget-header h3,
body.dark .dashboard-container .widget-card .todo-title,
body.dark .dashboard-container .widget-card .course-progress-info {
    color: #e5e7eb !important;
}

body.dark .dashboard-container .course-progress-bar-bg {
    background-color: #334155 !important;
}

body.dark .dashboard-container .feed-type {
    background: #153323 !important;
    border-color: #28543a !important;
    color: #a7e0b7 !important;
}

body.dark .dashboard-container .feed-actions .feed-read-action {
    background: #153323 !important;
    border-color: #28543a !important;
    color: #a7e0b7 !important;
}

body.dark .dashboard-container .feed-actions .feed-read-action:hover,
body.dark .dashboard-container .feed-announcement-card.is-read .feed-read-action {
    background: #20392b !important;
    border-color: #4b8b62 !important;
    color: #d1fae5 !important;
}
</style>

<div class="dashboard-container">
    <!-- Welcome Header -->
    <div class="welcome-header">
        <div>
            <h1>Good morning, <em><?=h($_SESSION['user']['first_name'].' '.$_SESSION['user']['last_name'])?></em> </h1>
            <p class="sub"><?=date('l, F j, Y')?> · Here's what's happening with your classes today.</p>
        </div>
        <div class="system-status">
            <span class="status-dot"></span> Learning portal ready
        </div>
    </div>

    <!-- Top Metric Cards -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-card-top">
                <div class="stat-icon" style="background: #e0f2fe; color: #0284c7;"><svg class="ui-icon" viewBox="0 0 24 24"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2Z"/></svg></div>
                <div class="stat-arrow">↗</div>
            </div>
            <div class="stat-content">
                <div class="number" id="dashboardTodayClasses"><?=count($sched)?></div>
                <div class="label">Today's Classes</div>
            </div>
            <div class="stat-footer"><span id="dashboardUpcomingClasses"><?=$upcomingCount?></span> upcoming</div>
        </div>

        <div class="stat-card">
            <div class="stat-card-top">
                <div class="stat-icon" style="background: #fef9c3; color: #ca8a04;"><svg class="ui-icon" viewBox="0 0 24 24"><path d="m5 12 4 4L19 6"/></svg></div>
                <div class="stat-arrow">↗</div>
            </div>
            <div class="stat-content">
                <div class="number" id="dashboardPendingTasks"><?=count($todos)?></div>
                <div class="label">Pending Tasks</div>
            </div>
            <div class="stat-footer"><span id="dashboardDueSoon"><?=$dueSoonCount?></span> due soon</div>
        </div>

        <div class="stat-card">
            <div class="stat-card-top">
                <div class="stat-icon" style="background: #ede9fe; color: #7c3aed;"><svg class="ui-icon" viewBox="0 0 24 24"><path d="M21 11.5a8.5 8.5 0 0 1-9 8.5 9 9 0 0 1-4-.9L3 21l1.9-4.8A8.5 8.5 0 1 1 21 11.5Z"/></svg></div>
                <div class="stat-arrow">↗</div>
            </div>
            <div class="stat-content">
                <div class="number" id="dashboardUnreadMessages"><?=$unreadCount?></div>
                <div class="label">Unread Messages</div>
            </div>
            <div class="stat-footer">Unread conversations</div>
        </div>

        <div class="stat-card">
            <div class="stat-card-top">
                <div class="stat-icon" style="background: #dcfce7; color: #16a34a;"><svg class="ui-icon" viewBox="0 0 24 24"><rect x="5" y="4" width="14" height="17" rx="2"/><path d="M9 4V2h6v2M8 10h8M8 14h8M8 18h5"/></svg></div>
                <div class="stat-arrow">↗</div>
            </div>
            <div class="stat-content">
                <div class="number"><?=h($st['att'])?></div>
                <div class="label">Attendance Rate</div>
            </div>
            <div class="stat-footer">Overall average</div>
        </div>
    </div>

    <!-- Main Content Layout -->
    <div class="main-grid">
        <!-- Keep announcements in the original main dashboard column. -->
        <div class="dashboard-announcements-column">
            <?php announcement_feed($ann,4); ?>
        </div>

        <!-- Right Column: Sidebar Widgets -->
        <div>
            <!-- Today's Schedule Widget -->
            <div class="widget-card">
                <div class="widget-header">
                    <h3>Today's Schedule</h3>
                    <a href="calendar.php" class="view-all">Full calendar</a>
                </div>
                <?php if(empty($sched)):?>
                    <div class="dashboard-widget-empty">
                        <strong><?=empty($enr)?'No courses yet':'No classes scheduled today'?></strong>
                        <p><?=empty($enr)?'Your schedule will appear here after your enrollment is confirmed.':'You have no classes on today’s schedule.'?></p>
                    </div>
                <?php else:?>
                    <?php foreach($sched as $i => $s):?>
                    <div class="schedule-slot <?=($i % 2 == 0)?'theme-orange':'theme-pink'?>">
                        <b><?=h($s['course_name'])?></b>
                        <small><?=substr($s['start_time'],0,5).' – '.substr($s['end_time'],0,5)?></small>
                        <small class="room">Room <?=h($s['room'])?></small>
                    </div>
                    <?php endforeach;?>
                <?php endif;?>
            </div>

            <!-- To-Do Widget -->
            <div class="widget-card">
                <div class="widget-header">
                    <h3>To-Do</h3>
                    <a href="todo.php" class="view-all">View all</a>
                </div>
                <div>
                    <?php if(empty($todos)):?>
                        <div class="dashboard-widget-empty">
                            <strong>You’re all caught up</strong>
                            <p>New assignments and tasks will appear here.</p>
                            <a class="view-all" href="todo.php">Open To-Do list</a>
                        </div>
                    <?php else: foreach($todos as $t):?>
                    <div class="todo-item" data-todo-row="<?= (int)$t['todo_id']?>">
                        <input type="checkbox" class="dashboard-todo-checkbox" data-todo-id="<?= (int)$t['todo_id']?>" aria-label="Mark <?=h($t['title'])?> as completed">
                        <div class="todo-content">
                            <span class="todo-title"><?=h($t['title'])?></span>
                            <div class="todo-meta-row">
                                <?php if(!empty($t['priority'])):?>
                                    <span class="badge-priority <?=h($t['priority'])?>"><?=h($t['priority'])?></span>
                                <?php endif;?>
                                <span class="todo-due">Due <?=h($t['due_date'] ?? 'Soon')?></span>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; endif;?>
                </div>
            </div>

            <!-- My Courses Progress Widget -->
            <div class="widget-card">
                <div class="widget-header">
                    <h3>My Courses</h3>
                </div>
                <div>
                    <?php if(empty($enr)):?>
                        <div class="dashboard-widget-empty">
                            <strong>No courses assigned yet</strong>
                            <p>Your course progress will appear here once your enrollment is confirmed.</p>
                        </div>
                    <?php else: foreach($enr as $e):?>
                    <div class="course-progress-item">
                        <div class="course-progress-info">
                            <span><?=h($e['course_name']??($cnames[$e['course_id']]??''))?></span>
                            <span style="color:#64748b; font-weight:normal;"><?=h($st['progress'][$e['course_id']]??0)?>%</span>
                        </div>
                        <div class="course-progress-bar-bg">
                            <div class="course-progress-bar-fill" style="width: <?=h($st['progress'][$e['course_id']]??0)?>%; background-color: <?=($e['color'] ?? '#db2777')?>;"></div>
                        </div>
                    </div>
                    <?php endforeach; endif;?>
                </div>
                <div style="margin-top: 16px; text-align: center;">
                    <a href="courses.php" class="view-all" style="font-size: 12px;">View all <?=count($enr)?> courses →</a>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Announcement actions intentionally use a normal POST + redirect for reliable server-side updates.
// Persist dashboard To-Do checkbox changes and reflect the saved state immediately.
document.addEventListener('change', async function(e){
 const checkbox=e.target.closest('.dashboard-todo-checkbox');if(!checkbox)return;
 const id=checkbox.dataset.todoId;if(!id)return;
 checkbox.disabled=true;
 try{
   const body=new FormData();body.append('id',id);
   const response=await fetch('todo_action.php',{method:'POST',body,headers:{'Accept':'application/json','X-Requested-With':'XMLHttpRequest'},credentials:'same-origin',cache:'no-store'});
   const raw=await response.text();let data;try{data=JSON.parse(raw);}catch(parseError){console.error('Dashboard todo response was not JSON:',response.status,raw.slice(0,500));throw new Error('The server did not return JSON (HTTP '+response.status+'). Check the PHP error log, then refresh and sign in again.');}
   if(!response.ok||!data.ok)throw new Error(data.error||'Could not update this task.');
   const row=checkbox.closest('[data-todo-row]');const done=String(data.status).toLowerCase()==='completed';
   checkbox.checked=done;if(row){row.classList.toggle('todo-completed',done);row.classList.toggle('done',done);const title=row.querySelector('.todo-title');if(title)title.style.textDecoration=done?'line-through':'';}
   await refreshDashboardStats();
 }catch(err){checkbox.checked=!checkbox.checked;alert(err.message||'Could not update this task. Please try again.');}
 finally{checkbox.disabled=false;}
});

async function refreshDashboardStats(){
 try{
   const response=await fetch('dashboard_data.php',{credentials:'same-origin',headers:{'Accept':'application/json'},cache:'no-store'});
   const raw=await response.text();let data;try{data=JSON.parse(raw);}catch(parseError){console.error('Dashboard data response was not JSON:',response.status,raw.slice(0,500));return;}if(!response.ok||!data.ok)return;
   const map={dashboardTodayClasses:data.today_classes,dashboardUpcomingClasses:data.upcoming_classes,dashboardPendingTasks:data.pending_tasks,dashboardDueSoon:data.due_soon,dashboardUnreadMessages:data.unread_messages};
   Object.entries(map).forEach(([id,value])=>{const node=document.getElementById(id);if(node&&Number.isFinite(Number(value)))node.textContent=String(value);});
 }catch(error){/* Keep the last known dashboard values if the network is temporarily unavailable. */}
}
refreshDashboardStats();window.setInterval(()=>{if(document.visibilityState==='visible')refreshDashboardStats();},30000);
</script>
<?php bottom();?>