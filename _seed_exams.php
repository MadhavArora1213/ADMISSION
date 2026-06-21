<?php
$pdo = new PDO('mysql:host=localhost;dbname=admission;charset=utf8mb4', 'root', '');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdo->beginTransaction();

function uid() {
    return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x', mt_rand(0,0xffff), mt_rand(0,0xffff), mt_rand(0,0xffff), mt_rand(0,0x0fff)|0x4000, mt_rand(0,0x3fff)|0x8000, mt_rand(0,0xffff), mt_rand(0,0xffff), mt_rand(0,0xffff));
}

echo "=== Step 1: Deleting all exam data ===" . PHP_EOL;
$pdo->exec("DELETE FROM exam_cutoffs");
$pdo->exec("DELETE FROM exam_syllabus");
$pdo->exec("DELETE FROM exam_dates");
$pdo->exec("DELETE FROM exams");
echo "All exam data deleted." . PHP_EOL;

echo PHP_EOL . "=== Step 2: Inserting exams ===" . PHP_EOL;

$exams = [
  [
    'id'=>'ex-jee-main-2026','name'=>'JEE Main 2026','slug'=>'jee-main-2026','abbr'=>'JEE Main',
    'body'=>'National Testing Agency (NTA)','logo'=>'https://upload.wikimedia.org/wikipedia/en/thumb/8/8d/NTA_logo.svg/200px-NTA_logo.svg.png',
    'level'=>'national','mode'=>'online','freq'=>'annual','colleges'=>3200,'applicants'=>1250000,'national'=>1,
    'status'=>'active','age_min'=>17,'age_max'=>25,'min_pct'=>75,'qualify'=>'10+2 with PCM','nationality'=>'both',
    'marks'=>300,'questions'=>75,'duration'=>180,
    'subjects'=>json_encode(['Physics','Chemistry','Mathematics']),
    'marking'=>json_encode(['+4'=>'Correct answer','-1'=>'Incorrect answer','0'=>'Unattempted']),
    'sections'=>json_encode(['Physics','Chemistry','Mathematics']),
    'languages'=>json_encode(['English','Hindi','Gujarati','Tamil','Telugu','Marathi','Bengali','Assamese','Odia','Punjabi','Kannada','Malayalam','Urdu']),
    'fee_g'=>1000,'fee_obc'=>500,'fee_sc'=>500,'fee_pwd'=>0,'fee_f'=>500,
    'app_url'=>'https://jeemain.nta.ac.in','website'=>'https://jeemain.nta.ac.in',
    'syllabus_pdf'=>'','result_url'=>'https://jeemain.nta.ac.in','scorecard'=>'https://jeemain.nta.ac.in',
    'counselling'=>'JoSAA / CSAB','c_rounds'=>5,'merit'=>'https://jeemain.nta.ac.in',
    'normalisation'=>'NTA uses percentile-based normalisation across multiple sessions to ensure fair comparison. The NTA score is calculated using the formula: 100 × (Number of candidates with raw score equal to or less than the candidate) / (Total number of candidates in the session).'
  ],
  [
    'id'=>'ex-jee-adv-2026','name'=>'JEE Advanced 2026','slug'=>'jee-advanced-2026','abbr'=>'JEE Advanced',
    'body'=>'Indian Institute of Technology (IIT)','logo'=>'https://upload.wikimedia.org/wikipedia/en/thumb/b/b2/JEE_Advanced_Logo.svg/200px-JEE_Advanced_Logo.svg.png',
    'level'=>'national','mode'=>'online','freq'=>'annual','colleges'=>23,'applicants'=>250000,'national'=>1,
    'status'=>'active','age_min'=>0,'age_max'=>25,'min_pct'=>75,'qualify'=>'JEE Main qualified','nationality'=>'both',
    'marks'=>396,'questions'=>54,'duration'=>180,
    'subjects'=>json_encode(['Physics','Chemistry','Mathematics']),
    'marking'=>json_encode(['+3'=>'MCQ Correct','-1'=>'MCQ Incorrect','+4'=>'Numerical Correct','0'=>'Unattempted']),
    'sections'=>json_encode(['Paper 1: Physics, Chemistry, Mathematics','Paper 2: Physics, Chemistry, Mathematics']),
    'languages'=>json_encode(['English','Hindi']),
    'fee_g'=>3200,'fee_obc'=>1600,'fee_sc'=>1600,'fee_pwd'=>0,'fee_f'=>1600,
    'app_url'=>'https://jeeadv.ac.in','website'=>'https://jeeadv.ac.in',
    'syllabus_pdf'=>'','result_url'=>'https://jeeadv.ac.in','scorecard'=>'https://jeeadv.ac.in',
    'counselling'=>'JoSAA','c_rounds'=>5,'merit'=>'https://jeeadv.ac.in',
    'normalisation'=>'Raw scores are used for ranking. Two papers (Paper 1 and Paper 2) are conducted. Both papers are compulsory. Final rank is based on combined marks of both papers.'
  ],
  [
    'id'=>'ex-neet-ug-2026','name'=>'NEET UG 2026','slug'=>'neet-ug-2026','abbr'=>'NEET',
    'body'=>'National Testing Agency (NTA)','logo'=>'https://upload.wikimedia.org/wikipedia/en/thumb/a/a2/NEET_logo.svg/200px-NEET_logo.svg.png',
    'level'=>'national','mode'=>'offline','freq'=>'annual','colleges'=>1800,'applicants'=>2400000,'national'=>1,
    'status'=>'active','age_min'=>17,'age_max'=>25,'min_pct'=>50,'qualify'=>'10+2 with PCB','nationality'=>'both',
    'marks'=>720,'questions'=>200,'duration'=>200,
    'subjects'=>json_encode(['Physics','Chemistry','Botany','Zoology']),
    'marking'=>json_encode(['+4'=>'Correct answer','-1'=>'Incorrect answer','0'=>'Unattempted/Skipped']),
    'sections'=>json_encode(['Section A: Physics (35 questions)','Section B: Physics (15 questions, attempt 10)','Section A: Chemistry (35 questions)','Section B: Chemistry (15 questions, attempt 10)','Section A: Botany (35 questions)','Section B: Botany (15 questions, attempt 10)','Section A: Zoology (35 questions)','Section B: Zoology (15 questions, attempt 10)']),
    'languages'=>json_encode(['English','Hindi','Assamese','Bengali','Gujarati','Kannada','Marathi','Odia','Punjabi','Tamil','Telugu','Urdu','Bodo','Dogri','Khasi','Konkani','Manipuri','Maithili','Santali']),
    'fee_g'=>1700,'fee_obc'=>1600,'fee_sc'=>900,'fee_pwd'=>0,'fee_f'=>900,
    'app_url'=>'https://neet.nta.nic.in','website'=>'https://neet.nta.nic.in',
    'syllabus_pdf'=>'','result_url'=>'https://neet.nta.nic.in','scorecard'=>'https://neet.nta.nic.in',
    'counselling'=>'MCC DG (15% AIQ) + State Counselling (85%)','c_rounds'=>4,'merit'=>'https://neet.nta.nic.in',
    'normalisation'=>'Raw marks are used for ranking. No normalisation is applied in NEET UG. All India Rank is based on total marks scored out of 720.'
  ],
  [
    'id'=>'ex-cuet-ug-2026','name'=>'CUET UG 2026','slug'=>'cuet-ug-2026','abbr'=>'CUET',
    'body'=>'National Testing Agency (NTA)','logo'=>'https://upload.wikimedia.org/wikipedia/en/thumb/4/41/CUET_logo.svg/200px-CUET_logo.svg.png',
    'level'=>'national','mode'=>'online','freq'=>'annual','colleges'=>250,'applicants'=>1500000,'national'=>1,
    'status'=>'active','age_min'=>0,'age_max'=>0,'min_pct'=>50,'qualify'=>'10+2 or equivalent','nationality'=>'both',
    'marks'=>800,'questions'=>150,'duration'=>195,
    'subjects'=>json_encode(['Language Test','Domain Subject','General Test']),
    'marking'=>json_encode(['+5'=>'Correct MCQ','-1'=>'Incorrect MCQ','0'=>'Unattempted']),
    'sections'=>json_encode(['Section IA: Language (13 languages)','Section IB: Language (20 languages)','Section II: Domain Subjects (29 subjects)','Section III: General Test']),
    'languages'=>json_encode(['English','Hindi','Assamese','Bengali','Gujarati','Kannada','Malayalam','Marathi','Odia','Punjabi','Tamil','Telugu','Urdu','French','German','Italian','Japanese','Korean','Chinese','Spanish','Russian','Arabic']),
    'fee_g'=>750,'fee_obc'=>750,'fee_sc'=>375,'fee_pwd'=>0,'fee_f'=>375,
    'app_url'=>'https://cuet.nta.nic.in','website'=>'https://cuet.nta.nic.in',
    'syllabus_pdf'=>'','result_url'=>'https://cuet.nta.nic.in','scorecard'=>'https://cuet.nta.nic.in',
    'counselling'=>'Participating Universities conduct their own counselling','c_rounds'=>3,'merit'=>'https://cuet.nta.nic.in',
    'normalisation'=>'NTA uses percentile-based normalisation across multiple shifts. Score of each candidate is equated to a common scale to ensure fairness.'
  ],
  [
    'id'=>'ex-neet-pg-2026','name'=>'NEET PG 2026','slug'=>'neet-pg-2026','abbr'=>'NEET PG',
    'body'=>'National Board of Examinations (NBE)','logo'=>'https://www.nbe.edu.in/images/nbe-logo.png',
    'level'=>'national','mode'=>'online','freq'=>'annual','colleges'=>700,'applicants'=>400000,'national'=>1,
    'status'=>'active','age_min'=>0,'age_max'=>0,'min_pct'=>50,'qualify'=>'MBBS degree','nationality'=>'indian',
    'marks'=>800,'questions'=>200,'duration'=>210,
    'subjects'=>json_encode(['Pre-Clinical','Para-Clinical','Clinical']),
    'marking'=>json_encode(['+4'=>'Correct answer','-1'=>'Incorrect answer','0'=>'Unattempted']),
    'sections'=>json_encode(['Anatomy','Physiology','Biochemistry','Pathology','Pharmacology','Microbiology','Forensic Medicine','Community Medicine','Ophthalmology','ENT','Medicine','Surgery','OBG','Pediatrics','Orthopedics','Anesthesia','Radiology','Psychiatry','Dermatology']),
    'languages'=>json_encode(['English']),
    'fee_g'=>5500,'fee_obc'=>5500,'fee_sc'=>5500,'fee_pwd'=>0,'fee_f'=>2750,
    'app_url'=>'https://natboard.edu.in','website'=>'https://natboard.edu.in',
    'syllabus_pdf'=>'','result_url'=>'https://natboard.edu.in','scorecard'=>'https://natboard.edu.in',
    'counselling'=>'MCC DG (50% AIQ) + State Counselling (50%)','c_rounds'=>4,'merit'=>'https://natboard.edu.in',
    'normalisation'=>'Raw scores are used. NBE applies normalisation if exam is conducted in multiple shifts using equipercentile method.'
  ],
  [
    'id'=>'ex-gate-2026','name'=>'GATE 2026','slug'=>'gate-2026','abbr'=>'GATE',
    'body'=>'Indian Institute of Science (IISc) & IITs','logo'=>'https://upload.wikimedia.org/wikipedia/en/thumb/0/0c/GATE_2024_logo.svg/200px-GATE_2024_logo.svg.png',
    'level'=>'national','mode'=>'online','freq'=>'annual','colleges'=>600,'applicants'=>900000,'national'=>1,
    'status'=>'active','age_min'=>0,'age_max'=>0,'min_pct'=>60,'qualify'=>'B.E./B.Tech or equivalent','nationality'=>'both',
    'marks'=>100,'questions'=>65,'duration'=>180,
    'subjects'=>json_encode(['General Aptitude','Engineering Mathematics','Subject Specific']),
    'marking'=>json_encode(['+1'=>'MCQ 1-mark Correct','-1/3'=>'MCQ 1-mark Incorrect','+2'=>'MCQ 2-mark Correct','-2/3'=>'MCQ 2-mark Incorrect','0'=>'Unattempted']),
    'sections'=>json_encode(['Section 1: General Aptitude (15 marks)','Section 2: Engineering Mathematics (13 marks)','Section 3: Subject Specific (72 marks)']),
    'languages'=>json_encode(['English']),
    'fee_g'=>1800,'fee_obc'=>1500,'fee_sc'=>1000,'fee_pwd'=>0,'fee_f'=>750,
    'app_url'=>'https://gate.iitb.ac.in','website'=>'https://gate.iitb.ac.in',
    'syllabus_pdf'=>'','result_url'=>'https://gate.iitb.ac.in','scorecard'=>'https://gate.iitb.ac.in',
    'counselling'=>'GATE COAP (IITs) / CCMT (NITs) / JoSAA','c_rounds'=>5,'merit'=>'https://gate.iitb.ac.in',
    'normalisation'=>'Raw marks are used. Score card is valid for 3 years from the date of announcement of results.'
  ],
  [
    'id'=>'ex-cat-2026','name'=>'CAT 2026','slug'=>'cat-2026','abbr'=>'CAT',
    'body'=>'Indian Institutes of Management (IIMs)','logo'=>'https://upload.wikimedia.org/wikipedia/en/thumb/f/fc/CAT_logo.svg/200px-CAT_logo.svg.png',
    'level'=>'national','mode'=>'online','freq'=>'annual','colleges'=>130,'applicants'=>350000,'national'=>1,
    'status'=>'active','age_min'=>0,'age_max'=>0,'min_pct'=>50,'qualify'=>'Bachelor degree with 50%','nationality'=>'both',
    'marks'=>198,'questions'=>66,'duration'=>120,
    'subjects'=>json_encode(['Verbal Ability & Reading Comprehension','Data Interpretation & Logical Reasoning','Quantitative Ability']),
    'marking'=>json_encode(['+3'=>'Correct answer','-1'=>'Incorrect answer','0'=>'Unattempted/MCQ','NA'=>'TITA (no negative marking)']),
    'sections'=>json_encode(['VARC: Verbal Ability & Reading Comprehension (24 questions)','DILR: Data Interpretation & Logical Reasoning (20 questions)','QA: Quantitative Ability (22 questions)']),
    'languages'=>json_encode(['English']),
    'fee_g'=>2400,'fee_obc'=>1200,'fee_sc'=>600,'fee_pwd'=>0,'fee_f'=>600,
    'app_url'=>'https://iimcat.ac.in','website'=>'https://iimcat.ac.in',
    'syllabus_pdf'=>'','result_url'=>'https://iimcat.ac.in','scorecard'=>'https://iimcat.ac.in',
    'counselling'=>'Individual IIM counselling + CAP process','c_rounds'=>3,'merit'=>'https://iimcat.ac.in',
    'normalisation'=>'IIMs use a complex normalisation process. Sectional and overall scores are scaled and percentiled. Each IIM has its own composite score formula.'
  ],
  [
    'id'=>'ex-clat-2026','name'=>'CLAT 2026','slug'=>'clat-2026','abbr'=>'CLAT',
    'body'=>'Consortium of National Law Universities','logo'=>'https://upload.wikimedia.org/wikipedia/en/thumb/7/7e/CLAT_logo.svg/200px-CLAT_logo.svg.png',
    'level'=>'national','mode'=>'offline','freq'=>'annual','colleges'=>22,'applicants'=>70000,'national'=>1,
    'status'=>'active','age_min'=>0,'age_max'=>0,'min_pct'=>45,'qualify'=>'10+2 or equivalent','nationality'=>'indian',
    'marks'=>150,'questions'=>150,'duration'=>120,
    'subjects'=>json_encode(['English Language','Current Affairs','Legal Reasoning','Logical Reasoning','Quantitative Techniques']),
    'marking'=>json_encode(['+1'=>'Correct answer','-0.25'=>'Incorrect answer','0'=>'Unattempted']),
    'sections'=>json_encode(['English Language (28-32 questions)','Current Affairs including GK (35-39 questions)','Legal Reasoning (35-39 questions)','Logical Reasoning (28-32 questions)','Quantitative Techniques (13-17 questions)']),
    'languages'=>json_encode(['English']),
    'fee_g'=>4000,'fee_obc'=>4000,'fee_sc'=>3500,'fee_pwd'=>0,'fee_f'=>3500,
    'app_url'=>'https://consortiumofnlus.ac.in','website'=>'https://consortiumofnlus.ac.in',
    'syllabus_pdf'=>'','result_url'=>'https://consortiumofnlus.ac.in','scorecard'=>'https://consortiumofnlus.ac.in',
    'counselling'=>'CLAT Consortium counselling','c_rounds'=>3,'merit'=>'https://consortiumofnlus.ac.in',
    'normalisation'=>'Raw marks are used for ranking. No normalisation applied. Merit list is based on total marks out of 150.'
  ],
  [
    'id'=>'ex-xat-2026','name'=>'XAT 2026','slug'=>'xat-2026','abbr'=>'XAT',
    'body'=>'Xavier Labour Relations Institute (XLRI)','logo'=>'https://www.xlri.ac.in/images/logo.png',
    'level'=>'national','mode'=>'online','freq'=>'annual','colleges'=>150,'applicants'=>100000,'national'=>1,
    'status'=>'active','age_min'=>0,'age_max'=>0,'min_pct'=>50,'qualify'=>'Bachelor degree with 50%','nationality'=>'both',
    'marks'=>100,'questions'=>100,'duration'=>180,
    'subjects'=>json_encode(['Verbal & Logical Reasoning','Decision Making','Quantitative Aptitude & Data Interpretation','General Knowledge']),
    'marking'=>json_encode(['+1'=>'Correct answer','-0.25'=>'Incorrect answer','0'=>'Unattempted']),
    'sections'=>json_encode(['Part 1: Verbal & Logical Reasoning','Part 1: Decision Making','Part 1: Quantitative Aptitude & DI','Part 2: General Knowledge (25 questions)']),
    'languages'=>json_encode(['English']),
    'fee_g'=>2100,'fee_obc'=>2100,'fee_sc'=>1050,'fee_pwd'=>0,'fee_f'=>1050,
    'app_url'=>'https://xatonline.in','website'=>'https://xatonline.in',
    'syllabus_pdf'=>'','result_url'=>'https://xatonline.in','scorecard'=>'https://xatonline.in',
    'counselling'=>'Individual institute counselling','c_rounds'=>3,'merit'=>'https://xatonline.in',
    'normalisation'=>'Raw scores are used. GK section is not used for ranking but considered during interview stage by individual institutes.'
  ],
  [
    'id'=>'ex-snap-2026','name'=>'SNAP 2026','slug'=>'snap-2026','abbr'=>'SNAP',
    'body'=>'Symbiosis International (Deemed University)','logo'=>'https://www.siu.edu.in/images/logo.png',
    'level'=>'national','mode'=>'online','freq'=>'annual','colleges'=>15,'applicants'=>80000,'national'=>1,
    'status'=>'active','age_min'=>0,'age_max'=>0,'min_pct'=>50,'qualify'=>'Bachelor degree with 50%','nationality'=>'both',
    'marks'=>150,'questions'=>60,'duration'=>60,
    'subjects'=>json_encode(['General English','Quantitative Aptitude','Logical & Analytical Reasoning','Current Affairs']),
    'marking'=>json_encode(['+1'=>'Correct answer','-0.25'=>'Incorrect answer','0'=>'Unattempted']),
    'sections'=>json_encode(['General English (15 questions)','Quantitative Aptitude (20 questions)','Logical & Analytical Reasoning (25 questions)']),
    'languages'=>json_encode(['English']),
    'fee_g'=>2250,'fee_obc'=>2250,'fee_sc'=>1125,'fee_pwd'=>0,'fee_f'=>1125,
    'app_url'=>'https://www.snaptest.org','website'=>'https://www.snaptest.org',
    'syllabus_pdf'=>'','result_url'=>'https://www.snaptest.org','scorecard'=>'https://www.snaptest.org',
    'counselling'=>'Symbiosis institute counselling','c_rounds'=>3,'merit'=>'https://www.snaptest.org',
    'normalisation'=>'Raw marks are used. Score is valid only for the admission year.'
  ],
  [
    'id'=>'ex-mat-2026','name'=>'MAT 2026','slug'=>'mat-2026','abbr'=>'MAT',
    'body'=>'All India Management Association (AIMA)','logo'=>'https://www.aima.in/images/logo.png',
    'level'=>'national','mode'=>'both','freq'=>'annual','colleges'=>800,'applicants'=>200000,'national'=>1,
    'status'=>'active','age_min'=>0,'age_max'=>0,'min_pct'=>50,'qualify'=>'Bachelor degree with 50%','nationality'=>'both',
    'marks'=>200,'questions'=>200,'duration'=>150,
    'subjects'=>json_encode(['Language Comprehension','Mathematical Skills','Data Analysis & Sufficiency','Intelligence & Critical Reasoning','Indian & Global Environment']),
    'marking'=>json_encode(['+1'=>'Correct answer','-0.25'=>'Incorrect answer','0'=>'Unattempted']),
    'sections'=>json_encode(['Language Comprehension (40 questions)','Mathematical Skills (40 questions)','Data Analysis & Sufficiency (40 questions)','Intelligence & Critical Reasoning (40 questions)','Indian & Global Environment (40 questions)']),
    'languages'=>json_encode(['English','Hindi']),
    'fee_g'=>1800,'fee_obc'=>1800,'fee_sc'=>900,'fee_pwd'=>0,'fee_f'=>900,
    'app_url'=>'https://www.aima.in','website'=>'https://www.aima.in',
    'syllabus_pdf'=>'','result_url'=>'https://www.aima.in','scorecard'=>'https://www.aima.in',
    'counselling'=>'Individual institute counselling','c_rounds'=>4,'merit'=>'https://www.aima.in',
    'normalisation'=>'MAT uses composite score. Indian & Global Environment section marks are not used in final percentile calculation.'
  ],
  [
    'id'=>'ex-cmat-2026','name'=>'CMAT 2026','slug'=>'cmat-2026','abbr'=>'CMAT',
    'body'=>'National Testing Agency (NTA)','logo'=>'https://upload.wikimedia.org/wikipedia/en/thumb/4/41/NTA_logo.svg/200px-NTA_logo.svg.png',
    'level'=>'national','mode'=>'online','freq'=>'annual','colleges'=>1000,'applicants'=>70000,'national'=>1,
    'status'=>'active','age_min'=>0,'age_max'=>0,'min_pct'=>50,'qualify'=>'Bachelor degree with 50%','nationality'=>'indian',
    'marks'=>400,'questions'=>100,'duration'=>180,
    'subjects'=>json_encode(['Logical Reasoning','Language Comprehension','Quantitative Techniques & DI','General Awareness','Innovation & Entrepreneurship']),
    'marking'=>json_encode(['+4'=>'Correct answer','-1'=>'Incorrect answer','0'=>'Unattempted']),
    'sections'=>json_encode(['Logical Reasoning (25 questions)','Language Comprehension (25 questions)','Quantitative Techniques & DI (25 questions)','General Awareness (25 questions)','Innovation & Entrepreneurship (Optional, 25 questions)']),
    'languages'=>json_encode(['English']),
    'fee_g'=>2100,'fee_obc'=>1050,'fee_sc'=>1050,'fee_pwd'=>0,'fee_f'=>1050,
    'app_url'=>'https://cmat.nta.nic.in','website'=>'https://cmat.nta.nic.in',
    'syllabus_pdf'=>'','result_url'=>'https://cmat.nta.nic.in','scorecard'=>'https://cmat.nta.nic.in',
    'counselling'=>'AICTE counselling + individual institute','c_rounds'=>3,'merit'=>'https://cmat.nta.nic.in',
    'normalisation'=>'NTA uses percentile-based normalisation across multiple sessions.'
  ],
  [
    'id'=>'ex-gmat-2026','name'=>'GMAT 2026','slug'=>'gmat-2026','abbr'=>'GMAT',
    'body'=>'Graduate Management Admission Council (GMAC)','logo'=>'https://www.mba.com/sites/default/files/2023-09/gmat-logo.png',
    'level'=>'international','mode'=>'online','freq'=>'annual','colleges'=>7000,'applicants'=>300000,'national'=>0,
    'status'=>'active','age_min'=>18,'age_max'=>0,'min_pct'=>0,'qualify'=>'Bachelor degree','nationality'=>'both',
    'marks'=>805,'questions'=>64,'duration'=>195,
    'subjects'=>json_encode(['Quantitative Reasoning','Verbal Reasoning','Data Insights']),
    'marking'=>json_encode(['Adaptive scoring from 200-805']),
    'sections'=>json_encode(['Quantitative Reasoning (21 questions)','Verbal Reasoning (23 questions)','Data Insights (20 questions)']),
    'languages'=>json_encode(['English']),
    'fee_g'=>275,'fee_obc'=>275,'fee_sc'=>275,'fee_pwd'=>0,'fee_f'=>275,
    'app_url'=>'https://www.mba.com/exams/gmat','website'=>'https://www.mba.com',
    'syllabus_pdf'=>'','result_url'=>'https://www.mba.com','scorecard'=>'https://www.mba.com',
    'counselling'=>'Direct application to business schools','c_rounds'=>0,'merit'=>'https://www.mba.com',
    'normalisation'=>'GMAT uses adaptive testing. Questions adjust difficulty based on performance. Final score is on a 200-805 scale.'
  ],
  [
    'id'=>'ex-upsc-cse-2026','name'=>'UPSC CSE 2026','slug'=>'upsc-cse-2026','abbr'=>'UPSC CSE',
    'body'=>'Union Public Service Commission','logo'=>'https://upload.wikimedia.org/wikipedia/en/thumb/1/1f/UPSC_logo.svg/200px-UPSC_logo.svg.png',
    'level'=>'national','mode'=>'offline','freq'=>'annual','colleges'=>0,'applicants'=>1000000,'national'=>1,
    'status'=>'active','age_min'=>21,'age_max'=>32,'min_pct'=>50,'qualify'=>'Bachelor degree from recognised university','nationality'=>'indian',
    'marks'=>2025,'questions'=>175,'duration'=>360,
    'subjects'=>json_encode(['General Studies','Optional Subject','Essay','Ethics','Language']),
    'marking'=>json_encode(['GS: +1/-0.33','Optional: +1/-0.33','Essay: No negative marking']),
    'sections'=>json_encode(['Prelims: GS Paper 1 (100 questions)','Prelims: CSAT Paper 2 (80 questions)','Mains: Essay Paper','Mains: GS Paper 1-4','Mains: Optional Subject Paper 1 & 2','Mains: Language Papers','Interview']),
    'languages'=>json_encode(['English','Hindi']),
    'fee_g'=>100,'fee_obc'=>100,'fee_sc'=>0,'fee_pwd'=>0,'fee_f'=>0,
    'app_url'=>'https://upsconline.nic.in','website'=>'https://upsc.gov.in',
    'syllabus_pdf'=>'','result_url'=>'https://upsc.gov.in','scorecard'=>'https://upsc.gov.in',
    'counselling'=>'DoPT/Department of Personnel & Training','c_rounds'=>1,'merit'=>'https://upsc.gov.in',
    'normalisation'=>'Prelims uses normalised scoring. Mains uses raw marks. Final merit is based on Mains (1750) + Interview (275) = 2025 total.'
  ],
  [
    'id'=>'ex-ssc-cgl-2026','name'=>'SSC CGL 2026','slug'=>'ssc-cgl-2026','abbr'=>'SSC CGL',
    'body'=>'Staff Selection Commission','logo'=>'https://upload.wikimedia.org/wikipedia/en/thumb/5/59/SSC_logo.svg/200px-SSC_logo.svg.png',
    'level'=>'national','mode'=>'online','freq'=>'annual','colleges'=>0,'applicants'=>3000000,'national'=>1,
    'status'=>'active','age_min'=>18,'age_max'=>32,'min_pct'=>0,'qualify'=>'Bachelor degree','nationality'=>'indian',
    'marks'=>390,'questions'=>100,'duration'=>300,
    'subjects'=>json_encode(['General Intelligence & Reasoning','General Awareness','Quantitative Aptitude','English Comprehension']),
    'marking'=>json_encode(['+3'=>'Correct answer','-1'=>'Incorrect answer','0'=>'Unattempted']),
    'sections'=>json_encode(['Tier 1: General Intelligence & Reasoning (25 questions)','Tier 1: General Awareness (25 questions)','Tier 1: Quantitative Aptitude (25 questions)','Tier 1: English Comprehension (25 questions)','Tier 2: Quantitative Abilities','Tier 2: English Language & Comprehension','Tier 2: General Awareness','Tier 2: Computer Proficiency']),
    'languages'=>json_encode(['English','Hindi']),
    'fee_g'=>100,'fee_obc'=>100,'fee_sc'=>0,'fee_pwd'=>0,'fee_f'=>0,
    'app_url'=>'https://ssc.nic.in','website'=>'https://ssc.nic.in',
    'syllabus_pdf'=>'','result_url'=>'https://ssc.nic.in','scorecard'=>'https://ssc.nic.in',
    'counselling'=>'Combined counselling by SSC','c_rounds'=>2,'merit'=>'https://ssc.nic.in',
    'normalisation'=>'SSC uses normalisation formula for Tier 1 exam across multiple shifts to equalise difficulty levels.'
  ],
  [
    'id'=>'ex-neet-pg-2026b','name'=>'AIIMS PG 2026','slug'=>'aiims-pg-2026','abbr'=>'AIIMS PG',
    'body'=>'All India Institute of Medical Sciences','logo'=>'https://www.aiims.edu/images/logo.png',
    'level'=>'national','mode'=>'online','freq'=>'annual','colleges'=>20,'applicants'=>50000,'national'=>1,
    'status'=>'active','age_min'=>0,'age_max'=>0,'min_pct'=>55,'qualify'=>'MBBS with internship completion','nationality'=>'indian',
    'marks'=>200,'questions'=>200,'duration'=>180,
    'subjects'=>json_encode(['Pre-Clinical','Para-Clinical','Clinical']),
    'marking'=>json_encode(['+1'=>'Correct answer','-1/3'=>'Incorrect answer','0'=>'Unattempted']),
    'sections'=>json_encode(['Anatomy','Physiology','Biochemistry','Pathology','Pharmacology','Microbiology','Community Medicine','Medicine','Surgery','OBG','Pediatrics']),
    'languages'=>json_encode(['English']),
    'fee_g'=>2000,'fee_obc'=>1500,'fee_sc'=>1000,'fee_pwd'=>0,'fee_f'=>1000,
    'app_url'=>'https://www.aiimsexams.ac.in','website'=>'https://www.aiims.edu',
    'syllabus_pdf'=>'','result_url'=>'https://www.aiimsexams.ac.in','scorecard'=>'https://www.aiimsexams.ac.in',
    'counselling'=>'AIIMS counselling','c_rounds'=>3,'merit'=>'https://www.aiimsexams.ac.in',
    'normalisation'=>'Raw marks used for ranking. Computer-based test.'
  ],
  [
    'id'=>'ex-inicet-2026','name'=>'INI-CET 2026','slug'=>'ini-cet-2026','abbr'=>'INI-CET',
    'body'=>'National Board of Examinations (NBE)','logo'=>'https://www.nbe.edu.in/images/nbe-logo.png',
    'level'=>'national','mode'=>'online','freq'=>'annual','colleges'=>10,'applicants'=>40000,'national'=>1,
    'status'=>'active','age_min'=>0,'age_max'=>0,'min_pct'=>55,'qualify'=>'MBBS with internship completion','nationality'=>'indian',
    'marks'=>200,'questions'=>200,'duration'=>180,
    'subjects'=>json_encode(['Pre-Clinical','Para-Clinical','Clinical']),
    'marking'=>json_encode(['+1'=>'Correct answer','-1/3'=>'Incorrect answer','0'=>'Unattempted']),
    'sections'=>json_encode(['Pre-Clinical subjects','Para-Clinical subjects','Clinical subjects']),
    'languages'=>json_encode(['English']),
    'fee_g'=>4250,'fee_obc'=>4250,'fee_sc'=>4250,'fee_pwd'=>0,'fee_f'=>2125,
    'app_url'=>'https://natboard.edu.in','website'=>'https://natboard.edu.in',
    'syllabus_pdf'=>'','result_url'=>'https://natboard.edu.in','scorecard'=>'https://natboard.edu.in',
    'counselling'=>'INI-CET counselling by AIIMS','c_rounds'=>3,'merit'=>'https://natboard.edu.in',
    'normalisation'=>'Raw marks used. INI-CET replaced AIIMS PG and JIPMER PG entrance exams.'
  ],
  [
    'id'=>'ex-nata-2026','name'=>'NATA 2026','slug'=>'nata-2026','abbr'=>'NATA',
    'body'=>'Council of Architecture (CoA)','logo'=>'https://www.nata.in/images/nata-logo.png',
    'level'=>'national','mode'=>'online','freq'=>'annual','colleges'=>400,'applicants'=>80000,'national'=>1,
    'status'=>'active','age_min'=>0,'age_max'=>0,'min_pct'=>50,'qualify'=>'10+2 with 50% aggregate in PCM','nationality'=>'indian',
    'marks'=>200,'questions'=>52,'duration'=>180,
    'subjects'=>json_encode(['Physics','Chemistry','Mathematics','Drawing & Composition']),
    'marking'=>json_encode(['MCQ: +2 per correct','Drawing: Graded on composition, creativity, visual communication']),
    'sections'=>json_encode(['Part A: PCM (12 MCQ questions, 30 marks)','Part B: Drawing (2 questions, 80 marks)','Part C: PCM MCQ (40 questions, 90 marks)']),
    'languages'=>json_encode(['English']),
    'fee_g'=>2000,'fee_obc'=>2000,'fee_sc'=>1000,'fee_pwd'=>0,'fee_f'=>1000,
    'app_url'=>'https://www.nata.in','website'=>'https://www.nata.in',
    'syllabus_pdf'=>'','result_url'=>'https://www.nata.in','scorecard'=>'https://www.nata.in',
    'counselling'=>'State-level counselling + CAP rounds','c_rounds'=>3,'merit'=>'https://www.nata.in',
    'normalisation'=>'Part A and Part B are evaluated separately. Drawing section is evaluated offline by expert panel.'
  ],
  [
    'id'=>'ex-wbjee-2026','name'=>'WBJEE 2026','slug'=>'wbjee-2026','abbr'=>'WBJEE',
    'body'=>'West Bengal Joint Entrance Examinations Board','logo'=>'https://wbjeeb.nic.in/images/logo.png',
    'level'=>'state','mode'=>'offline','freq'=>'annual','colleges'=>150,'applicants'=>120000,'national'=>0,
    'status'=>'active','age_min'=>17,'age_max'=>0,'min_pct'=>45,'qualify'=>'10+2 with PCM','nationality'=>'indian',
    'marks'=>200,'questions'=>155,'duration'=>240,
    'subjects'=>json_encode(['Physics','Chemistry','Mathematics']),
    'marking'=>json_encode(['+1'=>'Category I correct','-0.25'=>'Category I incorrect','+2'=>'Category II correct','0'=>'Category II incorrect']),
    'sections'=>json_encode(['Paper 1: Mathematics (100 marks)','Paper 2: Physics + Chemistry (100 marks)']),
    'languages'=>json_encode(['English','Hindi','Bengali']),
    'fee_g'=>600,'fee_obc'=>500,'fee_sc'=>250,'fee_pwd'=>0,'fee_f'=>250,
    'app_url'=>'https://wbjeeb.nic.in','website'=>'https://wbjeeb.nic.in',
    'syllabus_pdf'=>'','result_url'=>'https://wbjeeb.nic.in','scorecard'=>'https://wbjeeb.nic.in',
    'counselling'=>'WBJEE counselling by WBJEEB','c_rounds'=>3,'merit'=>'https://wbjeeb.nic.in',
    'normalisation'=>'No normalisation. Raw marks used for ranking.'
  ],
  [
    'id'=>'ex-mht-cet-2026','name'=>'MHT CET 2026','slug'=>'mht-cet-2026','abbr'=>'MHT CET',
    'body'=>'State Common Entrance Test Cell, Maharashtra','logo'=>'https://cetcell.mahacet.org/images/logo.png',
    'level'=>'state','mode'=>'online','freq'=>'annual','colleges'=>200,'applicants'=>500000,'national'=>0,
    'status'=>'active','age_min'=>0,'age_max'=>0,'min_pct'=>50,'qualify'=>'10+2 with PCM or PCB','nationality'=>'indian',
    'marks'=>200,'questions'=>150,'duration'=>180,
    'subjects'=>json_encode(['Physics','Chemistry','Mathematics','Biology']),
    'marking'=>json_encode(['+2'=>'Correct answer','0'=>'Unattempted','0'=>'Incorrect (no negative marking)']),
    'sections'=>json_encode(['PCM Group: Physics (50), Chemistry (50), Mathematics (50)','PCB Group: Physics (50), Chemistry (50), Biology (50)']),
    'languages'=>json_encode(['English','Marathi','Hindi','Urdu']),
    'fee_g'=>1000,'fee_obc'=>800,'fee_sc'=>400,'fee_pwd'=>0,'fee_f'=>400,
    'app_url'=>'https://cetcell.mahacet.org','website'=>'https://cetcell.mahacet.org',
    'syllabus_pdf'=>'','result_url'=>'https://cetcell.mahacet.org','scorecard'=>'https://cetcell.mahacet.org',
    'counselling'=>'State CAP counselling by Maharashtra CET Cell','c_rounds'=>4,'merit'=>'https://cetcell.mahacet.org',
    'normalisation'=>'10th + 12th board marks are also considered for merit ranking along with CET score.'
  ],
  [
    'id'=>'ex-ts-eamcet-2026','name'=>'TS EAMCET 2026','slug'=>'ts-eamcet-2026','abbr'=>'TS EAMCET',
    'body'=>'Jawaharlal Nehru Technological University Hyderabad','logo'=>'https://jntuh.ac.in/images/logo.png',
    'level'=>'state','mode'=>'online','freq'=>'annual','colleges'=>300,'applicants'=>250000,'national'=>0,
    'status'=>'active','age_min'=>0,'age_max'=>0,'min_pct'=>45,'qualify'=>'10+2 with PCM or PCB','nationality'=>'indian',
    'marks'=>160,'questions'=>160,'duration'=>180,
    'subjects'=>json_encode(['Physics','Chemistry','Mathematics','Biology']),
    'marking'=>json_encode(['+1'=>'Correct answer','0'=>'Unattempted','0'=>'Incorrect (no negative marking)']),
    'sections'=>json_encode(['Engineering: Physics (40), Chemistry (40), Mathematics (80)','Medical: Physics (40), Chemistry (40), Biology (80)']),
    'languages'=>json_encode(['English','Telugu','Hindi']),
    'fee_g'=>800,'fee_obc'=>400,'fee_sc'=>200,'fee_pwd'=>0,'fee_f'=>200,
    'app_url'=>'https://tseamcet.nic.in','website'=>'https://tseamcet.nic.in',
    'syllabus_pdf'=>'','result_url'=>'https://tseamcet.nic.in','scorecard'=>'https://tseamcet.nic.in',
    'counselling'=>'TS EAMCET counselling by TSCHE','c_rounds'=>3,'merit'=>'https://tseamcet.nic.in',
    'normalisation'=>'No negative marking. 25% weightage to 12th board marks in some years (check notification).'
  ],
  [
    'id'=>'ex-bitstat-2026','name'=>'BITSAT 2026','slug'=>'bitsat-2026','abbr'=>'BITSAT',
    'body'=>'Birla Institute of Technology and Science','logo'=>'https://www.bits-pilani.ac.in/images/logo.png',
    'level'=>'university','mode'=>'online','freq'=>'annual','colleges'=>4,'applicants'=>200000,'national'=>0,
    'status'=>'active','age_min'=>0,'age_max'=>0,'min_pct'=>75,'qualify'=>'10+2 with PCM and 75% aggregate','nationality'=>'indian',
    'marks'=>390,'questions'=>130,'duration'=>180,
    'subjects'=>json_encode(['Physics','Chemistry','Mathematics','English','Logical Reasoning']),
    'marking'=>json_encode(['+3'=>'Correct answer','-1'=>'Incorrect answer','0'=>'Unattempted']),
    'sections'=>json_encode(['Physics (40 questions)','Chemistry (40 questions)','Mathematics (45 questions)','English (15 questions)','Logical Reasoning (10 questions)']),
    'languages'=>json_encode(['English']),
    'fee_g'=>3400,'fee_obc'=>3400,'fee_sc'=>3400,'fee_pwd'=>0,'fee_f'=>3400,
    'app_url'=>'https://bitsadmission.com','website'=>'https://bits-pilani.ac.in',
    'syllabus_pdf'=>'','result_url'=>'https://bitsadmission.com','scorecard'=>'https://bitsadmission.com',
    'counselling'=>'BITS counselling','c_rounds'=>3,'merit'=>'https://bitsadmission.com',
    'normalisation'=>'No normalisation. Raw score out of 390. Additional 12 marks for correct answers in extra questions attempted if time permits.'
  ],
];

