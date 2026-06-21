-- ============================================================
-- SEED SCRIPT: Top 5 Indian Engineering Colleges
-- Generated for ADMISSION database
-- ============================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ============================================================
-- 1. CLEANUP: Delete old dummy colleges and related data
-- ============================================================

DELETE FROM answers WHERE question_id IN (
  'que00001-0000-0000-0000-000000000001','que00001-0000-0000-0000-000000000002',
  'que00001-0000-0000-0000-000000000003','que00001-0000-0000-0000-000000000004',
  'que00001-0000-0000-0000-000000000005','que00001-0000-0000-0000-000000000006',
  'que00001-0000-0000-0000-000000000007','que00001-0000-0000-0000-000000000008'
) OR id IN (
  'ans00001-0000-0000-0000-000000000001','ans00001-0000-0000-0000-000000000002',
  'ans00001-0000-0000-0000-000000000003','ans00001-0000-0000-0000-000000000004',
  'ans00001-0000-0000-0000-000000000005','ans00001-0000-0000-0000-000000000006',
  'ans00001-0000-0000-0000-000000000007','ans00001-0000-0000-0000-000000000008',
  'ans00001-0000-0000-0000-000000000009','ans00001-0000-0000-0000-000000000010',
  'ans00001-0000-0000-0000-000000000011','ans00001-0000-0000-0000-000000000012',
  'ans00001-0000-0000-0000-000000000013','ans00001-0000-0000-0000-000000000014',
  'ans00001-0000-0000-0000-000000000015','ans00001-0000-0000-0000-000000000016',
  'ans00001-0000-0000-0000-000000000017','ans00001-0000-0000-0000-000000000018'
);
DELETE FROM questions WHERE id IN (
  'que00001-0000-0000-0000-000000000001','que00001-0000-0000-0000-000000000002',
  'que00001-0000-0000-0000-000000000003','que00001-0000-0000-0000-000000000004',
  'que00001-0000-0000-0000-000000000005','que00001-0000-0000-0000-000000000006',
  'que00001-0000-0000-0000-000000000007','que00001-0000-0000-0000-000000000008'
);
DELETE FROM reviews WHERE college_id IN (
  'col00001-0000-0000-0000-000000000001',
  'col00001-0000-0000-0000-000000000002',
  'col00001-0000-0000-0000-000000000003',
  'col00001-0000-0000-0000-000000000004',
  'col00001-0000-0000-0000-000000000005'
);
DELETE FROM rankings WHERE college_id IN (
  'col00001-0000-0000-0000-000000000001',
  'col00001-0000-0000-0000-000000000002',
  'col00001-0000-0000-0000-000000000003',
  'col00001-0000-0000-0000-000000000004',
  'col00001-0000-0000-0000-000000000005'
);
DELETE FROM college_media WHERE college_id IN (
  'col00001-0000-0000-0000-000000000001',
  'col00001-0000-0000-0000-000000000002',
  'col00001-0000-0000-0000-000000000003',
  'col00001-0000-0000-0000-000000000004',
  'col00001-0000-0000-0000-000000000005'
);
DELETE FROM college_accreditations WHERE college_id IN (
  'col00001-0000-0000-0000-000000000001',
  'col00001-0000-0000-0000-000000000002',
  'col00001-0000-0000-0000-000000000003',
  'col00001-0000-0000-0000-000000000004',
  'col00001-0000-0000-0000-000000000005'
);
DELETE FROM college_hostels WHERE college_id IN (
  'col00001-0000-0000-0000-000000000001',
  'col00001-0000-0000-0000-000000000002',
  'col00001-0000-0000-0000-000000000003',
  'col00001-0000-0000-0000-000000000004',
  'col00001-0000-0000-0000-000000000005'
);
DELETE FROM college_infrastructure WHERE college_id IN (
  'col00001-0000-0000-0000-000000000001',
  'col00001-0000-0000-0000-000000000002',
  'col00001-0000-0000-0000-000000000003',
  'col00001-0000-0000-0000-000000000004',
  'col00001-0000-0000-0000-000000000005'
);
DELETE FROM college_scholarships WHERE college_id IN (
  'col00001-0000-0000-0000-000000000001',
  'col00001-0000-0000-0000-000000000002',
  'col00001-0000-0000-0000-000000000003',
  'col00001-0000-0000-0000-000000000004',
  'col00001-0000-0000-0000-000000000005'
);
DELETE FROM college_faqs WHERE college_id IN (
  'col00001-0000-0000-0000-000000000001',
  'col00001-0000-0000-0000-000000000002',
  'col00001-0000-0000-0000-000000000003',
  'col00001-0000-0000-0000-000000000004',
  'col00001-0000-0000-0000-000000000005'
);
DELETE FROM college_faculty WHERE college_id IN (
  'col00001-0000-0000-0000-000000000001',
  'col00001-0000-0000-0000-000000000002',
  'col00001-0000-0000-0000-000000000003',
  'col00001-0000-0000-0000-000000000004',
  'col00001-0000-0000-0000-000000000005'
);
DELETE FROM college_cutoffs WHERE college_id IN (
  'col00001-0000-0000-0000-000000000001',
  'col00001-0000-0000-0000-000000000002',
  'col00001-0000-0000-0000-000000000003',
  'col00001-0000-0000-0000-000000000004',
  'col00001-0000-0000-0000-000000000005'
);
DELETE FROM college_placements WHERE college_id IN (
  'col00001-0000-0000-0000-000000000001',
  'col00001-0000-0000-0000-000000000002',
  'col00001-0000-0000-0000-000000000003',
  'col00001-0000-0000-0000-000000000004',
  'col00001-0000-0000-0000-000000000005'
);
DELETE FROM college_admissions WHERE college_id IN (
  'col00001-0000-0000-0000-000000000001',
  'col00001-0000-0000-0000-000000000002',
  'col00001-0000-0000-0000-000000000003',
  'col00001-0000-0000-0000-000000000004',
  'col00001-0000-0000-0000-000000000005'
);
DELETE FROM college_courses WHERE college_id IN (
  'col00001-0000-0000-0000-000000000001',
  'col00001-0000-0000-0000-000000000002',
  'col00001-0000-0000-0000-000000000003',
  'col00001-0000-0000-0000-000000000004',
  'col00001-0000-0000-0000-000000000005'
);
DELETE FROM college_contacts WHERE college_id IN (
  'col00001-0000-0000-0000-000000000001',
  'col00001-0000-0000-0000-000000000002',
  'col00001-0000-0000-0000-000000000003',
  'col00001-0000-0000-0000-000000000004',
  'col00001-0000-0000-0000-000000000005'
);
DELETE FROM college_content WHERE college_id IN (
  'col00001-0000-0000-0000-000000000001',
  'col00001-0000-0000-0000-000000000002',
  'col00001-0000-0000-0000-000000000003',
  'col00001-0000-0000-0000-000000000004',
  'col00001-0000-0000-0000-000000000005'
);
DELETE FROM colleges WHERE id IN (
  'col00001-0000-0000-0000-000000000001',
  'col00001-0000-0000-0000-000000000002',
  'col00001-0000-0000-0000-000000000003',
  'col00001-0000-0000-0000-000000000004',
  'col00001-0000-0000-0000-000000000005'
);
DELETE FROM users WHERE id IN (
  'usr00001-0000-0000-0000-000000000001',
  'usr00001-0000-0000-0000-000000000002',
  'usr00001-0000-0000-0000-000000000003',
  'usr00001-0000-0000-0000-000000000004',
  'usr00001-0000-0000-0000-000000000005'
) OR email IN (
  'rahul.sharma@example.com',
  'priya.patel@example.com',
  'amit.kumar@example.com',
  'sneha.reddy@example.com',
  'vikram.singh@example.com'
);

-- ============================================================
-- 2. INSERT COLLEGES
-- ============================================================

INSERT INTO colleges (
  id, name, slug, college_type, ownership, status, is_featured, is_verified,
  featured_order, ranking_nirf, ranking_qs, ranking_times,
  city_id, state_id, established_year, data_quality_score,
  university_affiliation, autonomous, naac_grade, ugc_approved, aicte_approved,
  nba_approved, total_students, total_faculty, campus_area_acres,
  verification_status, founded_year, type_label, campus_type,
  overall_rating_avg, total_reviews, publish_status
) VALUES
('col00001-0000-0000-0000-000000000001', 'Indian Institute of Technology Bombay', 'iit-bombay',
 'govt', 'central', 'active', 1, 1, 1, 3, 177, 1,
 385, 20, 1958, 95, 'Autonomous', 1, 'A++', 1, 1, 1, 12000, 700, 550.0,
 'verified', 1958, 'Institute of National Importance', 'urban', 4.7, 1520, 'published'),
('col00001-0000-0000-0000-000000000002', 'Indian Institute of Technology Delhi', 'iit-delhi',
 'govt', 'central', 'active', 1, 1, 2, 2, 152, 2,
 139, 9, 1961, 94, 'Autonomous', 1, 'A++', 1, 1, 1, 11000, 650, 325.0,
 'verified', 1961, 'Institute of National Importance', 'urban', 4.6, 1380, 'published'),
('col00001-0000-0000-0000-000000000003', 'National Institute of Technology Tiruchirappalli', 'nit-trichy',
 'govt', 'central', 'active', 1, 1, 3, 9, 501, 8,
 566, 30, 1960, 88, 'Autonomous', 1, 'A+', 1, 1, 1, 8500, 450, 332.0,
 'verified', 1960, 'Institute of National Importance', 'semi-urban', 4.4, 980, 'published'),
('col00001-0000-0000-0000-000000000004', 'Birla Institute of Technology and Science Pilani', 'bits-pilani',
 'deemed', 'private_trust', 'active', 1, 1, 4, 22, 273, 12,
 384, 28, 1964, 90, 'Deemed University', 1, 'A', 1, 1, 1, 10000, 550, 328.0,
 'verified', 1964, 'Deemed University', 'semi-urban', 4.5, 1150, 'published'),
('col00001-0000-0000-0000-000000000005', 'Vellore Institute of Technology', 'vit-vellore',
 'deemed', 'private_trust', 'active', 1, 1, 5, 12, 601, 18,
 572, 30, 1984, 85, 'Deemed University', 1, 'A++', 1, 1, 1, 15000, 800, 350.0,
 'verified', 1984, 'Deemed University', 'urban', 4.3, 1250, 'published');

-- ============================================================
-- 3. INSERT COLLEGE CONTENT
-- ============================================================

