<?php
declare(strict_types=1);

/** Course Tabs */
function courseTabs(): array {
    return [
        'info'            => 'Overview & Info',
        'specializations' => 'Specializations',
        'careers'         => 'Career & Jobs',
        'colleges'        => 'Top Colleges'
    ];
}

function courseUrl(string $slug, string $tab = 'info'): string {
    $base = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
    $url = $base . '/course/' . urlencode($slug);
    if ($tab !== 'info') {
        $url .= '/' . urlencode($tab);
    }
    return $url;
}

function coursesUrl(array $params = []): string {
    $base = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
    $url = $base . '/courses';
    if (!empty($params)) {
        $url .= '?' . http_build_query($params);
    }
    return $url;
}

function loadCourseBySlug(PDO $pdo, string $slug): ?array {
    $stmt = $pdo->prepare("SELECT * FROM courses WHERE course_slug = ? AND status = 'active' LIMIT 1");
    $stmt->execute([$slug]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ?: null;
}

function getCourseSpecializations(PDO $pdo, string $course_id): array {
    $stmt = $pdo->prepare("SELECT * FROM course_specializations WHERE parent_course_id = ? ORDER BY sort_order ASC, is_popular DESC");
    $stmt->execute([$course_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getCourseCareers(PDO $pdo, string $course_id): array {
    $stmt = $pdo->prepare("SELECT * FROM course_career_paths WHERE course_id = ? ORDER BY avg_salary_lpa DESC");
    $stmt->execute([$course_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getCollegesForCourse(PDO $pdo, string $course_name): array {
    $search = explode(' ', $course_name)[0]; // e.g., 'B.Tech'
    $stmt = $pdo->prepare("
        SELECT DISTINCT c.* 
        FROM college_courses cc
        JOIN colleges c ON c.id = cc.college_id
        WHERE cc.course_name LIKE ? AND c.status = 'active'
        ORDER BY c.overall_rating_avg DESC
        LIMIT 20
    ");
    $stmt->execute(['%' . $search . '%']);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
