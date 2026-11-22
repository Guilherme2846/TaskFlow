<section class="card" style="margin-bottom:18px"><span class="eyebrow">TaskFlow IA</span><h2>Assistente de estudos</h2>
<form class="stack" id="study-chat"><input type="hidden" name="token" value="<?=h($_SESSION['token'])?>">
<label>Como consultar<select name="mode"><option value="local">IA local · sem internet</option><option value="cloud" <?=getenv('OPENAI_API_KEY')?'':'disabled'?>>OpenAI · <?=getenv('OPENAI_API_KEY')?'enviar pergunta e até 50 tarefas':'chave não configurada'?></option></select></label>
<label>Disciplina<select name="category"><option value="">Todas as disciplinas</option><?php $categories=array_unique(array_column($all,'category'));sort($categories);options($categories,'');?></select></label>
<div class="prompts"><?php foreach(['Por onde começar hoje?','O que vence amanhã?','Próximos sete dias','Quais tarefas estão atrasadas?'] as $prompt):?><button type="button" data-prompt="<?=h($prompt)?>"><?=h($prompt)?></button><?php endforeach;?></div>
<label>Sua pergunta<input required maxlength="600" name="question" placeholder="Por onde começar hoje?"></label>
<button type="submit">Consultar agenda</button></form><p id="chat-answer" style="white-space:pre-line" aria-live="polite"></p></section>
<script src="chat.js" defer></script>
