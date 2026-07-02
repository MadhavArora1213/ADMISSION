<?php
/**
 * Seed Real Indian Universities
 * Run: php seed_universities.php
 */
require_once 'admin/db.php';
require_once 'includes/college_helpers.php';

$ok = 0; $err = 0;

function uRun(PDO $pdo, string $sql, string $label, array $params = []) {
    global $ok, $err;
    try {
        $s = $pdo->prepare($sql); $s->execute($params); $ok++;
    } catch (Exception $e) {
        echo "ERR [$label]: " . $e->getMessage() . "\n"; $err++;
    }
}

// State/City IDs:
// 9=Delhi(NCT), 11=Gujarat, 16=Karnataka, 20=Maharashtra, 28=Rajasthan, 30=Tamil Nadu, 34=UP, 35=WB
// 139=New Delhi, 150=Ahmedabad, 267=Bengaluru Urban, 393=Pune, 544=Chennai, 713=Kolkata, 521=Jaipur

$universities = [
    // IIM Ahmedabad
    [
        '02f1f361-2b42-446e-bc59-4d7a7ac3a0fb', 'Indian Institute of Management Ahmedabad', 'iim-ahmedabad',
        'https://images.unsplash.com/photo-1562774053-701939374585?w=800&q=80',
        'https://images.unsplash.com/photo-1562774053-701939374585?w=400&q=80',
        'govt', 'central', 'active', 1, 1, 1,
        1, null, 11, 150, 1961,
        'A++', 1, 1, 1, 1200, 120, 107.0,
        'IIM Ahmedabad is one of the premier business schools in India, established in 1961. It is consistently ranked as the top management institute in the country. The institute offers MBA, PGPX, and doctoral programs with world-class faculty and infrastructure.',
    ],
    // IIT Bombay
    [
        'a1b2c3d4-e5f6-7890-abcd-ef1234567890', 'Indian Institute of Technology Bombay', 'iit-bombay',
        'https://images.unsplash.com/photo-1562774053-701939374585?w=800&q=80',
        'https://images.unsplash.com/photo-1562774053-701939374585?w=400&q=80',
        'govt', 'central', 'active', 1, 1, 1,
        2, null, 20, 393, 1958,
        'A++', 1, 1, 1, 12000, 650, 585.0,
        'IIT Bombay is one of the most prestigious engineering institutions in India. Established in 1958, it offers B.Tech, M.Tech, MBA, and PhD programs. Known for its cutting-edge research and innovation ecosystem.',
    ],
    // Delhi University
    [
        'b2c3d4e5-f6a7-8901-bcde-f12345678901', 'University of Delhi', 'delhi-university',
        'https://images.unsplash.com/photo-1562774053-701939374585?w=800&q=80',
        'https://images.unsplash.com/photo-1562774053-701939374585?w=400&q=80',
        'govt', 'central', 'active', 1, 1, 1,
        3, null, 9, 139, 1922,
        'A++', 1, 1, 0, 300000, 4500, 90.0,
        'University of Delhi is one of the largest and most prestigious universities in India. Established in 1922, it offers undergraduate, postgraduate, and doctoral programs across arts, science, commerce, and professional courses.',
    ],
    // IIT Madras
    [
        'c3d4e5f6-a7b8-9012-cdef-123456789012', 'Indian Institute of Technology Madras', 'iit-madras',
        'https://images.unsplash.com/photo-1562774053-701939374585?w=800&q=80',
        'https://images.unsplash.com/photo-1562774053-701939374585?w=400&q=80',
        'govt', 'central', 'active', 1, 1, 1,
        4, null, 30, 544, 1959,
        'A++', 1, 1, 1, 10000, 600, 520.0,
        'IIT Madras is ranked as the top engineering institute in India. Established in 1959, it offers B.Tech, M.Tech, MS, MBA, and PhD programs. Known for its research output and startup ecosystem.',
    ],
    // BITS Pilani
    [
        'd4e5f6a7-b8c9-0123-defa-234567890123', 'Birla Institute of Technology and Science Pilani', 'bits-pilani',
        'https://images.unsplash.com/photo-1562774053-701939374585?w=800&q=80',
        'https://images.unsplash.com/photo-1562774053-701939374585?w=400&q=80',
        'private', 'private_trust', 'active', 1, 1, 0,
        5, null, 28, 521, 1964,
        'A', 1, 1, 1, 4500, 350, 180.0,
        'BITS Pilani is one of the top private engineering institutions in India. Known for its unique Practice School program and strong industry connections. Offers B.E., M.E., MBA, and PhD programs.',
    ],
    // JNU
    [
        'e5f6a7b8-c9d0-1234-efab-345678901234', 'Jawaharlal Nehru University', 'jnu-delhi',
        'https://images.unsplash.com/photo-1562774053-701939374585?w=800&q=80',
        'https://images.unsplash.com/photo-1562774053-701939374585?w=400&q=80',
        'govt', 'central', 'active', 1, 1, 1,
        6, null, 9, 139, 1969,
        'A++', 1, 1, 0, 8000, 400, 45.0,
        'Jawaharlal Nehru University is a premier research university in New Delhi. Established in 1969, it is known for its strong emphasis on social sciences, humanities, and international studies.',
    ],
    // Anna University
    [
        'f6a7b8c9-d0e1-2345-fabc-456789012345', 'Anna University', 'anna-university',
        'https://images.unsplash.com/photo-1562774053-701939374585?w=800&q=80',
        'https://images.unsplash.com/photo-1562774053-701939374585?w=400&q=80',
        'govt', 'state', 'active', 1, 1, 1,
        7, null, 30, 544, 1978,
        'A', 1, 1, 1, 18000, 800, 250.0,
        'Anna University is a technical university in Chennai, Tamil Nadu. It offers engineering, technology, and management programs through its affiliated colleges across the state.',
    ],
    // Jadavpur University
    [
        'a7b8c9d0-e1f2-3456-abcd-567890123456', 'Jadavpur University', 'jadavpur-university',
        'https://images.unsplash.com/photo-1562774053-701939374585?w=800&q=80',
        'https://images.unsplash.com/photo-1562774053-701939374585?w=400&q=80',
        'govt', 'state', 'active', 0, 1, 1,
        8, null, 35, 713, 1955,
        'A', 1, 1, 0, 10000, 500, 80.0,
        'Jadavpur University is a premier public state university in Kolkata, West Bengal. Established in 1955, it offers programs in engineering, science, arts, and commerce.',
    ],
    // VIT Vellore
    [
        'b8c9d0e1-f2a3-4567-bcde-678901234567', 'Vellore Institute of Technology', 'vit-vellore',
        'https://images.unsplash.com/photo-1562774053-701939374585?w=800&q=80',
        'https://images.unsplash.com/photo-1562774053-701939374585?w=400&q=80',
        'private', 'private_trust', 'active', 1, 1, 0,
        9, null, 30, 544, 1984,
        'A', 1, 1, 1, 5000, 300, 120.0,
        'VIT Vellore is a private deemed university in Vellore, Tamil Nadu. Known for its VITEEE entrance exam and strong placement record. Offers B.Tech, M.Tech, MBA, and PhD programs.',
    ],
    // SRM University
    [
        'c9d0e1f2-a3b4-5678-cdef-789012345678', 'SRM Institute of Science and Technology', 'srm-chennai',
        'https://images.unsplash.com/photo-1562774053-701939374585?w=800&q=80',
        'https://images.unsplash.com/photo-1562774053-701939374585?w=400&q=80',
        'private', 'private_trust', 'active', 0, 1, 0,
        10, null, 30, 544, 1985,
        'A', 1, 1, 1, 6000, 350, 150.0,
        'SRM Institute of Science and Technology is a private deemed university in Chennai. Known for its SRMJEEE entrance exam and modern campus infrastructure.',
    ],
    // Manipal Academy
    [
        'd0e1f2a3-b4c5-6789-defa-890123456789', 'Manipal Academy of Higher Education', 'manipal',
        'https://images.unsplash.com/photo-1562774053-701939374585?w=800&q=80',
        'https://images.unsplash.com/photo-1562774053-701939374585?w=400&q=80',
        'private', 'private_trust', 'active', 1, 1, 0,
        11, null, 16, 267, 1953,
        'A+', 1, 1, 1, 8000, 400, 200.0,
        'Manipal Academy of Higher Education (MAHE) is a private deemed university in Manipal, Karnataka. Known for its medical, engineering, and management programs.',
    ],
    // NIT Trichy
    [
        'e1f2a3b4-c5d6-7890-efab-901234567890', 'National Institute of Technology Tiruchirappalli', 'nit-trichy',
        'https://images.unsplash.com/photo-1562774053-701939374585?w=800&q=80',
        'https://images.unsplash.com/photo-1562774053-701939374585?w=400&q=80',
        'govt', 'central', 'active', 1, 1, 1,
        12, null, 30, 544, 1964,
        'A', 1, 1, 1, 5000, 350, 180.0,
        'NIT Trichy is one of the top NITs in India. Established in 1964, it offers B.Tech, M.Tech, MCA, MBA, and PhD programs. Known for its strong placement record and research output.',
    ],
];

