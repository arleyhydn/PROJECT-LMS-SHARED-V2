<?php require '../inc.php';guard('professor');
$c=api('course','mine');$ids=implode(',',array_column($c,'course_id'));$ro=api('enrollment','roster',['ids'=>$ids]);$nm=names(array_column($ro,'student_id'));
$cur=(int)($_GET['course']??$_POST['course']??($c[0]['course_id']??0));$date=$_GET['date']??$_POST['date']??date('Y-m-d');
$okd=preg_match('/^\d{4}-\d{2}-\d{2}$/',$date)&&$date<=date('Y-m-d');if(!$okd)$date=date('Y-m-d');
$re=array_values(array_filter($ro,fn($r)=>$r['course_id']==$cur));$valid=array_column($re,'student_id','enrollment_id');
if($_POST&&$okd){$rows=[];foreach($_POST['status']??[] as $eid=>$s)if(isset($valid[$eid]))$rows[$eid]=$s;api('attendance','record',[],['date'=>$date,'rows'=>json_encode($rows)]);header("Location: attendance.php?course=$cur&date=$date&saved=1");exit;}
$k=implode(',',array_keys($valid));$ex=array_column(api('attendance','session',['ids'=>$k,'date'=>$date]),'status','enrollment_id');
$sum=[];foreach(api('attendance','mine',['ids'=>$k]) as $a)$sum[$a['enrollment_id']][$a['status']]=$a['n'];top('Attendance','attendance');?>
<style>
.at-wrap{padding:24px 32px;background:#f8fafc;min-height:100vh;font-family:'Inter',sans-serif;color:#1e293b;box-sizing:border-box}
.at-page-heading{margin-bottom:20px}.at-page-heading h1{font-size:24px;font-weight:700;color:#0f172a;margin:0 0 4px}.at-sub{font-size:14px;color:#64748b;margin:0}
.at-card{background:#fff;border:1px solid #e2e8f0;border-radius:16px;padding:22px;margin-bottom:18px;box-shadow:0 5px 18px rgba(15,23,42,.045)}
.at-filter-card{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px;align-items:end}
.at-filter-field{display:grid;gap:7px;min-width:0;color:#475569;font-size:12px;font-weight:650}
.at-filter-field select,.at-filter-field input[type=date]{width:100%;min-width:0;height:44px;padding:10px 12px;border:1px solid #d8e1e9;border-radius:10px;background:#fff;color:#1e293b;font:inherit;font-size:13px}
.at-filter-field select:focus,.at-filter-field input[type=date]:focus{border-color:#318254;outline:none;box-shadow:0 0 0 3px rgba(49,130,84,.13)}
.at-card-heading{display:flex;align-items:center;justify-content:space-between;gap:14px;margin-bottom:16px}.at-card-heading h2{margin:0 0 4px;color:#0f172a;font-size:16px;font-weight:700}.at-card-heading p{margin:0;color:#64748b;font-size:12px}
.at-roster-count{flex:0 0 auto;padding:6px 10px;border:1px solid #d8e9dc;border-radius:9px;background:#f1f8f3;color:#17613a;font-size:12px;font-weight:700}
.at-table-wrap{max-width:100%;overflow-x:auto;border:1px solid #e5eaf0;border-radius:12px;background:#fff}
.at-t{width:100%;min-width:720px;border-collapse:collapse}.at-t th{text-align:left;font-size:10px;color:#64748b;padding:12px;background:#f8fafc;text-transform:uppercase;letter-spacing:.04em;white-space:nowrap}.at-t td{padding:13px 12px;border-top:1px solid #eef2f1;font-size:13px;color:#334155;vertical-align:middle}.at-t tbody tr:hover td{background:#f8fafc}.at-t td:first-child b{color:#0f172a}
.at-status-options{display:flex;align-items:center;flex-wrap:wrap;gap:6px}.at-status-option{display:inline-flex;align-items:center;gap:5px;margin:0;padding:6px 8px;border:1px solid #e2e8f0;border-radius:8px;background:#fff;color:#475569;font-size:11px;cursor:pointer}.at-status-option input{margin:0;accent-color:#15803d}.at-status-option:has(input:checked){border-color:#9fc9ad;background:#f0f8f2;color:#17613a}
.at-actions{display:flex;align-items:center;justify-content:flex-end;flex-wrap:wrap;gap:10px;margin-top:16px}
.at-btn{display:inline-flex;align-items:center;justify-content:center;min-height:42px;padding:10px 16px;border:1px solid #17613a;border-radius:10px;background:#17613a;color:#fff;font:inherit;font-size:13px;font-weight:700;cursor:pointer;transition:background .15s,border-color .15s,transform .15s}.at-btn:hover{background:#104c2d;border-color:#104c2d;transform:translateY(-1px)}.at-btn.alt{background:#fff;color:#17613a;border-color:#cfe3d6}.at-btn.alt:hover{background:#f1f8f3;border-color:#9fc9ad}
.at-empty-state{margin:0;padding:18px;border:1px dashed #cbd5e1;border-radius:12px;background:#f8fafc;color:#64748b;font-size:13px}
.at-ok{background:#dcfce7;color:#166534;padding:12px 16px;border:1px solid #bbf7d0;border-radius:10px;margin-bottom:16px;font-size:14px}
body.dark .at-wrap{background:var(--bg);color:var(--ink)}body.dark .at-page-heading h1,body.dark .at-card-heading h2{color:var(--ink)}body.dark .at-page-heading .at-sub,body.dark .at-card-heading p{color:var(--mut)}
body.dark .at-card{background:var(--card);border-color:var(--bd);color:var(--ink);box-shadow:0 8px 24px rgba(0,0,0,.18)}body.dark .at-filter-field{color:#cbd5e1}body.dark .at-filter-field select,body.dark .at-filter-field input[type=date]{background:#0b1220;border-color:var(--bd);color:var(--ink)}
body.dark .at-roster-count{background:#153323;border-color:#28543a;color:#a7e0b7}body.dark .at-table-wrap{background:var(--card);border-color:var(--bd)}body.dark .at-t th{background:#172638;color:#cbd5e1}body.dark .at-t td{border-color:var(--bd);color:var(--ink)}body.dark .at-t tbody tr:hover td{background:#172638}body.dark .at-t td:first-child b{color:var(--ink)}
body.dark .at-status-option{background:#0b1220;border-color:var(--bd);color:#cbd5e1}body.dark .at-status-option:has(input:checked){background:#153323;border-color:#4b8b62;color:#a7e0b7}body.dark .at-btn.alt{background:#172638;border-color:#4b8b62;color:#a7e0b7}body.dark .at-btn.alt:hover{background:#20392b}body.dark .at-empty-state{background:#0b1220;border-color:var(--bd);color:var(--mut)}body.dark .at-ok{background:#153323;border-color:#28543a;color:#a7e0b7}
@media(max-width:640px){.at-wrap{padding:18px 14px}.at-card{padding:16px;border-radius:14px}.at-filter-card{grid-template-columns:minmax(0,1fr)}.at-actions{display:grid;grid-template-columns:1fr}.at-btn{width:100%}.at-card-heading{align-items:flex-start}}
</style>
<div class="at-wrap"><div class="at-page-heading"><h1>Attendance</h1><p class="at-sub">Record attendance for each class session</p></div>
<?php if(isset($_GET['saved'])):?><div class="at-ok">✓ Attendance saved for <?=h($date)?>.</div><?php endif;?>
<form class="at-card at-filter-card" method="get">
	<label class="at-filter-field" for="attendanceCourse">Course
		<select id="attendanceCourse" name="course" onchange="this.form.submit()"><?php foreach($c as $x):?><option value="<?=$x['course_id']?>" <?=(int)$x['course_id']===$cur?'selected':''?>><?=h($x['course_code'].' – '.$x['course_name'])?></option><?php endforeach;?></select>
	</label>
	<label class="at-filter-field" for="attendanceDate">Session date
		<input id="attendanceDate" type="date" name="date" value="<?=h($date)?>" max="<?=date('Y-m-d')?>" onchange="this.form.submit()">
	</label>
</form>
<form method="post" class="at-card at-session-card"><input type="hidden" name="course" value="<?=$cur?>"><input type="hidden" name="date" value="<?=h($date)?>">
	<header class="at-card-heading"><div><h2>Attendance roster</h2><p>Mark each student’s attendance for <?=h($date)?></p></div><span class="at-roster-count"><?=count($re)?> students</span></header>
	<?php if(!$re):?><p class="at-empty-state">No enrolled students in this course yet.</p><?php else:?>
	<div class="at-table-wrap"><table class="at-t"><thead><tr><th>Student</th><th>Status</th><th>Present</th><th>Late</th><th>Absent</th><th>Rate</th></tr></thead><tbody>
	<?php foreach($re as $r):$e=$r['enrollment_id'];$s=$ex[$e]??'present';$m=$sum[$e]??[];$p=$m['present']??0;$l=$m['late']??0;$ab=$m['absent']??0;$t=$p+$l+$ab;?><tr><td><b><?=h($nm[$r['student_id']]??'Student')?></b></td>
	<td><div class="at-status-options"><?php foreach(['present'=>'Present','late'=>'Late','absent'=>'Absent'] as $v=>$lb):?><label class="at-status-option"><input type="radio" name="status[<?=$e?>]" value="<?=$v?>" <?=$s===$v?'checked':''?>><span><?=$lb?></span></label><?php endforeach;?></div></td><td><?=$p?></td><td><?=$l?></td><td><?=$ab?></td><td><?=$t?round(($p+$l)/$t*100).'%':'—'?></td></tr><?php endforeach;?>
	</tbody></table></div>
	<div class="at-actions"><button class="at-btn" type="submit">Save Attendance</button><button type="button" class="at-btn alt" onclick="document.querySelectorAll('.at-session-card input[value=present]').forEach(i=>i.checked=true)">Mark all present</button></div>
	<?php endif;?>
</form></div><?php bottom();
