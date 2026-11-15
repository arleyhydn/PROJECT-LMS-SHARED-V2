<?php require '../../shared/lib.php'; // ATTENDANCE SERVICE
if(($_GET['action']??'')==='mine')out(q('attendance','SELECT enrollment_id,LOWER(status) status,COUNT(*) n FROM attendance WHERE enrollment_id IN('.idlist().') GROUP BY enrollment_id,LOWER(status)')->fetchAll());
$a=$_GET['action']??'';
if($a==='session')out(q('attendance','SELECT enrollment_id,LOWER(status) status FROM attendance WHERE session_date=? AND enrollment_id IN('.idlist().')',[$_GET['date']??''])->fetchAll());
if($a==='record'){
 if(!in_array($_SERVER['HTTP_X_ROLE']??'',['professor','instructor']))out([],403);
 $d=$_POST['date']??'';if(!preg_match('/^\d{4}-\d{2}-\d{2}$/',$d)||$d>date('Y-m-d'))out(['error'=>'Invalid date'],422);
 foreach(json_decode($_POST['rows']??'[]',true)?:[] as $eid=>$s){$s=strtolower($s);if(!in_array($s,['present','late','absent']))continue;
  $x=q('attendance','SELECT attendance_id FROM attendance WHERE enrollment_id=? AND session_date=?',[(int)$eid,$d])->fetch();
  if($x)q('attendance','UPDATE attendance SET status=?,recorded_by=?,recorded_at=NOW() WHERE attendance_id=?',[$s,(int)$_SERVER['HTTP_X_USER'],$x['attendance_id']]);
  else q('attendance','INSERT INTO attendance(enrollment_id,session_date,status,recorded_by) VALUES(?,?,?,?)',[(int)$eid,$d,$s,(int)$_SERVER['HTTP_X_USER']]);}
 out(['ok'=>1]);}
out(['error'=>'bad action'],400);
