<?php require '../inc.php';guard('student');[$enr,$ids]=ctx();$em=array_column($enr,'course_id','enrollment_id');
$courseRows=api('course','byids',['ids'=>$ids]);$cm=[];$courseNames=[];
foreach($courseRows as $course){$cid=(int)$course['course_id'];$cm[$cid]=$course['course_code']??'';$courseNames[$cid]=$course['course_name']??'';}
$selectedCourse=isset($_GET['course'])&&$_GET['course']!=='all'?(int)$_GET['course']:'all';
if($selectedCourse!=='all'&&!in_array($selectedCourse,array_map('intval',array_values($em)),true))$selectedCourse='all';
$g=api('grade','mine',['ids'=>implode(',',array_keys($em))]);
if($selectedCourse!=='all')$g=array_values(array_filter($g,fn($x)=>(int)($em[$x['enrollment_id']]??0)===$selectedCourse));$at=array_column(api('assignment','byids',['ids'=>implode(',',array_filter(array_column($g,'assignment_id')))]),'title','assignment_id');
$gradeByAssignment=[];foreach($g as $x){$gradeByAssignment[(int)$x['assignment_id']]=$x;}
$rows=api('assignment','student_list',['ids'=>$ids]);
if($selectedCourse!=='all')$rows=array_values(array_filter($rows,fn($x)=>(int)$x['course_id']===$selectedCourse));
$tot=[];$all=[];foreach($g as $x){$pc=$x['max_score']>0?$x['score']/$x['max_score']*100:0;$tot[$em[$x['enrollment_id']]??0][]=$pc;$all[]=$pc;}
$avg=$all?round(array_sum($all)/count($all),1):'—';top('Grades','grades');?>

<style>
/* Grades Page Styling matching reference design */
.grades-container {
    padding: 24px 32px;
    background-color: #f8fafc;
    min-height: 100vh;
    font-family: 'Inter', sans-serif;
    color: #1e293b;
    box-sizing: border-box;
}

.grades-header-row {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 24px;
}

.grades-header h1 {
    font-size: 24px;
    font-weight: 700;
    color: #0f172a;
    margin-bottom: 4px;
}

.grades-header p.sub {
    font-size: 14px;
    color: #64748b;
}

