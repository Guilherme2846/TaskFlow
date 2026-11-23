<?php if(!$premium): require __DIR__.'/tutor-locked.php'; elseif($all):?><form class="card stack" method="get"><h2>Ajuda com uma atividade</h2><input type="hidden" name="page" value="tutor"><label>Atividade<select name="id"><?php foreach($all as $item):?><option value="<?=$item['id']?>"><?=h($item['title'])?></option><?php endforeach;?></select></label><button>Abrir TutorIA</button></form><?php endif;?>
<section class="card"  style="margin-bottom:18px"><span class="eyebrow">TaskFlow IA</span><h2>Assistente de estudos</h2>
<form class="stack" id="study-chat"><input type="hidden" name="token" value="<?=h($_SESSION['token'])?>">
<div class="prompts"><?php foreach(['Por onde começar hoje?','O que vence amanhã?','Próximos sete dias','Quais tarefas estão atrasadas?'] as $prompt):?><button type="button" data-prompt="<?=h($prompt)?>"><?=h($prompt)?></button><?php endforeach;?></div>
<label>Sua pergunta<input required maxlength="600" name="question" placeholder="Por onde começar hoje?"></label>
<details class="filter-fold"><summary>Disciplina e modo de resposta</summary><label>Como consultar<select name="mode"><option value="local">Resumo local · sem internet</option><?php if($premium):?><option value="cloud" <?=modelKey()?'':'disabled'?>><?=modelProvider()?> · <?=modelKey()?'enviar pergunta e até 50 tarefas':'chave não configurada'?></option><?php endif;?></select></label>
<label>Disciplina<select name="category"><option value="">Todas as disciplinas</option><?php $categories=array_unique(array_column($all,'category'));sort($categories);options($categories,'');?></select></label>
</details><button type="submit">Consultar agenda</button></form><p id="chat-answer" style="white-space:pre-line" aria-live="polite"></p></section>
<script src="chat.js" defer></script>
