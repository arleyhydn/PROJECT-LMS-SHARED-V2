<?php
ini_set('display_errors','0');
ob_start();
require __DIR__.'/inc.php';
$isAjax=isset($_SERVER['HTTP_X_REQUESTED_WITH'])&&strtolower($_SERVER['HTTP_X_REQUESTED_WITH'])==='xmlhttprequest';
if(!in_array($_SESSION['role']??'', ['student','professor','admin'], true)){http_response_code(403);if($isAjax){header('Content-Type: application/json; charset=utf-8');ob_clean();echo json_encode(['ok'=>false,'error'=>'Your session expired or your account is not allowed to do this.']);}else echo 'Forbidden';exit;}
if($_SERVER['REQUEST_METHOD']!=='POST'){http_response_code(405);if($isAjax){header('Content-Type: application/json; charset=utf-8');ob_clean();echo json_encode(['ok'=>false,'error'=>'Method not allowed']);}else echo 'Method not allowed';exit;}
if($isAjax&&ob_get_level())ob_clean();
$action=$_POST['action']??'';$id=(int)($_POST['id']??0);
function announcement_action_error($message,$status=400,$ajax=false){
    http_response_code($status);
    if($ajax){header('Content-Type: application/json; charset=utf-8');echo json_encode(['ok'=>false,'error'=>$message]);exit;}
    exit($message);
}
if($id<1||!in_array($action,['like','dislike','read'],true))announcement_action_error('Invalid announcement action.',400,$isAjax);
$mutation=$action==='read'?api('announcement','read',[],['id'=>$id]):api('announcement','react',[],['id'=>$id,'reaction'=>$action]);
if(!is_array($mutation)||isset($mutation['error'])||empty($mutation['ok'])){
    error_log('Announcement action failed for ID '.$id.': '.(is_array($mutation)?($mutation['error']??'missing success response'):'invalid service response'));
    announcement_action_error(is_array($mutation)?($mutation['error']??'The announcement could not be updated. Please check the server logs.'): 'The announcement service returned an invalid response.',502,$isAjax);
}
if($isAjax){
    // The mutation is already committed. A secondary read/count query must not
    // turn a successful Like/Dislike/Read into a misleading failure response.
    $state=api('announcement','interactions',['id'=>$id]);
    $counts=api('announcement','counts',['id'=>$id]);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'ok'=>true,
        'action'=>$action,
        'id'=>$id,
        'state'=>(is_array($state)&&!isset($state['error']))?$state:[],
        'counts'=>(is_array($counts)&&!isset($counts['error']))?$counts:[],
        'refresh_needed'=>(isset($state['error'])||isset($counts['error']))
    ]);exit;
}
$role=$_SESSION['role'];
$returnTo=$_POST['return_to']??'';
// Keep users on the dashboard when they interact with the dashboard feed,
// regardless of whether they are a student, professor, or admin.
if($returnTo==='dashboard'){
    $dashboardByRole=['student'=>'student/dashboard.php','professor'=>'professor/dashboard.php','admin'=>'admin/dashboard.php'];
    $back=$dashboardByRole[$role]??'index.php';
}else{
    $announcementsByRole=['student'=>'student/announcements.php','professor'=>'professor/announcements.php','admin'=>'admin/announcements.php'];
    $back=$announcementsByRole[$role]??'index.php';
}
header('Location: '.BASE.'/web/'.$back);exit;
