<?php require '../../shared/lib.php'; // ENROLLMENT SERVICE
if(($_GET['action']??'')==='mine')
 out(q('enrollment',"SELECT enrollment_id,course_id FROM enrollments WHERE student_id=? AND status NOT IN('pending','dropped')",[(int)$_SERVER['HTTP_X_USER']])->fetchAll());
$adm=($_SERVER['HTTP_X_ROLE']??'')==='admin';$p=$_POST;
if(($_GET['action']??'')==='all')out(q('enrollment',"SELECT enrollment_id,student_id,course_id,status FROM enrollments WHERE status<>'dropped'")->fetchAll());
if(($_GET['action']??'')==='enroll'&&$adm){q('enrollment',"INSERT INTO enrollments(student_id,course_id,status,approved_by,approved_at) VALUES(?,?,'approved',?,NOW()) ON DUPLICATE KEY UPDATE status='approved',approved_by=VALUES(approved_by),approved_at=NOW()",[(int)$p['user_id'],(int)$p['course_id'],(int)$_SERVER['HTTP_X_USER']]);out(['ok'=>1]);}
if(($_GET['action']??'')==='unenroll'&&$adm){q('enrollment',"UPDATE enrollments SET status='dropped' WHERE student_id=? AND course_id=?",[(int)$p['user_id'],(int)$p['course_id']]);out(['ok'=>1]);}
if(($_GET['action']??'')==='roster')out(q('enrollment',"SELECT enrollment_id,student_id,course_id FROM enrollments WHERE status NOT IN('pending','dropped') AND course_id IN(".idlist().')')->fetchAll());
if(($_GET['action']??'')==='pending')out(q('enrollment',"SELECT COUNT(*) n FROM enrollments WHERE status='pending'")->fetch());
out(['error'=>'bad action'],400);
