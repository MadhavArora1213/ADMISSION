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

// ─── 1. COLLEGES ────────────────────────────────────────────────────────────
$sql = "INSERT IGNORE INTO colleges
(id,name,slug,college_type,ownership,status,is_featured,is_verified,featured_order,ranking_nirf,city_id,state_id,established_year,naac_grade,ugc_approved,aicte_approved,nba_approved,total_students,total_faculty,campus_area_acres,overall_rating_avg,total_reviews,publish_status,verification_status,data_quality_score,campus_type)
VALUES
('col-iitb-0001','IIT Bombay','iit-bombay','deemed','central','active',1,1,1,3,384,20,1958,'A++',1,1,1,10000,600,550.0,4.7,520,'published','verified',95,'urban'),
('col-iima-0002','IIM Ahmedabad','iim-ahmedabad','deemed','central','active',1,1,2,1,150,11,1961,'A++',1,0,0,1200,105,102.0,4.8,380,'published','verified',98,'urban'),
('col-nimh-0003','NIMHANS Bangalore','nimhans-bangalore','deemed','central','active',1,1,3,87,266,16,1974,'A+',1,0,0,2500,320,43.5,4.4,210,'published','verified',90,'urban'),
('col-du00-0004','University of Delhi','university-of-delhi','govt','central','active',1,1,4,11,137,9,1922,'A++',1,0,0,300000,9000,600.0,4.3,890,'published','verified',92,'urban'),
('col-anna-0005','Anna University','anna-university','govt','state','active',1,1,5,5,544,30,1978,'A+',1,1,1,25000,1800,180.0,4.5,340,'published','verified',88,'urban')";
run($pdo, $sql, 'colleges');

// ─── 2. CONTACTS ────────────────────────────────────────────────────────────
$contacts = [
    ['cnt-iitb-0001','col-iitb-0001','https://www.iitb.ac.in','info@iitb.ac.in','022-25722545','Powai, Mumbai, Maharashtra','400076',5,28],
    ['cnt-iima-0002','col-iima-0002','https://www.iima.ac.in','pgp@iima.ac.in','079-66328234','Vastrapur, Ahmedabad, Gujarat','380015',8,12],
    ['cnt-nimh-0003','col-nimh-0003','https://nimhans.ac.in','registrar@nimhans.ac.in','080-46110007','Hosur Road, Bengaluru, Karnataka','560029',4,35],
    ['cnt-du00-0004','col-du00-0004','https://www.du.ac.in','webmaster@du.ac.in','011-27666351','University Road, Delhi (NCT)','110007',2,22],
    ['cnt-anna-0005','col-anna-0005','https://www.annauniv.edu','registrar@annauniv.edu','044-22357004','Sardar Patel Road, Guindy, Chennai','600025',6,18],
];
$s = $pdo->prepare("INSERT IGNORE INTO college_contacts (id,college_id,website_url,email,phone,address,pincode,nearest_railway_km,nearest_airport_km) VALUES (?,?,?,?,?,?,?,?,?)");
foreach ($contacts as $c) { try { $s->execute($c); $ok++; } catch(Exception $e){ $err++; echo "ERR contacts: ".$e->getMessage()."\n"; } }