// 1. INSERT UNIVERSITIES
echo "=== Seeding Universities ===\n";
$uniSql = "INSERT IGNORE INTO universities (id,name,slug,cover_image_url,logo_url,university_type,ownership,status,is_featured,is_verified,ranking_nirf,ranking_qs,city_id,state_id,established_year,naac_grade,ugc_approved,aicte_approved,nba_approved,total_students,total_faculty,campus_area_acres) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)";
foreach ($universities as $u) {
    $params = array_slice($u, 0, 22); // take exactly 22, skip about_text at index 22+
    $s = $pdo->prepare($uniSql);
    try { $s->execute($params); $ok++; } catch(Exception $e) { $err++; echo "ERR uni [" . count($params) . "]: ".$e->getMessage()."\n"; }
}

// 1b. INSERT CONTENT (about_text)
echo "=== Seeding Content ===\n";
$s = $pdo->prepare("INSERT IGNORE INTO university_content (id,university_id,about_text) VALUES (?,?,?)");
foreach ($universities as $u) {
    $aboutText = end($u);
    $cid = 'uc-' . substr($u[0], 0, 8);
    try { $s->execute([$cid, $u[0], $aboutText]); $ok++; } catch(Exception $e) { $err++; echo "ERR content: ".$e->getMessage()."\n"; }
}

