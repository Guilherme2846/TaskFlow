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
    return $insert->rowCount()===1;
}

