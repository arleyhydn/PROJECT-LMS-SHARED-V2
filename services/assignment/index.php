<?php require '../../shared/lib.php'; // ASSIGNMENT SERVICE
if(($_GET['action']??'')==='byids')out(q('assignment','SELECT assignment_id,title,type,due_date FROM assignments WHERE assignment_id IN('.idlist().')')->fetchAll());
$a=$_GET['action']??'';$p=$_POST;

if($a==='notifications'){
 $role=$_SERVER['HTTP_X_ROLE']??'';
 $courseIds=array_filter(array_map('intval',explode(',',$_GET['ids']??'')),static fn($id)=>$id>0);
 if(!$courseIds)out([]);
 $in=implode(',', $courseIds);
 if($role==='student'){
  out(q('assignment',"SELECT a.assignment_id,a.course_id,a.title,a.type,a.due_date,a.created_at,c.course_name,c.course_code FROM assignments a LEFT JOIN lms_course_svc.courses c ON c.course_id=a.course_id WHERE a.course_id IN($in) AND a.status='published' ORDER BY a.created_at DESC,a.assignment_id DESC LIMIT 10")->fetchAll());
 }
 if(in_array($role,['professor','instructor'],true)){
  $uid=(int)$_SERVER['HTTP_X_USER'];
  out(q('assignment',"SELECT s.submission_id,s.submitted_at,a.title AS assignment_title,a.course_id,c.course_name,c.course_code,CONCAT(COALESCE(u.first_name,''),' ',COALESCE(u.last_name,'')) AS student_name FROM submissions s JOIN assignments a ON a.assignment_id=s.assignment_id LEFT JOIN lms_course_svc.courses c ON c.course_id=a.course_id LEFT JOIN lms_user_svc.users u ON u.user_id=s.student_id WHERE a.posted_by=? AND a.course_id IN($in) AND a.status='published' AND s.status='submitted' ORDER BY s.submitted_at DESC,s.submission_id DESC LIMIT 10",[$uid])->fetchAll());
 }
 out(['error'=>'Forbidden'],403);
}

if($a==='professor_list'){
 if(!in_array($_SERVER['HTTP_X_ROLE']??'', ['professor','instructor'],true))out(['error'=>'forbidden'],403);
 $uid=(int)($_SERVER['HTTP_X_USER']??0);$courseIds=array_filter(array_map('intval',explode(',',$_GET['ids']??'')));$in=implode(',', $courseIds)?:'0';
 $rows=q('assignment',"SELECT a.assignment_id,a.course_id,a.title,a.type,a.due_date,a.max_score,a.status,a.created_at,c.course_name,c.course_code FROM assignments a LEFT JOIN lms_course_svc.courses c ON c.course_id=a.course_id WHERE a.posted_by=? AND a.course_id IN($in) ORDER BY a.created_at DESC,a.assignment_id DESC",[$uid])->fetchAll();out($rows);
}
if($a==='student_list'){
 $uid=(int)($_SERVER['HTTP_X_USER']??0);
 $courseIds=array_filter(array_map('intval',explode(',',$_GET['ids']??'')));
 $in=implode(',', $courseIds)?:'0';
 $rows=q('assignment',"SELECT a.assignment_id,a.course_id,a.title,a.type,a.due_date,a.max_score, c.course_name,c.course_code, CONCAT(COALESCE(u.first_name,''),' ',COALESCE(u.last_name,'')) AS professor, s.submission_id,s.submitted_at,s.status,s.file_path FROM assignments a LEFT JOIN submissions s ON s.assignment_id=a.assignment_id AND s.student_id=? LEFT JOIN lms_course_svc.courses c ON c.course_id=a.course_id LEFT JOIN lms_user_svc.users u ON u.user_id=a.posted_by WHERE a.course_id IN($in) AND a.status='published' ORDER BY a.due_date ASC",[$uid])->fetchAll();
 out($rows);
}
if($a==='submit'){
 if(($_SERVER['HTTP_X_ROLE']??'')!=='student')out(['error'=>'forbidden'],403);
 $uid=(int)($_SERVER['HTTP_X_USER']??0);$aid=(int)($p['assignment_id']??0);
 $aRow=q('assignment','SELECT assignment_id,course_id,due_date,status FROM assignments WHERE assignment_id=?',[$aid])->fetch();
 if(!$aRow || $aRow['status']!=='published')out(['error'=>'Assignment unavailable'],404);
 $enr=http('enrollment','action=mine',['X-User: '.$uid,'X-Role: student']);
 if(!is_array($enr) || !in_array((int)$aRow['course_id'],array_map(fn($x)=>(int)$x['course_id'],$enr)))out(['error'=>'Not enrolled in this course'],403);
 $name=basename((string)($p['filename']??'submission.bin'));$ext=strtolower(pathinfo($name,PATHINFO_EXTENSION));
 $allowed=['pdf','doc','docx','txt','zip','png','jpg','jpeg'];
 $raw=(string)($p['file_base64']??'');$data=base64_decode($raw,true);
 if(!$data || strlen($data)>10485760 || !in_array($ext,$allowed,true))out(['error'=>'Invalid file. Allowed: PDF, DOC/DOCX, TXT, ZIP, PNG/JPG (max 10 MB).'],422);
 $dir=__DIR__.'/../../storage/submissions';if(!is_dir($dir)&&!mkdir($dir,0750,true))out(['error'=>'Storage unavailable'],500);
 $stored=bin2hex(random_bytes(16)).'.'.$ext;if(file_put_contents($dir.'/'.$stored,$data,LOCK_EX)===false)out(['error'=>'Could not save file'],500);
 $existing=q('assignment','SELECT submission_id FROM submissions WHERE assignment_id=? AND student_id=?',[$aid,$uid])->fetch();
 if($existing)q('assignment',"UPDATE submissions SET submitted_at=NOW(),status='submitted',file_path=? WHERE submission_id=?",[$stored,$existing['submission_id']]);
 else q('assignment',"INSERT INTO submissions(assignment_id,student_id,submitted_at,status,file_path) VALUES(?,?,NOW(),'submitted',?)",[$aid,$uid,$stored]);
 out(['ok'=>1,'message'=>'Assignment submitted']);
}

