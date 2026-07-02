<?php
require_once 'admin/db.php';

function q($pdo, $sql, $params = []) {
    try { $s = $pdo->prepare($sql); $s->execute($params); }
    catch(Exception $e) { echo "ERR: " . $e->getMessage() . "\n"; }
}

// Check which universities are missing contacts
$missing = $pdo->query("SELECT u.id, u.name, u.slug FROM universities u LEFT JOIN university_contacts uc ON uc.university_id = u.id WHERE u.status='active' AND uc.id IS NULL")->fetchAll(PDO::FETCH_ASSOC);
echo "Universities missing contacts: " . count($missing) . "\n";

// Add missing contacts
$contactData = [
    '02f1f361-2b42-446e-bc59-4d7a7ac3a0fb' => ['https://www.iima.ac.in','admissions@iima.ac.in','079-63066000','Vastrapur, Ahmedabad, Gujarat 380015'],
    'a1b2c3d4-e5f6-7890-abcd-ef1234567890' => ['https://www.iitb.ac.in','dean.acad@iitb.ac.in','022-25722545','Powai, Mumbai, Maharashtra 400076'],
    'c3d4e5f6-a7b8-9012-cdef-123456789012' => ['https://www.iitm.ac.in','dean@iitm.ac.in','044-22578000','Adyar, Chennai, Tamil Nadu 600036'],
    'f6a7b8c9-d0e1-2345-fabc-456789012345' => ['https://www.annauniv.edu','dean@annauniv.edu','0416-22358000','Sardar Patel Road, Chennai 600025'],
    'a7b8c9d0-e1f2-3456-abcd-567890123456' => ['https://www.jadavpur.edu','info@jadavpur.edu','033-24146330','188 Raja S C Mallick Road, Kolkata 700032'],
    'b8c9d0e1-f2a3-4567-bcde-678901234567' => ['https://www.vit.ac.in','admission@vit.ac.in','0416-2202000','Vellore, Tamil Nadu 632014'],
    'c9d0e1f2-a3b4-5678-cdef-789012345678' => ['https://www.srmist.edu.in','admissions@srmist.edu.in','044-27417000','Kattankulathur, Chennai 603203'],
    'd0e1f2a3-b4c5-6789-defa-890123456789' => ['https://manipal.edu','admissions@manipal.edu','0820-2571000','Manipal, Karnataka 576104'],
    'e1f2a3b4-c5d6-7890-efab-901234567890' => ['https://www.nitt.edu','dean@nitt.edu','0431-2503000','Tiruchirappalli, Tamil Nadu 620015'],
    'e5f6a7b8-c9d0-1234-efab-345678901234' => ['https://www.jnu.ac.in','info@jnu.ac.in','011-26704234','New Mehrauli Road, New Delhi 110067'],
];

foreach ($contactData as $uid => $d) {
    $exists = $pdo->prepare("SELECT 1 FROM university_contacts WHERE university_id = ?");
    $exists->execute([$uid]);
    if (!$exists->fetch()) {
        $cid = 'uc-' . substr($uid, 0, 8);
        q($pdo, "INSERT IGNORE INTO university_contacts (id,university_id,website_url,email,phone,address) VALUES (?,?,?,?,?,?)", array_merge([$cid, $uid], $d));
        echo "Added contact for $uid\n";
    }
}

// Add missing courses
$missingCourses = $pdo->query("SELECT u.id, u.name FROM universities u LEFT JOIN university_courses uc ON uc.university_id = u.id WHERE u.status='active' AND uc.id IS NULL")->fetchAll(PDO::FETCH_ASSOC);
echo "Universities missing courses: " . count($missingCourses) . "\n";

$courseSql = "INSERT IGNORE INTO university_courses (id,university_id,course_name,course_level,duration_years,total_fee,annual_fee,seats_available,specializations,eligibility_criteria,emi_available) VALUES (?,?,?,?,?,?,?,?,?,?,?)";

foreach ($missingCourses as $m) {
    $uid = $m['id'];
    $pref = 'uc-' . substr($uid, 0, 8);
    q($pdo, $courseSql, [$pref.'-c1',$uid,'B.Tech Computer Science','UG',4,400000,100000,200,'["CSE"]','10+2 with PCM',1]);
    q($pdo, $courseSql, [$pref.'-c2',$uid,'MBA','PG',2,300000,150000,120,'["MBA"]','Bachelor degree',1]);
    echo "Added courses for {$m['name']}\n";
}

// Add missing placements
$missingPlacements = $pdo->query("SELECT u.id, u.name FROM universities u LEFT JOIN university_placements up ON up.university_id = u.id WHERE u.status='active' AND up.id IS NULL")->fetchAll(PDO::FETCH_ASSOC);
echo "Universities missing placements: " . count($missingPlacements) . "\n";

$plSql = "INSERT IGNORE INTO university_placements (id,university_id,placement_year,avg_package_lpa,highest_package_lpa,median_package_lpa,placement_percentage,students_placed,top_recruiters) VALUES (?,?,?,?,?,?,?,?,?)";

foreach ($missingPlacements as $m) {
    $uid = $m['id'];
    $pref = 'up-' . substr($uid, 0, 8);
    q($pdo, $plSql, [$pref.'-p1',$uid,2024,6.00,45.00,4.00,80,2000,'["TCS","Infosys","Wipro","Cognizant"]']);
    echo "Added placements for {$m['name']}\n";
}

// Add missing content
$missingContent = $pdo->query("SELECT u.id, u.name FROM universities u LEFT JOIN university_content uc ON uc.university_id = u.id WHERE u.status='active' AND uc.id IS NULL")->fetchAll(PDO::FETCH_ASSOC);
echo "Universities missing content: " . count($missingContent) . "\n";

foreach ($missingContent as $m) {
    $uid = $m['id'];
    $cid = 'ucn-' . substr($uid, 0, 8);
    $about = $m['name'] . ' is a reputed institution of higher education in India, offering a wide range of undergraduate, postgraduate, and doctoral programs.';
    q($pdo, "INSERT IGNORE INTO university_content (id,university_id,about_text) VALUES (?,?,?)", [$cid, $uid, $about]);
    echo "Added content for {$m['name']}\n";
}

echo "\nDone!\n";
