<?php
declare(strict_types=1);

function database(): PDO {
    $dir = dirname(__DIR__) . '/data';
    if (!is_dir($dir) && !mkdir($dir, 0700, true) && !is_dir($dir)) {
        throw new RuntimeException('Falha ao criar a pasta de dados.');
    }
    $db = new PDO('sqlite:' . $dir . '/taskflow.sqlite', null, null, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
    $db->exec('PRAGMA foreign_keys = ON');
    $db->exec('PRAGMA busy_timeout = 4000');
    $db->exec('CREATE TABLE IF NOT EXISTS users (
        id INTEGER PRIMARY KEY, name TEXT NOT NULL, email TEXT NOT NULL UNIQUE, hash TEXT NOT NULL
    );
    CREATE TABLE IF NOT EXISTS tasks (
        id INTEGER PRIMARY KEY, user_id INTEGER NOT NULL REFERENCES users(id),
        title TEXT NOT NULL, description TEXT NOT NULL, category TEXT NOT NULL,
        due TEXT NOT NULL, priority TEXT NOT NULL, status TEXT NOT NULL
    )');
    $db->exec('CREATE TABLE IF NOT EXISTS trials (
        user_id INTEGER PRIMARY KEY REFERENCES users(id), expires TEXT NOT NULL
    );
    CREATE TABLE IF NOT EXISTS study_log (
        id INTEGER PRIMARY KEY, user_id INTEGER NOT NULL REFERENCES users(id), request TEXT NOT NULL,
        title TEXT NOT NULL, category TEXT NOT NULL, studied TEXT NOT NULL, minutes INTEGER NOT NULL CHECK(minutes BETWEEN 1 AND 480),
        UNIQUE(user_id,request)
    )');
    $db->exec('CREATE TABLE IF NOT EXISTS study_blocks (
        task_id INTEGER NOT NULL REFERENCES tasks(id) ON DELETE CASCADE,
        day TEXT NOT NULL, minutes INTEGER NOT NULL CHECK(minutes>0), PRIMARY KEY(task_id,day)
    )');
    $db->exec("CREATE TABLE IF NOT EXISTS availability(user_id INTEGER PRIMARY KEY REFERENCES users(id),weekday INTEGER NOT NULL,saturday INTEGER NOT NULL,sunday INTEGER NOT NULL,last_day TEXT NOT NULL);
    CREATE TABLE IF NOT EXISTS day_limits(user_id INTEGER NOT NULL REFERENCES users(id),day TEXT NOT NULL,minutes INTEGER NOT NULL,PRIMARY KEY(user_id,day));
    CREATE TABLE IF NOT EXISTS estimates(task_id INTEGER PRIMARY KEY REFERENCES tasks(id) ON DELETE CASCADE,minutes INTEGER NOT NULL,warning TEXT NOT NULL DEFAULT '')");
    return $db;
}

function rows(PDO $db, string $sql, array $params = []): array {
    $q = $db->prepare($sql);
    $q->execute($params);
    return $q->fetchAll();
}

function field(string $name): string {
    $value = $_POST[$name] ?? '';
    return is_string($value) ? trim($value) : '';
}

function guard(bool $condition, string $message): void {
    if (!$condition) throw new DomainException($message);
}

function day(string $value): string {
    $d = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
    guard($d && $d->format('Y-m-d') === $value,
          'Informe uma data válida para o prazo.');
    return $value;
}

function account(PDO $db, string $action): void {
    $email = strtolower(field('email'));
    $password = $_POST['password'] ?? '';
    guard(filter_var($email, FILTER_VALIDATE_EMAIL) !== false && is_string($password), 'Confira os dados de acesso.');
    if ($action === 'signup') {
        $name = field('name');
        guard($name !== '' && strlen($name) <= 120, 'Informe o seu nome (até 120 bytes).');
        guard(strlen($password) >= 8 && strlen($password) <= 72, 'Use uma senha entre 8 e 72 bytes.');
        $q = $db->prepare('INSERT INTO users(name,email,hash) VALUES (?,?,?) ON CONFLICT(email) DO NOTHING');
        $q->execute([$name,$email,password_hash($password, PASSWORD_DEFAULT)]);
        guard($q->rowCount() === 1, 'Este e-mail já possui uma conta.');
        $id = (int)$db->lastInsertId();
    } else {
        $user = rows($db,'SELECT id, hash FROM users WHERE email = ?',[$email])[0] ?? null;
        guard($user && password_verify($password, $user['hash']), 'E-mail ou senha incorretos.');
        $id = (int)$user['id'];
    }
    session_regenerate_id(true);
    $_SESSION = ['user' => $id, 'token' => bin2hex(random_bytes(32))];
}

function redirect(string $query = ''): never {
    header('Location: ./' . $query, true, 303);
    exit;
}

function h(string $s): string { return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function num(float $v, int $digits = 2): string { return number_format($v, $digits, ',', '.'); }
function brDate(string $iso): string { return implode('/', array_reverse(explode('-', $iso))); }

function modelKey(): string {
    return getenv('OPENROUTER_API_KEY') ?: (getenv('OPENAI_API_KEY') ?: '');
}

function modelProvider(): string {
    return getenv('OPENROUTER_API_KEY') ? 'OpenRouter' : 'OpenAI';
}