INSERT INTO college_content (id, college_id, about_text, highlights_json, rankings_json, awards_json) VALUES
('cnt00001-0000-0000-0000-000000000001', 'col00001-0000-0000-0000-000000000001',
 'Indian Institute of Technology Bombay (IIT Bombay) is one of the premier engineering institutions in India, established in 1958 with Soviet assistance. Located in Powai, Mumbai, the institute has grown to become a leading center for engineering education, research, and innovation. IIT Bombay offers undergraduate, postgraduate, and doctoral programs across engineering, science, design, and management disciplines. The institute is renowned for its rigorous academic curriculum, world-class faculty, and exceptional placement record. With a sprawling 550-acre campus, IIT Bombay provides state-of-the-art infrastructure including advanced laboratories, a vast central library, and modern computing facilities. The institute has produced numerous alumni who have excelled in academia, industry, entrepreneurship, and public service globally.',
 '[{"icon":"trophy","text":"Ranked #3 in NIRF 2024 Engineering"},{"icon":"globe","text":"177 in QS World Rankings 2025"},{"icon":"users","text":"12,000+ students enrolled"},{"icon":"building","text":"550-acre lush green campus"},{"icon":"award","text":"16 Distinguished Alumni Awards"}]',
 '{"nirf_2023":3,"nirf_2024":3,"qs_2025":177,"times_2024":1}',
 '[{"year":2024,"award":"Best Innovation Hub - NIRF"},{"year":2023,"award":"Excellence in Research - UI GreenMetric"},{"year":2023,"award":"Outstanding Placement Record - ABP News"}]'),
('cnt00001-0000-0000-0000-000000000002', 'col00001-0000-0000-0000-000000000002',
 'Indian Institute of Technology Delhi (IIT Delhi) is a prestigious technical university established in 1961 in Hauz Khas, New Delhi. As one of the oldest IITs, it has consistently maintained its position among the top engineering institutions in India and globally. IIT Delhi offers a wide range of programs including B.Tech, M.Tech, M.Sc, MBA, and PhD across various departments. The institute is known for its cutting-edge research in areas like artificial intelligence, nanotechnology, biotechnology, and sustainable energy. The 325-acre campus houses advanced research centers, innovation hubs, and a vibrant student community. IIT Delhi has been ranked #2 in NIRF and #152 in QS World Rankings, reflecting its academic excellence and research output.',
 '[{"icon":"trophy","text":"Ranked #2 in NIRF 2024 Engineering"},{"icon":"globe","text":"152 in QS World Rankings 2025"},{"icon":"users","text":"11,000+ students enrolled"},{"icon":"building","text":"325-acre campus in South Delhi"},{"icon":"award","text":"12 Distinguished Alumni Awards"}]',
 '{"nirf_2023":2,"nirf_2024":2,"qs_2025":152,"times_2024":2}',
 '[{"year":2024,"award":"Top Research Institution - NIRF"},{"year":2023,"award":"Best Campus Innovation - Times Higher Education"},{"year":2023,"award":"Excellence in Industry Collaboration - FICCI"}]'),
('cnt00001-0000-0000-0000-000000000003', 'col00001-0000-0000-0000-000000000003',
 'National Institute of Technology Tiruchirappalli (NIT Trichy) is one of the premier National Institutes of Technology in India, established in 1960 as Regional Engineering College and upgraded to NIT status in 2002. Located in Tiruchirappalli, Tamil Nadu, NIT Trichy offers undergraduate, postgraduate, and doctoral programs in engineering, science, and management. The institute is known for its excellent academic environment, strong industry connections, and impressive placement record. Spread across 332 acres, the campus provides modern infrastructure including smart classrooms, advanced research labs, a well-stocked library, and comprehensive sports facilities. NIT Trichy consistently ranks among the top 10 engineering institutions in India.',
 '[{"icon":"trophy","text":"Ranked #9 in NIRF 2024 Engineering"},{"icon":"globe","text":"501 in QS World Rankings"},{"icon":"users","text":"8,500+ students enrolled"},{"icon":"building","text":"332-acre scenic campus"},{"icon":"award","text":"10 Distinguished Alumni Awards"}]',
 '{"nirf_2023":10,"nirf_2024":9,"qs_2025":501,"times_2024":10}',
 '[{"year":2024,"award":"Best NIT - NIRF"},{"year":2023,"award":"Excellence in Technical Education - AICTE"},{"year":2023,"award":"Outstanding Research Output - Scopus"}]'),
('cnt00001-0000-0000-0000-000000000004', 'col00001-0000-0000-0000-000000000004',
 'Birla Institute of Technology and Science (BITS) Pilani is a prestigious deemed university established in 1964 by the Birla family. With its main campus in Pilani, Rajasthan, and additional campuses in Goa, Hyderabad, and Dubai, BITS Pilani is known for its Practice School program, industry-oriented curriculum, and strong alumni network. The institute offers integrated first-degree programs, higher degree programs, and PhD programs across engineering, sciences, pharmacy, and management. BITS Pilani has been consistently ranked among the top private engineering institutions in India. The institute is known for its unique BITSAT admission process, rigorous academic standards, and exceptional placement record with students placed in top global companies.',
 '[{"icon":"trophy","text":"Ranked #22 in NIRF 2024 Engineering"},{"icon":"globe","text":"273 in QS World Rankings"},{"icon":"users","text":"10,000+ students across campuses"},{"icon":"building","text":"328-acre Pilani campus"},{"icon":"award","text":"8 Distinguished Alumni Awards"}]',
 '{"nirf_2023":21,"nirf_2024":22,"qs_2025":273,"times_2024":15}',
 '[{"year":2024,"award":"Best Private University - NIRF"},{"year":2023,"award":"Innovation in Education - India Today"},{"year":2023,"award":"Best Campus Placement - Outlook"}]'),
('cnt00001-0000-0000-0000-000000000005', 'col00001-0000-0000-0000-000000000005',
 'Vellore Institute of Technology (VIT) is a prestigious deemed university established in 1984 by Dr. G. Viswanathan in Vellore, Tamil Nadu. VIT is known for its VITEEE entrance examination, industry-aligned curriculum, and strong emphasis on research and innovation. The university offers B.Tech, M.Tech, M.Sc, MBA, MCA, and PhD programs across multiple disciplines. VIT has been consistently ranked among the top private engineering institutions in India and has earned NAAC A++ accreditation. The 350-acre campus features world-class infrastructure including smart classrooms, advanced research labs, a central library with over 2 lakh books, and comprehensive sports facilities. VIT has a strong placement record with students placed in top multinational companies.',
 '[{"icon":"trophy","text":"Ranked #12 in NIRF 2024 Engineering"},{"icon":"globe","text":"601 in QS World Rankings"},{"icon":"users","text":"15,000+ students enrolled"},{"icon":"building","text":"350-acre modern campus"},{"icon":"award","text":"NAAC A++ Accredited"}]',
 '{"nirf_2023":13,"nirf_2024":12,"qs_2025":601,"times_2024":20}',
 '[{"year":2024,"award":"Best Private Engineering Institute - NIRF"},{"year":2023,"award":"Excellence in Research - UI GreenMetric"},{"year":2023,"award":"Best University for Innovation - AICTE"}]');

-- ============================================================
-- 4. INSERT COLLEGE CONTACTS
-- ============================================================

INSERT INTO college_contacts (id, college_id, website_url, email, phone, address, latitude, longitude, pincode, nearest_railway_km, nearest_airport_km) VALUES
('con00001-0000-0000-0000-000000000001', 'col00001-0000-0000-0000-000000000001',
 'https://www.iitb.ac.in', 'dean.acad@iitb.ac.in', '022-25722545',
 'Indian Institute of Technology Bombay, Powai, Mumbai, Maharashtra 400076',
 19.1334, 72.9133, '400076', 8.5, 22.0),
('con00001-0000-0000-0000-000000000002', 'col00001-0000-0000-0000-000000000002',
 'https://www.iitd.ac.in', 'dean.academics@iitd.ac.in', '011-26591111',
 'Indian Institute of Technology Delhi, Hauz Khas, New Delhi 110016',
 28.5450, 77.1926, '110016', 5.0, 12.0),
('con00001-0000-0000-0000-000000000003', 'col00001-0000-0000-0000-000000000003',
 'https://www.nitt.edu', 'dean.academic@nitt.edu', '0431-2503000',
 'National Institute of Technology Tiruchirappalli, Thanjavur Main Road, Tiruchirappalli, Tamil Nadu 620015',
 10.8050, 78.7482, '620015', 15.0, 35.0),
('con00001-0000-0000-0000-000000000004', 'col00001-0000-0000-0000-000000000004',
 'https://www.bits-pilani.ac.in', 'dean.acad@bits-pilani.ac.in', '01596-245073',
 'BITS Pilani, Pilani, Rajasthan 333031',
 28.0339, 75.5900, '333031', 20.0, 220.0),
('con00001-0000-0000-0000-000000000005', 'col00001-0000-0000-0000-000000000005',
 'https://www.vit.ac.in', 'dean.academic@vit.ac.in', '0416-2202111',
 'Vellore Institute of Technology, Vellore, Tamil Nadu 632014',
 12.9690, 79.1559, '632014', 10.0, 130.0);

-- ============================================================
-- 5. INSERT COLLEGE COURSES
-- ============================================================

INSERT INTO college_courses (id, college_id, course_name, course_level, duration_years, total_fee, semester_fee, annual_fee, seats_available, eligibility_criteria, application_fee, emi_available) VALUES
('crs00001-0000-0000-0000-000000000001', 'col00001-0000-0000-0000-000000000001',
 'B.Tech Computer Science and Engineering', 'UG', 4, 800000.00, 100000.00, 200000.00, 150,
 'JEE Advanced qualified, 10+2 with PCM and minimum 75% aggregate', 2000.00, 1),
('crs00001-0000-0000-0000-000000000002', 'col00001-0000-0000-0000-000000000001',
 'B.Tech Electrical Engineering', 'UG', 4, 800000.00, 100000.00, 200000.00, 120,
 'JEE Advanced qualified, 10+2 with PCM and minimum 75% aggregate', 2000.00, 1),
('crs00001-0000-0000-0000-000000000003', 'col00001-0000-0000-0000-000000000001',
 'M.Tech Computer Science and Engineering', 'PG', 2, 400000.00, 100000.00, 200000.00, 60,
 'GATE qualified, B.Tech in CSE or equivalent with minimum 60% aggregate', 2000.00, 0),
('crs00001-0000-0000-0000-000000000004', 'col00001-0000-0000-0000-000000000001',
 'PhD in Computer Science and Engineering', 'PhD', 5, 500000.00, 50000.00, 100000.00, 30,
 'GATE/NET qualified, M.Tech with minimum 65% aggregate or equivalent CGPA', 2000.00, 0),

('crs00001-0000-0000-0000-000000000005', 'col00001-0000-0000-0000-000000000002',
 'B.Tech Computer Science and Engineering', 'UG', 4, 840000.00, 105000.00, 210000.00, 140,
 'JEE Advanced qualified, 10+2 with PCM and minimum 75% aggregate', 2000.00, 1),
('crs00001-0000-0000-0000-000000000006', 'col00001-0000-0000-0000-000000000002',
 'B.Tech Mechanical Engineering', 'UG', 4, 840000.00, 105000.00, 210000.00, 110,
 'JEE Advanced qualified, 10+2 with PCM and minimum 75% aggregate', 2000.00, 1),
