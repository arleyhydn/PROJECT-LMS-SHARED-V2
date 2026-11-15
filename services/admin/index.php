<?php require '../../shared/lib.php'; // ADMIN SERVICE (portal settings + audit log)
$f=__DIR__.'/settings.json';$d=['portal_name'=>'FEU Marikina TED LMS','academic_year'=>'A.Y. 2026-2027','semester'=>'Second Semester','year_start'=>'August 25, 2026'];
switch($_GET['action']??''){
 case 'get': out(array_merge($d,is_file($f)?json_decode(file_get_contents($f),true):[]));
 case 'set': if(($_SERVER['HTTP_X_ROLE']??'')!=='admin')out([],403);
  file_put_contents($f,json_encode(array_intersect_key($_POST,$d)));
  q('admin','INSERT INTO admin_audit_log(admin_id,action_type,target_table,target_id,description,created_at) VALUES(?,?,?,?,?,NOW())',[(int)$_SERVER['HTTP_X_USER'],'update','settings',0,'Portal settings updated']);out(['ok'=>1]);
}
out(['error'=>'bad action'],400);
