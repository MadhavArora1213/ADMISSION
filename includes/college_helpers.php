<?php
declare(strict_types=1);

if (!function_exists('cCol')) {
    function cCol(PDO $pdo, string $sql, int $d = 0): int {
        try {
            $s = $pdo->query($sql);
            $v = $s->fetchColumn();
            return $v !== false && $v !== null ? (int)$v : $d;
        } catch (Exception $e) {
            return $d;
        }
    }
}

if (!function_exists('cAll')) {
    function cAll(PDO $pdo, string $sql): array {
        try {
            return $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return [];
        }
    }
}

if (!function_exists('cImg')) {
    function cImg(?string $url = ''): string {
        if (!$url) return 'https://images.unsplash.com/photo-1562774053-701939374585?w=800&q=80';
        if (str_starts_with($url, 'http') || str_starts_with($url, '//')) return $url;
        $base = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
        return $base . '/' . ltrim($url, '/');
    }
}

/** Frontend college detail tabs (Shiksha-style) */
function collegeTabs(): array {
    return [
        'info'           => 'College Info',
        'courses'        => 'Courses',
        'fees'           => 'Fees',
        'reviews'        => 'Reviews',
        'admissions'     => 'Admissions',
        'placements'     => 'Placements',
        'cutoffs'        => 'Cut-Offs',
        'seat_matrix'    => 'Seat Matrix',
        'rankings'       => 'Rankings',
        'gallery'        => 'Gallery',
        'infrastructure' => 'Infrastructure',
        'faculty'        => 'Faculty',
        'qna'            => 'Q&A',
        'news'           => 'News',
    ];
}

function collegeUrl(string $slug, string $tab = 'info'): string {
    $base = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
    $url = $base . '/college/' . urlencode($slug);
    if ($tab !== 'info') {
        $url .= '/' . urlencode($tab);
    }
    return $url;
}

function collegesUrl(array $params = []): string {
    $base = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
    $url = $base . '/colleges';
    if (!empty($params)) {
        $url .= '?' . http_build_query($params);
    }
    return $url;
}

function formatFee(?float $amount): string {
    if ($amount === null || $amount <= 0) {
        return '—';
    }
    if ($amount >= 100000) {
        return '₹' . number_format($amount / 100000, 2) . ' L';
    }
    return '₹' . number_format($amount, 0);
}

function formatLpa(?float $lpa): string {
    if ($lpa === null || $lpa <= 0) {
        return '—';
    }
    return '₹' . number_format($lpa, 2) . ' LPA';
}

function collegeTypeLabel(?string $type, ?string $ownership = null): string {
    $map = [
        'govt' => 'Government',
        'private' => 'Private',
        'deemed' => 'Deemed University',
        'autonomous' => 'Autonomous',
    ];
    $base = $map[$type ?? ''] ?? ($type ? ucfirst($type) : 'Institute');
    if ($ownership === 'central' || $type === 'govt') {
        return 'Public/Government Institute';
    }
    if ($ownership === 'state') {
        return 'State Government Institute';
    }
    return $base . ' Institute';
}

function loadCollegeBySlug(PDO $pdo, string $slug): ?array {
    $stmt = $pdo->prepare("
        SELECT c.*,
               s.name AS state_name, ci.name AS city_name,
               u.name AS university_name,
               cm.logo_url, cm.cover_image_url,
               cc.email, cc.phone, cc.address, cc.website_url, cc.pincode,
               cc.latitude, cc.longitude, cc.google_maps_embed_url,
               ct.about_text, ct.highlights_json, ct.accreditations_json, ct.rankings_json, ct.awards_json,
               ca.admission_process, ca.accepted_exams, ca.admission_start_date, ca.admission_end_date,
               ca.merit_based, ca.direct_admission, ca.management_quota_seats, ca.nri_quota_seats,
               ca.lateral_entry_available, ca.application_mode, ca.selection_criteria,
               inf.library, inf.auditorium, inf.cafeteria, inf.wifi, inf.medical_facility, inf.transport,
               inf.ev_charging, inf.solar_power, inf.sports_facilities, inf.labs,
               h.hostel_available, h.hostel_type, h.hostel_capacity, h.hostel_fee_annual,
               h.mess_available, h.mess_type, h.ac_available, h.laundry_available,
               sm.meta_title, sm.meta_description, sm.canonical_url
        FROM colleges c
        LEFT JOIN states s ON c.state_id = s.id
        LEFT JOIN cities ci ON c.city_id = ci.id
        LEFT JOIN universities u ON c.university_id = u.id
        LEFT JOIN college_media cm ON cm.college_id = c.id AND (cm.image_type IS NULL OR cm.image_type = 'cover')
        LEFT JOIN college_contacts cc ON cc.college_id = c.id
        LEFT JOIN college_content ct ON ct.college_id = c.id
        LEFT JOIN college_admissions ca ON ca.college_id = c.id
        LEFT JOIN college_infrastructure inf ON inf.college_id = c.id
        LEFT JOIN college_hostels h ON h.college_id = c.id
        LEFT JOIN seo_meta sm ON sm.page_id = c.id AND sm.page_type = 'college'
        WHERE c.slug = ? AND c.status = 'active'
        LIMIT 1
    ");
    $stmt->execute([$slug]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ?: null;
}

function collegeRatingBreakdown(PDO $pdo, string $collegeId): array {
    $stmt = $pdo->prepare("
        SELECT
            ROUND(AVG(placements_rating), 1) AS placements,
            ROUND(AVG(infrastructure_rating), 1) AS infrastructure,
            ROUND(AVG(faculty_rating), 1) AS faculty,
            ROUND(AVG(social_life_rating), 1) AS campus_life,
            ROUND(AVG(academics_rating), 1) AS value_money,
            ROUND(AVG(overall_rating), 1) AS overall,
            COUNT(*) AS count
        FROM reviews
        WHERE college_id = ? AND moderation_status = 'approved'
    ");
    $stmt->execute([$collegeId]);
    $r = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

    if (empty($r['count'])) {
        $avg = (float)($r['overall'] ?? 0);
        return [
            'placements' => $avg,
            'infrastructure' => $avg,
            'faculty' => $avg,
            'campus_life' => $avg,
            'value_money' => $avg,
            'overall' => $avg,
            'count' => 0,
        ];
    }
    return $r;
}

if (!function_exists('jsonLines')) {
    function jsonLines(?string $json): array {
        if (empty($json)) {
            return [];
        }
        $data = json_decode($json, true);
        return is_array($data) ? $data : [];
    }
}

function generateUuid(): string {
    return sprintf(
        '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        mt_rand(0, 0xffff), mt_rand(0, 0xffff),
        mt_rand(0, 0xffff), mt_rand(0, 0x0fff) | 0x4000,
        mt_rand(0, 0x3fff) | 0x8000,
        mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
    );
}