('crs00001-0000-0000-0000-000000000007', 'col00001-0000-0000-0000-000000000002',
 'M.Tech Artificial Intelligence', 'PG', 2, 420000.00, 105000.00, 210000.00, 50,
 'GATE qualified, B.Tech in CSE/IT or equivalent with minimum 60% aggregate', 2000.00, 0),
('crs00001-0000-0000-0000-000000000008', 'col00001-0000-0000-0000-000000000002',
 'PhD in Electrical Engineering', 'PhD', 5, 525000.00, 52500.00, 105000.00, 25,
 'GATE/NET qualified, M.Tech with minimum 65% aggregate or equivalent CGPA', 2000.00, 0),

('crs00001-0000-0000-0000-000000000009', 'col00001-0000-0000-0000-000000000003',
 'B.Tech Computer Science and Engineering', 'UG', 4, 600000.00, 75000.00, 150000.00, 180,
 'JEE Main qualified through JoSAA counselling, 10+2 with PCM and minimum 75% aggregate', 1500.00, 1),
('crs00001-0000-0000-0000-000000000010', 'col00001-0000-0000-0000-000000000003',
 'B.Tech Electronics and Communication Engineering', 'UG', 4, 600000.00, 75000.00, 150000.00, 150,
 'JEE Main qualified through JoSAA counselling, 10+2 with PCM and minimum 75% aggregate', 1500.00, 1),
('crs00001-0000-0000-0000-000000000011', 'col00001-0000-0000-0000-000000000003',
 'M.Tech VLSI Design', 'PG', 2, 300000.00, 75000.00, 150000.00, 45,
 'GATE qualified, B.Tech in ECE or equivalent with minimum 60% aggregate', 1500.00, 0),
('crs00001-0000-0000-0000-000000000012', 'col00001-0000-0000-0000-000000000003',
 'MBA in Technology Management', 'PG', 2, 400000.00, 100000.00, 200000.00, 60,
 'CAT/XAT qualified, Bachelor degree with minimum 60% aggregate', 2000.00, 1),

('crs00001-0000-0000-0000-000000000013', 'col00001-0000-0000-0000-000000000004',
 'B.E. Computer Science', 'UG', 4, 1200000.00, 150000.00, 300000.00, 200,
 'BITSAT qualified, 10+2 with PCM and minimum 75% aggregate', 3000.00, 1),
('crs00001-0000-0000-0000-000000000014', 'col00001-0000-0000-0000-000000000004',
 'B.E. Electrical and Electronics Engineering', 'UG', 4, 1200000.00, 150000.00, 300000.00, 150,
 'BITSAT qualified, 10+2 with PCM and minimum 75% aggregate', 3000.00, 1),
('crs00001-0000-0000-0000-000000000015', 'col00001-0000-0000-0000-000000000004',
 'M.Tech Software Systems', 'PG', 2, 600000.00, 150000.00, 300000.00, 50,
 'GATE qualified or BITS HD written test, B.Tech with minimum 60% aggregate', 3000.00, 0),
('crs00001-0000-0000-0000-000000000016', 'col00001-0000-0000-0000-000000000004',
 'PhD in Management', 'PhD', 5, 750000.00, 75000.00, 150000.00, 15,
 'CAT/GMAT qualified, Master degree with minimum 65% aggregate', 3000.00, 0),

('crs00001-0000-0000-0000-000000000017', 'col00001-0000-0000-0000-000000000005',
 'B.Tech Computer Science and Engineering', 'UG', 4, 800000.00, 100000.00, 200000.00, 600,
 'VITEEE qualified, 10+2 with PCM and minimum 60% aggregate', 1350.00, 1),
('crs00001-0000-0000-0000-000000000018', 'col00001-0000-0000-0000-000000000005',
 'B.Tech Electronics and Communication Engineering', 'UG', 4, 700000.00, 87500.00, 175000.00, 400,
 'VITEEE qualified, 10+2 with PCM and minimum 60% aggregate', 1350.00, 1),
('crs00001-0000-0000-0000-000000000019', 'col00001-0000-0000-0000-000000000005',
 'M.Tech Cloud Computing', 'PG', 2, 400000.00, 100000.00, 200000.00, 80,
 'GATE/VITMEE qualified, B.Tech with minimum 60% aggregate', 1350.00, 0),
('crs00001-0000-0000-0000-000000000020', 'col00001-0000-0000-0000-000000000005',
 'PhD in Computer Science and Engineering', 'PhD', 5, 600000.00, 60000.00, 120000.00, 40,
 'GATE/NET qualified, M.Tech with minimum 65% aggregate or equivalent CGPA', 1350.00, 0);

-- ============================================================
-- 6. INSERT COLLEGE ADMISSIONS
-- ============================================================

INSERT INTO college_admissions (id, college_id, admission_process, accepted_exams, admission_start_date, admission_end_date, merit_based, direct_admission, management_quota_seats, nri_quota_seats, lateral_entry_available, application_mode, selection_criteria) VALUES
('adm00001-0000-0000-0000-000000000001', 'col00001-0000-0000-0000-000000000001',
 'Admission to IIT Bombay is through JEE Advanced examination. Students must first qualify JEE Main to be eligible for JEE Advanced. Based on JEE Advanced rank, students participate in JoSAA counselling for seat allocation. The process includes online registration, choice filling, seat acceptance, and document verification at the reporting center.',
 '["JEE Advanced","JEE Main","GATE","CAT","CREST","IISc Entrance"]',
 '2024-04-01', '2024-06-30', 1, 0, 0, 10, 0, 'online',
 'JEE Advanced rank combined with 10+2 marks and category reservation. Final seat allocation through JoSAA counselling rounds.'),
('adm00001-0000-0000-0000-000000000002', 'col00001-0000-0000-0000-000000000002',
 'Admission to IIT Delhi is through JEE Advanced examination. Students must first qualify JEE Main to appear for JEE Advanced. Seat allocation is done through JoSAA counselling based on All India Rank in JEE Advanced. The counselling process includes multiple rounds of seat allocation.',
 '["JEE Advanced","JEE Main","GATE","CAT","IISc Entrance"]',
 '2024-04-01', '2024-06-30', 1, 0, 0, 8, 0, 'online',
 'JEE Advanced rank combined with category and gender-based reservation. Final admission through JoSAA counselling with document verification.'),
('adm00001-0000-0000-0000-000000000003', 'col00001-0000-0000-0000-000000000003',
 'Admission to NIT Trichy is through JEE Main examination followed by JoSAA counselling. Home state quota (50%) and other state quota (50%) seats are allocated separately. Students must register on JoSAA portal and fill their choices based on JEE Main percentile.',
 '["JEE Main","GATE","CAT","NIMCET","IIIT Entrance"]',
 '2024-03-15', '2024-06-30', 1, 0, 0, 5, 1, 'online',
 'JEE Main percentile/All India Rank through JoSAA counselling. Separate counselling for home state and other state quota seats.'),
('adm00001-0000-0000-0000-000000000004', 'col00001-0000-0000-0000-000000000004',
 'Admission to BITS Pilani is through BITSAT (BITS Admission Test) - a computer-based online test. BITS Pilani also accepts SAT scores for international admissions. Admission is based on BITSAT score along with 10+2 marks. The institute conducts its own counselling process.',
 '["BITSAT","SAT","GATE","GRE","CAT"]',
 '2024-01-15', '2024-05-31', 1, 0, 0, 15, 1, 'online',
 'BITSAT score combined with 10+2 PCM marks. Merit list is prepared based on BITSAT score with tie-breaking using 10+2 marks.'),
('adm00001-0000-0000-0000-000000000005', 'col00001-0000-0000-0000-000000000005',
 'Admission to VIT Vellore is through VITEEE (VIT Engineering Entrance Examination) - a computer-based online test conducted across multiple cities in India. Students can appear for VITEEE in multiple slots. Admission is based on VITEEE rank.',
 '["VITEEE","GATE","CAT","XAT","MAT","CMAT"]',
 '2024-01-01', '2024-04-30', 1, 0, 200, 50, 1, 'online',
 'VITEEE All India Rank through counselling. Separate counselling for different VIT campuses. Rank list based on VITEEE score.');

-- ============================================================
-- 7. INSERT COLLEGE PLACEMENTS
-- ============================================================

INSERT INTO college_placements (id, college_id, placement_year, avg_package_lpa, highest_package_lpa, median_package_lpa, placement_percentage, students_placed, international_placements, top_recruiters, sector_wise_json) VALUES
('plc00001-0000-0000-0000-000000000001', 'col00001-0000-0000-0000-000000000001', 2023, 28.50, 214.00, 22.00, 92.5, 1110, 85,
 '["Google","Microsoft","Amazon","Goldman Sachs","Samsung","Qualcomm","Oracle","Flipkart"]',
 '{"IT":40,"Finance":20,"Core":15,"Consulting":15,"Analytics":10}'),
('plc00001-0000-0000-0000-000000000002', 'col00001-0000-0000-0000-000000000001', 2024, 32.00, 250.00, 25.00, 94.0, 1128, 95,
 '["Google","Microsoft","Apple","Amazon","Goldman Sachs","Samsung","Nvidia","Tesla"]',
 '{"IT":42,"Finance":18,"Core":12,"Consulting":18,"Analytics":10}'),
('plc00001-0000-0000-0000-000000000003', 'col00001-0000-0000-0000-000000000002', 2023, 26.00, 200.00, 20.00, 91.0, 1001, 70,
 '["Google","Microsoft","Amazon","Goldman Sachs","McKinsey","Qualcomm","Adobe"]',
 '{"IT":38,"Finance":22,"Core":18,"Consulting":12,"Analytics":10}'),
('plc00001-0000-0000-0000-000000000004', 'col00001-0000-0000-0000-000000000002', 2024, 29.50, 220.00, 23.00, 93.0, 1023, 80,
 '["Google","Microsoft","Apple","Amazon","Bain & Company","McKinsey","Nvidia"]',
 '{"IT":40,"Finance":20,"Core":15,"Consulting":15,"Analytics":10}'),
('plc00001-0000-0000-0000-000000000005', 'col00001-0000-0000-0000-000000000003', 2023, 12.50, 65.00, 10.00, 88.0, 748, 25,
 '["TCS","Infosys","Wipro","Cognizant","Accenture","Amazon","Samsung"]',
 '{"IT":50,"Core":20,"Finance":10,"Analytics":10,"Others":10}'),
('plc00001-0000-0000-0000-000000000006', 'col00001-0000-0000-0000-000000000003', 2024, 14.00, 72.00, 12.00, 90.0, 765, 30,
 '["TCS","Infosys","Wipro","Cognizant","Amazon","Qualcomm","L&T"]',
 '{"IT":48,"Core":22,"Finance":12,"Analytics":10,"Others":8}'),
('plc00001-0000-0000-0000-000000000007', 'col00001-0000-0000-0000-000000000004', 2023, 18.00, 120.00, 15.00, 89.0, 890, 40,
 '["Google","Microsoft","Amazon","Flipkart","Goldman Sachs","DE Shaw","Adobe"]',
 '{"IT":45,"Finance":20,"Core":15,"Consulting":10,"Analytics":10}'),
