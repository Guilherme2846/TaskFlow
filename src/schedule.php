<?php
declare(strict_types=1);

function buildSchedule(PDO $db,int $user,string $today): array {
    $id=(int)field('task');
    $task=rows($db,"SELECT id,title,due FROM tasks WHERE id=? AND user_id=? AND status!='Concluída'",[$id,$user])[0]??null;
    guard($task && $task['due'] >= $today,'Selecione uma entrega em aberto com prazo a partir de hoje.');
    $effort=filter_var(field('effort'),FILTER_VALIDATE_INT);
    $limit=filter_var(field('capacity'),FILTER_VALIDATE_INT);
    guard($effort>=1 && $effort<=1440 && $limit>=1 && $limit<=480,'Confira o esforço (1 a 1440 min) e a disponibilidade diária (1 a 480 min).');
    $busy=array_column(rows($db,"SELECT b.day,SUM(b.minutes) AS total FROM study_blocks b JOIN tasks t ON t.id=b.task_id WHERE t.user_id=? AND t.status!='Concluída' AND t.id!=? AND b.day BETWEEN ? AND ? GROUP BY b.day",[$user,$id,$today,$task['due']]),'total','day');
    $remaining=$effort;
    $blocks=[];
    for($date=new DateTimeImmutable($today);$remaining>0 && $date->format('Y-m-d')<=$task['due'];$date=$date->modify('+1 day')) {
        if(field('weekdays')==='yes' && (int)$date->format('N')>5) continue;
        $day=$date->format('Y-m-d');
        $available=max(0,$limit-(int)($busy[$day]??0));
        $minutes=min($available,$remaining);
        if ($minutes>0) { $blocks[$day]=$minutes; $remaining-=$minutes; }
    }
    return ['task'=>$task,'blocks'=>$blocks,'remaining'=>$remaining,'effort'=>$effort,'capacity'=>$limit];
}

function saveSchedule(PDO $db,int $user,string $today): void {
    $db->exec('BEGIN IMMEDIATE');
    try {
        $plan=buildSchedule($db,$user,$today);
        guard(!$plan['remaining'] && hash_equals(hash('sha256',serialize($plan)),field('preview')),'A agenda mudou. Gere uma nova prévia antes de salvar.');
        $id=$plan['task']['id'];
        $db->prepare('DELETE FROM study_blocks WHERE task_id=? AND day>=?')->execute([$id,$today]);
        $insert=$db->prepare('INSERT INTO study_blocks(task_id,day,minutes) VALUES(?,?,?)');
        foreach($plan['blocks'] as $day=>$minutes) $insert->execute([$id,$day,$minutes]);
        $db->exec('COMMIT');
    } catch(Throwable $e) { $db->exec('ROLLBACK');throw $e; }
}

function loadExamples(PDO $db,int $user,string $today): void {
    $db->exec('BEGIN IMMEDIATE');
    try {
        guard(!rows($db,'SELECT id FROM tasks WHERE user_id=? LIMIT 1',[$user]),'Os exemplos estão disponíveis para uma agenda vazia.');
        $q=$db->prepare('INSERT INTO tasks(user_id,title,category,description,due,priority,status) VALUES(?,?,?,?,?,?,?)');
        foreach ([['Revisar bibliografia','TCC',-1,'Alta','Pendente'],['Ensaiar entrevista','Inglês',0,'Média','Em andamento'],['Preparar seminário','Gestão de Equipes',5,'Alta','Pendente'],['Entregar exercício','IA',1,'Média','Concluída']] as [$title,$cat,$days,$priority,$status])
            $q->execute([$user,$title,$cat,'Exemplo para demonstração',(new DateTimeImmutable($today))->modify("$days days")->format('Y-m-d'),$priority,$status]);
        $db->exec('COMMIT');
    } catch(Throwable $e) { $db->exec('ROLLBACK');throw $e; }
}

