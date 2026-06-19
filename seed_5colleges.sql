-- ============================================================
-- AdmissionSeason – 5 College Seed Data
-- IIT Bombay | IIM Ahmedabad | NIMHANS | Delhi Univ | Anna Univ
-- ============================================================
SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ─── 1. COLLEGES ────────────────────────────────────────────
INSERT IGNORE INTO `colleges`
(`id`,`name`,`slug`,`college_type`,`ownership`,`status`,`is_featured`,`is_verified`,`featured_order`,`ranking_nirf`,`city_id`,`state_id`,`established_year`,`naac_grade`,`ugc_approved`,`aicte_approved`,`nba_approved`,`total_students`,`total_faculty`,`campus_area_acres`,`overall_rating_avg`,`total_reviews`,`publish_status`,`verification_status`,`data_quality_score`,`campus_type`)
VALUES
('col-iitb-0001','IIT Bombay','iit-bombay','deemed','central','active',1,1,1,3,384,20,1958,'A++',1,1,1,10000,600,550.0,4.7,520,'published','verified',95,'urban'),
('col-iima-0002','IIM Ahmedabad','iim-ahmedabad','deemed','central','active',1,1,2,1,150,11,1961,'A++',1,0,0,1200,105,102.0,4.8,380,'published','verified',98,'urban'),
('col-nimh-0003','NIMHANS Bangalore','nimhans-bangalore','deemed','central','active',1,1,3,87,266,16,1974,'A+',1,0,0,2500,320,43.5,4.4,210,'published','verified',90,'urban'),
('col-du00-0004','University of Delhi','university-of-delhi','govt','central','active',1,1,4,11,137,9,1922,'A++',1,0,0,300000,9000,600.0,4.3,890,'published','verified',92,'urban'),
('col-anna-0005','Anna University','anna-university','govt','state','active',1,1,5,5,544,30,1978,'A+',1,1,1,25000,1800,180.0,4.5,340,'published','verified',88,'urban');

-- ─── 2. CONTACTS ────────────────────────────────────────────
INSERT IGNORE INTO `college_contacts`
(`id`,`college_id`,`website_url`,`email`,`phone`,`address`,`pincode`,`nearest_railway_km`,`nearest_airport_km`)
VALUES
('cnt-iitb-0001','col-iitb-0001','https://www.iitb.ac.in','info@iitb.ac.in','022-25722545','Powai, Mumbai, Maharashtra','400076',5,28),
('cnt-iima-0002','col-iima-0002','https://www.iima.ac.in','pgp@iima.ac.in','079-66328234','Vastrapur, Ahmedabad, Gujarat','380015',8,12),
('cnt-nimh-0003','col-nimh-0003','https://nimhans.ac.in','registrar@nimhans.ac.in','080-46110007','Hosur Road, Bengaluru, Karnataka','560029',4,35),
('cnt-du00-0004','col-du00-0004','https://www.du.ac.in','webmaster@du.ac.in','011-27666351','University Road, Delhi (NCT)','110007',2,22),
('cnt-anna-0005','col-anna-0005','https://www.annauniv.edu','registrar@annauniv.edu','044-22357004','Sardar Patel Road, Guindy, Chennai','600025',6,18);

-- ─── 3. CONTENT ─────────────────────────────────────────────
INSERT IGNORE INTO `college_content`
(`id`,`college_id`,`about_text`,`highlights_json`,`accreditations_json`,`rankings_json`,`awards_json`)
VALUES
('cct-iitb-0001','col-iitb-0001',
'Indian Institute of Technology Bombay (IIT Bombay) is a public technical and research university located in Powai, Mumbai. Established in 1958 with assistance from UNESCO, it is one of the premier engineering institutions in India. IIT Bombay is known for world-class infrastructure, cutting-edge research, and celebrated alumni who lead global corporations, research labs, and startups. The institute has 17 departments and 13 interdisciplinary programs across engineering, sciences, design, and management.',
'["Ranked #3 in India by NIRF 2024","Top 150 globally by QS World University Rankings 2025","550-acre sprawling campus in Powai, Mumbai","10,000+ students across UG, PG and PhD programs","600+ distinguished faculty members","700+ active research projects annually","Strong industry-academia collaborations with 500+ companies","98% eligible students placed in campus recruitment"]',
'["NAAC A++","UGC Approved","NBA Accredited","AICTE Approved","QS Stars 5-Star"]',
'{"nirf":3,"qs":149,"times":201}',
'["National Institutional Ranking Framework Award","Best Technical Institution – MoE 2023"]'),

('cct-iima-0002','col-iima-0002',
'Indian Institute of Management Ahmedabad (IIMA) is one of the most prestigious management schools in the world, founded in 1961 in collaboration with Harvard Business School. IIMA offers world-renowned Post Graduate Programme (PGP) and Fellow Programme in Management. It is ranked #1 management school in India and among top 50 globally. The institute is famous for its case-study based pedagogy, exceptional faculty and outstanding alumni network of 38,000+ professionals worldwide.',
'["Ranked #1 in India by NIRF Management Category 2024","Top 50 globally – Financial Times MBA Rankings","Highest MBA package: 109 LPA International","Average domestic package: 32 LPA PGP 2024","102-acre beautifully designed heritage campus","105 full-time faculty with global expertise","38,000+ alumni across 6 continents","Case study pedagogy pioneered in India"]',
'["NAAC A++","AACSB Accredited","EQUIS Accredited","UGC Approved","AMBA Accredited"]',
'{"nirf":1,"ft_mba":47}',
'["Best B-School in India – Business Today 2024","AACSB Triple Crown Accreditation"]'),