('plc00001-0000-0000-0000-000000000008', 'col00001-0000-0000-0000-000000000004', 2024, 20.50, 140.00, 17.00, 91.0, 910, 45,
 '["Google","Microsoft","Apple","Amazon","DE Shaw","Goldman Sachs","Nvidia"]',
 '{"IT":43,"Finance":22,"Core":12,"Consulting":13,"Analytics":10}'),
('plc00001-0000-0000-0000-000000000009', 'col00001-0000-0000-0000-000000000005', 2023, 7.50, 52.00, 6.00, 85.0, 12750, 15,
 '["TCS","Infosys","Wipro","Cognizant","HCL","Amazon","Flipkart","Accenture"]',
 '{"IT":55,"Core":18,"Finance":8,"Analytics":10,"Others":9}'),
('plc00001-0000-0000-0000-000000000010', 'col00001-0000-0000-0000-000000000005', 2024, 8.20, 58.00, 6.50, 87.0, 13050, 20,
 '["TCS","Infosys","Wipro","Cognizant","HCL","Amazon","Microsoft","Accenture"]',
 '{"IT":52,"Core":20,"Finance":10,"Analytics":10,"Others":8}');

-- ============================================================
-- 8. INSERT COLLEGE CUTOFFS
-- ============================================================

INSERT INTO college_cutoffs (id, college_id, category, year, opening_rank, closing_rank, round_number, quota, gender) VALUES
('cut00001-0000-0000-0000-000000000001', 'col00001-0000-0000-0000-000000000001', 'General', 2024, 1, 105, 7, 'AI', 'neutral'),
('cut00001-0000-0000-0000-000000000002', 'col00001-0000-0000-0000-000000000001', 'OBC', 2024, 5, 320, 7, 'AI', 'neutral'),
('cut00001-0000-0000-0000-000000000003', 'col00001-0000-0000-0000-000000000001', 'SC', 2024, 10, 680, 7, 'AI', 'neutral'),
('cut00001-0000-0000-0000-000000000004', 'col00001-0000-0000-0000-000000000001', 'ST', 2024, 15, 1200, 7, 'AI', 'neutral'),
('cut00001-0000-0000-0000-000000000005', 'col00001-0000-0000-0000-000000000001', 'EWS', 2024, 20, 180, 7, 'AI', 'neutral'),
('cut00001-0000-0000-0000-000000000006', 'col00001-0000-0000-0000-000000000001', 'General', 2024, 1, 150, 7, 'AI', 'female_only'),
('cut00001-0000-0000-0000-000000000007', 'col00001-0000-0000-0000-000000000001', 'General', 2023, 1, 110, 6, 'AI', 'neutral'),
('cut00001-0000-0000-0000-000000000008', 'col00001-0000-0000-0000-000000000001', 'OBC', 2023, 8, 350, 6, 'AI', 'neutral'),
('cut00001-0000-0000-0000-000000000009', 'col00001-0000-0000-0000-000000000002', 'General', 2024, 2, 115, 7, 'AI', 'neutral'),
('cut00001-0000-0000-0000-000000000010', 'col00001-0000-0000-0000-000000000002', 'OBC', 2024, 10, 350, 7, 'AI', 'neutral'),
('cut00001-0000-0000-0000-000000000011', 'col00001-0000-0000-0000-000000000002', 'SC', 2024, 15, 720, 7, 'AI', 'neutral'),
('cut00001-0000-0000-0000-000000000012', 'col00001-0000-0000-0000-000000000002', 'ST', 2024, 20, 1300, 7, 'AI', 'neutral'),
('cut00001-0000-0000-0000-000000000013', 'col00001-0000-0000-0000-000000000002', 'EWS', 2024, 25, 200, 7, 'AI', 'neutral'),
('cut00001-0000-0000-0000-000000000014', 'col00001-0000-0000-0000-000000000002', 'General', 2023, 2, 120, 6, 'AI', 'neutral'),
('cut00001-0000-0000-0000-000000000015', 'col00001-0000-0000-0000-000000000003', 'General', 2024, 500, 3500, 5, 'AI', 'neutral'),
('cut00001-0000-0000-0000-000000000016', 'col00001-0000-0000-0000-000000000003', 'OBC', 2024, 1500, 9000, 5, 'AI', 'neutral'),
('cut00001-0000-0000-0000-000000000017', 'col00001-0000-0000-0000-000000000003', 'SC', 2024, 3000, 18000, 5, 'AI', 'neutral'),
('cut00001-0000-0000-0000-000000000018', 'col00001-0000-0000-0000-000000000003', 'General', 2024, 800, 5000, 5, 'HS', 'neutral'),
('cut00001-0000-0000-0000-000000000019', 'col00001-0000-0000-0000-000000000003', 'General', 2023, 600, 3800, 5, 'AI', 'neutral'),
('cut00001-0000-0000-0000-000000000020', 'col00001-0000-0000-0000-000000000004', 'General', 2024, 1, 280, 1, 'AI', 'neutral'),
('cut00001-0000-0000-0000-000000000021', 'col00001-0000-0000-0000-000000000004', 'General', 2024, 1, 350, 1, 'AI', 'female_only'),
('cut00001-0000-0000-0000-000000000022', 'col00001-0000-0000-0000-000000000004', 'General', 2023, 1, 300, 1, 'AI', 'neutral'),
('cut00001-0000-0000-0000-000000000023', 'col00001-0000-0000-0000-000000000005', 'General', 2024, 1, 5000, 1, 'AI', 'neutral'),
('cut00001-0000-0000-0000-000000000024', 'col00001-0000-0000-0000-000000000005', 'General', 2024, 1, 8000, 1, 'AI', 'female_only'),
('cut00001-0000-0000-0000-000000000025', 'col00001-0000-0000-0000-000000000005', 'General', 2023, 1, 5500, 1, 'AI', 'neutral');

-- ============================================================
-- 9. INSERT COLLEGE FACULTY
-- ============================================================

INSERT INTO college_faculty (id, college_id, faculty_name, designation, department, qualification, experience_years, specialization) VALUES
('fac00001-0000-0000-0000-000000000001', 'col00001-0000-0000-0000-000000000001',
 'Dr. Rajesh Kumar Sharma', 'Professor and Head', 'Computer Science and Engineering', 'PhD IIT Delhi', 28, 'Artificial Intelligence and Machine Learning'),
('fac00001-0000-0000-0000-000000000002', 'col00001-0000-0000-0000-000000000001',
 'Dr. Priya Venkatesh', 'Associate Professor', 'Electrical Engineering', 'PhD Stanford University', 18, 'VLSI Design and Embedded Systems'),
('fac00001-0000-0000-0000-000000000003', 'col00001-0000-0000-0000-000000000001',
 'Dr. Amitabh Singh', 'Professor', 'Mechanical Engineering', 'PhD MIT', 22, 'Robotics and Automation'),

('fac00001-0000-0000-0000-000000000004', 'col00001-0000-0000-0000-000000000002',
 'Dr. Sanjay Gupta', 'Professor and Dean', 'Computer Science and Engineering', 'PhD IIT Kanpur', 25, 'Data Science and Big Data Analytics'),
('fac00001-0000-0000-0000-000000000005', 'col00001-0000-0000-0000-000000000002',
 'Dr. Meera Deshmukh', 'Associate Professor', 'Electrical Engineering', 'PhD University of California', 16, 'Signal Processing and Communications'),
('fac00001-0000-0000-0000-000000000006', 'col00001-0000-0000-0000-000000000002',
 'Dr. Vikram Malhotra', 'Professor', 'Mechanical Engineering', 'PhD Imperial College London', 20, 'Thermodynamics and Energy Systems'),

('fac00001-0000-0000-0000-000000000007', 'col00001-0000-0000-0000-000000000003',
 'Dr. K. Ramanathan', 'Professor and Head', 'Computer Science and Engineering', 'PhD IIT Madras', 24, 'Network Security and Cryptography'),
('fac00001-0000-0000-0000-000000000008', 'col00001-0000-0000-0000-000000000003',
 'Dr. S. Lakshmi', 'Associate Professor', 'Electronics and Communication Engineering', 'PhD Anna University', 15, 'VLSI and Signal Processing'),
('fac00001-0000-0000-0000-000000000009', 'col00001-0000-0000-0000-000000000003',
 'Dr. M. Karthik', 'Assistant Professor', 'Civil Engineering', 'PhD NIT Trichy', 10, 'Structural Engineering'),

('fac00001-0000-0000-0000-000000000010', 'col00001-0000-0000-0000-000000000004',
 'Dr. Ashok Kumar Bajaj', 'Professor and Dean', 'Computer Science and Information Systems', 'PhD University of Illinois', 22, 'Algorithms and Theoretical Computer Science'),
('fac00001-0000-0000-0000-000000000011', 'col00001-0000-0000-0000-000000000004',
 'Dr. Neeraj Kumar Sharma', 'Associate Professor', 'Electrical and Electronics Engineering', 'PhD Georgia Tech', 17, 'Power Electronics and Drives'),
('fac00001-0000-0000-0000-000000000012', 'col00001-0000-0000-0000-000000000004',
 'Dr. Pooja Rani', 'Assistant Professor', 'Mechanical Engineering', 'PhD BITS Pilani', 8, 'Manufacturing and Industrial Engineering'),

('fac00001-0000-0000-0000-000000000013', 'col00001-0000-0000-0000-000000000005',
 'Dr. K. Sivakumar', 'Professor and Director', 'Computer Science and Engineering', 'PhD IIT Kharagpur', 20, 'Computer Networks and Distributed Systems'),
('fac00001-0000-0000-0000-000000000014', 'col00001-0000-0000-0000-000000000005',
 'Dr. R. Saravanan', 'Associate Professor', 'Electronics and Communication Engineering', 'PhD Anna University', 14, 'Embedded Systems and IoT'),
('fac00001-0000-0000-0000-000000000015', 'col00001-0000-0000-0000-000000000005',
 'Dr. Anitha Rajan', 'Associate Professor', 'Mechanical Engineering', 'PhD VIT University', 12, 'CAD/CAM and Additive Manufacturing');

-- ============================================================
-- 10. INSERT COLLEGE FAQs
-- ============================================================

INSERT INTO college_faqs (id, college_id, question_text, answer_text, category, sort_order) VALUES
('faq00001-0000-0000-0000-000000000001', 'col00001-0000-0000-0000-000000000001',
 'How can I get admission to IIT Bombay?',
 'Admission to IIT Bombay is through JEE Advanced examination. Students must first qualify JEE Main to be eligible for JEE Advanced. Based on JEE Advanced All India Rank, students participate in JoSAA counselling. The institute also accepts GATE scores for M.Tech admissions and CAT scores for MBA.',
 'admission', 1),
('faq00001-0000-0000-0000-000000000002', 'col00001-0000-0000-0000-000000000001',
 'What is the fee structure for B.Tech at IIT Bombay?',
 'The annual fee for B.Tech programs at IIT Bombay is approximately Rs. 2,00,000 per year for general category students. This includes tuition fee, hostel fee, mess charges, and other institutional charges. Fee waivers are available for SC/ST/PwD students and economically weaker sections.',
 'fees', 2),
