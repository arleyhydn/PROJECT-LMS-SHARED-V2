<?php
require '../inc.php';
guard('professor');
$id=(int)($_GET['id']??0);
if($id<1){http_response_code(400);exit('Invalid submission ID.');}
$info=api('assignment','submission_info',['submission_id'=>$id]);
if(!empty($info['error'])||empty($info['file_path'])){http_response_code(404);exit(h($info['error']??'Submission not found.'));}
$file=basename((string)$info['file_path']);
$path=__DIR__.'/../../storage/submissions/'.$file;
if(!is_file($path)){http_response_code(404);exit('The submitted file is missing from storage.');}
$ext=strtolower(pathinfo($file,PATHINFO_EXTENSION));
$types=['pdf'=>'application/pdf','txt'=>'text/plain','png'=>'image/png','jpg'=>'image/jpeg','jpeg'=>'image/jpeg','doc'=>'application/msword','docx'=>'application/vnd.openxmlformats-officedocument.wordprocessingml.document','zip'=>'application/zip'];
header('Content-Type: '.($types[$ext]??'application/octet-stream'));
header('Content-Length: '.filesize($path));
header('Content-Disposition: attachment; filename="'.preg_replace('/[^A-Za-z0-9._-]/','_', (string)($info['title']??'submission')).'.'.$ext.'"');
header('X-Content-Type-Options: nosniff');
readfile($path);
exit;
