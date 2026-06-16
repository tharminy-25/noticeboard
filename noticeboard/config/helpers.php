<?php
session_start();
header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }
require_once __DIR__ . '/database.php';
function respond($data, $code=200){ http_response_code($code); echo json_encode($data); exit; }
function ok($data=[], $msg='Success'){ respond(array_merge(['success'=>true,'message'=>$msg],$data)); }
function fail($msg='Error', $code=400){ respond(['success'=>false,'message'=>$msg],$code); }
function body(){ static $b=null; if($b===null){$b=json_decode(file_get_contents('php://input'),true)??[];} return $b; }
function param($k,$d=null){ $b=body(); return $b[$k]??$_POST[$k]??$_GET[$k]??$d; }
function currentUser(){ return $_SESSION['user']??null; }
function requireAuth(){ $u=currentUser(); if(!$u)fail('Not authenticated.',401); return $u; }
function requireAdmin(){ $u=requireAuth(); if($u['role']!=='admin')fail('Admin only.',403); return $u; }
function requireApproved(){ $u=requireAuth(); if(!$u['approved'])fail('Account pending approval.',403); return $u; }