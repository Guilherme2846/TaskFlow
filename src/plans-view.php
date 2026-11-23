<a href="./">← Minha agenda</a><h1>Planos do TaskFlow</h1>
<div class="plan-grid"><section class="card stack"><h2>Gratuito</h2><strong>R$ 0</strong>
<p>Organize tarefas ilimitadas, distribua o tempo disponível e consulte o resumo local da agenda.</p>
<a href="?view=week">Abrir minha agenda</a></section>
<section class="card stack"><h2>Plus individual</h2><strong>R$ 9,90 por mês · preço proposto</strong>
<p>Inclui todo o gratuito e o TutorIA para conversar sobre suas atividades. Registre o tempo que realmente estudou e acompanhe o progresso por disciplina.</p>
<?php if ($premium):?><p>Teste ativo até <?=brDate($expires)?> (data de encerramento).</p><a class="button" href="?page=study">Abrir painel de estudos</a>
<?php elseif ($expires):?><p>O teste terminou. Você pode consultar, editar e remover as sessões já registradas.</p><a href="?page=study">Consultar histórico</a>
<?php else:?><form method="post"><?php fields('trial');?><button>Experimentar Plus por 7 dias</button></form><?php endif;?>
<small>O teste dura 7 dias e pode ser ativado uma vez por conta. A versão acadêmica não solicita cartão nem realiza cobranças.</small></section></div>
<section class="card plan-comparison"><h2>Comparar recursos</h2>
<table aria-label="Recursos incluídos por plano"><thead><tr><th scope="col">Recurso</th><th scope="col">Gratuito</th><th scope="col">Plus</th></tr></thead><tbody>
<?php foreach ([
    'Tarefas ilimitadas em lista, semana e quadro'=>true,
    'Disponibilidade semanal e ajustes por data'=>true,
    'Planejamento por prioridade e avisos de prazo'=>true,
    'Resumo local da agenda'=>true,
    'TutorIA por atividade e consultas à IA online'=>false,
    'Registrar novas sessões de estudo por tarefa'=>false,
    'Descontar o tempo estudado da estimativa ao registrar a sessão'=>false,
    'Acompanhar o tempo por disciplina no relatório semanal'=>false
] as $feature=>$free): ?><tr><th scope="row"><?=h($feature)?></th><td><?=$free?'Incluído':'—'?></td><td>Incluído</td></tr><?php endforeach; ?>
</tbody></table><p><small>O TutorIA responde dentro do TaskFlow no Plus quando a API de IA está configurada. Após o teste, o histórico das sessões já registradas continua disponível.</small></p></section>