// 2. INSERT CONTACTS
echo "=== Seeding Contacts ===\n";
$contacts = [
    ['uc-01','02f1f361-2b42-446e-bc59-4d7a7ac3a0fb','https://www.iima.ac.in','admissions@iima.ac.in','079-63066000','Vastrapur, Ahmedabad, Gujarat 380015'],
    ['uc-02','a1b2c3d4-e5f6-7890-abcd-ef1234567890','https://www.iitb.ac.in','dean.acad@iitb.ac.in','022-25722545','Powai, Mumbai, Maharashtra 400076'],
    ['uc-03','b2c3d4e5-f6a7-8901-bcde-f12345678901','https://www.du.ac.in','info@du.ac.in','011-27667011','North Campus, Delhi 110007'],
    ['uc-04','c3d4e5f6-a7b8-9012-cdef-123456789012','https://www.iitm.ac.in','dean@iitm.ac.in','044-22578000','Adyar, Chennai, Tamil Nadu 600036'],
    ['uc-05','d4e5f6a7-b8c9-0123-defa-234567890123','https://www.bits-pilani.ac.in','admission@bits-pilani.ac.in','01596-245022','Pilani, Rajasthan 333031'],
    ['uc-06','e5f6a7b8-c9d0-1234-efab-345678901234','https://www.jnu.ac.in','info@jnu.ac.in','011-26704234','New Mehrauli Road, New Delhi 110067'],
    ['uc-07','f6a7b8c9-d0e1-2345-fabc-456789012345','https://www.annauniv.edu','dean@annauniv.edu','044-22358000','Sardar Patel Road, Chennai 600025'],
    ['uc-08','a7b8c9d0-e1f2-3456-abcd-567890123456','https://www.jadavpur.edu','info@jadavpur.edu','033-24146330','188 Raja S C Mallick Road, Kolkata 700032'],
    ['uc-09','b8c9d0e1-f2a3-4567-bcde-678901234567','https://www.vit.ac.in','admission@vit.ac.in','0416-2202000','Vellore, Tamil Nadu 632014'],
    ['uc-10','c9d0e1f2-a3b4-5678-cdef-789012345678','https://www.srmist.edu.in','admissions@srmist.edu.in','044-27417000','Kattankulathur, Chennai 603203'],
    ['uc-11','d0e1f2a3-b4c5-6789-defa-890123456789','https://manipal.edu','admissions@manipal.edu','0820-2571000','Manipal, Karnataka 576104'],
    ['uc-12','e1f2a3b4-c5d6-7890-efab-901234567890','https://www.nitt.edu','dean@nitt.edu','0431-2503000','Tiruchirappalli, Tamil Nadu 620015'],
];
$s = $pdo->prepare("INSERT IGNORE INTO university_contacts (id,university_id,website_url,email,phone,address) VALUES (?,?,?,?,?,?)");
foreach ($contacts as $c) { try { $s->execute($c); $ok++; } catch(Exception $e) { $err++; echo "ERR contact: ".$e->getMessage()."\n"; } }

