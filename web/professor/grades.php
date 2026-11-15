<?php
require '../inc.php';guard('professor');
$c=api('course','mine');$ids=implode(',',array_column($c,'course_id'));$ro=$ids?api('enrollment','roster',['ids'=>$ids]):[];if(!is_array($ro)||isset($ro['error']))$ro=[];$nm=names(array_column($ro,'student_id'));if(!is_array($nm))$nm=[];
$cur=(int)($_GET['course']??($c[0]['course_id']??0));if(!in_array($cur,array_map('intval',array_column($c,'course_id')),true))$cur=(int)($c[0]['course_id']??0);$re=array_values(array_filter($ro,fn($r)=>(int)$r['course_id']===$cur));
if($_SERVER['REQUEST_METHOD']==='POST'){
 $action=$_POST['form_action']??'';
 if($action==='create_assignment'){$res=api('assignment','create',[],['course_id'=>$cur,'title'=>trim($_POST['title']??''),'type'=>$_POST['type']??'assignment','due_date'=>$_POST['due_date']??'','max_score'=>$_POST['max_score']??100]);header('Location: grades.php?course='.$cur.(!empty($res['ok'])?'&created=1':'&error='.urlencode($res['error']??'Could not create assignment')));exit;}
 if($action==='update_grade'){$enrollmentId=(int)($_POST['enrollment_id']??0);$aid=(int)($_POST['assignment_id']??0);$enrollment=null;foreach($re as $r)if((int)$r['enrollment_id']===$enrollmentId)$enrollment=$r;
  $score=$_POST['score']??'';$max=$_POST['max_score']??'';$weight=$_POST['weight']??100;$title=trim((string)($_POST['title']??'Assignment'));
  if($enrollment&&$aid&&is_numeric($score)&&is_numeric($max)&&(float)$max>0&&(float)$score>=0&&(float)$score<=(float)$max&&is_numeric($weight)&&(float)$weight>=0&&(float)$weight<=100){
   $result=api('grade','record',[],['enrollment_id'=>$enrollmentId,'assignment_id'=>$aid,'course_id'=>$cur,'title'=>$title,'score'=>$score,'max_score'=>$max,'weight'=>$weight]);
   if(!empty($result['ok'])){header('Location: grades.php?course='.$cur.'&updated=1');exit;}$error=$result['error']??'Could not update grade.';
  }else $error='Invalid grade update. Check the score and weight.';
  header('Location: grades.php?course='.$cur.'&error='.urlencode($error));exit;
 }
 if($action==='grade_submission'){$submissionId=(int)($_POST['submission_id']??0);$studentId=(int)($_POST['student_id']??0);$aid=(int)($_POST['assignment_id']??0);$enrollment=null;foreach($re as $r)if((int)$r['student_id']===$studentId)$enrollment=$r;
  $score=$_POST['score']??'';$max=$_POST['max_score']??'';$weight=$_POST['weight']??100;
  if($enrollment&&$submissionId&&$aid&&is_numeric($score)&&is_numeric($max)&&(float)$max>0&&(float)$score>=0&&(float)$score<=(float)$max&&is_numeric($weight)&&(float)$weight>=0&&(float)$weight<=100){
   $result=api('grade','record',[],['enrollment_id'=>$enrollment['enrollment_id'],'assignment_id'=>$aid,'course_id'=>$cur,'title'=>$_POST['title']??'Assignment','score'=>$score,'max_score'=>$max,'weight'=>$weight]);
   if(!empty($result['ok'])){$marked=api('assignment','graded',[],['assignment_id'=>$aid,'student_id'=>$studentId]);if(!empty($marked['ok'])){header('Location: grades.php?course='.$cur.'&graded=1');exit;}$error='Grade was saved, but submission status could not be updated: '.($marked['error']??'Unknown error');}
   else $error=$result['error']??'Could not save grade. Please check the service/database configuration.';
  }else $error='Invalid grading data. Check the selected student, score, maximum score, and weight.';
  header('Location: grades.php?course='.$cur.'&error='.urlencode($error));exit;
 }
}
$published=$ids?api('assignment','professor_list',['ids'=>$ids]):[];if(!is_array($published))$published=[];$pending=$cur?api('assignment','pending',['ids'=>$cur]):[];if(!is_array($pending))$pending=[];$g=$re?api('grade','mine',['ids'=>implode(',',array_column($re,'enrollment_id'))]):[];if(!is_array($g)||isset($g['error']))$g=[];$activityRows=api('assignment','byids',['ids'=>implode(',',array_filter(array_column($g,'assignment_id')))]);if(!is_array($activityRows)||isset($activityRows['error']))$activityRows=[];$at=array_column($activityRows,'title','assignment_id');
if(isset($_GET['export'])){header('Content-Type: text/csv');header('Content-Disposition: attachment; filename=grades.csv');$fp=fopen('php://output','w');fputcsv($fp,['Student','Activity','Score','Max','Weight']);foreach($g as $x)fputcsv($fp,[$nm[array_search($x['enrollment_id'],array_column($re,'enrollment_id'))]??'', $at[$x['assignment_id']]??'', $x['score'],$x['max_score'],$x['weight']]);exit;}
top('Grade Management','grades'); ?>
<div class="page-heading"><h1>Assignments & Grading</h1><p class="sub">Publish an assignment, review submissions, and record grades.</p></div>
<?php if(isset($_GET['created'])):?><div class="card" role="status">Assignment published successfully.</div><?php endif;?>
<?php if(isset($_GET['graded'])):?><div class="card" role="status">Grade saved and submission marked as graded.</div><?php endif;?>
<?php if(isset($_GET['updated'])):?><div class="card" role="status">Grade updated successfully.</div><?php endif;?>
<?php if(isset($_GET['error'])):?><div class="card" role="alert"><?=h($_GET['error'])?></div><?php endif;?>
<div class="cols grades-management-grid">
<form method="post" class="card assignment-publish-form">
    <input type="hidden" name="form_action" value="create_assignment">
    <div class="assignment-form-heading">
        <span class="assignment-form-icon" aria-hidden="true"><?=ui_icon('file')?></span>
        <div>
            <h3>Publish Assignment</h3>
            <p>Set up an assignment or project for your course.</p>
        </div>
    </div>
    <div class="assignment-form-fields">
        <div class="assignment-form-field">
            <label for="cs">Course</label>
            <select id="cs" onchange="location='?course='+this.value">
                <?php foreach($c as $x):?><option value="<?=$x['course_id']?>" <?=$x['course_id']==$cur?'selected':''?>><?=h($x['course_code'].' – '.$x['course_name'])?></option><?php endforeach;?>
            </select>
        </div>
        <div class="assignment-form-row">
            <div class="assignment-form-field">
                <label for="assignmentType">Type</label>
                <select id="assignmentType" name="type">
                    <option value="assignment">Assignment</option>
                    <option value="project">Project</option>
                </select>
            </div>
            <div class="assignment-form-field">
                <label for="maxScore">Maximum score</label>
                <input id="maxScore" name="max_score" type="number" min="1" step="0.01" value="100" required>
            </div>
        </div>
        <div class="assignment-form-field">
            <label for="assignmentTitle">Title</label>
            <input id="assignmentTitle" name="title" required maxlength="180" placeholder="e.g. Activity 1 – UI Design">
        </div>
        <div class="assignment-form-field">
            <label for="dueDate">Due date <span>Optional</span></label>
            <input id="dueDate" type="datetime-local" name="due_date">
        </div>
        <button class="assignment-publish-button" type="submit"><?=ui_icon('check')?> Publish assignment</button>
    </div>
