<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/init.php';
require_once INCLUDES_PATH . '/auth.php';

$admin = requireRole('admin');

$filterUserId = isset($_GET['user']) ? (int) $_GET['user'] : 0;
if ($filterUserId < 0) {
    $filterUserId = 0;
}

$pdo     = getDatabase();
$errors  = [];
$success = '';

$roleLabels = [
    'admin'   => 'Admin',
    'trainer' => 'Treinador',
    'member'  => 'Membro',
];

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
                $currentLabel = $roleLabels[$targetUser['role']] ?? $targetUser['role'];
                $errors[] = "O utilizador '{$targetUser['name']}' já tem o papel '{$currentLabel}'.";
            } else {
                $pdo->prepare('UPDATE users SET role = :role WHERE id = :id')
                    ->execute(['role' => $newRole, 'id' => $userId]);
                $roleLabel = $roleLabels[$newRole] ?? $newRole;
                $success = "Papel de '{$targetUser['name']}' alterado para '{$roleLabel}'.";
            }
        }
    }

    /* ---------- DELETE REVIEW ---------- */
    if ($action === 'delete_review') {
        $reviewId = (int) ($_POST['review_id'] ?? 0);

        if ($reviewId <= 0) {
            $errors[] = 'Avaliação inválida.';
        } else {
            $reviewStmt = $pdo->prepare(
                'SELECT r.id, u.name AS member_name, c.title AS class_title
                 FROM reviews r
                 JOIN users u ON u.id = r.user_id
                 JOIN classes c ON c.id = r.class_id
                 WHERE r.id = :id'
            );
            $reviewStmt->execute(['id' => $reviewId]);
            $reviewRow = $reviewStmt->fetch();

            if (!$reviewRow) {
                $errors[] = 'Avaliação não encontrada.';
            } else {
                $pdo->prepare('DELETE FROM reviews WHERE id = :id')->execute(['id' => $reviewId]);
                $success = "Avaliação de '{$reviewRow['member_name']}' na aula '{$reviewRow['class_title']}' foi removida.";
            }
        }
    }

    /* ---------- TOGGLE REVIEW PERMISSION ---------- */
    if ($action === 'toggle_reviews_blocked') {
        $userId = (int) ($_POST['user_id'] ?? 0);

        if ($userId <= 0) {
            $errors[] = 'Utilizador inválido.';
        } else {
            $userStmt = $pdo->prepare('SELECT id, name, role, reviews_blocked FROM users WHERE id = :id');
            $userStmt->execute(['id' => $userId]);
            $targetUser = $userStmt->fetch();

            if (!$targetUser) {
                $errors[] = 'Utilizador não encontrado.';
            } elseif ($targetUser['role'] !== 'member') {
                $errors[] = 'Só é possível bloquear avaliações de membros.';
            } else {
                $newBlocked = (int) $targetUser['reviews_blocked'] === 1 ? 0 : 1;
                $pdo->prepare('UPDATE users SET reviews_blocked = :blocked WHERE id = :id')
                    ->execute(['blocked' => $newBlocked, 'id' => $userId]);

                if ($newBlocked === 1) {
                    $success = "Avaliações bloqueadas para '{$targetUser['name']}'.";
                } else {
                    $success = "Avaliações permitidas novamente para '{$targetUser['name']}'.";
                }
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
    SELECT u.id, u.username, u.name, u.email, u.role, u.active, u.created_at,
           u.reviews_blocked,
           (SELECT COUNT(*) FROM reviews r WHERE r.user_id = u.id) AS review_count
    FROM users u
    ORDER BY u.role, u.name
')->fetchAll();

$reviewsSql = '
    SELECT r.id, r.rating, r.comment, r.created_at,
           u.id AS user_id, u.name AS member_name, u.username, u.reviews_blocked,
           c.id AS class_id, c.title AS class_title
    FROM reviews r
    JOIN users u ON u.id = r.user_id
    JOIN classes c ON c.id = r.class_id
';
$reviewsParams = [];

if ($filterUserId > 0) {
    $reviewsSql .= ' WHERE u.id = :user_id';
    $reviewsParams['user_id'] = $filterUserId;
}

$reviewsSql .= ' ORDER BY r.created_at DESC';

$reviewsStmt = $pdo->prepare($reviewsSql);
$reviewsStmt->execute($reviewsParams);
$allReviews = $reviewsStmt->fetchAll();

$reviewAuthors = $pdo->query(
    'SELECT DISTINCT u.id, u.name, u.username
     FROM users u
     INNER JOIN reviews r ON r.user_id = u.id
     ORDER BY u.name, u.username'
)->fetchAll();

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
            <div class="admin-header-links">
                <a href="<?= e(pageUrl('admin_classes.php')) ?>" class="btn-admin-secondary">Gerir Aulas</a>
                <a href="<?= e(pageUrl('admin_equipment.php')) ?>" class="btn-admin-secondary">Gerir Equipamentos</a>
            </div>
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
        <details class="admin-card admin-details">
            <summary class="admin-details__summary">
                <span class="admin-details__title">Criar Novo Utilizador</span>
            </summary>
            <div class="admin-details__body">
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
            </div>
        </details>

        <!-- ===== TABLE: USER LIST ===== -->
        <details class="admin-card admin-details" open>
            <summary class="admin-details__summary">
                <span class="admin-details__title">
                    Utilizadores
                    <span class="badge-count"><?= count($users) ?></span>
                </span>
            </summary>
            <div class="admin-details__body">
            <div class="admin-filters" role="search">
                <input type="search"
                       id="user-search"
                       placeholder="Pesquisar por nome, username ou email…"
                       aria-label="Pesquisar utilizadores">
                <select id="user-filter-role" aria-label="Filtrar por papel">
                    <option value="">Todos os papéis</option>
                    <option value="member">Membro</option>
                    <option value="trainer">Treinador</option>
                    <option value="admin">Admin</option>
                </select>
                <select id="user-filter-active" aria-label="Filtrar por estado">
                    <option value="">Todos os estados</option>
                    <option value="1">Ativos</option>
                    <option value="0">Inativos</option>
                </select>
                <button type="button" class="btn-admin-filter-clear" id="user-filter-clear">Limpar</button>
                <span class="admin-filter-count" id="user-filter-count"></span>
            </div>

            <div class="table-wrapper">
                <table class="admin-table" id="users-table">
                    <thead>
                        <tr>
                            <th>Nome</th>
                            <th>Username</th>
                            <th>Email</th>
                            <th>Papel</th>
                            <th>Estado</th>
                            <th>Avaliações</th>
                            <th>Alterar papel</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $u):
                            $searchBlob = strtolower(implode(' ', array_filter([
                                (string) ($u['name'] ?? ''),
                                (string) $u['username'],
                                (string) ($u['email'] ?? ''),
                            ])));
                        ?>
                            <tr class="<?= $u['active'] ? '' : 'row--inactive' ?>"
                                data-user-row="1"
                                data-search="<?= e($searchBlob) ?>"
                                data-role="<?= e((string) $u['role']) ?>"
                                data-active="<?= (int) $u['active'] ?>"
                                data-user-id="<?= (int) $u['id'] ?>">
                                <td data-label="Nome"><strong><?= e($u['name'] ?? '—') ?></strong></td>
                                <td data-label="Username"><?= e($u['username']) ?></td>
                                <td data-label="Email"><?= e($u['email'] ?? '—') ?></td>
                                <td data-label="Papel">
                                    <span class="role-badge role-badge--<?= e($u['role']) ?>">
                                        <?= e($roleLabels[$u['role']] ?? $u['role']) ?>
                                    </span>
                                </td>
                                <td data-label="Estado">
                                    <span class="status-dot <?= $u['active'] ? 'status-dot--on' : 'status-dot--off' ?>">
                                        <?= $u['active'] ? 'Ativo' : 'Inativo' ?>
                                    </span>
                                </td>
                                <td data-label="Avaliações">
                                    <?php $reviewCount = (int) ($u['review_count'] ?? 0); ?>
                                    <span class="review-count-badge"><?= $reviewCount ?></span>
                                    <?php if ($reviewCount > 0): ?>
                                        <button type="button"
                                                class="filter-user-link"
                                                data-filter-user="<?= (int) $u['id'] ?>"
                                                data-filter-label="<?= e((string) ($u['name'] ?? $u['username'])) ?>">
                                            Ver avaliações
                                        </button>
                                    <?php else: ?>
                                        <span class="text-muted">—</span>
                                    <?php endif; ?>
                                    <?php if ($u['role'] === 'member'): ?>
                                        <form method="POST" action="<?= e(pageUrl('admin_users.php')) ?>" class="inline-form review-block-form">
                                            <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                                            <input type="hidden" name="action" value="toggle_reviews_blocked">
                                            <input type="hidden" name="user_id" value="<?= (int) $u['id'] ?>">
                                            <button type="submit"
                                                    class="<?= (int) $u['reviews_blocked'] === 1 ? 'btn-table-edit' : 'btn-table-delete' ?>"
                                                    title="<?= (int) $u['reviews_blocked'] === 1 ? 'Permitir avaliações' : 'Bloquear avaliações' ?>">
                                                <?= (int) $u['reviews_blocked'] === 1 ? '🔓 Permitir' : '🚫 Bloquear' ?>
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                                <td data-label="Alterar papel">
                                    <?php
                                    $availableRoles = [];
                                    if ($u['role'] !== 'trainer') {
                                        $availableRoles['trainer'] = 'Treinador';
                                    }
                                    if ($u['role'] !== 'admin') {
                                        $availableRoles['admin'] = 'Admin';
                                    }
                                    if ($u['role'] !== 'member') {
                                        $availableRoles['member'] = 'Membro';
                                    }
                                    $canApplyRole = $availableRoles !== [] && (int) $u['id'] !== (int) $admin['id'];
                                    ?>
                                    <form method="POST"
                                          action="<?= e(pageUrl('admin_users.php')) ?>"
                                          class="inline-form role-apply-form"
                                          data-user-name="<?= e((string) ($u['name'] ?? $u['username'])) ?>">
                                        <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                                        <input type="hidden" name="action" value="promote">
                                        <input type="hidden" name="user_id" value="<?= (int) $u['id'] ?>">
                                        <select name="new_role"
                                                class="select-inline"
                                                <?= !$canApplyRole ? 'disabled' : '' ?>
                                                required>
                                            <?php foreach ($availableRoles as $val => $label): ?>
                                                <option value="<?= e($val) ?>"><?= e($label) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                        <button type="submit"
                                                class="btn-table-apply"
                                                title="Aplicar o papel selecionado"
                                                <?= !$canApplyRole ? 'disabled' : '' ?>>
                                            ✓ Aplicar
                                        </button>
                                    </form>
                                </td>
                                <td data-label="Ações" class="table-actions">
                                    <form method="POST"
                                          action="<?= e(pageUrl('admin_users.php')) ?>"
                                          data-confirm="Alterar o estado deste utilizador?"
                                          data-confirm-title="Alterar estado">
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
                <p class="empty-state users-filter-empty" hidden>Nenhum utilizador corresponde aos filtros.</p>
            </div>
            </div>
        </details>

        <!-- ===== REVIEWS MANAGEMENT ===== -->
        <details class="admin-card admin-details" id="reviews-section"<?= $filterUserId > 0 ? ' open' : '' ?>>
            <summary class="admin-details__summary">
                <span class="admin-details__title">
                    Gestão de Avaliações
                    <span class="badge-count"><?= count($allReviews) ?></span>
                </span>
            </summary>
            <div class="admin-details__body">
            <div class="admin-filters" role="search">
                <input type="search"
                       id="review-user-search"
                       placeholder="Pesquisar por nome ou username do membro…"
                       aria-label="Pesquisar avaliações por membro">
                <select id="review-user-select" aria-label="Filtrar avaliações por utilizador">
                    <option value="">Todos os membros com avaliações</option>
                    <?php foreach ($reviewAuthors as $author): ?>
                        <option value="<?= (int) $author['id'] ?>"
                                data-label="<?= e((string) ($author['name'] ?? $author['username'])) ?>"
                                <?= $filterUserId === (int) $author['id'] ? 'selected' : '' ?>>
                            <?= e((string) ($author['name'] ?? $author['username'])) ?> (@<?= e($author['username']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
                <button type="button" class="btn-admin-filter-clear" id="review-filter-clear">Limpar</button>
                <span class="admin-filter-count" id="review-filter-count"></span>
            </div>

            <?php if (count($allReviews) === 0): ?>
                <p class="empty-state">Não há avaliações registadas.</p>
            <?php else: ?>
                <div class="table-wrapper">
                    <table class="admin-table admin-table--reviews" id="reviews-table">
                        <thead>
                            <tr>
                                <th>Membro</th>
                                <th>Aula</th>
                                <th>Classificação</th>
                                <th>Comentário</th>
                                <th>Data</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($allReviews as $review):
                                $reviewSearchBlob = strtolower(implode(' ', [
                                    (string) ($review['member_name'] ?? ''),
                                    (string) $review['username'],
                                    (string) ($review['class_title'] ?? ''),
                                ]));
                            ?>
                                <tr data-review-row="1"
                                    data-user-id="<?= (int) $review['user_id'] ?>"
                                    data-search="<?= e($reviewSearchBlob) ?>">
                                    <td data-label="Membro">
                                        <strong><?= e($review['member_name'] ?? $review['username']) ?></strong>
                                        <br><small>@<?= e($review['username']) ?></small>
                                        <?php if ((int) $review['reviews_blocked'] === 1): ?>
                                            <br><span class="status-dot status-dot--off">Avaliações bloqueadas</span>
                                        <?php endif; ?>
                                    </td>
                                    <td data-label="Aula">
                                        <a href="<?= e(pageUrl('class.php?id=' . (int) $review['class_id'])) ?>">
                                            <?= e($review['class_title']) ?>
                                        </a>
                                    </td>
                                    <td data-label="Classificação">
                                        <span class="review-stars-inline">
                                            <?= str_repeat('★', (int) $review['rating']) ?><?= str_repeat('☆', 5 - (int) $review['rating']) ?>
                                        </span>
                                    </td>
                                    <td data-label="Comentário" class="review-comment-cell">
                                        <?= $review['comment'] !== null && $review['comment'] !== ''
                                            ? e($review['comment'])
                                            : '—' ?>
                                    </td>
                                    <td data-label="Data"><?= e(substr((string) $review['created_at'], 0, 10)) ?></td>
                                    <td data-label="Ações" class="table-actions">
                                        <form method="POST"
                                              action="<?= e(pageUrl('admin_users.php') . ($filterUserId > 0 ? '?user=' . $filterUserId : '') . '#reviews-section') ?>"
                                              data-confirm="O membro poderá voltar a avaliar esta aula se tiver permissão."
                                              data-confirm-title="Remover avaliação"
                                              data-confirm-ok="Remover">
                                            <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                                            <input type="hidden" name="action" value="delete_review">
                                            <input type="hidden" name="review_id" value="<?= (int) $review['id'] ?>">
                                            <button type="submit" class="btn-table-delete">🗑 Remover</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <p class="empty-state reviews-filter-empty" hidden>Nenhuma avaliação corresponde aos filtros.</p>
            <?php endif; ?>
            </div>
        </details>

    </div>
</main>

<script src="<?= e(asset('js/admin-users.js')) ?>"></script>

<?php require INCLUDES_PATH . '/footer.php'; ?>