// 3. INSERT COURSES
echo "=== Seeding Courses ===\n";
$courses = [
    // IIM Ahmedabad
    ['uc-01','02f1f361-2b42-446e-bc59-4d7a7ac3a0fb','Post Graduate Programme in Management','PG',2,'2500000','1250000','2500000',400,'CAT','Bachelor degree with 50% marks',2000,1],
    ['uc-02','02f1f361-2b42-446e-bc59-4d7a7ac3a0fb','Executive MBA (PGPX)','PG',1,'3500000','3500000','3500000',140,'GMAT/GRE','Bachelor degree with 50% marks + 5yr work exp',2000,1],
    // IIT Bombay
    ['uc-03','a1b2c3d4-e5f6-7890-abcd-ef1234567890','B.Tech Computer Science','UG',4,'200000','100000','800000',120,'JEE Advanced','10+2 with PCM, 75% marks',2000,1],
    ['uc-04','a1b2c3d4-e5f6-7890-abcd-ef1234567890','B.Tech Electrical Engineering','UG',4,'200000','100000','800000',80,'JEE Advanced','10+2 with PCM, 75% marks',2000,0],
    ['uc-05','a1b2c3d4-e5f6-7890-abcd-ef1234567890','M.Tech Data Science','PG',2,'200000','100000','400000',60,'GATE','B.Tech with valid GATE score',2000,1],
    // Delhi University
    ['uc-06','b2c3d4e5-f6a7-8901-bcde-f12345678901','B.A. Economics (Hons)','UG',3,'15000','15000','45000',1500,'CUET','10+2 with 60% marks',500,1],
    ['uc-07','b2c3d4e5-f6a7-8901-bcde-f12345678901','B.Com (Hons)','UG',3,'12000','12000','36000',1200,'CUET','10+2 with Commerce, 60% marks',500,1],
    ['uc-08','b2c3d4e5-f6a7-8901-bcde-f12345678901','M.Sc Physics','PG',2,'18000','18000','36000',200,'DU Entrance','B.Sc with Physics, 55% marks',500,0],
    // IIT Madras
    ['uc-09','c3d4e5f6-a7b8-9012-cdef-123456789012','B.Tech Mechanical Engineering','UG',4,'200000','100000','800000',100,'JEE Advanced','10+2 with PCM, 75% marks',2000,1],
    ['uc-10','c3d4e5f6-a7b8-9012-cdef-123456789012','B.Tech Electrical Engineering','UG',4,'200000','100000','800000',80,'JEE Advanced','10+2 with PCM, 75% marks',2000,1],
    // BITS Pilani
    ['uc-11','d4e5f6a7-b8c9-0123-defa-234567890123','B.E. Computer Science','UG',4,'220000','110000','880000',300,'BITSAT','10+2 with PCM, 75% marks',2000,1],
    ['uc-12','d4e5f6a7-b8c9-0123-defa-234567890123','MBA','PG',2,'300000','150000','600000',150,'CAT/GMAT','Bachelor degree with 60% marks',2000,1],
    // JNU
    ['uc-13','e5f6a7b8-c9d0-1234-efab-345678901234','M.A. International Relations','PG',2,'5000','5000','10000',80,'JNU Entrance','Bachelor degree with 50% marks',500,1],
    ['uc-14','e5f6a7b8-c9d0-1234-efab-345678901234','M.A. Economics','PG',2,'5000','5000','10000',60,'JNU Entrance','Bachelor degree with 50% marks',500,0],
    // NIT Trichy
    ['uc-15','e1f2a3b4-c5d6-7890-efab-901234567890','B.Tech Computer Science','UG',4,'150000','75000','600000',120,'JEE Main','10+2 with PCM, 75% marks',2000,1],
    ['uc-16','e1f2a3b4-c5d6-7890-efab-901234567890','M.Tech Structural Engineering','PG',2,'150000','75000','300000',40,'GATE','B.Tech with valid GATE score',2000,0],
];
$s = $pdo->prepare("INSERT IGNORE INTO university_courses (id,university_id,course_name,course_level,duration_years,total_fee,semester_fee,annual_fee,seats_available,specializations,eligibility_criteria,application_fee,emi_available) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)");
foreach ($courses as $c) {
    $params = [$c[0],$c[1],$c[2],$c[3],$c[4],$c[5],$c[6],$c[7],$c[8],'["'.addslashes($c[9]).'"]',$c[10],$c[11],$c[12]];
    try { $s->execute($params); $ok++; } catch(Exception $e) { $err++; echo "ERR course: ".$e->getMessage()."\n"; }
}

