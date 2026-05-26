<?php
 
declare(strict_types=1);

$currentPage      = 'admin';
$pageTitle        = 'Gestão de Aulas | Admin';
$extraStylesheets = ['pages/admin.css'];
 
 
require_once __DIR__ . '/includes/init.php';
require_once __DIR__ . '/includes/auth.php';
 
$admin = requireRole('admin');
 
$pdo    = getDatabase();
$errors = [];
$success = '';
 
/* -------------------------------------------------------------------------
 * Handle POST actions
 * ---------------------------------------------------------------------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
 
    $action = $_POST['action'] ?? '';
 
    /* ---------- helpers ---------- */
    $cleanStr  = fn(string $k) => trim($_POST[$k] ?? '');
    $cleanInt  = fn(string $k) => (int) ($_POST[$k] ?? 0);
 
    /* ---------- validate shared fields ---------- */
    $validateClass = function () use ($cleanStr, $cleanInt, $pdo, &$errors): array {
        $title      = $cleanStr('title');
        $desc       = $cleanStr('description');
        $type       = $cleanStr('type');
        $trainerId  = $cleanInt('trainer_id');
        $dayOfWeek  = $cleanStr('day_of_week');
        $startTime  = $cleanStr('start_time');
        $endTime    = $cleanStr('end_time');
        $capacity   = $cleanInt('capacity');
 
        if ($title === '')      $errors[] = 'O título é obrigatório.';
        if ($type === '')       $errors[] = 'A categoria é obrigatória.';
        if ($dayOfWeek === '')  $errors[] = 'O dia da semana é obrigatório.';
        if ($startTime === '')  $errors[] = 'A hora de início é obrigatória.';
        if ($endTime === '')    $errors[] = 'A hora de fim é obrigatória.';
        if ($capacity <= 0)     $errors[] = 'A capacidade deve ser um número maior que 0.';
 
        if ($startTime !== '' && $endTime !== '' && $startTime >= $endTime) {
            $errors[] = 'A hora de fim deve ser posterior à hora de início.';
        }
 
        if ($trainerId > 0) {
            $trainer = $pdo->prepare('SELECT id FROM users WHERE id = :id AND role = :role AND active = 1');
            $trainer->execute(['id' => $trainerId, 'role' => 'trainer']);
            if (!$trainer->fetch()) {
                $errors[] = 'O treinador selecionado não é válido.';
            }
        }
 
        return compact('title','desc','type','trainerId','dayOfWeek','startTime','endTime','capacity');
    };
 
    /* ---------- CREATE ---------- */
    if ($action === 'create') {
        $data = $validateClass();
 
        if (empty($errors)) {
            // duplicate name check
            $dup = $pdo->prepare('SELECT id FROM classes WHERE LOWER(title) = LOWER(:title)');
            $dup->execute(['title' => $data['title']]);
            if ($dup->fetch()) {
                $errors[] = 'Já existe uma aula com este nome.';
            }
        }
 
        if (empty($errors)) {
            $stmt = $pdo->prepare('
                INSERT INTO classes (title, description, type, trainer_id, day_of_week, start_time, end_time, capacity)
                VALUES (:title, :desc, :type, :trainer_id, :day_of_week, :start_time, :end_time, :capacity)
            ');
            $stmt->execute([
                'title'      => $data['title'],
                'desc'       => $data['desc'],
                'type'       => $data['type'],
                'trainer_id' => $data['trainerId'] > 0 ? $data['trainerId'] : null,
                'day_of_week'=> $data['dayOfWeek'],
                'start_time' => $data['startTime'],
                'end_time'   => $data['endTime'],
                'capacity'   => $data['capacity'],
            ]);
            $success = 'Aula criada com sucesso!';
        }
    }
 
    /* ---------- EDIT ---------- */
    if ($action === 'edit') {
        $classId = $cleanInt('class_id');
        $data    = $validateClass();
 
        if (empty($errors)) {
            // duplicate name check (excluding itself)
            $dup = $pdo->prepare('SELECT id FROM classes WHERE LOWER(title) = LOWER(:title) AND id != :id');
            $dup->execute(['title' => $data['title'], 'id' => $classId]);
            if ($dup->fetch()) {
                $errors[] = 'Já existe outra aula com este nome.';
            }
        }
 
        if (empty($errors)) {
            $stmt = $pdo->prepare('
                UPDATE classes SET
                    title       = :title,
                    description = :desc,
                    type        = :type,
                    trainer_id  = :trainer_id,
                    day_of_week = :day_of_week,
                    start_time  = :start_time,
                    end_time    = :end_time,
                    capacity    = :capacity
                WHERE id = :id
            ');
            $stmt->execute([
                'title'      => $data['title'],
                'desc'       => $data['desc'],
                'type'       => $data['type'],
                'trainer_id' => $data['trainerId'] > 0 ? $data['trainerId'] : null,
                'day_of_week'=> $data['dayOfWeek'],
                'start_time' => $data['startTime'],
                'end_time'   => $data['endTime'],
                'capacity'   => $data['capacity'],
                'id'         => $classId,
            ]);
            $success = 'Aula atualizada com sucesso!';
        }
    }
 
    /* ---------- DELETE ---------- */
    if ($action === 'delete') {
        $classId = $cleanInt('class_id');
        if ($classId > 0) {
            $pdo->prepare('DELETE FROM classes WHERE id = :id')->execute(['id' => $classId]);
            $success = 'Aula eliminada com sucesso!';
        } else {
            $errors[] = 'ID de aula inválido.';
        }
    }
}
 
/* -------------------------------------------------------------------------
 * Fetch data for display
 * ---------------------------------------------------------------------- */
$classes = $pdo->query('
    SELECT c.*, u.name AS trainer_name
    FROM classes c
    LEFT JOIN users u ON u.id = c.trainer_id
    ORDER BY
        CASE c.day_of_week
            WHEN "monday"    THEN 1
            WHEN "tuesday"   THEN 2
            WHEN "wednesday" THEN 3
            WHEN "thursday"  THEN 4
            WHEN "friday"    THEN 5
            WHEN "saturday"  THEN 6
            WHEN "sunday"    THEN 7
        END,
        c.start_time
')->fetchAll();
 
$trainers = $pdo->query("SELECT id, name FROM users WHERE role = 'trainer' AND active = 1 ORDER BY name")->fetchAll();
 
$editClass = null;
if (isset($_GET['edit'])) {
    $editId    = (int) $_GET['edit'];
    $editStmt  = $pdo->prepare('SELECT * FROM classes WHERE id = :id');
    $editStmt->execute(['id' => $editId]);
    $editClass = $editStmt->fetch() ?: null;
}
 
$dayLabels = [
    'monday'    => 'Segunda-feira',
    'tuesday'   => 'Terça-feira',
    'wednesday' => 'Quarta-feira',
    'thursday'  => 'Quinta-feira',
    'friday'    => 'Sexta-feira',
    'saturday'  => 'Sábado',
    'sunday'    => 'Domingo',
];
 
$typeLabels = [
    'yoga'    => 'Yoga',
    'pilates' => 'Pilates',
    'hiit'    => 'HIIT',
    'zumba'   => 'Zumba',
    'cycling' => 'Cycling',
    'boxing'  => 'Boxe',
    'other'   => 'Outro',
];
 
/* -------------------------------------------------------------------------
 * Render
 * ---------------------------------------------------------------------- */

 
require INCLUDES_PATH . '/header.php';
require INCLUDES_PATH . '/nav.php';
?>
 
<main class="admin-main">
    <div class="admin-container">
 
        <div class="admin-header">
            <h1 class="admin-title">Gestão de <span>Aulas</span></h1>
            <a href="<?= e(pageUrl('admin_users.php')) ?>" class="btn-admin-secondary">Gerir Utilizadores</a>
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
 
        <!-- ===== FORM: CREATE / EDIT ===== -->
        <section class="admin-card">
            <h2><?= $editClass ? 'Editar Aula' : 'Nova Aula' ?></h2>
 
            <form method="POST" action="<?= e(pageUrl('admin_classes.php')) ?>" novalidate>
                <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                <input type="hidden" name="action"     value="<?= $editClass ? 'edit' : 'create' ?>">
                <?php if ($editClass): ?>
                    <input type="hidden" name="class_id" value="<?= (int) $editClass['id'] ?>">
                <?php endif; ?>
 
                <div class="form-grid">
                    <div class="input-group">
                        <label for="title">Nome da Aula *</label>
                        <input type="text" id="title" name="title" required
                               value="<?= e($editClass['title'] ?? '') ?>"
                               placeholder="Ex: Yoga Matinal">
                    </div>
 
                    <div class="input-group">
                        <label for="type">Categoria *</label>
                        <select id="type" name="type" required>
                            <option value="">— Selecionar —</option>
                            <?php foreach ($typeLabels as $val => $label): ?>
                                <option value="<?= e($val) ?>"
                                    <?= ($editClass['type'] ?? '') === $val ? 'selected' : '' ?>>
                                    <?= e($label) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
 
                    <div class="input-group">
                        <label for="trainer_id">Treinador</label>
                        <select id="trainer_id" name="trainer_id">
                            <option value="">— Sem treinador —</option>
                            <?php foreach ($trainers as $t): ?>
                                <option value="<?= (int) $t['id'] ?>"
                                    <?= ((int) ($editClass['trainer_id'] ?? 0)) === (int) $t['id'] ? 'selected' : '' ?>>
                                    <?= e($t['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
 
                    <div class="input-group">
                        <label for="day_of_week">Dia da Semana *</label>
                        <select id="day_of_week" name="day_of_week" required>
                            <option value="">— Selecionar —</option>
                            <?php foreach ($dayLabels as $val => $label): ?>
                                <option value="<?= e($val) ?>"
                                    <?= ($editClass['day_of_week'] ?? '') === $val ? 'selected' : '' ?>>
                                    <?= e($label) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
 
                    <div class="input-group">
                        <label for="start_time">Hora de Início *</label>
                        <input type="time" id="start_time" name="start_time" required
                               value="<?= e($editClass['start_time'] ?? '') ?>">
                    </div>
 
                    <div class="input-group">
                        <label for="end_time">Hora de Fim *</label>
                        <input type="time" id="end_time" name="end_time" required
                               value="<?= e($editClass['end_time'] ?? '') ?>">
                    </div>
 
                    <div class="input-group">
                        <label for="capacity">Capacidade Máxima *</label>
                        <input type="number" id="capacity" name="capacity" min="1" required
                               value="<?= (int) ($editClass['capacity'] ?? 15) ?>">
                    </div>
 
                    <div class="input-group input-group--full">
                        <label for="description">Descrição</label>
                        <textarea id="description" name="description" rows="3"
                                  placeholder="Descrição breve da aula..."><?= e($editClass['description'] ?? '') ?></textarea>
                    </div>
                </div>
 
                <div class="form-actions">
                    <button type="submit" class="btn-admin-primary">
                        <?= $editClass ? '💾 Guardar Alterações' : '➕ Criar Aula' ?>
                    </button>
                    <?php if ($editClass): ?>
                        <a href="<?= e(pageUrl('admin_classes.php')) ?>" class="btn-admin-cancel">Cancelar</a>
                    <?php endif; ?>
                </div>
            </form>
        </section>
 
        <!-- ===== TABLE: CLASS LIST ===== -->
        <section class="admin-card">
            <h2>Aulas Existentes <span class="badge-count"><?= count($classes) ?></span></h2>
 
            <?php if (empty($classes)): ?>
                <p class="empty-state">Nenhuma aula encontrada. Crie a primeira acima!</p>
            <?php else: ?>
                <div class="table-wrapper">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Nome</th>
                                <th>Categoria</th>
                                <th>Treinador</th>
                                <th>Dia</th>
                                <th>Horário</th>
                                <th>Cap.</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($classes as $class): ?>
                                <tr>
                                    <td><strong><?= e($class['title']) ?></strong></td>
                                    <td>
                                        <span class="tag tag--<?= e($class['type'] ?? 'other') ?>">
                                            <?= e($typeLabels[$class['type']] ?? $class['type']) ?>
                                        </span>
                                    </td>
                                    <td><?= e($class['trainer_name'] ?? '—') ?></td>
                                    <td><?= e($dayLabels[$class['day_of_week']] ?? $class['day_of_week']) ?></td>
                                    <td><?= e($class['start_time']) ?>–<?= e($class['end_time']) ?></td>
                                    <td><?= (int) $class['capacity'] ?></td>
                                    <td class="table-actions">
                                        <a href="<?= e(pageUrl('admin_classes.php') . '?edit=' . (int) $class['id']) ?>"
                                           class="btn-table-edit">✏️ Editar</a>
 
                                        <form method="POST" action="<?= e(pageUrl('admin_classes.php')) ?>"
                                              onsubmit="return confirm('Eliminar a aula \'<?= e(addslashes($class['title'])) ?>\'? Esta ação é irreversível.')">
                                            <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                                            <input type="hidden" name="action"   value="delete">
                                            <input type="hidden" name="class_id" value="<?= (int) $class['id'] ?>">
                                            <button type="submit" class="btn-table-delete">🗑️ Eliminar</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </section>
 
    </div>
</main>
 
<?php require INCLUDES_PATH . '/footer.php'; ?>
 
