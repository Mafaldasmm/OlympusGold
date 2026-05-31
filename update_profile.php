<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/init.php';
require_once INCLUDES_PATH . '/auth.php';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    redirect(pageUrl('profile.php'));
}

$user = requireLogin();
verifyCsrf();

$wantsJson = requestWantsJson();

/**
 * @param array<int, string> $errors
 */
function respondProfileErrors(array $errors, bool $asJson, array $old = []): never
{
    if ($asJson) {
        http_response_code(422);
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode([
            'success' => false,
            'message' => $errors[0] ?? 'Não foi possível atualizar o perfil.',
            'errors' => $errors,
            'csrfToken' => csrfToken(),
        ], JSON_THROW_ON_ERROR);
        exit;
    }

    redirectWithFormErrors('profile.php', $errors, $old);
}

$userId = (int) $user['id'];
$name = trim((string) ($_POST['name'] ?? ''));
$username = normalizeUsername((string) ($_POST['username'] ?? ''));
$password = (string) ($_POST['password'] ?? '');

$old = [
    'name' => $name,
    'username' => $username,
];

$errors = [];

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

if ($password !== '' && strlen($password) < 8) {
    $errors[] = 'A nova password deve ter pelo menos 8 caracteres.';
}

$duplicate = dbQuery(
    'SELECT id
     FROM users
     WHERE LOWER(username) = :username_lookup
       AND id != :id',
    [
        'username_lookup' => usernameLookupKey($username),
        'id' => $userId,
    ]
)->fetch();

if ($duplicate) {
    $errors[] = 'Esse username já está a ser utilizado por outra conta.';
}

$profilePhotoPath = null;

if ($hasNewPhotoUpload) {
    $upload = $_FILES['profile_photo'];
    $uploadError = (int) ($upload['error'] ?? UPLOAD_ERR_NO_FILE);

    if ($uploadError === UPLOAD_ERR_INI_SIZE || $uploadError === UPLOAD_ERR_FORM_SIZE) {
        $errors[] = 'A foto de perfil é demasiado grande.';
    } elseif ($uploadError !== UPLOAD_ERR_OK) {
        $errors[] = 'Não foi possível carregar a foto de perfil.';
    } else {
        $tmpPath = (string) ($upload['tmp_name'] ?? '');

        if ($tmpPath === '' || !is_uploaded_file($tmpPath)) {
            $errors[] = 'O ficheiro enviado não é válido.';
        } else {
            $maxBytes = 2 * 1024 * 1024;

            if ((int) ($upload['size'] ?? 0) > $maxBytes) {
                $errors[] = 'A foto de perfil não pode exceder 2 MB.';
            } else {
                $finfo = new finfo(FILEINFO_MIME_TYPE);
                $mime = $finfo->file($tmpPath) ?: '';
                $allowed = [
                    'image/jpeg' => 'jpg',
                    'image/png' => 'png',
                    'image/webp' => 'webp',
                ];

                if (!isset($allowed[$mime])) {
                    $errors[] = 'A foto de perfil tem de ser JPEG, PNG ou WebP.';
                } else {
                    $uploadDir = PUBLIC_ROOT . '/uploads/profiles';

                    if (!is_dir($uploadDir) && !mkdir($uploadDir, 0775, true) && !is_dir($uploadDir)) {
                        $errors[] = 'Não foi possível guardar a foto de perfil.';
                    } else {
                        $extension = $allowed[$mime];
                        $relativePath = 'uploads/profiles/' . $userId . '.' . $extension;
                        $destination = PUBLIC_ROOT . '/' . $relativePath;

                        foreach (glob($uploadDir . '/' . $userId . '.*') ?: [] as $existingFile) {
                            if (is_file($existingFile)) {
                                unlink($existingFile);
                            }
                        }

                        if (!move_uploaded_file($tmpPath, $destination)) {
                            $errors[] = 'Não foi possível guardar a foto de perfil.';
                        } else {
                            $profilePhotoPath = $relativePath;
                        }
                    }
                }
            }
        }
    }
}

if ($errors !== []) {
    respondProfileErrors($errors, $wantsJson, $old);
}

$hasNewPhotoUpload = isset($_FILES['profile_photo'])
    && is_array($_FILES['profile_photo'])
    && ($_FILES['profile_photo']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;

$profileUnchanged = trim($name) === trim((string) ($user['name'] ?? ''))
    && usernameLookupKey($username) === usernameLookupKey((string) $user['username'])
    && $password === ''
    && !$hasNewPhotoUpload;

if ($profileUnchanged) {
    clearCurrentUserCache();
    $updatedUser = currentUser(true);
    $displayName = $updatedUser !== null ? userDisplayName($updatedUser) : $name;
    $message = 'Estas informações já foram guardadas.';

    if ($wantsJson) {
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode([
            'success' => true,
            'alreadySaved' => true,
            'message' => $message,
            'displayName' => $displayName,
            'profilePhotoUrl' => profilePhotoUrl($updatedUser),
            'csrfToken' => csrfToken(),
        ], JSON_THROW_ON_ERROR);
        exit;
    }

    setFlash('profile_info', $message);
    redirect(pageUrl('profile.php'));
}

$params = [
    'name' => $name,
    'username' => $username,
    'id' => $userId,
];
$sql = 'UPDATE users SET name = :name, username = :username';

if ($password !== '') {
    $sql .= ', password = :password';
    $params['password'] = password_hash($password, PASSWORD_DEFAULT);
}

if ($profilePhotoPath !== null) {
    $sql .= ', profile_photo = :profile_photo';
    $params['profile_photo'] = $profilePhotoPath;
}

$sql .= ' WHERE id = :id';

dbQuery($sql, $params);

clearCurrentUserCache();

$updatedUser = currentUser(true);
$displayName = $updatedUser !== null ? userDisplayName($updatedUser) : $name;

setFlash('profile_success', 'Perfil atualizado com sucesso.');
setFlash('profile_display_name', $displayName);

if ($wantsJson) {
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode([
        'success' => true,
        'alreadySaved' => false,
        'message' => 'Perfil atualizado com sucesso.',
        'displayName' => $displayName,
        'profilePhotoUrl' => profilePhotoUrl($updatedUser),
        'csrfToken' => csrfToken(),
    ], JSON_THROW_ON_ERROR);
    exit;
}

redirect(pageUrl('profile.php'));
