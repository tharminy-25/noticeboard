<?php
require_once __DIR__ . '/../config/helpers.php';
$method=$_SERVER['REQUEST_METHOD'];
$id=isset($_GET['id'])?(int)$_GET['id']:null;
$db=getDB();
switch($method){
case 'GET':
    requireAdmin();
    if($id){
        $st=$db->prepare('SELECT id,name,email,role,dept,regno,dob,approved,created_at FROM users WHERE id=?');
        $st->execute([$id]); $user=$st->fetch();
        if(!$user) fail('User not found.',404);
        ok(['user'=>$user]);
    }
    $where=['1=1']; $params=[];
    if(!empty($_GET['search'])){
        $s='%'.$_GET['search'].'%';
        $where[]='(name LIKE ? OR email LIKE ? OR regno LIKE ?)';
        $params[]=$s; $params[]=$s; $params[]=$s;
    }
    if(isset($_GET['approved'])){ $where[]='approved=?'; $params[]=(int)$_GET['approved']; }
    if(!empty($_GET['role'])){ $where[]='role=?'; $params[]=$_GET['role']; }
    $w=implode(' AND ',$where);
    $st=$db->prepare("SELECT id,name,email,role,dept,regno,dob,approved,created_at FROM users WHERE $w ORDER BY created_at DESC");
    $st->execute($params);
    $users=$st->fetchAll();
    $p=$db->prepare("SELECT COUNT(*) FROM users WHERE approved=0 AND role='user'");
    $p->execute();
    ok(['users'=>$users,'pending_count'=>(int)$p->fetchColumn()]);
    break;
case 'PUT':
    $admin=requireAdmin();
    if(!$id) fail('User ID required.');
    if($id===$admin['id']) fail('Use profile endpoint for own account.');
    $st=$db->prepare('SELECT * FROM users WHERE id=?');
    $st->execute([$id]); if(!$st->fetch()) fail('User not found.',404);
    $fields=[]; $vals=[];
    $approved=param('approved',null);
    $role=param('role',null);
    $dept=param('dept',null);
    if(!is_null($approved)){ $fields[]='approved=?'; $vals[]=(int)$approved; }
    if(!is_null($role)&&in_array($role,['admin','user'])){ $fields[]='role=?'; $vals[]=$role; }
    if(!is_null($dept)){ $fields[]='dept=?'; $vals[]=$dept; }
    if(empty($fields)) fail('Nothing to update.');
    $vals[]=$id;
    $db->prepare('UPDATE users SET '.implode(',',$fields).' WHERE id=?')->execute($vals);
    ok([],'User updated.');
    break;
case 'DELETE':
    $admin=requireAdmin();
    if(!$id) fail('User ID required.');
    if($id===$admin['id']) fail('Cannot delete your own account.');
    $st=$db->prepare('DELETE FROM users WHERE id=?');
    $st->execute([$id]);
    if($st->rowCount()===0) fail('User not found.',404);
    ok([],'User removed.');
    break;
default: fail('Method not allowed.',405);
}