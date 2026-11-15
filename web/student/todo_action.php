<?php
ini_set('display_errors','0');
ob_start();
require '../inc.php';
if(($_SESSION['role']??'')!=='student'){http_response_code(401);header('Content-Type: application/json; charset=utf-8');
if(ob_get_level())ob_clean();echo json_encode(['ok'=>false,'error'=>'Your session expired. Please sign in again.']);exit;}

header('Content-Type: application/json; charset=utf-8');
if($_SERVER['REQUEST_METHOD']!=='POST'){http_response_code(405);echo json_encode(['ok'=>false,'error'=>'Method not allowed.']);exit;}
$id=(int)($_POST['id']??0);
if($id<1){http_response_code(400);echo json_encode(['ok'=>false,'error'=>'Invalid to-do item.']);exit;}
$result=api('planning','toggle_todo',[],['id'=>$id]);
if(!is_array($result)||isset($result['error'])||empty($result['ok'])){http_response_code(502);echo json_encode(['ok'=>false,'error'=>is_array($result)?($result['error']??'Could not update this task.'):'The planning service returned an invalid response.']);exit;}
$items=api('planning','todos');
if(!is_array($items)||isset($items['error'])){http_response_code(502);echo json_encode(['ok'=>false,'error'=>'Task was updated, but its current status could not be loaded.']);exit;}
foreach($items as $item){if((int)($item['todo_id']??0)===$id){echo json_encode(['ok'=>true,'id'=>$id,'status'=>$item['status']??'pending']);exit;}}
http_response_code(404);echo json_encode(['ok'=>false,'error'=>'Task was not found for this account.']);
