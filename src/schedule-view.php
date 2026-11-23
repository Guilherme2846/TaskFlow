<a href="./">← Minha agenda</a><h1>Planejar uma entrega</h1>
<?php $eligible=array_filter($all,fn($t)=>$t['status']!=='Concluída' && $t['due']>=$today); ?>
<div class="plan-grid"><section class="card stack"><h2>Tempo disponível</h2>
<?php if(!$eligible):?><p>Cadastre uma entrega em aberto com prazo para hoje ou depois.</p><a href="?page=new">Nova tarefa</a><?php else:?>
<form method="post" class="stack"><?php fields('preview');?><label>Entrega<select name="task"><?php foreach($eligible as $t):?><option value="<?=$t['id']?>" <?=(int)field('task')===(int)$t['id']?'selected':''?>><?=h($t['title'])?> · <?=brDate($t['due'])?></option><?php endforeach;?></select></label>
<label>Quanto tempo o trabalho exige? (min)<input name="effort" type="number" min="1" max="1440" required value="<?=h(field('effort')?:'120')?>"></label>
<label>Quanto você pode estudar por dia? (min)<input name="capacity" type="number" min="1" max="480" required value="<?=h(field('capacity')?:'40')?>"></label>
<label>Dias disponíveis<select name="weekdays"><option value="all">Todos os dias</option><option value="yes" <?=field('weekdays')==='yes'?'selected':''?>>Segunda a sexta</option></select></label><button>Simular cronograma</button></form><?php endif;?></section>
<section class="card stack"><h2>Prévia do cronograma</h2><?php if(empty($preview)):?><p>Informe o esforço e a disponibilidade. O cálculo desconta os minutos já reservados para outras entregas.</p><?php else:?>
<strong><?=h($preview['task']['title'])?> · <?=$preview['effort']?> min</strong>
<?php foreach($preview['blocks'] as $date=>$minutes):?><div class="block-row"><span><?=brDate($date)?></span><strong><?=$minutes?> min</strong></div><?php endforeach;?>
<?php if($preview['remaining']):?><p class="error">Faltam <?=$preview['remaining']?> minutos até o prazo. Aumente a disponibilidade ou revise o esforço e simule novamente.</p><?php else:?><form method="post"><?php fields('schedule');foreach(['task','effort','capacity','weekdays'] as $key):?><input type="hidden" name="<?=$key?>" value="<?=h(field($key))?>"><?php endforeach;?><input type="hidden" name="preview" value="<?=hash('sha256',serialize($preview))?>"><p>Salvar substitui os blocos futuros desta entrega.</p><button>Salvar na semana</button></form><?php endif;endif;?></section></div>
