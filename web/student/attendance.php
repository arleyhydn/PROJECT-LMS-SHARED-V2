<?php require '../inc.php';guard('student');[$enr,$ids]=ctx();$em=array_column($enr,'course_id','enrollment_id');$courseRows=api('course','byids',['ids'=>$ids]);
$cm=[];$courseNames=[];
foreach($courseRows as $course){$cid=(int)$course['course_id'];$cm[$cid]=$course['course_code']??'';$courseNames[$cid]=trim((string)($course['course_name']??'')) ?: ($course['course_code']??'Course name unavailable');}
$n=[];$T=['present'=>0,'late'=>0,'absent'=>0];foreach(api('attendance','mine',['ids'=>implode(',',array_keys($em))]) as $x){$n[$em[$x['enrollment_id']]??0][$x['status']]=$x['n'];$T[$x['status']]=($T[$x['status']]??0)+$x['n'];}
$sum=array_sum($T);$rate=$sum?round(($T['present']+$T['late'])/$sum*100):0;top('Attendance','attendance');?>

<style>
/* Attendance Page Styling matching reference design */
.attendance-container {
    padding: 24px 32px;
    background-color: #f8fafc;
    min-height: 100vh;
    font-family: 'Inter', sans-serif;
    color: #1e293b;
    box-sizing: border-box;
}

.attendance-header {
    margin-bottom: 24px;
}

.attendance-header h1 {
    font-size: 24px;
    font-weight: 700;
    color: #0f172a;
    margin-bottom: 4px;
}

.attendance-header p.sub {
    font-size: 14px;
    color: #64748b;
}

/* Top Stat Cards Grid */
.attendance-stats-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 20px;
    margin-bottom: 32px;
}

.att-stat-card {
    background: #ffffff;
    border-radius: 16px;
    border: 1px solid #f1f5f9;
    padding: 20px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.02);
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    min-height: 110px;
}

