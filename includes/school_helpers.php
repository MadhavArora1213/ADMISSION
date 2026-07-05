<?php
declare(strict_types=1);

if (!function_exists('schoolUrl')) {
    function schoolUrl(string $slug, string $tab = 'overview'): string {
        $base = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
        $url = $base . '/school/' . urlencode($slug);
        if ($tab !== 'overview') {
            $url .= '/' . urlencode($tab);
        }
        return $url;
    }
}

if (!function_exists('schoolsUrl')) {
    function schoolsUrl(array $params = []): string {
        $base = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
        $url = $base . '/schools';
        if (!empty($params)) {
            $url .= '?' . http_build_query($params);
        }
        return $url;
    }
}

if (!function_exists('schoolTabs')) {
    function schoolTabs(): array {
        return [
            'overview'        => 'Overview',
            'courses'         => 'Courses & Fees',
            'admissions'      => 'Admissions',
            'infrastructure'  => 'Infrastructure',
            'reviews'         => 'Reviews',
            'news'            => 'News & Updates',
        ];
    }
}

if (!function_exists('schoolTypeLabel')) {
    function schoolTypeLabel(?string $type): string {
        $map = [
            'govt'          => 'Government School',
            'private'       => 'Private School',
            'aided'         => 'Aided School',
            'unaided'       => 'Unaided School',
            'international' => 'International School',
            'boarding'      => 'Boarding School',
        ];
        return $map[$type ?? ''] ?? ($type ? ucfirst($type) . ' School' : 'School');
    }
}

if (!function_exists('schoolBoardLabel')) {
    function schoolBoardLabel(?string $board): string {
        $map = [
            'CBSE'  => 'CBSE',
            'ICSE'  => 'ICSE',
            'State' => 'State Board',
            'IB'    => 'IB (International Baccalaureate)',
            'IGCSE' => 'IGCSE',
            'NIOS'  => 'NIOS',
        ];
        return $map[$board ?? ''] ?? ($board ? ucfirst($board) : '');
    }
}

if (!function_exists('loadSchoolBySlug')) {
    function loadSchoolBySlug(PDO $pdo, string $slug): ?array {
        $stmt = $pdo->prepare("
            SELECT s.*,
                   st.name AS state_name, ci.name AS city_name,
                   sm.logo_url, sm.cover_image_url,
                   sc.email, sc.phone, sc.address, sc.website_url, sc.pincode,
                   sc.latitude, sc.longitude, sc.google_maps_embed_url,
                   sct.about_text, sct.highlights_json, sct.admission_process,
                   sct.accepted_exams, sct.admission_start_date, sct.admission_end_date,
                   si.library, si.auditorium, si.cafeteria, si.wifi, si.medical_facility,
                   si.transport, si.playground, si.swimming_pool, si.labs, si.smart_classrooms
            FROM schools s
            LEFT JOIN states st ON s.state_id = st.id
            LEFT JOIN cities ci ON s.city_id = ci.id
            LEFT JOIN school_media sm ON sm.school_id = s.id AND sm.image_type IS NULL
            LEFT JOIN school_contacts sc ON sc.school_id = s.id
            LEFT JOIN school_content sct ON sct.school_id = s.id
            LEFT JOIN school_infrastructure si ON si.school_id = s.id
            WHERE s.slug = ? AND s.status = 'active'
            LIMIT 1
        ");
        $stmt->execute([$slug]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }
}