('cct-nimh-0003','col-nimh-0003',
'National Institute of Mental Health and Neurosciences (NIMHANS) in Bengaluru is India\'s premier autonomous institution of national importance for mental health and neurosciences. Established in 1974, NIMHANS is a Deemed University under the Ministry of Health and Family Welfare, Government of India. It serves as the nodal centre for education, training, research and advanced clinical services in psychiatry, neurology, neurosurgery and allied disciplines. NIMHANS operates an 800-bed hospital with over 30 departments.',
'["Only specialised neuroscience institute with National Importance status","800+ bed super speciality hospital with 30+ clinical departments","NAAC A+ accredited Deemed University","One of the lowest fee structures in India","Stipends and fellowships available for research scholars","International collaborations with WHO and NIH","Located in heart of Bangalore with excellent connectivity","Ranked among Top 100 Medical Institutes in India by NIRF"]',
'["NAAC A+","UGC Approved","MCI Approved","WHO Collaborating Centre","Institute of National Importance"]',
'{"nirf":87,"medical":12}',
'["WHO Collaborating Centre for Mental Health","National Award for Excellence in Medical Education 2023"]'),

('cct-du00-0004','col-du00-0004',
'The University of Delhi (DU), established in 1922, is a premier central university and one of the largest universities in the world. Located in the national capital with 16 faculties, 86 academic departments, and 91 affiliated colleges, DU offers over 500 programs. Its constituent colleges like SRCC, Miranda House, Lady Shri Ram, and St. Stephen\'s are among the most sought-after in India. DU graduates are found in the highest echelons of government, industry, academia, and civil society.',
'["Ranked #11 in India by NIRF 2024","91 affiliated colleges including SRCC, Miranda House, LSR","16 faculties and 86 academic departments","300,000+ students – one of the largest universities in the world","NAAC A++ accredited Central University","600-acre multi-campus in North and South Delhi","Alumni include Prime Ministers and Nobel Laureates","2000+ PhD scholars annually"]',
'["NAAC A++","UGC Approved","ACU Member","AIU Member","Commonwealth Universities Network"]',
'{"nirf":11,"india_rank":11}',
'["NAAC A++ Re-accreditation 2022","National Education Award – MoE 2023"]'),

('cct-anna-0005','col-anna-0005',
'Anna University, established in 1978, is a technical university located in Chennai, Tamil Nadu. Named after former Chief Minister Dr. C.N. Annadurai, it offers higher education in Engineering, Technology, Architecture and Applied Sciences. It is affiliated with over 600 engineering colleges across Tamil Nadu, making it one of the largest affiliating technical universities in India. Anna University is consistently ranked among the top 10 technical universities by NIRF.',
'["Ranked #5 in India by NIRF University Category 2024","Affiliated with 600+ engineering colleges across Tamil Nadu","NAAC A+ accredited – highest grade","25,000+ students in main campus","1800 faculty members with diverse expertise","Strong tie-ups with TCS, Infosys, Wipro, Google, Amazon","180-acre main campus in Guindy, Chennai","PhD programs in 40+ disciplines"]',
'["NAAC A+","UGC Approved","AICTE Approved","NBA Accredited","IET Accredited UK"]',
'{"nirf":5,"technical":5}',
'["Best Technical University – TN Govt 2023","NIRF Top 5 University 2024"]');