.att-stat-card.present-card { background-color: #f0fdf4; border-color: #dcfce7; }
.att-stat-card.late-card { background-color: #fefce8; border-color: #fef08a; }
.att-stat-card.absent-card { background-color: #fef2f2; border-color: #fee2e2; }

.att-stat-top {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
}

.att-stat-icon {
    font-size: 18px;
}

.att-stat-number {
    font-size: 28px;
    font-weight: 700;
    color: #0f172a;
    line-height: 1.1;
    margin-bottom: 2px;
}

.att-stat-label {
    font-size: 13px;
    color: #64748b;
    font-weight: 500;
}

.att-stat-subtext {
    font-size: 12px;
    color: #94a3b8;
    margin-top: 6px;
}

/* Card & Table Styling */
.att-content-card {
    background: #ffffff;
    border-radius: 16px;
    border: 1px solid #f1f5f9;
    padding: 24px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.02);
    margin-bottom: 32px;
}

.card-section-title {
    font-size: 16px;
    font-weight: 700;
    color: #0f172a;
    margin-bottom: 20px;
}

.attendance-table {
    width: 100%;
    border-collapse: collapse;
}

.attendance-table th {
    font-size: 11px;
    font-weight: 700;
    color: #64748b;
    text-transform: uppercase;
    padding: 10px 12px;
    border-bottom: 1px solid #f1f5f9;
    text-align: left;
}

.attendance-table td {
    padding: 16px 12px;
    font-size: 13px;
    color: #334155;
    border-bottom: 1px solid #f8fafc;
    vertical-align: middle;
}

.attendance-table tr:last-child td {
    border-bottom: none;
}

.course-code-badge-inline {
    background: #f1f5f9;
    color: #334155;
    font-size: 11px;
    font-weight: 700;
    padding: 3px 8px;
    border-radius: 6px;
    margin-right: 8px;
}

/* Progress bar inside table */
.bar {
    width: 100px;
    height: 6px;
    background-color: #f1f5f9;
    border-radius: 3px;
    overflow: hidden;
    display: inline-block;
    margin-left: 8px;
    vertical-align: middle;
}

.bar i {
    display: block;
    height: 100%;
    background-color: #16a34a;
    border-radius: 3px;
    font-style: normal;
}

/* Status Badges */
.att-status-badge {
    padding: 4px 12px;
    border-radius: 6px;
    font-size: 11px;
    font-weight: 600;
    display: inline-block;
}

.att-status-badge.excellent { background: #dcfce7; color: #166534; }
.att-status-badge.good { background: #e0f2fe; color: #0369a1; }
.att-status-badge.passing { background: #fef3c7; color: #b45309; }

/* Calendar Grid Section */
.calendar-month-grid {
    display: grid;
    grid-template-columns: repeat(7, 1fr);
    gap: 10px;
    margin-top: 16px;
}

.cal-weekday-header {
    text-align: center;
    font-size: 12px;
    font-weight: 600;
    color: #64748b;
    padding-bottom: 8px;
}

.cal-day-cell {
    background: #f8fafc;
    border: 1px solid #f1f5f9;
    border-radius: 12px;
    min-height: 80px;
    padding: 10px;
    font-size: 12px;
    color: #94a3b8;
}

.cal-day-cell.present { background: #f0fdf4; border-color: #dcfce7; color: #16a34a; font-weight: 700; }
.cal-day-cell.late { background: #fefce8; border-color: #fef08a; color: #ca8a04; font-weight: 700; }
.cal-day-cell.absent { background: #fef2f2; border-color: #fee2e2; color: #dc2626; font-weight: 700; }

@media(max-width: 1024px) {
    .attendance-stats-grid { grid-template-columns: repeat(2, 1fr); }
}
</style>

<div class="attendance-container">
    <div class="attendance-header">
        <h1>Attendance</h1>
        <p class="sub">Second Semester, A.Y. 2026–2027</p>
    </div>

    <!-- Top Metric Summary Cards -->
    <div class="attendance-stats-grid">
        <div class="att-stat-card">
            <div class="att-stat-top">
                <div class="att-stat-icon"><svg class="ui-icon" viewBox="0 0 24 24"><path d="M4 19V5M4 19h17M8 15v-4m5 4V7m5 8V3"/></svg></div>
                <span style="font-size:12px; color:#94a3b8;">↗</span>
            </div>
            <div>
                <div class="att-stat-number"><?=$rate?>%</div>
                <div class="att-stat-label">Overall Rate</div>
            </div>
            <div class="att-stat-subtext">All courses</div>
        </div>

        <div class="att-stat-card present-card">
            <div class="att-stat-top">
                <div class="att-stat-icon"><svg class="ui-icon" viewBox="0 0 24 24"><path d="m5 12 4 4L19 6"/></svg></div>
            </div>
            <div>
                <div class="att-stat-number"><?=$T['present']?></div>
                <div class="att-stat-label">Present</div>
            </div>
            <div class="att-stat-subtext">85% of classes</div>
        </div>

        <div class="att-stat-card late-card">
            <div class="att-stat-top">
                <div class="att-stat-icon"><svg class="ui-icon" viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg></div>
            </div>
            <div>
                <div class="att-stat-number"><?=$T['late']?></div>
                <div class="att-stat-label">Late</div>
            </div>
            <div class="att-stat-subtext">Tardiness records</div>
        </div>

        <div class="att-stat-card absent-card">
            <div class="att-stat-top">
                <div class="att-stat-icon"><svg class="ui-icon" viewBox="0 0 24 24"><path d="m6 6 12 12M18 6 6 18"/></svg></div>
            </div>
            <div>
                <div class="att-stat-number"><?=$T['absent']?></div>
                <div class="att-stat-label">Absent</div>
            </div>
            <div class="att-stat-subtext">Missed classes</div>
        </div>
    </div>

    <!-- Per-Course Attendance Table -->
    <div class="att-content-card">
        <div class="card-section-title">Per-Course Attendance</div>
        <div style="overflow-x: auto;">
            <table class="attendance-table">
                <tr>
                    <th>Course</th>
                    <th>Present</th>
                    <th>Late</th>
                    <th>Absent</th>
                    <th>Total Classes</th>
                    <th>Attendance Rate</th>
                    <th>Status</th>
                </tr>
                <?php foreach($cm as $cid=>$code):
                    $r=$n[$cid]??[];
                    $p=$r['present']??0;
                    $l=$r['late']??0;
                    $a=$r['absent']??0;
                    $t=$p+$l+$a;
                    $pc=$t?round(($p+$l)/$t*100):0;
                    
                    $statusClass = 'excellent';
                    $statusText = 'Excellent';
                    if($pc < 80) { $statusClass = 'passing'; $statusText = 'Passing'; }
                    elseif($pc < 90) { $statusClass = 'good'; $statusText = 'Good'; }
                ?>
                <tr>
                    <td>
                        <span class="course-code-badge-inline"><?=h($code)?></span>
                        <span style="font-weight:600; color:#0f172a;"><?=h($courseNames[$cid]??'Course name unavailable')?></span>
                    </td>
                    <td style="color:#16a34a; font-weight:700;"><?=$p?></td>
                    <td style="color:#ca8a04; font-weight:700;"><?=$l?></td>
                    <td style="color:#dc2626; font-weight:700;"><?=$a?></td>
                    <td style="color:#64748b; font-weight:600;"><?=$t?></td>
                    <td>
                        <div class="attendance-rate-cell">
                            <span><?=$pc?>%</span>
                            <div class="bar" role="progressbar" aria-label="Attendance rate" aria-valuemin="0" aria-valuemax="100" aria-valuenow="<?=$pc?>"><i style="width:<?=$pc?>%"></i></div>
                        </div>
                    </td>
                    <td>
                        <span class="att-status-badge <?=$statusClass?>"><?=$statusText?></span>
                    </td>
                </tr>
                <?php endforeach;?>
            </table>
        </div>
    </div>

</div>

<?php bottom();?>