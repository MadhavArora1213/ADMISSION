<?php
/**
 * RESET & RE-SEED ALL COURSES DATA
 * Deletes all existing courses, specializations, and career paths,
 * then re-inserts comprehensive data for all 5 tabs.
 */
require_once __DIR__ . '/admin/db.php';

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

// ═══════════════════════════════════════════════════════════════════════════════
// STEP 0: DISABLE FOREIGN KEY CHECKS
// ═══════════════════════════════════════════════════════════════════════════════
$pdo->exec("SET FOREIGN_KEY_CHECKS = 0");

// ═══════════════════════════════════════════════════════════════════════════════
// STEP 1: DELETE ALL EXISTING DATA
// ═══════════════════════════════════════════════════════════════════════════════
echo "Deleting existing course data...\n";
run($pdo, "DELETE FROM course_career_paths", 'delete careers');
run($pdo, "DELETE FROM course_specializations", 'delete specs');
run($pdo, "DELETE FROM courses", 'delete courses');

// ═══════════════════════════════════════════════════════════════════════════════
// STEP 2: INSERT COURSE CATEGORIES (needed for FK)
// ═══════════════════════════════════════════════════════════════════════════════
echo "Inserting course categories...\n";

$catStmt = $pdo->prepare("INSERT IGNORE INTO course_categories (id, category_name, category_slug, sort_order, is_featured) VALUES (?, ?, ?, ?, 1)");

$categories = [
    ['cat-eng-01', 'Engineering', 'engineering', 1],
    ['cat-mgt-02', 'Management', 'management', 2],
    ['cat-med-03', 'Medical', 'medical', 3],
    ['cat-it-01', 'IT & Software', 'it-software', 4],
    ['cat-law-01', 'Law', 'law', 5],
    ['cat-com-01', 'Commerce', 'commerce', 6],
    ['cat-des-01', 'Design', 'design', 7],
];

foreach ($categories as $cat) {
    try { $catStmt->execute($cat); $ok++; } catch(Exception $e) { echo "WARN cat: " . $e->getMessage() . "\n"; }
}

// ═══════════════════════════════════════════════════════════════════════════════
// STEP 3: INSERT COURSES (with full data for all tabs)
// ═══════════════════════════════════════════════════════════════════════════════
echo "Inserting courses...\n";

$s = $pdo->prepare("INSERT INTO courses (id,course_name,course_slug,course_level,course_category,category_id,duration_years,description,eligibility,career_scope,top_recruiters,avg_salary_lpa,salary_range_min,salary_range_max,is_popular,total_colleges_offering,status) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");