-- ─── 4. COURSES ─────────────────────────────────────────────
INSERT IGNORE INTO `college_courses`
(`id`,`college_id`,`course_name`,`course_level`,`duration_years`,`total_fee`,`semester_fee`,`annual_fee`,`seats_available`,`eligibility_criteria`,`application_fee`,`emi_available`,`specializations`)
VALUES
('crs-iitb-01','col-iitb-0001','B.Tech','UG',4,900000.00,112500.00,225000.00,880,'JEE Advanced + Board 75%',500.00,0,'["Computer Science","Electrical Engineering","Mechanical Engineering","Civil Engineering","Chemical Engineering","Aerospace Engineering"]'),
('crs-iitb-02','col-iitb-0001','M.Tech','PG',2,120000.00,30000.00,60000.00,600,'B.Tech + GATE Score',300.00,0,'["Data Science & AI","VLSI Design","Structural Engineering","Thermal Engineering"]'),
('crs-iitb-03','col-iitb-0001','M.Sc','PG',2,80000.00,20000.00,40000.00,200,'B.Sc + JAM Score',300.00,0,'["Mathematics","Physics","Chemistry","Statistics"]'),
('crs-iitb-04','col-iitb-0001','PhD','PhD',5,50000.00,12500.00,25000.00,500,'M.Tech/M.Sc + GATE/NET',200.00,0,'["Engineering","Sciences","Management"]'),
('crs-iima-01','col-iima-0002','MBA (PGP)','PG',2,2400000.00,600000.00,1200000.00,400,'Bachelor Degree + CAT Score + GD/PI',2000.00,0,'["Finance","Marketing","Operations","Strategy","Human Resources"]'),
('crs-iima-02','col-iima-0002','MBA (PGPX)','PG',1,3200000.00,800000.00,3200000.00,70,'Bachelor + 5yr Work Exp + CAT/GMAT',2000.00,0,'["General Management","Leadership","Global Strategy"]'),
('crs-iima-03','col-iima-0002','FPM (PhD in Management)','PhD',4,600000.00,150000.00,600000.00,30,'PG Degree + CAT/GMAT/GRE',1000.00,0,'["Finance","Marketing","OB & HRM","Economics","Strategy"]'),
('crs-nimh-01','col-nimh-0003','MD Psychiatry','PG',3,84000.00,14000.00,28000.00,10,'MBBS + INI-CET',500.00,0,'["Clinical Psychiatry","Child & Adolescent Psychiatry","Geriatric Psychiatry"]'),
('crs-nimh-02','col-nimh-0003','DM Neurology','PG',3,84000.00,14000.00,28000.00,8,'MD + NEET-SS',500.00,0,'["Stroke Medicine","Epilepsy","Movement Disorders"]'),
('crs-nimh-03','col-nimh-0003','MCh Neurosurgery','PG',3,84000.00,14000.00,28000.00,6,'MS Surgery + NEET-SS',500.00,0,'["Brain Surgery","Spine Surgery","Pediatric Neurosurgery"]'),
('crs-nimh-04','col-nimh-0003','M.Phil Clinical Psychology','PG',2,40000.00,10000.00,20000.00,14,'MA/MSc Psychology + NIMHANS Entrance',300.00,0,'["Assessment & Diagnosis","CBT","Neuropsychology"]'),
('crs-nimh-05','col-nimh-0003','M.Sc Neuroscience','PG',2,40000.00,10000.00,20000.00,15,'BSc/MBBS + NIMHANS Entrance',300.00,0,'["Cellular Neuroscience","Cognitive Neuroscience"]'),
('crs-nimh-06','col-nimh-0003','PhD Neurosciences','PhD',5,120000.00,30000.00,24000.00,20,'PG Degree + NIMHANS Entrance/UGC-NET',200.00,0,'["Molecular Neurobiology","Neuroimaging","Clinical Research"]'),
('crs-du00-01','col-du00-0004','B.A. (Hons)','UG',3,36000.00,9000.00,18000.00,5000,'CUET Score + Board 12th',500.00,0,'["Economics","Political Science","History","English","Psychology","Sociology"]'),
('crs-du00-02','col-du00-0004','B.Com (Hons)','UG',3,36000.00,9000.00,18000.00,2500,'CUET Score + Board 12th Commerce',500.00,0,'["Accounting & Finance","Business Studies"]'),
('crs-du00-03','col-du00-0004','B.Sc (Hons)','UG',3,60000.00,15000.00,30000.00,1800,'CUET Score + Board 12th Science',500.00,0,'["Physics","Chemistry","Mathematics","Computer Science","Zoology","Botany"]'),
('crs-du00-04','col-du00-0004','MA','PG',2,30000.00,7500.00,15000.00,2000,'Bachelor + CUET PG Score',300.00,0,'["Economics","History","Political Science","English"]'),
('crs-du00-05','col-du00-0004','MBA','PG',2,300000.00,75000.00,150000.00,300,'Bachelor + CAT/MAT/XAT',500.00,0,'["Finance","Marketing","HR & OB"]'),
('crs-anna-01','col-anna-0005','B.E / B.Tech','UG',4,600000.00,75000.00,150000.00,2400,'TN TNEA Rank / JEE Main',500.00,1,'["Computer Science & Engineering","Information Technology","Electronics & Communication","Mechanical Engineering","Civil Engineering","AI & Machine Learning"]'),
('crs-anna-02','col-anna-0005','M.E / M.Tech','PG',2,160000.00,40000.00,80000.00,800,'B.E/B.Tech + GATE Score',300.00,0,'["Applied Electronics","Structural Engineering","Software Engineering"]'),
('crs-anna-03','col-anna-0005','MCA','PG',2,120000.00,30000.00,60000.00,120,'BCA/BSc CS + TN PGCET',300.00,0,'["Software Development","Data Analytics","Cloud Computing"]'),
('crs-anna-04','col-anna-0005','PhD','PhD',5,200000.00,50000.00,40000.00,200,'M.E/M.Tech + GATE + Interview',200.00,0,'["Engineering","Science","Architecture"]');

