<?php
$open=array_values(array_filter($all,fn($t)=>$t['status']!=='Concluída'));
$next=$open[0]??null;
$blocks=rows($db,"SELECT t.id,t.title,t.category,b.minutes FROM study_blocks b JOIN tasks t ON t.id=b.task_id WHERE t.user_id=? AND b.day=? AND t.status!='Concluída' ORDER BY t.due,t.id",[$user,$today]);
?>
<div class="title"><div><span class="eyebrow">Minha agenda · <?=brDate($today)?></span><h1>Hoje</h1></div><a class="button" href="?page=new">+ Nova tarefa</a></div>
<section class="focus-panel"><div><small><?=$next?($next['due']<$today?'PRAZO VENCIDO':'PRÓXIMA ENTREGA'):'MINHA AGENDA'?></small>
<h2><?=$next?h($next['title']):($all?'Todas as tarefas concluídas':'Organize sua primeira entrega')?></h2>
<p><?=$next?h($next['category']).' · '.brDate($next['due']).' · '.h($next['status']):($all?'As entregas concluídas estão na sua agenda.':'Cadastre uma tarefa ou carregue quatro exemplos para experimentar.')?></p></div>
<?php if($next):?><form action="?id=<?=(int)$next['id']?>" method="post"><?php fields('advance');?><input type="hidden" name="from" value="<?=h($next['status'])?>"><button><?=$next['status']==='Pendente'?'Iniciar tarefa':'Concluir tarefa'?></button></form>
<?php elseif(!$all):?><form method="post"><?php fields('examples');?><button>Carregar exemplos</button></form><?php endif;?></section>
<div class="daily-grid"><section class="card"><h2>Estudo reservado para hoje</h2>
<?php if(!$blocks):?><p>Seu dia ainda não tem blocos reservados. Escolha uma entrega para distribuir o esforço até o prazo.</p>
<?php else: foreach($blocks as $b):?><div class="daily-row"><div><small><?=h($b['category'])?></small><h3><?=h($b['title'])?></h3></div><strong><?=(int)$b['minutes']?> min</strong></div><?php endforeach;endif;?>
<a href="?page=schedule">Planejar uma entrega →</a></section>
<section class="card"><h2>Prazos em aberto</h2><?php foreach(['Atrasada'=>'Atrasadas','Vence hoje'=>'Vencem hoje','Próxima'=>'Próximas'] as $k=>$label):?><a class="deadline-row" href="?view=list&amp;filter=<?=urlencode($k)?>"><span><?=h($label)?></span><strong><?=$deadlines[$k]??0?></strong></a><?php endforeach;?></section></div>
