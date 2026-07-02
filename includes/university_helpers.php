<?php
declare(strict_types=1);

function universityTabs(): array {
    return [
        'info'           => 'University Info',
        'courses'        => 'Courses & Fees',
        'placements'     => 'Placements',
        'cutoffs'        => 'Cut-Offs',
        'admissions'     => 'Admissions',
        'infrastructure' => 'Infrastructure',
        'faculty'        => 'Faculty',
        'scholarships'   => 'Scholarships',
        'gallery'        => 'Gallery',
        'faqs'           => 'FAQs',
    ];
}

function universityUrl(string $slug, string $tab = 'info'): string {
    $base = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
    $url = $base . '/university/' . urlencode($slug);
    if ($tab !== 'info') {
        $url .= '/' . urlencode($tab);
    }
    return $url;
}

function universitiesUrl(array $params = []): string {
    $base = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
    $url = $base . '/universities';
    if (!empty($params)) {
        $url .= '?' . http_build_query($params);
    }
    return $url;
}

function universityTypeLabel(?string $type, ?string $ownership = null): string {
    $map = [
        'govt' => 'Government University',
        'private' => 'Private University',
        'deemed' => 'Deemed University',
        'autonomous' => 'Autonomous University',
    ];
    $base = $map[$type ?? ''] ?? ($type ? ucfirst($type) : 'University');
    if ($ownership === 'central') return 'Central University';
    if ($ownership === 'state') return 'State University';
    return $base;
}

function loadUniversityBySlug(PDO $pdo, string $slug): ?array {
    $stmt = $pdo->prepare("
        SELECT u.*,
               s.name AS state_name, ci.name AS city_name,
               uc.website_url, uc.email, uc.phone, uc.address, uc.pincode,
               uc.latitude, uc.longitude, uc.google_maps_embed_url,
               content.about_text, content.highlights_json, content.accreditations_json, content.rankings_json, content.awards_json,
               adm.admission_process, adm.accepted_exams, adm.admission_start_date, adm.admission_end_date,
               adm.merit_based, adm.direct_admission, adm.management_quota_seats, adm.nri_quota_seats,
               adm.lateral_entry_available, adm.application_mode, adm.selection_criteria,
               inf.library, inf.library_books_count, inf.auditorium, inf.auditorium_capacity,
               inf.cafeteria, inf.wifi, inf.wifi_speed_mbps, inf.medical_facility, inf.transport,
               inf.ev_charging, inf.solar_power, inf.sports_facilities, inf.labs,
               h.hostel_available, h.hostel_type, h.hostel_capacity, h.hostel_fee_annual,
               h.mess_available, h.mess_type, h.ac_available, h.laundry_available
        FROM universities u
        LEFT JOIN states s ON u.state_id = s.id
        LEFT JOIN cities ci ON u.city_id = ci.id
        LEFT JOIN university_contacts uc ON uc.university_id = u.id
        LEFT JOIN university_content content ON content.university_id = u.id
        LEFT JOIN university_admissions adm ON adm.university_id = u.id
        LEFT JOIN university_infrastructure inf ON inf.university_id = u.id
        LEFT JOIN university_hostels h ON h.university_id = u.id
        WHERE u.slug = ? AND u.status = 'active'
        LIMIT 1
    ");
    $stmt->execute([$slug]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ?: null;
}
