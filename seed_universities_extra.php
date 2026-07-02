<?php
require_once 'admin/db.php';

function q($pdo, $sql, $params = []) {
    try { $s = $pdo->prepare($sql); $s->execute($params); echo "OK\n"; }
    catch(Exception $e) { echo "ERR: " . $e->getMessage() . "\n"; }
}

// Contacts
q($pdo, "INSERT IGNORE INTO university_contacts (id,university_id,website_url,email,phone,address) VALUES (?,?,?,?,?,?)", ['uc-n01','u1a00001-0001-0001-0001-000000000001','https://www.bhu.ac.in','info@bhu.ac.in','0542-2307254','Varanasi, UP 221005']);
q($pdo, "INSERT IGNORE INTO university_contacts (id,university_id,website_url,email,phone,address) VALUES (?,?,?,?,?,?)", ['uc-n02','u1a00001-0001-0001-0001-000000000002','https://www.amu.ac.in','info@amu.ac.in','0571-2700920','Aligarh, UP 202001']);
q($pdo, "INSERT IGNORE INTO university_contacts (id,university_id,website_url,email,phone,address) VALUES (?,?,?,?,?,?)", ['uc-n03','u1a00001-0001-0001-0001-000000000003','https://www.unipune.ac.in','info@unipune.ac.in','020-25601000','Pune, Maharashtra 411007']);
q($pdo, "INSERT IGNORE INTO university_contacts (id,university_id,website_url,email,phone,address) VALUES (?,?,?,?,?,?)", ['uc-n04','u1a00001-0001-0001-0001-000000000004','https://mu.ac.in','info@mu.ac.in','022-26521793','Fort, Mumbai 400032']);
q($pdo, "INSERT IGNORE INTO university_contacts (id,university_id,website_url,email,phone,address) VALUES (?,?,?,?,?,?)", ['uc-n05','u1a00001-0001-0001-0001-000000000005','https://www.cu.ac.in','info@cu.ac.in','033-22410071','87/1 College Street, Kolkata 700073']);
q($pdo, "INSERT IGNORE INTO university_contacts (id,university_id,website_url,email,phone,address) VALUES (?,?,?,?,?,?)", ['uc-n06','u1a00001-0001-0001-0001-000000000006','https://www.amity.edu','admissions@amity.edu','0120-4392500','Sector 125, Noida 201301']);
q($pdo, "INSERT IGNORE INTO university_contacts (id,university_id,website_url,email,phone,address) VALUES (?,?,?,?,?,?)", ['uc-n07','u1a00001-0001-0001-0001-000000000007','https://www.lpu.in','admissions@lpu.co.in','01824-517000','Phagwara, Punjab 144411']);
q($pdo, "INSERT IGNORE INTO university_contacts (id,university_id,website_url,email,phone,address) VALUES (?,?,?,?,?,?)", ['uc-n08','u1a00001-0001-0001-0001-000000000009','https://paruluniversity.ac.in','info@paruluniversity.ac.in','0265-2652100','Vadodara, Gujarat 391760']);
q($pdo, "INSERT IGNORE INTO university_contacts (id,university_id,website_url,email,phone,address) VALUES (?,?,?,?,?,?)", ['uc-n09','u1a00001-0001-0001-0001-000000000010','https://www.cuchd.in','admissions@cuchd.in','0172-3931000','Chandigarh-Mohali Highway, Punjab 140413']);

// Courses
$courseSql = "INSERT IGNORE INTO university_courses (id,university_id,course_name,course_level,duration_years,total_fee,annual_fee,seats_available,specializations,eligibility_criteria,emi_available) VALUES (?,?,?,?,?,?,?,?,?,?,?)";
q($pdo, $courseSql, ['ucrs-n01','u1a00001-0001-0001-0001-000000000001','B.A. LLB','UG',5,50000,10000,120,'["Law"]','10+2 with 50% marks',1]);
q($pdo, $courseSql, ['ucrs-n02','u1a00001-0001-0001-0001-000000000001','B.Tech Computer Science','UG',4,80000,20000,180,'["CSE"]','10+2 with PCM, 60% marks',1]);
q($pdo, $courseSql, ['ucrs-n03','u1a00001-0001-0001-0001-000000000002','B.Tech Computer Science','UG',4,120000,30000,150,'["CSE"]','10+2 with PCM, 60% marks via AMUEEE',1]);
q($pdo, $courseSql, ['ucrs-n04','u1a00001-0001-0001-0001-000000000002','MBA','PG',2,200000,100000,120,'["MBA"]','Bachelor degree with 50% marks',1]);
q($pdo, $courseSql, ['ucrs-n05','u1a00001-0001-0001-0001-000000000003','B.Sc Computer Science','UG',3,30000,10000,500,'["CS"]','10+2 with Science, 55% marks',1]);
q($pdo, $courseSql, ['ucrs-n06','u1a00001-0001-0001-0001-000000000003','MBA','PG',2,200000,100000,300,'["MBA"]','CAT/MAT score',1]);
q($pdo, $courseSql, ['ucrs-n07','u1a00001-0001-0001-0001-000000000006','B.Tech CSE','UG',4,800000,200000,1500,'["CSE"]','10+2 with PCM, 60% marks',1]);
q($pdo, $courseSql, ['ucrs-n08','u1a00001-0001-0001-0001-000000000006','MBA','PG',2,600000,300000,800,'["MBA"]','CAT/MAT/XAT score',1]);
q($pdo, $courseSql, ['ucrs-n09','u1a00001-0001-0001-0001-000000000007','B.Tech CSE','UG',4,600000,150000,2000,'["CSE"]','10+2 with PCM, LPUNEST',1]);
q($pdo, $courseSql, ['ucrs-n10','u1a00001-0001-0001-0001-000000000007','MBA','PG',2,400000,200000,1000,'["MBA"]','LPUNEST/CAT score',1]);
q($pdo, $courseSql, ['ucrs-n11','u1a00001-0001-0001-0001-000000000010','B.Tech CSE','UG',4,800000,200000,1800,'["CSE"]','10+2 with PCM, CUCET',1]);
q($pdo, $courseSql, ['ucrs-n12','u1a00001-0001-0001-0001-000000000010','MBA','PG',2,500000,250000,600,'["MBA"]','CUCET/CAT score',1]);