// ─── 3. CONTENT ─────────────────────────────────────────────────────────────
$contents = [
    ['cct-iitb-0001','col-iitb-0001',
     'Indian Institute of Technology Bombay (IIT Bombay) is a public technical and research university located in Powai, Mumbai. Established in 1958 with assistance from UNESCO, it is one of the premier engineering institutions in India. IIT Bombay is known for world-class infrastructure, cutting-edge research, and celebrated alumni who lead global corporations, research labs, and startups. The institute has 17 departments and 13 interdisciplinary programs.',
     '["Ranked #3 in India by NIRF 2024","Top 150 globally by QS World University Rankings 2025","550-acre sprawling campus in Powai Mumbai","10,000+ students across UG PG and PhD programs","600+ distinguished faculty members","700+ active research projects annually","Strong industry-academia collaborations with 500+ companies","98% eligible students placed in campus recruitment"]',
     '["NAAC A++","UGC Approved","NBA Accredited","AICTE Approved","QS Stars 5-Star"]',
     '{"nirf":3,"qs":149,"times":201}',
     '["National Institutional Ranking Framework Award","Best Technical Institution Ministry of Education 2023"]'],

    ['cct-iima-0002','col-iima-0002',
     'Indian Institute of Management Ahmedabad (IIMA) is one of the most prestigious management schools in the world, founded in 1961 in collaboration with Harvard Business School. IIMA offers world-renowned Post Graduate Programme (PGP) and Fellow Programme in Management. It is ranked #1 management school in India and among top 50 globally. The institute is famous for its case-study based pedagogy and outstanding alumni network of 38,000+ professionals worldwide.',
     '["Ranked #1 in India by NIRF Management Category 2024","Top 50 globally Financial Times MBA Rankings","Highest MBA package 109 LPA International","Average domestic package 32 LPA PGP 2024","102-acre beautifully designed heritage campus","105 full-time faculty with global expertise","38,000+ alumni across 6 continents","Case study pedagogy pioneered in India"]',
     '["NAAC A++","AACSB Accredited","EQUIS Accredited","UGC Approved","AMBA Accredited"]',
     '{"nirf":1,"ft_mba":47}',
     '["Best B-School in India Business Today 2024","AACSB Triple Crown Accreditation"]'],

    ['cct-nimh-0003','col-nimh-0003',
     'National Institute of Mental Health and Neurosciences (NIMHANS) in Bengaluru is India\'s premier autonomous institution of national importance for mental health and neurosciences. Established in 1974, NIMHANS is a Deemed University under the Ministry of Health and Family Welfare. It serves as the nodal centre for education, training, research and clinical services in psychiatry, neurology, neurosurgery. NIMHANS operates an 800-bed hospital with over 30 departments.',
     '["Only specialised neuroscience institute with National Importance status in India","800+ bed super speciality hospital with 30+ clinical departments","NAAC A+ accredited Deemed University","One of the lowest fee structures in India","Stipends and fellowships for research scholars","International collaborations with WHO and NIH","Located in heart of Bangalore","Ranked Top 100 Medical Institutes in India by NIRF"]',
     '["NAAC A+","UGC Approved","MCI Approved","WHO Collaborating Centre","Institute of National Importance"]',
     '{"nirf":87,"medical":12}',
     '["WHO Collaborating Centre for Mental Health","National Award for Excellence in Medical Education 2023"]'],

    ['cct-du00-0004','col-du00-0004',
     'The University of Delhi (DU), established in 1922, is a premier central university and one of the largest universities in the world. Located in the national capital with 16 faculties, 86 academic departments, and 91 affiliated colleges, DU offers over 500 programs. Its constituent colleges like SRCC, Miranda House, Lady Shri Ram, and St. Stephens are among the most sought-after in India.',
     '["Ranked #11 in India by NIRF 2024","91 affiliated colleges including SRCC Miranda House LSR","16 faculties and 86 academic departments","300,000+ students one of the largest universities worldwide","NAAC A++ accredited Central University","600-acre multi-campus in North and South Delhi","Alumni include Prime Ministers and Nobel Laureates","2000+ PhD scholars annually"]',
     '["NAAC A++","UGC Approved","ACU Member","AIU Member","Commonwealth Universities Network"]',
     '{"nirf":11,"india_rank":11}',
     '["NAAC A++ Re-accreditation 2022","National Education Award Ministry of Education 2023"]'],

    ['cct-anna-0005','col-anna-0005',
     'Anna University, established in 1978, is a technical university in Chennai, Tamil Nadu. Named after former Chief Minister Dr. C.N. Annadurai, it offers higher education in Engineering, Technology, Architecture and Applied Sciences. Affiliated with over 600 engineering colleges across Tamil Nadu, making it one of the largest affiliating technical universities in India.',
     '["Ranked #5 in India by NIRF University Category 2024","Affiliated with 600+ engineering colleges across Tamil Nadu","NAAC A+ accredited highest grade","25,000+ students in main campus","1800 faculty members with diverse expertise","Strong tie-ups with TCS Infosys Wipro Google Amazon","180-acre main campus in Guindy Chennai","PhD programs in 40+ disciplines"]',
     '["NAAC A+","UGC Approved","AICTE Approved","NBA Accredited","IET Accredited UK"]',
     '{"nirf":5,"technical":5}',
     '["Best Technical University TN Govt 2023","NIRF Top 5 University 2024"]'],
];
$s = $pdo->prepare("INSERT IGNORE INTO college_content (id,college_id,about_text,highlights_json,accreditations_json,rankings_json,awards_json) VALUES (?,?,?,?,?,?,?)");
foreach ($contents as $c) { try { $s->execute($c); $ok++; } catch(Exception $e){ $err++; echo "ERR content: ".$e->getMessage()."\n"; } }

