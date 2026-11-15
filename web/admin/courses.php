<?php require '../inc.php';guard('admin');
if($_POST){$m=['assign'=>['course','assign'],'unassign'=>['course','unassign'],'create'=>['course','create'],'enroll'=>['enrollment','enroll'],'unenroll'=>['enrollment','unenroll']];$a=$_POST['do'];if(isset($m[$a]))api($m[$a][0],$m[$a][1],[],$_POST);header('Location: courses.php'.(!empty($_POST['course_id'])?'?manage='.(int)$_POST['course_id']:''));exit;}
$c=api('course','all');$pn=array_column(api('user','list',['role'=>'professor']),null,'user_id');$sn=array_column(api('user','list',['role'=>'student']),null,'user_id');$man=(int)($_GET['manage']??0);
$ps=[];foreach(api('course','staff') as $x)$ps[$x['course_id']][]=$x['user_id'];$es=[];foreach(api('enrollment','all') as $x)$es[$x['course_id']][$x['student_id']]=$x['status'];
$nm=fn($u)=>$u?$u['first_name'].' '.$u['last_name']:'(unknown)';
function mini($do,$cid,$uid,$label,$cls='cm-lnk'){return '<form method="post" class="cm-inl"><input type="hidden" name="do" value="'.$do.'"><input type="hidden" name="course_id" value="'.$cid.'"><input type="hidden" name="user_id" value="'.$uid.'"><button class="'.$cls.'">'.$label.'</button></form>';}
top('Courses','courses');?>
<style>
.cm-wrap{padding:24px 32px;background:#f8fafc;min-height:100vh;font-family:'Inter',sans-serif;color:#1e293b;box-sizing:border-box}
.cm-head{display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:24px}.cm-head h1{font-size:24px;font-weight:700;color:#0f172a;margin:0 0 4px}.cm-head p{font-size:14px;color:#64748b;margin:0}
.cm-btn{background:#15803d;color:#fff;padding:10px 20px;border-radius:10px;font-size:14px;font-weight:600;text-decoration:none;border:0;cursor:pointer}.cm-btn:hover{background:#166534}
.cm-card{background:#fff;border:1px solid #e5e7eb;border-radius:14px;padding:20px;margin-bottom:20px}.cm-card h3{margin:0 0 14px;font-size:17px;font-weight:700;color:#0f172a}
.cm-grid{display:grid;grid-template-columns:1fr 1fr;gap:20px}.cm-tbl{width:100%;border-collapse:collapse}.cm-tbl th{text-align:left;font-size:12px;color:#64748b;padding:12px;background:#f8fafc;text-transform:uppercase}.cm-tbl td{padding:14px 12px;border-top:1px solid #eef2f1;font-size:14px}
.cm-tag{display:inline-block;font-size:12px;font-weight:600;padding:3px 10px;border-radius:99px;background:#dcfce7;color:#15803d;margin-right:6px}.cm-pill{font-size:12px;font-weight:600;padding:2px 10px;border-radius:99px;background:#dcfce7;color:#16a34a}.cm-pill.off{background:#fef3c7;color:#b45309}
.cm-lnk{background:none;border:0;color:#2563eb;font-weight:500;cursor:pointer;font-size:14px;text-decoration:none}.cm-lnk.d{color:#dc2626}.cm-inl{display:inline}
.cm-row{display:flex;gap:12px;margin-top:14px}.cm-row input,.cm-row select{flex:1;padding:12px 14px;border:1px solid #e5e7eb;border-radius:10px;font-size:14px;background:#fff}.cm-item{display:flex;justify-content:space-between;align-items:center;padding:8px 0;border-bottom:1px solid #f1f5f9;font-size:14px}
</style>
<div class="cm-wrap"><div class="cm-head"><div><h1>Course Assignment</h1><p>Assign professors and enroll students</p></div><a class="cm-btn" href="?new=1">+ Add Course</a></div>
<?php if(isset($_GET['new'])):?><form method="post" class="cm-card cm-row" style="margin-top:0"><input type="hidden" name="do" value="create"><input name="course_code" placeholder="Code e.g. IT301" required><input name="course_name" placeholder="Course name" required><button class="cm-btn">Save</button></form><?php endif;?>
<div class="cm-card"><table class="cm-tbl"><tr><th>Course</th><th>Professor(s)</th><th>Students</th><th></th></tr>
<?php foreach($c as $x):$id=$x['course_id'];?><tr><td><span class="cm-tag"><?=h($x['course_code'])?></span><b><?=h($x['course_name'])?></b></td><td><?=h(implode(', ',array_map(fn($u)=>$nm($pn[$u]??null),$ps[$id]??[]))?:'— none —')?></td><td><?=count($es[$id]??[])?></td><td><a class="cm-lnk" href="?manage=<?=$id?>">Manage</a></td></tr><?php endforeach;?></table></div>
<?php foreach($c as $x)if($x['course_id']==$man):$id=$man;?>
<div class="cm-grid"><div class="cm-card"><h3>Professors · <?=h($x['course_code'])?></h3>
<?php foreach($ps[$id]??[] as $u):?><div class="cm-item"><span><?=h($nm($pn[$u]??null))?></span><?=mini('unassign',$id,$u,'Remove','cm-lnk d')?></div><?php endforeach;?>
<form method="post" class="cm-row"><input type="hidden" name="do" value="assign"><input type="hidden" name="course_id" value="<?=$id?>"><select name="user_id"><?php foreach($pn as $u):if(!in_array($u['user_id'],$ps[$id]??[])):?><option value="<?=$u['user_id']?>"><?=h($nm($u))?></option><?php endif;endforeach;?></select><button class="cm-btn">Assign</button></form></div>
<div class="cm-card"><h3>Students · <?=h($x['course_code'])?></h3>
<?php foreach($es[$id]??[] as $u=>$s):?><div class="cm-item"><span><?=h($nm($sn[$u]??null))?> <span class="cm-pill <?=$s==='pending'?'off':''?>"><?=h($s)?></span></span><span><?=$s==='pending'?mini('enroll',$id,$u,'Approve'):''?> <?=mini('unenroll',$id,$u,'Remove','cm-lnk d')?></span></div><?php endforeach;?>
<form method="post" class="cm-row"><input type="hidden" name="do" value="enroll"><input type="hidden" name="course_id" value="<?=$id?>"><select name="user_id"><?php foreach($sn as $u):if(!isset($es[$id][$u['user_id']])):?><option value="<?=$u['user_id']?>"><?=h($nm($u))?></option><?php endif;endforeach;?></select><button class="cm-btn">Enroll</button></form></div></div>
<?php endif;?></div><?php bottom();