</form>
<section class="card assignment-support-card published-assignments-card">
    <header class="assignment-support-header">
        <span class="assignment-form-icon" aria-hidden="true"><?=ui_icon('file')?></span>
        <div class="assignment-support-title">
            <h3>Published Assignments &amp; Projects</h3>
            <p>Assignments and projects available across your courses.</p>
        </div>
        <span class="assignment-count"><?=count($published)?></span>
    </header>
    <?php if(!$published):?>
        <p class="assignment-empty-state">Wala ka pang published assignments or projects for your courses.</p>
    <?php else:?>
        <div class="assignment-table-wrap">
            <table>
                <thead><tr><th>TITLE</th><th>COURSE</th><th>TYPE</th><th>DUE DATE</th><th>MAX SCORE</th><th>STATUS</th></tr></thead>
                <tbody><?php foreach($published as $pub):?>
                    <tr>
                        <td><strong><?=h($pub['title']??'Untitled')?></strong><small>Published <?=h(substr($pub['created_at']??'',0,16))?></small></td>
                        <td><?=h(($pub['course_code']??'').' – '.($pub['course_name']??''))?></td>
                        <td><span class="pill"><?=h(ucfirst($pub['type']??'assignment'))?></span></td>
                        <td><?=h($pub['due_date']?:'No due date')?></td>
                        <td><?=h($pub['max_score']??100)?></td>
                        <td><span class="pill"><?=h(ucfirst($pub['status']??'published'))?></span></td>
                    </tr>
                <?php endforeach;?></tbody>
            </table>
        </div>
    <?php endif;?>
