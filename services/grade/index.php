<?php require '../../shared/lib.php'; // GRADE SERVICE
if(($_GET['action']??'')==='mine')out(q('grade','SELECT g.enrollment_id,g.assignment_id,g.score,g.max_score,g.graded_at,COALESCE(c.weight,0) weight FROM grades g LEFT JOIN grade_components c ON c.component_id=g.component_id WHERE g.enrollment_id IN('.idlist().') ORDER BY g.graded_at DESC')->fetchAll());
if(($_GET['action']??'')==='record'){
 $p=$_POST;
 if(!in_array($_SERVER['HTTP_X_ROLE']??'', ['professor','instructor'],true))out(['error'=>'Only professors can record grades.'],403);
 $uid=(int)($_SERVER['HTTP_X_USER']??0);$enrollmentId=(int)($p['enrollment_id']??0);$assignmentId=(int)($p['assignment_id']??0);$courseId=(int)($p['course_id']??0);
 $score=$p['score']??'';$max=$p['max_score']??'';$weight=$p['weight']??'';
 if(!$enrollmentId||!$assignmentId||!$courseId||!is_numeric($score)||!is_numeric($max)||!is_numeric($weight)||(float)$max<=0||(float)$score<0||(float)$score>(float)$max||(float)$weight<0||(float)$weight>100)out(['error'=>'Enter a valid score, maximum score, and weight (0–100%).'],422);
 $allowed=q('grade',"SELECT e.enrollment_id,a.assignment_id FROM lms_enrollment_svc.enrollments e JOIN lms_assignment_svc.assignments a ON a.course_id=e.course_id JOIN lms_assignment_svc.submissions s ON s.assignment_id=a.assignment_id AND s.student_id=e.student_id AND s.status IN ('submitted','graded','returned') JOIN lms_course_svc.course_staff cs ON cs.course_id=a.course_id WHERE e.enrollment_id=? AND a.assignment_id=? AND a.course_id=? AND a.posted_by=? AND cs.user_id=? AND e.status NOT IN ('pending','dropped')",[$enrollmentId,$assignmentId,$courseId,$uid,$uid])->fetch();
 if(!$allowed)out(['error'=>'This student or assignment is not part of a course you teach.'],403);
 $title=trim((string)($p['title']??'Assignment'));if($title==='')$title='Assignment';
 $f=fn()=>q('grade','SELECT component_id id FROM grade_components WHERE course_id=? AND name=?',[$courseId,$title])->fetch();
 if(!$f())q('grade','INSERT INTO grade_components(course_id,name,weight) VALUES(?,?,?)',[$courseId,$title,(float)$weight]);
 $component=$f();if(!$component)out(['error'=>'Could not create the grade component.'],500);$componentId=(int)$component['id'];
 q('grade','UPDATE grade_components SET weight=? WHERE component_id=?',[(float)$weight,$componentId]);
 $g=q('grade','SELECT grade_id,score FROM grades WHERE enrollment_id=? AND assignment_id=?',[$enrollmentId,$assignmentId])->fetch();
 if($g){q('grade','INSERT INTO grade_history(grade_id,old_score,new_score,changed_by) VALUES(?,?,?,?)',[$g['grade_id'],$g['score'],(float)$score,$uid]);q('grade','UPDATE grades SET score=?,max_score=?,component_id=?,graded_by=?,graded_at=NOW() WHERE grade_id=?',[(float)$score,(float)$max,$componentId,(int)$uid,$g['grade_id']]);}
 else q('grade','INSERT INTO grades(enrollment_id,assignment_id,component_id,score,max_score,remarks,graded_by) VALUES(?,?,?,?,?,\'\',?)',[$enrollmentId,$assignmentId,$componentId,(float)$score,(float)$max,(int)$uid]);
 out(['ok'=>1]);
}
out(['error'=>'bad action'],400);