// ─── 4. COURSES ─────────────────────────────────────────────────────────────
$courses = [
    ['crs-iitb-01','col-iitb-0001','B.Tech','UG',4,900000,112500,225000,880,'JEE Advanced + Board 75%',500,0,'["Computer Science","Electrical Engineering","Mechanical Engineering","Civil Engineering","Chemical Engineering","Aerospace Engineering"]'],
    ['crs-iitb-02','col-iitb-0001','M.Tech','PG',2,120000,30000,60000,600,'B.Tech + GATE Score',300,0,'["Data Science & AI","VLSI Design","Structural Engineering","Thermal Engineering"]'],
    ['crs-iitb-03','col-iitb-0001','M.Sc','PG',2,80000,20000,40000,200,'B.Sc + JAM Score',300,0,'["Mathematics","Physics","Chemistry","Statistics"]'],
    ['crs-iitb-04','col-iitb-0001','PhD','PhD',5,50000,12500,25000,500,'M.Tech/M.Sc + GATE/NET',200,0,'["Engineering","Sciences","Management"]'],
    ['crs-iima-01','col-iima-0002','MBA (PGP)','PG',2,2400000,600000,1200000,400,'Bachelor Degree + CAT Score',2000,0,'["Finance","Marketing","Operations","Strategy","Human Resources"]'],
    ['crs-iima-02','col-iima-0002','MBA (PGPX)','PG',1,3200000,800000,3200000,70,'Bachelor + 5yr Work Exp + CAT/GMAT',2000,0,'["General Management","Leadership","Global Strategy"]'],
    ['crs-iima-03','col-iima-0002','FPM (PhD in Management)','PhD',4,600000,150000,600000,30,'PG Degree + CAT/GMAT/GRE',1000,0,'["Finance","Marketing","OB & HRM","Economics","Strategy"]'],
    ['crs-nimh-01','col-nimh-0003','MD Psychiatry','PG',3,84000,14000,28000,10,'MBBS + INI-CET',500,0,'["Clinical Psychiatry","Child Psychiatry","Geriatric Psychiatry"]'],
    ['crs-nimh-02','col-nimh-0003','DM Neurology','PG',3,84000,14000,28000,8,'MD + NEET-SS',500,0,'["Stroke Medicine","Epilepsy","Movement Disorders"]'],
    ['crs-nimh-03','col-nimh-0003','MCh Neurosurgery','PG',3,84000,14000,28000,6,'MS Surgery + NEET-SS',500,0,'["Brain Surgery","Spine Surgery","Pediatric Neurosurgery"]'],
    ['crs-nimh-04','col-nimh-0003','M.Phil Clinical Psychology','PG',2,40000,10000,20000,14,'MA/MSc Psychology + NIMHANS Entrance',300,0,'["Assessment & Diagnosis","CBT","Neuropsychology"]'],
    ['crs-nimh-05','col-nimh-0003','M.Sc Neuroscience','PG',2,40000,10000,20000,15,'BSc/MBBS + NIMHANS Entrance',300,0,'["Cellular Neuroscience","Cognitive Neuroscience"]'],
    ['crs-nimh-06','col-nimh-0003','PhD Neurosciences','PhD',5,120000,30000,24000,20,'PG Degree + UGC-NET',200,0,'["Molecular Neurobiology","Neuroimaging","Clinical Research"]'],
    ['crs-du00-01','col-du00-0004','B.A. (Hons)','UG',3,36000,9000,18000,5000,'CUET Score + Board 12th',500,0,'["Economics","Political Science","History","English","Psychology","Sociology"]'],
    ['crs-du00-02','col-du00-0004','B.Com (Hons)','UG',3,36000,9000,18000,2500,'CUET Score + Board 12th Commerce',500,0,'["Accounting & Finance","Business Studies"]'],
    ['crs-du00-03','col-du00-0004','B.Sc (Hons)','UG',3,60000,15000,30000,1800,'CUET Score + Board 12th Science',500,0,'["Physics","Chemistry","Mathematics","Computer Science","Zoology"]'],
    ['crs-du00-04','col-du00-0004','MA','PG',2,30000,7500,15000,2000,'Bachelor + CUET PG Score',300,0,'["Economics","History","Political Science","English"]'],
    ['crs-du00-05','col-du00-0004','MBA','PG',2,300000,75000,150000,300,'Bachelor + CAT/MAT/XAT',500,0,'["Finance","Marketing","HR & OB"]'],
    ['crs-anna-01','col-anna-0005','B.E / B.Tech','UG',4,600000,75000,150000,2400,'TN TNEA Rank / JEE Main',500,1,'["Computer Science","Information Technology","Electronics","Mechanical","Civil","AI & Machine Learning"]'],
    ['crs-anna-02','col-anna-0005','M.E / M.Tech','PG',2,160000,40000,80000,800,'B.E/B.Tech + GATE Score',300,0,'["Applied Electronics","Structural Engineering","Software Engineering"]'],
    ['crs-anna-03','col-anna-0005','MCA','PG',2,120000,30000,60000,120,'BCA/BSc CS + TN PGCET',300,0,'["Software Development","Data Analytics","Cloud Computing"]'],
    ['crs-anna-04','col-anna-0005','PhD','PhD',5,200000,50000,40000,200,'M.E/M.Tech + GATE + Interview',200,0,'["Engineering","Science","Architecture"]'],
];
$s = $pdo->prepare("INSERT IGNORE INTO college_courses (id,college_id,course_name,course_level,duration_years,total_fee,semester_fee,annual_fee,seats_available,eligibility_criteria,application_fee,emi_available,specializations) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)");
foreach ($courses as $c) { try { $s->execute($c); $ok++; } catch(Exception $e){ $err++; echo "ERR course: ".$e->getMessage()."\n"; } }

// ─── 5. PLACEMENTS ──────────────────────────────────────────────────────────
$placements = [
    ['plc-iitb-2024','col-iitb-0001',2024,21.82,99.00,18.50,94.5,1320,145,'["Google","Microsoft","Goldman Sachs","McKinsey & Company","Amazon","Apple","Meta","Qualcomm","Samsung R&D","Boston Consulting Group","JP Morgan","Bain & Company"]'],
    ['plc-iitb-2023','col-iitb-0001',2023,20.34,85.00,17.20,93.0,1280,132,'["Google","Microsoft","Morgan Stanley","Flipkart","Intel","Nvidia","Amazon","Deutsche Bank"]'],
    ['plc-iima-2024','col-iima-0002',2024,32.00,99.00,28.50,100.0,400,78,'["McKinsey & Company","BCG","Bain & Company","Goldman Sachs","JP Morgan","Microsoft","Google","Amazon","HUL","Nestle","Accenture Strategy","Deloitte"]'],
    ['plc-iima-2023','col-iima-0002',2023,29.00,95.00,26.40,100.0,390,70,'["McKinsey","BCG","Amazon","Microsoft","RIL","HUL","P&G"]'],
    ['plc-nimh-2024','col-nimh-0003',2024,18.00,45.00,16.00,92.0,180,8,'["AIIMS","PGI Chandigarh","Apollo Hospitals","Fortis Healthcare","Manipal Hospitals","WHO","ICMR"]'],
    ['plc-du00-2024','col-du00-0004',2024,8.50,42.00,7.20,78.0,12400,0,'["Deloitte","EY","KPMG","PwC","Accenture","TCS","Wipro","HCL","HDFC Bank","Goldman Sachs"]'],
    ['plc-anna-2024','col-anna-0005',2024,7.80,62.00,6.40,89.0,3200,12,'["TCS","Infosys","Wipro","HCL Technologies","Zoho","Cognizant","Tech Mahindra","Ford India","Samsung","Intel","Qualcomm"]'],
    ['plc-anna-2023','col-anna-0005',2023,7.20,55.00,5.90,87.0,3100,10,'["TCS","Infosys","CTS","HCL","Zoho","PayPal","Dell","IBM"]'],
];
$s = $pdo->prepare("INSERT IGNORE INTO college_placements (id,college_id,placement_year,avg_package_lpa,highest_package_lpa,median_package_lpa,placement_percentage,students_placed,international_placements,top_recruiters) VALUES (?,?,?,?,?,?,?,?,?,?)");
foreach ($placements as $p) { try { $s->execute($p); $ok++; } catch(Exception $e){ $err++; echo "ERR placement: ".$e->getMessage()."\n"; } }