('faq00001-0000-0000-0000-000000000003', 'col00001-0000-0000-0000-000000000001',
 'What are the placement statistics for IIT Bombay?',
 'IIT Bombay has an excellent placement record with over 94% students placed in 2024. The average package was Rs. 32 LPA and the highest package was Rs. 250 LPA. Top recruiters include Google, Microsoft, Apple, Amazon, Goldman Sachs, and Nvidia.',
 'placements', 3),
('faq00001-0000-0000-0000-000000000004', 'col00001-0000-0000-0000-000000000001',
 'Does IIT Bombay provide hostel facilities?',
 'Yes, IIT Bombay provides hostel accommodation to all students. There are 16 hostels for boys and 3 hostels for girls. Rooms are available in single, double, and triple occupancy. Hostels have common rooms, mess facilities, Wi-Fi, laundry, and 24/7 security.',
 'hostel', 4),

('faq00001-0000-0000-0000-000000000005', 'col00001-0000-0000-0000-000000000002',
 'What is the admission process for IIT Delhi?',
 'IIT Delhi admits students through JEE Advanced examination for B.Tech programs. Students must qualify JEE Main first to appear for JEE Advanced. Admission is through JoSAA counselling based on JEE Advanced rank. For postgraduate programs, GATE and CAT scores are accepted.',
 'admission', 1),
('faq00001-0000-0000-0000-000000000006', 'col00001-0000-0000-0000-000000000002',
 'What is the total fee for B.Tech at IIT Delhi?',
 'The total fee for B.Tech programs at IIT Delhi is approximately Rs. 8,40,000 for the complete 4-year program. Annual fee is around Rs. 2,10,000 which includes tuition, hostel, mess, and other charges. Scholarships and fee waivers are available.',
 'fees', 2),
('faq00001-0000-0000-0000-000000000007', 'col00001-0000-0000-0000-000000000002',
 'How are placements at IIT Delhi?',
 'IIT Delhi has a strong placement record with 93% placement rate in 2024. The average package was Rs. 29.5 LPA and highest package was Rs. 220 LPA. Top recruiters include Google, Microsoft, Apple, Amazon, McKinsey, and Bain & Company.',
 'placements', 3),
('faq00001-0000-0000-0000-000000000008', 'col00001-0000-0000-0000-000000000002',
 'What hostel facilities are available at IIT Delhi?',
 'IIT Delhi has 13 boys hostels and 4 girls hostels with accommodation for all students. Rooms are available in single, double, and triple sharing. Each hostel has a mess, common room, reading room, and Wi-Fi.',
 'hostel', 4),

('faq00001-0000-0000-0000-000000000009', 'col00001-0000-0000-0000-000000000003',
 'How do I get admission to NIT Trichy?',
 'NIT Trichy admits students through JEE Main examination followed by JoSAA counselling. There are two quotas - Home State (50%) and Other State (50%). Students must register on JoSAA portal, fill choices, and participate in counselling rounds based on JEE Main percentile.',
 'admission', 1),
('faq00001-0000-0000-0000-000000000010', 'col00001-0000-0000-0000-000000000003',
 'What is the fee structure at NIT Trichy?',
 'The annual fee for B.Tech at NIT Trichy is approximately Rs. 1,50,000 for general category students. This includes tuition, hostel, mess, and other charges. SC/ST/PwD students have significant fee waivers.',
 'fees', 2),
('faq00001-0000-0000-0000-000000000011', 'col00001-0000-0000-0000-000000000003',
 'What are the placement statistics for NIT Trichy?',
 'NIT Trichy achieved 90% placement rate in 2024 with average package of Rs. 14 LPA and highest package of Rs. 72 LPA. Top recruiters include TCS, Infosys, Wipro, Cognizant, Amazon, Qualcomm, and L&T.',
 'placements', 3),
('faq00001-0000-0000-0000-000000000012', 'col00001-0000-0000-0000-000000000003',
 'Does NIT Trichy have hostel facilities?',
 'Yes, NIT Trichy has separate hostels for boys and girls with capacity for all students. Hostels have single and shared rooms, mess facilities, Wi-Fi, sports facilities, and 24/7 security. Annual hostel fee ranges from Rs. 25,000 to Rs. 45,000.',
 'hostel', 4),

('faq00001-0000-0000-0000-000000000013', 'col00001-0000-0000-0000-000000000004',
 'How can I get admission to BITS Pilani?',
 'BITS Pilani admits students through BITSAT (BITS Admission Test) - a computer-based online test. BITSAT tests Physics, Chemistry, Mathematics, and English. Admission is based on BITSAT score combined with 10+2 marks.',
 'admission', 1),
('faq00001-0000-0000-0000-000000000014', 'col00001-0000-0000-0000-000000000004',
 'What is the fee structure at BITS Pilani?',
 'The annual fee for B.E. programs at BITS Pilani is approximately Rs. 3,00,000. The total fee for the 4-year program is around Rs. 12,00,000. BITS offers merit scholarships and need-based financial aid.',
 'fees', 2),
('faq00001-0000-0000-0000-000000000015', 'col00001-0000-0000-0000-000000000004',
 'How are placements at BITS Pilani?',
 'BITS Pilani has a strong placement record with 91% placement rate in 2024. The average package was Rs. 20.5 LPA and highest package was Rs. 140 LPA. Top recruiters include Google, Microsoft, Apple, Amazon, DE Shaw, and Goldman Sachs.',
 'placements', 3),
('faq00001-0000-0000-0000-000000000016', 'col00001-0000-0000-0000-000000000004',
 'What hostel facilities are available at BITS Pilani?',
 'BITS Pilani has separate hostels for boys and girls on campus. Rooms are available in single, double, and triple occupancy with attached bathrooms. Each hostel has a mess, common room, TV room, and Wi-Fi.',
 'hostel', 4),

('faq00001-0000-0000-0000-000000000017', 'col00001-0000-0000-0000-000000000005',
 'How do I get admission to VIT Vellore?',
 'VIT Vellore admits students through VITEEE (VIT Engineering Entrance Examination) - a computer-based online test conducted across multiple cities. Students can appear for VITEEE in multiple slots. Admission is based on VITEEE All India Rank.',
 'admission', 1),
('faq00001-0000-0000-0000-000000000018', 'col00001-0000-0000-0000-000000000005',
 'What is the fee structure at VIT Vellore?',
 'The annual fee for B.Tech CSE at VIT Vellore is approximately Rs. 2,00,000. For other branches, it ranges from Rs. 1,75,000 to Rs. 2,00,000. Scholarships are available based on VITEEE rank and academic performance.',
 'fees', 2),
('faq00001-0000-0000-0000-000000000019', 'col00001-0000-0000-0000-000000000005',
 'What are the placement statistics for VIT Vellore?',
 'VIT Vellore achieved 87% placement rate in 2024 with average package of Rs. 8.2 LPA and highest package of Rs. 58 LPA. Top recruiters include TCS, Infosys, Wipro, Cognizant, HCL, Amazon, and Microsoft.',
 'placements', 3),
('faq00001-0000-0000-0000-000000000020', 'col00001-0000-0000-0000-000000000005',
 'Does VIT Vellore provide hostel facilities?',
 'Yes, VIT Vellore has 16 hostels (10 for boys, 6 for girls) with accommodation for all students. Rooms are available in 2-bed, 3-bed, and 4-bed configurations. Hostels have mess, laundry, Wi-Fi, gym, and 24/7 security.',
 'hostel', 4);

-- ============================================================
-- 11. INSERT COLLEGE SCHOLARSHIPS
-- ============================================================

INSERT INTO college_scholarships (id, college_id, scholarship_name, scholarship_type, amount, amount_type, eligibility_criteria, renewable) VALUES
('sch00001-0000-0000-0000-000000000001', 'col00001-0000-0000-0000-000000000001',
 'Institute Merit-cum-Means Scholarship', 'merit', 50.00, 'percentage',
 'Top 25% students in each department based on academic performance. Family income should be less than Rs. 5 lakh per annum.', 1),
('sch00001-0000-0000-0000-000000000002', 'col00001-0000-0000-0000-000000000001',
 'SC/ST Scholarship', 'need', 100.00, 'percentage',
 'All SC/ST students with family income less than Rs. 6 lakh per annum. Covers full tuition fee.', 1),
('sch00001-0000-0000-0000-000000000003', 'col00001-0000-0000-0000-000000000001',
 'Aditya Birla Scholarship', 'merit', 150000.00, 'fixed',
 'Top rankers in JEE Advanced admitted to IIT Bombay. Merit-based selection.', 0),

('sch00001-0000-0000-0000-000000000004', 'col00001-0000-0000-0000-000000000002',
 'Institute Free Studentship', 'need', 100.00, 'percentage',
 'Students with family income less than Rs. 4.5 lakh per annum. Full fee waiver including tuition and hostel.', 1),
('sch00001-0000-0000-0000-000000000005', 'col00001-0000-0000-0000-000000000002',
 'AICTE Pragati Scholarship', 'need', 50000.00, 'fixed',
 'Female students admitted to AICTE-approved programs. Family income less than Rs. 8 lakh per annum.', 1),
('sch00001-0000-0000-0000-000000000006', 'col00001-0000-0000-0000-000000000002',
 'Prime Minister Special Scholarship', 'need', 200000.00, 'fixed',
 'Top 1000 rankers in JEE Advanced from economically weaker sections. Covers full education expenses.', 0),

('sch00001-0000-0000-0000-000000000007', 'col00001-0000-0000-0000-000000000003',
 'Institute Merit Scholarship', 'merit', 25000.00, 'fixed',
 'Top 10% students in each semester based on CGPA. Renewable each semester based on performance.', 1),
('sch00001-0000-0000-0000-000000000008', 'col00001-0000-0000-0000-000000000003',
 'Post Matric Scholarship for SC/ST', 'need', 75.00, 'percentage',
 'SC/ST students with family income less than Rs. 6 lakh per annum. 75% fee reimbursement.', 1),
('sch00001-0000-0000-0000-000000000009', 'col00001-0000-0000-0000-000000000003',
 'Central Sector Scholarship', 'merit', 20000.00, 'fixed',
 'Students who are in top 1 percentile of board exams. Family income less than Rs. 8 lakh per annum.', 1),

('sch00001-0000-0000-0000-000000000010', 'col00001-0000-0000-0000-000000000004',
 'BITS Merit Scholarship', 'merit', 80.00, 'percentage',
 'Top 5% students in BITSAT. Covers 80% tuition fee. Renewable based on CGPA >= 8.0.', 1),
('sch00001-0000-0000-0000-000000000011', 'col00001-0000-0000-0000-000000000004',
 'BITS Need-Based Financial Aid', 'need', 50.00, 'percentage',
 'Students from economically weaker sections with family income less than Rs. 8 lakh per annum.', 1),
