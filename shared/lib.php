<?php // Shared kernel: each service opens ONLY its own database.
require_once __DIR__.'/../config.php';
function q($svc,$sql,$p=[]){static $c=[];
  $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . SERVICES[$svc] . ';charset=utf8mb4';
  if (DB_PORT !== '') $dsn .= ';port=' . DB_PORT;
  $c[$svc]??=new PDO($dsn,DB_USER,DB_PASS,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
  $s=$c[$svc]->prepare($sql);$s->execute($p);return $s;}
function out($d,$code=200){http_response_code($code);header('Content-Type: application/json');echo json_encode($d);exit;}
// Services only accept calls that carry the internal key (i.e. from the gateway or another service)
if(strpos($_SERVER['SCRIPT_NAME']??'','/services/')!==false&&!hash_equals(INTERNAL_KEY,$_SERVER['HTTP_X_INTERNAL_KEY']??''))out(['error'=>'forbidden'],403);
// HTTP client used for service-to-service and gateway calls
function http($svc,$qs,$headers=[],$post=null){
  $ch=curl_init(BASE."/services/$svc/index.php?$qs");
  $o=[CURLOPT_RETURNTRANSFER=>1,CURLOPT_HTTPHEADER=>array_merge($headers,['X-Internal-Key: '.INTERNAL_KEY]),CURLOPT_TIMEOUT=>5];
  if($post)$o[CURLOPT_POSTFIELDS]=http_build_query($post);
  curl_setopt_array($ch,$o);return json_decode(curl_exec($ch),true);}
function idlist(){return implode(',',array_filter(array_map('intval',explode(',',$_GET['ids']??''))))?:'0';}