.filter-dropdown-btn {
    position:relative;
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
    gap: 10px;
    box-shadow: 0 4px 14px rgba(15,23,42,.05);
    transition: border-color .18s, box-shadow .18s, transform .18s;
}
.filter-dropdown-btn:focus-within { border-color:#176b45; box-shadow:0 0 0 3px rgba(23,107,69,.12); }
.filter-dropdown-btn select { appearance:none; -webkit-appearance:none; min-width:145px; max-width:min(320px,55vw)!important; padding:9px 34px 9px 12px!important; border:1px solid #dbe4ee!important; border-radius:8px!important; background:#f8fafc!important; color:#1e293b!important; font-weight:600!important; outline:none; cursor:pointer; }
.filter-dropdown-btn::after { content:""; position:absolute; right:25px; top:50%; width:7px; height:7px; border-right:2px solid #64748b; border-bottom:2px solid #64748b; transform:translateY(-70%) rotate(45deg); pointer-events:none; }
body.dark .filter-dropdown-btn { background:var(--card); border-color:var(--bd); color:var(--ink); }
body.dark .filter-dropdown-btn select { background:#182536!important; color:var(--ink)!important; border-color:#34465c!important; }
@media(max-width:640px){.grades-container{padding:18px 14px}.grades-header-row{gap:14px;flex-direction:column;align-items:stretch}.filter-dropdown-btn{justify-content:space-between;width:100%;box-sizing:border-box}.filter-dropdown-btn select{flex:1;min-width:0;max-width:75vw!important}}

/* Top Course Summary Cards Grid */
.grades-stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
    gap: 16px;
    margin-bottom: 24px;
}

.grade-stat-card {
    background: #ffffff;
    border-radius: 16px;
    border: 1px solid #f1f5f9;
    padding: 16px;
    text-align: center;
    box-shadow: 0 1px 3px rgba(0,0,0,0.02);
}

.grade-stat-card small {
    display: block;
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    margin-bottom: 6px;
}

.grade-stat-card:nth-child(1) small { color: #dc2626; }
.grade-stat-card:nth-child(2) small { color: #2563eb; }
.grade-stat-card:nth-child(3) small { color: #16a34a; }
.grade-stat-card:nth-child(4) small { color: #ea580c; }
.grade-stat-card:nth-child(5) small { color: #ca8a04; }
.grade-stat-card:nth-child(6) small { color: #9333ea; }

.grade-stat-card h3 {
    font-size: 20px;
    font-weight: 800;
    color: #0f172a;
}

/* Main Layout Grid */
.grades-main-layout {
    display: grid;
    grid-template-columns: minmax(0, 2fr) minmax(260px, 1fr);
    gap: 24px;
}

/* Card & Table Styling */
.content-card {
    background: #ffffff;
    border-radius: 16px;
    border: 1px solid #f1f5f9;
    padding: 24px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.02);
    margin-bottom: 24px;
}

.card-title-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
}

.card-title-row h3 {
    font-size: 16px;
    font-weight: 700;
    color: #0f172a;
}

.overall-avg-badge {
    font-size: 13px;
    color: #64748b;
    font-weight: 500;
}

.overall-avg-badge b {
    color: #16a34a;
}

/* Data Table */
.grades-table {
    width: 100%;
    border-collapse: collapse;
}

.grades-table th {
    font-size: 11px;
    font-weight: 700;
    color: #64748b;
    text-transform: uppercase;
    padding: 10px 12px;
    border-bottom: 1px solid #f1f5f9;
    text-align: left;
}

.grades-table td {
    padding: 14px 12px;
    font-size: 13px;
    color: #334155;
    border-bottom: 1px solid #f8fafc;
    vertical-align: middle;
}

.grades-table tr:last-child td {
    border-bottom: none;
}

.course-code-pill {
    font-weight: 700;
    font-size: 12px;
}

.status-badge {
    padding: 4px 10px;
    border-radius: 6px;
    font-size: 11px;
    font-weight: 600;
}

.status-badge.completed {
    background: #dcfce7;
    color: #166534;
}

.status-badge.pending {
    background: #fef3c7;
    color: #b45309;
}

/* Right Sidebar Widgets */
.calc-circle-box {
    text-align: center;
    padding: 10px 0 20px 0;
    border-bottom: 1px solid #f1f5f9;
    margin-bottom: 20px;
}

.score-circle {
    width: 80px;
    height: 80px;
    border: 4px solid #dcfce7;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 10px auto;
    font-size: 20px;
    font-weight: 800;
    color: #16a34a;
}

.breakdown-row {
    margin-bottom: 14px;
}

.breakdown-info {
    display: flex;
    justify-content: space-between;
    font-size: 12px;
    color: #475569;
    margin-bottom: 4px;
    font-weight: 500;
}

.breakdown-bar {
    width: 100%;
    height: 6px;
    background: #f1f5f9;
    border-radius: 3px;
    overflow: hidden;
}

.breakdown-fill {
    height: 100%;
    background: #16a34a;
    border-radius: 3px;
}

/* Grade Scale Table */
.scale-table {
    width: 100%;
    font-size: 12px;
}

.scale-table td {
    padding: 6px 0;
    color: #475569;
    border-bottom: 1px solid #f8fafc;
}

.scale-table td:last-child {
    text-align: right;
    font-weight: 600;
    color: #0f172a;
}

@media(max-width: 1200px) {
    .grades-stats-grid { grid-template-columns: repeat(3, 1fr); }
    .grades-main-layout { grid-template-columns: 1fr; }
}
</style>

<div class="grades-container">
    <div class="grades-header-row">
        <div class="grades-header">
            <h1>Grades</h1>
            <p class="sub">Second Semester, A.Y. 2026–2027</p>
        </div>
        <label class="filter-dropdown-btn"> <span style="white-space:nowrap;color:#64748b;font-size:12px;text-transform:uppercase;letter-spacing:.04em">Course</span>
            <select id="gradeCourseFilter" aria-label="Filter grades by course" onchange="location.href='?course='+encodeURIComponent(this.value)">
                <option value="all" <?=$selectedCourse==='all'?'selected':''?>>All Courses</option>
                <?php foreach($courseRows as $course): ?><option value="<?=(int)$course['course_id']?>" <?=(string)$selectedCourse===(string)$course['course_id']?'selected':''?>><?=h(($course['course_code']??'').' – '.($course['course_name']??''))?></option><?php endforeach; ?>
            </select>
        </label>
    </div>

    <!-- Top Course Score Cards Grid -->
    <div class="grades-stats-grid">
        <?php foreach($cm as $cid=>$code):?>
        <div class="grade-stat-card">
            <small><?=h($code)?></small>
            <h3><?=isset($tot[$cid])?round(array_sum($tot[$cid])/count($tot[$cid]),1):'—'?></h3>
        </div>
        <?php endforeach;?>
    </div>

    <!-- Main Content Grid -->
    <div class="grades-main-layout">
        <!-- Left Column: Grade Records Table -->
        <div class="content-card" style="margin-bottom:0;">
            <div class="card-title-row">
                <h3>Grade Records</h3>
                <span class="overall-avg-badge">Overall Average: <b><?=$avg?></b></span>
            </div>
            
            <div style="overflow-x: auto;">
                <table class="grades-table">
                    <tr>
                        <th>COURSE</th>
                        <th>ACTIVITY</th>
                        <th>SCORE</th>
                        <th>WEIGHT</th>
                        <th>WEIGHTED</th>
                        <th>STATUS</th>
                    </tr>
                    <?php if(!$rows): ?><tr><td colspan="6" style="text-align:center;padding:28px;color:#64748b">No assignments have been published for your enrolled courses yet.</td></tr><?php endif; ?>
                    <?php foreach($rows as $a):
                        $courseId=(int)$a['course_id'];$enrollmentId=array_search($courseId,$em,true);$grade=$gradeByAssignment[(int)$a['assignment_id']]??null;
                        $submitted=!empty($a['submission_id']);$isGraded=$grade && $grade['max_score']>0;
                        $late=$submitted && !empty($a['due_date']) && strtotime($a['submitted_at'])>strtotime($a['due_date']);
                        $status=$isGraded?'Graded':($submitted?($late?'Submitted late':'Submitted'):'Not submitted');
                        $statusClass=$isGraded?'completed':'pending';
                    ?>
                    <tr>
                        <td><span class="course-code-pill"><?=h($cm[$courseId]??'')?></span><div style="font-size:11px;color:#64748b"><?=h($courseNames[$courseId]??'')?></div></td>
                        <td><div style="font-weight:600;color:#0f172a;"><?=h($a['title'])?></div><div style="font-size:11px;color:#94a3b8;">Due: <?=h($a['due_date']?:'No due date')?></div></td>
                        <td><?php if($isGraded): ?><b><?=h($grade['score'])?></b>/<?=h($grade['max_score'])?><?php elseif($submitted): ?>Awaiting grade<?php else: ?>—<?php endif; ?></td>
                        <td><?= $isGraded?h($grade['weight']).'%':'—' ?></td>
                        <td><?= $isGraded?number_format($grade['max_score']>0?$grade['score']/$grade['max_score']*$grade['weight']:0,2):'—' ?></td>
                        <td><span class="status-badge <?=h($statusClass)?>"><?=h($status)?></span></td>
                    </tr>
                    <?php endforeach;?>
                </table>
            </div>
        </div>

        <!-- Right Column: Calculation Breakdown & Grade Scale -->
        <div>
            <!-- Grade Calculation Widget -->
            <div class="content-card">
                <h3>Grade Calculation</h3>
                <div class="calc-circle-box">
                    <div class="score-circle"><?=$avg?></div>
                    <div style="font-size:12px; color:#64748b; font-weight:500;">Overall Average</div>
                </div>

                <div style="font-size:12px; font-weight:700; color:#0f172a; margin-bottom:12px;">Grade breakdown by weight:</div>
                
                <div class="breakdown-row">
                    <div class="breakdown-info"><span>Quizzes</span><span>20% weight</span></div>
                    <div class="breakdown-bar"><div class="breakdown-fill" style="width: 85%;"></div></div>
                </div>
                <div class="breakdown-row">
                    <div class="breakdown-info"><span>Exercises</span><span>35% weight</span></div>
                    <div class="breakdown-bar"><div class="breakdown-fill" style="width: 90%;"></div></div>
                </div>
                <div class="breakdown-row">
                    <div class="breakdown-info"><span>Assignments</span><span>20% weight</span></div>
                    <div class="breakdown-bar"><div class="breakdown-fill" style="width: 95%;"></div></div>
                </div>
                <div class="breakdown-row">
                    <div class="breakdown-info"><span>Lab Activities</span><span>15% weight</span></div>
                    <div class="breakdown-bar"><div class="breakdown-fill" style="width: 88%;"></div></div>
                </div>
                <div class="breakdown-row" style="margin-bottom:0;">
                    <div class="breakdown-info"><span>Midterm/Finals</span><span>10% weight</span></div>
                    <div class="breakdown-bar"><div class="breakdown-fill" style="width: 75%;"></div></div>
                </div>
            </div>

            <!-- Grade Scale Widget -->
            <div class="content-card" style="margin-bottom:0;">
                <div style="display:flex; align-items:center; gap:8px; margin-bottom:14px;">
                    <span><svg class="ui-icon" viewBox="0 0 24 24"><path d="M4 19V5M4 19h17M8 15v-4m5 4V7m5 8V3"/></svg></span> <h3 style="font-size:15px; font-weight:700; color:#0f172a;">Grade Scale</h3>
                </div>
                <table class="scale-table">
                    <tr><td>1.0</td><td>97–100</td><td>Excellent</td></tr>
                    <tr><td>1.25</td><td>93–96</td><td>Excellent</td></tr>
                    <tr><td>1.5</td><td>89–92</td><td>Very Good</td></tr>
                    <tr><td>1.75</td><td>85–88</td><td>Very Good</td></tr>
                    <tr><td>2.0</td><td>81–84</td><td>Good</td></tr>
                    <tr><td>2.5–3.0</td><td>75–80</td><td>Passing</td></tr>
                    <tr><td>5.0</td><td>Below 75</td><td>Failed</td></tr>
                </table>
            </div>
        </div>
    </div>
</div>

<?php bottom();?>