('sch00001-0000-0000-0000-000000000012', 'col00001-0000-0000-0000-000000000004',
 'Tata Trust Scholarship', 'merit', 100000.00, 'fixed',
 'Top performing students in first year. Based on academic merit and need.', 0),

('sch00001-0000-0000-0000-000000000013', 'col00001-0000-0000-0000-000000000005',
 'VIT Merit Scholarship', 'merit', 100.00, 'percentage',
 'VITEEE rank holders in top 100. Full tuition fee waiver. Renewable with CGPA >= 9.0.', 1),
('sch00001-0000-0000-0000-000000000014', 'col00001-0000-0000-0000-000000000005',
 'VIT Sports Scholarship', 'sports', 50.00, 'percentage',
 'Students with national/state level sports achievements. 50% fee waiver.', 1),
('sch00001-0000-0000-0000-000000000015', 'col00001-0000-0000-0000-000000000005',
 'SC/ST Fee Reimbursement', 'need', 100.00, 'percentage',
 'SC/ST students with family income less than Rs. 6 lakh per annum. Full fee reimbursement from Tamil Nadu government.', 1);

-- ============================================================
-- 12. INSERT COLLEGE INFRASTRUCTURE
-- ============================================================

INSERT INTO college_infrastructure (id, college_id, library, library_books_count, sports_facilities, labs, auditorium, auditorium_capacity, cafeteria, wifi, wifi_speed_mbps, medical_facility, transport, ev_charging, solar_power) VALUES
('inf00001-0000-0000-0000-000000000001', 'col00001-0000-0000-0000-000000000001',
 1, 500000, '["Cricket Ground","Football Field","Basketball Court","Tennis Court","Swimming Pool","Athletic Track","Gymnasium","Badminton Court","Volleyball Court","Squash Court"]',
 '["AI Research Lab","VLSI Lab","Robotics Lab","Central Computing Facility","Nanotechnology Lab","Biotech Lab","Material Science Lab","Energy Research Lab"]',
 1, 1500, 1, 1, 1000, 1, 1, 1, 1),
('inf00001-0000-0000-0000-000000000002', 'col00001-0000-0000-0000-000000000002',
 1, 450000, '["Cricket Ground","Football Field","Basketball Court","Tennis Court","Swimming Pool","Athletic Track","Gymnasium","Badminton Court","Hockey Field"]',
 '["AI/ML Lab","Supercomputing Center","Nanoscience Lab","Biotech Lab","Innovation Center","Quantum Computing Lab","Robotics Workshop"]',
 1, 1200, 1, 1, 800, 1, 1, 1, 1),
('inf00001-0000-0000-0000-000000000003', 'col00001-0000-0000-0000-000000000003',
 1, 300000, '["Cricket Ground","Football Field","Basketball Court","Tennis Court","Volleyball Court","Athletic Track","Gymnasium","Badminton Court","Table Tennis"]',
 '["Central Computing Lab","Electronics Lab","Mechanical Workshop","Civil Engineering Lab","Chemical Lab","Biotech Lab"]',
 1, 800, 1, 1, 500, 1, 1, 0, 0),
('inf00001-0000-0000-0000-000000000004', 'col00001-0000-0000-0000-000000000004',
 1, 350000, '["Cricket Ground","Football Field","Basketball Court","Tennis Court","Swimming Pool","Gymnasium","Badminton Court","Squash Court","Horse Riding"]',
 '["Innovation Lab","Robotics Lab","VLSI Lab","Central Computing Facility","Aerospace Lab","Biotech Lab","Data Science Center"]',
 1, 1000, 1, 1, 700, 1, 1, 1, 1),
('inf00001-0000-0000-0000-000000000005', 'col00001-0000-0000-0000-000000000005',
 1, 250000, '["Cricket Ground","Football Field","Basketball Court","Tennis Court","Swimming Pool","Athletic Track","Gymnasium","Badminton Court","Volleyball Court","Chess Room"]',
 '["Central Computing Lab","IoT Lab","AI/ML Lab","Robotics Lab","Biotech Lab","Chemical Lab","Physics Lab","Electronics Lab"]',
 1, 1200, 1, 1, 600, 1, 1, 1, 1);

-- ============================================================
-- 13. INSERT COLLEGE HOSTELS
-- ============================================================

INSERT INTO college_hostels (id, college_id, hostel_available, hostel_type, hostel_capacity, hostel_fee_annual, mess_available, mess_type, ac_available, room_types, security_features, laundry_available) VALUES
('hos00001-0000-0000-0000-000000000001', 'col00001-0000-0000-0000-000000000001',
 1, 'both', 8000, 45000.00, 1, 'both', 0,
 '["Single Occupancy","Double Sharing","Triple Sharing"]',
 '["CCTV Surveillance","24/7 Security Guard","Biometric Entry","Fire Safety Systems"]', 1),
('hos00001-0000-0000-0000-000000000002', 'col00001-0000-0000-0000-000000000002',
 1, 'both', 7500, 50000.00, 1, 'both', 0,
 '["Single Occupancy","Double Sharing","Triple Sharing"]',
 '["CCTV Surveillance","24/7 Security Guard","Biometric Entry","Fire Safety Systems"]', 1),
('hos00001-0000-0000-0000-000000000003', 'col00001-0000-0000-0000-000000000003',
 1, 'both', 5000, 35000.00, 1, 'both', 0,
 '["Double Sharing","Triple Sharing","Four Sharing"]',
 '["CCTV Surveillance","24/7 Security Guard","ID Card Entry","Fire Safety Systems"]', 1),
('hos00001-0000-0000-0000-000000000004', 'col00001-0000-0000-0000-000000000004',
 1, 'both', 6000, 55000.00, 1, 'both', 1,
 '["Single AC Room","Double Sharing AC","Triple Sharing Non-AC"]',
 '["CCTV Surveillance","24/7 Security Guard","Biometric Entry","Fire Safety Systems","Access Control"]', 1),
('hos00001-0000-0000-0000-000000000005', 'col00001-0000-0000-0000-000000000005',
 1, 'both', 12000, 40000.00, 1, 'both', 0,
 '["2-Bed AC Room","3-Bed Non-AC Room","4-Bed Non-AC Room"]',
 '["CCTV Surveillance","24/7 Security Guard","Biometric Entry","Fire Safety Systems","Visitor Management"]', 1);

-- ============================================================
-- 14. INSERT COLLEGE ACCREDITATIONS
-- ============================================================

INSERT INTO college_accreditations (id, college_id, accreditation_body, accreditation_grade, accreditation_year, accreditation_valid_until) VALUES
('acc00001-0000-0000-0000-000000000001', 'col00001-0000-0000-0000-000000000001', 'NAAC', 'A++', 2022, '2027-03-31'),
('acc00001-0000-0000-0000-000000000002', 'col00001-0000-0000-0000-000000000001', 'NBA', 'Accredited', 2021, '2026-06-30'),
('acc00001-0000-0000-0000-000000000003', 'col00001-0000-0000-0000-000000000001', 'UGC', 'Autonomous', 2018, '2028-12-31'),
('acc00001-0000-0000-0000-000000000004', 'col00001-0000-0000-0000-000000000002', 'NAAC', 'A++', 2023, '2028-03-31'),
('acc00001-0000-0000-0000-000000000005', 'col00001-0000-0000-0000-000000000002', 'NBA', 'Accredited', 2022, '2027-06-30'),
('acc00001-0000-0000-0000-000000000006', 'col00001-0000-0000-0000-000000000002', 'UGC', 'Autonomous', 2019, '2029-12-31'),
('acc00001-0000-0000-0000-000000000007', 'col00001-0000-0000-0000-000000000003', 'NAAC', 'A+', 2021, '2026-03-31'),
('acc00001-0000-0000-0000-000000000008', 'col00001-0000-0000-0000-000000000003', 'NBA', 'Accredited', 2020, '2025-06-30'),
('acc00001-0000-0000-0000-000000000009', 'col00001-0000-0000-0000-000000000003', 'UGC', 'Autonomous', 2017, '2027-12-31'),
('acc00001-0000-0000-0000-000000000010', 'col00001-0000-0000-0000-000000000004', 'NAAC', 'A', 2022, '2027-03-31'),
('acc00001-0000-0000-0000-000000000011', 'col00001-0000-0000-0000-000000000004', 'NBA', 'Accredited', 2021, '2026-06-30'),
('acc00001-0000-0000-0000-000000000012', 'col00001-0000-0000-0000-000000000004', 'UGC', 'Deemed University', 2016, '2026-12-31'),
('acc00001-0000-0000-0000-000000000013', 'col00001-0000-0000-0000-000000000005', 'NAAC', 'A++', 2023, '2028-03-31'),
('acc00001-0000-0000-0000-000000000014', 'col00001-0000-0000-0000-000000000005', 'NBA', 'Accredited', 2022, '2027-06-30'),
('acc00001-0000-0000-0000-000000000015', 'col00001-0000-0000-0000-000000000005', 'UGC', 'Deemed University', 2019, '2029-12-31');

-- ============================================================
-- 15. INSERT COLLEGE MEDIA
-- ============================================================

