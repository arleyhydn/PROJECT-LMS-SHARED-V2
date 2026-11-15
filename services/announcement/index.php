<?php require '../../shared/lib.php'; // ANNOUNCEMENT SERVICE
$ids=array_filter(array_map('intval',explode(',',$_GET['ids']??'')))?:[0];$in=implode(',',$ids);
if(($_GET['action']??'')==='list')
 out(q('announcement',"SELECT announcement_id,title,content,announcement_type,is_pinned,posted_by,created_at FROM announcements WHERE is_archived=0 AND (course_id=0 OR course_id IN($in)) ORDER BY is_pinned DESC,created_at DESC LIMIT 10")->fetchAll());
$pro=in_array($_SERVER['HTTP_X_ROLE']??'',['professor','instructor']);$adm=($_SERVER['HTTP_X_ROLE']??'')==='admin';
$role=$_SERVER['HTTP_X_ROLE']??'';
if(($_GET['action']??'')==='notifications'){
 if(!in_array($role,['admin','student','professor','instructor'],true))out(['error'=>'Forbidden'],403);
 $where=$role==='student'?" AND (course_id=0 OR course_id IN($in))":'';
 out(q('announcement',"SELECT announcement_id,title,content,announcement_type,is_pinned,course_id,created_at FROM announcements WHERE is_archived=0$where ORDER BY created_at DESC,announcement_id DESC LIMIT 10")->fetchAll());
}
switch($_GET['action']??''){
 case 'feed-meta':
  $feedIds=array_values(array_unique(array_filter(array_map('intval',explode(',',$_GET['ids']??'')),static fn($id)=>$id>0)));
  if(!$feedIds)out(['counts'=>[],'states'=>[]]);
  $placeholders=implode(',',array_fill(0,count($feedIds),'?'));
  $counts=[];$states=[];
  foreach($feedIds as $id){$counts[$id]=['likes'=>0,'dislikes'=>0];$states[$id]=['reaction'=>null,'read'=>false];}
  foreach(q('announcement',"SELECT announcement_id,reaction,COUNT(*) AS total FROM announcement_reactions WHERE announcement_id IN ($placeholders) GROUP BY announcement_id,reaction",$feedIds)->fetchAll() as $row)
   $counts[$row['announcement_id']][$row['reaction'].'s']=(int)$row['total'];
  $uid=(int)($_SERVER['HTTP_X_USER']??0);
  foreach(q('announcement',"SELECT announcement_id,reaction FROM announcement_reactions WHERE user_id=? AND announcement_id IN ($placeholders)",array_merge([$uid],$feedIds))->fetchAll() as $row)
   $states[$row['announcement_id']]['reaction']=$row['reaction'];
  foreach(q('announcement',"SELECT announcement_id FROM announcement_reads WHERE user_id=? AND announcement_id IN ($placeholders)",array_merge([$uid],$feedIds))->fetchAll() as $row)
   $states[$row['announcement_id']]['read']=true;
  out(['counts'=>$counts,'states'=>$states]);
 case 'counts':
  $aid=(int)($_GET['id']??0);
  $likes=(int)q('announcement',"SELECT COUNT(*) FROM announcement_reactions WHERE announcement_id=? AND reaction='like'",[$aid])->fetchColumn();
  $dislikes=(int)q('announcement',"SELECT COUNT(*) FROM announcement_reactions WHERE announcement_id=? AND reaction='dislike'",[$aid])->fetchColumn();
  out(['likes'=>$likes,'dislikes'=>$dislikes]);
 case 'interactions':
  $uid=(int)($_SERVER['HTTP_X_USER']??0);$aid=(int)($_GET['id']??0);
  $reaction=q('announcement','SELECT reaction FROM announcement_reactions WHERE announcement_id=? AND user_id=?',[$aid,$uid])->fetch();
  $read=q('announcement','SELECT 1 FROM announcement_reads WHERE announcement_id=? AND user_id=?',[$aid,$uid])->fetch();
  out(['reaction'=>$reaction['reaction']??null,'read'=>(bool)$read]);
 case 'react':
  $uid=(int)($_SERVER['HTTP_X_USER']??0);$aid=(int)($_POST['id']??0);$v=$_POST['reaction']??'';if(!in_array($v,['like','dislike'],true))out(['error'=>'Invalid reaction'],422);
  $old=q('announcement','SELECT reaction FROM announcement_reactions WHERE announcement_id=? AND user_id=?',[$aid,$uid])->fetch();
  if(($old['reaction']??null)===$v)q('announcement','DELETE FROM announcement_reactions WHERE announcement_id=? AND user_id=?',[$aid,$uid]);
  else q('announcement','INSERT INTO announcement_reactions(announcement_id,user_id,reaction) VALUES(?,?,?) ON DUPLICATE KEY UPDATE reaction=VALUES(reaction)',[$aid,$uid,$v]);out(['ok'=>1]);
 case 'read':
  $uid=(int)($_SERVER['HTTP_X_USER']??0);$aid=(int)($_POST['id']??0);q('announcement','INSERT IGNORE INTO announcement_reads(announcement_id,user_id,read_at) VALUES(?,?,NOW())',[$aid,$uid]);out(['ok'=>1]);

 case 'all': out(q('announcement','SELECT announcement_id,title,content,announcement_type,is_pinned,posted_by,created_at FROM announcements WHERE is_archived=0 ORDER BY is_pinned DESC,created_at DESC LIMIT 10')->fetchAll());
 case 'create': if(!$adm)out([],403);q('announcement',"INSERT INTO announcements(title,content,announcement_type,program_id,course_id,is_pinned,is_archived,posted_by,created_at) VALUES(?,?,'admin',0,0,?,0,?,NOW())",[$_POST['title'],$_POST['content'],(int)isset($_POST['pin']),(int)$_SERVER['HTTP_X_USER']]);out(['ok'=>1]);
 case 'mine': if(!$pro)out([],403);out(q('announcement','SELECT announcement_id,title,content,course_id,created_at FROM announcements WHERE posted_by=? AND is_archived=0 ORDER BY created_at DESC',[(int)$_SERVER['HTTP_X_USER']])->fetchAll());
 case 'pcreate': if(!$pro)out([],403);q('announcement',"INSERT INTO announcements(title,content,announcement_type,program_id,course_id,is_pinned,is_archived,posted_by,created_at) VALUES(?,?,'professor',0,?,0,0,?,NOW())",[$_POST['title'],$_POST['content'],(int)$_POST['course_id'],(int)$_SERVER['HTTP_X_USER']]);out(['ok'=>1]);
 case 'pdelete': if(!$pro)out([],403);q('announcement','DELETE FROM announcements WHERE announcement_id=? AND posted_by=?',[(int)$_POST['id'],(int)$_SERVER['HTTP_X_USER']]);out(['ok'=>1]);
 case 'delete': if(!$adm)out([],403);q('announcement','DELETE FROM announcements WHERE announcement_id=?',[(int)$_POST['id']]);out(['ok'=>1]);
}
out(['error'=>'bad action'],400);
