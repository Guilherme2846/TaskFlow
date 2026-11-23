<a href="./">← Minha agenda</a><h1>Planos do TaskFlow</h1>
<div class="plan-grid"><section class="card stack"><h2>Gratuito</h2><strong>R$ 0</strong><p>Organize tarefas ilimitadas por disciplina. Acompanhe prazos na lista, na semana ou no quadro e consulte o assistente local.</p></section>
<section class="card stack"><h2>Plus individual</h2><strong>R$ 9,90 por mês · preço proposto</strong><p>Inclui a agenda gratuita, o registro de sessões de estudo por tarefa e o relatório semanal de minutos por disciplina.</p>
<?php if ($premium):?><p>Teste ativo até <?=brDate($expires)?> (data de encerramento).</p><a class="button" href="?page=study">Abrir painel de estudos</a>
<?php elseif ($expires):?><p>O teste terminou. Você pode consultar os registros feitos durante o período.</p><a href="?page=study">Consultar histórico</a>
<?php else:?><form method="post"><?php fields('trial');?><button>Experimentar Plus por 7 dias</button></form><?php endif;?>
<small>Demonstração acadêmica. Sem cartão ou cobrança automática.</small></section></div>