INSERT INTO college_media (id, college_id, image_type, image_url, caption, sort_order) VALUES
('med00001-0000-0000-0000-000000000001', 'col00001-0000-0000-0000-000000000001', 'campus', 'https://images.unsplash.com/photo-1562774053-701939374585?w=800', 'IIT Bombay Main Building', 1),
('med00001-0000-0000-0000-000000000002', 'col00001-0000-0000-0000-000000000001', 'lab', 'https://images.unsplash.com/photo-1581091226825-a6a2a5aee158?w=800', 'Advanced Research Laboratory', 2),
('med00001-0000-0000-0000-000000000003', 'col00001-0000-0000-0000-000000000001', 'classroom', 'https://images.unsplash.com/photo-1580537659466-0a9bfa916a54?w=800', 'Smart Classroom with Modern Amenities', 3),
('med00001-0000-0000-0000-000000000004', 'col00001-0000-0000-0000-000000000001', 'event', 'https://images.unsplash.com/photo-1540575467063-178a50c2df87?w=800', 'Annual Tech Festival Techfest', 4),
('med00001-0000-0000-0000-000000000005', 'col00001-0000-0000-0000-000000000002', 'campus', 'https://images.unsplash.com/photo-1541339907198-e08756dedf3f?w=800', 'IIT Delhi Campus View', 1),
('med00001-0000-0000-0000-000000000006', 'col00001-0000-0000-0000-000000000002', 'lab', 'https://images.unsplash.com/photo-1532094349884-543bc11b234d?w=800', 'Supercomputing Center', 2),
('med00001-0000-0000-0000-000000000007', 'col00001-0000-0000-0000-000000000002', 'classroom', 'https://images.unsplash.com/photo-1509062522246-3755977927d7?w=800', 'Lecture Hall Complex', 3),
('med00001-0000-0000-0000-000000000008', 'col00001-0000-0000-0000-000000000002', 'event', 'https://images.unsplash.com/photo-1523580494863-6f3031224c94?w=800', 'Annual Cultural Festival Rendezvous', 4),
('med00001-0000-0000-0000-000000000009', 'col00001-0000-0000-0000-000000000003', 'campus', 'https://images.unsplash.com/photo-1519457431-44ccd64a579b?w=800', 'NIT Trichy Campus', 1),
('med00001-0000-0000-0000-000000000010', 'col00001-0000-0000-0000-000000000003', 'lab', 'https://images.unsplash.com/photo-1581092160607-ee22621dd758?w=800', 'Central Electronics Lab', 2),
('med00001-0000-0000-0000-000000000011', 'col00001-0000-0000-0000-000000000003', 'classroom', 'https://images.unsplash.com/photo-1497633762265-9d179a990aa6?w=800', 'Auditorium and Seminar Hall', 3),
('med00001-0000-0000-0000-000000000012', 'col00001-0000-0000-0000-000000000003', 'event', 'https://images.unsplash.com/photo-1492684223066-81342ee5ff30?w=800', 'Annual Cultural Festival Festember', 4),
('med00001-0000-0000-0000-000000000013', 'col00001-0000-0000-0000-000000000004', 'campus', 'https://images.unsplash.com/photo-1523050854058-8df90110c476?w=800', 'BITS Pilani Campus Heritage Building', 1),
('med00001-0000-0000-0000-000000000014', 'col00001-0000-0000-0000-000000000004', 'lab', 'https://images.unsplash.com/photo-1581092160562-40aa08e78837?w=800', 'Innovation and Incubation Lab', 2),
('med00001-0000-0000-0000-000000000015', 'col00001-0000-0000-0000-000000000004', 'classroom', 'https://images.unsplash.com/photo-1571260899304-425eee4c7efc?w=800', 'Modern Classroom with Projector', 3),
('med00001-0000-0000-0000-000000000016', 'col00001-0000-0000-0000-000000000004', 'event', 'https://images.unsplash.com/photo-1505373877841-8d25f7d46678?w=800', 'Annual Cultural Festival BOSM', 4),
('med00001-0000-0000-0000-000000000017', 'col00001-0000-0000-0000-000000000005', 'campus', 'https://images.unsplash.com/photo-1498243691581-b145c3f54a5a?w=800', 'VIT Vellore Main Campus', 1),
('med00001-0000-0000-0000-000000000018', 'col00001-0000-0000-0000-000000000005', 'lab', 'https://images.unsplash.com/photo-1576086213369-97a306d36557?w=800', 'IoT and AI Research Lab', 2),
('med00001-0000-0000-0000-000000000019', 'col00001-0000-0000-0000-000000000005', 'classroom', 'https://images.unsplash.com/photo-1524178232363-1fb2b075b655?w=800', 'Smart Classroom with Digital Board', 3),
('med00001-0000-0000-0000-000000000020', 'col00001-0000-0000-0000-000000000005', 'event', 'https://images.unsplash.com/photo-1475721027785-f74eccf877e2?w=800', 'Annual Cultural Festival Riviera', 4);

-- ============================================================
-- 16. INSERT DUMMY USERS
-- ============================================================

