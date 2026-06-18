<?php
declare(strict_types=1);

if (!function_exists('cImg')) {
    function cImg(?string $url = ''): string {
        return $url ?: 'https://images.unsplash.com/photo-1562774053-701939374585?w=800&q=80';
    }
}

if (!function_exists('jsonLines')) {
    function jsonLines(?string $json): array {
        if (empty($json)) return [];
        $data = json_decode($json, true);
        return is_array($data) ? $data : [];
    }
}

/** Exams Tabs */
function examTabs(): array {
    return [
        'info'     => 'Exam Info',
        'dates'    => 'Important Dates',
        'pattern'  => 'Exam Pattern',
        'syllabus' => 'Syllabus',
        'fees'     => 'Application Fees',
        'cutoffs'  => 'Cut-Offs'
    ];
}

function examUrl(string $slug, string $tab = 'info'): string {
    $base = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
    $url = $base . '/exam/' . urlencode($slug);
    if ($tab !== 'info') {
        $url .= '/' . urlencode($tab);
    }
    return $url;
}

function examsUrl(array $params = []): string {
    $base = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
    $url = $base . '/exams';
    if (!empty($params)) {
        $url .= '?' . http_build_query($params);
    }
    return $url;
}

function loadExamBySlug(PDO $pdo, string $slug): ?array {
    $stmt = $pdo->prepare("SELECT * FROM exams WHERE exam_slug = ? AND status != 'cancelled' LIMIT 1");
    $stmt->execute([$slug]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ?: null;
}

function getExamDates(PDO $pdo, string $exam_id): array {
    $stmt = $pdo->prepare("SELECT * FROM exam_dates WHERE exam_id = ? ORDER BY event_date ASC");
    $stmt->execute([$exam_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getExamSyllabus(PDO $pdo, string $exam_id): array {
    $stmt = $pdo->prepare("SELECT * FROM exam_syllabus WHERE exam_id = ? ORDER BY subject ASC, weightage_pct DESC");
    $stmt->execute([$exam_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getExamCutoffs(PDO $pdo, string $exam_id): array {
    $stmt = $pdo->prepare("
        SELECT ec.*, c.name as college_name, c.slug as college_slug, crs.course_name 
        FROM exam_cutoffs ec
        LEFT JOIN colleges c ON c.id = ec.college_id
        LEFT JOIN courses crs ON crs.id = ec.course_id
        WHERE ec.exam_id = ?
        ORDER BY ec.year DESC, ec.opening_rank ASC
    ");
    $stmt->execute([$exam_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
