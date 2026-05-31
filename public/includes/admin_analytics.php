<?php

declare(strict_types=1);

require_once __DIR__ . '/equipment.php';

/**
 * Gym-wide admin analytics (no trainer_id filter).
 *
 * See trainer_analytics.php for trainer-scoped metric definitions; same formulas apply per class.
 */

function gymClassUtilizationPercent(int $enrolled, int $capacity): int
{
    if ($capacity <= 0) {
        return 0;
    }

    return (int) min(100, round(($enrolled / $capacity) * 100));
}

function formatAdminAvgRating(?float $avgRating, int $reviewCount): string
{
    if ($reviewCount === 0 || $avgRating === null) {
        return 'N/D';
    }

    return number_format($avgRating, 1, ',', '') . ' / 5';
}

/**
 * @return array{active_members: int, total_enrollments: int, total_reviews: int, total_classes: int}
 */
function fetchGymMemberActivity(PDO $pdo): array
{
    return [
        'active_members'    => (int) $pdo->query(
            "SELECT COUNT(*) FROM users WHERE role = 'member' AND active = 1"
        )->fetchColumn(),
        'total_enrollments' => (int) $pdo->query('SELECT COUNT(*) FROM enrollments')->fetchColumn(),
        'total_reviews'     => (int) $pdo->query('SELECT COUNT(*) FROM reviews')->fetchColumn(),
        'total_classes'     => (int) $pdo->query('SELECT COUNT(*) FROM classes')->fetchColumn(),
    ];
}

/**
 * @return array{avg_rating: ?float, review_count: int}
 */
function fetchGymReviewSummary(PDO $pdo): array
{
    $row         = $pdo->query('SELECT AVG(rating) AS avg_rating, COUNT(*) AS review_count FROM reviews')->fetch();
    $reviewCount = (int) ($row['review_count'] ?? 0);

    return [
        'avg_rating'   => $reviewCount > 0 && $row['avg_rating'] !== null
            ? (float) $row['avg_rating']
            : null,
        'review_count' => $reviewCount,
    ];
}

/**
 * @return list<array{id: int, title: string, enrolled_count: int}>
 */