-- ─── 5. PLACEMENTS ──────────────────────────────────────────
INSERT IGNORE INTO `college_placements`
(`id`,`college_id`,`placement_year`,`avg_package_lpa`,`highest_package_lpa`,`median_package_lpa`,`placement_percentage`,`students_placed`,`international_placements`,`top_recruiters`)
VALUES
('plc-iitb-2024','col-iitb-0001',2024,21.82,99.00,18.50,94.5,1320,145,'["Google","Microsoft","Goldman Sachs","McKinsey & Company","Amazon","Apple","Meta","Qualcomm","Samsung R&D","Boston Consulting Group","JP Morgan","Bain & Company"]'),
('plc-iitb-2023','col-iitb-0001',2023,20.34,85.00,17.20,93.0,1280,132,'["Google","Microsoft","Morgan Stanley","Flipkart","Intel","Nvidia","Amazon","Deutsche Bank"]'),
('plc-iima-2024','col-iima-0002',2024,32.00,99.00,28.50,100.0,400,78,'["McKinsey & Company","BCG","Bain & Company","Goldman Sachs","JP Morgan","Microsoft","Google","Amazon","Hindustan Unilever","Nestle","Accenture Strategy","Deloitte"]'),
('plc-iima-2023','col-iima-0002',2023,29.00,95.00,26.40,100.0,390,70,'["McKinsey","BCG","Amazon","Microsoft","RIL","HUL","P&G"]'),
('plc-nimh-2024','col-nimh-0003',2024,18.00,45.00,16.00,92.0,180,8,'["AIIMS","PGI Chandigarh","Apollo Hospitals","Fortis Healthcare","Manipal Hospitals","WHO","ICMR","National Mental Health Programme"]'),
('plc-du00-2024','col-du00-0004',2024,8.50,42.00,7.20,78.0,12400,0,'["Deloitte","EY","KPMG","PwC","Accenture","TCS","Wipro","HCL","Capgemini","HDFC Bank","Goldman Sachs","IAS/IPS Civil Services"]'),
('plc-anna-2024','col-anna-0005',2024,7.80,62.00,6.40,89.0,3200,12,'["TCS","Infosys","Wipro","HCL Technologies","Zoho","Cognizant","Tech Mahindra","Ford India","Samsung","Intel","Qualcomm","AMD"]'),
('plc-anna-2023','col-anna-0005',2023,7.20,55.00,5.90,87.0,3100,10,'["TCS","Infosys","CTS","HCL","Zoho","PayPal","Dell","IBM"]');

-- ─── 6. RANKINGS ────────────────────────────────────────────
INSERT IGNORE INTO `rankings`
(`id`,`ranking_body`,`ranking_year`,`category`,`college_id`,`rank_position`,`score`,`previous_year_rank`)
VALUES
(101,'NIRF',2024,'Engineering','col-iitb-0001',3,82.55,3),
(102,'QS World University Rankings',2025,'Overall','col-iitb-0001',149,49.8,172),
(103,'Times Higher Education',2024,'Engineering','col-iitb-0001',201,38.2,220),
(104,'India Today',2024,'Engineering','col-iitb-0001',2,NULL,2),
(201,'NIRF',2024,'Management','col-iima-0002',1,83.12,1),
(202,'Financial Times MBA',2024,'Global MBA','col-iima-0002',47,NULL,52),
(203,'QS Global MBA Rankings',2025,'MBA','col-iima-0002',61,NULL,65),
(301,'NIRF',2024,'Medical','col-nimh-0003',87,52.30,90),
(401,'NIRF',2024,'University','col-du00-0004',11,57.42,11),
(402,'QS World University Rankings',2025,'Overall','col-du00-0004',521,28.4,551),
(403,'India Today',2024,'University','col-du00-0004',3,NULL,3),
(501,'NIRF',2024,'University','col-anna-0005',5,62.18,6),
(502,'NIRF',2024,'Engineering','col-anna-0005',14,55.10,16);

-- ─── 7. MEDIA ───────────────────────────────────────────────
INSERT IGNORE INTO `college_media`
(`id`,`college_id`,`logo_url`,`cover_image_url`,`image_url`,`caption`,`sort_order`,`image_type`)
VALUES
('med-iitb-cov','col-iitb-0001','https://upload.wikimedia.org/wikipedia/en/thumb/1/1d/Indian_Institute_of_Technology_Bombay_Logo.svg/200px-Indian_Institute_of_Technology_Bombay_Logo.svg.png','https://images.unsplash.com/photo-1562774053-701939374585?w=1200&q=80',NULL,'IIT Bombay Main Building',0,NULL),
('med-iitb-g1','col-iitb-0001',NULL,NULL,'https://images.unsplash.com/photo-1541339907198-e08756dedf3f?w=600&q=80','IIT Bombay Campus Aerial View',1,'campus'),
('med-iitb-g2','col-iitb-0001',NULL,NULL,'https://images.unsplash.com/photo-1498243691581-b145c3f54a5a?w=600&q=80','Central Library Complex',2,'campus'),
('med-iitb-g3','col-iitb-0001',NULL,NULL,'https://images.unsplash.com/photo-1559526324-4b87b5e36e44?w=600&q=80','Computer Science Research Lab',3,'lab'),
('med-iima-cov','col-iima-0002','https://upload.wikimedia.org/wikipedia/en/f/fb/IIM_Ahmedabad_Logo.svg','https://images.unsplash.com/photo-1523050854058-8df90110c9f1?w=1200&q=80',NULL,'IIM Ahmedabad Heritage Campus',0,NULL),
('med-iima-g1','col-iima-0002',NULL,NULL,'https://images.unsplash.com/photo-1544717305-2782549b5136?w=600&q=80','Louis Kahn Campus Architecture',1,'campus'),
('med-iima-g2','col-iima-0002',NULL,NULL,'https://images.unsplash.com/photo-1606761568499-6d2451b23c66?w=600&q=80','Case Study Classroom',2,'classroom'),
('med-nimh-cov','col-nimh-0003','https://nimhans.ac.in/wp-content/uploads/2019/11/nimhans_logo.png','https://images.unsplash.com/photo-1587351021759-3e566b6af7cc?w=1200&q=80',NULL,'NIMHANS Hospital Complex',0,NULL),
('med-nimh-g1','col-nimh-0003',NULL,NULL,'https://images.unsplash.com/photo-1519494026892-80bbd2d6fd0d?w=600&q=80','NIMHANS Main Hospital Building',1,'campus'),
('med-nimh-g2','col-nimh-0003',NULL,NULL,'https://images.unsplash.com/photo-1582719471384-894fbb16e074?w=600&q=80','Neuroscience Research Laboratory',2,'lab'),
('med-du00-cov','col-du00-0004','https://upload.wikimedia.org/wikipedia/en/thumb/7/70/University_of_Delhi.svg/200px-University_of_Delhi.svg.png','https://images.unsplash.com/photo-1607237138185-eedd9c632b0b?w=1200&q=80',NULL,'Delhi University North Campus',0,NULL),
('med-du00-g1','col-du00-0004',NULL,NULL,'https://images.unsplash.com/photo-1503676260728-1c00da094a0b?w=600&q=80','DU Heritage Buildings',1,'campus'),
('med-du00-g2','col-du00-0004',NULL,NULL,'https://images.unsplash.com/photo-1541339907198-e08756dedf3f?w=600&q=80','Central Library DU',2,'campus'),
('med-anna-cov','col-anna-0005','https://upload.wikimedia.org/wikipedia/en/thumb/e/ef/Anna_University_Logo.svg/200px-Anna_University_Logo.svg.png','https://images.unsplash.com/photo-1513077202514-c511b41bd4c7?w=1200&q=80',NULL,'Anna University Guindy Campus',0,NULL),
('med-anna-g1','col-anna-0005',NULL,NULL,'https://images.unsplash.com/photo-1581092795360-fd1ca04f0952?w=600&q=80','Engineering Research Lab',1,'lab'),
('med-anna-g2','col-anna-0005',NULL,NULL,'https://images.unsplash.com/photo-1562774053-701939374585?w=600&q=80','Anna University Main Campus',2,'campus');

