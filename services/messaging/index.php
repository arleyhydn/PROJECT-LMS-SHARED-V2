<?php require '../../shared/lib.php'; // MESSAGING SERVICE
$u=(int)$_SERVER['HTTP_X_USER'];
switch($_GET['action']??''){
 case 'notifications':
	$count=(int)q('messaging','SELECT COUNT(*) FROM messages WHERE receiver_id=? AND is_read=0',[$u])->fetchColumn();
	$items=q('messaging',"SELECT m.message_id,m.sender_id,m.subject,m.body,m.sent_at,CONCAT(COALESCE(u.first_name,''),' ',COALESCE(u.last_name,'')) AS sender_name FROM messages m LEFT JOIN lms_user_svc.users u ON u.user_id=m.sender_id WHERE m.receiver_id=? AND m.is_read=0 ORDER BY m.sent_at DESC,m.message_id DESC LIMIT 10",[$u])->fetchAll();
	out(['count'=>$count,'items'=>$items]);
 case 'inbox': out(q('messaging','SELECT message_id,sender_id,receiver_id,body,sent_at,is_read FROM messages WHERE sender_id=? OR receiver_id=? ORDER BY sent_at',[$u,$u])->fetchAll());
 case 'conversation': $peer=(int)($_GET['with']??0); if($peer<1)out(['error'=>'Invalid conversation'],400); out(q('messaging','SELECT message_id,sender_id,receiver_id,body,sent_at,is_read FROM messages WHERE (sender_id=? AND receiver_id=?) OR (sender_id=? AND receiver_id=?) ORDER BY sent_at,message_id',[$u,$peer,$peer,$u])->fetchAll());
 case 'unread': out(q('messaging','SELECT COUNT(*) n FROM messages WHERE receiver_id=? AND is_read=0',[$u])->fetch());
 case 'mark-read': $sender=(int)($_POST['with']??0); if($sender<1)out(['error'=>'Invalid conversation'],400); q('messaging','UPDATE messages SET is_read=1 WHERE receiver_id=? AND sender_id=? AND is_read=0',[$u,$sender]); out(['ok'=>1]);
 case 'send': q('messaging','INSERT INTO messages(sender_id,receiver_id,parent_message_id,subject,body,is_read,sent_at) VALUES(?,?,0,?,?,0,NOW())',[$u,(int)$_POST['to'],$_POST['subject']??'Message',$_POST['body']??'']);out(['ok'=>1]);
}
out(['error'=>'bad action'],400);
