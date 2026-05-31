<?php

declare(strict_types=1);

/**
 * Trainer analytics helpers — metrics scoped to classes.trainer_id = logged-in trainer.
 *
 * Metric definitions:
 * - enrolled_count: COUNT(enrollments) for the class (active bookings).
 * - capacity: classes.capacity (max spots).
 * - utilization_pct: min(100, round(enrolled_count / capacity * 100)); 0 if capacity is 0.
 * - avg_rating: AVG(reviews.rating) for the class; null when review_count is 0.
 * - review_count: COUNT(reviews) for the class.
 * - summary avg_rating: AVG across all reviews on the trainer's classes (not mean of class averages).
 */

/**
 * Capacity utilization percentage, capped at 100%.
 */
function trainerClassUtilizationPercent(int $enrolled, int $capacity): int
{
    if ($capacity <= 0) {
        return 0;
    }

    return (int) min(100, round(($enrolled / $capacity) * 100));
}

/**
 * Per-class analytics for one trainer (no cross-trainer data).
 *
 * @return list<array<string, mixed>>
 */
function fetchTrainerClassAnalytics(PDO $pdo, int $trainerId): array
{
    $stmt = $pdo->prepare('
        SELECT
            c.id,
            c.title,
            c.type,
            c.day_of_week,
            c.start_time,
            c.end_time,
            c.capacity,
            (SELECT COUNT(*) FROM enrollments e WHERE e.class_id = c.id) AS enrolled_count,
            (SELECT AVG(r.rating) FROM reviews r WHERE r.class_id = c.id) AS avg_rating,
            (SELECT COUNT(*) FROM reviews r WHERE r.class_id = c.id) AS review_count
        FROM classes c
        WHERE c.trainer_id = :trainer_id
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
    ');
    $stmt->execute(['trainer_id' => $trainerId]);
    $rows = $stmt->fetchAll();

    $classes = [];
    foreach ($rows as $row) {
        $enrolled     = (int) $row['enrolled_count'];
        $capacity     = (int) $row['capacity'];
        $reviewCount  = (int) $row['review_count'];
        $avgRatingRaw = $row['avg_rating'];

        $classes[] = [
            'id'               => (int) $row['id'],
            'title'            => (string) $row['title'],
            'type'             => (string) $row['type'],
            'day_of_week'      => (string) $row['day_of_week'],
            'start_time'       => (string) $row['start_time'],
            'end_time'         => (string) $row['end_time'],
            'capacity'         => $capacity,
            'enrolled_count'   => $enrolled,
            'utilization_pct'  => trainerClassUtilizationPercent($enrolled, $capacity),
            'review_count'     => $reviewCount,
            'avg_rating'       => $reviewCount > 0 && $avgRatingRaw !== null
                ? (float) $avgRatingRaw
                : null,
        ];
    }

    return $classes;
}

/**
 * Enrolled members grouped by class id (trainer's classes only).
 *
 * @return array<int, list<array<string, mixed>>>
 */
function fetchTrainerClassEnrollmentsByClass(PDO $pdo, int $trainerId): array
{
    $stmt = $pdo->prepare('
        SELECT
            c.id AS class_id,
            u.id,
            u.name,
            u.username,
            u.email,
            e.created_at
        FROM classes c
        INNER JOIN enrollments e ON e.class_id = c.id
        JOIN users u ON u.id = e.user_id
        WHERE c.trainer_id = :trainer_id
        ORDER BY c.id, u.name
    ');
    $stmt->execute(['trainer_id' => $trainerId]);

    $byClass = [];
    foreach ($stmt->fetchAll() as $row) {
        $classId = (int) $row['class_id'];
        if (!isset($byClass[$classId])) {
            $byClass[$classId] = [];
        }
        $byClass[$classId][] = [
            'id'         => (int) $row['id'],
            'name'       => (string) ($row['name'] ?? ''),
            'username'   => (string) $row['username'],
            'email'      => (string) ($row['email'] ?? ''),
            'created_at' => (string) $row['created_at'],
        ];
    }

    return $byClass;
}

/**
 * Aggregate review stats for all classes assigned to the trainer.
 *
 * @return array{avg_rating: ?float, review_count: int}
 */
function fetchTrainerReviewSummary(PDO $pdo, int $trainerId): array
{
    $stmt = $pdo->prepare('
        SELECT AVG(r.rating) AS avg_rating, COUNT(r.id) AS review_count
        FROM reviews r
        INNER JOIN classes c ON c.id = r.class_id
        WHERE c.trainer_id = :trainer_id
    ');
    $stmt->execute(['trainer_id' => $trainerId]);
    $row = $stmt->fetch();

    $reviewCount = (int) ($row['review_count'] ?? 0);

    return [
        'avg_rating'   => $reviewCount > 0 && $row['avg_rating'] !== null
            ? (float) $row['avg_rating']
            : null,
        'review_count' => $reviewCount,
    ];
}

/**
 * Full analytics payload for the trainer analytics page.
 *
 * @return array{
 *     classes: list<array<string, mixed>>,
 *     summary: array{
 *         class_count: int,
 *         total_enrollments: int,
 *         avg_utilization_pct: int,
 *         avg_rating: ?float,
 *         review_count: int
 *     }
 * }
 */
function loadTrainerAnalytics(PDO $pdo, int $trainerId): array
{
    $classes = fetchTrainerClassAnalytics($pdo, $trainerId);
    $reviews = fetchTrainerReviewSummary($pdo, $trainerId);

    $totalEnrollments = 0;
    $utilizationSum   = 0;

    foreach ($classes as $class) {
        $totalEnrollments += $class['enrolled_count'];
        $utilizationSum   += $class['utilization_pct'];
    }

    $classCount = count($classes);

    return [
        'classes' => $classes,
        'summary' => [
            'class_count'           => $classCount,
            'total_enrollments'     => $totalEnrollments,
            'avg_utilization_pct'   => $classCount > 0
                ? (int) round($utilizationSum / $classCount)
                : 0,
            'avg_rating'            => $reviews['avg_rating'],
            'review_count'          => $reviews['review_count'],
        ],
    ];
}

/**
 * Formats average rating for display, or a not-available label when there are no reviews.
 */
function formatTrainerAvgRating(?float $avgRating, int $reviewCount): string
{
    if ($reviewCount === 0 || $avgRating === null) {
        return 'N/D';
    }

    return number_format($avgRating, 1, ',', '') . ' / 5';
}
