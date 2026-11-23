<?php
declare(strict_types=1);

function trial(PDO $db, int $user): ?string {
    $record=rows($db,'SELECT expires FROM trials WHERE user_id=?',[$user])[0]??null;
    return $record['expires']??null;
}

function recordStudy(PDO $db,int $user,bool $premium): bool {
    guard($premium,'Ative o teste do Plus para registrar estudos.');
    $task=rows($db,'SELECT title,category FROM tasks WHERE id=? AND user_id=?', [field('task'),$user])[0]??null;
    guard((bool)$task,'Escolha uma tarefa da sua agenda.');
    $minutes=filter_var(field('minutes'),FILTER_VALIDATE_INT);
    guard($minutes!==false && $minutes>=1 && $minutes<=480,'Informe entre 1 e 480 minutos.');
    $date=day(field('studied'));
    guard($date<=date('Y-m-d'),'Escolha hoje ou uma data anterior.');
    $request=field('request');
    guard((bool)preg_match('/^[a-f0-9]{32}$/D',$request),'Reabra o formulário.');
    $insert=$db->prepare('INSERT INTO study_log(user_id,request,title,category,studied,minutes) VALUES(?,?,?,?,?,?)
        ON CONFLICT(user_id,request) DO NOTHING');
    $insert->execute([$user,$request,$task['title'],$task['category'],$date,$minutes]);
    if (!$insert->rowCount()) return false;
    $logId=(int)$db->lastInsertId();
    $taskId=(int)field('task');
    $remaining=(int)(rows($db,'SELECT minutes FROM estimates WHERE task_id=?',[$taskId])[0]['minutes']??0);
    $deducted=min($minutes,$remaining);
    $db->prepare('UPDATE estimates SET minutes=minutes-? WHERE task_id=?')->execute([$deducted,$taskId]);
    $db->prepare('INSERT INTO study_effects(log_id,task_id,deducted) VALUES(?,?,?)')->execute([$logId,$taskId,$deducted]);
    return true;
}


function correctStudy(PDO $db,int $user,bool $remove,string $today): string {
    $id=(int)field('session_id');
    $log=rows($db,'SELECT l.*,e.task_id,e.deducted FROM study_log l LEFT JOIN study_effects e ON e.log_id=l.id WHERE l.id=? AND l.user_id=?',[$id,$user])[0]??null;
    guard((bool)$log,'Esta sessão já foi removida ou está indisponível.');
    $date=$remove?$log['studied']:day(field('studied'));
    guard($date<=$today,'Escolha hoje ou uma data anterior.');
    $minutes=$remove?0:filter_var(field('minutes'),FILTER_VALIDATE_INT);
    guard($remove || ($minutes>=1 && $minutes<=480),'Informe entre 1 e 480 minutos.');
    if ($log['task_id']!==null) {
        $taskId=$log['task_id'];
        $remaining=(int)(rows($db,'SELECT minutes FROM estimates WHERE task_id=?',[$taskId])[0]['minutes']??0);
        $old=(int)$log['deducted'];
        $deducted=min($minutes,$old+min($remaining,max(0,$minutes-$log['minutes'])));
        $db->prepare('UPDATE estimates SET minutes=minutes+? WHERE task_id=?')->execute([$old-$deducted,$taskId]);
        $db->prepare('UPDATE study_effects SET deducted=? WHERE log_id=?')->execute([$deducted,$id]);
    }
    if ($remove) $db->prepare('DELETE FROM study_log WHERE id=?')->execute([$id]);
    else $db->prepare('UPDATE study_log SET minutes=?,studied=? WHERE id=?')->execute([$minutes,$date,$id]);
    return $date;
}
