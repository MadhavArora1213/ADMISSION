<?php
require_once 'admin/db.php';

$ok = 0; $err = 0;

function run(PDO $pdo, string $sql, string $label, array $params = []) {
    global $ok, $err;
    try {
        if (empty($params)) { $pdo->exec($sql); }
        else { $s = $pdo->prepare($sql); $s->execute($params); }
        $ok++;
    } catch (Exception $e) {
        echo "ERR [$label]: " . $e->getMessage() . "\n";
        $err++;
    }
}

// ─── 1. COURSES ────────────────────────────────────────────────────────────
$courses = [
    ['crs-btech-01','B.Tech - Bachelor of Technology','btech','UG','Engineering','cat-eng-01',4,'B.Tech is a 4-year undergraduate professional degree in the field of engineering and technology. It is one of the most sought-after courses in India.','10+2 with Physics, Chemistry, and Mathematics with a minimum of 60% aggregate marks. Admission mostly via JEE Main, JEE Advanced, or state-level exams.','B.Tech graduates are hired as software engineers, mechanical engineers, data scientists, etc. High demand in IT, manufacturing, and core sectors.','["TCS", "Infosys", "Wipro", "Microsoft", "Google", "L&T", "Tata Motors"]',8.50,4.00,45.00,1,3500,'active'],
    ['crs-mba-02','MBA - Master of Business Administration','mba','PG','Management','cat-mgt-02',2,'MBA is a 2-year postgraduate program that covers various areas of business administration like finance, human resources, and marketing.','Bachelor’s degree with a minimum of 50% aggregate marks. Admission mostly via CAT, XAT, MAT, or GMAT.','MBA graduates take up managerial and leadership roles such as Marketing Manager, Financial Analyst, HR Manager.','["McKinsey", "BCG", "Amazon", "Deloitte", "HDFC Bank", "Reliance"]',12.00,6.00,60.00,1,2800,'active'],
    ['crs-mbbs-03','MBBS - Bachelor of Medicine and Bachelor of Surgery','mbbs','UG','Medical','cat-med-03',5,'MBBS is a 5.5-year degree (including 1 year internship) to become a certified doctor in India.','10+2 with Physics, Chemistry, and Biology with a minimum of 50% aggregate. Must qualify NEET UG.','MBBS graduates can practice as medical officers, physicians, or pursue higher studies (MD/MS).','["Apollo Hospitals", "Fortis", "AIIMS", "Max Healthcare"]',9.00,6.00,30.00,1,600,'active'],
];
$s = $pdo->prepare("INSERT IGNORE INTO courses (id,course_name,course_slug,course_level,course_category,category_id,duration_years,description,eligibility,career_scope,top_recruiters,avg_salary_lpa,salary_range_min,salary_range_max,is_popular,total_colleges_offering,status) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
foreach ($courses as $c) { try { $s->execute($c); $ok++; } catch(Exception $e){ $err++; echo "ERR courses: ".$e->getMessage()."\n"; } }

// ─── 2. COURSE SPECIALIZATIONS ────────────────────────────────────────────────────────────
$specs = [
    ['sp-cs-01','crs-btech-01','Computer Science Engineering','cse','Focuses on computer programming and networking.',1,1],
    ['sp-mech-02','crs-btech-01','Mechanical Engineering','mechanical','Involves design, manufacturing, and operation of machinery.',2,1],
    ['sp-ece-03','crs-btech-01','Electronics & Communication','ece','Deals with electronic devices, circuits, and communication systems.',3,1],
    ['sp-fin-04','crs-mba-02','Finance','finance','Focuses on financial management, investment, and banking.',1,1],
    ['sp-mkt-05','crs-mba-02','Marketing','marketing','Covers sales, advertising, brand management, and consumer behavior.',2,1],
    ['sp-hr-06','crs-mba-02','Human Resource','hr','Deals with recruitment, training, and employee relations.',3,1],
];
$s = $pdo->prepare("INSERT IGNORE INTO course_specializations (id,parent_course_id,specialization_name,specialization_slug,description,sort_order,is_popular) VALUES (?,?,?,?,?,?,?)");
foreach ($specs as $c) { try { $s->execute($c); $ok++; } catch(Exception $e){ $err++; echo "ERR specs: ".$e->getMessage()."\n"; } }

// ─── 3. CAREER PATHS ────────────────────────────────────────────────────────────
$careers = [
    ['cp-se-01','crs-btech-01','Software Engineer',8.50,'["Google", "Microsoft", "Amazon", "TCS"]','high','["Java", "Python", "DSA"]',5.50,15.00],
    ['cp-ds-02','crs-btech-01','Data Scientist',10.00,'["Fractal", "MuSigma", "Meta", "IBM"]','high','["Python", "Machine Learning", "SQL"]',6.50,22.00],
    ['cp-mm-03','crs-mba-02','Marketing Manager',12.00,'["HUL", "P&G", "Amazon", "ITC"]','high','["Digital Marketing", "Strategy", "Communication"]',8.00,25.00],
    ['cp-ib-04','crs-mba-02','Investment Banker',15.00,'["Goldman Sachs", "JP Morgan", "Morgan Stanley"]','high','["Financial Modeling", "Valuation", "Excel"]',10.00,40.00],
    ['cp-mo-05','crs-mbbs-03','Medical Officer',9.00,'["Govt Hospitals", "Apollo", "Fortis"]','medium','["Clinical Skills", "Diagnostics"]',6.00,18.00],
];
$s = $pdo->prepare("INSERT IGNORE INTO course_career_paths (id,course_id,job_role,avg_salary_lpa,top_companies,growth_outlook,skills_required,fresher_salary_lpa,experienced_salary_lpa) VALUES (?,?,?,?,?,?,?,?,?)");
foreach ($careers as $c) { try { $s->execute($c); $ok++; } catch(Exception $e){ $err++; echo "ERR careers: ".$e->getMessage()."\n"; } }

echo "\n✅ DONE: $ok statements OK, $err errors\n";