// ─── 6. RANKINGS ────────────────────────────────────────────────────────────
$rankings = [
    [101,'NIRF',2024,'Engineering','col-iitb-0001',3,82.55,3],
    [102,'QS World University Rankings',2025,'Overall','col-iitb-0001',149,49.8,172],
    [103,'Times Higher Education',2024,'Engineering','col-iitb-0001',201,38.2,220],
    [104,'India Today',2024,'Engineering','col-iitb-0001',2,null,2],
    [201,'NIRF',2024,'Management','col-iima-0002',1,83.12,1],
    [202,'Financial Times MBA',2024,'Global MBA','col-iima-0002',47,null,52],
    [203,'QS Global MBA Rankings',2025,'MBA','col-iima-0002',61,null,65],
    [301,'NIRF',2024,'Medical','col-nimh-0003',87,52.30,90],
    [401,'NIRF',2024,'University','col-du00-0004',11,57.42,11],
    [402,'QS World University Rankings',2025,'Overall','col-du00-0004',521,28.4,551],
    [403,'India Today',2024,'University','col-du00-0004',3,null,3],
    [501,'NIRF',2024,'University','col-anna-0005',5,62.18,6],
    [502,'NIRF',2024,'Engineering','col-anna-0005',14,55.10,16],
];
$s = $pdo->prepare("INSERT IGNORE INTO rankings (id,ranking_body,ranking_year,category,college_id,rank_position,score,previous_year_rank) VALUES (?,?,?,?,?,?,?,?)");
foreach ($rankings as $r) { try { $s->execute($r); $ok++; } catch(Exception $e){ $err++; echo "ERR rank: ".$e->getMessage()."\n"; } }

// ─── 7. MEDIA ───────────────────────────────────────────────────────────────
$media = [
    ['med-iitb-cov','col-iitb-0001','https://upload.wikimedia.org/wikipedia/en/thumb/1/1d/Indian_Institute_of_Technology_Bombay_Logo.svg/200px-Indian_Institute_of_Technology_Bombay_Logo.svg.png','https://images.unsplash.com/photo-1562774053-701939374585?w=1200&q=80',null,'IIT Bombay Main Building',0,null],
    ['med-iitb-g1','col-iitb-0001',null,null,'https://images.unsplash.com/photo-1541339907198-e08756dedf3f?w=600&q=80','IIT Bombay Campus Aerial View',1,'campus'],
    ['med-iitb-g2','col-iitb-0001',null,null,'https://images.unsplash.com/photo-1498243691581-b145c3f54a5a?w=600&q=80','Central Library Complex',2,'campus'],
    ['med-iitb-g3','col-iitb-0001',null,null,'https://images.unsplash.com/photo-1559526324-4b87b5e36e44?w=600&q=80','Computer Science Research Lab',3,'lab'],
    ['med-iima-cov','col-iima-0002','https://upload.wikimedia.org/wikipedia/en/f/fb/IIM_Ahmedabad_Logo.svg','https://images.unsplash.com/photo-1523050854058-8df90110c9f1?w=1200&q=80',null,'IIM Ahmedabad Heritage Campus',0,null],
    ['med-iima-g1','col-iima-0002',null,null,'https://images.unsplash.com/photo-1544717305-2782549b5136?w=600&q=80','Louis Kahn Campus Architecture',1,'campus'],
    ['med-iima-g2','col-iima-0002',null,null,'https://images.unsplash.com/photo-1606761568499-6d2451b23c66?w=600&q=80','Case Study Classroom',2,'classroom'],
    ['med-nimh-cov','col-nimh-0003','https://nimhans.ac.in/wp-content/uploads/2019/11/nimhans_logo.png','https://images.unsplash.com/photo-1587351021759-3e566b6af7cc?w=1200&q=80',null,'NIMHANS Hospital Complex',0,null],
    ['med-nimh-g1','col-nimh-0003',null,null,'https://images.unsplash.com/photo-1519494026892-80bbd2d6fd0d?w=600&q=80','NIMHANS Main Hospital Building',1,'campus'],
    ['med-nimh-g2','col-nimh-0003',null,null,'https://images.unsplash.com/photo-1582719471384-894fbb16e074?w=600&q=80','Neuroscience Research Laboratory',2,'lab'],
    ['med-du00-cov','col-du00-0004','https://upload.wikimedia.org/wikipedia/en/thumb/7/70/University_of_Delhi.svg/200px-University_of_Delhi.svg.png','https://images.unsplash.com/photo-1607237138185-eedd9c632b0b?w=1200&q=80',null,'Delhi University North Campus',0,null],
    ['med-du00-g1','col-du00-0004',null,null,'https://images.unsplash.com/photo-1503676260728-1c00da094a0b?w=600&q=80','DU Heritage Buildings',1,'campus'],
    ['med-du00-g2','col-du00-0004',null,null,'https://images.unsplash.com/photo-1541339907198-e08756dedf3f?w=600&q=80','Central Library DU',2,'campus'],
    ['med-anna-cov','col-anna-0005','https://upload.wikimedia.org/wikipedia/en/thumb/e/ef/Anna_University_Logo.svg/200px-Anna_University_Logo.svg.png','https://images.unsplash.com/photo-1513077202514-c511b41bd4c7?w=1200&q=80',null,'Anna University Guindy Campus',0,null],
    ['med-anna-g1','col-anna-0005',null,null,'https://images.unsplash.com/photo-1581092795360-fd1ca04f0952?w=600&q=80','Engineering Research Lab',1,'lab'],
    ['med-anna-g2','col-anna-0005',null,null,'https://images.unsplash.com/photo-1562774053-701939374585?w=600&q=80','Anna University Main Campus',2,'campus'],
];
$s = $pdo->prepare("INSERT IGNORE INTO college_media (id,college_id,logo_url,cover_image_url,image_url,caption,sort_order,image_type) VALUES (?,?,?,?,?,?,?,?)");
foreach ($media as $m) { try { $s->execute($m); $ok++; } catch(Exception $e){ $err++; echo "ERR media: ".$e->getMessage()."\n"; } }