-- ─── 8. INFRASTRUCTURE ──────────────────────────────────────
INSERT IGNORE INTO `college_infrastructure`
(`id`,`college_id`,`library`,`library_books_count`,`sports_facilities`,`labs`,`auditorium`,`auditorium_capacity`,`cafeteria`,`wifi`,`wifi_speed_mbps`,`medical_facility`,`transport`,`ev_charging`,`solar_power`)
VALUES
('inf-iitb-0001','col-iitb-0001',1,500000,'["Cricket","Football","Basketball","Swimming Pool","Tennis","Badminton","Athletics Track"]','["Advanced Computing Lab","Nanotechnology Lab","Robotics Lab","AI & ML Research Lab","Semiconductor Lab","Chemical Engineering Lab","Bio-Engineering Lab"]',1,3000,1,1,1000,1,1,1,1),
('inf-iima-0002','col-iima-0002',1,200000,'["Cricket","Tennis","Squash","Basketball","Swimming Pool","Yoga Center","Gymnasium"]','["Computer Lab","Bloomberg Terminal Lab","Behavioral Lab","Strategy Simulation Lab","Analytics Lab"]',1,1200,1,1,1000,1,1,1,1),
('inf-nimh-0003','col-nimh-0003',1,150000,'["Cricket Ground","Basketball Court","Badminton Court","Yoga Center","Gymnasium"]','["Neuroscience Research Lab","Brain Imaging MRI Lab","Electrophysiology Lab","Drug Research Lab","Genetic Testing Lab"]',1,800,1,1,500,1,1,0,1),
('inf-du00-0004','col-du00-0004',1,1000000,'["Cricket","Football","Hockey","Athletics Track","Tennis","Basketball","Swimming Pool","Gymnasium"]','["Physics Lab","Chemistry Lab","Computer Lab","Bio-Informatics Lab","Language Lab","Social Research Lab"]',1,5000,1,1,100,1,1,0,0),
('inf-anna-0005','col-anna-0005',1,300000,'["Cricket","Football","Volleyball","Basketball","Athletics","Swimming Pool","Tennis"]','["Advanced Computing Lab","VLSI Lab","Robotics Lab","Automotive Engineering Lab","Structural Testing Lab","Environmental Lab","Biomedical Lab"]',1,2000,1,1,500,1,1,1,1);

-- ─── 9. HOSTELS ─────────────────────────────────────────────
INSERT IGNORE INTO `college_hostels`
(`id`,`college_id`,`hostel_available`,`hostel_type`,`hostel_capacity`,`hostel_fee_annual`,`mess_available`,`mess_type`,`ac_available`,`laundry_available`)
VALUES
('hst-iitb-0001','col-iitb-0001',1,'both',6000,85000.00,1,'both',0,1),
('hst-iima-0002','col-iima-0002',1,'both',1500,120000.00,1,'both',1,1),
('hst-nimh-0003','col-nimh-0003',1,'both',800,45000.00,1,'both',0,1),
('hst-du00-0004','col-du00-0004',1,'both',15000,55000.00,1,'both',0,1),
('hst-anna-0005','col-anna-0005',1,'both',5000,70000.00,1,'both',0,1);