INSERT INTO users (id, full_name, email, phone, password_hash, auth_provider, status, email_verified, phone_verified) VALUES
('usr00001-0000-0000-0000-000000000001', 'Rahul Sharma', 'rahul.sharma@example.com', '9876543210', '.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'email', 'active', 1, 1),
('usr00001-0000-0000-0000-000000000002', 'Priya Patel', 'priya.patel@example.com', '9876543211', '.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'email', 'active', 1, 1),
('usr00001-0000-0000-0000-000000000003', 'Amit Kumar', 'amit.kumar@example.com', '9876543212', '.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'email', 'active', 1, 1),
('usr00001-0000-0000-0000-000000000004', 'Sneha Reddy', 'sneha.reddy@example.com', '9876543213', '.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'email', 'active', 1, 1),
('usr00001-0000-0000-0000-000000000005', 'Vikram Singh', 'vikram.singh@example.com', '9876543214', '.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'email', 'active', 1, 1);

-- ============================================================
-- 17. INSERT REVIEWS (2 per college, 10 total)
-- ============================================================

INSERT INTO reviews (id, user_id, college_id, overall_rating, academics_rating, faculty_rating, placements_rating, infrastructure_rating, hostel_rating, social_life_rating, food_rating, review_title, review_body, pros, cons, batch_year, helpful_votes, moderation_status, is_verified_alumnus, ai_sentiment) VALUES
('rev00001-0000-0000-0000-000000000001', 'usr00001-0000-0000-0000-000000000001', 'col00001-0000-0000-0000-000000000001',
 4.8, 5.0, 4.5, 5.0, 4.5, 4.0, 5.0, 3.5,
 'Best Engineering College in India',
 'IIT Bombay provides an unmatched academic environment with world-class faculty and research opportunities. The campus life is vibrant with numerous clubs and festivals. Placement opportunities are exceptional.',
 'Excellent faculty, world-class research facilities, amazing placements, vibrant campus life',
 'Highly competitive environment, food quality could be better, limited hostel rooms for final year',
 2023, 45, 'approved', 1, 'positive'),
('rev00001-0000-0000-0000-000000000002', 'usr00001-0000-0000-0000-000000000002', 'col00001-0000-0000-0000-000000000001',
 4.5, 4.5, 4.5, 4.5, 4.5, 4.0, 5.0, 3.5,
 'Amazing Experience at IIT Bombay',
 'My four years at IIT Bombay were transformative. The exposure to diverse cultures, cutting-edge research, and industry connections helped shape my career. Techfest and Mood Indigo are highlight events.',
 'Great alumni network, research opportunities, industry exposure, cultural diversity',
 'Academic pressure is intense, hostel rooms are shared, mess food is average',
 2022, 38, 'approved', 1, 'positive'),

('rev00001-0000-0000-0000-000000000003', 'usr00001-0000-0000-0000-000000000003', 'col00001-0000-0000-0000-000000000002',
 4.6, 5.0, 4.5, 4.5, 4.5, 4.0, 4.5, 3.5,
 'Excellent Academic Environment',
 'IIT Delhi offers a perfect blend of academics and extracurricular activities. The faculty is approachable and research-oriented. Location in Delhi provides excellent industry connectivity.',
 'Prime location in Delhi, strong research culture, excellent faculty, great industry connections',
 'Limited campus space, high cost of living in Delhi, competitive atmosphere',
 2023, 35, 'approved', 1, 'positive'),
('rev00001-0000-0000-0000-000000000004', 'usr00001-0000-0000-0000-000000000004', 'col00001-0000-0000-0000-000000000002',
 4.4, 4.5, 4.0, 4.5, 4.5, 4.0, 5.0, 3.5,
 'Transformative Learning Experience',
 'IIT Delhi provided me with the platform to explore my interests in AI and machine learning. The innovation and incubation center helped me start my own venture during college years.',
 'Innovation ecosystem, startup culture, diverse student body, central location',
 'Overcrowded campus, limited sports facilities, high expectations',
 2023, 42, 'approved', 1, 'positive'),

('rev00001-0000-0000-0000-000000000005', 'usr00001-0000-0000-0000-000000000005', 'col00001-0000-0000-0000-000000000003',
 4.3, 4.5, 4.0, 4.0, 4.0, 4.0, 4.5, 4.0,
 'Great Value for Money',
 'NIT Trichy offers excellent education at affordable fees. The campus is beautiful and the faculty is dedicated. Placements have improved significantly over the years.',
 'Affordable fees, beautiful campus, improving placements, strong alumni network',
 'Remote location, limited industry exposure, infrastructure needs updating',
 2023, 28, 'approved', 1, 'positive'),
('rev00001-0000-0000-0000-000000000006', 'usr00001-0000-0000-0000-000000000001', 'col00001-0000-0000-0000-000000000003',
 4.2, 4.0, 4.0, 4.0, 4.0, 4.0, 4.5, 4.0,
 'Wonderful Campus Life',
 'NIT Trichy has a wonderful campus with lush greenery and excellent sports facilities. The institute focuses on overall development of students. Hostel life is memorable.',
 'Beautiful campus, sports facilities, friendly faculty, holistic development',
 'Placement packages lower than IITs, infrastructure needs modernization',
 2022, 32, 'approved', 1, 'positive'),

('rev00001-0000-0000-0000-000000000007', 'usr00001-0000-0000-0000-000000000003', 'col00001-0000-0000-0000-000000000004',
 4.5, 5.0, 4.5, 4.5, 4.5, 4.5, 4.5, 4.0,
 'Industry-Ready Education',
 'BITS Pilani Practice School program is unique and provides real-world industry experience during academics. The curriculum is industry-aligned and graduates are immediately productive.',
 'Practice School program, industry-aligned curriculum, excellent placements, strong alumni network',
 'High fees, competitive atmosphere, limited cultural diversity',
 2023, 38, 'approved', 1, 'positive'),
('rev00001-0000-0000-0000-000000000008', 'usr00001-0000-0000-0000-000000000004', 'col00001-0000-0000-0000-000000000004',
 4.3, 4.5, 4.0, 4.5, 4.5, 4.0, 4.5, 4.0,
 'Excellent Return on Investment',
 'BITS Pilani provides excellent ROI despite higher fees compared to government colleges. The Practice School program ensures students are job-ready. Strong industry connections.',
 'ROI despite high fees, strong industry connections, practice school exposure, campus infrastructure',
 'High cost, demanding curriculum, limited hostel availability during peak season',
 2023, 30, 'approved', 1, 'positive'),

('rev00001-0000-0000-0000-000000000009', 'usr00001-0000-0000-0000-000000000005', 'col00001-0000-0000-0000-000000000005',
 4.3, 4.5, 4.0, 4.0, 4.5, 4.0, 4.5, 4.0,
 'Best Private University for Engineering',
 'VIT Vellore offers excellent infrastructure and a diverse student community. The VITEEE exam is well-organized and the counselling process is transparent. Good placement record.',
 'Modern infrastructure, transparent admission process, good placements, diverse student body',
 'Strict attendance rules, limited research opportunities, crowded campus',
 2023, 35, 'approved', 1, 'positive'),
('rev00001-0000-0000-0000-000000000010', 'usr00001-0000-0000-0000-000000000001', 'col00001-0000-0000-0000-000000000005',
 4.2, 4.0, 4.0, 4.0, 4.5, 4.0, 4.5, 4.0,
 'Value for Money Private College',
 'VIT Vellore provides good quality education at reasonable fees compared to other private colleges. Campus is well-maintained and faculty is supportive. Good for students seeking private college education.',
 'Reasonable fees, well-maintained campus, supportive faculty, good placement cell',
 'Some departments need more research focus, strict rules, limited freedom',
 2022, 25, 'approved', 1, 'positive');

-- ============================================================
-- 18. INSERT RANKINGS (NIRF 2023 and 2024)
-- ============================================================

INSERT INTO rankings (ranking_body, ranking_year, category, college_id, rank_position, score, previous_year_rank, rank_delta) VALUES
('NIRF', 2023, 'Engineering', 'col00001-0000-0000-0000-000000000001', 3, 87.5, 4, 1),
('NIRF', 2024, 'Engineering', 'col00001-0000-0000-0000-000000000001', 3, 88.2, 3, 0),
('NIRF', 2023, 'Engineering', 'col00001-0000-0000-0000-000000000002', 2, 88.0, 2, 0),
('NIRF', 2024, 'Engineering', 'col00001-0000-0000-0000-000000000002', 2, 89.1, 2, 0),
('NIRF', 2023, 'Engineering', 'col00001-0000-0000-0000-000000000003', 10, 72.5, 11, 1),
('NIRF', 2024, 'Engineering', 'col00001-0000-0000-0000-000000000003', 9, 73.8, 10, 1),
('NIRF', 2023, 'Engineering', 'col00001-0000-0000-0000-000000000004', 21, 65.2, 22, 1),
('NIRF', 2024, 'Engineering', 'col00001-0000-0000-0000-000000000004', 22, 64.8, 21, -1),
('NIRF', 2023, 'Engineering', 'col00001-0000-0000-0000-000000000005', 13, 69.5, 15, 2),
('NIRF', 2024, 'Engineering', 'col00001-0000-0000-0000-000000000005', 12, 70.2, 13, 1);

-- ============================================================
-- 19. INSERT QUESTIONS (Q&A) - 8 questions across colleges
-- ============================================================

INSERT INTO questions (id, question_text, question_category, related_college_id, asked_by, views, answer_count, is_featured, status) VALUES
('que00001-0000-0000-0000-000000000001',
 'What is the JEE Advanced cutoff rank for B.Tech CSE at IIT Bombay for General category?',
 'admission', 'col00001-0000-0000-0000-000000000001', 'usr00001-0000-0000-0000-000000000001',
 1250, 3, 1, 'answered'),
('que00001-0000-0000-0000-000000000002',
 'How are the hostel facilities at IIT Delhi for first year B.Tech students?',
 'hostel', 'col00001-0000-0000-0000-000000000002', 'usr00001-0000-0000-0000-000000000002',
 890, 2, 0, 'answered'),
('que00001-0000-0000-0000-000000000003',
 'What is the placement scenario for B.Tech ECE at NIT Trichy?',
 'placements', 'col00001-0000-0000-0000-000000000003', 'usr00001-0000-0000-0000-000000000003',
 756, 2, 0, 'answered'),
('que00001-0000-0000-0000-000000000004',
 'Is BITS Pilani worth the high fee compared to IITs?',
 'fees', 'col00001-0000-0000-0000-000000000004', 'usr00001-0000-0000-0000-000000000004',
 1580, 3, 1, 'answered'),
('que00001-0000-0000-0000-000000000005',
 'How is the VITEEE exam pattern and difficulty level?',
 'exams', 'col00001-0000-0000-0000-000000000005', 'usr00001-0000-0000-0000-000000000005',
 1020, 2, 0, 'answered'),
('que00001-0000-0000-0000-000000000006',
 'What is the average package for B.Tech CSE at IIT Bombay in 2024?',
 'placements', 'col00001-0000-0000-0000-000000000001', 'usr00001-0000-0000-0000-000000000002',
 2100, 2, 1, 'answered'),
('que00001-0000-0000-0000-000000000007',
 'What documents are needed for JoSAA counselling at IITs?',
 'admission', 'col00001-0000-0000-0000-000000000001', 'usr00001-0000-0000-0000-000000000003',
 980, 2, 0, 'answered'),
('que00001-0000-0000-0000-000000000008',
 'Does BITS Pilani offer lateral entry admission for Diploma holders?',
 'admission', 'col00001-0000-0000-0000-000000000004', 'usr00001-0000-0000-0000-000000000001',
 650, 2, 0, 'answered');

-- ============================================================
-- 20. INSERT ANSWERS (2-3 answers per question)
-- ============================================================

INSERT INTO answers (id, question_id, answer_text, answered_by, is_expert_answer, is_verified_alumnus, upvotes, is_accepted) VALUES
-- Answers for Question 1 (IIT Bombay CSE cutoff)
('ans00001-0000-0000-0000-000000000001', 'que00001-0000-0000-0000-000000000001',
 'For General category, the JEE Advanced closing rank for B.Tech CSE at IIT Bombay was around 105 in 2024 (Round 7). The opening rank was 1. For female-only seats, the closing rank was around 150.',
 'usr00001-0000-0000-0000-000000000004', 1, 0, 45, 1),
('ans00001-0000-0000-0000-000000000002', 'que00001-0000-0000-0000-000000000001',
 'The cutoff varies each year based on difficulty level and number of candidates. In 2023, the closing rank was 110 for General category. Check JoSAA website for exact cutoffs after each round.',
 'usr00001-0000-0000-0000-000000000005', 0, 1, 32, 0),
('ans00001-0000-0000-0000-000000000003', 'que00001-0000-0000-0000-000000000001',
 'For OBC category, the closing rank is around 320, for SC it is around 680, for ST around 1200, and for EWS around 180. These are approximate values for 2024.',
 'usr00001-0000-0000-0000-000000000002', 0, 0, 28, 0),

-- Answers for Question 2 (IIT Delhi hostel)
('ans00001-0000-0000-0000-000000000004', 'que00001-0000-0000-0000-000000000002',
 'IIT Delhi provides hostel accommodation to all first-year students. There are separate hostels for boys and girls. Rooms are generally double or triple sharing. Each hostel has a mess, common room, and Wi-Fi facility.',
 'usr00001-0000-0000-0000-000000000003', 0, 1, 38, 1),
('ans00001-0000-0000-0000-000000000005', 'que00001-0000-0000-0000-000000000002',
 'First year students get triple sharing rooms. From second year onwards, based on CGPA, you can get double or single rooms. Hostel fee is included in the annual fee.',
 'usr00001-0000-0000-0000-000000000005', 0, 0, 22, 0),

-- Answers for Question 3 (NIT Trichy ECE placements)
('ans00001-0000-0000-0000-000000000006', 'que00001-0000-0000-0000-000000000003',
 'NIT Trichy ECE department has good placements. In 2024, the average package for ECE was around Rs. 12 LPA with highest package of Rs. 45 LPA. Top recruiters include Qualcomm, Texas Instruments, and Samsung.',
 'usr00001-0000-0000-0000-000000000004', 0, 1, 30, 1),
('ans00001-0000-0000-0000-000000000007', 'que00001-0000-0000-0000-000000000003',
 'Around 85% of ECE students get placed through campus recruitment. Many students also go for higher studies at IITs and foreign universities. The placement cell provides training and mock interviews.',
 'usr00001-0000-0000-0000-000000000001', 0, 0, 18, 0),

-- Answers for Question 4 (BITS Pilani ROI)
('ans00001-0000-0000-0000-000000000008', 'que00001-0000-0000-0000-000000000004',
 'BITS Pilani offers excellent ROI. The Practice School program provides 6 months of paid internship, which offsets a significant portion of the fees. Average placement of Rs. 20+ LPA justifies the investment.',
 'usr00001-0000-0000-0000-000000000005', 1, 1, 52, 1),
('ans00001-0000-0000-0000-000000000009', 'que00001-0000-0000-0000-000000000004',
 'BITS Pilani offers merit scholarships covering up to 80% tuition for top BITSAT performers. The need-based financial aid also helps economically weaker students. The industry connections ensure good placements.',
 'usr00001-0000-0000-0000-000000000002', 0, 0, 35, 0),
('ans00001-0000-0000-0000-000000000010', 'que00001-0000-0000-0000-000000000004',
 'While IITs have lower fees, BITS Pilani compensates with the Practice School program, international exposure, and industry-aligned curriculum. Many BITS graduates end up in top companies alongside IIT graduates.',
 'usr00001-0000-0000-0000-000000000003', 0, 0, 28, 0),

-- Answers for Question 5 (VITEEE exam pattern)
('ans00001-0000-0000-0000-000000000011', 'que00001-0000-0000-0000-000000000005',
 'VITEEE is a computer-based online test with 125 multiple choice questions. Duration is 2.5 hours. Physics (35), Chemistry (35), Maths/Biology (40), Aptitude (15). Difficulty level is moderate.',
 'usr00001-0000-0000-0000-000000000001', 0, 1, 40, 1),
('ans00001-0000-0000-0000-000000000012', 'que00001-0000-0000-0000-000000000005',
 'VITEEE is easier compared to JEE Main. Focus on NCERT concepts and practice previous year papers. The exam is conducted in multiple slots from April to May. You can choose your preferred slot.',
 'usr00001-0000-0000-0000-000000000004', 0, 0, 25, 0),

-- Answers for Question 6 (IIT Bombay average package)
('ans00001-0000-0000-0000-000000000013', 'que00001-0000-0000-0000-000000000006',
 'The average package for B.Tech CSE at IIT Bombay in 2024 was Rs. 32 LPA. The highest package was Rs. 250 LPA from an international company. Over 94% of students were placed.',
 'usr00001-0000-0000-0000-000000000004', 0, 1, 55, 1),
('ans00001-0000-0000-0000-000000000014', 'que00001-0000-0000-0000-000000000006',
 'IIT Bombay CSE placements are among the best in India. Top recruiters include Google, Microsoft, Apple, Amazon, and Goldman Sachs. Many students also receive pre-placement offers from summer internships.',
 'usr00001-0000-0000-0000-000000000005', 0, 0, 38, 0),

-- Answers for Question 7 (JoSAA counselling documents)
('ans00001-0000-0000-0000-000000000015', 'que00001-0000-0000-0000-000000000007',
 'Documents needed for JoSAA counselling: JEE Advanced admit card, Class 10 and 12 mark sheets, category certificate (if applicable), photo ID, passport size photographs, and medical fitness certificate.',
 'usr00001-0000-0000-0000-000000000004', 0, 1, 42, 1),
('ans00001-0000-0000-0000-000000000016', 'que00001-0000-0000-0000-000000000007',
 'Keep original documents and self-attested copies ready. You also need to carry the seat acceptance fee payment receipt. Visit the reporting center with all documents on the specified date.',
 'usr00001-0000-0000-0000-000000000002', 0, 0, 30, 0),

-- Answers for Question 8 (BITS Pilani lateral entry)
('ans00001-0000-0000-0000-000000000017', 'que00001-0000-0000-0000-000000000008',
 'BITS Pilani does offer lateral entry admission for B.E. programs. Diploma holders and B.Sc graduates can apply through BITS lateral entry test. The duration is reduced to 3 years for lateral entry students.',
 'usr00001-0000-0000-0000-000000000003', 0, 1, 35, 1),
('ans00001-0000-0000-0000-000000000018', 'que00001-0000-0000-0000-000000000008',
 'Lateral entry admission at BITS is competitive. You need to clear the BITS lateral entry test and meet the minimum percentage criteria in your diploma/bachelor degree. Check the BITS website for exact eligibility.',
 'usr00001-0000-0000-0000-000000000005', 0, 0, 22, 0);

SET FOREIGN_KEY_CHECKS = 1;
