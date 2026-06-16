<?php
require_once __DIR__ . '/../config/helpers.php';
$method=$_SERVER['REQUEST_METHOD'];
$db=getDB();
switch($method){
case 'GET':
    $user=requireApproved();
    $nid=(int)($_GET['notice_id']??0);
    if(!$nid) fail('notice_id required.');
    $st=$db->prepare('SELECT COUNT(*) FROM likes WHERE notice_id=?');
    $st->execute([$nid]); $total=(int)$st->fetchColumn();
    $st2=$db->prepare('SELECT 1 FROM likes WHERE notice_id=? AND user_id=?');
    $st2->execute([$nid,$user['id']]); $liked=(bool)$st2->fetch();
    ok(['like_count'=>$total,'user_liked'=>$liked]);
    break;
case 'POST':
    $user=requireApproved();
    $nid=(int)param('notice_id',0);
    if(!$nid) fail('notice_id required.');
    $c=$db->prepare('SELECT id FROM notices WHERE id=?');
    $c->execute([$nid]); if(!$c->fetch()) fail('Notice not found.',404);
    $st=$db->prepare('SELECT 1 FROM likes WHERE user_id=? AND notice_id=?');
    $st->execute([$user['id'],$nid]); $already=(bool)$st->fetch();
    if($already){
        $db->prepare('DELETE FROM likes WHERE user_id=? AND notice_id=?')->execute([$user['id'],$nid]);
        $action='unliked';
    } else {
        $db->prepare('INSERT INTO likes (user_id,notice_id) VALUES (?,?)')->execute([$user['id'],$nid]);
        $action='liked';
    }
    $cnt=$db->prepare('SELECT COUNT(*) FROM likes WHERE notice_id=?');
    $cnt->execute([$nid]);
    ok(['action'=>$action,'like_count'=>(int)$cnt->fetchColumn(),'user_liked'=>($action==='liked')]);
    break;
default: fail('Method not allowed.',405);
}