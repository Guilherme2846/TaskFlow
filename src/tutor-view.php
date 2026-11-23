<?php if(!$premium) { require __DIR__.'/tutor-locked.php'; return; } if(!$task): http_response_code(404); ?><h1>Atividade indisponível</h1><a href="?view=list">Voltar à agenda</a>
<?php else: $connected=(bool)modelKey(); ?>
<?php if(empty($embedded)): ?><a href="?view=list">← Minha agenda</a><h1>TutorIA</h1>
<div class="plan-grid"><section class="card stack"><small><?=h($task['category'])?> · entrega <?=brDate($task['due'])?></small>
<h2><?=h($task['title'])?></h2><p style="white-space:pre-wrap"><?=h($task['description']?:'Adicione o enunciado para receber ajuda mais específica.')?></p><a href="?page=edit&amp;id=<?=$id?>">Editar enunciado</a></section>
<?php endif; ?><section class="card stack"><?php if(empty($embedded)): ?><h2>Trabalhar nesta tarefa</h2><?php endif; ?>
<form class="stack" id="tutor-form"><input type="hidden" name="mode" value="tutor"><input type="hidden" name="task" value="<?=$id?>"><input type="hidden" name="token" value="<?=h($_SESSION['token'])?>">
<label>Como quer ajuda?<select name="goal"><option>Ajuda geral</option><option>Montar um roteiro</option><option>Explicar um conceito</option><option>Revisar meu rascunho</option><option>Ensaiar a apresentação</option></select></label>
<label>Dúvida ou rascunho<textarea name="question" maxlength="6000" rows="5" placeholder="Faça uma pergunta sobre a atividade ou cole o que você já fez."></textarea></label>
<?php if($connected):?><small>Ao enviar, o serviço <?=modelProvider()?> recebe o enunciado desta tarefa, sua pergunta e as últimas mensagens desta conversa.</small><button>Perguntar ao TutorIA</button>
<?php else:?><p>A API está sem chave. Prepare a pergunta aqui e cole no ChatGPT para conversar sobre a atividade.</p><button>Preparar pergunta para o ChatGPT</button><?php endif;?>
</form><div id="tutor-dialog" aria-live="polite"></div>
<div id="handoff" hidden><label>Pergunta pronta<textarea id="tutor-prompt" rows="9" readonly style="width:100%"></textarea></label>
<button type="button" id="copy-prompt">Copiar pergunta</button> <a href="https://chatgpt.com/" target="_blank" rel="noopener noreferrer">Abrir ChatGPT ↗</a></div></section><?php if(empty($embedded)): ?></div><?php endif; ?>
<script src="tutor.js?v=3" defer></script><?php endif;?>
