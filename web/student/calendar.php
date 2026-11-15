<?php
require '../inc.php'; guard('student');
[$enr,$ids]=ctx();
$schedules=api('course','schedule',['ids'=>$ids]);
$by=[]; foreach($schedules as $item){$key=strtolower(substr((string)($item['day_of_week']??''),0,3));$by[$key][]=$item;}
$monthParam=$_GET['month']??date('Y-m');
$monthDate=DateTime::createFromFormat('!Y-m',$monthParam) ?: new DateTime('first day of this month');
$monthStart=clone $monthDate; $monthStart->modify('first day of this month');
$selectedParam=$_GET['day']??date('Y-m-d');
$selected=DateTime::createFromFormat('!Y-m-d',$selectedParam) ?: new DateTime('today');
if($selected->format('Y-m')!==$monthDate->format('Y-m')) $selected=clone $monthStart;
$prev=(clone $monthStart)->modify('-1 month')->format('Y-m'); $next=(clone $monthStart)->modify('+1 month')->format('Y-m');
$dayCode=strtolower($selected->format('D')); $selectedSchedules=$by[$dayCode]??[];
$firstWeekday=(int)$monthStart->format('N'); $daysInMonth=(int)$monthStart->format('t');
top('Calendar','calendar');
?>
<style>
.student-calendar{padding:24px 30px;background:#f8fafc;min-height:calc(100vh - 70px);color:#1e293b}.calendar-head{display:flex;justify-content:space-between;align-items:center;gap:16px;flex-wrap:wrap;margin-bottom:20px}.calendar-head h1{margin:0;font-size:25px}.calendar-head p{margin:5px 0;color:#64748b}.calendar-nav{display:flex;gap:8px;align-items:center}.calendar-nav a,.calendar-nav span{padding:10px 14px;border:1px solid #e2e8f0;border-radius:9px;background:#fff;text-decoration:none;color:#334155;font-weight:600}.calendar-nav span{min-width:140px;text-align:center}.calendar-columns{display:grid;grid-template-columns:minmax(0,1.5fr) minmax(280px,1fr);gap:20px}.month-card,.selected-day-card{background:#fff;border:1px solid #e2e8f0;border-radius:16px;padding:20px;box-shadow:0 2px 6px #0f172a08}.month-grid{display:grid;grid-template-columns:repeat(7,minmax(0,1fr));gap:7px}.weekday{text-align:center;font-size:12px;color:#64748b;font-weight:700;padding:8px 0}.day-cell{min-height:76px;border:1px solid #edf2f7;border-radius:10px;background:#fff;text-decoration:none;color:#334155;padding:8px;display:flex;flex-direction:column;gap:6px}.day-cell:hover{border-color:#86efac;background:#f0fdf4}.day-cell.selected{border:2px solid #15803d;background:#f0fdf4;padding:7px}.day-cell.today .day-number{color:#15803d;font-weight:800}.day-number{font-size:13px;font-weight:600}.day-dot{font-size:10px;color:#15803d;background:#dcfce7;border-radius:5px;padding:3px 4px;line-height:1.2;overflow:hidden;white-space:nowrap;text-overflow:ellipsis}.selected-day-card h2{margin:0 0 5px;font-size:20px}.selected-day-card .muted{color:#64748b;font-size:13px;margin:0 0 18px}.schedule-item{padding:14px 0;border-top:1px solid #f1f5f9}.schedule-item strong{display:block;font-size:14px;margin-bottom:5px}.schedule-item span{display:block;font-size:12px;color:#64748b;margin-top:4px}.no-classes{padding:18px;border-radius:10px;background:#f8fafc;color:#64748b;font-size:14px}@media(max-width:850px){.calendar-columns{grid-template-columns:1fr}.day-cell{min-height:60px;padding:5px}.day-dot{display:none}.student-calendar{padding:18px 14px}}
</style>
<div class="student-calendar">
 <div class="calendar-head"><div><h1>Class Calendar</h1><p>Select a date to view your course schedule.</p></div><div class="calendar-nav"><a href="?month=<?=h($prev)?>&day=<?=h($prev.'-01')?>" aria-label="Previous month">‹ Prev</a><span><?=h($monthStart->format('F Y'))?></span><a href="?month=<?=h($next)?>&day=<?=h($next.'-01')?>" aria-label="Next month">Next ›</a></div></div>
 <div class="calendar-columns">
  <section class="month-card"><div class="month-grid">
   <?php foreach(['Mon','Tue','Wed','Thu','Fri','Sat','Sun'] as $wd): ?><div class="weekday"><?=h($wd)?></div><?php endforeach; ?>
   <?php for($blank=1;$blank<$firstWeekday;$blank++): ?><div aria-hidden="true"></div><?php endfor; ?>
   <?php for($d=1;$d<=$daysInMonth;$d++): $date=DateTime::createFromFormat('!Y-m-d',$monthStart->format('Y-m-').sprintf('%02d',$d));$key=strtolower($date->format('D'));$events=$by[$key]??[];$isSelected=$date->format('Y-m-d')===$selected->format('Y-m-d');$isToday=$date->format('Y-m-d')===date('Y-m-d'); ?>
    <a class="day-cell <?=$isSelected?'selected':''?> <?=$isToday?'today':''?>" href="?month=<?=h($monthStart->format('Y-m'))?>&day=<?=h($date->format('Y-m-d'))?>" aria-label="<?=h($date->format('F j, Y'))?>, <?=count($events)?> scheduled classes"><span class="day-number"><?=$d?></span><?php if($events): ?><span class="day-dot"><?=count($events)?> class<?=count($events)!==1?'es':''?></span><?php endif; ?></a>
   <?php endfor; ?>
  </div></section>
  <section class="selected-day-card"><h2><?=h($selected->format('l, F j'))?></h2><p class="muted">Your schedule for this day</p>
   <?php if(!$selectedSchedules): ?><div class="no-classes">No classes scheduled for this day.</div><?php else: foreach($selectedSchedules as $item): ?><div class="schedule-item"><strong><?=h($item['course_name']??$item['course_code']??'Course')?></strong><span><?=h($item['course_code']??'')?></span><span><?=h(substr((string)($item['start_time']??''),0,5))?> – <?=h(substr((string)($item['end_time']??''),0,5))?></span><span>Room <?=h($item['room']??'TBA')?></span></div><?php endforeach; endif; ?>
  </section>
 </div>
</div>
<?php bottom(); ?>