$courses = [
    // ─── 1. B.TECH ───────────────────────────────────────────────────────────
    [
        'crs-btech-01', 'B.Tech - Bachelor of Technology', 'btech', 'UG', 'Engineering', 'cat-eng-01', 4,
        'B.Tech (Bachelor of Technology) is a 4-year undergraduate professional degree program in engineering and technology. It is one of the most popular and sought-after courses in India, offered by thousands of engineering colleges including IITs, NITs, and private institutions. The program provides a strong foundation in technical and analytical skills, preparing students for careers in software development, hardware engineering, civil infrastructure, electronics, and emerging technologies like AI, data science, and cybersecurity.',
        '10+2 with Physics, Chemistry, and Mathematics (PCM) with a minimum of 60% aggregate marks (50% for reserved categories). Admission is primarily through national-level entrance exams like JEE Main, JEE Advanced, state-level CETs, and university-specific exams. Some private colleges also accept SAT scores.',
        'B.Tech graduates have excellent career prospects across diverse sectors. The IT industry remains the largest recruiter, hiring software engineers, full-stack developers, data scientists, and AI/ML engineers. Core engineering sectors like manufacturing, automotive, construction, and energy also offer significant opportunities. Graduates can pursue higher studies (M.Tech, MS abroad), appear for GATE/IES, or start their own tech ventures. Average starting salary ranges from ₹4-8 LPA, with top performers at premium institutions earning ₹20-50+ LPA.',
        '[{"name":"Tata Consultancy Services","logo":""},{"name":"Infosys","logo":""},{"name":"Wipro","logo":""},{"name":"Microsoft India","logo":""},{"name":"Google India","logo":""},{"name":"Larsen & Toubro","logo":""},{"name":"Tata Motors","logo":""},{"name":"Samsung India","logo":""},{"name":"Amazon India","logo":""},{"name":"Flipkart","logo":""}]',
        8.50, 4.00, 45.00, 1, 3500, 'active'
    ],

    // ─── 2. MBA ──────────────────────────────────────────────────────────────
    [
        'crs-mba-02', 'MBA - Master of Business Administration', 'mba', 'PG', 'Management', 'cat-mgt-02', 2,
        'MBA (Master of Business Administration) is a 2-year postgraduate program that provides comprehensive knowledge of business management, leadership, and organizational strategy. It covers core areas including finance, marketing, human resources, operations, and entrepreneurship. MBA graduates are equipped with analytical, managerial, and decision-making skills essential for leadership roles in corporate organizations, startups, consulting firms, and multinational companies. The program also offers excellent networking opportunities through peer learning and industry connections.',
        'Bachelor\'s degree in any discipline from a recognized university with a minimum of 50% aggregate marks (45% for reserved categories). Final year students can also apply. Admission is through national-level entrance exams like CAT, XAT, MAT, CMAT, ATMA, GMAT, or state-level exams like KMAT, TANCET. Some institutes conduct their own admission processes including GD, PI, and WAT.',
        'MBA graduates are in high demand across all sectors of the economy. Top recruiters include consulting firms (McKinsey, BCG, Deloitte), FMCG companies (HUL, P&G, ITC), banking and finance (Goldman Sachs, HDFC Bank, ICICI), e-commerce (Amazon, Flipkart), and technology companies (Google, Microsoft). Career roles include Business Analyst, Marketing Manager, Financial Analyst, HR Manager, Product Manager, Operations Manager, and Management Consultant. Entrepreneurs leverage MBA skills to launch successful startups. Average starting salary ranges from ₹6-12 LPA, with top B-school graduates earning ₹25-60+ LPA.',
        '[{"name":"McKinsey & Company","logo":""},{"name":"Boston Consulting Group","logo":""},{"name":"Amazon","logo":""},{"name":"Deloitte","logo":""},{"name":"HDFC Bank","logo":""},{"name":"Reliance Industries","logo":""},{"name":"Hindustan Unilever","logo":""},{"name":"ITC Limited","logo":""},{"name":"Goldman Sachs","logo":""},{"name":"Tata Group","logo":""}]',
        12.00, 6.00, 60.00, 1, 2800, 'active'
    ],

    // ─── 3. MBBS ─────────────────────────────────────────────────────────────
    [
        'crs-mbbs-03', 'MBBS - Bachelor of Medicine and Bachelor of Surgery', 'mbbs', 'UG', 'Medical', 'cat-med-03', 5,
        'MBBS (Bachelor of Medicine and Bachelor of Surgery) is a 5.5-year undergraduate medical degree program (including 1 year of compulsory rotating internship). It is the primary medical qualification in India that enables graduates to practice as licensed medical doctors. The curriculum covers anatomy, physiology, biochemistry, pharmacology, pathology, microbiology, forensic medicine, community medicine, and clinical rotations across various departments including medicine, surgery, pediatrics, obstetrics & gynecology, ophthalmology, and ENT.',
        '10+2 with Physics, Chemistry, and Biology (PCB) with a minimum of 50% aggregate marks (40% for reserved categories). Must qualify NEET UG (National Eligibility cum Entrance Test) conducted by NTA. The admission process is through MCC counseling for AIQ seats and state counseling for state quota seats. Age requirement: minimum 17 years as of December 31 of the admission year.',
        'MBBS graduates have diverse career opportunities in the medical field. After completing the degree, graduates can practice as general physicians, pursue higher specialization through MD/MS/DM/MCh programs, or appear for competitive exams like NEET PG, UPSC CMS, and AIIMS PG. Career options include Government and Private Hospital practice, Medical Officer in PHCs/CHCs, Armed Forces Medical Services, Medical College teaching, Research in medical sciences, Public Health administration, and Medical journalism. The healthcare sector is growing rapidly with increasing demand for qualified doctors.',
        '[{"name":"All India Institute of Medical Sciences","logo":""},{"name":"Apollo Hospitals","logo":""},{"name":"Fortis Healthcare","logo":""},{"name":"Max Healthcare","logo":""},{"name":"Manipal Hospitals","logo":""},{"name":"Narayana Health","logo":""},{"name":"AIIMS Delhi","logo":""},{"name":"PGIMER Chandigarh","logo":""},{"name":"Armed Forces Medical Services","logo":""},{"name":"Government Medical Colleges","logo":""}]',
        9.00, 6.00, 30.00, 1, 600, 'active'
    ],

    // ─── 4. BCA ──────────────────────────────────────────────────────────────
    [
        'crs-bca-04', 'BCA - Bachelor of Computer Applications', 'bca', 'UG', 'IT & Software', 'cat-it-01', 3,
        'BCA (Bachelor of Computer Applications) is a 3-year undergraduate program that provides a strong foundation in computer science, programming languages, database management, networking, and web development. It is an excellent alternative to B.Tech for students interested in building a career in the IT industry. The curriculum covers programming in C, C++, Java, Python, web technologies (HTML, CSS, JavaScript, React), database systems (MySQL, MongoDB), operating systems, and software engineering principles.',
        '10+2 from a recognized board with a minimum of 50% aggregate marks. Mathematics as a subject in 12th grade is preferred (and required by some universities). Some colleges conduct their own entrance exams while others offer direct admission based on merit. Universities like IGNOU, Symbiosis, Christ University, and SRM have specific admission processes.',
        'BCA graduates can work as Software Developers, Web Developers, System Analysts, Database Administrators, Network Engineers, and IT Support Specialists. The IT industry offers excellent growth opportunities with companies like TCS, Infosys, Wipro, and tech startups regularly hiring BCA graduates. Pursuing MCA after BCA significantly enhances career prospects and salary potential. Average starting salary ranges from ₹3-6 LPA, with experienced professionals earning ₹10-20+ LPA.',
        '[{"name":"Tata Consultancy Services","logo":""},{"name":"Wipro","logo":""},{"name":"Infosys","logo":""},{"name":"Tech Mahindra","logo":""},{"name":"Capgemini","logo":""},{"name":"Cognizant","logo":""},{"name":"HCL Technologies","logo":""},{"name":"Mindtree","logo":""},{"name":"Mphasis","logo":""},{"name":"LTIMindtree","logo":""}]',
        4.50, 2.50, 15.00, 1, 1200, 'active'
    ],

    // ─── 5. LLB ──────────────────────────────────────────────────────────────
    [
        'crs-llb-05', 'LLB - Bachelor of Legislative Law', 'llb', 'UG', 'Law', 'cat-law-01', 3,
        'LLB (Bachelor of Legislative Law) is a 3-year undergraduate degree in law that provides comprehensive knowledge of the Indian legal system, constitutional law, criminal law, civil law, corporate law, and international law. The program trains students in legal research, drafting, advocacy, and analytical thinking. Students learn about the Indian Penal Code, Code of Criminal Procedure, Code of Civil Procedure, Evidence Act, Contract Act, and various other statutes. Moot courts, legal aid clinics, and internships with law firms are integral parts of the curriculum.',
        'Graduation in any discipline from a recognized university with a minimum of 45-50% aggregate marks (40-45% for reserved categories). Admission is through entrance exams like DU LLB Entrance, MH CET Law, LSAT India, BHU UET, AP LAWCET, TS LAWCET, and Christ University Law Entrance. Some universities also offer merit-based admission.',
        'LLB graduates have diverse career opportunities in the legal profession. They can practice as Advocates in High Courts and Supreme Court, become Legal Advisors for corporations, join the Judiciary through competitive exams, or work in government legal departments. Other career paths include Corporate Law firms, Legal Process Outsourcing (LPO), Arbitration and Mediation, Legal Journalism, Academia and Law Teaching, Public Interest Litigation, and Civil Services (IAS/IPS through UPSC). Average starting salary ranges from ₹3-8 LPA, with experienced lawyers at top firms earning ₹20-50+ LPA.',
        '[{"name":"Cyril Amarchand Mangaldas","logo":""},{"name":"Khaitan & Co","logo":""},{"name":"Shardul Amarchand Mangaldas","logo":""},{"name":"Trilegal","logo":""},{"name":"AZB & Partners","logo":""},{"name":"J Sagar Associates","logo":""},{"name":"Luthra & Luthra","logo":""},{"name":"S&R Associates","logo":""},{"name":"Nishith Desai Associates","logo":""},{"name":"Crawford Bayley & Co","logo":""}]',
        6.00, 3.50, 25.00, 1, 850, 'active'
    ],

    // ─── 6. B.Com (Hons) ────────────────────────────────────────────────────
    [
        'crs-bcom-06', 'B.Com (Hons) - Bachelor of Commerce (Honours)', 'bcom-hons', 'UG', 'Commerce', 'cat-com-01', 3,
        'B.Com (Hons) is a 3-year undergraduate degree program that provides advanced knowledge in commerce, accounting, finance, economics, and business law. It is one of the most popular choices for students aspiring to careers in chartered accountancy, company secretaryship, banking, financial services, and business management. The curriculum covers financial accounting, cost accounting, taxation, auditing, business economics, corporate law, and management accounting.',
        '10+2 from a recognized board with Commerce stream (Mathematics/Accountancy/Economics as subjects) with a minimum of 50-60% aggregate marks. Some universities accept students from all streams. Admission is through merit-based cutoffs (Delhi University, Mumbai University) or entrance exams (IPU CET, BHU UET, Christ University).',
        'B.Com (Hons) graduates can pursue careers as Chartered Accountants (CA), Company Secretaries (CS), Cost and Management Accountants (CMA), Financial Analysts, Bankers, Tax Consultants, and Auditors. They can also join government services through SSC, UPSC, or banking exams. Many graduates pursue MBA or M.Com for advanced career opportunities. Average starting salary ranges from ₹3-6 LPA, with CA/CFA qualified professionals earning ₹8-25+ LPA.',
        '[{"name":"Deloitte","logo":""},{"name":"PwC India","logo":""},{"name":"EY India","logo":""},{"name":"KPMG India","logo":""},{"name":"ICICI Bank","logo":""},{"name":"HDFC Bank","logo":""},{"name":"SBI","logo":""},{"name":"Axis Bank","logo":""},{"name":"Grant Thornton","logo":""},{"name":"BDO India","logo":""}]',
        5.00, 2.50, 20.00, 1, 3000, 'active'
    ],

    // ─── 7. BBA ──────────────────────────────────────────────────────────────
    [
        'crs-bba-07', 'BBA - Bachelor of Business Administration', 'bba', 'UG', 'Management', 'cat-mgt-02', 3,
        'BBA (Bachelor of Business Administration) is a 3-year undergraduate program that provides foundational knowledge in business management, entrepreneurship, marketing, finance, and human resource management. It is designed to develop managerial and leadership skills in students. The curriculum covers principles of management, business communication, financial accounting, marketing management, organizational behavior, business law, and strategic management. Many BBA programs also include internships, industry projects, and personality development modules.',
        '10+2 from any recognized board with a minimum of 50% aggregate marks. Some colleges require English as a compulsory subject. Admission is through entrance exams like IPMAT (IIM Indore/Ranchi), DU JAT, AIMA UGAT, NPAT, or merit-based selection. Top colleges include IIM Indore (IPM), NMIMS, Symbiosis, Christ University, and Amity.',
        'BBA graduates can start careers in marketing, sales, human resources, operations, and business development. They can pursue MBA for advanced career growth or join family businesses. Career roles include Marketing Executive, Sales Executive, HR Executive, Business Development Executive, Operations Analyst, and Management Trainee. Many BBA graduates also prepare for competitive exams like CAT, XAT for MBA admissions. Average starting salary ranges from ₹3-6 LPA.',
        '[{"name":"Amazon","logo":""},{"name":"Deloitte","logo":""},{"name":"KPMG","logo":""},{"name":"EY","logo":""},{"name":"Wipro","logo":""},{"name":"HCL Technologies","logo":""},{"name":"Aditya Birla Group","logo":""},{"name":"ITC Limited","logo":""},{"name":"Nestle India","logo":""},{"name":"Asian Paints","logo":""}]',
        4.50, 2.50, 18.00, 1, 2200, 'active'
    ],

    // ─── 8. B.Sc Nursing ─────────────────────────────────────────────────────
    [
        'crs-nursing-08', 'B.Sc Nursing - Bachelor of Science in Nursing', 'bsc-nursing', 'UG', 'Nursing', 'cat-med-03', 4,
        'B.Sc Nursing is a 4-year undergraduate program that trains students to become professional nurses capable of providing healthcare services in hospitals, clinics, community health centers, and other healthcare settings. The curriculum covers anatomy, physiology, microbiology, pharmacology, medical-surgical nursing, community health nursing, pediatric nursing, psychiatric nursing, and obstetric & gynecological nursing. Clinical practice in hospitals is a mandatory component of the program.',
        '10+2 with Physics, Chemistry, Biology, and English with a minimum of 45-50% aggregate marks. Must be at least 17 years old at the time of admission. Admission is through state-level entrance exams, NEET (some states), or institution-level exams. Female candidates are preferred, though male candidates are also eligible.',
        'Nurses are in high demand globally, with excellent job opportunities in India and abroad. Career options include Staff Nurse, Nursing Officer, ICU Nurse, OT Nurse, Community Health Nurse, School Nurse, and Nursing Educator. Qualified nurses can work in government hospitals, private hospitals, international organizations (WHO, UNICEF), cruise ships, and old-age homes. Post-graduation (M.Sc Nursing) opens doors to specialization and teaching roles. Average starting salary ranges from ₹3-5 LPA in India, with significantly higher salaries abroad.',
        '[{"name":"Apollo Hospitals","logo":""},{"name":"Fortis Healthcare","logo":""},{"name":"Max Healthcare","logo":""},{"name":"AIIMS","logo":""},{"name":"Manipal Hospitals","logo":""},{"name":"Narayana Health","logo":""},{"name":"Christian Medical College","logo":""},{"name":"Lilavati Hospital","logo":""},{"name":"Kokilaben Hospital","logo":""},{"name":"Global Hospital","logo":""}]',
        4.00, 2.50, 12.00, 0, 1500, 'active'
    ],

    // ─── 9. BA LLB ───────────────────────────────────────────────────────────
    [
        'crs-ba-llb-09', 'BA LLB - Bachelor of Arts and Bachelor of Legislative Law', 'ba-llb', 'Integrated', 'Law', 'cat-law-01', 5,
        'BA LLB is a 5-year integrated dual-degree program that combines liberal arts education with legal studies. Students earn both a Bachelor of Arts degree and a Bachelor of Legislative Law degree upon completion. The program provides a strong foundation in humanities subjects (political science, history, sociology, economics) alongside comprehensive legal education. It covers constitutional law, criminal law, civil law, corporate law, international law, and environmental law, along with practical training through moot courts and legal internships.',
        '10+2 from any recognized stream with a minimum of 45-50% aggregate marks (40-45% for reserved categories). Admission is through national-level CLAT (Common Law Admission Test) for NLUs, AILET for NLU Delhi, LSAT India, or state-level law entrance exams like MH CET Law, AP LAWCET, TS LAWCET. Some private universities conduct their own entrance exams.',
        'BA LLB graduates have excellent career opportunities in the legal profession. They can practice as Advocates, join corporate legal departments, work in law firms, or appear for judicial services exams. Other options include legal journalism, legal process outsourcing, arbitration, and mediation. Many graduates pursue LLM for specialization or appear for UPSC/State PSC exams. Average starting salary ranges from ₹4-8 LPA, with experienced lawyers at top firms earning ₹15-50+ LPA.',
        '[{"name":"Cyril Amarchand Mangaldas","logo":""},{"name":"Khaitan & Co","logo":""},{"name":"Trilegal","logo":""},{"name":"AZB & Partners","logo":""},{"name":"J Sagar Associates","logo":""},{"name":"Luthra & Luthra","logo":""},{"name":"Nishith Desai Associates","logo":""},{"name":"Shardul Amarchand Mangaldas","logo":""},{"name":"S&R Associates","logo":""},{"name":"Crawford Bayley & Co","logo":""}]',
        6.50, 3.50, 30.00, 1, 700, 'active'
    ],

    // ─── 10. B.Des ───────────────────────────────────────────────────────────
    [
        'crs-bdes-10', 'B.Des - Bachelor of Design', 'bdes', 'UG', 'Design', 'cat-des-01', 4,
        'B.Des (Bachelor of Design) is a 4-year undergraduate program that develops creative and analytical skills in design thinking, visual communication, product design, UI/UX design, fashion design, interior design, and industrial design. The curriculum covers design fundamentals, sketching, color theory, typography, user research, prototyping, materials science, and design management. Students work on live projects, portfolio development, and industry internships throughout the program.',
        '10+2 from any recognized board with a minimum of 50% aggregate marks. Admission is through national-level entrance exams like NIFT Entrance Exam, UCEED (IIT Bombay), NID DAT, AIEED, or state-level design exams. Portfolio review and design aptitude tests are also part of the selection process at many institutions.',
        'Design professionals are in high demand across industries including technology, fashion, automotive, architecture, advertising, and media. Career options include UI/UX Designer, Product Designer, Graphic Designer, Fashion Designer, Interior Designer, Industrial Designer, and Design Manager. Top employers include design studios, IT companies, fashion brands, automotive companies, and e-commerce platforms. Average starting salary ranges from ₹4-8 LPA, with experienced designers earning ₹15-35+ LPA.',
        '[{"name":"IDEO","logo":""},{"name":"Frog Design","logo":""},{"name":"Tata Group","logo":""},{"name":"Infosys","logo":""},{"name":"Wipro","logo":""},{"name":"Godrej","logo":""},{"name":"Flipkart","logo":""},{"name":"Amazon India","logo":""},{"name":"Ola","logo":""},{"name":"Zomato","logo":""}]',
        7.00, 3.50, 30.00, 1, 500, 'active'
    ],
];

