<?php
require_once 'admin/db.php';

// Try to insert B.Tech directly without IGNORE to see the exact error
try {
    $s = $pdo->prepare("INSERT INTO courses (id,course_name,course_slug,course_level,course_category,category_id,duration_years,description,eligibility,career_scope,top_recruiters,avg_salary_lpa,salary_range_min,salary_range_max,is_popular,total_colleges_offering,status) VALUES (?,?,?,?,?,NULL,?,?,?,?,?,?,?,?,?,?,?)");
    $s->execute(['crs-btech-01','B.Tech - Bachelor of Technology','btech','UG','Engineering',4,'B.Tech is a 4-year undergraduate professional degree in the field of engineering and technology. It is one of the most sought-after courses in India.','10+2 with Physics, Chemistry, and Mathematics with a minimum of 60% aggregate marks. Admission mostly via JEE Main, JEE Advanced, or state-level exams.','B.Tech graduates are hired as software engineers, mechanical engineers, data scientists, etc. High demand in IT, manufacturing, and core sectors.','["TCS", "Infosys", "Wipro", "Microsoft", "Google", "L&T", "Tata Motors"]',8.50,4.00,45.00,1,3500,'active']);
    echo "B.Tech Inserted\n";
} catch (Exception $e) {
    echo "B.Tech Error: " . $e->getMessage() . "\n";
}

// Update existing mba and mbbs
$pdo->exec("UPDATE courses SET 
    course_name = 'MBA - Master of Business Administration',
    duration_years = 2,
    avg_salary_lpa = 12.00,
    total_colleges_offering = 2800,
    description = 'MBA is a 2-year postgraduate program that covers various areas of business administration like finance, human resources, and marketing.',
    eligibility = 'Bachelor degree with a minimum of 50% aggregate marks. Admission mostly via CAT, XAT, MAT, or GMAT.',
    career_scope = 'MBA graduates take up managerial and leadership roles such as Marketing Manager, Financial Analyst, HR Manager.',
    top_recruiters = '[\"McKinsey\", \"BCG\", \"Amazon\", \"Deloitte\", \"HDFC Bank\", \"Reliance\"]',
    status = 'active'
    WHERE course_slug = 'mba'");

$pdo->exec("UPDATE courses SET 
    course_name = 'MBBS - Bachelor of Medicine and Bachelor of Surgery',
    duration_years = 5,
    avg_salary_lpa = 9.00,
    total_colleges_offering = 600,
    description = 'MBBS is a 5.5-year degree (including 1 year internship) to become a certified doctor in India.',
    eligibility = '10+2 with Physics, Chemistry, and Biology with a minimum of 50% aggregate. Must qualify NEET UG.',
    career_scope = 'MBBS graduates can practice as medical officers, physicians, or pursue higher studies (MD/MS).',
    top_recruiters = '[\"Apollo Hospitals\", \"Fortis\", \"AIIMS\", \"Max Healthcare\"]',
    status = 'active'
    WHERE course_slug = 'mbbs'");

$pdo->exec("UPDATE courses SET 
    course_name = 'BCA - Bachelor of Computer Applications',
    course_level = 'UG',
    duration_years = 3,
    avg_salary_lpa = 4.50,
    total_colleges_offering = 1200,
    description = 'BCA is a 3-year undergraduate program focusing on computer languages, database management, and networking.',
    eligibility = '10+2 from a recognized board with minimum 50% marks.',
    career_scope = 'Software Developers, Web Designers, System Analysts.',
    top_recruiters = '[\"TCS\", \"Wipro\", \"Infosys\"]',
    status = 'active'
    WHERE course_slug = 'regtbeg'");

echo "Updates done.\n";
