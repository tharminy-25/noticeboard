<?php
require_once __DIR__ . '/../config/helpers.php';
$method=$_SERVER['REQUEST_METHOD'];
$id=isset($_GET['id'])?(int)$_GET['id']:null;
$db=getDB();
switch($method){
case 'GET':
    $user=requireApproved();
    if($id){
        $st=$db->prepare("SELECT n.*,COUNT(DISTINCT l.user_id) AS like_count,COUNT(DISTINCT c.id) AS comment_count,MAX(CASE WHEN l.user_id=? THEN 1 ELSE 0 END) AS user_liked FROM notices n LEFT JOIN likes l ON l.notice_id=n.id LEFT JOIN comments c ON c.notice_id=n.id WHERE n.id=? GROUP BY n.id");
        $st->execute([$user['id'],$id]); $n=$st->fetch(); if(!$n) fail('Notice not found.',404);
        $cs=$db->prepare('SELECT id,notice_id,user_id,author_name,text,created_at FROM comments WHERE notice_id=? ORDER BY created_at ASC');
        $cs->execute([$id]); $n['comments']=$cs->fetchAll(); ok(['notice'=>$n]);
    }
    $where=['1=1']; $params=[$user['id']];
    if(!empty($_GET['type'])){ $where[]='n.type=?'; $params[]=$_GET['type']; }
    if($user['role']!=='admin'&&!empty($user['dept'])){ $where[]='(n.dept="All" OR n.dept=?)'; $params[]=$user['dept']; }
    if(!empty($_GET['search'])){ $where[]='(n.title LIKE ? OR n.content LIKE ?)'; $s='%'.$_GET['search'].'%'; $params[]=$s; $params[]=$s; }
    $w=implode(' AND ',$where);
    $st=$db->prepare("SELECT n.*,COUNT(DISTINCT l.user_id) AS like_count,COUNT(DISTINCT c.id) AS comment_count,MAX(CASE WHEN l.user_id=? THEN 1 ELSE 0 END) AS user_liked FROM notices n LEFT JOIN likes l ON l.notice_id=n.id LEFT JOIN comments c ON c.notice_id=n.id WHERE $w GROUP BY n.id ORDER BY n.created_at DESC");
    $st->execute($params); ok(['notices'=>$st->fetchAll()]);
    break;
case 'POST':
    $admin=requireAdmin();
    $title=trim(param('title','')); $content=trim(param('content','')); $type=param('type','general'); $dept=param('dept','All');
    if(!$title||!$content) fail('Title and content required.');
    $db->prepare('INSERT INTO notices (title,content,type,dept,author_id,author_name) VALUES (?,?,?,?,?,?)')->execute([$title,$content,$type,$dept,$admin['id'],$admin['name']]);
    $nid=$db->lastInsertId(); $st=$db->prepare('SELECT * FROM notices WHERE id=?'); $st->execute([$nid]); $n=$st->fetch();
    $n['like_count']=0; $n['comment_count']=0; $n['user_liked']=0; $n['comments']=[];
    ok(['notice'=>$n],'Notice published.');
    break;
case 'PUT':
    requireAdmin(); if(!$id) fail('Notice ID required.');
    $title=trim(param('title','')); $content=trim(param('content','')); $type=param('type','general'); $dept=param('dept','All');
    if(!$title||!$content) fail('Title and content required.');
    $db->prepare('UPDATE notices SET title=?,content=?,type=?,dept=? WHERE id=?')->execute([$title,$content,$type,$dept,$id]);
    ok([],'Notice updated.');
    break;
case 'DELETE':
    requireAdmin(); if(!$id) fail('Notice ID required.');
    $st=$db->prepare('DELETE FROM notices WHERE id=?'); $st->execute([$id]);
    if($st->rowCount()===0) fail('Notice not found.',404);
    ok([],'Notice deleted.');
    break;
default: fail('Method not allowed.',405);
}