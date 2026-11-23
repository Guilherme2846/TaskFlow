<?php
$estimates=$cfg ? rows($db,"SELECT t.id,t.title,t.due,e.minutes,e.warning,COALESCE(SUM(b.minutes),0) AS planned,MAX(b.day) AS last FROM estimates e JOIN tasks t ON t.id=e.task_id LEFT JOIN study_blocks b ON b.task_id=t.id AND b.day BETWEEN ? AND t.due WHERE t.user_id=? AND t.status!='Concluída' AND e.minutes>0 GROUP BY t.id ORDER BY t.due,t.id",[$today,$user]) : [];
$alerts=array_filter($estimates,fn($entry)=>$entry['warning']!=='');
$byDue=[];
foreach($estimates as $entry) {
    if ($entry['due']<$today) continue;
    $day=$entry['due'];
    $byDue[$day]??=['estimate'=>0,'planned'=>0];
    $byDue[$day]['estimate']+=(int)$entry['minutes'];
    $byDue[$day]['planned']+=(int)$entry['planned'];
}
$shortfalls=array_filter($byDue,fn($day)=>$day['estimate']>$day['planned']);
$critical=count(array_filter($shortfalls,fn($day)=>$day['estimate']-$day['planned']>=180));
function studyDuration(int $minutes): string {
    if ($minutes<60) return $minutes.' min';
    return intdiv($minutes,60).' h'.($minutes%60 ? ' '.($minutes%60).' min' : '');
}
?>
<aside class="tutor-dock" aria-label="TutorIA">
<?php if($alerts): ?><button type="button" id="alert-launch" class="<?=$critical?'critical':''?>" aria-expanded="false" aria-controls="tutor-panel"><?=$critical?'Risco alto':'Prazos'?> · <?=count($alerts)?> <?=count($alerts)===1?'aviso':'avisos'?></button><?php endif; ?>
<button type="button" id="tutor-launch" aria-expanded="false" aria-controls="tutor-panel">✦ TutorIA</button>
<section id="tutor-panel" hidden aria-labelledby="panel-title">
<div class="dock-heading"><strong id="panel-title">TutorIA</strong><button type="button" id="tutor-close" aria-label="Fechar painel">×</button></div>
<?php if($alerts): ?><section id="panel-alerts" hidden><h2>Atenção aos prazos</h2>
<?php if($shortfalls): ?><div class="deadline-summary"><h3>Tempo que falta até cada entrega</h3>
<?php foreach($shortfalls as $day=>$totals): ?><p><strong><?=brDate($day)?> · faltam <?=studyDuration($totals['estimate']-$totals['planned'])?></strong><br>As entregas dessa data somam <?=studyDuration($totals['estimate'])?> de trabalho estimado. Sua agenda reserva <?=studyDuration($totals['planned'])?> para elas até lá.</p><?php endforeach; ?></div><h3>Por atividade</h3>
<?php else: ?><p>Confira abaixo as entregas vencidas ou que ocupam o último dia do prazo.</p><?php endif; ?>
<?php foreach($alerts as $alert): ?><div class="deadline-note"><strong><?=h($alert['title'])?></strong><p><?=h(estimateWarning((int)$alert['minutes'],(int)$alert['planned'],$alert['due'],$today,$alert['last']))?></p><a href="?page=tutor&amp;id=<?=(int)$alert['id']?>">Conversar sobre esta tarefa</a> · <a href="?page=edit&amp;id=<?=(int)$alert['id']?>">Revisar estimativa</a></div><?php endforeach; ?><a href="?page=availability">Ajustar disponibilidade</a></section><?php endif; ?>
<div id="panel-conversation"><?php if($all): ?>
<label class="dock-picker">Atividade<select id="panel-task"><?php foreach($all as $entry): ?><option value="<?=(int)$entry['id']?>" <?=$id==(int)$entry['id']?'selected':''?>><?=h($entry['title'])?></option><?php endforeach; ?></select></label>
<iframe title="Conversa sobre a atividade" id="panel-chat"></iframe>
<?php else: ?><p>Cadastre uma atividade para conversar sobre ela.</p><a href="?page=new">Criar atividade</a><?php endif; ?>
</div></section></aside><script src="tutor-panel.js?v=2" defer></script>