// Placements
$plSql = "INSERT IGNORE INTO university_placements (id,university_id,placement_year,avg_package_lpa,highest_package_lpa,median_package_lpa,placement_percentage,students_placed,top_recruiters) VALUES (?,?,?,?,?,?,?,?,?)";
q($pdo, $plSql, ['up-n01','u1a00001-0001-0001-0001-000000000001',2024,6.00,45.00,4.00,75,2000,'["TCS","Infosys","Wipro","HCL"]']);
q($pdo, $plSql, ['up-n02','u1a00001-0001-0001-0001-000000000002',2024,5.50,40.00,3.50,70,1500,'["TCS","Infosys","Wipro"]']);
q($pdo, $plSql, ['up-n03','u1a00001-0001-0001-0001-000000000003',2024,5.00,30.00,3.50,72,5000,'["TCS","Infosys","Wipro","Cognizant"]']);
q($pdo, $plSql, ['up-n04','u1a00001-0001-0001-0001-000000000006',2024,6.50,55.00,5.00,85,10000,'["TCS","Infosys","Wipro","Amazon","Microsoft"]']);
q($pdo, $plSql, ['up-n05','u1a00001-0001-0001-0001-000000000007',2024,5.00,42.00,3.80,90,20000,'["TCS","Infosys","Wipro","Cognizant","HCL"]']);
q($pdo, $plSql, ['up-n06','u1a00001-0001-0001-0001-000000000010',2024,6.00,50.00,4.50,88,15000,'["TCS","Infosys","Wipro","Amazon","Deloitte"]']);

// FAQs
$faqSql = "INSERT IGNORE INTO university_faqs (id,university_id,question_text,answer_text,category,sort_order,is_active) VALUES (?,?,?,?,?,?,?)";
q($pdo, $faqSql, ['uf-n01','u1a00001-0001-0001-0001-000000000006','What is the admission process at Amity?','Admission through Amity Entrance Test or valid CAT/MAT/XAT for MBA.','Admission',1,1]);
q($pdo, $faqSql, ['uf-n02','u1a00001-0001-0001-0001-000000000007','Does LPU offer scholarships?','Yes, based on LPUNEST scores, sports achievements, and financial need.','Fees',1,1]);
q($pdo, $faqSql, ['uf-n03','u1a00001-0001-0001-0001-000000000001','What courses does BHU offer?','Wide range of UG, PG, and PhD programs across arts, science, engineering, medicine, law.','General',1,1]);

// Faculty
$facSql = "INSERT IGNORE INTO university_faculty (id,university_id,faculty_name,designation,department,qualification,experience_years,research_papers) VALUES (?,?,?,?,?,?,?,?)";
q($pdo, $facSql, ['ufa-n01','u1a00001-0001-0001-0001-000000000001','Prof. Sudhir Kumar Jain','Vice Chancellor','Administration','PhD IIT Kanpur',30,80]);
q($pdo, $facSql, ['ufa-n02','u1a00001-0001-0001-0001-000000000002','Prof. Naima Khatoon','Vice Chancellor','Administration','PhD AMU',25,40]);
q($pdo, $facSql, ['ufa-n03','u1a00001-0001-0001-0001-000000000006','Dr. Atul Chauhan','President','Administration','PhD IIT Delhi',20,30]);

// Content
$cntSql = "INSERT IGNORE INTO university_content (id,university_id,about_text) VALUES (?,?,?)";
q($pdo, $cntSql, ['ucn-n01','u1a00001-0001-0001-0001-000000000001','Banaras Hindu University (BHU) is a public central university in Varanasi, Uttar Pradesh. Founded in 1916, it is one of the largest residential universities in Asia with over 30,000 students.']);
q($pdo, $cntSql, ['ucn-n02','u1a00001-0001-0001-0001-000000000002','Aligarh Muslim University (AMU) is a public central university in Aligarh, Uttar Pradesh. Founded in 1875, it offers programs in engineering, management, medicine, law, and humanities.']);
q($pdo, $cntSql, ['ucn-n03','u1a00001-0001-0001-0001-000000000003','Savitribai Phule Pune University (SPPU) is a public state university in Pune, Maharashtra. Established in 1949, it has over 500 affiliated colleges.']);
q($pdo, $cntSql, ['ucn-n04','u1a00001-0001-0001-0001-000000000006','Amity University Noida is a private university in Noida, Uttar Pradesh. Established in 2005, it offers programs across 100+ disciplines.']);
q($pdo, $cntSql, ['ucn-n05','u1a00001-0001-0001-0001-000000000007','Lovely Professional University (LPU) is a private university in Phagwara, Punjab. One of the largest universities in India with 300,000+ students.']);
q($pdo, $cntSql, ['ucn-n06','u1a00001-0001-0001-0001-000000000010','Chandigarh University is a private university in Mohali, Punjab. Established in 2012, it has strong placement records with top recruiters.']);
