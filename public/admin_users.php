<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/init.php';
require_once INCLUDES_PATH . '/auth.php';

$admin = requireRole('admin');

$pdo     = getDatabase();
$errors  = [];
$success = '';

/* -------------------------------------------------------------------------
 * Handle POST actions
 * ---------------------------------------------------------------------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();

    $action = $_POST['action'] ?? '';

    /* ---------- CREATE USER ---------- */
    if ($action === 'create') {
        $name     = trim($_POST['name']     ?? '');
        $username = trim($_POST['username'] ?? '');
        $email    = trim($_POST['email']    ?? '');
        $password = $_POST['password']      ?? '';
        $role     = $_POST['role']          ?? '';

        if ($name     === '') $errors[] = 'O nome é obrigatório.';
        if ($username === '') $errors[] = 'O username é obrigatório.';
        if ($email    === '') $errors[] = 'O email é obrigatório.';
        if ($password === '') $errors[] = 'A password é obrigatória.';
        if (strlen($password) < 6) $errors[] = 'A password deve ter pelo menos 6 caracteres.';
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'O email não é válido.';
        if (!in_array($role, ['member', 'trainer', 'admin'], true)) $errors[] = 'Papel inválido.';

        if (empty($errors)) {
            $dup = $pdo->prepare('SELECT id FROM users WHERE username = :u OR email = :e');
            $dup->execute(['u' => $username, 'e' => $email]);
            if ($dup->fetch()) {
                $errors[] = 'Já existe um utilizador com esse username ou email.';
            }
        }

        if (empty($errors)) {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $pdo->prepare('INSERT INTO users (username, password, email, name, role) VALUES (:u, :p, :e, :n, :r)')
                ->execute(['u' => $username, 'p' => $hash, 'e' => $email, 'n' => $name, 'r' => $role]);
            $success = "Utilizador '{$name}' criado com sucesso!";
        }
    }

    /* ---------- PROMOTE USER ---------- */
    if ($action === 'promote') {
        $userId  = (int) ($_POST['user_id']  ?? 0);
        $newRole = $_POST['new_role'] ?? '';

        if (!in_array($newRole, ['member', 'trainer', 'admin'], true)) {
            $errors[] = 'Papel inválido.';
        } elseif ($userId <= 0) {
            $errors[] = 'Utilizador inválido.';
        } else {
            $userStmt = $pdo->prepare('SELECT id, name, role FROM users WHERE id = :id');
            $userStmt->execute(['id' => $userId]);
            $targetUser = $userStmt->fetch();

            if (!$targetUser) {
                $errors[] = 'Utilizador não encontrado.';
            } elseif ($targetUser['role'] === $newRole) {
                $errors[] = "O utilizador '{$targetUser['name']}' já tem o papel '{$newRole}'.";
            } else {
                $pdo->prepare('UPDATE users SET role = :role WHERE id = :id')
                    ->execute(['role' => $newRole, 'id' => $userId]);
                $success = "Papel de '{$targetUser['name']}' atualizado para '{$newRole}'.";
            }
        }
    }

    /* ---------- TOGGLE ACTIVE ---------- */
    if ($action === 'toggle_active') {
        $userId = (int) ($_POST['user_id'] ?? 0);
        if ($userId === (int) $admin['id']) {
            $errors[] = 'Não podes desativar a tua própria conta.';
        } elseif ($userId > 0) {
            $userStmt = $pdo->prepare('SELECT name, active FROM users WHERE id = :id');
            $userStmt->execute(['id' => $userId]);
            $targetUser = $userStmt->fetch();
            if ($targetUser) {
                $newActive = $targetUser['active'] ? 0 : 1;
                $pdo->prepare('UPDATE users SET active = :active WHERE id = :id')
                    ->execute(['active' => $newActive, 'id' => $userId]);
                $state   = $newActive ? 'ativado' : 'desativado';
                $success = "Utilizador '{$targetUser['name']}' {$state}.";
            } else {
                $errors[] = 'Utilizador não encontrado.';
            }
        }
    }
}

/* -------------------------------------------------------------------------
 * Fetch users
 * ---------------------------------------------------------------------- */
