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
if ($q==='' || strlen($q)>2400 || !in_array($mode,['local','cloud'],true)) reply(422,'Confira a pergunta e o modo.');
$user=(int)$_SESSION['user'];session_write_close();
$tasks=rows(database(),"SELECT title,due,priority,status FROM tasks WHERE user_id=? ORDER BY (status='Concluída'),due,CASE priority WHEN 'Alta' THEN 1 WHEN 'Média' THEN 2 ELSE 3 END,id",[$user]);
$today=date('Y-m-d');
if ($mode==='local') reply(200,localAnswer($tasks,$q,$today));
$key=getenv('OPENAI_API_KEY');
if (!$key) reply(503,'A chave da OpenAI ainda não foi configurada.');
try { reply(200,cloudAnswer($tasks,$q,$today,$key)); }
catch (RuntimeException $e) { reply(503,$e->getMessage()); }

