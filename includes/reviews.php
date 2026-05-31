<?php

declare(strict_types=1);

/**
 * Review permission and lookup helpers.
 */

/**
 * Returns true when the user may submit class reviews.
 */
function userCanLeaveReviews(int $userId): bool
{
    $user = dbQuery(
        'SELECT role, active, reviews_blocked FROM users WHERE id = :id',
        ['id' => $userId]
    )->fetch();

    if ($user === false) {
        return false;
    }

    if ((int) $user['active'] !== 1) {
        return false;
    }

    if ($user['role'] !== 'member') {
        return false;
    }

    return (int) ($user['reviews_blocked'] ?? 0) !== 1;
}

/**
 * Returns true when the user already reviewed the given class.
 */
function userHasClassReview(int $userId, int $classId): bool
{
    return dbQuery(
        'SELECT id FROM reviews WHERE user_id = :uid AND class_id = :cid',
        ['uid' => $userId, 'cid' => $classId]
    )->fetch() !== false;
}

/**
 * Returns true when the user is enrolled in the given class.
 */
function userIsEnrolledInClass(int $userId, int $classId): bool
{
    return dbQuery(
        'SELECT id FROM enrollments WHERE user_id = :uid AND class_id = :cid',
        ['uid' => $userId, 'cid' => $classId]
    )->fetch() !== false;
}
