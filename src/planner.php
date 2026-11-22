<?php
$groups=[];
if ($view==='board') {
    foreach ($statuses as $status) $groups[$status]=[];
    foreach ($all as $item) $groups[$item['status']][]=$item;
} else {
    $weekdays=['Seg','Ter','Qua','Qui','Sex','Sáb','Dom'];
    for ($i=0;$i<7;$i++) $groups[$start->modify("+$i days")->format('Y-m-d')]=[];
    foreach ($all as $item) if(isset($groups[$item['due']])) $groups[$item['due']][]=$item;
}
?>
<section class="card">
<?php if($view==='week'):?>
<div class="week-head"><h2><?= $start->format('d/m') ?> a <?= $start->modify('+6 days')->format('d/m/Y') ?></h2>
<nav aria-label="Navegação entre semanas"><?php if($weekOffset>-104):?><a href="?view=week&amp;week=<?=$weekOffset-1?>" aria-label="Semana anterior">‹</a><?php endif;?>
<a href="?view=week">Esta semana</a><?php if($weekOffset<104):?><a href="?view=week&amp;week=<?=$weekOffset+1?>" aria-label="Próxima semana">›</a><?php endif;?></nav></div>
<?php else:?><h2>Quadro de tarefas</h2><?php endif;?>
<div class="<?=$view==='board'?'board':'week'?>">
<?php foreach($groups as $group=>$items):?>
<section class="lane <?=$group===$today?'current-day':''?>"><h3><?= $view==='board'?h($group):$weekdays[(int)(new DateTimeImmutable($group))->format('N')-1].' · '.substr(brDate($group),0,5) ?> <small><?=count($items)?></small></h3>
<?php if(!$items):?><span class="quiet">Sem tarefas</span><?php endif;?>
<?php foreach($items as $item):?><section class="ticket"><small><?=h($item['category'])?></small>
<h4><a href="?page=edit&amp;id=<?=$item['id']?>"><?=h($item['title'])?></a></h4>
<p><?=h($item['priority'])?> · <?=brDate($item['due'])?> · <?=h($item['status'])?></p>
<?php if($view==='board' && $item['status']!=='Concluída'):?>
<form method="post" action="?id=<?=$item['id']?>"><?php fields('advance');?><input type="hidden" name="view" value="board"><input type="hidden" name="from" value="<?=h($item['status'])?>"><button><?=$item['status']==='Pendente'?'Iniciar':'Concluir'?></button></form>
<?php endif;?></section><?php endforeach;?></section>
<?php endforeach;?></div></section>