$ins = $pdo->prepare("INSERT INTO exams (id, exam_name, exam_slug, exam_abbreviation, conducting_body, conducting_body_logo, exam_level, exam_mode, exam_frequency, participating_colleges_count, applicants_last_year, is_national, status, age_min, age_max, min_percentage_required, qualifying_exam, nationality, total_marks, total_questions, duration_minutes, subjects_json, marking_scheme, sections, language_options, application_fee_general, application_fee_obc, application_fee_sc_st, application_fee_pwd, application_fee_female, application_url, official_website, syllabus_pdf_url, result_url, scorecard_url, counselling_authority, counselling_rounds, merit_list_url, normalisation_method) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

$count = 0;
foreach ($exams as $e) {
    $ins->execute([
        $e['id'], $e['name'], $e['slug'], $e['abbr'], $e['body'], $e['logo'],
        $e['level'], $e['mode'], $e['freq'], $e['colleges'], $e['applicants'], $e['national'],
        $e['status'], $e['age_min'], $e['age_max'], $e['min_pct'], $e['qualify'], $e['nationality'],
        $e['marks'], $e['questions'], $e['duration'], $e['subjects'], $e['marking'], $e['sections'],
        $e['languages'], $e['fee_g'], $e['fee_obc'], $e['fee_sc'], $e['fee_pwd'], $e['fee_f'],
        $e['app_url'], $e['website'], $e['syllabus_pdf'], $e['result_url'], $e['scorecard'],
        $e['counselling'], $e['c_rounds'], $e['merit'], $e['normalisation']
    ]);
    $count++;
    echo "Inserted: " . $e['name'] . PHP_EOL;
}
echo "Total exams inserted: $count" . PHP_EOL;

$pdo->commit();
echo PHP_EOL . "=== DONE ===" . PHP_EOL;
