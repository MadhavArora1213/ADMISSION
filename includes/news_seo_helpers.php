<?php
/**
 * News SEO Helpers
 * Dynamic SEO & GEO extraction for each news article
 */

/**
 * Auto-detect base URL: localhost for dev, production domain for live
 */
function getBaseUrl(): string {
    $host = $_SERVER['HTTP_HOST'] ?? '';
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';

    // Localhost / 127.0.0.1 / ::1
    if ($host === 'localhost' || $host === '127.0.0.1' || $host === '[::1]' || str_starts_with($host, 'localhost:')) {
        return $scheme . '://' . $host . '/ADMISSION';
    }

    // Production
    return 'https://admissionseason.com';
}

/**
 * Indian states and cities with their GEO coordinates
 */
function getGeoLocations(): array {
    return [
        // States
        'andhra pradesh'     => ['lat' => '15.9129', 'lng' => '79.7400', 'region' => 'IN-AP', 'placename' => 'Andhra Pradesh, India'],
        'arunachal pradesh'  => ['lat' => '28.2180', 'lng' => '94.7278', 'region' => 'IN-AR', 'placename' => 'Arunachal Pradesh, India'],
        'assam'              => ['lat' => '26.2006', 'lng' => '92.9376', 'region' => 'IN-AS', 'placename' => 'Assam, India'],
        'bihar'              => ['lat' => '25.0961', 'lng' => '85.3131', 'region' => 'IN-BR', 'placename' => 'Bihar, India'],
        'chhattisgarh'       => ['lat' => '21.2787', 'lng' => '81.8661', 'region' => 'IN-CT', 'placename' => 'Chhattisgarh, India'],
        'goa'                => ['lat' => '15.2993', 'lng' => '74.1240', 'region' => 'IN-GA', 'placename' => 'Goa, India'],
        'gujarat'            => ['lat' => '22.2587', 'lng' => '71.1924', 'region' => 'IN-GJ', 'placename' => 'Gujarat, India'],
        'haryana'            => ['lat' => '20.9517', 'lng' => '85.0985', 'region' => 'IN-HR', 'placename' => 'Haryana, India'],
        'himachal pradesh'   => ['lat' => '31.1048', 'lng' => '77.1734', 'region' => 'IN-HP', 'placename' => 'Himachal Pradesh, India'],
        'jharkhand'          => ['lat' => '23.6102', 'lng' => '85.2799', 'region' => 'IN-JH', 'placename' => 'Jharkhand, India'],
        'karnataka'          => ['lat' => '15.3173', 'lng' => '75.7139', 'region' => 'IN-KA', 'placename' => 'Karnataka, India'],
        'kerala'             => ['lat' => '10.8505', 'lng' => '76.2711', 'region' => 'IN-KL', 'placename' => 'Kerala, India'],
        'madhya pradesh'     => ['lat' => '22.9734', 'lng' => '78.6569', 'region' => 'IN-MP', 'placename' => 'Madhya Pradesh, India'],
        'maharashtra'        => ['lat' => '19.7515', 'lng' => '75.7139', 'region' => 'IN-MH', 'placename' => 'Maharashtra, India'],
        'manipur'            => ['lat' => '24.6637', 'lng' => '93.9063', 'region' => 'IN-MN', 'placename' => 'Manipur, India'],
        'meghalaya'          => ['lat' => '25.4670', 'lng' => '91.3662', 'region' => 'IN-ML', 'placename' => 'Meghalaya, India'],
        'mizoram'            => ['lat' => '23.1645', 'lng' => '92.9376', 'region' => 'IN-MZ', 'placename' => 'Mizoram, India'],
        'nagaland'           => ['lat' => '26.1581', 'lng' => '94.5624', 'region' => 'IN-NL', 'placename' => 'Nagaland, India'],
        'odisha'             => ['lat' => '20.9517', 'lng' => '85.0985', 'region' => 'IN-OR', 'placename' => 'Odisha, India'],
        'punjab'             => ['lat' => '31.1471', 'lng' => '75.3412', 'region' => 'IN-PB', 'placename' => 'Punjab, India'],
        'rajasthan'          => ['lat' => '27.0238', 'lng' => '74.2179', 'region' => 'IN-RJ', 'placename' => 'Rajasthan, India'],
        'sikkim'             => ['lat' => '27.5330', 'lng' => '88.5122', 'region' => 'IN-SK', 'placename' => 'Sikkim, India'],
        'tamil nadu'         => ['lat' => '11.1271', 'lng' => '78.6569', 'region' => 'IN-TN', 'placename' => 'Tamil Nadu, India'],
        'telangana'          => ['lat' => '18.1124', 'lng' => '79.0193', 'region' => 'IN-TG', 'placename' => 'Telangana, India'],
        'tripura'            => ['lat' => '23.9408', 'lng' => '91.9882', 'region' => 'IN-TR', 'placename' => 'Tripura, India'],
        'uttar pradesh'      => ['lat' => '26.8467', 'lng' => '80.9462', 'region' => 'IN-UP', 'placename' => 'Uttar Pradesh, India'],
        'uttarakhand'        => ['lat' => '30.0668', 'lng' => '79.0193', 'region' => 'IN-UT', 'placename' => 'Uttarakhand, India'],
        'west bengal'        => ['lat' => '22.9868', 'lng' => '87.8550', 'region' => 'IN-WB', 'placename' => 'West Bengal, India'],
        'delhi'              => ['lat' => '28.7041', 'lng' => '77.1025', 'region' => 'IN-DL', 'placename' => 'Delhi, India'],
        'jammu'              => ['lat' => '32.7266', 'lng' => '74.8570', 'region' => 'IN-JK', 'placename' => 'Jammu & Kashmir, India'],
        'kashmir'            => ['lat' => '34.0837', 'lng' => '74.7973', 'region' => 'IN-JK', 'placename' => 'Jammu & Kashmir, India'],
        'chandigarh'         => ['lat' => '30.7333', 'lng' => '76.7794', 'region' => 'IN-CH', 'placename' => 'Chandigarh, India'],
        'puducherry'         => ['lat' => '11.9416', 'lng' => '79.8083', 'region' => 'IN-PY', 'placename' => 'Puducherry, India'],

        // Major Cities
        'mumbai'             => ['lat' => '19.0760', 'lng' => '72.8777', 'region' => 'IN-MH', 'placename' => 'Mumbai, Maharashtra, India'],
        'delhi'              => ['lat' => '28.6139', 'lng' => '77.2090', 'region' => 'IN-DL', 'placename' => 'Delhi, India'],
        'new delhi'          => ['lat' => '28.6139', 'lng' => '77.2090', 'region' => 'IN-DL', 'placename' => 'New Delhi, India'],
        'bangalore'          => ['lat' => '12.9716', 'lng' => '77.5946', 'region' => 'IN-KA', 'placename' => 'Bangalore, Karnataka, India'],
        'bengaluru'          => ['lat' => '12.9716', 'lng' => '77.5946', 'region' => 'IN-KA', 'placename' => 'Bengaluru, Karnataka, India'],
        'hyderabad'          => ['lat' => '17.3850', 'lng' => '78.4867', 'region' => 'IN-TG', 'placename' => 'Hyderabad, Telangana, India'],
        'chennai'            => ['lat' => '13.0827', 'lng' => '80.2707', 'region' => 'IN-TN', 'placename' => 'Chennai, Tamil Nadu, India'],
        'kolkata'            => ['lat' => '22.5726', 'lng' => '88.3639', 'region' => 'IN-WB', 'placename' => 'Kolkata, West Bengal, India'],
        'pune'               => ['lat' => '18.5204', 'lng' => '73.8567', 'region' => 'IN-MH', 'placename' => 'Pune, Maharashtra, India'],
        'ahmedabad'          => ['lat' => '23.0225', 'lng' => '72.5714', 'region' => 'IN-GJ', 'placename' => 'Ahmedabad, Gujarat, India'],
        'jaipur'             => ['lat' => '26.9124', 'lng' => '75.7873', 'region' => 'IN-RJ', 'placename' => 'Jaipur, Rajasthan, India'],
        'lucknow'            => ['lat' => '26.8467', 'lng' => '80.9462', 'region' => 'IN-UP', 'placename' => 'Lucknow, Uttar Pradesh, India'],
        'patna'              => ['lat' => '25.6093', 'lng' => '85.1376', 'region' => 'IN-BR', 'placename' => 'Patna, Bihar, India'],
        'bhopal'             => ['lat' => '23.2599', 'lng' => '77.4126', 'region' => 'IN-MP', 'placename' => 'Bhopal, Madhya Pradesh, India'],
        'nagpur'             => ['lat' => '21.1458', 'lng' => '79.0882', 'region' => 'IN-MH', 'placename' => 'Nagpur, Maharashtra, India'],
        'indore'             => ['lat' => '22.7196', 'lng' => '75.8577', 'region' => 'IN-MP', 'placename' => 'Indore, Madhya Pradesh, India'],
        'thiruvananthapuram' => ['lat' => '8.5241', 'lng' => '76.9366', 'region' => 'IN-KL', 'placename' => 'Thiruvananthapuram, Kerala, India'],
        'coimbatore'         => ['lat' => '11.0168', 'lng' => '76.9558', 'region' => 'IN-TN', 'placename' => 'Coimbatore, Tamil Nadu, India'],
        'chandigarh'         => ['lat' => '30.7333', 'lng' => '76.7794', 'region' => 'IN-CH', 'placename' => 'Chandigarh, India'],
        'visakhapatnam'      => ['lat' => '17.6868', 'lng' => '83.2185', 'region' => 'IN-AP', 'placename' => 'Visakhapatnam, Andhra Pradesh, India'],
        'vijayawada'         => ['lat' => '16.5062', 'lng' => '80.6480', 'region' => 'IN-AP', 'placename' => 'Vijayawada, Andhra Pradesh, India'],
        'noida'              => ['lat' => '28.5355', 'lng' => '77.3910', 'region' => 'IN-UP', 'placename' => 'Noida, Uttar Pradesh, India'],
        'gurgaon'            => ['lat' => '28.4595', 'lng' => '77.0266', 'region' => 'IN-HR', 'placename' => 'Gurgaon, Haryana, India'],
        'gurugram'           => ['lat' => '28.4595', 'lng' => '77.0266', 'region' => 'IN-HR', 'placename' => 'Gurugram, Haryana, India'],
        'dehradun'           => ['lat' => '30.3165', 'lng' => '78.0322', 'region' => 'IN-UT', 'placename' => 'Dehradun, Uttarakhand, India'],
        'guwahati'           => ['lat' => '26.1445', 'lng' => '91.7362', 'region' => 'IN-AS', 'placename' => 'Guwahati, Assam, India'],
        'raipur'             => ['lat' => '21.2514', 'lng' => '81.6296', 'region' => 'IN-CT', 'placename' => 'Raipur, Chhattisgarh, India'],
        'ranchi'             => ['lat' => '23.3441', 'lng' => '85.3096', 'region' => 'IN-JH', 'placename' => 'Ranchi, Jharkhand, India'],
        'bhubaneswar'        => ['lat' => '20.2961', 'lng' => '85.8245', 'region' => 'IN-OR', 'placename' => 'Bhubaneswar, Odisha, India'],
        'mangalore'          => ['lat' => '12.9141', 'lng' => '74.8560', 'region' => 'IN-KA', 'placename' => 'Mangalore, Karnataka, India'],
        'mysore'             => ['lat' => '12.2958', 'lng' => '76.6394', 'region' => 'IN-KA', 'placename' => 'Mysore, Karnataka, India'],
        'varanasi'           => ['lat' => '25.3176', 'lng' => '82.9739', 'region' => 'IN-UP', 'placename' => 'Varanasi, Uttar Pradesh, India'],
        'agra'               => ['lat' => '27.1767', 'lng' => '78.0081', 'region' => 'IN-UP', 'placename' => 'Agra, Uttar Pradesh, India'],
        'madurai'            => ['lat' => '9.9252', 'lng' => '78.1198', 'region' => 'IN-TN', 'placename' => 'Madurai, Tamil Nadu, India'],
        'surat'              => ['lat' => '21.1702', 'lng' => '72.8311', 'region' => 'IN-GJ', 'placename' => 'Surat, Gujarat, India'],
        'vadodara'           => ['lat' => '22.3072', 'lng' => '73.1812', 'region' => 'IN-GJ', 'placename' => 'Vadodara, Gujarat, India'],
        'nashik'             => ['lat' => '19.9975', 'lng' => '73.7898', 'region' => 'IN-MH', 'placename' => 'Nashik, Maharashtra, India'],
        'meerut'             => ['lat' => '28.9845', 'lng' => '77.7064', 'region' => 'IN-UP', 'placename' => 'Meerut, Uttar Pradesh, India'],
    ];
}

