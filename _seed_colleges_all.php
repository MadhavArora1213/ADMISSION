<?php
/**
 * Comprehensive College Seed Script
 * Re-seeds ALL college data for 12 top Indian colleges
 * Tables: colleges, college_media, college_content, college_contacts,
 *         college_courses, college_admissions, college_placements,
 *         college_cutoffs, college_infrastructure, college_hostels,
 *         college_faculty, college_faqs, college_scholarships,
 *         college_accreditations, rankings, college_updates
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

function uid() {
    return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        mt_rand(0,0xffff), mt_rand(0,0xffff), mt_rand(0,0xffff),
        mt_rand(0x4000,0x4fff), mt_rand(0,0x3fff)|0x8000,
        mt_rand(0,0xffff), mt_rand(0,0xffff), mt_rand(0,0xffff));
}

try {
    $pdo = new PDO('mysql:host=localhost;dbname=admission;charset=utf8mb4', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");

    echo "=== College Seed Script Starting ===\n\n";

    // ============================================================
    // COLLEGE DATA DEFINITIONS
    // ============================================================
    $colleges = [
        // 1. IIT Bombay
        'iitb-0001' => [
            'name' => 'Indian Institute of Technology Bombay',
            'slug' => 'iit-bombay',
            'college_type' => 'engineering',
            'ownership' => 'private',
            'ranking_nirf' => 3,
            'city_id' => 500001,
            'state_id' => 21,
            'established_year' => 1958,
            'university_affiliation' => 'Autonomous Institute of National Importance',
            'naac_grade' => 'A++',
            'total_students' => 12000,
            'total_faculty' => 780,
            'campus_area_acres' => 1200,
            'overall_rating_avg' => 4.8,
            'total_reviews' => 3420,
            'is_featured' => 1,
            'featured_order' => 1,
            'website' => 'https://www.iitb.ac.in',
            'email' => 'dean.acad@iitb.ac.in',
            'phone' => '+91-22-25722545',
            'address' => 'Powai, Mumbai',
            'pincode' => 400076,
            'logo_url' => 'assets/images/exam-logos/iitb.svg',
            'cover_image_url' => 'https://images.unsplash.com/photo-1562774053-701939374585?w=800&q=80',
            'about' => 'IIT Bombay is one of the premier engineering institutions in India, established in 1958 with assistance from UNESCO. Located on a 1200-acre campus in Powai, Mumbai, it is consistently ranked among the top engineering colleges in the country. The institute offers a wide range of undergraduate, postgraduate, and doctoral programs across engineering, science, design, and management disciplines.',
            'highlights' => ["NIRF Rank 3 (Engineering)", "NAAC A++ Accredited", "1200 Acre Campus", "Established 1958", "780+ Faculty Members"],
            'accreditations' => ["NAAC A++", "NBA Accredited", "UGC Recognized"],
            'rankings' => [
                ['body' => 'NIRF', 'year' => 2025, 'category' => 'Engineering', 'rank' => 3, 'score' => 92.5],
                ['body' => 'NIRF', 'year' => 2024, 'category' => 'Engineering', 'rank' => 4, 'score' => 91.8],
            ],
        ],
        // 2. IIT Delhi
        'iitd-0002' => [
            'name' => 'Indian Institute of Technology Delhi',
            'slug' => 'iit-delhi',
            'college_type' => 'engineering',
            'ownership' => 'private',
            'ranking_nirf' => 2,
            'city_id' => 110001,
            'state_id' => 7,
            'established_year' => 1963,
            'university_affiliation' => 'Autonomous Institute of National Importance',
            'naac_grade' => 'A++',
            'total_students' => 11500,
            'total_faculty' => 720,
            'campus_area_acres' => 320,
            'overall_rating_avg' => 4.7,
            'total_reviews' => 3150,
            'is_featured' => 1,
            'featured_order' => 2,
            'website' => 'https://www.iitd.ac.in',
            'email' => 'dean.acad@iitd.ac.in',
            'phone' => '+91-11-26591754',
            'address' => 'Hauz Khas, New Delhi',
            'pincode' => 110016,
            'logo_url' => 'assets/images/exam-logos/iitd.svg',
            'cover_image_url' => 'https://images.unsplash.com/photo-1562774053-701939374585?w=800&q=80',
            'about' => 'IIT Delhi, established in 1963, is one of the most prestigious engineering institutions in India. Located in Hauz Khas, New Delhi, the institute is known for its academic excellence, cutting-edge research, and strong industry connections. It has consistently maintained a top position in national and international rankings.',
            'highlights' => ["NIRF Rank 2 (Engineering)", "NAAC A++ Accredited", "320 Acre Campus", "Established 1963", "720+ Faculty Members"],
            'accreditations' => ["NAAC A++", "NBA Accredited", "UGC Recognized"],
            'rankings' => [
                ['body' => 'NIRF', 'year' => 2025, 'category' => 'Engineering', 'rank' => 2, 'score' => 93.1],
                ['body' => 'NIRF', 'year' => 2024, 'category' => 'Engineering', 'rank' => 2, 'score' => 92.7],
            ],
        ],
        // 3. IIT Madras
        'iitm-0003' => [
            'name' => 'Indian Institute of Technology Madras',
            'slug' => 'iit-madras',
            'college_type' => 'engineering',
            'ownership' => 'private',
            'ranking_nirf' => 1,
            'city_id' => 600001,
            'state_id' => 33,
            'established_year' => 1959,
            'university_affiliation' => 'Autonomous Institute of National Importance',
            'naac_grade' => 'A++',
            'total_students' => 12500,
            'total_faculty' => 800,
            'campus_area_acres' => 630,
            'overall_rating_avg' => 4.9,
            'total_reviews' => 3800,
            'is_featured' => 1,
            'featured_order' => 3,
            'website' => 'https://www.iitm.ac.in',
            'email' => 'dean.acad@iitm.ac.in',
            'phone' => '+91-44-22578200',
            'address' => 'Adyar, Chennai',
            'pincode' => 600036,
            'logo_url' => 'assets/images/exam-logos/iitm.svg',
            'cover_image_url' => 'https://images.unsplash.com/photo-1562774053-701939374585?w=800&q=80',
            'about' => 'IIT Madras, established in 1959, is consistently ranked as the number one engineering institution in India. Located on a 630-acre campus in Adyar, Chennai, the institute is renowned for its world-class faculty, research output, and strong alumni network. It offers programs across engineering, science, humanities, and management.',
            'highlights' => ["NIRF Rank 1 (Engineering)", "NAAC A++ Accredited", "630 Acre Campus", "Established 1959", "800+ Faculty Members"],
            'accreditations' => ["NAAC A++", "NBA Accredited", "UGC Recognized"],
            'rankings' => [
                ['body' => 'NIRF', 'year' => 2025, 'category' => 'Engineering', 'rank' => 1, 'score' => 94.2],
                ['body' => 'NIRF', 'year' => 2024, 'category' => 'Engineering', 'rank' => 1, 'score' => 93.8],
            ],
        ],
        // 4. IIT Kanpur
        'iitk-0004' => [
            'name' => 'Indian Institute of Technology Kanpur',
            'slug' => 'iit-kanpur',
            'college_type' => 'engineering',
            'ownership' => 'private',
            'ranking_nirf' => 4,
            'city_id' => 208016,
            'state_id' => 9,
            'established_year' => 1960,
            'university_affiliation' => 'Autonomous Institute of National Importance',
            'naac_grade' => 'A++',
            'total_students' => 10500,
            'total_faculty' => 650,
            'campus_area_acres' => 1055,
            'overall_rating_avg' => 4.7,
            'total_reviews' => 2980,
            'is_featured' => 1,
            'featured_order' => 4,
            'website' => 'https://www.iitk.ac.in',
            'email' => 'dean.acad@iitk.ac.in',
            'phone' => '+91-512-2590106',
            'address' => 'Kalyanpur, Kanpur',
            'pincode' => 208016,
            'logo_url' => 'assets/images/exam-logos/iitk.svg',
            'cover_image_url' => 'https://images.unsplash.com/photo-1562774053-701939374585?w=800&q=80',
            'about' => 'IIT Kanpur, established in 1960, is known for its strong emphasis on research and innovation. Spread across 1055 acres in Kalyanpur, Kanpur, the institute has a rich legacy of producing top engineers and scientists. It offers programs in engineering, sciences, design, and management.',
            'highlights' => ["NIRF Rank 4 (Engineering)", "NAAC A++ Accredited", "1055 Acre Campus", "Established 1960", "650+ Faculty Members"],
            'accreditations' => ["NAAC A++", "NBA Accredited", "UGC Recognized"],
            'rankings' => [
                ['body' => 'NIRF', 'year' => 2025, 'category' => 'Engineering', 'rank' => 4, 'score' => 91.3],
                ['body' => 'NIRF', 'year' => 2024, 'category' => 'Engineering', 'rank' => 5, 'score' => 90.6],
            ],
        ],
        // 5. IIT Kharagpur
        'iitkgp-0005' => [
            'name' => 'Indian Institute of Technology Kharagpur',
            'slug' => 'iit-kharagpur',
            'college_type' => 'engineering',
            'ownership' => 'private',
            'ranking_nirf' => 5,
            'city_id' => 721302,
            'state_id' => 19,
            'established_year' => 1951,
            'university_affiliation' => 'Autonomous Institute of National Importance',
            'naac_grade' => 'A++',
            'total_students' => 13000,
            'total_faculty' => 850,
            'campus_area_acres' => 2100,
            'overall_rating_avg' => 4.6,
            'total_reviews' => 3600,
            'is_featured' => 1,
            'featured_order' => 5,
            'website' => 'https://www.iitkgp.ac.in',
            'email' => 'dean.acad@iitkgp.ac.in',
            'phone' => '+91-3222-255221',
            'address' => 'Kharagpur, West Bengal',
            'pincode' => 721302,
            'logo_url' => 'assets/images/exam-logos/iitkgp.svg',
            'cover_image_url' => 'https://images.unsplash.com/photo-1562774053-701939374585?w=800&q=80',
            'about' => 'IIT Kharagpur, established in 1951, is the oldest and largest IIT in India. Spread across a sprawling 2100-acre campus, it offers the widest range of academic programs among all IITs. The institute has been a pioneer in engineering education and research in India.',
            'highlights' => ["NIRF Rank 5 (Engineering)", "NAAC A++ Accredited", "2100 Acre Campus", "Established 1951 (Oldest IIT)", "850+ Faculty Members"],
            'accreditations' => ["NAAC A++", "NBA Accredited", "UGC Recognized"],
            'rankings' => [
                ['body' => 'NIRF', 'year' => 2025, 'category' => 'Engineering', 'rank' => 5, 'score' => 90.9],
                ['body' => 'NIRF', 'year' => 2024, 'category' => 'Engineering', 'rank' => 6, 'score' => 90.2],
            ],
        ],
        // 6. NIT Tiruchirappalli
        'nitt-0006' => [
            'name' => 'National Institute of Technology Tiruchirappalli',
            'slug' => 'nit-tiruchirappalli',
            'college_type' => 'engineering',
            'ownership' => 'govt',
            'ranking_nirf' => 9,
            'city_id' => 620015,
            'state_id' => 33,
            'established_year' => 1964,
            'university_affiliation' => 'Autonomous Institute',
            'naac_grade' => 'A++',
            'total_students' => 8500,
            'total_faculty' => 480,
            'campus_area_acres' => 325,
            'overall_rating_avg' => 4.5,
            'total_reviews' => 2450,
            'is_featured' => 1,
            'featured_order' => 6,
            'website' => 'https://www.nitt.edu',
            'email' => 'dean.acad@nitt.edu',
            'phone' => '+91-431-2503000',
            'address' => 'Tiruchirappalli, Tamil Nadu',
            'pincode' => 620015,
            'logo_url' => 'assets/images/exam-logos/nitt.svg',
            'cover_image_url' => 'https://images.unsplash.com/photo-1562774053-701939374585?w=800&q=80',
            'about' => 'NIT Tiruchirappalli, established in 1964, is one of the premier National Institutes of Technology in India. Located in Tiruchirappalli, Tamil Nadu, the institute is known for its excellent academic programs and strong placement records. It offers undergraduate, postgraduate, and doctoral programs in engineering and technology.',
            'highlights' => ["NIRF Rank 9 (Engineering)", "NAAC A++ Accredited", "325 Acre Campus", "Established 1964", "480+ Faculty Members"],
            'accreditations' => ["NAAC A++", "NBA Accredited", "UGC Recognized"],
            'rankings' => [
                ['body' => 'NIRF', 'year' => 2025, 'category' => 'Engineering', 'rank' => 9, 'score' => 85.3],
            ],
        ],
        // 7. NIT Surathkal
        'nitk-0007' => [
            'name' => 'National Institute of Technology Surathkal',
            'slug' => 'nit-surathkal',
            'college_type' => 'engineering',
            'ownership' => 'govt',
            'ranking_nirf' => 10,
            'city_id' => 575025,
            'state_id' => 18,
            'established_year' => 1960,
            'university_affiliation' => 'Autonomous Institute',
            'naac_grade' => 'A++',
            'total_students' => 7500,
            'total_faculty' => 420,
            'campus_area_acres' => 295,
            'overall_rating_avg' => 4.5,
            'total_reviews' => 2200,
            'is_featured' => 0,
            'featured_order' => 7,
            'website' => 'https://www.nitk.ac.in',
            'email' => 'dean.acad@nitk.ac.in',
            'phone' => '+91-824-2474000',
            'address' => 'Surathkal, Mangalore',
            'pincode' => 575025,
            'logo_url' => 'assets/images/exam-logos/nitk.svg',
            'cover_image_url' => 'https://images.unsplash.com/photo-1562774053-701939374585?w=800&q=80',
            'about' => 'NIT Surathkal, established in 1960, is a premier engineering institution located on the scenic coast of Karnataka. Formerly known as Karnataka Regional Engineering College, it is known for its excellent academic environment and strong placement record. The institute offers programs in engineering, science, and management.',
            'highlights' => ["NIRF Rank 10 (Engineering)", "NAAC A++ Accredited", "295 Acre Campus", "Established 1960", "420+ Faculty Members"],
            'accreditations' => ["NAAC A++", "NBA Accredited", "UGC Recognized"],
            'rankings' => [
                ['body' => 'NIRF', 'year' => 2025, 'category' => 'Engineering', 'rank' => 10, 'score' => 84.7],
            ],
        ],
        // 8. BITS Pilani
        'bits-0008' => [
            'name' => 'Birla Institute of Technology and Science Pilani',
            'slug' => 'bits-pilani',
            'college_type' => 'engineering',
            'ownership' => 'deemed',
            'ranking_nirf' => 22,
            'city_id' => 333031,
            'state_id' => 8,
            'established_year' => 1964,
            'university_affiliation' => 'Deemed to be University',
            'naac_grade' => 'A++',
            'total_students' => 16000,
            'total_faculty' => 600,
            'campus_area_acres' => 328,
            'overall_rating_avg' => 4.6,
            'total_reviews' => 2800,
            'is_featured' => 0,
            'featured_order' => 8,
            'website' => 'https://www.bits-pilani.ac.in',
            'email' => 'dean.acad@bits-pilani.ac.in',
            'phone' => '+91-1596-245073',
            'address' => 'Pilani, Rajasthan',
            'pincode' => 333031,
            'logo_url' => 'assets/images/exam-logos/bits.svg',
            'cover_image_url' => 'https://images.unsplash.com/photo-1562774053-701939374585?w=800&q=80',
            'about' => 'BITS Pilani, established in 1964, is a premier private deemed university known for its innovative practice school system and strong industry connections. Located in Pilani, Rajasthan, the institute offers integrated first-degree, higher degree, and doctoral programs. BITS is known for its rigorous academic curriculum and excellent placement record.',
            'highlights' => ["NIRF Rank 22 (Engineering)", "NAAC A++ Accredited", "328 Acre Campus", "Established 1964", "Deemed University Status"],
            'accreditations' => ["NAAC A++", "NBA Accredited", "UGC Recognized"],
            'rankings' => [
                ['body' => 'NIRF', 'year' => 2025, 'category' => 'Engineering', 'rank' => 22, 'score' => 78.4],
            ],
        ],
        // 9. IIM Ahmedabad
        'iima-0009' => [
            'name' => 'Indian Institute of Management Ahmedabad',
            'slug' => 'iim-ahmedabad',
            'college_type' => 'management',
            'ownership' => 'govt',
            'ranking_nirf' => 1,
            'city_id' => 380015,
            'state_id' => 24,
            'established_year' => 1961,
            'university_affiliation' => 'Autonomous Institute of National Importance',
            'naac_grade' => 'A++',
            'total_students' => 2000,
            'total_faculty' => 120,
            'campus_area_acres' => 102,
            'overall_rating_avg' => 4.9,
            'total_reviews' => 1500,
            'is_featured' => 1,
            'featured_order' => 9,
            'website' => 'https://www.iima.ac.in',
            'email' => 'dean.acad@iima.ac.in',
            'phone' => '+91-79-63066000',
            'address' => 'Vastrapur, Ahmedabad',
            'pincode' => 380015,
            'logo_url' => 'assets/images/exam-logos/iima.svg',
            'cover_image_url' => 'https://images.unsplash.com/photo-1562774053-701939374585?w=800&q=80',
            'about' => 'IIM Ahmedabad, established in 1961, is consistently ranked as the number one management institution in India. Located in Ahmedabad, Gujarat, the institute is known for its case-based pedagogy, world-class faculty, and exceptional placement records. IIMA offers MBA, PGPX, and doctoral programs.',
            'highlights' => ["NIRF Rank 1 (Management)", "NAAC A++ Accredited", "102 Acre Campus", "Established 1961", "120+ Faculty Members"],
            'accreditations' => ["NAAC A++", "NBA Accredited", "UGC Recognized"],
            'rankings' => [
                ['body' => 'NIRF', 'year' => 2025, 'category' => 'Management', 'rank' => 1, 'score' => 95.1],
            ],
        ],
        // 10. IIM Bangalore
        'iimb-0010' => [
            'name' => 'Indian Institute of Management Bangalore',
            'slug' => 'iim-bangalore',
            'college_type' => 'management',
            'ownership' => 'govt',
            'ranking_nirf' => 2,
            'city_id' => 560012,
            'state_id' => 18,
            'established_year' => 1973,
            'university_affiliation' => 'Autonomous Institute of National Importance',
            'naac_grade' => 'A++',
            'total_students' => 1800,
            'total_faculty' => 110,
            'campus_area_acres' => 100,
            'overall_rating_avg' => 4.8,
            'total_reviews' => 1350,
            'is_featured' => 0,
            'featured_order' => 10,
            'website' => 'https://www.iimb.ac.in',
            'email' => 'dean.acad@iimb.ac.in',
            'phone' => '+91-80-26993000',
            'address' => 'Bannerghatta Road, Bangalore',
            'pincode' => 560076,
            'logo_url' => 'assets/images/exam-logos/iimb.svg',
            'cover_image_url' => 'https://images.unsplash.com/photo-1562774053-701939374585?w=800&q=80',
            'about' => 'IIM Bangalore, established in 1973, is the second-ranked management institution in India. Located in the IT hub of Bangalore, the institute is known for its research focus, innovative pedagogy, and strong industry interface. IIMB offers PGP, EPGP, and executive education programs.',
            'highlights' => ["NIRF Rank 2 (Management)", "NAAC A++ Accredited", "100 Acre Campus", "Established 1973", "110+ Faculty Members"],
            'accreditations' => ["NAAC A++", "NBA Accredited", "UGC Recognized"],
            'rankings' => [
                ['body' => 'NIRF', 'year' => 2025, 'category' => 'Management', 'rank' => 2, 'score' => 94.3],
            ],
        ],
        // 11. AIIMS Delhi
        'aiims-0011' => [
            'name' => 'All India Institute of Medical Sciences Delhi',
            'slug' => 'aiims-delhi',
            'college_type' => 'medical',
            'ownership' => 'govt',
            'ranking_nirf' => 1,
            'city_id' => 110001,
            'state_id' => 7,
            'established_year' => 1956,
            'university_affiliation' => 'Autonomous Institute of National Importance',
            'naac_grade' => 'A++',
            'total_students' => 3500,
            'total_faculty' => 500,
            'campus_area_acres' => 115,
            'overall_rating_avg' => 4.9,
            'total_reviews' => 2500,
            'is_featured' => 1,
            'featured_order' => 11,
            'website' => 'https://www.aiims.ac.in',
            'email' => 'dean.acad@aiims.ac.in',
            'phone' => '+91-11-26588500',
            'address' => 'Sri Aurobindo Marg, New Delhi',
            'pincode' => 110029,
            'logo_url' => 'assets/images/exam-logos/aiims.svg',
            'cover_image_url' => 'https://images.unsplash.com/photo-1562774053-701939374585?w=800&q=80',
            'about' => 'AIIMS Delhi, established in 1956, is the premier medical institution in India and a hospital of national importance. Located in New Delhi, AIIMS is known for its world-class medical education, cutting-edge research, and affordable healthcare. It offers MBBS, MD, MS, and various super-specialty programs.',
            'highlights' => ["NIRF Rank 1 (Medical)", "NAAC A++ Accredited", "115 Acre Campus", "Established 1956", "500+ Faculty Members"],
            'accreditations' => ["NAAC A++", "NMC Accredited", "UGC Recognized"],
            'rankings' => [
                ['body' => 'NIRF', 'year' => 2025, 'category' => 'Medical', 'rank' => 1, 'score' => 96.5],
            ],
        ],
        // 12. NLSIU Bangalore
        'nlsiu-0012' => [
            'name' => 'National Law School of India University Bangalore',
            'slug' => 'nlsiu-bangalore',
            'college_type' => 'law',
            'ownership' => 'private',
            'ranking_nirf' => 1,
            'city_id' => 560012,
            'state_id' => 18,
            'established_year' => 1986,
            'university_affiliation' => 'State University',
            'naac_grade' => 'A++',
            'total_students' => 1200,
            'total_faculty' => 85,
            'campus_area_acres' => 25,
            'overall_rating_avg' => 4.8,
            'total_reviews' => 850,
            'is_featured' => 0,
            'featured_order' => 12,
            'website' => 'https://www.nls.ac.in',
            'email' => 'dean.acad@nls.ac.in',
            'phone' => '+91-80-23213161',
            'address' => 'Nagarbhavi, Bangalore',
            'pincode' => 560072,
            'logo_url' => 'assets/images/exam-logos/nlsiu.svg',
            'cover_image_url' => 'https://images.unsplash.com/photo-1562774053-701939374585?w=800&q=80',
            'about' => 'NLSIU Bangalore, established in 1986, is India\'s premier law university and consistently ranked number one in legal education. Located in Nagarbhavi, Bangalore, NLSIU is known for its rigorous academic programs, moot court competitions, and strong placement record in top law firms. It offers BA LLB, LLM, and PhD programs.',
            'highlights' => ["NIRF Rank 1 (Law)", "NAAC A++ Accredited", "25 Acre Campus", "Established 1986", "85+ Faculty Members"],
            'accreditations' => ["NAAC A++", "BCI Accredited", "UGC Recognized"],
            'rankings' => [
                ['body' => 'NIRF', 'year' => 2025, 'category' => 'Law', 'rank' => 1, 'score' => 93.7],
            ],
        ],
    ];

    // ============================================================
    // COURSE DATA
    // ============================================================
    $courses = [
        'iitb-0001' => [
            ['name' => 'B.Tech Computer Science and Engineering', 'level' => 'UG', 'duration' => 4, 'annual_fee' => 200000, 'total_fee' => 800000, 'seats' => 160, 'eligibility' => 'JEE Advanced qualified, Class XII (75% aggregate)', 'specializations' => ['Data Science', 'Artificial Intelligence', 'Cybersecurity']],
            ['name' => 'B.Tech Electrical Engineering', 'level' => 'UG', 'duration' => 4, 'annual_fee' => 200000, 'total_fee' => 800000, 'seats' => 120, 'eligibility' => 'JEE Advanced qualified, Class XII (75% aggregate)', 'specializations' => ['Power Systems', 'Control Systems', 'VLSI Design']],
            ['name' => 'M.Tech Computer Science and Engineering', 'level' => 'PG', 'duration' => 2, 'annual_fee' => 200000, 'total_fee' => 400000, 'seats' => 60, 'eligibility' => 'GATE qualified, B.Tech in CS or equivalent', 'specializations' => ['Machine Learning', 'Software Systems', 'Information Security']],
            ['name' => 'PhD Engineering', 'level' => 'PhD', 'duration' => 5, 'annual_fee' => 50000, 'total_fee' => 250000, 'seats' => 200, 'eligibility' => 'GATE/NET qualified, Master degree with 60% aggregate', 'specializations' => ['All Engineering Disciplines']],
        ],
        'iitd-0002' => [
            ['name' => 'B.Tech Computer Science and Engineering', 'level' => 'UG', 'duration' => 4, 'annual_fee' => 200000, 'total_fee' => 800000, 'seats' => 150, 'eligibility' => 'JEE Advanced qualified, Class XII (75% aggregate)', 'specializations' => ['Artificial Intelligence', 'Data Engineering', 'Cybersecurity']],
            ['name' => 'B.Tech Mechanical Engineering', 'level' => 'UG', 'duration' => 4, 'annual_fee' => 200000, 'total_fee' => 800000, 'seats' => 110, 'eligibility' => 'JEE Advanced qualified, Class XII (75% aggregate)', 'specializations' => ['Thermal Engineering', 'Manufacturing', 'Robotics']],
            ['name' => 'M.Tech Artificial Intelligence', 'level' => 'PG', 'duration' => 2, 'annual_fee' => 200000, 'total_fee' => 400000, 'seats' => 50, 'eligibility' => 'GATE qualified, B.Tech in CS or equivalent', 'specializations' => ['Machine Learning', 'Deep Learning', 'Natural Language Processing']],
            ['name' => 'PhD Engineering', 'level' => 'PhD', 'duration' => 5, 'annual_fee' => 50000, 'total_fee' => 250000, 'seats' => 180, 'eligibility' => 'GATE/NET qualified, Master degree with 60% aggregate', 'specializations' => ['All Engineering Disciplines']],
        ],
        'iitm-0003' => [
            ['name' => 'B.Tech Computer Science and Engineering', 'level' => 'UG', 'duration' => 4, 'annual_fee' => 200000, 'total_fee' => 800000, 'seats' => 170, 'eligibility' => 'JEE Advanced qualified, Class XII (75% aggregate)', 'specializations' => ['AI and Machine Learning', 'Data Science', 'Cybersecurity']],
            ['name' => 'B.Tech Electrical Engineering', 'level' => 'UG', 'duration' => 4, 'annual_fee' => 200000, 'total_fee' => 800000, 'seats' => 120, 'eligibility' => 'JEE Advanced qualified, Class XII (75% aggregate)', 'specializations' => ['Embedded Systems', 'Signal Processing', 'Power Electronics']],
            ['name' => 'M.Tech Data Science', 'level' => 'PG', 'duration' => 2, 'annual_fee' => 200000, 'total_fee' => 400000, 'seats' => 55, 'eligibility' => 'GATE qualified, B.Tech in CS or equivalent', 'specializations' => ['Big Data Analytics', 'Machine Learning', 'Statistical Learning']],
            ['name' => 'PhD Engineering', 'level' => 'PhD', 'duration' => 5, 'annual_fee' => 50000, 'total_fee' => 250000, 'seats' => 220, 'eligibility' => 'GATE/NET qualified, Master degree with 60% aggregate', 'specializations' => ['All Engineering Disciplines']],
        ],
        'iitk-0004' => [
            ['name' => 'B.Tech Computer Science and Engineering', 'level' => 'UG', 'duration' => 4, 'annual_fee' => 200000, 'total_fee' => 800000, 'seats' => 140, 'eligibility' => 'JEE Advanced qualified, Class XII (75% aggregate)', 'specializations' => ['Artificial Intelligence', 'Computer Systems', 'Software Engineering']],
            ['name' => 'B.Tech Aerospace Engineering', 'level' => 'UG', 'duration' => 4, 'annual_fee' => 200000, 'total_fee' => 800000, 'seats' => 60, 'eligibility' => 'JEE Advanced qualified, Class XII (75% aggregate)', 'specializations' => ['Aerodynamics', 'Propulsion', 'Flight Mechanics']],
            ['name' => 'M.Tech Computer Science', 'level' => 'PG', 'duration' => 2, 'annual_fee' => 200000, 'total_fee' => 400000, 'seats' => 50, 'eligibility' => 'GATE qualified, B.Tech in CS or equivalent', 'specializations' => ['Algorithms', 'Distributed Systems', 'Machine Learning']],
            ['name' => 'PhD Engineering', 'level' => 'PhD', 'duration' => 5, 'annual_fee' => 50000, 'total_fee' => 250000, 'seats' => 160, 'eligibility' => 'GATE/NET qualified, Master degree with 60% aggregate', 'specializations' => ['All Engineering Disciplines']],
        ],
        'iitkgp-0005' => [
            ['name' => 'B.Tech Computer Science and Engineering', 'level' => 'UG', 'duration' => 4, 'annual_fee' => 200000, 'total_fee' => 800000, 'seats' => 180, 'eligibility' => 'JEE Advanced qualified, Class XII (75% aggregate)', 'specializations' => ['Artificial Intelligence', 'Data Science', 'Cloud Computing']],
            ['name' => 'B.Tech Civil Engineering', 'level' => 'UG', 'duration' => 4, 'annual_fee' => 200000, 'total_fee' => 800000, 'seats' => 100, 'eligibility' => 'JEE Advanced qualified, Class XII (75% aggregate)', 'specializations' => ['Structural Engineering', 'Transportation', 'Environmental Engineering']],
            ['name' => 'M.Tech Mechanical Engineering', 'level' => 'PG', 'duration' => 2, 'annual_fee' => 200000, 'total_fee' => 400000, 'seats' => 60, 'eligibility' => 'GATE qualified, B.Tech in ME or equivalent', 'specializations' => ['Thermal Science', 'Design', 'Manufacturing']],
            ['name' => 'PhD Engineering', 'level' => 'PhD', 'duration' => 5, 'annual_fee' => 50000, 'total_fee' => 250000, 'seats' => 250, 'eligibility' => 'GATE/NET qualified, Master degree with 60% aggregate', 'specializations' => ['All Engineering Disciplines']],
        ],
        'nitt-0006' => [
            ['name' => 'B.Tech Computer Science and Engineering', 'level' => 'UG', 'duration' => 4, 'annual_fee' => 120000, 'total_fee' => 480000, 'seats' => 120, 'eligibility' => 'JEE Main qualified, Class XII (75% aggregate)', 'specializations' => ['Computer Networks', 'Data Science', 'Information Security']],
            ['name' => 'B.Tech Electrical and Electronics Engineering', 'level' => 'UG', 'duration' => 4, 'annual_fee' => 120000, 'total_fee' => 480000, 'seats' => 100, 'eligibility' => 'JEE Main qualified, Class XII (75% aggregate)', 'specializations' => ['Power Systems', 'Electronics', 'Control Systems']],
            ['name' => 'M.Tech Structural Engineering', 'level' => 'PG', 'duration' => 2, 'annual_fee' => 120000, 'total_fee' => 240000, 'seats' => 40, 'eligibility' => 'GATE qualified, B.Tech in CE or equivalent', 'specializations' => ['Structural Dynamics', 'Earthquake Engineering', 'Concrete Technology']],
        ],
        'nitk-0007' => [
            ['name' => 'B.Tech Computer Science and Engineering', 'level' => 'UG', 'duration' => 4, 'annual_fee' => 120000, 'total_fee' => 480000, 'seats' => 110, 'eligibility' => 'JEE Main qualified, Class XII (75% aggregate)', 'specializations' => ['Artificial Intelligence', 'Cybersecurity', 'Software Engineering']],
            ['name' => 'B.Tech Electronics and Communication Engineering', 'level' => 'UG', 'duration' => 4, 'annual_fee' => 120000, 'total_fee' => 480000, 'seats' => 100, 'eligibility' => 'JEE Main qualified, Class XII (75% aggregate)', 'specializations' => ['Signal Processing', 'VLSI Design', 'Embedded Systems']],
            ['name' => 'M.Tech Computer Science', 'level' => 'PG', 'duration' => 2, 'annual_fee' => 120000, 'total_fee' => 240000, 'seats' => 35, 'eligibility' => 'GATE qualified, B.Tech in CS or equivalent', 'specializations' => ['Machine Learning', 'Cloud Computing', 'Big Data']],
        ],
        'bits-0008' => [
            ['name' => 'B.E. Computer Science', 'level' => 'UG', 'duration' => 4, 'annual_fee' => 350000, 'total_fee' => 1400000, 'seats' => 200, 'eligibility' => 'BITSAT qualified, Class XII (75% in PCM)', 'specializations' => ['AI and Data Science', 'Software Development', 'Cybersecurity']],
            ['name' => 'B.E. Electrical and Electronics Engineering', 'level' => 'UG', 'duration' => 4, 'annual_fee' => 350000, 'total_fee' => 1400000, 'seats' => 100, 'eligibility' => 'BITSAT qualified, Class XII (75% in PCM)', 'specializations' => ['Power Electronics', 'Control Systems', 'Robotics']],
            ['name' => 'M.Tech Software Systems', 'level' => 'PG', 'duration' => 2, 'annual_fee' => 350000, 'total_fee' => 700000, 'seats' => 60, 'eligibility' => 'GATE qualified, B.E. in CS or equivalent', 'specializations' => ['Software Engineering', 'DevOps', 'Cloud Architecture']],
            ['name' => 'PhD Engineering', 'level' => 'PhD', 'duration' => 5, 'annual_fee' => 100000, 'total_fee' => 500000, 'seats' => 80, 'eligibility' => 'GATE/NET qualified, Master degree with 60% aggregate', 'specializations' => ['All Engineering Disciplines']],
        ],
        'iima-0009' => [
            ['name' => 'MBA Post Graduate Programme in Management', 'level' => 'PG', 'duration' => 2, 'annual_fee' => 2300000, 'total_fee' => 4600000, 'seats' => 395, 'eligibility' => 'CAT qualified, Bachelor degree with 50% aggregate', 'specializations' => ['Marketing', 'Finance', 'Operations', 'Strategy']],
            ['name' => 'PGPX Executive MBA', 'level' => 'PG', 'duration' => 1, 'annual_fee' => 2800000, 'total_fee' => 2800000, 'seats' => 140, 'eligibility' => 'GMAT/GRE qualified, 5+ years work experience', 'specializations' => ['Leadership', 'General Management', 'Digital Transformation']],
            ['name' => 'PhD Management', 'level' => 'PhD', 'duration' => 5, 'annual_fee' => 100000, 'total_fee' => 500000, 'seats' => 50, 'eligibility' => 'CAT/GMAT qualified, Master degree with 55% aggregate', 'specializations' => ['Organizational Behaviour', 'Finance', 'Marketing', 'Operations']],
        ],
        'iimb-0010' => [
            ['name' => 'MBA Post Graduate Programme in Management', 'level' => 'PG', 'duration' => 2, 'annual_fee' => 2300000, 'total_fee' => 4600000, 'seats' => 520, 'eligibility' => 'CAT qualified, Bachelor degree with 50% aggregate', 'specializations' => ['Finance', 'Marketing', 'Human Resources', 'Business Analytics']],
            ['name' => 'Executive PGPEM', 'level' => 'PG', 'duration' => 2, 'annual_fee' => 2500000, 'total_fee' => 5000000, 'seats' => 120, 'eligibility' => 'GMAT/GRE qualified, 5+ years work experience', 'specializations' => ['Leadership', 'Strategy', 'Innovation Management']],
            ['name' => 'PhD Management', 'level' => 'PhD', 'duration' => 5, 'annual_fee' => 100000, 'total_fee' => 500000, 'seats' => 45, 'eligibility' => 'CAT/GMAT qualified, Master degree with 55% aggregate', 'specializations' => ['Economics', 'Finance', 'Information Systems', 'Operations']],
        ],
        'aiims-0011' => [
            ['name' => 'MBBS Bachelor of Medicine and Surgery', 'level' => 'UG', 'duration' => 5.5, 'annual_fee' => 1500, 'total_fee' => 8250, 'seats' => 125, 'eligibility' => 'NEET UG qualified, Class XII (50% in PCB)', 'specializations' => ['General Medicine', 'Surgery']],
            ['name' => 'MD Doctor of Medicine', 'level' => 'PG', 'duration' => 3, 'annual_fee' => 5000, 'total_fee' => 15000, 'seats' => 200, 'eligibility' => 'NEET PG qualified, MBBS degree', 'specializations' => ['Internal Medicine', 'Pediatrics', 'Radiology', 'Anesthesiology']],
            ['name' => 'MS Master of Surgery', 'level' => 'PG', 'duration' => 3, 'annual_fee' => 5000, 'total_fee' => 15000, 'seats' => 100, 'eligibility' => 'NEET PG qualified, MBBS degree', 'specializations' => ['General Surgery', 'Orthopedics', 'Ophthalmology', 'ENT']],
            ['name' => 'PhD Medical Sciences', 'level' => 'PhD', 'duration' => 5, 'annual_fee' => 10000, 'total_fee' => 50000, 'seats' => 80, 'eligibility' => 'AIIMS PhD entrance qualified, MD/MS degree', 'specializations' => ['Biochemistry', 'Pharmacology', 'Pathology', 'Microbiology']],
        ],
        'nlsiu-0012' => [
            ['name' => 'BA LLB Honours', 'level' => 'UG', 'duration' => 5, 'annual_fee' => 250000, 'total_fee' => 1250000, 'seats' => 120, 'eligibility' => 'CLAT qualified, Class XII (45% aggregate)', 'specializations' => ['Constitutional Law', 'Criminal Law', 'Corporate Law', 'International Law']],
            ['name' => 'LLM Master of Laws', 'level' => 'PG', 'duration' => 1, 'annual_fee' => 200000, 'total_fee' => 200000, 'seats' => 80, 'eligibility' => 'CLAT LLM qualified, LLB degree', 'specializations' => ['Human Rights Law', 'Business Law', 'Intellectual Property Law']],
            ['name' => 'PhD Law', 'level' => 'PhD', 'duration' => 5, 'annual_fee' => 50000, 'total_fee' => 250000, 'seats' => 30, 'eligibility' => 'NET/CLAT PhD qualified, LLM degree with 55% aggregate', 'specializations' => ['Constitutional Law', 'Criminal Justice', 'International Law']],
        ],
    ];

    // ============================================================
    // PLACEMENT DATA
    // ============================================================
    $placements = [
        'iitb-0001' => [
            ['year' => 2024, 'avg' => 20.3, 'highest' => 120.0, 'median' => 17.5, 'pct' => 92.5, 'placed' => 1480, 'recruiters' => ['Google', 'Microsoft', 'Goldman Sachs', 'Amazon', 'Apple', 'McKinsey & Company', 'Bain & Company']],
            ['year' => 2025, 'avg' => 22.1, 'highest' => 135.0, 'median' => 19.2, 'pct' => 94.0, 'placed' => 1520, 'recruiters' => ['Google', 'Microsoft', 'Goldman Sachs', 'Amazon', 'Apple', 'McKinsey & Company', 'Bain & Company', 'OpenAI']],
        ],
        'iitd-0002' => [
            ['year' => 2024, 'avg' => 19.8, 'highest' => 110.0, 'median' => 16.8, 'pct' => 91.8, 'placed' => 1350, 'recruiters' => ['Google', 'Microsoft', 'Amazon', 'Goldman Sachs', 'Deloitte', 'Adobe', 'Qualcomm']],
            ['year' => 2025, 'avg' => 21.5, 'highest' => 125.0, 'median' => 18.5, 'pct' => 93.2, 'placed' => 1400, 'recruiters' => ['Google', 'Microsoft', 'Amazon', 'Goldman Sachs', 'Deloitte', 'Adobe', 'Qualcomm', 'Flipkart']],
        ],
        'iitm-0003' => [
            ['year' => 2024, 'avg' => 21.5, 'highest' => 115.0, 'median' => 18.2, 'pct' => 93.5, 'placed' => 1550, 'recruiters' => ['Google', 'Microsoft', 'Amazon', 'Goldman Sachs', 'McKinsey & Company', 'Texas Instruments', 'Samsung']],
            ['year' => 2025, 'avg' => 23.2, 'highest' => 140.0, 'median' => 20.1, 'pct' => 95.0, 'placed' => 1600, 'recruiters' => ['Google', 'Microsoft', 'Amazon', 'Goldman Sachs', 'McKinsey & Company', 'Texas Instruments', 'Samsung', 'Apple']],
        ],
        'iitk-0004' => [
            ['year' => 2024, 'avg' => 19.2, 'highest' => 100.0, 'median' => 16.0, 'pct' => 91.0, 'placed' => 1200, 'recruiters' => ['Google', 'Microsoft', 'Amazon', 'Goldman Sachs', 'Adobe', 'Qualcomm', 'Flipkart']],
            ['year' => 2025, 'avg' => 20.8, 'highest' => 115.0, 'median' => 17.8, 'pct' => 92.5, 'placed' => 1250, 'recruiters' => ['Google', 'Microsoft', 'Amazon', 'Goldman Sachs', 'Adobe', 'Qualcomm', 'Flipkart', 'Razorpay']],
        ],
        'iitkgp-0005' => [
            ['year' => 2024, 'avg' => 18.5, 'highest' => 95.0, 'median' => 15.5, 'pct' => 90.5, 'placed' => 1400, 'recruiters' => ['Google', 'Microsoft', 'Amazon', 'Goldman Sachs', 'TCS', 'Infosys', 'Cognizant']],
            ['year' => 2025, 'avg' => 20.1, 'highest' => 110.0, 'median' => 17.2, 'pct' => 92.0, 'placed' => 1450, 'recruiters' => ['Google', 'Microsoft', 'Amazon', 'Goldman Sachs', 'TCS', 'Infosys', 'Cognizant', 'Wipro']],
        ],
        'nitt-0006' => [
            ['year' => 2024, 'avg' => 12.5, 'highest' => 52.0, 'median' => 10.8, 'pct' => 88.0, 'placed' => 950, 'recruiters' => ['TCS', 'Infosys', 'Wipro', 'Cognizant', 'Amazon', 'Microsoft', 'L&T']],
            ['year' => 2025, 'avg' => 13.8, 'highest' => 58.0, 'median' => 12.0, 'pct' => 89.5, 'placed' => 1000, 'recruiters' => ['TCS', 'Infosys', 'Wipro', 'Cognizant', 'Amazon', 'Microsoft', 'L&T', 'Bosch']],
        ],
        'nitk-0007' => [
            ['year' => 2024, 'avg' => 12.0, 'highest' => 48.0, 'median' => 10.5, 'pct' => 87.5, 'placed' => 850, 'recruiters' => ['TCS', 'Infosys', 'Wipro', 'Amazon', 'Microsoft', 'Samsung', 'Bosch']],
            ['year' => 2025, 'avg' => 13.2, 'highest' => 55.0, 'median' => 11.8, 'pct' => 89.0, 'placed' => 900, 'recruiters' => ['TCS', 'Infosys', 'Wipro', 'Amazon', 'Microsoft', 'Samsung', 'Bosch', 'Oracle']],
        ],
        'bits-0008' => [
            ['year' => 2024, 'avg' => 16.5, 'highest' => 72.0, 'median' => 14.2, 'pct' => 89.0, 'placed' => 1600, 'recruiters' => ['Google', 'Microsoft', 'Amazon', 'Goldman Sachs', 'Adobe', 'Qualcomm', 'Flipkart']],
            ['year' => 2025, 'avg' => 18.0, 'highest' => 85.0, 'median' => 15.8, 'pct' => 91.0, 'placed' => 1650, 'recruiters' => ['Google', 'Microsoft', 'Amazon', 'Goldman Sachs', 'Adobe', 'Qualcomm', 'Flipkart', 'Atlassian']],
        ],
        'iima-0009' => [
            ['year' => 2024, 'avg' => 34.5, 'highest' => 120.0, 'median' => 30.0, 'pct' => 100.0, 'placed' => 395, 'recruiters' => ['McKinsey & Company', 'BCG', 'Bain & Company', 'Goldman Sachs', 'JP Morgan', 'Google', 'Amazon']],
            ['year' => 2025, 'avg' => 37.2, 'highest' => 135.0, 'median' => 32.5, 'pct' => 100.0, 'placed' => 400, 'recruiters' => ['McKinsey & Company', 'BCG', 'Bain & Company', 'Goldman Sachs', 'JP Morgan', 'Google', 'Amazon', 'Deloitte']],
        ],
        'iimb-0010' => [
            ['year' => 2024, 'avg' => 33.8, 'highest' => 110.0, 'median' => 29.5, 'pct' => 100.0, 'placed' => 520, 'recruiters' => ['McKinsey & Company', 'BCG', 'Bain & Company', 'Goldman Sachs', 'JP Morgan', 'Microsoft', 'Google']],
            ['year' => 2025, 'avg' => 36.5, 'highest' => 125.0, 'median' => 32.0, 'pct' => 100.0, 'placed' => 530, 'recruiters' => ['McKinsey & Company', 'BCG', 'Bain & Company', 'Goldman Sachs', 'JP Morgan', 'Microsoft', 'Google', 'Flipkart']],
        ],
        'aiims-0011' => [
            ['year' => 2024, 'avg' => 12.0, 'highest' => 25.0, 'median' => 10.0, 'pct' => 98.0, 'placed' => 340, 'recruiters' => ['AIIMS Hospital', 'Fortis Healthcare', 'Apollo Hospitals', 'Max Healthcare', 'Manipal Hospitals', 'Medanta']],
            ['year' => 2025, 'avg' => 13.5, 'highest' => 28.0, 'median' => 11.5, 'pct' => 98.5, 'placed' => 345, 'recruiters' => ['AIIMS Hospital', 'Fortis Healthcare', 'Apollo Hospitals', 'Max Healthcare', 'Manipal Hospitals', 'Medanta', 'Narayana Health']],
        ],
        'nlsiu-0012' => [
            ['year' => 2024, 'avg' => 18.0, 'highest' => 55.0, 'median' => 15.0, 'pct' => 95.0, 'placed' => 180, 'recruiters' => ['Cyril Amarchand Mangaldas', 'AZB & Partners', 'Khaitan & Co', 'Luthra & Luthra', 'Shardul Amarchand Mangaldas', 'ICICI Bank']],
            ['year' => 2025, 'avg' => 20.0, 'highest' => 60.0, 'median' => 17.0, 'pct' => 96.0, 'placed' => 185, 'recruiters' => ['Cyril Amarchand Mangaldas', 'AZB & Partners', 'Khaitan & Co', 'Luthra & Luthra', 'Shardul Amarchand Mangaldas', 'ICICI Bank', 'Tata Group']],
        ],
    ];

    // ============================================================
    // CUTOFF DATA
    // ============================================================
    $cutoffs = [
        'iitb-0001' => [
            ['category' => 'General', 'year' => 2024, 'opening' => 1, 'closing' => 110, 'round' => 1],
            ['category' => 'OBC-NCL', 'year' => 2024, 'opening' => 50, 'closing' => 450, 'round' => 1],
            ['category' => 'SC', 'year' => 2024, 'opening' => 100, 'closing' => 800, 'round' => 1],
            ['category' => 'General', 'year' => 2025, 'opening' => 1, 'closing' => 105, 'round' => 1],
        ],
        'iitd-0002' => [
            ['category' => 'General', 'year' => 2024, 'opening' => 1, 'closing' => 100, 'round' => 1],
            ['category' => 'OBC-NCL', 'year' => 2024, 'opening' => 45, 'closing' => 420, 'round' => 1],
            ['category' => 'SC', 'year' => 2024, 'opening' => 90, 'closing' => 750, 'round' => 1],
            ['category' => 'General', 'year' => 2025, 'opening' => 1, 'closing' => 95, 'round' => 1],
        ],
        'iitm-0003' => [
            ['category' => 'General', 'year' => 2024, 'opening' => 1, 'closing' => 95, 'round' => 1],
            ['category' => 'OBC-NCL', 'year' => 2024, 'opening' => 40, 'closing' => 400, 'round' => 1],
            ['category' => 'SC', 'year' => 2024, 'opening' => 85, 'closing' => 700, 'round' => 1],
            ['category' => 'General', 'year' => 2025, 'opening' => 1, 'closing' => 90, 'round' => 1],
        ],
        'iitk-0004' => [
            ['category' => 'General', 'year' => 2024, 'opening' => 1, 'closing' => 130, 'round' => 1],
            ['category' => 'OBC-NCL', 'year' => 2024, 'opening' => 60, 'closing' => 500, 'round' => 1],
            ['category' => 'SC', 'year' => 2024, 'opening' => 120, 'closing' => 850, 'round' => 1],
            ['category' => 'General', 'year' => 2025, 'opening' => 1, 'closing' => 125, 'round' => 1],
        ],
        'iitkgp-0005' => [
            ['category' => 'General', 'year' => 2024, 'opening' => 1, 'closing' => 150, 'round' => 1],
            ['category' => 'OBC-NCL', 'year' => 2024, 'opening' => 70, 'closing' => 550, 'round' => 1],
            ['category' => 'SC', 'year' => 2024, 'opening' => 130, 'closing' => 900, 'round' => 1],
            ['category' => 'General', 'year' => 2025, 'opening' => 1, 'closing' => 145, 'round' => 1],
        ],
        'nitt-0006' => [
            ['category' => 'General', 'year' => 2024, 'opening' => 500, 'closing' => 5000, 'round' => 1],
            ['category' => 'OBC-NCL', 'year' => 2024, 'opening' => 2000, 'closing' => 12000, 'round' => 1],
            ['category' => 'SC', 'year' => 2024, 'opening' => 4000, 'closing' => 20000, 'round' => 1],
        ],
        'nitk-0007' => [
            ['category' => 'General', 'year' => 2024, 'opening' => 600, 'closing' => 5500, 'round' => 1],
            ['category' => 'OBC-NCL', 'year' => 2024, 'opening' => 2500, 'closing' => 13000, 'round' => 1],
            ['category' => 'SC', 'year' => 2024, 'opening' => 5000, 'closing' => 22000, 'round' => 1],
        ],
        'bits-0008' => [
            ['category' => 'General', 'year' => 2024, 'opening' => 100, 'closing' => 3000, 'round' => 1],
            ['category' => 'OBC-NCL', 'year' => 2024, 'opening' => 500, 'closing' => 8000, 'round' => 1],
            ['category' => 'SC', 'year' => 2024, 'opening' => 1000, 'closing' => 15000, 'round' => 1],
        ],
        'iima-0009' => [
            ['category' => 'General', 'year' => 2024, 'opening' => 1, 'closing' => 200, 'round' => 1],
            ['category' => 'OBC-NCL', 'year' => 2024, 'opening' => 100, 'closing' => 800, 'round' => 1],
            ['category' => 'SC', 'year' => 2024, 'opening' => 200, 'closing' => 1500, 'round' => 1],
        ],
        'iimb-0010' => [
            ['category' => 'General', 'year' => 2024, 'opening' => 1, 'closing' => 250, 'round' => 1],
            ['category' => 'OBC-NCL', 'year' => 2024, 'opening' => 120, 'closing' => 900, 'round' => 1],
            ['category' => 'SC', 'year' => 2024, 'opening' => 250, 'closing' => 1800, 'round' => 1],
        ],
        'aiims-0011' => [
            ['category' => 'General', 'year' => 2024, 'opening' => 1, 'closing' => 50, 'round' => 1],
            ['category' => 'OBC-NCL', 'year' => 2024, 'opening' => 25, 'closing' => 300, 'round' => 1],
            ['category' => 'SC', 'year' => 2024, 'opening' => 50, 'closing' => 600, 'round' => 1],
        ],
        'nlsiu-0012' => [
            ['category' => 'General', 'year' => 2024, 'opening' => 1, 'closing' => 100, 'round' => 1],
            ['category' => 'OBC-NCL', 'year' => 2024, 'opening' => 50, 'closing' => 500, 'round' => 1],
            ['category' => 'SC', 'year' => 2024, 'opening' => 100, 'closing' => 1000, 'round' => 1],
        ],
    ];

    // ============================================================
    // FACULTY DATA
    // ============================================================
    $faculty = [
        'iitb-0001' => [
            ['name' => 'Prof. Rajesh K. Sharma', 'designation' => 'Professor', 'dept' => 'Computer Science and Engineering', 'exp' => 25, 'spec' => 'Artificial Intelligence and Machine Learning'],
            ['name' => 'Prof. Meera S. Patel', 'designation' => 'Associate Professor', 'dept' => 'Electrical Engineering', 'exp' => 18, 'spec' => 'VLSI Design and Embedded Systems'],
            ['name' => 'Dr. Anand V. Kulkarni', 'designation' => 'Assistant Professor', 'dept' => 'Mechanical Engineering', 'exp' => 8, 'spec' => 'Thermal Engineering and Fluid Mechanics'],
        ],
        'iitd-0002' => [
            ['name' => 'Prof. Sanjay K. Gupta', 'designation' => 'Professor', 'dept' => 'Computer Science and Engineering', 'exp' => 28, 'spec' => 'Distributed Systems and Cloud Computing'],
            ['name' => 'Prof. Priya R. Singh', 'designation' => 'Associate Professor', 'dept' => 'Electrical Engineering', 'exp' => 16, 'spec' => 'Signal Processing and Communication Systems'],
            ['name' => 'Dr. Vikram M. Reddy', 'designation' => 'Assistant Professor', 'dept' => 'Mechanical Engineering', 'exp' => 7, 'spec' => 'Robotics and Control Systems'],
        ],
        'iitm-0003' => [
            ['name' => 'Prof. K. Ramanathan', 'designation' => 'Professor', 'dept' => 'Computer Science and Engineering', 'exp' => 30, 'spec' => 'Machine Learning and Data Science'],
            ['name' => 'Prof. Lakshmi N. Iyer', 'designation' => 'Associate Professor', 'dept' => 'Electrical Engineering', 'exp' => 20, 'spec' => 'Power Electronics and Drives'],
            ['name' => 'Dr. Suresh Babu R.', 'designation' => 'Assistant Professor', 'dept' => 'Aerospace Engineering', 'exp' => 9, 'spec' => 'Aerodynamics and Flight Mechanics'],
        ],
        'iitk-0004' => [
            ['name' => 'Prof. Ashok K. Misra', 'designation' => 'Professor', 'dept' => 'Computer Science and Engineering', 'exp' => 26, 'spec' => 'Algorithms and Complexity Theory'],
            ['name' => 'Prof. Nandini S. Rao', 'designation' => 'Associate Professor', 'dept' => 'Electrical Engineering', 'exp' => 17, 'spec' => 'Control Systems and Automation'],
            ['name' => 'Dr. Prakash C. Verma', 'designation' => 'Assistant Professor', 'dept' => 'Aerospace Engineering', 'exp' => 6, 'spec' => 'Propulsion Systems and Combustion'],
        ],
        'iitkgp-0005' => [
            ['name' => 'Prof. Subrata K. Das', 'designation' => 'Professor', 'dept' => 'Computer Science and Engineering', 'exp' => 27, 'spec' => 'Computer Networks and Security'],
            ['name' => 'Prof. Arpita M. Banerjee', 'designation' => 'Associate Professor', 'dept' => 'Civil Engineering', 'exp' => 19, 'spec' => 'Structural Engineering and Earthquake Analysis'],
            ['name' => 'Dr. Tapan K. Ghosh', 'designation' => 'Assistant Professor', 'dept' => 'Mechanical Engineering', 'exp' => 10, 'spec' => 'Manufacturing Science and Engineering'],
        ],
        'nitt-0006' => [
            ['name' => 'Prof. R. Palanisamy', 'designation' => 'Professor', 'dept' => 'Computer Science and Engineering', 'exp' => 24, 'spec' => 'Network Security and Cryptography'],
            ['name' => 'Prof. K. Sangeetha', 'designation' => 'Associate Professor', 'dept' => 'Electrical and Electronics Engineering', 'exp' => 15, 'spec' => 'Power Systems and Renewable Energy'],
            ['name' => 'Dr. M. Selvakumar', 'designation' => 'Assistant Professor', 'dept' => 'Mechanical Engineering', 'exp' => 7, 'spec' => 'Thermal Engineering and HVAC'],
        ],
        'nitk-0007' => [
            ['name' => 'Prof. P. Shama Bhat', 'designation' => 'Professor', 'dept' => 'Computer Science and Engineering', 'exp' => 22, 'spec' => 'Artificial Intelligence and Data Mining'],
            ['name' => 'Prof. S. Naveen', 'designation' => 'Associate Professor', 'dept' => 'Electronics and Communication Engineering', 'exp' => 14, 'spec' => 'VLSI and Signal Processing'],
            ['name' => 'Dr. Arun K. Shetty', 'designation' => 'Assistant Professor', 'dept' => 'Civil Engineering', 'exp' => 8, 'spec' => 'Transportation Engineering and Planning'],
        ],
        'bits-0008' => [
            ['name' => 'Prof. M. Balakrishnan', 'designation' => 'Professor', 'dept' => 'Computer Science and Information Systems', 'exp' => 29, 'spec' => 'Software Engineering and DevOps'],
            ['name' => 'Prof. V. Chithambaram', 'designation' => 'Associate Professor', 'dept' => 'Electrical and Electronics Engineering', 'exp' => 16, 'spec' => 'Embedded Systems and IoT'],
            ['name' => 'Dr. Sneha S. Nair', 'designation' => 'Assistant Professor', 'dept' => 'Mechanical Engineering', 'exp' => 9, 'spec' => 'Robotics and Automation'],
        ],
        'iima-0009' => [
            ['name' => 'Prof. Ashish Nanda', 'designation' => 'Professor', 'dept' => 'Organizational Behaviour', 'exp' => 30, 'spec' => 'Leadership and Organizational Design'],
            ['name' => 'Prof. Saral Mukherjee', 'designation' => 'Associate Professor', 'dept' => 'Production and Quantitative Methods', 'exp' => 18, 'spec' => 'Operations Management and Supply Chain'],
            ['name' => 'Dr. Ruchira Gupta', 'designation' => 'Assistant Professor', 'dept' => 'Marketing', 'exp' => 8, 'spec' => 'Consumer Behaviour and Digital Marketing'],
        ],
        'iimb-0010' => [
            ['name' => 'Prof. Suresh Bhagavatula', 'designation' => 'Professor', 'dept' => 'Entrepreneurship', 'exp' => 28, 'spec' => 'Entrepreneurship and Family Business'],
            ['name' => 'Prof. Dinesh Kumar', 'designation' => 'Associate Professor', 'dept' => 'Finance and Accounting', 'exp' => 17, 'spec' => 'Corporate Finance and Investment Banking'],
            ['name' => 'Dr. Anupama Kondayya', 'designation' => 'Assistant Professor', 'dept' => 'Information Systems', 'exp' => 7, 'spec' => 'Business Analytics and Digital Transformation'],
        ],
        'aiims-0011' => [
            ['name' => 'Prof. Randeep Guleria', 'designation' => 'Professor', 'dept' => 'Pulmonary Medicine', 'exp' => 35, 'spec' => 'Pulmonary and Critical Care Medicine'],
            ['name' => 'Prof. Neerja Bhatla', 'designation' => 'Associate Professor', 'dept' => 'Obstetrics and Gynaecology', 'exp' => 22, 'spec' => 'Maternal and Fetal Medicine'],
            ['name' => 'Dr. Sanjeev Kumar', 'designation' => 'Assistant Professor', 'dept' => 'Anaesthesiology', 'exp' => 10, 'spec' => 'Regional Anaesthesia and Pain Management'],
        ],
        'nlsiu-0012' => [
            ['name' => 'Prof. (Dr.) M.P. Singh', 'designation' => 'Professor', 'dept' => 'Constitutional Law', 'exp' => 28, 'spec' => 'Constitutional Law and Public Policy'],
            ['name' => 'Prof. (Dr.) Sudhir Krishnaswamy', 'designation' => 'Associate Professor', 'dept' => 'Corporate Law', 'exp' => 20, 'spec' => 'Corporate Governance and Securities Regulation'],
            ['name' => 'Dr. Varsha Valsala Menon', 'designation' => 'Assistant Professor', 'dept' => 'International Law', 'exp' => 9, 'spec' => 'International Humanitarian Law and Human Rights'],
        ],
    ];

    // ============================================================
    // FAQ DATA
    // ============================================================
    $faqs = [
        'iitb-0001' => [
            ['q' => 'What is the admission process for B.Tech at IIT Bombay?', 'a' => 'Admission to B.Tech programs at IIT Bombay is through JEE Advanced. Candidates must first qualify JEE Main, then appear for JEE Advanced. Admission is based on All India Rank in JEE Advanced through JoSAA counselling.'],
            ['q' => 'What is the total fee for B.Tech at IIT Bombay?', 'a' => 'The total fee for B.Tech at IIT Bombay is approximately INR 8,00,000 for 4 years (INR 2,00,000 per year). This includes tuition fee, hostel fee, and other charges. Scholarships are available for meritorious students.'],
            ['q' => 'How are placements at IIT Bombay?', 'a' => 'IIT Bombay has an excellent placement record with over 94% placement rate. The average package is around INR 22 LPA and the highest package goes up to INR 1.35 Crore per annum. Top recruiters include Google, Microsoft, Goldman Sachs, and Amazon.'],
        ],
        'iitd-0002' => [
            ['q' => 'What is the admission process for B.Tech at IIT Delhi?', 'a' => 'Admission to B.Tech at IIT Delhi is through JEE Advanced. Candidates must qualify JEE Main and then appear for JEE Advanced. Admission is through JoSAA counselling based on All India Rank.'],
            ['q' => 'What is the fee structure for B.Tech at IIT Delhi?', 'a' => 'The total fee for B.Tech at IIT Delhi is approximately INR 8,00,000 for 4 years (INR 2,00,000 per year). This includes tuition, hostel, and mess charges. Fee waivers are available for economically weaker sections.'],
            ['q' => 'What are the placement statistics of IIT Delhi?', 'a' => 'IIT Delhi has a strong placement record with over 93% placement rate. The average package is around INR 21.5 LPA with the highest package reaching INR 1.25 Crore. Top recruiters include Google, Microsoft, Amazon, and Goldman Sachs.'],
        ],
        'iitm-0003' => [
            ['q' => 'What is the admission process for B.Tech at IIT Madras?', 'a' => 'Admission to B.Tech at IIT Madras is through JEE Advanced. Candidates must first qualify JEE Main, then appear for JEE Advanced. Seat allocation is through JoSAA counselling based on All India Rank.'],
            ['q' => 'What is the total fee for B.Tech at IIT Madras?', 'a' => 'The total fee for B.Tech at IIT Madras is approximately INR 8,00,000 for 4 years (INR 2,00,000 per year). This covers tuition, hostel, and other institutional charges. Merit-based scholarships are available.'],
            ['q' => 'How are placements at IIT Madras?', 'a' => 'IIT Madras has an exceptional placement record with 95% placement rate. The average package is around INR 23.2 LPA and the highest package is INR 1.40 Crore. Top recruiters include Google, Microsoft, Amazon, and Apple.'],
        ],
        'iitk-0004' => [
            ['q' => 'What is the admission process for B.Tech at IIT Kanpur?', 'a' => 'Admission to B.Tech at IIT Kanpur is through JEE Advanced. Candidates must qualify JEE Main first, then appear for JEE Advanced. Admission is through JoSAA counselling based on All India Rank.'],
            ['q' => 'What is the fee structure for B.Tech at IIT Kanpur?', 'a' => 'The total fee for B.Tech at IIT Kanpur is approximately INR 8,00,000 for 4 years (INR 2,00,000 per year). This includes tuition, hostel, and mess charges. Scholarships are available for meritorious and economically weaker students.'],
            ['q' => 'What are the placement statistics of IIT Kanpur?', 'a' => 'IIT Kanpur has a strong placement record with 92.5% placement rate. The average package is around INR 20.8 LPA with the highest package reaching INR 1.15 Crore. Top recruiters include Google, Microsoft, Amazon, and Goldman Sachs.'],
        ],
        'iitkgp-0005' => [
            ['q' => 'What is the admission process for B.Tech at IIT Kharagpur?', 'a' => 'Admission to B.Tech at IIT Kharagpur is through JEE Advanced. Candidates must first qualify JEE Main, then appear for JEE Advanced. Seat allocation is through JoSAA counselling based on All India Rank.'],
            ['q' => 'What is the total fee for B.Tech at IIT Kharagpur?', 'a' => 'The total fee for B.Tech at IIT Kharagpur is approximately INR 8,00,000 for 4 years (INR 2,00,000 per year). This covers tuition, hostel, and other institutional charges. Merit-based scholarships are available.'],
            ['q' => 'How are placements at IIT Kharagpur?', 'a' => 'IIT Kharagpur has a good placement record with 92% placement rate. The average package is around INR 20.1 LPA and the highest package is INR 1.10 Crore. Top recruiters include Google, Microsoft, Amazon, and TCS.'],
        ],
        'nitt-0006' => [
            ['q' => 'What is the admission process for B.Tech at NIT Tiruchirappalli?', 'a' => 'Admission to B.Tech at NIT Trichy is through JEE Main. Candidates must qualify JEE Main and apply through CSAB/JoSAA counselling. Admission is based on All India Rank in JEE Main.'],
            ['q' => 'What is the fee structure for B.Tech at NIT Trichy?', 'a' => 'The total fee for B.Tech at NIT Trichy is approximately INR 4,80,000 for 4 years (INR 1,20,000 per year). Government funding makes it more affordable compared to private institutions. Scholarships are available.'],
            ['q' => 'What are the placement statistics of NIT Trichy?', 'a' => 'NIT Trichy has a strong placement record with 89.5% placement rate. The average package is around INR 13.8 LPA with the highest package reaching INR 58 LPA. Top recruiters include TCS, Infosys, Amazon, and Microsoft.'],
        ],
        'nitk-0007' => [
            ['q' => 'What is the admission process for B.Tech at NIT Surathkal?', 'a' => 'Admission to B.Tech at NIT Surathkal is through JEE Main. Candidates must qualify JEE Main and apply through CSAB/JoSAA counselling. Admission is based on All India Rank in JEE Main.'],
            ['q' => 'What is the fee structure for B.Tech at NIT Surathkal?', 'a' => 'The total fee for B.Tech at NIT Surathkal is approximately INR 4,80,000 for 4 years (INR 1,20,000 per year). Being a government institution, the fees are subsidized. Scholarships are available for meritorious students.'],
            ['q' => 'How are placements at NIT Surathkal?', 'a' => 'NIT Surathkal has a good placement record with 89% placement rate. The average package is around INR 13.2 LPA with the highest package reaching INR 55 LPA. Top recruiters include TCS, Infosys, Amazon, and Microsoft.'],
        ],
        'bits-0008' => [
            ['q' => 'What is the admission process for B.E. at BITS Pilani?', 'a' => 'Admission to BITS Pilani is through BITSAT (BITS Admission Test). Candidates must have scored 75% in PCM in Class XII and qualify BITSAT. Admission is based on BITSAT score through iterative counselling.'],
            ['q' => 'What is the fee structure for B.E. at BITS Pilani?', 'a' => 'The total fee for B.E. at BITS Pilani is approximately INR 14,00,000 for 4 years (INR 3,50,000 per year). Being a deemed university, fees are higher but BITS offers merit scholarships and fee waivers.'],
            ['q' => 'What are the placement statistics of BITS Pilani?', 'a' => 'BITS Pilani has a strong placement record with 91% placement rate. The average package is around INR 18 LPA with the highest package reaching INR 85 LPA. Top recruiters include Google, Microsoft, Amazon, and Goldman Sachs.'],
        ],
        'iima-0009' => [
            ['q' => 'What is the admission process for MBA at IIM Ahmedabad?', 'a' => 'Admission to PGP (MBA) at IIM Ahmedabad is through CAT (Common Admission Test). Shortlisted candidates are called for Written Ability Test (WAT) and Personal Interview (PI). Final selection is based on CAT score, WAT, PI, and academic profile.'],
            ['q' => 'What is the fee structure for MBA at IIM Ahmedabad?', 'a' => 'The total fee for PGP (MBA) at IIM Ahmedabad is approximately INR 46,00,000 for 2 years (INR 23,00,000 per year). This includes tuition, hostel, mess, and other charges. Education loans are readily available.'],
            ['q' => 'What are the placement statistics of IIM Ahmedabad?', 'a' => 'IIM Ahmedabad has 100% placement record. The average package is around INR 37.2 LPA with the highest package reaching INR 1.35 Crore. Top recruiters include McKinsey, BCG, Bain, Goldman Sachs, and Google.'],
        ],
        'iimb-0010' => [
            ['q' => 'What is the admission process for MBA at IIM Bangalore?', 'a' => 'Admission to PGP (MBA) at IIM Bangalore is through CAT (Common Admission Test). Shortlisted candidates are called for Written Ability Test (WAT) and Personal Interview (PI). Final selection considers CAT score, WAT, PI, and profile.'],
            ['q' => 'What is the fee structure for MBA at IIM Bangalore?', 'a' => 'The total fee for PGP (MBA) at IIM Bangalore is approximately INR 46,00,000 for 2 years (INR 23,00,000 per year). This includes tuition, accommodation, and other institutional charges. Financial assistance is available.'],
            ['q' => 'How are placements at IIM Bangalore?', 'a' => 'IIM Bangalore has 100% placement record. The average package is around INR 36.5 LPA with the highest package reaching INR 1.25 Crore. Top recruiters include McKinsey, BCG, Goldman Sachs, JP Morgan, and Google.'],
        ],
        'aiims-0011' => [
            ['q' => 'What is the admission process for MBBS at AIIMS?', 'a' => 'Admission to MBBS at AIIMS Delhi is through NEET UG (National Eligibility cum Entrance Test). Candidates must qualify NEET UG with a top All India Rank. AIIMS accepts NEET scores for MBBS admission since 2020.'],
            ['q' => 'What is the fee structure for MBBS at AIIMS?', 'a' => 'The total fee for MBBS at AIIMS Delhi is extremely affordable at approximately INR 8,250 for 5.5 years (INR 1,500 per year). AIIMS is fully funded by the Government of India, making it one of the most affordable medical education options.'],
            ['q' => 'What are the placement statistics of AIIMS Delhi?', 'a' => 'AIIMS Delhi has a 98.5% placement record. The average package is around INR 13.5 LPA with the highest package reaching INR 28 LPA. Most graduates join AIIMS Hospital, Fortis, Apollo, or pursue super-specialty courses.'],
        ],
        'nlsiu-0012' => [
            ['q' => 'What is the admission process for BA LLB at NLSIU?', 'a' => 'Admission to BA LLB at NLSIU is through CLAT (Common Law Admission Test). Candidates must qualify CLAT with a top All India Rank. NLSIU is one of the most sought-after law schools accepting CLAT scores.'],
            ['q' => 'What is the fee structure for BA LLB at NLSIU?', 'a' => 'The total fee for BA LLB at NLSIU is approximately INR 12,50,000 for 5 years (INR 2,50,000 per year). This includes tuition, hostel, and other institutional charges. Scholarships are available for meritorious students.'],
            ['q' => 'How are placements at NLSIU Bangalore?', 'a' => 'NLSIU has a 96% placement record. The average package is around INR 20 LPA with the highest package reaching INR 60 LPA. Top recruiters include Cyril Amarchand Mangaldas, AZB & Partners, Khaitan & Co, and Tata Group.'],
        ],
    ];

    // ============================================================
    // SCHOLARSHIP DATA
    // ============================================================
    $scholarships = [
        'iitb-0001' => [
            ['name' => 'Institute Free Studentship', 'type' => 'Need-based', 'amount' => 'Full tuition waiver', 'eligibility' => 'Family income below INR 5 LPA'],
            ['name' => 'Institute Merit-cum-Means Scholarship', 'type' => 'Merit-cum-Need', 'amount' => 'INR 1,00,000 per year', 'eligibility' => 'Top 10% of department with family income below INR 8 LPA'],
        ],
        'iitd-0002' => [
            ['name' => 'Institute Free Studentship', 'type' => 'Need-based', 'amount' => 'Full tuition waiver', 'eligibility' => 'Family income below INR 5 LPA'],
            ['name' => 'Institute Merit-cum-Means Scholarship', 'type' => 'Merit-cum-Need', 'amount' => 'INR 1,00,000 per year', 'eligibility' => 'Top 10% of department with family income below INR 8 LPA'],
        ],
        'iitm-0003' => [
            ['name' => 'Institute Free Studentship', 'type' => 'Need-based', 'amount' => 'Full tuition waiver', 'eligibility' => 'Family income below INR 5 LPA'],
            ['name' => 'Institute Merit-cum-Means Scholarship', 'type' => 'Merit-cum-Need', 'amount' => 'INR 1,00,000 per year', 'eligibility' => 'Top 10% of department with family income below INR 8 LPA'],
        ],
        'iitk-0004' => [
            ['name' => 'Institute Free Studentship', 'type' => 'Need-based', 'amount' => 'Full tuition waiver', 'eligibility' => 'Family income below INR 5 LPA'],
            ['name' => 'Institute Merit-cum-Means Scholarship', 'type' => 'Merit-cum-Need', 'amount' => 'INR 1,00,000 per year', 'eligibility' => 'Top 10% of department with family income below INR 8 LPA'],
        ],
        'iitkgp-0005' => [
            ['name' => 'Institute Free Studentship', 'type' => 'Need-based', 'amount' => 'Full tuition waiver', 'eligibility' => 'Family income below INR 5 LPA'],
            ['name' => 'Institute Merit-cum-Means Scholarship', 'type' => 'Merit-cum-Need', 'amount' => 'INR 1,00,000 per year', 'eligibility' => 'Top 10% of department with family income below INR 8 LPA'],
        ],
        'nitt-0006' => [
            ['name' => 'Government of India Merit Scholarship', 'type' => 'Merit-based', 'amount' => 'INR 20,000 per year', 'eligibility' => 'Top 10% of department'],
            ['name' => 'SC/ST Scholarship', 'type' => 'Need-based', 'amount' => 'Full tuition waiver + stipend', 'eligibility' => 'SC/ST category with family income below INR 6 LPA'],
        ],
        'nitk-0007' => [
            ['name' => 'Government of India Merit Scholarship', 'type' => 'Merit-based', 'amount' => 'INR 20,000 per year', 'eligibility' => 'Top 10% of department'],
            ['name' => 'SC/ST Scholarship', 'type' => 'Need-based', 'amount' => 'Full tuition waiver + stipend', 'eligibility' => 'SC/ST category with family income below INR 6 LPA'],
        ],
        'bits-0008' => [
            ['name' => 'BITS Merit Scholarship', 'type' => 'Merit-based', 'amount' => 'INR 1,00,000 per year', 'eligibility' => 'BITSAT score above 350'],
            ['name' => 'BITS Need-based Scholarship', 'type' => 'Need-based', 'amount' => 'Up to 50% tuition waiver', 'eligibility' => 'Family income below INR 8 LPA'],
        ],
        'iima-0009' => [
            ['name' => 'IIMA Need-Based Financial Assistance', 'type' => 'Need-based', 'amount' => 'Full tuition waiver to 50% waiver', 'eligibility' => 'Family income below INR 8 LPA'],
            ['name' => 'IIMA Merit Scholarship', 'type' => 'Merit-based', 'amount' => 'INR 2,00,000 per year', 'eligibility' => 'Top 10% of batch'],
        ],
        'iimb-0010' => [
            ['name' => 'IIMB Need-Based Financial Assistance', 'type' => 'Need-based', 'amount' => 'Full tuition waiver to 50% waiver', 'eligibility' => 'Family income below INR 8 LPA'],
            ['name' => 'IIMB Merit Scholarship', 'type' => 'Merit-based', 'amount' => 'INR 2,00,000 per year', 'eligibility' => 'Top 10% of batch'],
        ],
        'aiims-0011' => [
            ['name' => 'Government of India Scholarship', 'type' => 'Merit-based', 'amount' => 'Full tuition waiver + stipend', 'eligibility' => 'All admitted students (AIIMS is fully funded)'],
        ],
        'nlsiu-0012' => [
            ['name' => 'NLSIU Merit Scholarship', 'type' => 'Merit-based', 'amount' => 'INR 50,000 per year', 'eligibility' => 'Top 10% of batch'],
            ['name' => 'NLSIU Need-Based Scholarship', 'type' => 'Need-based', 'amount' => 'Up to 50% tuition waiver', 'eligibility' => 'Family income below INR 6 LPA'],
        ],
    ];

    // ============================================================
    // NOW SEED ALL DATA
    // ============================================================

    $totalQueries = 0;

    // ---- 1. SEED colleges ----
    echo "[1/16] Seeding colleges table...\n";
    $stmt = $pdo->prepare("INSERT INTO colleges (
        id, name, slug, college_type, ownership, status, is_featured, is_verified, featured_order,
        ranking_nirf, city_id, state_id, established_year, university_affiliation, naac_grade,
        ugc_approved, total_students, total_faculty, campus_area_acres, overall_rating_avg,
        total_reviews, publish_status
    ) VALUES (
        :id, :name, :slug, :college_type, :ownership, 'active', :is_featured, 1, :featured_order,
        :ranking_nirf, :city_id, :state_id, :established_year, :university_affiliation, :naac_grade,
        1, :total_students, :total_faculty, :campus_area_acres, :overall_rating_avg,
        :total_reviews, 'published'
    )");

    foreach ($colleges as $cid => $c) {
        $collegeId = "col-{$cid}";
        $stmt->execute([
            ':id' => $collegeId,
            ':name' => $c['name'],
            ':slug' => $c['slug'],
            ':college_type' => $c['college_type'],
            ':ownership' => $c['ownership'],
            ':is_featured' => $c['is_featured'],
            ':featured_order' => $c['featured_order'],
            ':ranking_nirf' => $c['ranking_nirf'],
            ':city_id' => $c['city_id'],
            ':state_id' => $c['state_id'],
            ':established_year' => $c['established_year'],
            ':university_affiliation' => $c['university_affiliation'],
            ':naac_grade' => $c['naac_grade'],
            ':total_students' => $c['total_students'],
            ':total_faculty' => $c['total_faculty'],
            ':campus_area_acres' => $c['campus_area_acres'],
            ':overall_rating_avg' => $c['overall_rating_avg'],
            ':total_reviews' => $c['total_reviews'],
        ]);
        $totalQueries++;
    }
    echo "  -> Inserted " . count($colleges) . " colleges\n";

    // ---- 2. SEED college_media ----
    echo "[2/16] Seeding college_media table...\n";
    $stmt = $pdo->prepare("INSERT INTO college_media (id, college_id, logo_url, cover_image_url, image_type)
        VALUES (:id, :college_id, :logo_url, :cover_image_url, 'campus')");

    foreach ($colleges as $cid => $c) {
        $collegeId = "col-{$cid}";
        $stmt->execute([
            ':id' => uid(),
            ':college_id' => $collegeId,
            ':logo_url' => $c['logo_url'],
            ':cover_image_url' => $c['cover_image_url'],
        ]);
        $totalQueries++;
    }
    echo "  -> Inserted " . count($colleges) . " media records\n";

    // ---- 3. SEED college_content ----
    echo "[3/16] Seeding college_content table...\n";
    $stmt = $pdo->prepare("INSERT INTO college_content (id, college_id, about_text, highlights_json, accreditations_json, rankings_json)
        VALUES (:id, :college_id, :about_text, :highlights_json, :accreditations_json, :rankings_json)");

    foreach ($colleges as $cid => $c) {
        $collegeId = "col-{$cid}";
        $stmt->execute([
            ':id' => uid(),
            ':college_id' => $collegeId,
            ':about_text' => $c['about'],
            ':highlights_json' => json_encode($c['highlights']),
            ':accreditations_json' => json_encode($c['accreditations']),
            ':rankings_json' => json_encode($c['rankings']),
        ]);
        $totalQueries++;
    }
    echo "  -> Inserted " . count($colleges) . " content records\n";

    // ---- 4. SEED college_contacts ----
    echo "[4/16] Seeding college_contacts table...\n";
    $stmt = $pdo->prepare("INSERT INTO college_contacts (id, college_id, website_url, email, phone, address, pincode)
        VALUES (:id, :college_id, :website_url, :email, :phone, :address, :pincode)");

    foreach ($colleges as $cid => $c) {
        $collegeId = "col-{$cid}";
        $stmt->execute([
            ':id' => uid(),
            ':college_id' => $collegeId,
            ':website_url' => $c['website'],
            ':email' => $c['email'],
            ':phone' => $c['phone'],
            ':address' => $c['address'],
            ':pincode' => $c['pincode'],
        ]);
        $totalQueries++;
    }
    echo "  -> Inserted " . count($colleges) . " contact records\n";

    // ---- 5. SEED college_courses ----
    echo "[5/16] Seeding college_courses table...\n";
    $stmt = $pdo->prepare("INSERT INTO college_courses (id, college_id, course_name, course_level, duration_years, annual_fee, total_fee, seats_available, eligibility_criteria, specializations)
        VALUES (:id, :college_id, :course_name, :course_level, :duration_years, :annual_fee, :total_fee, :seats_available, :eligibility_criteria, :specializations)");

    $courseCount = 0;
    foreach ($courses as $cid => $courseList) {
        $collegeId = "col-{$cid}";
        foreach ($courseList as $course) {
            $stmt->execute([
                ':id' => uid(),
                ':college_id' => $collegeId,
                ':course_name' => $course['name'],
                ':course_level' => $course['level'],
                ':duration_years' => $course['duration'],
                ':annual_fee' => $course['annual_fee'],
                ':total_fee' => $course['total_fee'],
                ':seats_available' => $course['seats'],
                ':eligibility_criteria' => $course['eligibility'],
                ':specializations' => json_encode($course['specializations']),
            ]);
            $courseCount++;
            $totalQueries++;
        }
    }
    echo "  -> Inserted {$courseCount} course records\n";

    // ---- 6. SEED college_admissions ----
    echo "[6/16] Seeding college_admissions table...\n";
    $admissionsData = [
        'iitb-0001' => [
            'process' => 'Admission to IIT Bombay is through JEE Advanced examination. Candidates must first qualify JEE Main, then appear for JEE Advanced. Seats are allotted through JoSAA counselling based on All India Rank, category, and seat availability.',
            'exams' => ['JEE Advanced', 'JEE Main'],
            'criteria' => 'Selection is based on All India Rank in JEE Advanced. Counselling is conducted through JoSAA with multiple rounds of seat allocation.',
        ],
        'iitd-0002' => [
            'process' => 'Admission to IIT Delhi is through JEE Advanced examination. Candidates must first qualify JEE Main, then appear for JEE Advanced. Seats are allotted through JoSAA counselling.',
            'exams' => ['JEE Advanced', 'JEE Main'],
            'criteria' => 'Selection is based on All India Rank in JEE Advanced. JoSAA counselling with multiple rounds.',
        ],
        'iitm-0003' => [
            'process' => 'Admission to IIT Madras is through JEE Advanced examination. Candidates must qualify JEE Main and then appear for JEE Advanced. Seat allocation is through JoSAA counselling.',
            'exams' => ['JEE Advanced', 'JEE Main'],
            'criteria' => 'Selection based on JEE Advanced All India Rank. JoSAA counselling with multiple rounds of seat allocation.',
        ],
        'iitk-0004' => [
            'process' => 'Admission to IIT Kanpur is through JEE Advanced. Candidates must qualify JEE Main first. Seats are allotted through JoSAA counselling based on rank and preferences.',
            'exams' => ['JEE Advanced', 'JEE Main'],
            'criteria' => 'Selection based on JEE Advanced rank. JoSAA counselling with multiple rounds.',
        ],
        'iitkgp-0005' => [
            'process' => 'Admission to IIT Kharagpur is through JEE Advanced. Candidates must qualify JEE Main and then appear for JEE Advanced. Seat allocation through JoSAA counselling.',
            'exams' => ['JEE Advanced', 'JEE Main'],
            'criteria' => 'Selection based on JEE Advanced All India Rank. JoSAA counselling with multiple rounds.',
        ],
        'nitt-0006' => [
            'process' => 'Admission to NIT Trichy is through JEE Main. Candidates must qualify JEE Main and apply through CSAB/JoSAA counselling. Seats are allotted based on All India Rank in JEE Main.',
            'exams' => ['JEE Main'],
            'criteria' => 'Selection based on JEE Main All India Rank. CSAB/JoSAA counselling with multiple rounds.',
        ],
        'nitk-0007' => [
            'process' => 'Admission to NIT Surathkal is through JEE Main. Candidates must qualify JEE Main and apply through CSAB/JoSAA counselling.',
            'exams' => ['JEE Main'],
            'criteria' => 'Selection based on JEE Main All India Rank. CSAB/JoSAA counselling.',
        ],
        'bits-0008' => [
            'process' => 'Admission to BITS Pilani is through BITSAT (BITS Admission Test). Candidates must have 75% aggregate in PCM in Class XII and qualify BITSAT. Admission is through iterative counselling based on BITSAT score.',
            'exams' => ['BITSAT'],
            'criteria' => 'Selection based on BITSAT score. Iterative counselling rounds with preference filling.',
        ],
        'iima-0009' => [
            'process' => 'Admission to IIM Ahmedabad PGP is through CAT (Common Admission Test). Shortlisted candidates are called for Written Ability Test (WAT) and Personal Interview (PI).',
            'exams' => ['CAT', 'GMAT'],
            'criteria' => 'Final selection based on CAT score, WAT performance, PI, and academic profile. Composite score used for ranking.',
        ],
        'iimb-0010' => [
            'process' => 'Admission to IIM Bangalore PGP is through CAT (Common Admission Test). Shortlisted candidates undergo WAT and PI.',
            'exams' => ['CAT', 'GMAT'],
            'criteria' => 'Final selection based on CAT score, WAT, PI, and academic diversity factors.',
        ],
        'aiims-0011' => [
            'process' => 'Admission to AIIMS Delhi MBBS is through NEET UG (National Eligibility cum Entrance Test). Candidates must qualify NEET UG with a top All India Rank.',
            'exams' => ['NEET UG'],
            'criteria' => 'Selection based on NEET UG All India Rank. counselling through MCC for All India Quota seats.',
        ],
        'nlsiu-0012' => [
            'process' => 'Admission to NLSIU BA LLB is through CLAT (Common Law Admission Test). Candidates must qualify CLAT with a top All India Rank.',
            'exams' => ['CLAT'],
            'criteria' => 'Selection based on CLAT All India Rank. Counselling through CLAT Consortium.',
        ],
    ];

    $stmt = $pdo->prepare("INSERT INTO college_admissions (id, college_id, admission_process, accepted_exams, merit_based, application_mode, selection_criteria)
        VALUES (:id, :college_id, :admission_process, :accepted_exams, 1, 'online', :selection_criteria)");

    foreach ($admissionsData as $cid => $a) {
        $stmt->execute([
            ':id' => uid(),
            ':college_id' => "col-{$cid}",
            ':admission_process' => $a['process'],
            ':accepted_exams' => json_encode($a['exams']),
            ':selection_criteria' => $a['criteria'],
        ]);
        $totalQueries++;
    }
    echo "  -> Inserted " . count($admissionsData) . " admission records\n";

    // ---- 7. SEED college_placements ----
    echo "[7/16] Seeding college_placements table...\n";
    $stmt = $pdo->prepare("INSERT INTO college_placements (id, college_id, placement_year, avg_package_lpa, highest_package_lpa, median_package_lpa, placement_percentage, students_placed, top_recruiters)
        VALUES (:id, :college_id, :placement_year, :avg_package_lpa, :highest_package_lpa, :median_package_lpa, :placement_percentage, :students_placed, :top_recruiters)");

    $plCount = 0;
    foreach ($placements as $cid => $plList) {
        $collegeId = "col-{$cid}";
        foreach ($plList as $pl) {
            $stmt->execute([
                ':id' => uid(),
                ':college_id' => $collegeId,
                ':placement_year' => $pl['year'],
                ':avg_package_lpa' => $pl['avg'],
                ':highest_package_lpa' => $pl['highest'],
                ':median_package_lpa' => $pl['median'],
                ':placement_percentage' => $pl['pct'],
                ':students_placed' => $pl['placed'],
                ':top_recruiters' => json_encode($pl['recruiters']),
            ]);
            $plCount++;
            $totalQueries++;
        }
    }
    echo "  -> Inserted {$plCount} placement records\n";

    // ---- 8. SEED college_cutoffs ----
    echo "[8/16] Seeding college_cutoffs table...\n";
    $stmt = $pdo->prepare("INSERT INTO college_cutoffs (id, college_id, exam_id, category, year, opening_rank, closing_rank, round_number)
        VALUES (:id, :college_id, NULL, :category, :year, :opening_rank, :closing_rank, :round_number)");

    $cutCount = 0;
    foreach ($cutoffs as $cid => $cutList) {
        $collegeId = "col-{$cid}";
        foreach ($cutList as $cut) {
            $stmt->execute([
                ':id' => uid(),
                ':college_id' => $collegeId,
                ':category' => $cut['category'],
                ':year' => $cut['year'],
                ':opening_rank' => $cut['opening'],
                ':closing_rank' => $cut['closing'],
                ':round_number' => $cut['round'],
            ]);
            $cutCount++;
            $totalQueries++;
        }
    }
    echo "  -> Inserted {$cutCount} cutoff records\n";

    // ---- 9. SEED college_infrastructure ----
    echo "[9/16] Seeding college_infrastructure table...\n";
    $infraData = [
        'iitb-0001' => ['books' => 500000],
        'iitd-0002' => ['books' => 450000],
        'iitm-0003' => ['books' => 520000],
        'iitk-0004' => ['books' => 400000],
        'iitkgp-0005' => ['books' => 600000],
        'nitt-0006' => ['books' => 300000],
        'nitk-0007' => ['books' => 280000],
        'bits-0008' => ['books' => 350000],
        'iima-0009' => ['books' => 150000],
        'iimb-0010' => ['books' => 140000],
        'aiims-0011' => ['books' => 200000],
        'nlsiu-0012' => ['books' => 100000],
    ];

    $stmt = $pdo->prepare("INSERT INTO college_infrastructure (id, college_id, library, library_books_count, auditorium, cafeteria, wifi, medical_facility, transport)
        VALUES (:id, :college_id, 1, :library_books_count, 1, 1, 1, 1, 1)");

    foreach ($infraData as $cid => $infra) {
        $stmt->execute([
            ':id' => uid(),
            ':college_id' => "col-{$cid}",
            ':library_books_count' => $infra['books'],
        ]);
        $totalQueries++;
    }
    echo "  -> Inserted " . count($infraData) . " infrastructure records\n";

    // ---- 10. SEED college_hostels ----
    echo "[10/16] Seeding college_hostels table...\n";
    $hostelData = [
        'iitb-0001'    => ['capacity' => 8000, 'fee' => 50000],
        'iitd-0002'    => ['capacity' => 7500, 'fee' => 55000],
        'iitm-0003'    => ['capacity' => 8500, 'fee' => 45000],
        'iitk-0004'    => ['capacity' => 7000, 'fee' => 48000],
        'iitkgp-0005'  => ['capacity' => 9000, 'fee' => 42000],
        'nitt-0006'    => ['capacity' => 5500, 'fee' => 35000],
        'nitk-0007'    => ['capacity' => 5000, 'fee' => 33000],
        'bits-0008'    => ['capacity' => 10000, 'fee' => 80000],
        'iima-0009'    => ['capacity' => 1200, 'fee' => 150000],
        'iimb-0010'    => ['capacity' => 1100, 'fee' => 145000],
        'aiims-0011'   => ['capacity' => 2000, 'fee' => 15000],
        'nlsiu-0012'   => ['capacity' => 800, 'fee' => 60000],
    ];

    $stmt = $pdo->prepare("INSERT INTO college_hostels (id, college_id, hostel_available, hostel_type, hostel_capacity, hostel_fee_annual, mess_available, mess_type, ac_available)
        VALUES (:id, :college_id, 1, 'both', :hostel_capacity, :hostel_fee_annual, 1, 'both', 1)");

    foreach ($hostelData as $cid => $h) {
        $stmt->execute([
            ':id' => uid(),
            ':college_id' => "col-{$cid}",
            ':hostel_capacity' => $h['capacity'],
            ':hostel_fee_annual' => $h['fee'],
        ]);
        $totalQueries++;
    }
    echo "  -> Inserted " . count($hostelData) . " hostel records\n";

    // ---- 11. SEED college_faculty ----
    echo "[11/16] Seeding college_faculty table...\n";
    $stmt = $pdo->prepare("INSERT INTO college_faculty (id, college_id, faculty_name, designation, department, qualification, experience_years, specialization)
        VALUES (:id, :college_id, :faculty_name, :designation, :department, 'PhD', :experience_years, :specialization)");

    $facCount = 0;
    foreach ($faculty as $cid => $facList) {
        $collegeId = "col-{$cid}";
        foreach ($facList as $fac) {
            $stmt->execute([
                ':id' => uid(),
                ':college_id' => $collegeId,
                ':faculty_name' => $fac['name'],
                ':designation' => $fac['designation'],
                ':department' => $fac['dept'],
                ':experience_years' => $fac['exp'],
                ':specialization' => $fac['spec'],
            ]);
            $facCount++;
            $totalQueries++;
        }
    }
    echo "  -> Inserted {$facCount} faculty records\n";

    // ---- 12. SEED college_faqs ----
    echo "[12/16] Seeding college_faqs table...\n";
    $stmt = $pdo->prepare("INSERT INTO college_faqs (id, college_id, question_text, answer_text, sort_order, is_active)
        VALUES (:id, :college_id, :question_text, :answer_text, :sort_order, 1)");

    $faqCount = 0;
    foreach ($faqs as $cid => $faqList) {
        $collegeId = "col-{$cid}";
        $order = 1;
        foreach ($faqList as $faq) {
            $stmt->execute([
                ':id' => uid(),
                ':college_id' => $collegeId,
                ':question_text' => $faq['q'],
                ':answer_text' => $faq['a'],
                ':sort_order' => $order,
            ]);
            $order++;
            $faqCount++;
            $totalQueries++;
        }
    }
    echo "  -> Inserted {$faqCount} FAQ records\n";

    // ---- 13. SEED college_scholarships ----
    echo "[13/16] Seeding college_scholarships table...\n";
    $stmt = $pdo->prepare("INSERT INTO college_scholarships (id, college_id, scholarship_name, scholarship_type, amount, eligibility_criteria)
        VALUES (:id, :college_id, :scholarship_name, :scholarship_type, :amount, :eligibility_criteria)");

    $schCount = 0;
    foreach ($scholarships as $cid => $schList) {
        $collegeId = "col-{$cid}";
        foreach ($schList as $sch) {
            $stmt->execute([
                ':id' => uid(),
                ':college_id' => $collegeId,
                ':scholarship_name' => $sch['name'],
                ':scholarship_type' => $sch['type'],
                ':amount' => $sch['amount'],
                ':eligibility_criteria' => $sch['eligibility'],
            ]);
            $schCount++;
            $totalQueries++;
        }
    }
    echo "  -> Inserted {$schCount} scholarship records\n";

    // ---- 14. SEED college_accreditations ----
    echo "[14/16] Seeding college_accreditations table...\n";
    $accreditationData = [
        'iitb-0001' => ['body' => 'NAAC', 'grade' => 'A++', 'year' => 2022],
        'iitd-0002' => ['body' => 'NAAC', 'grade' => 'A++', 'year' => 2023],
        'iitm-0003' => ['body' => 'NAAC', 'grade' => 'A++', 'year' => 2022],
        'iitk-0004' => ['body' => 'NAAC', 'grade' => 'A++', 'year' => 2023],
        'iitkgp-0005' => ['body' => 'NAAC', 'grade' => 'A++', 'year' => 2022],
        'nitt-0006' => ['body' => 'NAAC', 'grade' => 'A++', 'year' => 2023],
        'nitk-0007' => ['body' => 'NAAC', 'grade' => 'A++', 'year' => 2022],
        'bits-0008' => ['body' => 'NAAC', 'grade' => 'A++', 'year' => 2023],
        'iima-0009' => ['body' => 'NAAC', 'grade' => 'A++', 'year' => 2022],
        'iimb-0010' => ['body' => 'NAAC', 'grade' => 'A++', 'year' => 2023],
        'aiims-0011' => ['body' => 'NAAC', 'grade' => 'A++', 'year' => 2022],
        'nlsiu-0012' => ['body' => 'NAAC', 'grade' => 'A++', 'year' => 2023],
    ];

    $stmt = $pdo->prepare("INSERT INTO college_accreditations (id, college_id, accreditation_body, accreditation_grade, accreditation_year)
        VALUES (:id, :college_id, :accreditation_body, :accreditation_grade, :accreditation_year)");

    foreach ($accreditationData as $cid => $ac) {
        $stmt->execute([
            ':id' => uid(),
            ':college_id' => "col-{$cid}",
            ':accreditation_body' => $ac['body'],
            ':accreditation_grade' => $ac['grade'],
            ':accreditation_year' => $ac['year'],
        ]);
        $totalQueries++;
    }
    echo "  -> Inserted " . count($accreditationData) . " accreditation records\n";

    // ---- 15. SEED rankings ----
    echo "[15/16] Seeding rankings table...\n";
    // Note: rankings.id is AUTO_INCREMENT, so we do NOT insert id
    $stmt = $pdo->prepare("INSERT INTO rankings (ranking_body, ranking_year, category, college_id, rank_position, score)
        VALUES (:ranking_body, :ranking_year, :category, :college_id, :rank_position, :score)");

    $rankCount = 0;
    foreach ($colleges as $cid => $c) {
        $collegeId = "col-{$cid}";
        foreach ($c['rankings'] as $rank) {
            $stmt->execute([
                ':ranking_body' => $rank['body'],
                ':ranking_year' => $rank['year'],
                ':category' => $rank['category'],
                ':college_id' => $collegeId,
                ':rank_position' => $rank['rank'],
                ':score' => $rank['score'],
            ]);
            $rankCount++;
            $totalQueries++;
        }
    }
    echo "  -> Inserted {$rankCount} ranking records\n";

    // ---- 16. SEED college_updates ----
    echo "[16/16] Seeding college_updates table...\n";
    $stmt = $pdo->prepare("INSERT INTO college_updates (id, college_id, update_type, title, description, event_date, status)
        VALUES (:id, :college_id, :update_type, :title, :description, :event_date, 'published')");

    $updateCount = 0;
    foreach ($colleges as $cid => $c) {
        $collegeId = "col-{$cid}";
        $collegeName = $c['name'];
        $slug = $c['slug'];
        $established = $c['established_year'];

        // Update 1: Admission announcement
        $stmt->execute([
            ':id' => uid(),
            ':college_id' => $collegeId,
            ':update_type' => 'admission',
            ':title' => "{$collegeName} Admission 2026 Open",
            ':description' => "Applications for {$collegeName} are now open for the academic session 2026-27. Interested candidates must appear for the relevant entrance examination and apply through the official counselling process.",
            ':event_date' => date('Y-m-d', strtotime('+30 days')),
        ]);
        $updateCount++;
        $totalQueries++;

        // Update 2: Placement update
        $stmt->execute([
            ':id' => uid(),
            ':college_id' => $collegeId,
            ':update_type' => 'placement',
            ':title' => "{$collegeName} Placement Season 2025 - Record Breaking Numbers",
            ':description' => "{$collegeName} has concluded its 2025 placement season with outstanding results. The institute achieved over 90% placement with top packages exceeding INR 1 Crore per annum. Leading companies from various sectors participated in the campus recruitment drive.",
            ':event_date' => date('Y-m-d', strtotime('-15 days')),
        ]);
        $updateCount++;
        $totalQueries++;
    }
    echo "  -> Inserted {$updateCount} update records\n";

    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");

    echo "\n=== SEEDING COMPLETE ===\n";
    echo "Total queries executed: {$totalQueries}\n";
    echo "Total colleges seeded: " . count($colleges) . "\n";
    echo "Tables populated: colleges, college_media, college_content, college_contacts,\n";
    echo "  college_courses, college_admissions, college_placements, college_cutoffs,\n";
    echo "  college_infrastructure, college_hostels, college_faculty, college_faqs,\n";
    echo "  college_scholarships, college_accreditations, rankings, college_updates\n";

} catch (PDOException $e) {
    echo "DATABASE ERROR: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . " Line: " . $e->getLine() . "\n";
    exit(1);
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . " Line: " . $e->getLine() . "\n";
    exit(1);
}
