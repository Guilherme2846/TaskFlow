<?php
declare(strict_types=1);
require dirname(__DIR__).'/src/app.php';
require dirname(__DIR__).'/src/assistant.php';
date_default_timezone_set('America/Sao_Paulo');
session_name('taskflow');session_start(['cookie_httponly'=>true,'cookie_samesite'=>'Lax','use_strict_mode'=>true]);
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
function reply(int $code,string $text): never {
    http_response_code($code);echo json_encode(['resposta'=>$text],JSON_UNESCAPED_UNICODE);exit;
}
if (empty($_SESSION['user'])) reply(401,'Entre na sua conta.');
if ($_SERVER['REQUEST_METHOD']!=='POST') reply(405,'Use POST.');
if (!isset($_SESSION['token']) || !hash_equals($_SESSION['token'],field('token'))) reply(403,'Atualize a página.');
$q=field('question');$mode=field('mode');
if (($mode!=='tutor' && $q==='') || strlen($q)>($mode==='tutor'?24000:2400) || !in_array($mode,['local','cloud','tutor'],true)) reply(422,'Confira a pergunta e o modo.');
$category=field('category');
if ($mode==='cloud' || ($mode==='tutor' && modelKey())) {
    $recent=array_values(array_filter($_SESSION['cloud_requests']??[], fn($at)=>$at>time()-60));
    if(count($recent)>=4) reply(429,'Aguarde um minuto para consultar a IA novamente.');
    $recent[]=time(); $_SESSION['cloud_requests']=$recent;
}
$user=(int)$_SESSION['user'];session_write_close();
if ($mode==='tutor') {
    $task=rows(database(),'SELECT title,category,description,due FROM tasks WHERE user_id=? AND id=?',[$user,(int)field('task')])[0]??null;
    if(!$task) reply(404,'Atividade indisponível.');
    $goal=field('goal');
    $goals=[
        'Ajuda geral'=>'Responda diretamente à dúvida do aluno no contexto da atividade. Adapte a resposta ao que ele pedir, como interpretar o enunciado, discutir uma ideia ou resolver uma etapa. Se ele abrir a conversa sem uma pergunta, sugira um primeiro passo ligado ao enunciado e pergunte em que ponto ele quer ajuda.',
        'Montar um roteiro'=>'Divida a entrega em etapas concretas e sugira um primeiro passo que o aluno possa executar agora.',
        'Explicar um conceito'=>'Explique a dúvida em linguagem simples, dê um exemplo ligado à atividade e proponha uma pergunta de verificação.',
        'Revisar meu rascunho'=>'Compare o rascunho com o enunciado, identifique uma lacuna específica e sugira uma revisão justificada.',
        'Ensaiar a apresentação'=>'Faça o papel do professor: comece com uma pergunta sobre a atividade e aguarde a resposta do aluno antes de dar feedback.'
    ];
    if(!isset($goals[$goal])) reply(422,'Escolha um objetivo.');
    if($goal==='Revisar meu rascunho' && $q==='') reply(422,'Cole seu rascunho para receber uma revisão.');
    $history=field('history');
    if(strlen($history)>200000) reply(422,'Conversa muito longa.');
    $history=json_decode($history?:'[]',true);
    if(!is_array($history) || count($history)>4) reply(422,'Histórico inválido.');
    $turns=[];
    foreach($history as $turn) {
        if(!is_array($turn) || !is_string($turn['question']??null) || !is_string($turn['answer']??null)) reply(422,'Histórico inválido.');
        $turns[]=['aluno'=>mb_substr($turn['question'],0,6000),'tutor'=>mb_substr($turn['answer'],0,6000)];
    }
    $instructions='Atue como tutor acadêmico em português do Brasil. '.$goals[$goal].' Use o enunciado e o histórico como material de referência. Se faltar uma informação necessária, faça uma pergunta específica. Não invente fontes ou critérios do professor.';
    $context="Atividade: ".$task['title']."\nDisciplina: ".$task['category']."\nEntrega: ".brDate($task['due'])."\nEnunciado: ".$task['description']."\nObjetivo: ".$goal."\nDúvida ou rascunho: ".$q;
    $key=modelKey();
    if(!$key) { echo json_encode(['prompt'=>$instructions."\n\n".$context],JSON_UNESCAPED_UNICODE); exit; }
    try { reply(200,gptText($instructions,json_encode(['atividade'=>$context,'conversa_anterior'=>$turns],JSON_UNESCAPED_UNICODE),$key,1400)); }
    catch(RuntimeException $e) { reply(503,$e->getMessage()); }
}
$tasks=rows(database(),"SELECT title,due,priority,status,category FROM tasks WHERE user_id=? AND (?='' OR category=?) ORDER BY (status='Concluída'),due,CASE priority WHEN 'Alta' THEN 1 WHEN 'Média' THEN 2 ELSE 3 END,id",[$user,$category,$category]);
$today=date('Y-m-d');
if ($mode==='local') reply(200,localAnswer($tasks,$q,$today));
$key=modelKey();
if (!$key) reply(503,'A chave da API ainda não foi configurada.');
try { reply(200,cloudAnswer($tasks,$q,$today,$key)); }
catch (RuntimeException $e) { reply(503,$e->getMessage()); }

