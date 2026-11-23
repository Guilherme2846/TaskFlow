<?php
$groups=[];
if ($view==='board') {
    foreach ($statuses as $status) $groups[$status]=[];
    foreach ($all as $item) $groups[$item['status']][]=$item;
} else {
    $studyDays=[];
    foreach(rows($db,"SELECT b.day,b.minutes,t.id,t.title FROM study_blocks b JOIN tasks t ON t.id=b.task_id WHERE t.user_id=? AND t.status!='Concluída' AND b.day BETWEEN ? AND ? ORDER BY b.day,t.title",[$user,$start->format('Y-m-d'),$start->modify('+6 days')->format('Y-m-d')]) as $block) $studyDays[$block['day']][]=$block;
    $weekdays=['Seg','Ter','Qua','Qui','Sex','Sáb','Dom'];
    for ($i=0;$i<7;$i++) $groups[$start->modify("+$i days")->format('Y-m-d')]=[];
    foreach ($all as $item) if(isset($groups[$item['due']])) $groups[$item['due']][]=$item;
}
?>
<section class="card">
<?php if($view==='week'):?>
<div class="week-head"><h2><?= $start->format('d/m') ?> a <?= $start->modify('+6 days')->format('d/m/Y') ?></h2>
<nav aria-label="Navegação entre semanas"><a href="?page=availability">Minha disponibilidade</a><?php if($weekOffset>-104):?><a href="?view=week&amp;week=<?=$weekOffset-1?>" aria-label="Semana anterior">‹</a><?php endif;?>
<a href="?view=week">Esta semana</a><?php if($weekOffset<104):?><a href="?view=week&amp;week=<?=$weekOffset+1?>" aria-label="Próxima semana">›</a><?php endif;?></nav></div>
<?php $loads=[];foreach($groups as $day=>$items) $loads[$day]=array_sum(array_column($studyDays[$day]??[],'minutes'));
$peak=max(1,max($loads));?>
<div class="week-overview"><div><small>Estudo planejado</small><strong><?=array_sum($loads)?> <em>min</em></strong></div>
<div><small>Entregas na semana</small><strong><?=array_sum(array_map('count',$groups))?></strong></div>
<div class="load-chart" aria-label="Tempo de estudo por dia"><?php foreach($loads as $day=>$minutes): $weekday=$weekdays[(int)(new DateTimeImmutable($day))->format('N')-1];?>
<a href="#day-<?=$day?>" aria-label="<?=h($weekday)?>: <?=$minutes?> minutos"><small><?=$minutes?>m</small><span style="--height:<?=max(3,(int)(32*$minutes/$peak))?>px"></span><b><?=$weekday?></b></a><?php endforeach;?></div></div>
<?php else:?><h2>Quadro de tarefas</h2><?php endif;?>
<div class="<?=$view==='board'?'board':'week'?>">
<?php foreach($groups as $group=>$items):?>
<section id="day-<?=h($group)?>" class="lane <?=$group===$today?'current-day':''?>"><h3><?= $view==='board'?h($group):$weekdays[(int)(new DateTimeImmutable($group))->format('N')-1].' · '.substr(brDate($group),0,5) ?> <small><?=count($items)+count($studyDays[$group]??[])?></small></h3>
<?php foreach(($studyDays[$group]??[]) as $block):?><div class="ticket study-block"><small>Bloco de estudo · <?=(int)$block['minutes']?> min</small><h4><?=h($block['title'])?></h4><a class="edit-link" href="?page=edit&amp;id=<?=$block['id']?>">Editar atividade</a></div><?php endforeach;?>
<?php if(!$items && empty($studyDays[$group])):?><span class="quiet">Sem tarefas</span><?php endif;?>
<?php foreach($items as $item):?><section class="ticket"><small><?=$view==='week'?'Entrega · ':''?><?=h($item['category'])?></small>
<h4><a href="?page=edit&amp;id=<?=$item['id']?>"><?=h($item['title'])?></a></h4>
<p><?=h($item['priority'])?> · <?=brDate($item['due'])?> · <?=h($item['status'])?></p>
<a href="?page=tutor&amp;id=<?=$item['id']?>">Tutor IA</a> · <a class="edit-link" href="?page=edit&amp;id=<?=$item['id']?>">Editar</a>
<?php if($view==='board' && $item['status']!=='Concluída'):?>
<form method="post" action="?id=<?=$item['id']?>"><?php fields('advance');?><input type="hidden" name="view" value="board"><input type="hidden" name="from" value="<?=h($item['status'])?>"><button><?=$item['status']==='Pendente'?'Iniciar':'Concluir'?></button></form>
<?php endif;?></section><?php endforeach;?></section>
<?php endforeach;?></div></section>