if($a==='pending'){if(!in_array($_SERVER['HTTP_X_ROLE']??'', ['professor','instructor'],true))out(['error'=>'Forbidden'],403);$uid=(int)($_SERVER['HTTP_X_USER']??0);out(q('assignment',"SELECT s.submission_id,s.assignment_id,s.student_id,s.submitted_at,s.file_path,t.title,t.course_id,t.max_score,t.due_date FROM submissions s JOIN assignments t ON t.assignment_id=s.assignment_id JOIN lms_course_svc.course_staff cs ON cs.course_id=t.course_id AND cs.user_id=? WHERE t.posted_by=? AND t.course_id IN(".idlist().") AND s.status='submitted' ORDER BY s.submitted_at DESC LIMIT 100",[$uid,$uid])->fetchAll());}
if($a==='submission_info'){if(!in_array($_SERVER['HTTP_X_ROLE']??'', ['professor','instructor'],true))out(['error'=>'Forbidden'],403);$uid=(int)($_SERVER['HTTP_X_USER']??0);$sid=(int)($_GET['submission_id']??0);$row=q('assignment',"SELECT s.file_path,t.title FROM submissions s JOIN assignments t ON t.assignment_id=s.assignment_id JOIN lms_course_svc.course_staff cs ON cs.course_id=t.course_id AND cs.user_id=? WHERE s.submission_id=? AND t.posted_by=?",[$uid,$sid,$uid])->fetch();if(!$row)out(['error'=>'Submission not found or access denied.'],404);out($row);}
if($a==='create'){
 if(!in_array($_SERVER['HTTP_X_ROLE']??'', ['professor','instructor'],true))out(['error'=>'Only professors can publish assignments.'],403);
 $uid=(int)($_SERVER['HTTP_X_USER']??0);$cid=(int)($p['course_id']??0);$title=trim($p['title']??'');$max=(float)($p['max_score']??100);$type=strtolower(trim($p['type']??'assignment'));
 if(!in_array($type,['assignment','project'],true))$type='assignment';
 if(!$cid||$title===''||$max<=0)out(['error'=>'Enter a title and valid maximum score.'],422);
 $owns=q('course','SELECT course_id FROM course_staff WHERE course_id=? AND user_id=?',[$cid,$uid])->fetch();
 if(!$owns)out(['error'=>'You are not assigned to teach this course.'],403);
 $due=trim($p['due_date']??'');if($due!==''){ $dt=strtotime($due);if($dt===false)out(['error'=>'Invalid due date.'],422);$due=date('Y-m-d H:i:s',$dt); }else $due=null;
 q('assignment',"INSERT INTO assignments(course_id,title,type,due_date,max_score,status,posted_by) VALUES(?,?,?,?,?,'published',?)",[$cid,$title,$type,$due,$max,$uid]);out(['ok'=>1]);
}
if($a==='ensure'){$f=fn()=>q('assignment','SELECT assignment_id id FROM assignments WHERE course_id=? AND title=?',[(int)$p['course_id'],$p['title']])->fetch();
 if(!$f())q('assignment',"INSERT INTO assignments(course_id,title,type,max_score,status,posted_by) VALUES(?,?,'exercise',?,'published',?)",[(int)$p['course_id'],$p['title'],(float)$p['max_score'],(int)$_SERVER['HTTP_X_USER']]);out($f());}
if($a==='graded'){
 if(!in_array($_SERVER['HTTP_X_ROLE']??'', ['professor','instructor'],true))out(['error'=>'Forbidden'],403);
 $uid=(int)($_SERVER['HTTP_X_USER']??0);$aid=(int)($p['assignment_id']??0);$sid=(int)($p['student_id']??0);
 $valid=q('assignment','SELECT a.assignment_id FROM assignments a JOIN lms_course_svc.course_staff cs ON cs.course_id=a.course_id WHERE a.assignment_id=? AND a.posted_by=? AND cs.user_id=?',[$aid,$uid,$uid])->fetch();
 if(!$valid)out(['error'=>'Assignment not found in your courses.'],403);
 q('assignment',"UPDATE submissions SET status='graded' WHERE assignment_id=? AND student_id=?",[$aid,$sid]);out(['ok'=>1]);
}
if($a==='counts')out(q('assignment','SELECT course_id,COUNT(*) n FROM assignments WHERE course_id IN('.idlist().') GROUP BY course_id')->fetchAll());
out(['error'=>'bad action'],400);
