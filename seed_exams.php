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

// ─── 1. EXAMS ────────────────────────────────────────────────────────────
$exams = [
    ['ex-jee-001','Joint Entrance Examination (Main)','jee-main','JEE Main','National Testing Agency (NTA)','https://upload.wikimedia.org/wikipedia/en/thumb/f/fa/National_Testing_Agency_logo.svg/220px-National_Testing_Agency_logo.svg.png','national','online','biannual',1200,1250000,1,'active',17,25,75.0,'12th Class','both',300,90,180,'{"+4":"Correct","-1":"Incorrect"}','["Physics", "Chemistry", "Mathematics"]','["English", "Hindi", "Regional"]','1000.00','800.00','500.00','500.00','800.00','https://jeemain.nta.nic.in','https://jeemain.nta.nic.in',null,null,null,'JoSAA',6,null,'NTA Score'],
    ['ex-cat-002','Common Admission Test','cat','CAT','IIMs (Rotational)','https://upload.wikimedia.org/wikipedia/en/f/fb/IIM_Ahmedabad_Logo.svg','national','online','annual',200,330000,1,'active',20,99,50.0,'Bachelor Degree','indian',198,66,120,'{"+3":"Correct","-1":"Incorrect"}','["VARC", "DILR", "QA"]','["English"]','2400.00','2400.00','1200.00','1200.00','2400.00','https://iimcat.ac.in','https://iimcat.ac.in',null,null,null,'IIMs directly',0,null,'Percentile Score'],
    ['ex-neet-003','National Eligibility cum Entrance Test (UG)','neet','NEET','National Testing Agency (NTA)','https://upload.wikimedia.org/wikipedia/en/thumb/f/fa/National_Testing_Agency_logo.svg/220px-National_Testing_Agency_logo.svg.png','national','offline','annual',600,2400000,1,'active',17,99,50.0,'12th Class PCB','both',720,200,200,'{"+4":"Correct","-1":"Incorrect"}','["Physics", "Chemistry", "Botany", "Zoology"]','["English", "Hindi", "Regional"]','1700.00','1600.00','1000.00','1000.00','1700.00','https://neet.nta.nic.in','https://neet.nta.nic.in',null,null,null,'MCC',4,null,'Percentile'],
];
$s = $pdo->prepare("INSERT IGNORE INTO exams (id,exam_name,exam_slug,exam_abbreviation,conducting_body,conducting_body_logo,exam_level,exam_mode,exam_frequency,participating_colleges_count,applicants_last_year,is_national,status,age_min,age_max,min_percentage_required,qualifying_exam,nationality,total_marks,total_questions,duration_minutes,marking_scheme,sections,language_options,application_fee_general,application_fee_obc,application_fee_sc_st,application_fee_pwd,application_fee_female,application_url,official_website,syllabus_pdf_url,result_url,scorecard_url,counselling_authority,counselling_rounds,merit_list_url,normalisation_method) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
foreach ($exams as $c) { try { $s->execute($c); $ok++; } catch(Exception $e){ $err++; echo "ERR exams: ".$e->getMessage()."\n"; } }

// ─── 2. EXAM DATES ────────────────────────────────────────────────────────────
$dates = [
    ['d-jee-1','ex-jee-001',2026,'Session 1 Applications Start','2025-11-01','2025-11-01','2025-11-30','2026-01-24','2026-02-12',null,null,null,0],
    ['d-jee-2','ex-jee-001',2026,'Session 1 Exam Window','2026-01-24',null,null,'2026-01-24',null,null,null,null,0],
    ['d-jee-3','ex-jee-001',2026,'Session 1 Results','2026-02-12',null,null,null,'2026-02-12',null,null,null,0],
    ['d-cat-1','ex-cat-002',2026,'CAT 2026 Applications Open','2026-08-05','2026-08-05','2026-09-15',null,null,null,null,null,1],
    ['d-cat-2','ex-cat-002',2026,'CAT 2026 Exam Date','2026-11-29',null,null,'2026-11-29',null,null,null,null,1],
];
$s = $pdo->prepare("INSERT IGNORE INTO exam_dates (id,exam_id,year,event_name,event_date,application_start,application_end,exam_date,result_date,admit_card_date,counselling_start,answer_key_date,is_tentative) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)");
foreach ($dates as $c) { try { $s->execute($c); $ok++; } catch(Exception $e){ $err++; echo "ERR dates: ".$e->getMessage()."\n"; } }

// ─── 3. SYLLABUS ────────────────────────────────────────────────────────────
$syllabus = [
    ['s-jee-1','ex-jee-001','Physics','Mechanics','["Kinematics", "Laws of Motion", "Work Energy Power"]',30.5,null],
    ['s-jee-2','ex-jee-001','Physics','Electromagnetism','["Electrostatics", "Current Electricity", "Magnetism"]',25.0,null],
    ['s-jee-3','ex-jee-001','Mathematics','Calculus','["Limits", "Derivatives", "Integrals"]',35.0,null],
    ['s-cat-1','ex-cat-002','Quantitative Ability','Arithmetic','["Percentages", "Profit Loss", "Time Speed Distance"]',45.0,null],
    ['s-cat-2','ex-cat-002','Quantitative Ability','Algebra','["Linear Equations", "Quadratic Equations", "Logarithms"]',30.0,null],
    ['s-cat-3','ex-cat-002','VARC','Reading Comprehension','["Passage based questions"]',70.0,null],
];
$s = $pdo->prepare("INSERT IGNORE INTO exam_syllabus (id,exam_id,subject,topic,subtopics,weightage_pct,chapter_pdf_url) VALUES (?,?,?,?,?,?,?)");
foreach ($syllabus as $c) { try { $s->execute($c); $ok++; } catch(Exception $e){ $err++; echo "ERR syllabus: ".$e->getMessage()."\n"; } }

// ─── 4. CUTOFFS ────────────────────────────────────────────────────────────
// Ensure we use the college IDs from previous seed: col-iitb-0001, col-iima-0002
$cutoffs = [
    ['ec-jee-1','ex-jee-001','col-iitb-0001',null,2024,'General',63,5], // We omit course_id for brevity
    ['ec-cat-1','ex-cat-002','col-iima-0002',null,2024,'General',99,1],
];
$s = $pdo->prepare("INSERT IGNORE INTO exam_cutoffs (id,exam_id,college_id,course_id,year,category,opening_rank,closing_rank) VALUES (?,?,?,?,?,?,?,?)");
foreach ($cutoffs as $c) { try { $s->execute($c); $ok++; } catch(Exception $e){ $err++; echo "ERR cutoffs: ".$e->getMessage()."\n"; } }

echo "\n✅ DONE: $ok statements OK, $err errors\n";
