<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/init.php';
require_once INCLUDES_PATH . '/auth.php';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    redirect(pageUrl('login.php'));
}

verifyCsrf();

$username = normalizeUsername((string) ($_POST['username'] ?? ''));
$password = (string) ($_POST['password'] ?? '');

$errors = [];

if ($username === '') {
    $errors[] = 'O username é obrigatório.';
}

if ($password === '') {
    $errors[] = 'A password é obrigatória.';
}

if ($errors !== []) {
    redirectWithFormErrors('login.php', $errors, ['username' => $username]);
}

$user = dbQuery(
    'SELECT id, password, active
     FROM users
     WHERE LOWER(username) = :username_lookup',
    ['username_lookup' => usernameLookupKey($username)]
)->fetch();

if (!$user || !password_verify($password, (string) $user['password'])) {
    redirectWithFormErrors(
        'login.php',
        ['Username ou password incorretos.'],
        ['username' => $username]
    );
}

if ((int) $user['active'] !== 1) {
    redirectWithFormErrors(
        'login.php',
        ['A tua conta está desativada. Contacta um administrador.'],
        ['username' => $username]
    );
}

if (password_needs_rehash((string) $user['password'], PASSWORD_DEFAULT)) {
    dbQuery(
        'UPDATE users SET password = :password WHERE id = :id',
        [
            'password' => password_hash($password, PASSWORD_DEFAULT),
            'id' => (int) $user['id'],
        ]
    );
}

loginSessionUser((int) $user['id']);

redirect(consumeRedirectAfterLogin(pageUrl('home.php')));