// ─── 8. INFRASTRUCTURE ──────────────────────────────────────────────────────
$infra = [
    ['inf-iitb-0001','col-iitb-0001',1,500000,'["Cricket","Football","Basketball","Swimming Pool","Tennis","Badminton","Athletics Track"]','["Advanced Computing Lab","Nanotechnology Lab","Robotics Lab","AI & ML Research Lab","Semiconductor Lab","Chemical Engineering Lab"]',1,3000,1,1,1000,1,1,1,1],
    ['inf-iima-0002','col-iima-0002',1,200000,'["Cricket","Tennis","Squash","Basketball","Swimming Pool","Yoga Center","Gymnasium"]','["Computer Lab","Bloomberg Terminal Lab","Behavioral Lab","Strategy Simulation Lab","Analytics Lab"]',1,1200,1,1,1000,1,1,1,1],
    ['inf-nimh-0003','col-nimh-0003',1,150000,'["Cricket Ground","Basketball Court","Badminton Court","Yoga Center","Gymnasium"]','["Neuroscience Research Lab","Brain Imaging MRI Lab","Electrophysiology Lab","Drug Research Lab","Genetic Testing Lab"]',1,800,1,1,500,1,1,0,1],
    ['inf-du00-0004','col-du00-0004',1,1000000,'["Cricket","Football","Hockey","Athletics Track","Tennis","Basketball","Swimming Pool","Gymnasium"]','["Physics Lab","Chemistry Lab","Computer Lab","Bio-Informatics Lab","Language Lab","Social Research Lab"]',1,5000,1,1,100,1,1,0,0],
    ['inf-anna-0005','col-anna-0005',1,300000,'["Cricket","Football","Volleyball","Basketball","Athletics","Swimming Pool","Tennis"]','["Advanced Computing Lab","VLSI Lab","Robotics Lab","Automotive Engineering Lab","Structural Testing Lab","Environmental Lab"]',1,2000,1,1,500,1,1,1,1],
];
$s = $pdo->prepare("INSERT IGNORE INTO college_infrastructure (id,college_id,library,library_books_count,sports_facilities,labs,auditorium,auditorium_capacity,cafeteria,wifi,wifi_speed_mbps,medical_facility,transport,ev_charging,solar_power) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
foreach ($infra as $i) { try { $s->execute($i); $ok++; } catch(Exception $e){ $err++; echo "ERR infra: ".$e->getMessage()."\n"; } }

// ─── 9. HOSTELS ─────────────────────────────────────────────────────────────
$hostels = [
    ['hst-iitb-0001','col-iitb-0001',1,'both',6000,85000,1,'both',0,1],
    ['hst-iima-0002','col-iima-0002',1,'both',1500,120000,1,'both',1,1],
    ['hst-nimh-0003','col-nimh-0003',1,'both',800,45000,1,'both',0,1],
    ['hst-du00-0004','col-du00-0004',1,'both',15000,55000,1,'both',0,1],
    ['hst-anna-0005','col-anna-0005',1,'both',5000,70000,1,'both',0,1],
];
$s = $pdo->prepare("INSERT IGNORE INTO college_hostels (id,college_id,hostel_available,hostel_type,hostel_capacity,hostel_fee_annual,mess_available,mess_type,ac_available,laundry_available) VALUES (?,?,?,?,?,?,?,?,?,?)");
foreach ($hostels as $h) { try { $s->execute($h); $ok++; } catch(Exception $e){ $err++; echo "ERR hostel: ".$e->getMessage()."\n"; } }

// ─── 10. ADMISSIONS ─────────────────────────────────────────────────────────
$admissions = [
    ['adm-iitb-0001','col-iitb-0001','Admissions to B.Tech are based on JEE Advanced rank. Candidates must qualify JEE Main first then appear for JEE Advanced. Final allocation via JoSAA counselling. M.Tech admissions through GATE score followed by institute-level interviews.','["JEE Advanced","JEE Main","GATE","JAM","CEED"]','2026-01-01','2026-05-31',1,0,0,50,0,'online','Merit through national entrance exams'],
    ['adm-iima-0002','col-iima-0002','Admissions to PGP (MBA) based on CAT score followed by Written Ability Test and Personal Interview. Final selection considers academics, work experience, diversity and interview performance. PGPX requires GMAT/GRE with 5+ years work experience.','["CAT","GMAT","GRE"]','2025-11-01','2026-04-30',1,0,0,20,0,'online','CAT Score 30% plus Academic Record 20% plus WAT-PI 50%'],
    ['adm-nimh-0003','col-nimh-0003','Admissions through NIMHANS Entrance Examination for MPhil, MSc and PhD. MD/DM/MCh through INI-CET or NEET-SS. Candidates must meet eligibility criteria for each specific program.','["INI-CET","NEET-SS","NIMHANS Entrance Exam","UGC-NET","JRF"]','2026-01-15','2026-06-30',1,0,0,5,0,'online','National entrance exam plus Merit plus Interview'],
    ['adm-du00-0004','col-du00-0004','UG admissions through CUET via centralised DU portal. Seats allotted based on CUET score and merit list. PG admissions use CUET-PG for most departments.','["CUET","CUET-PG","CAT","MAT","XAT","CLAT","JEE Main"]','2026-03-01','2026-07-31',1,0,150,50,1,'online','CUET Score merit list with college-wise cut-offs'],
    ['adm-anna-0005','col-anna-0005','B.E/B.Tech admissions through TNEA based on 12th marks. Out-of-state via JEE Main. M.E/M.Tech through TANCET or GATE.','["TNEA","JEE Main","GATE","TANCET","PGCET"]','2026-04-01','2026-08-31',1,0,300,100,1,'online','TNEA rank for UG plus GATE/TANCET for PG'],
];
$s = $pdo->prepare("INSERT IGNORE INTO college_admissions (id,college_id,admission_process,accepted_exams,admission_start_date,admission_end_date,merit_based,direct_admission,management_quota_seats,nri_quota_seats,lateral_entry_available,application_mode,selection_criteria) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)");
foreach ($admissions as $a) { try { $s->execute($a); $ok++; } catch(Exception $e){ $err++; echo "ERR admission: ".$e->getMessage()."\n"; } }

// ─── 11. FAQs ───────────────────────────────────────────────────────────────
$faqs = [
    ['faq-iitb-01','col-iitb-0001','What JEE Advanced rank is needed for IIT Bombay CSE?','The closing rank for IIT Bombay Computer Science (General) is typically between 60-100. For female candidates around 200-300.','Admissions',1,1],
    ['faq-iitb-02','col-iitb-0001','What is the average placement package at IIT Bombay?','Average CTC for 2024 batch was 21.82 LPA. Highest domestic package 1.12 Crore. International offers 1-2.5 Lac USD annually.','Placements',2,1],
    ['faq-iitb-03','col-iitb-0001','Does IIT Bombay have hostel for all students?','Yes, 18 hostels with 6,000+ capacity. All first-year students guaranteed accommodation.','Hostel',3,1],
    ['faq-iima-01','col-iima-0002','What CAT percentile is needed for IIM Ahmedabad?','99+ percentile typically required. Cut-off: 99th for General, 95+ for OBC, 85+ for SC/ST.','Admissions',1,1],
    ['faq-iima-02','col-iima-0002','What is the total fee for MBA at IIM Ahmedabad?','Total fee approximately 24 Lakhs for 2024-26 batch including tuition, hostel and academic expenses.','Fees',2,1],
    ['faq-iima-03','col-iima-0002','What is the average salary after MBA from IIM Ahmedabad?','Average domestic CTC for PGP 2024 was 32 LPA. Highest international offer 109 LPA. 100% placement.','Placements',3,1],
    ['faq-nimh-01','col-nimh-0003','How to apply for MD Psychiatry at NIMHANS?','Through INI-CET examination. Must have MBBS degree. NIMHANS participates in INI-CET counselling. Only 10 seats.','Admissions',1,1],
    ['faq-nimh-02','col-nimh-0003','What is the fee structure at NIMHANS?','One of the lowest in India. MD/DM/MCh annual fee 28,000 rupees. MPhil 20,000. PhD 24,000. Hostel 45,000/year.','Fees',2,1],
    ['faq-nimh-03','col-nimh-0003','Does NIMHANS provide stipend to students?','Yes, MD/DM/MCh students get government stipend. PhD students with JRF/SRF receive 12,000 to 55,000 per month.','Financial Aid',3,1],
    ['faq-du00-01','col-du00-0004','What is the CUET cutoff for B.Com Hons at SRCC Delhi University?','General category requires CUET score 700+ out of 800. Previous year threshold was around 99.8th percentile.','Admissions',1,1],
    ['faq-du00-02','col-du00-0004','How many colleges are affiliated to Delhi University?','91 colleges including St. Stephens, Miranda House, SRCC, Lady Shri Ram, Hansraj and Kirori Mal.','General',2,1],
    ['faq-du00-03','col-du00-0004','What is the fee for B.A. Honours at Delhi University?','Annual fee ranges from 12,000 to 35,000 rupees depending on the college. Government colleges charge around 15,000/year.','Fees',3,1],
    ['faq-anna-01','col-anna-0005','What is the admission process for B.E at Anna University?','Through TNEA based on 12th marks. Eligibility: PCM minimum 45%. Counselling online by TNEA.','Admissions',1,1],
    ['faq-anna-02','col-anna-0005','What is Anna University placement percentage?','89% placement rate in 2024. Average package 7.8 LPA. Highest 62 LPA. Major recruiters: TCS, Zoho, Infosys, Wipro, HCL.','Placements',2,1],
    ['faq-anna-03','col-anna-0005','Does Anna University have GATE waiver for M.E admission?','GATE holders receive fee waiver and AICTE stipend of 12,400/month. Non-GATE can apply through TANCET.','Financial Aid',3,1],
];
$s = $pdo->prepare("INSERT IGNORE INTO college_faqs (id,college_id,question_text,answer_text,category,sort_order,is_active) VALUES (?,?,?,?,?,?,?)");
foreach ($faqs as $f) { try { $s->execute($f); $ok++; } catch(Exception $e){ $err++; echo "ERR faq: ".$e->getMessage()."\n"; } }

// ─── 12. UPDATES ────────────────────────────────────────────────────────────
$updates = [
    ['upd-iitb-01','col-iitb-0001','IIT Bombay Placement 2024: Average Package 21.82 LPA','IIT Bombay final placement 2024 achieved average CTC of 21.82 LPA. Google, Microsoft and Goldman Sachs were top recruiters. 145 international offers.','news','2024-05-15','published','https://www.iitb.ac.in'],
    ['upd-iitb-02','col-iitb-0001','JEE Advanced 2026: Application Portal Opens','Admissions to B.Tech 2026 based on JEE Advanced rank. JoSAA counselling July 2026. Total 880 seats.','admission_deadline','2026-05-31','published','https://josaa.nic.in'],
    ['upd-iitb-03','col-iitb-0001','IIT Bombay Launches New M.Tech in AI and Data Science','New 2-year M.Tech program in Artificial Intelligence and Data Science from 2026-27 via GATE-DA score.','news','2026-03-01','published','https://www.iitb.ac.in'],
    ['upd-iima-01','col-iima-0002','IIM Ahmedabad CAT 2025 Shortlist Released','900 candidates shortlisted from 3.28 lakh CAT applicants for WAT-PI 2026.','admission_deadline','2026-01-20','published','https://www.iima.ac.in'],
    ['upd-iima-02','col-iima-0002','IIM A Final Placements 2024: 100% Placement Avg 32 LPA','100% final placement achieved. Average domestic CTC 32 LPA. McKinsey top recruiter. 78 international placements.','news','2024-04-01','published','https://www.iima.ac.in'],
    ['upd-nimh-01','col-nimh-0003','NIMHANS INI-CET 2026: Applications Open','Applications for MD Psychiatry, DM Neurology, MCh Neurosurgery through INI-CET July 2026. Total 24 seats.','admission_deadline','2026-04-15','published','https://aiimsexams.ac.in'],
    ['upd-nimh-02','col-nimh-0003','NIMHANS Launches PhD in Computational Neuroscience','New interdisciplinary PhD with IISc Bangalore. 10 seats for 2026 intake.','news','2026-02-10','published','https://nimhans.ac.in'],
    ['upd-du00-01','col-du00-0004','CUET 2026 Registration Begins for Delhi University','Register at cuet.samarth.ac.in by March 31 2026. Admissions to 91 DU colleges through CUET.','admission_deadline','2026-03-31','published','https://cuet.samarth.ac.in'],
    ['upd-du00-02','col-du00-0004','Delhi University Gets NAAC A++ for Third Consecutive Time','University of Delhi awarded NAAC A++ grade for third consecutive assessment cycle, reaffirming premier status.','news','2024-11-10','published','https://www.du.ac.in'],
    ['upd-anna-01','col-anna-0005','TNEA 2026 Counselling Schedule Released','TNEA 2026 schedule released. Register at tneaonline.org. First round counselling July 15, 2026.','admission_deadline','2026-06-15','published','https://tneaonline.org'],
    ['upd-anna-02','col-anna-0005','Anna University Placements 2024: 89% Placed Highest 62 LPA','89% placement rate for 2024. Zoho tops with 320 offers. TCS 280 offers. Infosys 250 offers.','news','2024-06-10','published','https://www.annauniv.edu'],
];
$s = $pdo->prepare("INSERT IGNORE INTO college_updates (id,college_id,title,description,update_type,event_date,status,action_url) VALUES (?,?,?,?,?,?,?,?)");
foreach ($updates as $u) { try { $s->execute($u); $ok++; } catch(Exception $e){ $err++; echo "ERR update: ".$e->getMessage()."\n"; } }

// ─── 13. CUTOFFS ────────────────────────────────────────────────────────────
$cutoffs = [
    ['cut-iitb-01','col-iitb-0001',null,'crs-iitb-01','General',2024,1,63,5],
    ['cut-iitb-02','col-iitb-0001',null,'crs-iitb-01','OBC',2024,1,182,5],
    ['cut-iitb-03','col-iitb-0001',null,'crs-iitb-01','SC',2024,1,440,5],
    ['cut-iitb-04','col-iitb-0001',null,'crs-iitb-01','ST',2024,1,198,5],
    ['cut-iitb-05','col-iitb-0001',null,'crs-iitb-02','General',2024,100,1200,3],
    ['cut-iima-01','col-iima-0002',null,'crs-iima-01','General',2024,99,99,1],
    ['cut-iima-02','col-iima-0002',null,'crs-iima-01','OBC',2024,95,97,1],
    ['cut-iima-03','col-iima-0002',null,'crs-iima-01','SC',2024,85,90,1],
    ['cut-anna-01','col-anna-0005',null,'crs-anna-01','General',2024,100,12500,3],
    ['cut-anna-02','col-anna-0005',null,'crs-anna-01','OBC',2024,1,20000,3],
    ['cut-anna-03','col-anna-0005',null,'crs-anna-01','SC',2024,1,35000,3],
    ['cut-du00-01','col-du00-0004',null,'crs-du00-01','General',2024,650,750,2],
    ['cut-du00-02','col-du00-0004',null,'crs-du00-01','OBC',2024,600,700,2],
    ['cut-du00-03','col-du00-0004',null,'crs-du00-01','SC',2024,500,620,2],
];
$s = $pdo->prepare("INSERT IGNORE INTO college_cutoffs (id,college_id,exam_id,course_id,category,year,opening_rank,closing_rank,round_number) VALUES (?,?,?,?,?,?,?,?,?)");
foreach ($cutoffs as $c) { try { $s->execute($c); $ok++; } catch(Exception $e){ $err++; echo "ERR cutoff: ".$e->getMessage()."\n"; } }

// ─── 14. FACULTY ────────────────────────────────────────────────────────────
$faculty = [
    ['fac-iitb-01','col-iitb-0001','Prof. Subhasis Chaudhuri','Director','Electrical Engineering','PhD, IIT Kharagpur',35,null,'Image Processing, Computer Vision'],
    ['fac-iitb-02','col-iitb-0001','Prof. Kavi Arya','Professor','Computer Science & Engineering','PhD, Imperial College London',28,null,'Operating Systems, Embedded Systems'],
    ['fac-iitb-03','col-iitb-0001','Prof. Pushpak Bhattacharyya','Professor','Computer Science & Engineering','PhD, IIT Bombay',30,null,'NLP, AI, Computational Linguistics'],
    ['fac-iitb-04','col-iitb-0001','Prof. Krithi Ramamritham','Professor Emeritus','Computer Science','PhD, University of Utah',40,null,'Real-Time Systems, IoT, Data Management'],
    ['fac-iima-01','col-iima-0002','Prof. Bharat Bhasker','Director','Information Systems','PhD, IIT Kanpur',32,null,'AI in Management, Decision Support'],
    ['fac-iima-02','col-iima-0002','Prof. Arnab Laha','Professor','Production & Quantitative Methods','PhD, IIM Ahmedabad',25,null,'Statistics, Operations Research, Analytics'],
    ['fac-iima-03','col-iima-0002','Prof. Chiranjib Bhattacharya','Professor','Marketing','PhD, University of Michigan',22,null,'Consumer Behaviour, Brand Strategy'],
    ['fac-nimh-01','col-nimh-0003','Prof. Pratima Murthy','Director','Psychiatry','MD, DPM, FRCPsych',30,null,'Addiction Medicine, Clinical Psychiatry'],
    ['fac-nimh-02','col-nimh-0003','Dr. Suresh Bada Math','Professor & Head','Psychiatry','MD, LLB, MBA',25,null,'Forensic Psychiatry, Policy Research'],
    ['fac-nimh-03','col-nimh-0003','Dr. Rose D Bharath','Associate Professor','Neuroimaging','MD, PhD',18,null,'Functional MRI, Epilepsy, Brain Mapping'],
    ['fac-du00-01','col-du00-0004','Prof. Yogesh Singh','Vice Chancellor','Applied Sciences','PhD, Roorkee University',35,null,'Microelectronics, VLSI Design'],
    ['fac-du00-02','col-du00-0004','Prof. Amita Singh','Professor','Political Science','PhD, JNU',28,null,'Public Administration, Gender Politics'],
    ['fac-anna-01','col-anna-0005','Dr. R Velraj','Vice Chancellor','Mechanical Engineering','PhD, Anna University',38,null,'Thermal Energy Storage, Renewable Energy'],
    ['fac-anna-02','col-anna-0005','Prof. Rajeswari Mukesh','Professor & Dean','Computer Science','PhD, IIT Madras',30,null,'Machine Learning, Data Mining, IoT'],
];
$s = $pdo->prepare("INSERT IGNORE INTO college_faculty (id,college_id,faculty_name,designation,department,qualification,experience_years,photo_url,specialization) VALUES (?,?,?,?,?,?,?,?,?)");
foreach ($faculty as $f) { try { $s->execute($f); $ok++; } catch(Exception $e){ $err++; echo "ERR faculty: ".$e->getMessage()."\n"; } }

echo "\n=====================================\n";
echo "✅ DONE: $ok statements OK, $err errors\n";
echo "=====================================\n";

// Verify
$rows = $pdo->query("SELECT slug, name, overall_rating_avg FROM colleges WHERE id LIKE 'col-%' ORDER BY featured_order")->fetchAll(PDO::FETCH_ASSOC);
echo "\nSeeded colleges:\n";
foreach ($rows as $r) echo "  ✓ " . $r['slug'] . " | " . $r['name'] . " | Rating: " . $r['overall_rating_avg'] . "\n";