-- ─── 10. ADMISSIONS ─────────────────────────────────────────
INSERT IGNORE INTO `college_admissions`
(`id`,`college_id`,`admission_process`,`accepted_exams`,`admission_start_date`,`admission_end_date`,`merit_based`,`direct_admission`,`management_quota_seats`,`nri_quota_seats`,`lateral_entry_available`,`application_mode`,`selection_criteria`)
VALUES
('adm-iitb-0001','col-iitb-0001','Admissions to B.Tech are based on JEE Advanced rank. Candidates must qualify JEE Main first, then appear for JEE Advanced. Final allocation via JoSAA counselling. M.Tech admissions through GATE score followed by institute-level interviews.','["JEE Advanced","JEE Main","GATE","JAM","CEED"]','2026-01-01','2026-05-31',1,0,0,50,0,'online','Merit through national entrance exams; counselling via JoSAA/CCMT'),
('adm-iima-0002','col-iima-0002','Admissions to PGP (MBA) based on CAT score followed by Written Ability Test (WAT) and Personal Interview (PI). Final selection considers academics, work experience, diversity and WAT-PI performance. PGPX requires GMAT/GRE with 5+ years work experience.','["CAT","GMAT","GRE"]','2025-11-01','2026-04-30',1,0,0,20,0,'online','CAT Score 30% + Academic Record 20% + WAT-PI 50%'),
('adm-nimh-0003','col-nimh-0003','Admissions through NIMHANS Entrance Examination for MPhil, MSc and PhD. MD/DM/MCh through INI-CET or NEET-SS. Candidates must meet eligibility criteria for each specific program.','["INI-CET","NEET-SS","NIMHANS Entrance Exam","UGC-NET","JRF"]','2026-01-15','2026-06-30',1,0,0,5,0,'online','National entrance exam + Merit + Interview for shortlisted candidates'),
('adm-du00-0004','col-du00-0004','UG admissions through CUET (Common University Entrance Test) via centralised DU portal. Seats allotted based on CUET score and merit list. PG admissions use CUET-PG for most departments.','["CUET","CUET-PG","CAT","MAT","XAT","CLAT","JEE Main"]','2026-03-01','2026-07-31',1,0,150,50,1,'online','CUET Score merit list; college-wise cut-off lists released separately'),
('adm-anna-0005','col-anna-0005','B.E/B.Tech admissions through Tamil Nadu Engineering Admissions (TNEA) based on 12th marks. Out-of-state candidates via JEE Main. M.E/M.Tech admissions through TANCET or GATE score.','["TNEA","JEE Main","GATE","TANCET","PGCET"]','2026-04-01','2026-08-31',1,0,300,100,1,'online','TNEA rank/JEE score for UG; GATE/TANCET for PG; Merit for PhD');

-- ─── 11. FAQs ───────────────────────────────────────────────
INSERT IGNORE INTO `college_faqs`
(`id`,`college_id`,`question_text`,`answer_text`,`category`,`sort_order`,`is_active`)
VALUES
('faq-iitb-01','col-iitb-0001','What is the JEE Advanced rank required for IIT Bombay CSE?','The closing rank for IIT Bombay Computer Science (General category) is typically between 60-100. For female candidates around 200-300. Ranks vary by year.','Admissions',1,1),
('faq-iitb-02','col-iitb-0001','What is the average placement package at IIT Bombay?','The average CTC for IIT Bombay B.Tech 2024 batch was 21.82 LPA with highest domestic package of 1.12 Crore. International offers ranged from 1-2.5 Lac USD annually.','Placements',2,1),
('faq-iitb-03','col-iitb-0001','Does IIT Bombay have hostel for all students?','Yes, IIT Bombay has 18 hostels with 6,000+ capacity. All first-year students are guaranteed hostel accommodation. Allocation is merit and lottery based.','Hostel',3,1),
('faq-iima-01','col-iima-0002','What CAT percentile is needed for IIM Ahmedabad PGP?','IIM Ahmedabad shortlists 99+ percentile in CAT typically. Cut-off varies by category: 99th for General, 95+ for OBC, 85+ for SC/ST.','Admissions',1,1),
('faq-iima-02','col-iima-0002','What is the total fee for MBA at IIM Ahmedabad?','Total fee for 2-year PGP at IIMA is approximately 24 Lakhs for 2024-26 batch, including tuition, hostel and academic expenses. Scholarships are available.','Fees',2,1),
('faq-iima-03','col-iima-0002','What is the average salary after MBA from IIM Ahmedabad?','Average domestic CTC for PGP 2024 batch was 32 LPA. Highest international offer was 109 LPA. 100% students received placement offers.','Placements',3,1),
('faq-nimh-01','col-nimh-0003','How do I apply for MD Psychiatry at NIMHANS?','MD Psychiatry at NIMHANS is through INI-CET examination. Candidates must have MBBS degree. NIMHANS participates in INI-CET counselling. Only 10 seats available annually.','Admissions',1,1),
('faq-nimh-02','col-nimh-0003','What is the fee structure at NIMHANS?','NIMHANS has one of the lowest fee structures in India. MD/DM/MCh annual fee is 28,000 rupees. MPhil annual fee is 20,000 rupees. PhD annual fee is 24,000. Hostel 45,000/year.','Fees',2,1),
('faq-nimh-03','col-nimh-0003','Does NIMHANS provide stipend to students?','Yes, NIMHANS provides stipend to MD/DM/MCh students as per government norms. PhD students with JRF/SRF receive monthly stipend ranging from 12,000 to 55,000/month.','Financial Aid',3,1),
('faq-du00-01','col-du00-0004','What is the CUET cutoff for B.Com Hons at SRCC, Delhi University?','SRCC B.Com Hons requires CUET General category score of 700+ out of 800. Previous year rank threshold was around 99.8th percentile.','Admissions',1,1),
('faq-du00-02','col-du00-0004','How many colleges are affiliated to Delhi University?','Delhi University has 91 colleges including St. Stephens, Miranda House, SRCC, Lady Shri Ram, Hansraj, Kirori Mal and more.','General',2,1),
('faq-du00-03','col-du00-0004','What is the fee for B.A. Honours at Delhi University?','Annual fee for B.A. Honours at DU ranges from 12,000 to 35,000 rupees depending on the college. Government colleges charge around 15,000/year.','Fees',3,1),
('faq-anna-01','col-anna-0005','What is the admission process for B.E at Anna University?','B.E admissions are through TNEA based on 12th marks. Eligibility: 12th pass with PCM scoring minimum 45%. Counselling conducted online by TNEA.','Admissions',1,1),
('faq-anna-02','col-anna-0005','What is Anna University placement percentage?','Anna University achieved 89% placement rate in 2024. Average package 7.8 LPA, highest 62 LPA. Major recruiters: TCS, Zoho, Infosys, Wipro, HCL.','Placements',2,1),
('faq-anna-03','col-anna-0005','Does Anna University have GATE waiver for M.E admission?','Yes, GATE score holders receive fee waiver and AICTE stipend of 12,400/month. Non-GATE candidates can apply through TANCET.','Financial Aid',3,1);

