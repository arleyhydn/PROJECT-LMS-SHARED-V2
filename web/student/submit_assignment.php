<?php require '../inc.php';guard('student');
if($_SERVER['REQUEST_METHOD']!=='POST'||empty($_FILES['submission'])||$_FILES['submission']['error']!==UPLOAD_ERR_OK){http_response_code(400);exit('Please choose a valid file.');}
$f=$_FILES['submission'];if($f['size']>10485760){http_response_code(413);exit('Maximum file size is 10 MB.');}
$allowed=['pdf','doc','docx','txt','zip','png','jpg','jpeg'];$ext=strtolower(pathinfo($f['name'],PATHINFO_EXTENSION));if(!in_array($ext,$allowed,true)){http_response_code(415);exit('File type not allowed.');}
$data=file_get_contents($f['tmp_name']);$res=api('assignment','submit',[],['assignment_id'=>(int)($_POST['assignment_id']??0),'filename'=>basename($f['name']),'file_base64'=>base64_encode($data)]);
if(!empty($res['ok'])){header('Location: assignments.php?sent=1');exit;}http_response_code(400);echo h($res['error']??'Submission failed');