$users = $pdo->query('
    SELECT id, username, name, email, role, active, created_at
    FROM users
    ORDER BY role, name
')->fetchAll();

$roleLabels = [
    'admin'   => 'Admin',
    'trainer' => 'Treinador',
    'member'  => 'Membro',
];

$currentPage      = 'admin';
$pageTitle        = 'Gestão de Utilizadores | Admin';
$extraStylesheets = ['pages/admin.css'];

require INCLUDES_PATH . '/header.php';
require INCLUDES_PATH . '/nav.php';
?>

<main class="admin-main">
    <div class="admin-container">

        <div class="admin-header">
            <h1 class="admin-title">Gestão de <span>Utilizadores</span></h1>
            <a href="<?= e(pageUrl('admin_classes.php')) ?>" class="btn-admin-secondary">Gerir Aulas</a>
        </div>

        <?php if ($success): ?>
            <div class="alert alert-success"><?= e($success) ?></div>
        <?php endif; ?>

        <?php if ($errors): ?>
            <div class="alert alert-error">
                <?php foreach ($errors as $err): ?>
                    <p><?= e($err) ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <!-- ===== FORM: CREATE USER ===== -->
        <section class="admin-card">
            <h2>Criar Novo Utilizador</h2>

            <form method="POST" action="<?= e(pageUrl('admin_users.php')) ?>" novalidate>
                <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                <input type="hidden" name="action"     value="create">

                <div class="form-grid">
                    <div class="input-group">
                        <label for="name">Nome Completo *</label>
                        <input type="text" id="name" name="name" required placeholder="Ex: Maria Sousa">
                    </div>

                    <div class="input-group">
                        <label for="username">Username *</label>
                        <input type="text" id="username" name="username" required placeholder="Ex: maria.sousa">
                    </div>

                    <div class="input-group">
                        <label for="email">Email *</label>
                        <input type="email" id="email" name="email" required placeholder="Ex: maria@exemplo.pt">
                    </div>

                    <div class="input-group">
                        <label for="password">Password *</label>
                        <input type="password" id="password" name="password" required placeholder="Mínimo 6 caracteres">
                    </div>

                    <div class="input-group">
                        <label for="role">Papel *</label>
                        <select id="role" name="role" required>
                            <option value="">— Selecionar —</option>
                            <option value="member">Membro</option>
                            <option value="trainer">Treinador</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn-admin-primary">➕ Criar Utilizador</button>
                </div>
            </form>
        </section>

        <!-- ===== TABLE: USER LIST ===== -->
        <section class="admin-card">
            <h2>Utilizadores <span class="badge-count"><?= count($users) ?></span></h2>

            <div class="table-wrapper">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Nome</th>
                            <th>Username</th>
                            <th>Email</th>
                            <th>Papel</th>
                            <th>Estado</th>
                            <th>Promover Para</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $u): ?>
                            <tr class="<?= $u['active'] ? '' : 'row--inactive' ?>">
                                <td><strong><?= e($u['name'] ?? '—') ?></strong></td>
                                <td><?= e($u['username']) ?></td>
                                <td><?= e($u['email'] ?? '—') ?></td>
                                <td>
                                    <span class="role-badge role-badge--<?= e($u['role']) ?>">
                                        <?= e($roleLabels[$u['role']] ?? $u['role']) ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="status-dot <?= $u['active'] ? 'status-dot--on' : 'status-dot--off' ?>">
                                        <?= $u['active'] ? 'Ativo' : 'Inativo' ?>
                                    </span>
                                </td>
                                <td>
                                    <?php
                                    $promotions = [];
                                    if ($u['role'] !== 'trainer') $promotions['trainer'] = 'Treinador';
                                    if ($u['role'] !== 'admin')   $promotions['admin']   = 'Admin';
                                    if ($u['role'] !== 'member')  $promotions['member']  = 'Membro';
                                    ?>
                                    <form method="POST" action="<?= e(pageUrl('admin_users.php')) ?>" class="inline-form">
                                        <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                                        <input type="hidden" name="action"    value="promote">
                                        <input type="hidden" name="user_id"   value="<?= (int) $u['id'] ?>">
                                        <select name="new_role" class="select-inline">
                                            <?php foreach ($promotions as $val => $label): ?>
                                                <option value="<?= e($val) ?>"><?= e($label) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                        <button type="submit" class="btn-table-promote"
                                            <?= ((int) $u['id'] === (int) $admin['id']) ? 'disabled' : '' ?>>
                                            ↑ Promover
                                        </button>
                                    </form>
                                </td>
                                <td class="table-actions">
                                    <form method="POST" action="<?= e(pageUrl('admin_users.php')) ?>"
                                          onsubmit="return confirm('Alterar estado do utilizador?')">
                                        <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                                        <input type="hidden" name="action"  value="toggle_active">
                                        <input type="hidden" name="user_id" value="<?= (int) $u['id'] ?>">
                                        <button type="submit"
                                            class="<?= $u['active'] ? 'btn-table-delete' : 'btn-table-edit' ?>"
                                            <?= ((int) $u['id'] === (int) $admin['id']) ? 'disabled' : '' ?>>
                                            <?= $u['active'] ? '🚫 Desativar' : '✅ Ativar' ?>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>

    </div>
</main>

<?php require INCLUDES_PATH . '/footer.php'; ?>