foreach ($courses as $c) {
    try { $s->execute($c); $ok++; } catch(Exception $e) { $err++; echo "ERR course [{$c[1]}]: " . $e->getMessage() . "\n"; }
}

// ═══════════════════════════════════════════════════════════════════════════════
// STEP 3: INSERT SPECIALIZATIONS
// ═══════════════════════════════════════════════════════════════════════════════
echo "Inserting specializations...\n";

$s = $pdo->prepare("INSERT INTO course_specializations (id,parent_course_id,specialization_name,specialization_slug,description,sort_order,is_popular) VALUES (?,?,?,?,?,?,?)");

$specs = [
    // B.Tech
    ['sp-cse-01','crs-btech-01','Computer Science & Engineering','cse','Focuses on computer programming, algorithms, data structures, artificial intelligence, machine learning, and software development.',1,1],
    ['sp-me-02','crs-btech-01','Mechanical Engineering','mechanical','Involves design, manufacturing, and operation of machinery, thermal systems, robotics, and automotive engineering.',2,1],
    ['sp-ece-03','crs-btech-01','Electronics & Communication','ece','Deals with electronic devices, circuits, VLSI design, embedded systems, and communication networks.',3,1],
    ['sp-ce-04','crs-btech-01','Civil Engineering','civil','Covers construction, structural design, transportation engineering, water resources, and urban planning.',4,0],
    ['sp-ee-05','crs-btech-01','Electrical Engineering','electrical','Deals with power systems, electrical machines, control systems, renewable energy, and power electronics.',5,0],
    ['sp-ai-06','crs-btech-01','AI & Data Science','ai-data-science','Specializes in artificial intelligence, machine learning, deep learning, data analytics, and big data technologies.',6,1],

    // MBA
    ['sp-fin-01','crs-mba-02','Finance','finance','Focuses on financial management, investment analysis, corporate finance, banking, and portfolio management.',1,1],
    ['sp-mkt-02','crs-mba-02','Marketing','marketing','Covers sales management, digital marketing, brand management, consumer behavior, and advertising strategy.',2,1],
    ['sp-hr-03','crs-mba-02','Human Resource Management','hr','Deals with recruitment, talent management, employee relations, compensation, and organizational development.',3,1],
    ['sp-ops-04','crs-mba-02','Operations Management','operations','Focuses on supply chain management, production planning, quality control, and logistics optimization.',4,0],
    ['sp-it-05','crs-mba-02','Information Technology','it-mba','Combines business management with IT strategy, digital transformation, and technology-driven innovation.',5,0],
    ['sp-ba-06','crs-mba-02','Business Analytics','ba','Specializes in data-driven decision making, predictive modeling, business intelligence, and statistical analysis.',6,1],

    // MBBS
    ['sp-gen-01','crs-mbbs-03','General Medicine','general-medicine','Focuses on diagnosis and treatment of adult diseases, internal medicine, and patient care.',1,1],
    ['sp-surg-02','crs-mbbs-03','General Surgery','general-surgery','Covers surgical procedures, operative techniques, pre-operative and post-operative patient management.',2,1],
    ['sp-ped-03','crs-mbbs-03','Pediatrics','pediatrics','Deals with medical care of infants, children, and adolescents including preventive health and developmental issues.',3,1],
    ['sp-gyn-04','crs-mbbs-03','Obstetrics & Gynecology','obs-gyn','Focuses on pregnancy, childbirth, reproductive health, and female reproductive system disorders.',4,0],

    // BCA
    ['sp-web-01','crs-bca-04','Web Development','web-development','Covers frontend (HTML, CSS, JavaScript, React) and backend (Node.js, PHP, Python) web technologies.',1,1],
    ['sp-cyber-02','crs-bca-04','Cyber Security','cyber-security','Focuses on protecting computer systems, networks, and data from cyber threats and vulnerabilities.',2,1],
    ['sp-data-03','crs-bca-04','Data Science','data-analytics','Involves data analysis, visualization, machine learning, and statistical modeling using Python and R.',3,1],
    ['sp-cloud-04','crs-bca-04','Cloud Computing','cloud-computing','Study of AWS, Azure, GCP cloud services, deployment, and cloud-native application development.',4,0],

    // LLB
    ['sp-corp-01','crs-llb-05','Corporate Law','corporate-law','Deals with laws governing businesses, mergers & acquisitions, corporate governance, and compliance.',1,1],
    ['sp-crim-02','crs-llb-05','Criminal Law','criminal-law','Involves prosecution and defense of criminal cases, criminology, and criminal justice system.',2,1],
    ['sp-const-03','crs-llb-05','Constitutional Law','constitutional-law','Focuses on interpretation of the Constitution, fundamental rights, and judicial review.',3,0],
    ['sp-intl-04','crs-llb-05','International Law','international-law','Covers treaties, international organizations, human rights law, and cross-border legal issues.',4,0],

    // B.Com (Hons)
    ['sp-acc-01','crs-bcom-06','Accounting & Auditing','accounting-auditing','Focuses on financial accounting, cost accounting, auditing procedures, and tax compliance.',1,1],
    ['sp-bfin-02','crs-bcom-06','Banking & Finance','banking-finance','Covers banking operations, financial markets, investment analysis, and portfolio management.',2,1],
    ['sp-tax-03','crs-bcom-06','Taxation','taxation','Deals with income tax, GST, tax planning, and tax compliance for individuals and businesses.',3,0],

    // BBA
    ['sp-bm-01','crs-bba-07','Business Management','business-management','Covers general management principles, entrepreneurship, strategic planning, and organizational leadership.',1,1],
    ['sp-dm-02','crs-bba-07','Digital Marketing','digital-marketing','Focuses on SEO, SEM, social media marketing, content marketing, and online advertising.',2,1],
    ['sp-hrm-03','crs-bba-07','Human Resources','hr-bba','Deals with recruitment, training, performance management, and employee engagement.',3,0],

    // B.Sc Nursing
    ['sp-msn-01','crs-nursing-08','Medical-Surgical Nursing','med-surg','Focuses on care of adult patients with acute and chronic medical conditions.',1,1],
    ['sp-chn-02','crs-nursing-08','Community Health Nursing','community-health','Covers public health nursing, community assessment, and health promotion programs.',2,0],
    ['sp-pdn-03','crs-nursing-08','Pediatric Nursing','pediatric-nursing','Deals with healthcare of infants, children, and adolescents.',3,1],

    // BA LLB
    ['sp-crl-01','crs-ba-llb-09','Criminal Law','criminal-law-ba','In-depth study of criminal jurisprudence, IPC, CrPC, and criminal litigation.',1,1],
    ['sp-cnl-02','crs-ba-llb-09','Constitutional & Administrative Law','const-admin','Focuses on constitutional framework, fundamental rights, and administrative governance.',2,1],
    ['sp-cml-03','crs-ba-llb-09','Corporate & Commercial Law','corp-comm','Covers business regulations, company law, competition law, and commercial transactions.',3,0],

    // B.Des
    ['sp-uxd-01','crs-bdes-10','UI/UX Design','ui-ux','Focuses on user interface design, user experience research, prototyping, and usability testing.',1,1],
    ['sp-gd-02','crs-bdes-10','Graphic Design','graphic-design','Covers visual communication, typography, branding, and print/digital media design.',2,1],
    ['sp-pd-03','crs-bdes-10','Product Design','product-design','Deals with industrial product design, ergonomics, materials, and manufacturing processes.',3,0],
    ['sp-id-04','crs-bdes-10','Interior Design','interior-design','Focuses on space planning, interior architecture, furniture design, and sustainable interiors.',4,0],
];

