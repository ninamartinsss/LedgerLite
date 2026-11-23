<?php
declare(strict_types=1);
require_once dirname(__DIR__).'/src/Ledger.php';
header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: no-store');
function respond(int $status,array $payload):never {http_response_code($status);echo json_encode($payload,JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR);exit;}
try {
 $dbPath=getenv('LEDGER_DB')?:dirname(__DIR__).'/data/ledger.sqlite';
 $dir=dirname($dbPath);
 if(!is_dir($dir)&&!mkdir($dir,0770,true)&&!is_dir($dir))throw new RuntimeException('Cannot create data directory');
 $ledger=new Ledger($dbPath);
 $method=$_SERVER['REQUEST_METHOD']??'GET';$path=parse_url($_SERVER['REQUEST_URI']??'/',PHP_URL_PATH);
 if($method==='GET'&&$path==='/health')respond(200,['status'=>'ok']);
 if($method==='GET'&&$path==='/transactions')respond(200,['data'=>$ledger->all(isset($_GET['month'])?(string)$_GET['month']:null)]);
 if($method==='GET'&&$path==='/summary')respond(200,$ledger->summary(isset($_GET['month'])?(string)$_GET['month']:null));
 if($method==='POST'&&$path==='/transactions'){
  $body=json_decode(file_get_contents('php://input'),true,512,JSON_THROW_ON_ERROR);
  if(!is_array($body)||array_is_list($body))throw new InvalidArgumentException('Expected a JSON object');
  respond(201,['data'=>$ledger->add($body)]);
 }
 if($method==='DELETE'&&preg_match('~^/transactions/([1-9][0-9]*)$~D',$path,$matches)){
  if(!$ledger->delete((int)$matches[1]))respond(404,['error'=>'transaction not found']);
  respond(200,['deleted'=>true]);
 }
 respond(404,['error'=>'route not found']);
} catch(InvalidArgumentException|JsonException $e){respond(422,['error'=>$e->getMessage()]);}
catch(Throwable $e){error_log((string)$e);respond(500,['error'=>'internal server error']);}