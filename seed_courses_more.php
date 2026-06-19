<?php
require_once 'admin/db.php';

$ok = 0; $err = 0;

// ─── 1. COURSES ────────────────────────────────────────────────────────────
$courses = [
    ['crs-bca-04','BCA - Bachelor of Computer Applications','bca','UG','IT & Software','cat-it-01',3,'BCA is a 3-year undergraduate program focusing on computer languages, database management, and networking. It serves as an alternative to B.Tech for software engineering.','10+2 from a recognized board with minimum 50% marks. Mathematics as a subject in 12th is preferred by some universities.','BCA graduates can work as Software Developers, Web Designers, System Analysts, and can pursue MCA for better prospects.','["TCS", "Wipro", "Infosys", "Tech Mahindra", "Capgemini", "Cognizant"]',4.50,2.50,15.00,1,1200,'active'],
    ['crs-llb-05','LLB - Bachelor of Legislative Law','llb','UG','Law','cat-law-01',3,'LLB is a 3-year undergraduate degree in law. It provides a foundation in various legal domains including criminal, civil, corporate, and family law.','Graduation in any discipline with a minimum of 45-50% aggregate. Admission usually through entrance exams like DU LLB, MH CET Law.','Lawyers, Legal Advisors for corporations, Judiciary, and teaching.','["Cyril Amarchand Mangaldas", "Khaitan & Co", "Shardul Amarchand Mangaldas", "Trilegal"]',6.00,3.50,25.00,1,850,'active'],
];
$s = $pdo->prepare("INSERT IGNORE INTO courses (id,course_name,course_slug,course_level,course_category,category_id,duration_years,description,eligibility,career_scope,top_recruiters,avg_salary_lpa,salary_range_min,salary_range_max,is_popular,total_colleges_offering,status) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
foreach ($courses as $c) { try { $s->execute($c); $ok++; } catch(Exception $e){ $err++; echo "ERR courses: ".$e->getMessage()."\n"; } }

// ─── 2. COURSE SPECIALIZATIONS ────────────────────────────────────────────────────────────
$specs = [
    ['sp-bca-01','crs-bca-04','Cyber Security','cyber-security','Focuses on protecting computer systems, networks, and data from cyber attacks.',1,1],
    ['sp-bca-02','crs-bca-04','Data Analytics','data-analytics','Involves analyzing raw data to find trends and answer questions.',2,1],
    ['sp-bca-03','crs-bca-04','Cloud Computing','cloud-computing','Study of delivering computing services over the internet.',3,1],
    ['sp-llb-01','crs-llb-05','Corporate Law','corporate-law','Deals with laws governing the rights and conduct of businesses.',1,1],
    ['sp-llb-02','crs-llb-05','Criminal Law','criminal-law','Involves prosecution and defense of individuals accused of crimes.',2,1],
];
$s = $pdo->prepare("INSERT IGNORE INTO course_specializations (id,parent_course_id,specialization_name,specialization_slug,description,sort_order,is_popular) VALUES (?,?,?,?,?,?,?)");
foreach ($specs as $c) { try { $s->execute($c); $ok++; } catch(Exception $e){ $err++; echo "ERR specs: ".$e->getMessage()."\n"; } }

// ─── 3. CAREER PATHS ────────────────────────────────────────────────────────────
$careers = [
    ['cp-bca-01','crs-bca-04','Web Developer',4.00,'["Amazon", "Flipkart", "TCS", "Wipro"]','high','["HTML/CSS", "JavaScript", "PHP", "React"]',3.00,10.00],
    ['cp-bca-02','crs-bca-04','System Analyst',5.50,'["Infosys", "IBM", "Accenture"]','medium','["System Architecture", "Networking", "Problem Solving"]',3.50,12.00],
    ['cp-llb-01','crs-llb-05','Corporate Lawyer',8.00,'["Khaitan & Co", "Trilegal", "AZB & Partners"]','high','["Legal Drafting", "Negotiation", "Corporate Governance"]',5.00,20.00],
    ['cp-llb-02','crs-llb-05','Legal Advisor',6.50,'["HDFC Bank", "ICICI Bank", "Reliance"]','medium','["Contract Law", "Compliance", "Advisory"]',4.50,15.00],
];
$s = $pdo->prepare("INSERT IGNORE INTO course_career_paths (id,course_id,job_role,avg_salary_lpa,top_companies,growth_outlook,skills_required,fresher_salary_lpa,experienced_salary_lpa) VALUES (?,?,?,?,?,?,?,?,?)");
foreach ($careers as $c) { try { $s->execute($c); $ok++; } catch(Exception $e){ $err++; echo "ERR careers: ".$e->getMessage()."\n"; } }

echo "\n✅ DONE: $ok statements OK, $err errors\n";
