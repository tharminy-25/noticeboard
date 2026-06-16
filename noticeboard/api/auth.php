<?php
require_once __DIR__ . '/../config/helpers.php';
$action = $_GET['action'] ?? param('action','');
switch($action){
case 'login':
    $email=trim(param('email','')); $pass=param('password',''); $role=param('role','user');
    if(!$email||!$pass) fail('Email and password required.');
    $db=getDB(); $st=$db->prepare('SELECT * FROM users WHERE email=? AND role=?');
    $st->execute([$email,$role]); $user=$st->fetch();
    if(!$user){ $st2=$db->prepare('SELECT role FROM users WHERE email=?'); $st2->execute([$email]); $o=$st2->fetch();
        if($o) fail("This email is registered as {$o['role']}. Use correct portal."); fail('Email not found.'); }
    if(!password_verify($pass,$user['password'])) fail('Incorrect password.');
    if($user['role']==='user'&&!$user['approved']){ unset($user['password']); ok(['status'=>'pending','user'=>$user],'Pending approval.'); }
    unset($user['password']); $_SESSION['user']=$user; ok(['status'=>'ok','user'=>$user],'Login successful.');
    break;
case 'register':
    $role=param('role','user'); $db=getDB();
    if($role==='admin'){
        $name=trim(param('name','')); $email=trim(param('email','')); $pass=param('password',''); $code=param('admin_code','');
        if(!$name||!$email||!$pass||!$code) fail('All fields required.');
        if($code!=='ADMIN2025') fail('Invalid admin code.');
        if(strlen($pass)<6) fail('Password min 6 characters.');
        $c=$db->prepare('SELECT id FROM users WHERE email=?'); $c->execute([$email]); if($c->fetch()) fail('Email already registered.');
        $h=password_hash($pass,PASSWORD_BCRYPT);
        $db->prepare('INSERT INTO users (name,email,password,role,dept,approved) VALUES (?,?,?,"admin","Administration",1)')->execute([$name,$email,$h]);
        ok(['user_id'=>$db->lastInsertId()],'Admin account created.');
    } else {
        $name=trim(param('name','')); $email=trim(param('email','')); $pass=param('password','');
        $regno=trim(param('regno','')); $dob=param('dob',''); $dept=param('dept','');
        if(!$name||!$email||!$pass||!$regno||!$dob||!$dept) fail('All fields required.');
        if(strlen($pass)<6) fail('Password min 6 characters.');
        $c=$db->prepare('SELECT id FROM users WHERE email=?'); $c->execute([$email]); if($c->fetch()) fail('Email already registered.');
        $h=password_hash($pass,PASSWORD_BCRYPT);
        $db->prepare('INSERT INTO users (name,email,password,role,dept,regno,dob,approved) VALUES (?,?,?,"user",?,?,?,0)')->execute([$name,$email,$h,$dept,$regno,$dob]);
        ok(['user_id'=>$db->lastInsertId()],'Registration submitted. Waiting for admin approval.');
    }
    break;
case 'logout':
    session_destroy(); ok([],'Logged out.');
    break;
case 'recover':
    $email=trim(param('email','')); if(!$email) fail('Email required.');
    $db=getDB(); $st=$db->prepare('SELECT id,name,email FROM users WHERE email=?'); $st->execute([$email]); $user=$st->fetch();
    if(!$user) fail('No account found with that email.');
    ok(['email'=>$user['email']],"Password reset link sent to {$user['email']}.");
    break;
case 'me':
    $user=currentUser(); if(!$user) fail('Not authenticated.',401);
    $db=getDB(); $st=$db->prepare('SELECT * FROM users WHERE id=?'); $st->execute([$user['id']]); $u=$st->fetch();
    if(!$u) fail('User not found.',404); unset($u['password']); ok(['user'=>$u]);
    break;
case 'update_profile':
    $user=requireAuth(); $db=getDB();
    $name=trim(param('name','')); $pass=param('password',''); $dept=param('dept',''); $dob=param('dob','');
    if(!$name) fail('Name cannot be empty.');
    if($pass){ if(strlen($pass)<6) fail('Password min 6 characters.'); $h=password_hash($pass,PASSWORD_BCRYPT);
        $db->prepare('UPDATE users SET name=?,dept=?,dob=?,password=? WHERE id=?')->execute([$name,$dept,$dob?:null,$h,$user['id']]);
    } else { $db->prepare('UPDATE users SET name=?,dept=?,dob=? WHERE id=?')->execute([$name,$dept,$dob?:null,$user['id']]); }
    $_SESSION['user']['name']=$name; $_SESSION['user']['dept']=$dept;
    $st=$db->prepare('SELECT * FROM users WHERE id=?'); $st->execute([$user['id']]); $u=$st->fetch(); unset($u['password']);
    ok(['user'=>$u],'Profile updated.');
    break;
default: fail('Unknown action.',404);
}