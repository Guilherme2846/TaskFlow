<aside class="tutor-dock" aria-label="TutorIA">
<button type="button" id="tutor-launch" aria-expanded="false" aria-controls="tutor-panel">✦ TutorIA</button>
<section id="tutor-panel" hidden aria-labelledby="panel-title">
<div class="dock-heading"><strong id="panel-title">TutorIA</strong><button type="button" id="tutor-close" aria-label="Fechar conversa">×</button></div>
<?php if($all): ?>
<label class="dock-picker">Atividade<select id="panel-task"><?php foreach($all as $entry): ?><option value="<?=(int)$entry['id']?>" <?=$id==(int)$entry['id']?'selected':''?>><?=h($entry['title'])?></option><?php endforeach; ?></select></label>
<iframe title="Conversa sobre a atividade" id="panel-chat"></iframe>
<?php else: ?><p>Cadastre uma atividade para conversar sobre ela.</p><a href="?page=new">Criar atividade</a><?php endif; ?>
</section></aside><script src="tutor-panel.js" defer></script>
