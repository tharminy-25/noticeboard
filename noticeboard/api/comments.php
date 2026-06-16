<?php
require_once __DIR__ . '/../config/helpers.php';
$method=$_SERVER['REQUEST_METHOD'];
$db=getDB();
switch($method){
case 'GET':
    $user=requireApproved();
    $nid=(int)($_GET['notice_id']??0);
    if(!$nid) fail('notice_id required.');
    $st=$db->prepare('SELECT id,notice_id,user_id,author_name,text,created_at FROM comments WHERE notice_id=? ORDER BY created_at ASC');
    $st->execute([$nid]);
    $comments=$st->fetchAll();
    ok(['comments'=>$comments,'count'=>count($comments)]);
    break;
case 'POST':
    $user=requireApproved();
    $nid=(int)param('notice_id',0);
    $text=trim(param('text',''));
    if(!$nid) fail('notice_id required.');
    if(!$text) fail('Comment cannot be empty.');
    if(strlen($text)>1000) fail('Comment too long.');
    $c=$db->prepare('SELECT id FROM notices WHERE id=?');
    $c->execute([$nid]); if(!$c->fetch()) fail('Notice not found.',404);
    $db->prepare('INSERT INTO comments (notice_id,user_id,author_name,text) VALUES (?,?,?,?)')->execute([$nid,$user['id'],$user['name'],$text]);
    $newid=$db->lastInsertId();
    $st=$db->prepare('SELECT * FROM comments WHERE id=?');
    $st->execute([$newid]); $comment=$st->fetch();
    $cnt=$db->prepare('SELECT COUNT(*) FROM comments WHERE notice_id=?');
    $cnt->execute([$nid]);
    ok(['comment'=>$comment,'comment_count'=>(int)$cnt->fetchColumn()],'Comment posted.');
    break;
case 'DELETE':
    $user=requireAuth();
    $id=(int)($_GET['id']??0);
    if(!$id) fail('Comment ID required.');
    $st=$db->prepare('SELECT * FROM comments WHERE id=?');
    $st->execute([$id]); $comment=$st->fetch();
    if(!$comment) fail('Comment not found.',404);
    if($user['role']!=='admin'&&$comment['user_id']!==$user['id']) fail('Not allowed.',403);
    $db->prepare('DELETE FROM comments WHERE id=?')->execute([$id]);
    $cnt=$db->prepare('SELECT COUNT(*) FROM comments WHERE notice_id=?');
    $cnt->execute([$comment['notice_id']]);
    ok(['comment_count'=>(int)$cnt->fetchColumn()],'Comment deleted.');
    break;
default: fail('Method not allowed.',405);
}