// 4. INSERT PLACEMENTS
echo "=== Seeding Placements ===\n";
$placements = [
    ['up-01','02f1f361-2b42-446e-bc59-4d7a7ac3a0fb',2024,35.00,120.00,30.00,100,400,'["McKinsey","BCG","Bain","Deloitte","Goldman Sachs","Google"]'],
    ['up-02','02f1f361-2b42-446e-bc59-4d7a7ac3a0fb',2023,32.00,110.00,28.00,100,395,'["McKinsey","BCG","Bain","Amazon","Microsoft"]'],
    ['up-03','a1b2c3d4-e5f6-7890-abcd-ef1234567890',2024,25.00,200.00,20.00,98,1200,'["Google","Microsoft","Amazon","Apple","Goldman Sachs"]'],
    ['up-04','a1b2c3d4-e5f6-7890-abcd-ef1234567890',2023,22.00,180.00,18.00,97,1180,'["Google","Microsoft","Amazon","Samsung"]'],
    ['up-05','c3d4e5f6-a7b8-9012-cdef-123456789012',2024,20.00,150.00,16.00,95,800,'["Google","Microsoft","Amazon","Flipkart","Qualcomm"]'],
    ['up-06','d4e5f6a7-b8c9-0123-defa-234567890123',2024,18.00,60.00,14.00,92,900,'["Microsoft","Amazon","Goldman Sachs","Deloitte"]'],
    ['up-07','e1f2a3b4-c5d6-7890-efab-901234567890',2024,12.00,50.00,9.00,90,600,'["TCS","Infosys","Wipro","Cognizant","Amazon"]'],
    ['up-08','f6a7b8c9-d0e1-2345-fabc-456789012345',2024,8.00,35.00,6.00,85,2000,'["TCS","Infosys","Wipro","HCL"]'],
];
$s = $pdo->prepare("INSERT IGNORE INTO university_placements (id,university_id,placement_year,avg_package_lpa,highest_package_lpa,median_package_lpa,placement_percentage,students_placed,top_recruiters) VALUES (?,?,?,?,?,?,?,?,?)");
foreach ($placements as $p) { try { $s->execute($p); $ok++; } catch(Exception $e) { $err++; echo "ERR placement: ".$e->getMessage()."\n"; } }