function fetchPopularClasses(PDO $pdo, int $limit = 5): array
{
    $stmt = $pdo->prepare('
        SELECT c.id, c.title, COUNT(e.id) AS enrolled_count
        FROM classes c
        LEFT JOIN enrollments e ON e.class_id = c.id
        GROUP BY c.id
        ORDER BY enrolled_count DESC, c.title COLLATE NOCASE ASC
        LIMIT :limit
    ');
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();

    $popular = [];
    foreach ($stmt->fetchAll() as $row) {
        $popular[] = [
            'id'             => (int) $row['id'],
            'title'          => (string) $row['title'],
            'enrolled_count' => (int) $row['enrolled_count'],
        ];
    }

    return $popular;
}

/**
 * @return list<array<string, mixed>>
 */
function fetchGymClassMetrics(PDO $pdo): array
{
    $stmt = $pdo->query('
        SELECT
            c.id,
            c.title,
            c.type,
            c.day_of_week,
            c.start_time,
            c.end_time,
            c.capacity,
            c.trainer_id,
            u.name AS trainer_name,
            (SELECT COUNT(*) FROM enrollments e WHERE e.class_id = c.id) AS enrolled_count,
            (SELECT AVG(r.rating) FROM reviews r WHERE r.class_id = c.id) AS avg_rating,
            (SELECT COUNT(*) FROM reviews r WHERE r.class_id = c.id) AS review_count
        FROM classes c
        LEFT JOIN users u ON u.id = c.trainer_id
        ORDER BY enrolled_count DESC, c.title COLLATE NOCASE ASC
    ');

    $classes = [];
    foreach ($stmt->fetchAll() as $row) {
        $enrolled    = (int) $row['enrolled_count'];
        $capacity    = (int) $row['capacity'];
        $reviewCount = (int) $row['review_count'];

        $classes[] = [
            'id'              => (int) $row['id'],
            'title'           => (string) $row['title'],
            'type'            => (string) $row['type'],
            'day_of_week'     => (string) $row['day_of_week'],
            'start_time'      => (string) $row['start_time'],
            'end_time'        => (string) $row['end_time'],
            'capacity'        => $capacity,
            'trainer_id'      => $row['trainer_id'] !== null ? (int) $row['trainer_id'] : null,
            'trainer_name'    => (string) ($row['trainer_name'] ?? ''),
            'enrolled_count'  => $enrolled,
            'utilization_pct' => gymClassUtilizationPercent($enrolled, $capacity),
            'review_count'    => $reviewCount,
            'avg_rating'      => $reviewCount > 0 && $row['avg_rating'] !== null
                ? (float) $row['avg_rating']
                : null,
        ];
    }

    return $classes;
}

/**
 * @return list<array{id: int, title: string, avg_rating: float, review_count: int}>
 */
function fetchClassesByRating(PDO $pdo, string $order, int $limit = 5): array
{
    $direction = strtolower($order) === 'asc' ? 'ASC' : 'DESC';

    $stmt = $pdo->prepare("
        SELECT
            c.id,
            c.title,
            AVG(r.rating) AS avg_rating,
            COUNT(r.id) AS review_count
        FROM classes c
        INNER JOIN reviews r ON r.class_id = c.id
        GROUP BY c.id
        HAVING review_count > 0
        ORDER BY avg_rating {$direction}, review_count DESC, c.title COLLATE NOCASE ASC
        LIMIT :limit
    ");
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();

    $rows = [];
    foreach ($stmt->fetchAll() as $row) {
        $rows[] = [
            'id'           => (int) $row['id'],
            'title'        => (string) $row['title'],
            'avg_rating'   => (float) $row['avg_rating'],
            'review_count' => (int) $row['review_count'],
        ];
    }

    return $rows;
}

/**
 * @return array{available: int, in_use: int, maintenance: int, total: int}
 */
function fetchGymEquipmentStatusBreakdown(PDO $pdo): array
{
    $counts = ['available' => 0, 'in_use' => 0, 'maintenance' => 0];

    $stmt = $pdo->prepare('
        SELECT status, COUNT(*) AS item_count
        FROM equipment
        WHERE area = :area
        GROUP BY status
    ');
    $stmt->execute(['area' => EQUIPMENT_AREA_MAIN]);

    foreach ($stmt->fetchAll() as $row) {
        $status = equipmentNormalizeStatus((string) $row['status']);
        if (isset($counts[$status])) {
            $counts[$status] = (int) $row['item_count'];
        }
    }

    return [
        'available'   => $counts['available'],
        'in_use'      => $counts['in_use'],
        'maintenance' => $counts['maintenance'],
        'total'       => $counts['available'] + $counts['in_use'] + $counts['maintenance'],
    ];
}

/**
 * @return array{
 *     activity: array<string, int>,
 *     reviews: array{avg_rating: ?float, review_count: int},
 *     popular_classes: list<array<string, mixed>>,
 *     classes: list<array<string, mixed>>,
 *     top_rated_classes: list<array<string, mixed>>,
 *     low_rated_classes: list<array<string, mixed>>,
 *     equipment: array<string, int>,
 *     summary: array{avg_utilization_pct: int, total_capacity: int, total_enrolled: int}
 * }
 */
function loadAdminAnalytics(PDO $pdo): array
{
    $classes  = fetchGymClassMetrics($pdo);
    $activity = fetchGymMemberActivity($pdo);
    $reviews  = fetchGymReviewSummary($pdo);

    $totalCapacity  = 0;
    $totalEnrolled  = 0;
    $utilizationSum = 0;

    foreach ($classes as $class) {
        $totalCapacity  += $class['capacity'];
        $totalEnrolled  += $class['enrolled_count'];
        $utilizationSum += $class['utilization_pct'];
    }

    $classCount = count($classes);

    return [
        'activity'          => $activity,
        'reviews'           => $reviews,
        'popular_classes'   => fetchPopularClasses($pdo, 5),
        'classes'           => $classes,
        'top_rated_classes' => fetchClassesByRating($pdo, 'desc', 5),
        'low_rated_classes' => fetchClassesByRating($pdo, 'asc', 5),
        'equipment'         => fetchGymEquipmentStatusBreakdown($pdo),
        'summary'           => [
            'avg_utilization_pct' => $classCount > 0
                ? (int) round($utilizationSum / $classCount)
                : 0,
            'total_capacity'      => $totalCapacity,
            'total_enrolled'      => $totalEnrolled,
        ],
    ];
}
