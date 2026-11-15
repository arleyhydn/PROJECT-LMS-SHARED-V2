<?php require '../../shared/lib.php'; // COURSE SERVICE
$ids=array_filter(array_map('intval',explode(',',$_GET['ids']??'')))?:[0];$in=implode(',',$ids);
$adm=($_SERVER['HTTP_X_ROLE']??'')==='admin';
switch($_GET['action']??''){
 case 'all': out(q('course',"SELECT c.course_id,c.course_code,c.course_name, (SELECT GROUP_CONCAT(DISTINCT CONCAT(u.first_name,' ',u.last_name) SEPARATOR ', ') FROM course_staff cs JOIN lms_user_svc.users u ON u.user_id=cs.user_id WHERE cs.course_id=c.course_id) AS professor, (SELECT COUNT(*) FROM lms_enrollment_svc.enrollments e WHERE e.course_id=c.course_id AND e.status NOT IN('pending','dropped')) AS students_count FROM courses c ORDER BY c.course_code")->fetchAll());
 case 'staff': out(q('course','SELECT course_id,user_id FROM course_staff')->fetchAll());
 case 'assign': if(!$adm)out([],403);q('course','INSERT IGNORE INTO course_staff(course_id,user_id) VALUES(?,?)',[(int)$_POST['course_id'],(int)$_POST['user_id']]);out(['ok'=>1]);
 case 'unassign': if(!$adm)out([],403);q('course','DELETE FROM course_staff WHERE course_id=? AND user_id=?',[(int)$_POST['course_id'],(int)$_POST['user_id']]);out(['ok'=>1]);
 case 'create': if(!$adm)out([],403);q('course','INSERT INTO courses(course_code,course_name,program_id,created_by) VALUES(?,?,(SELECT COALESCE(MIN(program_id),1) FROM programs),?)',[$_POST['course_code'],$_POST['course_name'],(int)$_SERVER['HTTP_X_USER']]);out(['ok'=>1]);
 case 'mine': $u=(int)$_SERVER['HTTP_X_USER'];out(q('course','SELECT course_id,course_code,course_name FROM courses WHERE course_id IN(SELECT course_id FROM course_staff WHERE user_id=?)',[$u])->fetchAll());
 case 'count': out(q('course','SELECT COUNT(*) n FROM courses')->fetch());
 case 'byids': out(q('course',"SELECT c.course_id,c.course_code,c.course_name, (SELECT GROUP_CONCAT(DISTINCT CONCAT(u.first_name,' ',u.last_name) SEPARATOR ', ') FROM course_staff cs JOIN lms_user_svc.users u ON u.user_id=cs.user_id WHERE cs.course_id=c.course_id) AS professor, (SELECT COUNT(*) FROM lms_enrollment_svc.enrollments e WHERE e.course_id=c.course_id AND e.status NOT IN('pending','dropped')) AS students_count FROM courses c WHERE c.course_id IN($in)")->fetchAll());
 case 'schedule': out(q('course',"SELECT s.course_id,c.course_code,c.course_name,s.start_time,s.end_time,s.room,s.day_of_week FROM course_schedule s JOIN courses c USING(course_id) WHERE s.course_id IN($in) ORDER BY s.start_time")->fetchAll());
}
out(['error'=>'bad action'],400);
