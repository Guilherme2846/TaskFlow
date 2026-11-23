<?php
declare(strict_types=1);

function availability(PDO $db,int $user): ?array {
    return rows($db,'SELECT * FROM availability WHERE user_id=?',[$user])[0]??null;
}

function validatedMinutes(string $key,int $maximum): int {
    $minutes=filter_var(field($key),FILTER_VALIDATE_INT);
    guard($minutes!==false && $minutes>=0 && $minutes<=$maximum,'Informe minutos inteiros entre 0 e '.$maximum.'.');
    return $minutes;
}

function updateAvailability(PDO $db,int $user,string $today,string $action): void {
    if($action==='routine') {
        if(!availability($db,$user)) {
            // Migra reservas ainda vigentes; estimativas explícitas prevalecem.
            $db->prepare("INSERT OR IGNORE INTO estimates(task_id,minutes)
                SELECT t.id,SUM(b.minutes) FROM tasks t JOIN study_blocks b ON b.task_id=t.id
                WHERE t.user_id=? AND b.day>=? AND t.status!='Concluída' GROUP BY t.id")->execute([$user,$today]);
        }
        $db->prepare("INSERT INTO availability(user_id,weekday,saturday,sunday,last_day) VALUES(?,?,?,?,?)
            ON CONFLICT(user_id) DO UPDATE SET weekday=excluded.weekday,saturday=excluded.saturday,sunday=excluded.sunday")
            ->execute([$user,validatedMinutes('weekday',1440),validatedMinutes('saturday',1440),validatedMinutes('sunday',1440),$today]);
        return;
    }
    guard((bool)availability($db,$user),'Salve primeiro sua disponibilidade semanal.');
    $date=day(field('date'));
    guard($date>=$today,'Escolha hoje ou uma data futura.');
    if($action==='remove_date') {
        $db->prepare('DELETE FROM day_limits WHERE user_id=? AND day=?')->execute([$user,$date]); return;
    }
    $db->prepare('INSERT INTO day_limits(user_id,day,minutes) VALUES(?,?,?) ON CONFLICT(user_id,day) DO UPDATE SET minutes=excluded.minutes')
        ->execute([$user,$date,validatedMinutes('minutes',1440)]);
}

function estimateWarning(int $estimated, int $planned, string $due, string $today, ?string $last): string {
    if ($due < $today) return 'O prazo já passou. Restam '.$estimated.' min pela estimativa atual.';
    $missing = $estimated - $planned;
    if ($missing > 0) {
        $hours = intdiv($missing, 60);
        $minutes = $missing % 60;
        $duration = $hours ? $hours.' h'.($minutes ? ' '.$minutes.' min' : '') : $minutes.' min';
        $balance = "Faltam $duration na agenda. Cabem $planned dos $estimated min estimados até a entrega.";
        if ($missing >= 180) return "Risco alto. $balance Divida o trabalho em mais sessões ou renegocie o prazo.";
        if (!$planned) return "Sem horário reservado. $balance Abra um horário antes da entrega.";
        if ($missing <= 30) return "Apertado. $balance Se terminar um pouco antes do previsto, pode dar tempo.";
        return "Replaneje. $balance Reserve mais tempo ou reveja o que precisa entregar.";
    }
    if ($due === $today) return 'Corrido: a atividade vence hoje.';
    return $last === $due ? "Corrido: os $estimated min estimados cabem, mas o último bloco fica no dia da entrega." : '';
}

function refreshBlocks(PDO $db,int $user,string $today): array {
    $config=availability($db,$user);
    if(!$config) return [];
    $db->prepare('DELETE FROM study_blocks WHERE day>=? AND task_id IN (SELECT id FROM tasks WHERE user_id=?)')->execute([$today,$user]);
    $limits=array_column(rows($db,'SELECT day,minutes FROM day_limits WHERE user_id=? AND day>=? ORDER BY day',[$user,$today]),'minutes','day');
    $used=array_column(rows($db,'SELECT studied,SUM(minutes) AS minutes FROM study_log WHERE user_id=? AND studied=? GROUP BY studied',[$user,$today]),'minutes','studied');
    $tasks=rows($db,"SELECT t.id,t.title,t.due,e.minutes,CASE t.priority WHEN 'Alta' THEN 1 WHEN 'Média' THEN 2 ELSE 3 END AS rank FROM tasks t JOIN estimates e ON e.task_id=t.id WHERE t.user_id=? AND t.status!='Concluída' AND e.minutes>0 ORDER BY t.due, CASE t.priority WHEN 'Alta' THEN 1 WHEN 'Média' THEN 2 ELSE 3 END,t.id",[$user]);
    $insert=$db->prepare('INSERT INTO study_blocks(task_id,day,minutes) VALUES(?,?,?)');
    $warn=$db->prepare('UPDATE estimates SET warning=? WHERE task_id=?');
    $db->prepare("UPDATE estimates SET warning='' WHERE task_id IN (SELECT id FROM tasks WHERE user_id=?)")->execute([$user]);
    // Limita o calendário ao primeiro período que já comporta todo o trabalho.
    $slots=[];$target=array_sum(array_column($tasks,'minutes'));$capacity=0;
    $end=$tasks?max(array_column($tasks,'due')):$today;
    $onlyExceptions=!($config['weekday'] || $config['saturday'] || $config['sunday']);
    $custom=array_keys(array_filter($limits));$i=0;
    for($day=new DateTimeImmutable($today);$capacity<$target;$day=$day->modify('+1 day')) {
        if($onlyExceptions) {
            if(!isset($custom[$i])) break;
            $day=new DateTimeImmutable($custom[$i++]);
        }
        $d=$day->format('Y-m-d');if($d>$end) break;
        $key=match($day->format('N')){'6'=>'saturday','7'=>'sunday',default=>'weekday'};
        $free=max(0,($limits[$d]??$config[$key])-($used[$d]??0));
        if($free) { $slots[$d]=$free;$capacity+=$free; }
    }
    // Reserva primeiro as prioridades altas, usando os dias mais tardios possíveis.
    // Assim uma entrega posterior não rouba o único dia de outra que vence hoje.
    $ranked=$tasks;
    usort($ranked,static fn($a,$b)=>[$a['rank'],$a['due'],$a['id']]<=>[$b['rank'],$b['due'],$b['id']]);
    $reserve=array_reverse($slots,true);$accepted=[];
    foreach($ranked as $t) {
        $left=(int)$t['minutes'];
        foreach($reserve as $d=>$free) {
            if($d>$t['due']) continue;
            $n=min($free,$left);$reserve[$d]-=$n;$left-=$n;
            if(!$left) break;
        }
        $accepted[$t['id']]=$t['minutes']-$left;
    }
    // Antecipação por prazo: mantém viáveis todas as reservas e aproveita hoje.
    foreach($tasks as $t) {
        $amount=$accepted[$t['id']];$last=null;
        foreach($slots as $d=>$free) {
            if(!$amount || $d>$t['due']) break;
            $n=min($free,$amount);
            if(!$n) continue;
            $insert->execute([$t['id'],$d,$n]);$slots[$d]-=$n;$amount-=$n;$last=$d;
        }
        $message=estimateWarning((int)$t['minutes'], $accepted[$t['id']], $t['due'], $today, $last);
        $warn->execute([$message,$t['id']]);
    }
    $db->prepare('UPDATE availability SET last_day=? WHERE user_id=?')->execute([$today,$user]);
    return [];
}

