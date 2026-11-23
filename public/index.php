<?php
declare(strict_types=1);
require dirname(__DIR__).'/src/app.php';
date_default_timezone_set('America/Sao_Paulo');
session_name('taskflow');
session_start(['cookie_httponly'=>true,'cookie_samesite'=>'Lax','use_strict_mode'=>true]);
$_SESSION['token'] ??= bin2hex(random_bytes(32));
$db=database();
$user=(int)($_SESSION['user']??0);
$page=$_GET['page']??'';
$id=(int)($_GET['id']??0);
$statuses=['Pendente','Em andamento','Concluída'];
$priorities=['Alta','Média','Baixa'];
$today=date('Y-m-d');
require dirname(__DIR__).'/src/study.php';
require dirname(__DIR__).'/src/schedule.php';
$expires=$user?trial($db,$user):null;
$premium=$expires!==null && $expires>$today;
$error='';
$task=$id && $user ? (rows($db,'SELECT * FROM tasks WHERE id=? AND user_id=?',[$id,$user])[0]??null) : null;
if ($_SERVER['REQUEST_METHOD']==='POST') {
    try {
        guard(hash_equals($_SESSION['token'],field('token')),'Atualize a página e tente novamente.');
        $action=field('action');
        if (!$user) {
            guard(in_array($action,['signup','login'],true),'Entre na sua conta.');
            account($db,$action);
            redirect();
        }
        if ($action==='logout') { $_SESSION=[]; session_destroy(); redirect(); }
        if ($action==='trial') {
            $ends=(new DateTimeImmutable($today))->modify('+7 days')->format('Y-m-d');
            $db->prepare('INSERT INTO trials(user_id,expires) VALUES(?,?) ON CONFLICT(user_id) DO NOTHING')->execute([$user,$ends]);
            redirect('?page=study');
        }
        if ($action==='study') {
            recordStudy($db,$user,$premium);
            $_SESSION['notice']='Sessão registrada.';
            $week=(new DateTimeImmutable(field('studied')))->modify('monday this week');
            $current=(new DateTimeImmutable($today))->modify('monday this week');
            $offset=intdiv((int)$current->diff($week)->format('%r%a'),7);
            redirect('?page=study&week='.$offset);
        }
        if ($action==='examples') { loadExamples($db,$user,$today); redirect(); }
        if ($action==='schedule') {
            saveSchedule($db,$user,$today); $_SESSION['notice']='Cronograma salvo na sua semana.'; redirect('?view=week');
        }
        if ($action==='preview') { $preview=buildSchedule($db,$user,$today); }
        elseif ($action==='create' || $action==='edit') {
            $title=field('title'); $description=field('description'); $category=field('category');
            guard($title!=='' && strlen($title)<=480,'Informe um título de até 120 caracteres.');
            guard(strlen($description)<=8000 && $category!=='' && strlen($category)<=240,'Confira a descrição e a disciplina.');
            $values=[$title,$description,$category,day(field('due')),field('priority'),field('status')];
            guard(in_array($values[4],$priorities,true) && in_array($values[5],$statuses,true),'Selecione prioridade e status válidos.');
            if ($action==='edit') {
                guard((bool)$task,'Tarefa indisponível.');
                $db->prepare('UPDATE tasks SET title=?,description=?,category=?,due=?,priority=?,status=? WHERE id=? AND user_id=?')->execute([...$values,$id,$user]);
            } else {
                $db->prepare('INSERT INTO tasks(title,description,category,due,priority,status,user_id) VALUES(?,?,?,?,?,?,?)')->execute([...$values,$user]);
            }
        } elseif ($action==='delete') {
            guard((bool)$task,'Tarefa indisponível.');
            $db->prepare('DELETE FROM tasks WHERE id=? AND user_id=?')->execute([$id,$user]);
        } elseif ($action==='advance') {
            guard((bool)$task,'Tarefa indisponível.');
            $from=field('from'); guard(in_array($from,array_slice($statuses,0,2),true),'Status inválido.');
            $db->prepare('UPDATE tasks SET status=? WHERE id=? AND user_id=? AND status=?')->execute([$from==='Pendente'?'Em andamento':'Concluída',$id,$user,$from]);
        } else { throw new DomainException('Operação inválida.'); }
        if ($action!=='preview') { $_SESSION['notice']='Agenda atualizada.'; redirect(field('view')==='board'?'?view=board':''); }
    } catch (DomainException $e) { $error=$e->getMessage(); }
}
function fields(string $action): void { ?>
<input type="hidden" name="token" value="<?=h($_SESSION['token'])?>"><input type="hidden" name="action" value="<?=$action?>">
<?php }
function options(array $items,string $selected): void {foreach($items as $i) { ?>
<option <?=$i===$selected?'selected':''?>><?=h($i)?></option><?php }}
function urgency(array $t,string $today): string {
    if($t['status']==='Concluída') return 'Concluída';
    return $t['due']<$today?'Atrasada':($t['due']===$today?'Vence hoje':'Próxima');
}
$all=$user?rows($db,"SELECT * FROM tasks WHERE user_id=? ORDER BY (status='Concluída'),due,CASE priority WHEN 'Alta' THEN 1 WHEN 'Média' THEN 2 ELSE 3 END,id",[$user]):[];
$counts=array_count_values(array_column($all,'status'));
$deadlines=array_count_values(array_map(fn($t)=>urgency($t,$today),$all));
$filter=(string)($_GET['filter']??''); $query=trim((string)($_GET['q']??''));
$visible=array_filter($all,fn($t)=>(!$filter || in_array($filter,[$t['status'],urgency($t,$today)],true)) && (!$query || stripos($t['title'].' '.$t['category'],$query)!==false));
$name=$user?(rows($db,'SELECT name FROM users WHERE id=?',[$user])[0]['name']??''):'';
$view=in_array($_GET['view']??'', ['board','week'],true)?$_GET['view']:'list';
$range=$page==='study'?520000:104;
$weekOffset=max(-$range,min($range,(int)($_GET['week']??0)));
$start=(new DateTimeImmutable($today))->modify('monday this week')->modify(sprintf('%+d weeks',$weekOffset));
$notice=$_SESSION['notice']??'';unset($_SESSION['notice']);
$form=$error && isset($_POST['title'])?$_POST:($task??[]);
?>
<!doctype html><html lang="pt-BR"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>TaskFlow · Agenda acadêmica</title><link rel="stylesheet" href="style.css"><link rel="stylesheet" href="agenda.css"></head><body>
<header><div class="wrap bar"><a class="brand" href="./"><i>✓</i>TaskFlow</a><?php if($user):?><div class="account"><a class="top-link" href="?page=schedule">Planejar</a><a class="top-link" href="?page=plans"><?=$premium?'Meu Plus':'Planos'?></a><a class="top-link" href="?page=study">Estudos</a><span><?=h($name)?></span><form method="post"><?php fields('logout');?><button class="light">Sair</button></form></div><?php endif;?></div></header>
<main class="wrap"><?php if($error):?><p class="error" role="alert"><?=h($error)?></p><?php endif;if($notice):?><p class="notice" role="status"><?=h($notice)?></p><?php endif;?>
<?php if(!$user): $signup=$page==='signup';?>
<section class="login"><div class="intro"><span class="eyebrow">Agenda acadêmica</span><h1>O que vence primeiro?</h1><p>Organize trabalhos, provas e leituras por disciplina. Acompanhe suas entregas desde o primeiro rascunho até a conclusão.</p></div><form class="card stack" method="post"><?php fields($signup?'signup':'login');?><h2><?=$signup?'Criar minha conta':'Acessar minha agenda'?></h2>
<?php if($signup):?><label>Nome<input name="name" required autocomplete="name"></label><?php endif;?>
<label>E-mail<input type="email" name="email" required autocomplete="email"></label><label>Senha <?=$signup?'(mínimo de 8 caracteres)':''?><input type="password" name="password" required autocomplete="<?=$signup?'new-password':'current-password'?>"></label><button><?=$signup?'Criar conta':'Entrar'?></button><a href="<?=$signup?'./':'?page=signup'?>"><?=$signup?'Já tenho conta':'Criar uma conta'?></a></form></section>
<?php elseif($page==='schedule'): require dirname(__DIR__).'/src/schedule-view.php'; elseif($page==='plans'): require dirname(__DIR__).'/src/plans-view.php'; elseif($page==='study'): require dirname(__DIR__).'/src/study-view.php'; elseif(in_array($page,['edit','delete'],true) && !$task): http_response_code(404);?><h1>Tarefa indisponível</h1><a href="./">Voltar à agenda</a>
<?php elseif($page==='delete'):?><section class="card narrow"><h1>Excluir tarefa?</h1><p><?=h($task['title'])?></p><form method="post"><?php fields('delete');?><button>Confirmar exclusão</button> <a href="./">Cancelar</a></form></section>
<?php elseif($page==='new'||$page==='edit'):?><a href="./">← Minha agenda</a><h1><?=$page==='edit'?'Editar tarefa':'Nova tarefa'?></h1><form class="card stack narrow" method="post"><?php fields($page==='edit'?'edit':'create');?>
<label>Título<input name="title" maxlength="120" required value="<?=h($form['title']??'')?>"></label><label>Disciplina ou categoria<input name="category" maxlength="60" required value="<?=h($form['category']??'')?>"></label><label>Descrição<textarea name="description" maxlength="2000"><?=h($form['description']??'')?></textarea></label>
<label>Prazo<input type="date" required name="due" value="<?=h($form['due']??$today)?>"></label><div class="pair"><label>Prioridade<select name="priority"><?php options($priorities,$form['priority']??'Média');?></select></label><label>Status<select name="status"><?php options($statuses,$form['status']??'Pendente');?></select></label></div><button>Salvar tarefa</button></form>
<?php else:?><div class="title"><div><span class="eyebrow"><?=brDate($today)?></span><h1>Minha agenda</h1></div><a class="button" href="?page=new">+ Nova tarefa</a></div>
<?php $next=array_values(array_filter($all,fn($t)=>$t['status']!=='Concluída'))[0]??null;?>
<?php if(!$query && !$filter):?><section class="focus-panel"><div><small><?=$next?'PRÓXIMA ENTREGA':'MINHA AGENDA'?></small><h2><?=$next?h($next['title']):($all?'Todas as entregas concluídas':'Monte sua primeira semana')?></h2><p><?=$next?h($next['category']).' · '.brDate($next['due']).' · '.urgency($next,$today):($all?'Consulte o histórico no quadro.':'Crie uma tarefa ou experimente uma semana com quatro atividades de exemplo.')?></p></div>
<?php if(!$all):?><form method="post"><?php fields('examples');?><button>Carregar exemplos</button></form><?php else:?><a class="button" href="?page=schedule">Planejar uma entrega →</a><?php endif;?></section><?php endif;?>
<div class="stats"><div><small>Total de tarefas</small><strong><?=count($all)?></strong></div><?php foreach($statuses as $s):?><div><small><?=$s?></small><strong><?=$counts[$s]??0?></strong></div><?php endforeach;?></div>
<div class="workspace"><section class="planner"><nav class="views" aria-label="Visualização da agenda"><?php foreach(['list'=>'Lista','week'=>'Semana','board'=>'Quadro'] as $key=>$caption):?><a href="?view=<?=$key?>" aria-current="<?=$view===$key?'page':'false'?>"><?=$caption?></a><?php endforeach;?></nav>
<?php if($view==='list'):?><section class="card"><h2>Entregas por prazo</h2><div class="chips"><?php foreach(['Atrasada'=>'Atrasadas','Vence hoje'=>'Vencem hoje','Próxima'=>'Próximas'] as $k=>$l):?><a href="?filter=<?=urlencode($k)?>"><?=$l?> · <?=$deadlines[$k]??0?></a><?php endforeach;?></div>
<form class="filters" method="get"><label>Pesquisar<input name="q" placeholder="Título ou disciplina" value="<?=h($query)?>"></label><label>Exibir<select name="filter"><option value="">Todas as tarefas</option><?php options([...$statuses,'Atrasada','Vence hoje','Próxima'],$filter);?></select></label><button>Filtrar</button><a href="./">Limpar</a></form>
<?php if(!$visible):?><p><?=$all?'Nenhuma tarefa corresponde ao filtro.':'Sua agenda está vazia. Cadastre sua primeira entrega em “Nova tarefa”.'?></p><?php endif;foreach($visible as $t):?><article><div><small><?=h($t['category'])?> · Prioridade <?=h($t['priority'])?></small><h3><a href="?page=edit&amp;id=<?=$t['id']?>"><?=h($t['title'])?></a></h3><p><?=brDate($t['due'])?> · <?=urgency($t,$today)?><?=$t['status']==='Concluída'?'':' · '.$t['status']?></p></div><div class="actions"><?php if($t['status']!=='Concluída'):?><form method="post" action="?id=<?=$t['id']?>"><?php fields('advance');?><input type="hidden" name="from" value="<?=$t['status']?>"><button><?=$t['status']==='Pendente'?'Iniciar':'Concluir'?></button></form><?php endif;?><a href="?page=delete&amp;id=<?=$t['id']?>">Excluir</a></div></article><?php endforeach;?></section><?php else: require dirname(__DIR__).'/src/planner.php'; endif;?></section><aside><?php require dirname(__DIR__).'/src/chat-view.php';?></aside></div><?php endif;?></main></body></html>