foreach ($specs as $s_data) {
    try { $s->execute($s_data); $ok++; } catch(Exception $e) { $err++; echo "ERR spec [{$s_data[2]}]: " . $e->getMessage() . "\n"; }
}

// ═══════════════════════════════════════════════════════════════════════════════
// STEP 4: INSERT CAREER PATHS
// ═══════════════════════════════════════════════════════════════════════════════
echo "Inserting career paths...\n";

$s = $pdo->prepare("INSERT INTO course_career_paths (id,course_id,job_role,avg_salary_lpa,top_companies,growth_outlook,skills_required,fresher_salary_lpa,experienced_salary_lpa) VALUES (?,?,?,?,?,?,?,?,?)");

$careers = [
    // B.Tech careers
    ['cp-bt-01','crs-btech-01','Software Engineer',8.50,'["Google","Microsoft","Amazon","TCS","Infosys"]','high','["Java","Python","Data Structures","Algorithms","System Design"]',5.50,18.00],
    ['cp-bt-02','crs-btech-01','Data Scientist',12.00,'["Fractal Analytics","MuSigma","Meta","IBM","Netflix"]','high','["Python","Machine Learning","SQL","Statistics","Deep Learning"]',7.00,28.00],
    ['cp-bt-03','crs-btech-01','Full Stack Developer',9.00,'["Flipkart","Razorpay","Zomato","Swiggy","PhonePe"]','high','["JavaScript","React","Node.js","MongoDB","AWS"]',5.00,20.00],
    ['cp-bt-04','crs-btech-01','Mechanical Engineer',5.50,'["Tata Motors","L&T","Maruti Suzuki","BOEING","HAL"]','medium','["AutoCAD","SolidWorks","Thermodynamics","Manufacturing"]',3.50,12.00],
    ['cp-bt-05','crs-btech-01','Civil Engineer',5.00,'["L&T","Tata Projects","DLF","NCC Ltd"]','medium','["AutoCAD","Staad Pro","Construction Management"]',3.00,10.00],

    // MBA careers
    ['cp-mb-01','crs-mba-02','Management Consultant',18.00,'["McKinsey","BCG","Bain","Deloitte","Accenture"]','high','["Strategy","Problem Solving","Excel","PowerPoint","Analytics"]',12.00,45.00],
    ['cp-mb-02','crs-mba-02','Marketing Manager',12.00,'["HUL","P&G","Amazon","ITC","Nestle"]','high','["Digital Marketing","Brand Management","Market Research","Communication"]',8.00,25.00],
    ['cp-mb-03','crs-mba-02','Investment Banker',15.00,'["Goldman Sachs","JP Morgan","Morgan Stanley","ICICI Securities"]','high','["Financial Modeling","Valuation","Excel","Negotiation"]',10.00,40.00],
    ['cp-mb-04','crs-mba-02','Product Manager',14.00,'["Google","Microsoft","Amazon","Flipkart","Zomato"]','high','["Product Strategy","Analytics","Agile","UX Understanding"]',9.00,35.00],
    ['cp-mb-05','crs-mba-02','HR Manager',8.00,'["TCS","Infosys","Wipro","HDFC Bank"]','medium','["Recruitment","Employee Relations","Performance Management"]',5.00,18.00],

    // MBBS careers
    ['cp-mb-06','crs-mbbs-03','General Physician',9.00,'["Apollo Hospitals","Fortis","Govt Hospitals","Max Healthcare"]','medium','["Clinical Skills","Diagnostics","Patient Care","Communication"]',6.00,18.00],
    ['cp-mb-07','crs-mbbs-03','Surgeon',15.00,'["AIIMS","Apollo","Fortis","Narayana Health"]','high','["Surgery","Operative Skills","Anatomy","Emergency Medicine"]',8.00,35.00],
    ['cp-mb-08','crs-mbbs-03','Pediatrician',10.00,'["AIIMS","CMC Vellore","Manipal Hospital"]','medium','["Child Care","Vaccination","Developmental Assessment"]',6.50,22.00],
    ['cp-mb-09','crs-mbbs-03','Medical Researcher',8.00,'["ICMR","AIIMS Research","WHO","Pharma Companies"]','high','["Research Methodology","Biostatistics","Publishing"]',5.00,20.00],

    // BCA careers
    ['cp-bca-01','crs-bca-04','Web Developer',4.50,'["TCS","Wipro","Infosys","Tech Mahindra","Capgemini"]','high','["HTML/CSS","JavaScript","React","PHP","Node.js"]',3.00,10.00],
    ['cp-bca-02','crs-bca-04','Software Developer',5.50,'["Infosys","IBM","Accenture","HCL","Cognizant"]','high','["Java","Python","SQL","Git","Problem Solving"]',3.50,14.00],
    ['cp-bca-03','crs-bca-04','System Analyst',6.00,'["IBM","Wipro","TCS","Tech Mahindra"]','medium','["System Architecture","Networking","Database Management"]',4.00,15.00],
    ['cp-bca-04','crs-bca-04','Database Administrator',5.00,'["Oracle","TCS","Infosys","Cognizant"]','medium','["MySQL","Oracle","SQL Server","Backup & Recovery"]',3.50,12.00],

    // LLB careers
    ['cp-llb-01','crs-llb-05','Corporate Lawyer',8.00,'["Khaitan & Co","Trilegal","AZB & Partners","Cyril Amarchand"]','high','["Legal Drafting","Negotiation","Corporate Governance","M&A"]',5.00,25.00],
    ['cp-llb-02','crs-llb-05','Litigation Lawyer',6.50,'["Independent Practice","Crawford Bayley","Luthra & Luthra"]','medium','["Court Practice","Legal Research","Advocacy","Drafting"]',3.50,20.00],
    ['cp-llb-03','crs-llb-05','Legal Advisor',7.00,'["HDFC Bank","ICICI Bank","Reliance","Tata Group"]','high','["Contract Law","Compliance","Risk Management","Advisory"]',4.50,18.00],
    ['cp-llb-04','crs-llb-05','Judicial Officer',10.00,'["District Courts","High Courts","Supreme Court"]','medium','["Judiciary Exam Prep","Legal Knowledge","Integrity"]',7.00,25.00],

    // B.Com (Hons) careers
    ['cp-bc-01','crs-bcom-06','Chartered Accountant',12.00,'["Deloitte","PwC","EY","KPMG","BDO"]','high','["Accounting","Tax","Audit","Financial Reporting"]',7.00,30.00],
    ['cp-bc-02','crs-bcom-06','Financial Analyst',6.50,'["HDFC Bank","ICICI Bank","Axis Bank","Kotak"]','high','["Financial Analysis","Excel","Valuation","Forecasting"]',4.00,15.00],
    ['cp-bc-03','crs-bcom-06','Tax Consultant',7.00,'["Deloitte","EY","PwC","KPMG","Grant Thornton"]','high','["Income Tax","GST","Tax Planning","Compliance"]',4.50,18.00],

    // BBA careers
    ['cp-bba-01','crs-bba-07','Marketing Executive',4.50,'["HUL","Nestle","ITC","Asian Paints","Britannia"]','high','["Sales","Digital Marketing","Market Research","Communication"]',3.00,12.00],
    ['cp-bba-02','crs-bba-07','Business Development Executive',5.00,'["Amazon","Flipkart","BYJU\'S","Swiggy","Zomato"]','high','["Sales","Negotiation","CRM","Client Management"]',3.50,14.00],
    ['cp-bba-03','crs-bba-07','HR Executive',4.00,'["TCS","Wipro","Infosys","HCL"]','medium','["Recruitment","Payroll","Employee Relations"]',2.80,10.00],

    // B.Sc Nursing careers
    ['cp-ns-01','crs-nursing-08','Staff Nurse',4.00,'["Apollo","Fortis","AIIMS","CMC Vellore","Max Healthcare"]','high','["Patient Care","Medication Administration","Documentation"]',3.00,8.00],
    ['cp-ns-02','crs-nursing-08','ICU Nurse',5.00,'["Apollo","Fortis","Manipal","Narayana Health"]','high','["Critical Care","Ventilator Management","Monitoring"]',3.50,10.00],
    ['cp-ns-03','crs-nursing-08','Community Health Nurse',3.50,'["Govt Health Dept","WHO","UNICEF"]','medium','["Public Health","Immunization","Health Education"]',2.50,7.00],

    // BA LLB careers
    ['cp-ballb-01','crs-ba-llb-09','Litigation Advocate',7.00,'["Cyril Amarchand","Khaitan & Co","Trilegal","Independent Practice"]','high','["Court Practice","Legal Drafting","Advocacy","Research"]',4.00,22.00],
    ['cp-ballb-02','crs-ba-llb-09','Legal Counsel',10.00,'["Google","Microsoft","Amazon","Tata Group","Reliance"]','high','["Corporate Law","Compliance","Contract Drafting","Risk Assessment"]',6.00,28.00],
    ['cp-ballb-03','crs-ba-llb-09','Public Prosecutor',8.00,'["State Government","Central Government"]','medium','["Criminal Law","Trial Advocacy","Legal Knowledge"]',5.50,18.00],

    // B.Des careers
    ['cp-bd-01','crs-bdes-10','UI/UX Designer',8.00,'["Google","Microsoft","Flipkart","Zomato","PhonePe"]','high','["Figma","Sketch","Prototyping","User Research","Wireframing"]',5.00,20.00],
    ['cp-bd-02','crs-bdes-10','Graphic Designer',5.00,'["Ogilvy","Wieden+Kennedy","Pentagram","DDB Mudra"]','medium','["Photoshop","Illustrator","InDesign","Typography"]',3.00,12.00],
    ['cp-bd-03','crs-bdes-10','Product Designer',9.00,'["IDEO","Frog Design","Godrej","Tata","Ola"]','high','["Industrial Design","3D Modeling","CAD","Materials Science"]',5.50,22.00],
    ['cp-bd-04','crs-bdes-10','Interior Designer',6.00,'["Livspace","HomeLane","Godrej Interio","Asian Paints"]','medium','["AutoCAD","3ds Max","Space Planning","Materials"]',3.50,15.00],
];

foreach ($careers as $c_data) {
    try { $s->execute($c_data); $ok++; } catch(Exception $e) { $err++; echo "ERR career [{$c_data[2]}]: " . $e->getMessage() . "\n"; }
}

echo "\n✅ DONE: $ok OK, $err errors\n";
echo "Courses: 10 | Specializations: " . count($specs) . " | Career Paths: " . count($careers) . "\n";

// Re-enable FK checks
$pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
