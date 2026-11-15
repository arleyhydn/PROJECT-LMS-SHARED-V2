<?php require '../../shared/lib.php'; // PLANNING SERVICE (todos + calendar)
$u=(int)$_SERVER['HTTP_X_USER'];
switch($_GET['action']??''){
 case 'todos': out(q('planning',"SELECT todo_id,title,status,due_date FROM todos WHERE user_id=? ORDER BY due_date",[$u])->fetchAll());
 case 'add_todo': q('planning',"INSERT INTO todos(user_id,title,status,due_date,created_at) VALUES(?,?, 'pending',DATE_ADD(NOW(),INTERVAL 7 DAY),NOW())",[$u,$_POST['title']??'']);out(['ok'=>1]);
 case 'toggle_todo': q('planning',"UPDATE todos SET status=IF(status='completed','pending','completed') WHERE todo_id=? AND user_id=?",[(int)$_POST['id'],$u]);out(['ok'=>1]);
}
out(['error'=>'bad action'],400);