// 5. INSERT FAQs
echo "=== Seeding FAQs ===\n";
$faqs = [
    ['uf-01','02f1f361-2b42-446e-bc59-4d7a7ac3a0fb','What is the admission process for IIMA?','Admission to IIM Ahmedabad is through CAT exam followed by Written Ability Test (WAT) and Personal Interview (PI). Candidates must have a Bachelor degree with minimum 50% marks.','Admission',1,1],
    ['uf-02','02f1f361-2b42-446e-bc59-4d7a7ac3a0fb','What is the fee structure for PGP at IIMA?','The total fee for PGP at IIMA is approximately INR 25 Lakhs for 2 years including tuition, hostel, and other charges.','Fees',2,1],
    ['uf-03','a1b2c3d4-e5f6-7890-abcd-ef1234567890','How to get admission in IIT Bombay?','Admission to IIT Bombay B.Tech programs is through JEE Advanced exam. Candidates must first qualify JEE Main and then appear for JEE Advanced.','Admission',1,1],
    ['uf-04','a1b2c3d4-e5f6-7890-abcd-ef1234567890','What are the placement statistics of IIT Bombay?','IIT Bombay has a 98%+ placement rate. Average package is around INR 25 LPA with highest package reaching INR 2 Cr+ for international offers.','Placements',2,1],
    ['uf-05','b2c3d4e5-f6a7-8901-bcde-f12345678901','How to get admission in Delhi University?','Admission to Delhi University undergraduate programs is through CUET (Common University Entrance Test). Students must register on DU portal and appear for CUET.','Admission',1,1],
    ['uf-06','e1f2a3b4-c5d6-7890-efab-901234567890','What is the fee structure for NIT Trichy?','Annual fee at NIT Trichy is approximately INR 1.5 Lakhs for tuition plus hostel charges. Total 4-year B.Tech cost is around INR 6-8 Lakhs.','Fees',1,1],
];
$s = $pdo->prepare("INSERT IGNORE INTO university_faqs (id,university_id,question_text,answer_text,category,sort_order,is_active) VALUES (?,?,?,?,?,?,?)");
foreach ($faqs as $f) { try { $s->execute($f); $ok++; } catch(Exception $e) { $err++; echo "ERR faq: ".$e->getMessage()."\n"; } }

// 6. INSERT FACULTY
echo "=== Seeding Faculty ===\n";
$faculty = [
    ['ufa-01','02f1f361-2b42-446e-bc59-4d7a7ac3a0fb','Dr. Ashish Nanda','Director','Management','PhD Harvard Business School',25,30],
    ['ufa-02','a1b2c3d4-e5f6-7890-abcd-ef1234567890','Dr. Subhasis Chaudhuri','Director','Engineering','PhD IIT Bombay',20,50],
    ['ufa-03','c3d4e5f6-a7b8-9012-cdef-123456789012','Dr. V. Kamakoti','Director','Computer Science','PhD IIT Madras',18,40],
    ['ufa-04','d4e5f6a7-b8c9-0123-defa-234567890123','Dr. Ramgopal Rao','Director','Electronics','PhD IIT Bombay',22,35],
    ['ufa-05','e5f6a7b8-c9d0-1234-efab-345678901234','Dr. Santishree D. Pandit','Vice Chancellor','Social Sciences','PhD JNU',20,25],
];
$s = $pdo->prepare("INSERT IGNORE INTO university_faculty (id,university_id,faculty_name,designation,department,qualification,experience_years,research_papers) VALUES (?,?,?,?,?,?,?,?)");
foreach ($faculty as $f) { try { $s->execute($f); $ok++; } catch(Exception $e) { $err++; echo "ERR faculty: ".$e->getMessage()."\n"; } }

// 7. INSERT SCHOLARSHIPS
echo "=== Seeding Scholarships ===\n";
$scholarships = [
    ['usch-01','02f1f361-2b42-446e-bc59-4d7a7ac3a0fb','IIMA Merit-cum-Means Scholarship','merit',1000000,'full_tuition','Based on academic performance and financial need',1],
    ['usch-02','a1b2c3d4-e5f67890-abcd-ef1234567890','IIT Bombay Merit Scholarship','merit',500000,'fixed','Top 10% of JEE Advanced rankers',1],
    ['usch-03','d4e5f6a7-b8c9-0123-defa-234567890123','BITS Pilani Merit Scholarship','merit',100,'percentage','Based on BITSAT score',1],
    ['usch-04','e5f6a7b8-c9d0-1234-efab-345678901234','JNU Fee Waiver','need',100,'full_tuition','For students from economically weaker sections',0],
];
$s = $pdo->prepare("INSERT IGNORE INTO university_scholarships (id,university_id,scholarship_name,scholarship_type,amount,amount_type,eligibility_criteria,renewable) VALUES (?,?,?,?,?,?,?,?)");
foreach ($scholarships as $sch) { try { $s->execute($sch); $ok++; } catch(Exception $e) { $err++; echo "ERR scholarship: ".$e->getMessage()."\n"; } }

echo "\n=== Done: $ok inserted, $err errors ===\n";
