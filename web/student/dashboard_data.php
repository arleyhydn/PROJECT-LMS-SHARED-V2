<?php
ini_set('display_errors','0');
ob_start();
require '../inc.php';
if(($_SESSION['role']??'')!=='student'){http_response_code(401);header('Content-Type: application/json; charset=utf-8');
if(ob_get_level())ob_clean();echo json_encode(['ok'=>false,'error'=>'Your session expired. Please sign in again.']);exit;}

header('Content-Type: application/json; charset=utf-8');
$enrollments=api('enrollment','mine');
if(!is_array($enrollments)||isset($enrollments['error'])){http_response_code(502);echo json_encode(['ok'=>false,'error'=>'Could not load enrolled courses.']);exit;}
$courseIds=array_values(array_unique(array_filter(array_map('intval',array_column($enrollments,'course_id')))));
$ids=implode(',',$courseIds);
$schedule=$ids!==''?api('course','schedule',['ids'=>$ids]):[];
$todos=api('planning','todos');
$unread=api('messaging','unread');
if(!is_array($schedule)||isset($schedule['error'])||!is_array($todos)||isset($todos['error'])||!is_array($unread)||isset($unread['error'])){http_response_code(502);echo json_encode(['ok'=>false,'error'=>'Could not refresh dashboard data.']);exit;}
$today=date('D');$now=date('H:i:s');$weekEnd=(new DateTimeImmutable('now'))->modify('+7 days');
$todaySchedule=array_values(array_filter($schedule,static fn($s)=>substr((string)($s['day_of_week']??''),0,3)===$today));
$upcoming=count(array_filter($todaySchedule,static fn($s)=>substr((string)($s['start_time']??''),0,8)>=$now));
$pending=array_values(array_filter($todos,static fn($t)=>strtolower((string)($t['status']??''))!=='completed'));
$dueSoon=0;$nowDate=new DateTimeImmutable('today');
foreach($pending as $todo){$due=trim((string)($todo['due_date']??''));if($due==='')continue;try{$dueDate=new DateTimeImmutable($due);if($dueDate>=$nowDate&&$dueDate<=$weekEnd)$dueSoon++;}catch(Throwable $e){}}
echo json_encode(['ok'=>true,'today_classes'=>count($todaySchedule),'upcoming_classes'=>$upcoming,'pending_tasks'=>count($pending),'due_soon'=>$dueSoon,'unread_messages'=>(int)($unread['n']??0)]);