-- ─── 12. UPDATES / NEWS ─────────────────────────────────────
INSERT IGNORE INTO `college_updates`
(`id`,`college_id`,`title`,`description`,`update_type`,`event_date`,`status`,`action_url`)
VALUES
('upd-iitb-01','col-iitb-0001','IIT Bombay Placement 2024: Average Package 21.82 LPA','IIT Bombay concludes its final placement drive for 2024 batch with average CTC of 21.82 LPA. Google, Microsoft and Goldman Sachs were top recruiters. 12 international offers above 1 Lac USD.','news','2024-05-15','published','https://www.iitb.ac.in'),
('upd-iitb-02','col-iitb-0001','JEE Advanced 2026: IIT Bombay Application Portal Opens','Admissions to B.Tech 2026 batch based on JEE Advanced 2026 rank. JoSAA counselling July 2026. Total seats: 880 across all B.Tech programs.','admission_deadline','2026-05-31','published','https://josaa.nic.in'),
('upd-iitb-03','col-iitb-0001','IIT Bombay Launches New M.Tech in AI and Data Science','New 2-year M.Tech program in Artificial Intelligence and Data Science from 2026-27. Applications through GATE-DA score.','news','2026-03-01','published','https://www.iitb.ac.in'),
('upd-iima-01','col-iima-0002','IIM Ahmedabad CAT 2025 Shortlist Announced','Shortlist for WAT-PI 2025 released. 900 candidates shortlisted from 3.28 lakh CAT applicants. PI rounds begin February 2026.','admission_deadline','2026-01-20','published','https://www.iima.ac.in'),
('upd-iima-02','col-iima-0002','IIM A Final Placements 2024: 100% Placement, Avg 32 LPA','IIM Ahmedabad completes 100% final placement for PGP 2024. Average domestic CTC 32 LPA. Top recruiter: McKinsey. International placements: 78 students.','news','2024-04-01','published','https://www.iima.ac.in'),
('upd-nimh-01','col-nimh-0003','NIMHANS INI-CET 2026: Applications Open for MD/DM/MCh','Applications for MD Psychiatry, DM Neurology and MCh Neurosurgery through INI-CET July 2026. Total seats: 24.','admission_deadline','2026-04-15','published','https://aiimsexams.ac.in'),
('upd-nimh-02','col-nimh-0003','NIMHANS Launches PhD in Computational Neuroscience','New interdisciplinary PhD in Computational Neuroscience with IISc Bangalore. 10 seats for 2026 intake.','news','2026-02-10','published','https://nimhans.ac.in'),
('upd-du00-01','col-du00-0004','CUET 2026 Registration Begins for Delhi University Admissions','CUET 2026 registration open. Register at cuet.samarth.ac.in by March 31, 2026. Admissions to 91 DU colleges through CUET.','admission_deadline','2026-03-31','published','https://cuet.samarth.ac.in'),
('upd-du00-02','col-du00-0004','Delhi University Gets NAAC A++ Re-accreditation for Third Consecutive Time','University of Delhi awarded NAAC A++ grade for third consecutive assessment cycle.','news','2024-11-10','published','https://www.du.ac.in'),
('upd-anna-01','col-anna-0005','TNEA 2026 Counselling Schedule Released by Anna University','TNEA 2026 counselling schedule released. Register at tneaonline.org. First round counselling July 15, 2026.','admission_deadline','2026-06-15','published','https://tneaonline.org'),
('upd-anna-02','col-anna-0005','Anna University Placements 2024: 89% Placed, Highest Package 62 LPA','89% placement rate for 2024 batch. Zoho tops with 320 offers. TCS 280 offers. Infosys 250 offers.','news','2024-06-10','published','https://www.annauniv.edu');

