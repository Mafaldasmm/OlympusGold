<?php

declare(strict_types=1);

/**
 * Class waitlist helpers (active members only).
 */

/**
 * SQL fragment: waitlist rows joined to eligible members only.
 */
function waitlistMemberJoinSql(): string
{
    return 'FROM class_waitlist w
            INNER JOIN users u ON u.id = w.user_id AND u.role = \'member\' AND u.active = 1';
}

/**
 * Removes waitlist rows for non-members or inactive users.
 */
function purgeInvalidWaitlistEntries(?PDO $pdo = null): void
{
    $pdo = $pdo ?? getDatabase();
    $pdo->exec(
        'DELETE FROM class_waitlist
         WHERE user_id IN (
             SELECT id FROM users WHERE role != \'member\' OR active != 1
         )'
    );
}

/**
 * @return array<string, mixed>|null
 */
function fetchActiveMemberForWaitlist(int $userId): ?array
{
    $row = dbQuery(
        'SELECT id, role, active FROM users WHERE id = :id',
        ['id' => $userId]
    )->fetch();

    if ($row === false || $row['role'] !== 'member' || (int) $row['active'] !== 1) {
        return null;
    }

    return $row;
}

/**
 * @param list<string> $errors
 */
function userCanJoinClassWaitlist(int $userId, array &$errors): bool
{
    if (fetchActiveMemberForWaitlist($userId) === null) {
        $errors[] = 'Apenas membros ativos podem entrar na lista de espera.';
        return false;
    }

    return true;
}

/**
 * @return list<array<string, mixed>>
 */
function fetchClassWaitlist(int $classId): array
{
    return dbQuery(
        'SELECT w.id, w.user_id, w.class_id, w.created_at,
                u.name AS member_name, u.username
         ' . waitlistMemberJoinSql() . '
         WHERE w.class_id = :class_id
         ORDER BY w.created_at ASC',
        ['class_id' => $classId]
    )->fetchAll();
}

/**
 * Returns 1-based waitlist position for a member on a class, or null if not waitlisted.
 */
function memberWaitlistPosition(int $classId, int $userId): ?int
{
    $entries = dbQuery(
        'SELECT w.user_id
         ' . waitlistMemberJoinSql() . '
         WHERE w.class_id = :class_id
         ORDER BY w.created_at ASC',
        ['class_id' => $classId]
    )->fetchAll();

    $position = 1;
    foreach ($entries as $entry) {
        if ((int) $entry['user_id'] === $userId) {
            return $position;
        }
        $position++;
    }

    return null;
}

function isMemberOnClassWaitlist(int $classId, int $userId): bool
{
    return memberWaitlistPosition($classId, $userId) !== null;
}

function classWaitlistCount(int $classId): int
{
    return (int) dbQuery(
        'SELECT COUNT(*)
         ' . waitlistMemberJoinSql() . '
         WHERE w.class_id = :class_id',
        ['class_id' => $classId]
    )->fetchColumn();
}

/**
 * @return array<int, int> class_id => 1-based position
 */
function fetchMemberWaitlistPositions(int $userId): array
{
    if (fetchActiveMemberForWaitlist($userId) === null) {
        return [];
    }

    $rows = dbQuery(
        'SELECT w.class_id
         ' . waitlistMemberJoinSql() . '
         WHERE w.user_id = :user_id',
        ['user_id' => $userId]
    )->fetchAll();

    $map = [];
    foreach ($rows as $row) {
        $classId = (int) $row['class_id'];
        $pos = memberWaitlistPosition($classId, $userId);
        if ($pos !== null) {
            $map[$classId] = $pos;
        }
    }

    return $map;
}

/**
 * Promotes the first waitlisted active member if a spot is available.
 *
 * @return array{promoted: bool, memberName: string|null, userId: int|null}
 */
function promoteNextFromWaitlist(int $classId, ?PDO $pdo = null): array
{
    $ownTransaction = $pdo === null;
    $pdo = $pdo ?? getDatabase();

    if ($ownTransaction) {
        $pdo->beginTransaction();
    }

    try {
        $class = $pdo->prepare('SELECT id, capacity FROM classes WHERE id = :id');
        $class->execute(['id' => $classId]);
        $classRow = $class->fetch();

        if (!$classRow) {
            if ($ownTransaction) {
                $pdo->rollBack();
            }
            return ['promoted' => false, 'memberName' => null, 'userId' => null];
        }

        $countStmt = $pdo->prepare('SELECT COUNT(*) FROM enrollments WHERE class_id = :cid');
        $countStmt->execute(['cid' => $classId]);
        $enrolled = (int) $countStmt->fetchColumn();

        if ($enrolled >= (int) $classRow['capacity']) {
            if ($ownTransaction) {
                $pdo->commit();
            }
            return ['promoted' => false, 'memberName' => null, 'userId' => null];
        }

        $nextStmt = $pdo->prepare(
            'SELECT w.id AS waitlist_id, w.user_id, u.name, u.username
             FROM class_waitlist w
             INNER JOIN users u ON u.id = w.user_id AND u.role = \'member\' AND u.active = 1
             WHERE w.class_id = :class_id
             ORDER BY w.created_at ASC
             LIMIT 1'
        );
        $nextStmt->execute(['class_id' => $classId]);
        $next = $nextStmt->fetch();

        if ($next === false) {
            if ($ownTransaction) {
                $pdo->commit();
            }
            return ['promoted' => false, 'memberName' => null, 'userId' => null];
        }

        $userId = (int) $next['user_id'];
        $memberName = trim((string) ($next['name'] ?? ''));
        if ($memberName === '') {
            $memberName = (string) $next['username'];
        }

        $pdo->prepare('INSERT INTO enrollments (user_id, class_id) VALUES (:uid, :cid)')
            ->execute(['uid' => $userId, 'cid' => $classId]);

        $pdo->prepare('DELETE FROM class_waitlist WHERE id = :id')
            ->execute(['id' => (int) $next['waitlist_id']]);

        if ($ownTransaction) {
            $pdo->commit();
        }

        return ['promoted' => true, 'memberName' => $memberName, 'userId' => $userId];
    } catch (Throwable $e) {
        if ($ownTransaction && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }
}

/**
 * Adds positions (1-based) to waitlist rows for display.
 *
 * @param list<array<string, mixed>> $rows
 * @return list<array<string, mixed>>
 */
function annotateWaitlistPositions(array $rows): array
{
    $position = 1;
    foreach ($rows as &$row) {
        $row['position'] = $position;
        $position++;
    }
    unset($row);

    return $rows;
}