</section>
<section class="card assignment-support-card pending-grading-card">
    <header class="assignment-support-header">
        <span class="assignment-form-icon" aria-hidden="true"><?=ui_icon('clock')?></span>
        <div class="assignment-support-title">
            <h3>Pending Grading</h3>
            <p>Review submissions and enter a score and weight.</p>
        </div>
        <span class="assignment-count"><?=count($pending)?></span>
    </header>
    <?php if(!$pending):?>
        <p class="assignment-empty-state">No submitted assignments are waiting for grading.</p>
    <?php else:?>
        <div class="pending-grading-list">
            <?php foreach($pending as $x):$studentEnrollment=null;foreach($re as $rr)if((int)$rr['student_id']===(int)$x['student_id'])$studentEnrollment=$rr;if(!$studentEnrollment)continue;?>
                <form method="post" class="pending-grading-item">
                    <input type="hidden" name="form_action" value="grade_submission">
                    <input type="hidden" name="submission_id" value="<?= (int)$x['submission_id'] ?>">
                    <input type="hidden" name="assignment_id" value="<?= (int)$x['assignment_id'] ?>">
                    <input type="hidden" name="student_id" value="<?= (int)$x['student_id'] ?>">
                    <input type="hidden" name="title" value="<?=h($x['title'])?>">
                    <input type="hidden" name="max_score" value="<?=h($x['max_score'])?>">
                    <div class="pending-grading-student">
                        <div><strong><?=h($nm[$x['student_id']]??'Student')?></strong><p><?=h($x['title'])?> · Submitted <?=h($x['submitted_at'])?></p></div>
                        <a class="submission-link" href="submission.php?id=<?=(int)$x['submission_id']?>" target="_blank" rel="noopener">View / Download submission</a>
                    </div>
                    <div class="grading-controls">
                        <label class="grading-field">Score <span>/ <?=h($x['max_score'])?></span><input name="score" type="number" min="0" max="<?=h($x['max_score'])?>" step="0.01" required placeholder="Enter score"></label>
                        <label class="grading-field">Weight (%)<input name="weight" type="number" min="0" max="100" value="100" step="0.1" required></label>
                        <button class="assignment-publish-button save-grade-button" type="submit"><?=ui_icon('check')?> Save grade</button>
                    </div>
                </form>
            <?php endforeach;?>
        </div>
    <?php endif;?>
</section>
<section class="card assignment-support-card grade-records-card">
    <header class="assignment-support-header">
        <span class="assignment-form-icon" aria-hidden="true"><?=ui_icon('file')?></span>
        <div class="assignment-support-title">
            <h3>Grade Records</h3>
            <p>Review saved grades or edit a score and weight.</p>
        </div>
        <a class="grade-export-link" href="?course=<?=$cur?>&amp;export=1">Export CSV</a>
    </header>
    <?php if(!$g):?>
        <p class="assignment-empty-state">No grades have been recorded for this course yet.</p>
    <?php else:?>
        <div class="assignment-table-wrap">
            <table>
                <thead><tr><th>STUDENT</th><th>ACTIVITY</th><th>SCORE</th><th>WEIGHT</th><th>WEIGHTED</th><th>STATUS</th><th>ACTION</th></tr></thead>
                <tbody><?php foreach($g as $x):$studentName='Student';$recordEnrollmentId=0;foreach($re as $rr)if((int)$rr['enrollment_id']===(int)$x['enrollment_id']){$studentName=$nm[$rr['student_id']]??'Student';$recordEnrollmentId=(int)$rr['enrollment_id'];} $activityTitle=$at[$x['assignment_id']]??'Assignment';?>
                    <tr>
                        <td><b><?=h($studentName)?></b></td>
                        <td><?=h($activityTitle)?></td>
                        <td><?=h($x['score'])?>/<?=h($x['max_score'])?></td>
                        <td><?=h($x['weight'])?>%</td>
                        <td><b><?=number_format($x['max_score']>0?$x['score']/$x['max_score']*$x['weight']:0,2)?></b></td>
                        <td><span class="pill">Graded</span></td>
                        <td>
                            <?php if($recordEnrollmentId):?>
                                <details class="grade-edit-details">
                                    <summary class="grade-edit-toggle">Edit</summary>
                                    <form method="post" class="grade-edit-form">
                                        <input type="hidden" name="form_action" value="update_grade">
                                        <input type="hidden" name="enrollment_id" value="<?=$recordEnrollmentId?>">
                                        <input type="hidden" name="assignment_id" value="<?=(int)$x['assignment_id']?>">
                                        <input type="hidden" name="title" value="<?=h($activityTitle)?>">
                                        <input type="hidden" name="max_score" value="<?=h($x['max_score'])?>">
                                        <label>Score<input name="score" type="number" min="0" max="<?=h($x['max_score'])?>" step="0.01" value="<?=h($x['score'])?>" required></label>
                                        <label>Weight (%)<input name="weight" type="number" min="0" max="100" step="0.1" value="<?=h($x['weight'])?>" required></label>
                                        <button class="assignment-publish-button" type="submit"><?=ui_icon('check')?> Save changes</button>
                                    </form>
                                </details>
                            <?php endif;?>
                        </td>
                    </tr>
                <?php endforeach;?></tbody>
            </table>
        </div>
    <?php endif;?>
</section>
<?php bottom(); ?>