/**
 * Extract geographic location from article title and content
 * Returns the most relevant GEO data for the article
 */
function extractArticleGeo(?string $title, ?string $content, ?string $excerpt = ''): array {
    $allGeo = getGeoLocations();
    $text = strtolower(($title ?? '') . ' ' . strip_tags($content ?? '') . ' ' . strip_tags($excerpt ?? ''));

    // Priority: title matches first, then content
    $titleText = strtolower($title ?? '');
    $found = [];

    // Check title first (higher priority)
    foreach ($allGeo as $key => $geo) {
        if (strpos($titleText, $key) !== false) {
            $found[] = ['key' => $key, 'geo' => $geo, 'source' => 'title'];
        }
    }

    // Then check content
    if (empty($found)) {
        foreach ($allGeo as $key => $geo) {
            if (strpos($text, $key) !== false) {
                $found[] = ['key' => $key, 'geo' => $geo, 'source' => 'content'];
                break;
            }
        }
    }

    // Return most specific match (prefer city over state)
    if (!empty($found)) {
        // Sort: title matches first, then city matches over state
        usort($found, function($a, $b) {
            if ($a['source'] !== $b['source']) return $a['source'] === 'title' ? -1 : 1;
            return strlen($b['key']) - strlen($a['key']);
        });
        return $found[0]['geo'];
    }

    // Default: India-wide
    return [
        'lat' => '20.5937',
        'lng' => '78.9629',
        'region' => 'IN',
        'placename' => 'India'
    ];
}

