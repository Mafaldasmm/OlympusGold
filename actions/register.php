<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/init.php';
require_once INCLUDES_PATH . '/auth.php';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    redirect(pageUrl('register.php'));
}

verifyCsrf();

$name = trim((string) ($_POST['name'] ?? ''));
$username = normalizeUsername((string) ($_POST['username'] ?? ''));
$email = normalizeEmail((string) ($_POST['email'] ?? ''));
$password = (string) ($_POST['password'] ?? '');
$confirm = (string) ($_POST['confirm'] ?? '');

$errors = [];
$old = [
    'name' => $name,
    'username' => $username,
    'email' => $email,
];

if ($name === '') {
    $errors[] = 'O nome é obrigatório.';
} elseif (strlen($name) > 100) {
    $errors[] = 'O nome é demasiado longo.';
}

if ($username === '') {
    $errors[] = 'O username é obrigatório.';
} elseif (!preg_match('/^[A-Za-z0-9_.-]{3,30}$/', $username)) {
    $errors[] = 'O username deve ter 3-30 caracteres e só pode usar letras, números, ponto, underscore ou hífen.';
}

if ($email === '') {
    $errors[] = 'O email é obrigatório.';
} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'O email não é válido.';
} elseif (strlen($email) > 254) {
    $errors[] = 'O email é demasiado longo.';
}

if (strlen($password) < 8) {
    $errors[] = 'A password deve ter pelo menos 8 caracteres.';
}

if ($password !== $confirm) {
    $errors[] = 'As passwords não coincidem.';
}

if ($errors !== []) {
    redirectWithFormErrors('register.php', $errors, $old);
}

$duplicate = dbQuery(
    'SELECT id
     FROM users
     WHERE LOWER(username) = :username_lookup
        OR LOWER(email) = :email_lookup',
    [
        'username_lookup' => usernameLookupKey($username),
        'email_lookup' => $email,
    ]
)->fetch();

if ($duplicate) {
    redirectWithFormErrors(
        'register.php',
        ['Já existe uma conta com esse username ou email.'],
        $old
    );
}

$hash = password_hash($password, PASSWORD_DEFAULT);

dbQuery(
    'INSERT INTO users (username, password, email, name, role, active)
     VALUES (:username, :password, :email, :name, :role, :active)',
    [
        'username' => $username,
        'password' => $hash,
        'email' => $email,
        'name' => $name,
        'role' => 'member',
        'active' => 1,
    ]
);

setFlash('auth_success', 'Conta criada com sucesso. Agora já podes iniciar sessão.');
redirect(pageUrl('login.php'));
