<?php
$from=$start->format('Y-m-d');$until=$start->modify('+7 days')->format('Y-m-d');
$logs=rows($db,'SELECT l.id,l.title,l.category,l.studied,l.minutes,e.task_id FROM study_log l LEFT JOIN study_effects e ON e.log_id=l.id WHERE user_id=? AND studied>=? AND studied<? ORDER BY studied DESC,id DESC',[$user,$from,$until]);
$totals=[];foreach($logs as $log) $totals[$log['category']]=($totals[$log['category']]??0)+(int)$log['minutes'];
arsort($totals);
?>
<a href="./">← Minha agenda</a><h1>Painel de estudos</h1>
<?php if(!$expires):?><section class="card"><h2>Quanto tempo foi para cada disciplina?</h2><p>Registre os minutos dedicados a cada tarefa e veja como distribuiu a semana.</p><a class="button" href="?page=plans">Conhecer o Plus</a></section>
<?php else:?><div class="plan-grid">
<section class="card"><div class="week-head"><h2>Resumo semanal</h2><nav><a href="?page=study&amp;week=<?=$weekOffset-1?>" aria-label="Semana anterior">‹</a><a href="?page=study">Esta semana</a><a href="?page=study&amp;week=<?=$weekOffset+1?>" aria-label="Próxima semana">›</a></nav></div>
<p><?=brDate($from)?> a <?=brDate($start->modify('+6 days')->format('Y-m-d'))?></p>
<h3><?=array_sum($totals)?> minutos · <?=count($logs)?> <?=count($logs)===1?'sessão':'sessões'?></h3>
<?php foreach($totals as $category=>$minutes):?><p><?=h((string)$category)?> · <?=$minutes?> min<br><progress max="<?=array_sum($totals)?>" value="<?=$minutes?>"></progress></p><?php endforeach;?>
<?php if(!$logs):?><p>Nenhuma sessão registrada nesta semana.</p><?php else:?><h3>Histórico da semana</h3><?php foreach($logs as $log):?><p><?=brDate($log['studied'])?> · <?=h($log['title'])?> · <?=(int)$log['minutes']?> min</p><details><summary>Editar ou remover</summary>
<?php if(!$log['task_id']):?><p>Esta sessão não tem vínculo com uma tarefa atual. A correção atualiza o histórico e o tempo disponível. Se necessário, revise a estimativa da atividade na agenda.</p><?php endif;?>
<form method="post" class="stack"><?php fields('edit_study');?><input type="hidden" name="session_id" value="<?=$log['id']?>">
<label>Data do estudo<input type="date" name="studied" max="<?=$today?>" value="<?=$log['studied']?>" required></label>
<label>Minutos estudados<input type="number" name="minutes" min="1" max="480" value="<?=$log['minutes']?>" required></label><button>Salvar alteração</button></form>
<details><summary>Remover sessão</summary><form method="post"><?php fields('remove_study');?><input type="hidden" name="session_id" value="<?=$log['id']?>"><button>Confirmar remoção</button></form></details></details><?php endforeach;endif;?></section>
<section class="card stack"><h2>Registrar estudo</h2>
<?php if(!$premium):?><p>Seu período de teste terminou.</p><?php elseif(!$all):?><a href="?page=new">Cadastrar a primeira tarefa</a><?php else:?><form method="post" class="stack"><?php fields('study');?><input type="hidden" name="request" value="<?=bin2hex(random_bytes(16))?>">
<label>Tarefa<select name="task"><?php foreach($all as $item):?><option value="<?=(int)$item['id']?>"><?=h($item['category'].' · '.$item['title'])?></option><?php endforeach;?></select></label>
<label>Quando estudou?<input type="date" name="studied" required max="<?=$today?>" value="<?=$today?>"></label><label>Tempo dedicado (minutos)<input type="number" name="minutes" min="1" max="480" value="25" required></label><button>Salvar sessão</button></form><?php endif;?></section></div><?php endif;?>