-- ─── 13. CUTOFFS ────────────────────────────────────────────
INSERT IGNORE INTO `college_cutoffs`
(`id`,`college_id`,`exam_id`,`course_id`,`category`,`year`,`opening_rank`,`closing_rank`,`round_number`)
VALUES
('cut-iitb-01','col-iitb-0001',NULL,'crs-iitb-01','General',2024,1,63,5),
('cut-iitb-02','col-iitb-0001',NULL,'crs-iitb-01','OBC',2024,1,182,5),
('cut-iitb-03','col-iitb-0001',NULL,'crs-iitb-01','SC',2024,1,440,5),
('cut-iitb-04','col-iitb-0001',NULL,'crs-iitb-01','ST',2024,1,198,5),
('cut-iitb-05','col-iitb-0001',NULL,'crs-iitb-02','General',2024,100,1200,3),
('cut-iima-01','col-iima-0002',NULL,'crs-iima-01','General',2024,99,99,1),
('cut-iima-02','col-iima-0002',NULL,'crs-iima-01','OBC',2024,95,97,1),
('cut-iima-03','col-iima-0002',NULL,'crs-iima-01','SC',2024,85,90,1),
('cut-anna-01','col-anna-0005',NULL,'crs-anna-01','General',2024,100,12500,3),
('cut-anna-02','col-anna-0005',NULL,'crs-anna-01','OBC',2024,1,20000,3),
('cut-anna-03','col-anna-0005',NULL,'crs-anna-01','SC',2024,1,35000,3),
('cut-du00-01','col-du00-0004',NULL,'crs-du00-01','General',2024,650,750,2),
('cut-du00-02','col-du00-0004',NULL,'crs-du00-01','OBC',2024,600,700,2),
('cut-du00-03','col-du00-0004',NULL,'crs-du00-01','SC',2024,500,620,2);

-- ─── 14. FACULTY ────────────────────────────────────────────
INSERT IGNORE INTO `college_faculty`
(`id`,`college_id`,`faculty_name`,`designation`,`department`,`qualification`,`experience_years`,`photo_url`,`specialization`)
VALUES
('fac-iitb-01','col-iitb-0001','Prof. Subhasis Chaudhuri','Director','Electrical Engineering','PhD, IIT Kharagpur',35,NULL,'Image Processing, Computer Vision'),
('fac-iitb-02','col-iitb-0001','Prof. Kavi Arya','Professor','Computer Science & Engineering','PhD, Imperial College London',28,NULL,'Operating Systems, Embedded Systems'),
('fac-iitb-03','col-iitb-0001','Prof. Pushpak Bhattacharyya','Professor','Computer Science & Engineering','PhD, IIT Bombay',30,NULL,'NLP, AI, Computational Linguistics'),
('fac-iitb-04','col-iitb-0001','Prof. Krithi Ramamritham','Professor Emeritus','Computer Science & Engineering','PhD, University of Utah',40,NULL,'Real-Time Systems, IoT, Data Management'),
('fac-iima-01','col-iima-0002','Prof. Bharat Bhasker','Director','Information Systems','PhD, IIT Kanpur',32,NULL,'AI in Management, Decision Support'),
('fac-iima-02','col-iima-0002','Prof. Arnab Laha','Professor','Production & Quantitative Methods','PhD, IIM Ahmedabad',25,NULL,'Statistics, Operations Research, Analytics'),
('fac-iima-03','col-iima-0002','Prof. Chiranjib Bhattacharya','Professor','Marketing','PhD, University of Michigan',22,NULL,'Consumer Behaviour, Brand Strategy'),
('fac-nimh-01','col-nimh-0003','Prof. Pratima Murthy','Director','Psychiatry','MD, DPM, FRCPsych',30,NULL,'Addiction Medicine, Clinical Psychiatry'),
('fac-nimh-02','col-nimh-0003','Dr. Suresh Bada Math','Professor & Head','Psychiatry','MD, LLB, MBA',25,NULL,'Forensic Psychiatry, Policy Research'),
('fac-nimh-03','col-nimh-0003','Dr. Rose D Bharath','Associate Professor','Neuroimaging','MD, PhD',18,NULL,'Functional MRI, Epilepsy, Brain Mapping'),
('fac-du00-01','col-du00-0004','Prof. Yogesh Singh','Vice Chancellor','Applied Sciences','PhD, Roorkee University',35,NULL,'Microelectronics, VLSI Design'),
('fac-du00-02','col-du00-0004','Prof. Amita Singh','Professor','Political Science','PhD, JNU',28,NULL,'Public Administration, Gender Politics'),
('fac-anna-01','col-anna-0005','Dr. R Velraj','Vice Chancellor','Mechanical Engineering','PhD, Anna University',38,NULL,'Thermal Energy Storage, Renewable Energy'),
('fac-anna-02','col-anna-0005','Prof. Rajeswari Mukesh','Professor & Dean','Computer Science','PhD, IIT Madras',30,NULL,'Machine Learning, Data Mining, IoT');

SET FOREIGN_KEY_CHECKS = 1;
-- END: 5 Colleges Fully Seeded