/**
 * Generate dynamic GEO meta tags for an article
 */
function renderGeoMetaTags(?string $title, ?string $content, ?string $excerpt = ''): string {
    $geo = extractArticleGeo($title, $content, $excerpt);
    return <<<HTML
  <!-- Dynamic GEO Meta Tags -->
  <meta name="geo.region" content="{$geo['region']}">
  <meta name="geo.placename" content="{$geo['placename']}">
  <meta name="geo.position" content="{$geo['lat']};{$geo['lng']}">
  <meta name="ICBM" content="{$geo['lat']}, {$geo['lng']}">
  <meta name="language" content="English">
HTML;
}

/**
 * Generate dynamic contentLocation JSON-LD for an article
 */
function getArticleContentLocation(?string $title, ?string $content, ?string $excerpt = ''): array {
    $geo = extractArticleGeo($title, $content, $excerpt);
    return [
        '@type' => 'Place',
        'name' => $geo['placename'],
        'geo' => [
            '@type' => 'GeoCoordinates',
            'latitude' => (float)$geo['lat'],
            'longitude' => (float)$geo['lng']
        ]
    ];
}

/**
 * Generate dynamic article:section OG meta from article type and category
 */
function getArticleSectionMeta(?string $articleType, ?string $categoryName): string {
    $sections = [
        'news'       => 'College News',
        'blog'       => 'Education Blog',
        'guide'      => 'Study Guide',
        'exam_update'=> 'Exam Updates',
        'opinion'    => 'Expert Opinion',
        'ranking'    => 'College Rankings',
    ];
    $section = $categoryName ?: ($sections[$articleType] ?? 'Education News');
    return htmlspecialchars($section);
}
