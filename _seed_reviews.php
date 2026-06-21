<?php
$pdo = new PDO('mysql:host=localhost;dbname=admission;charset=utf8mb4','root','');

function uuid() {
    return sprintf('%08x-%04x-%04x-%04x-%012x', mt_rand(0,0xffffffff), mt_rand(0,0xffff), mt_rand(0,0xffff), mt_rand(0,0xffff), mt_rand(0,0xffffffffffff));
}

$users = [
    'usr00001-0000-0000-0000-000000000001',
    'usr00001-0000-0000-0000-000000000002',
    'usr00001-0000-0000-0000-000000000003',
    'usr00001-0000-0000-0000-000000000004',
    'usr00001-0000-0000-0000-000000000005',
    '64e20c70-d8d7-402f-a700-53c759a659d4',
    '8b0478e7-602f-11f1-9ea0-a0510b1a7448',
];

$reviews = [];

$reviews['col-iitb-0001'][] = [
    'title' => 'Exceptional Institute with World-Class Faculty',
    'body' => 'IIT Bombay has been an incredible experience. The faculty is top-notch, and the curriculum is designed to challenge and inspire. The campus life is vibrant with numerous clubs and festivals.',
    'pros' => 'World-class faculty, excellent research opportunities, amazing campus',
    'cons' => 'Heavy academic workload, limited hostel rooms',
    'rating' => 4.8, 'academics' => 4.9, 'faculty' => 5.0, 'placements' => 4.7, 'infra' => 4.5, 'hostel' => 4.0, 'social' => 4.8, 'food' => 3.5,
    'batch' => 2023, 'course' => 'B.Tech Computer Science and Engineering', 'verified' => 1
];
$reviews['col-iitb-0001'][] = [
    'title' => 'Best Placement Scene in India',
    'body' => 'Placements at IIT Bombay are unmatched. Every year, top companies visit the campus and offer lucrative packages. The average package is around 22 LPA with highest going above 1 Cr.',
    'pros' => 'Amazing placements, great peer group, startup culture',
    'cons' => 'ACB attendance, hectic schedule',
    'rating' => 4.7, 'academics' => 4.8, 'faculty' => 4.5, 'placements' => 5.0, 'infra' => 4.5, 'hostel' => 4.0, 'social' => 4.5, 'food' => 3.5,
    'batch' => 2022, 'course' => 'B.Tech Computer Science and Engineering', 'verified' => 1
];
$reviews['col-iitb-0001'][] = [
    'title' => 'Research Paradise',
    'body' => 'If you are interested in research, IIT Bombay is the place to be. The labs are well-equipped, and professors are always encouraging students to take up research projects.',
    'pros' => 'Research infrastructure, funded projects, global collaborations',
    'cons' => 'Bureaucracy in admin, outdated electives sometimes',
    'rating' => 4.9, 'academics' => 5.0, 'faculty' => 5.0, 'placements' => 4.5, 'infra' => 5.0, 'hostel' => 4.0, 'social' => 4.5, 'food' => 3.5,
    'batch' => 2021, 'course' => 'M.Tech Computer Science and Engineering', 'verified' => 1
];
$reviews['col-iitb-0001'][] = [
    'title' => 'Great Campus but Hostels Need Work',
    'body' => 'The academics and placements are stellar but the hostel facilities are aging. Food quality in mess varies. The campus itself is beautiful with Powai Lake nearby.',
    'pros' => 'Campus beauty, location in Mumbai, brand value',
    'cons' => 'Hostel maintenance, mess food variety, internet speed',
    'rating' => 4.5, 'academics' => 4.8, 'faculty' => 4.5, 'placements' => 4.7, 'infra' => 4.0, 'hostel' => 3.5, 'social' => 4.8, 'food' => 3.0,
    'batch' => 2023, 'course' => 'B.Tech Electrical Engineering', 'verified' => 0
];
$reviews['col-iitb-0001'][] = [
    'title' => 'Transformative MBA Experience',
    'body' => 'The SJMSOM MBA program is rigorous and industry-oriented. Great mix of case studies, live projects, and industry interactions. The alumni network is incredibly strong.',
    'pros' => 'Industry connections, case-based learning, brand value',
    'cons' => 'Expensive compared to IIMs, fewer MBA-specific companies',
    'rating' => 4.6, 'academics' => 4.5, 'faculty' => 4.5, 'placements' => 4.5, 'infra' => 4.5, 'hostel' => 4.0, 'social' => 4.5, 'food' => 3.5,
    'batch' => 2022, 'course' => 'PhD Engineering', 'verified' => 1
];

$reviews['col-iitd-0002'][] = [
    'title' => 'Premier Institute in Heart of Delhi',
    'body' => 'IIT Delhi offers the perfect blend of academics and city life. Being in Delhi, there are endless opportunities for internships, cultural exposure, and networking.',
    'pros' => 'Location advantage, strong alumni network, diverse culture',
    'cons' => 'Small campus, crowded hostels, pollution',
    'rating' => 4.7, 'academics' => 4.8, 'faculty' => 4.7, 'placements' => 4.7, 'infra' => 4.0, 'hostel' => 3.5, 'social' => 4.8, 'food' => 3.5,
    'batch' => 2023, 'course' => 'B.Tech Computer Science and Engineering', 'verified' => 1
];
$reviews['col-iitd-0002'][] = [
    'title' => 'Innovation Hub with Startup Support',
    'body' => 'IIT Delhi has a thriving startup ecosystem. The incubation cell supports student ventures with funding and mentorship. Many unicorns have roots here.',
    'pros' => 'Startup support, location in Delhi, industry connect',
    'cons' => 'Competition is intense, limited green spaces',
    'rating' => 4.8, 'academics' => 4.8, 'faculty' => 4.7, 'placements' => 4.8, 'infra' => 4.0, 'hostel' => 3.5, 'social' => 4.8, 'food' => 3.5,
    'batch' => 2022, 'course' => 'B.Tech Computer Science and Engineering', 'verified' => 1
];
$reviews['col-iitd-0002'][] = [
    'title' => 'Strong Academics, Average Hostels',
    'body' => 'Academic rigour is excellent. Faculty is approachable and knowledgeable. But hostels are cramped and food could be better. Still, the brand opens many doors.',
    'pros' => 'Academic quality, brand recognition, placement opportunities',
    'cons' => 'Hostel conditions, mess food, small campus',
    'rating' => 4.5, 'academics' => 4.8, 'faculty' => 4.5, 'placements' => 4.7, 'infra' => 4.0, 'hostel' => 3.5, 'social' => 4.5, 'food' => 3.0,
    'batch' => 2023, 'course' => 'B.Tech Electrical Engineering', 'verified' => 0
];
$reviews['col-iitd-0002'][] = [
    'title' => 'Great for Research and Higher Studies',
    'body' => 'The research culture at IIT Delhi is phenomenal. Many students go on to top PhD programs globally. The faculty actively publishes in top conferences and journals.',
    'pros' => 'Research output, global collaborations, library resources',
    'cons' => 'Admin processes slow, some labs outdated',
    'rating' => 4.6, 'academics' => 4.8, 'faculty' => 4.6, 'placements' => 4.5, 'infra' => 4.0, 'hostel' => 3.5, 'social' => 4.5, 'food' => 3.5,
    'batch' => 2021, 'course' => 'M.Tech Computer Science and Engineering', 'verified' => 1
];

$reviews['col-iitm-0003'][] = [
    'title' => 'Best IIT in India, Period',
    'body' => 'IIT Madras consistently ranks #1 in NIRF for a reason. The campus is lush green, the faculty is outstanding, and the academic atmosphere is unparalleled. The Chennai weather takes some getting used to.',
    'pros' => '#1 NIRF ranking, beautiful campus, excellent faculty',
    'cons' => 'Chennai heat, strict hostel rules, language barrier sometimes',
    'rating' => 4.9, 'academics' => 5.0, 'faculty' => 5.0, 'placements' => 4.8, 'infra' => 5.0, 'hostel' => 4.5, 'social' => 4.5, 'food' => 4.0,
    'batch' => 2023, 'course' => 'B.Tech Computer Science and Engineering', 'verified' => 1
];
$reviews['col-iitm-0003'][] = [
    'title' => 'Paradise for Tech Enthusiasts',
    'body' => 'From Shaastra to Techfest, the technical culture is amazing. Research Park provides real-world exposure. The startup ecosystem within campus is growing rapidly.',
    'pros' => 'Tech festivals, research park, industry collaborations',
    'cons' => 'Rainy campus can get muddy, limited nightlife',
    'rating' => 4.8, 'academics' => 5.0, 'faculty' => 4.8, 'placements' => 4.8, 'infra' => 5.0, 'hostel' => 4.5, 'social' => 4.5, 'food' => 4.0,
    'batch' => 2022, 'course' => 'M.Tech Computer Science and Engineering', 'verified' => 1
];
$reviews['col-iitm-0003'][] = [
    'title' => 'Well-Rounded Experience',
    'body' => 'IIT Madras gave me everything - academics, research, cultural activities, and lifelong friendships. The alumni network is incredibly supportive and active worldwide.',
    'pros' => 'Holistic development, alumni network, campus culture',
    'cons' => 'Academic pressure can be intense, limited girls hostel space',
    'rating' => 4.9, 'academics' => 5.0, 'faculty' => 5.0, 'placements' => 4.8, 'infra' => 4.5, 'hostel' => 4.0, 'social' => 4.8, 'food' => 4.0,
    'batch' => 2021, 'course' => 'B.Tech Computer Science and Engineering', 'verified' => 1
];
$reviews['col-iitm-0003'][] = [
    'title' => 'Outstanding Education Quality',
    'body' => 'The curriculum is well-structured and updated regularly. Professors are approachable and genuinely interested in student growth. Lab facilities are world-class.',
    'pros' => 'Curriculum quality, lab infrastructure, professor mentorship',
    'cons' => 'Heavy coursework, competitive environment',
    'rating' => 4.8, 'academics' => 4.9, 'faculty' => 5.0, 'placements' => 4.7, 'infra' => 4.5, 'hostel' => 4.0, 'social' => 4.3, 'food' => 3.8,
    'batch' => 2023, 'course' => 'B.Tech Electrical Engineering', 'verified' => 0
];

$reviews['col-iitk-0004'][] = [
    'title' => 'Academic Excellence with Open Culture',
    'body' => 'IIT Kanpur is known for its academic rigor and open culture. The freedom to explore diverse fields while maintaining academic standards is what sets it apart.',
    'pros' => 'Academic freedom, strong CS department, beautiful campus',
    'cons' => 'Remote location, limited city connectivity',
    'rating' => 4.7, 'academics' => 4.8, 'faculty' => 4.7, 'placements' => 4.7, 'infra' => 4.5, 'hostel' => 4.0, 'social' => 4.5, 'food' => 3.5,
    'batch' => 2023, 'course' => 'B.Tech Computer Science and Engineering', 'verified' => 1
];
$reviews['col-iitk-0004'][] = [
    'title' => 'Strong CS Culture',
    'body' => 'The computer science department at IIT Kanpur is legendary. The coding culture is vibrant with regular hackathons and programming contests. Many alumni are at top tech companies.',
    'pros' => 'CS reputation, coding culture, research opportunities',
    'cons' => 'Other departments get less attention, weather extremes',
    'rating' => 4.8, 'academics' => 4.9, 'faculty' => 4.8, 'placements' => 4.8, 'infra' => 4.5, 'hostel' => 4.0, 'social' => 4.5, 'food' => 3.5,
    'batch' => 2022, 'course' => 'B.Tech Computer Science and Engineering', 'verified' => 1
];
$reviews['col-iitk-0004'][] = [
    'title' => 'Good But Could Be Better',
    'body' => 'Academics are great but infrastructure needs modernization. The campus is green and spacious. Placements are excellent across all branches.',
    'pros' => 'Campus size, academic quality, placement record',
    'cons' => 'Infrastructure aging, limited food options, remote location',
    'rating' => 4.5, 'academics' => 4.7, 'faculty' => 4.5, 'placements' => 4.7, 'infra' => 4.0, 'hostel' => 4.0, 'social' => 4.3, 'food' => 3.0,
    'batch' => 2023, 'course' => 'B.Tech Mechanical Engineering', 'verified' => 0
];
$reviews['col-iitk-0004'][] = [
    'title' => 'Best for Mathematics and Computing',
    'body' => 'If you are into mathematics and computing, IIT Kanpur is the best choice. The department has excellent faculty and research groups working on cutting-edge problems.',
    'pros' => 'Math department strength, research groups, academic depth',
    'cons' => 'Less industry exposure compared to Bombay and Delhi',
    'rating' => 4.7, 'academics' => 4.8, 'faculty' => 4.8, 'placements' => 4.5, 'infra' => 4.5, 'hostel' => 4.0, 'social' => 4.5, 'food' => 3.5,
    'batch' => 2021, 'course' => 'M.Tech Computer Science and Engineering', 'verified' => 1
];

$reviews['col-iitkgp-0005'][] = [
    'title' => 'Massive Campus, Endless Opportunities',
    'body' => 'IIT Kharagpur has the largest campus among all IITs. The variety of courses, clubs, and activities is unmatched. The brand value of the oldest IIT opens many doors.',
    'pros' => 'Largest campus, most diverse courses, iconic brand',
    'cons' => 'Very hot and humid weather, remote location',
    'rating' => 4.6, 'academics' => 4.7, 'faculty' => 4.5, 'placements' => 4.6, 'infra' => 4.5, 'hostel' => 4.0, 'social' => 4.8, 'food' => 3.5,
    'batch' => 2023, 'course' => 'B.Tech Computer Science and Engineering', 'verified' => 1
];
$reviews['col-iitkgp-0005'][] = [
    'title' => 'Great Campus Life',
    'body' => 'The campus life at KGP is legendary. From Kshitij to Spring Fest, there is always something happening. The food courts, night canteens, and hostel culture create unforgettable memories.',
    'pros' => 'Campus festivals, social life, food options, alumni network',
    'cons' => 'Humidity, distance from Kolkata, some buildings old',
    'rating' => 4.7, 'academics' => 4.5, 'faculty' => 4.5, 'placements' => 4.6, 'infra' => 4.0, 'hostel' => 4.0, 'social' => 5.0, 'food' => 4.0,
    'batch' => 2022, 'course' => 'B.Tech Computer Science and Engineering', 'verified' => 1
];
$reviews['col-iitkgp-0005'][] = [
    'title' => 'Solid Academics, Growing Infrastructure',
    'body' => 'The academics are solid and placements have improved significantly over the years. The new buildings and labs are modern, though some old infrastructure still exists.',
    'pros' => 'Placement growth, new infrastructure, diverse student body',
    'cons' => 'Old hostel blocks, inconsistent faculty quality',
    'rating' => 4.5, 'academics' => 4.6, 'faculty' => 4.3, 'placements' => 4.6, 'infra' => 4.2, 'hostel' => 3.8, 'social' => 4.8, 'food' => 3.5,
    'batch' => 2023, 'course' => 'B.Tech Mechanical Engineering', 'verified' => 0
];
$reviews['col-iitkgp-0005'][] = [
    'title' => 'Oldest IIT, Rich Legacy',
    'body' => 'Being the oldest IIT, Kharagpur has a rich legacy and one of the strongest alumni networks globally. The brand value combined with solid academics makes it a top choice.',
    'pros' => 'Legacy, alumni network, brand recognition, diverse courses',
    'cons' => 'Needs more modern labs, campus too spread out',
    'rating' => 4.6, 'academics' => 4.5, 'faculty' => 4.5, 'placements' => 4.6, 'infra' => 4.2, 'hostel' => 4.0, 'social' => 4.8, 'food' => 3.5,
    'batch' => 2022, 'course' => 'B.Tech Computer Science and Engineering', 'verified' => 1
];

$reviews['col-nitt-0006'][] = [
    'title' => 'Best NIT in India',
    'body' => 'NIT Trichy lives up to its reputation as the best NIT. The academics, placements, and campus culture are excellent. A great alternative to IITs with much lower fees.',
    'pros' => 'Best NIT ranking, excellent placements, affordable fees',
    'cons' => 'Trichy heat, limited city life, strict rules',
    'rating' => 4.5, 'academics' => 4.5, 'faculty' => 4.5, 'placements' => 4.7, 'infra' => 4.5, 'hostel' => 4.0, 'social' => 4.0, 'food' => 3.5,
    'batch' => 2023, 'course' => 'B.Tech Computer Science and Engineering', 'verified' => 1
];
$reviews['col-nitt-0006'][] = [
    'title' => 'Value for Money Education',
    'body' => 'NIT Trichy offers world-class education at a fraction of IIT fees. The ROI is amazing. Companies like Google, Microsoft visit regularly for placements.',
    'pros' => 'Affordable fees, strong placements, brand value',
    'cons' => 'Campus infrastructure needs upgrade, limited sports facilities',
    'rating' => 4.6, 'academics' => 4.5, 'faculty' => 4.5, 'placements' => 4.8, 'infra' => 4.2, 'hostel' => 4.0, 'social' => 4.0, 'food' => 3.5,
    'batch' => 2022, 'course' => 'B.Tech Computer Science and Engineering', 'verified' => 1
];
$reviews['col-nitt-0006'][] = [
    'title' => 'Good Overall Experience',
    'body' => 'NIT Trichy provides a good overall experience with decent academics, active clubs, and solid placements. The campus is well-maintained and the faculty is supportive.',
    'pros' => 'Supportive faculty, active clubs, good placement support',
    'cons' => 'Trichy location not very exciting, limited entertainment',
    'rating' => 4.4, 'academics' => 4.5, 'faculty' => 4.3, 'placements' => 4.7, 'infra' => 4.2, 'hostel' => 4.0, 'social' => 3.8, 'food' => 3.5,
    'batch' => 2023, 'course' => 'B.Tech Electrical and Electronics Engineering', 'verified' => 0
];

$reviews['col-nitk-0007'][] = [
    'title' => 'Beautiful Campus by the Sea',
    'body' => 'NITK Surathkal has a stunning campus right next to the Arabian Sea. The combination of beach access and quality education makes it unique among all engineering colleges.',
    'pros' => 'Beach campus, beautiful scenery, strong CS department',
    'cons' => 'Humidity, snakes on campus sometimes, limited public transport',
    'rating' => 4.5, 'academics' => 4.5, 'faculty' => 4.5, 'placements' => 4.6, 'infra' => 4.5, 'hostel' => 4.0, 'social' => 4.5, 'food' => 3.5,
    'batch' => 2023, 'course' => 'B.Tech Computer Science and Engineering', 'verified' => 1
];
$reviews['col-nitk-0007'][] = [
    'title' => 'Strong Technical Culture',
    'body' => 'NITK has a thriving technical culture with clubs like IEEE, ACM, and numerous technical festivals. The coding culture has improved tremendously and placements are on par with many IITs.',
    'pros' => 'Technical clubs, improving placements, campus beauty',
    'cons' => 'City Mangalore is average, limited intern opportunities locally',
    'rating' => 4.5, 'academics' => 4.5, 'faculty' => 4.4, 'placements' => 4.6, 'infra' => 4.5, 'hostel' => 4.0, 'social' => 4.5, 'food' => 3.5,
    'batch' => 2022, 'course' => 'B.Tech Computer Science and Engineering', 'verified' => 1
];
$reviews['col-nitk-0007'][] = [
    'title' => 'Decent College, Great Location',
    'body' => 'The college provides a good education and the campus location is simply unbeatable. The beach is a huge stress buster. Placements are good especially for CS and IT branches.',
    'pros' => 'Campus location, decent placements, growing reputation',
    'cons' => 'Some departments lack focus, limited industry visits',
    'rating' => 4.3, 'academics' => 4.4, 'faculty' => 4.3, 'placements' => 4.5, 'infra' => 4.5, 'hostel' => 4.0, 'social' => 4.5, 'food' => 3.5,
    'batch' => 2023, 'course' => 'B.Tech Electronics and Communication Engineering', 'verified' => 0
];

$reviews['col-bits-0008'][] = [
    'title' => 'Premium Private Engineering College',
    'body' => 'BITS Pilani offers a unique practice school model where students get mandatory industry exposure. The campus is excellent and the peer group is incredibly talented.',
    'pros' => 'Practice school model, great peer group, modern campus',
    'cons' => 'Very expensive, tough grading, high competition',
    'rating' => 4.6, 'academics' => 4.7, 'faculty' => 4.5, 'placements' => 4.7, 'infra' => 5.0, 'hostel' => 4.5, 'social' => 4.5, 'food' => 4.0,
    'batch' => 2023, 'course' => 'B.E. Computer Science', 'verified' => 1
];
$reviews['col-bits-0008'][] = [
    'title' => 'Industry-Ready Graduates',
    'body' => 'The practice school program at BITS is a game changer. Students spend 6 to 8 months at companies during their degree, making them industry-ready from day one. Companies love BITS graduates.',
    'pros' => 'Practice school, industry readiness, BITS brand',
    'cons' => 'Fees are very high, academic pressure immense',
    'rating' => 4.7, 'academics' => 4.7, 'faculty' => 4.5, 'placements' => 4.8, 'infra' => 5.0, 'hostel' => 4.5, 'social' => 4.5, 'food' => 4.0,
    'batch' => 2022, 'course' => 'B.E. Computer Science', 'verified' => 1
];
$reviews['col-bits-0008'][] = [
    'title' => 'Expensive But Worth It',
    'body' => 'Yes, BITS is expensive, but the ROI is excellent. The exposure, peer learning, and placement opportunities make it worth the investment. Campus life is vibrant and modern.',
    'pros' => 'ROI, modern facilities, global exposure, alumni network',
    'cons' => 'Heavy fees, hostel occupancy issues, academic load',
    'rating' => 4.5, 'academics' => 4.6, 'faculty' => 4.4, 'placements' => 4.7, 'infra' => 5.0, 'hostel' => 4.0, 'social' => 4.5, 'food' => 4.0,
    'batch' => 2023, 'course' => 'B.E. Computer Science', 'verified' => 0
];
$reviews['col-bits-0008'][] = [
    'title' => 'Pilani Campus is Beautiful',
    'body' => 'The Pilani campus is massive and beautiful with modern infrastructure. The desert landscape is unique. BITS has invested heavily in state-of-the-art labs and facilities.',
    'pros' => 'Campus infrastructure, modern labs, sports facilities',
    'cons' => 'Pilani is in Rajasthan desert, extreme weather',
    'rating' => 4.5, 'academics' => 4.6, 'faculty' => 4.4, 'placements' => 4.7, 'infra' => 5.0, 'hostel' => 4.5, 'social' => 4.3, 'food' => 4.0,
    'batch' => 2022, 'course' => 'B.E. Electrical and Electronics Engineering', 'verified' => 1
];

$reviews['col-iima-0009'][] = [
    'title' => 'Gold Standard of Management Education',
    'body' => 'IIMA is the undisputed leader of management education in India. The case-study pedagogy, world-class faculty, and incredible peer learning make it transformative.',
    'pros' => 'Case method, brand value, alumni network, global recognition',
    'cons' => 'Intense academic pressure, competitive environment',
    'rating' => 4.9, 'academics' => 5.0, 'faculty' => 5.0, 'placements' => 5.0, 'infra' => 4.5, 'hostel' => 4.0, 'social' => 4.5, 'food' => 3.5,
    'batch' => 2022, 'course' => 'MBA Post Graduate Programme in Management', 'verified' => 1
];
$reviews['col-iima-0009'][] = [
    'title' => 'Life-Changing Two Years',
    'body' => 'The PGP at IIMA is a life-changing experience. The rigor, the case studies, the sleepless nights - all build you into a complete professional. Placements are exceptional with median package over 35 LPA.',
    'pros' => 'Transformative experience, top recruiters, global brand',
    'cons' => 'Extremely hectic, no work-life balance, expensive canteen',
    'rating' => 4.9, 'academics' => 5.0, 'faculty' => 5.0, 'placements' => 5.0, 'infra' => 4.5, 'hostel' => 4.0, 'social' => 4.0, 'food' => 3.5,
    'batch' => 2023, 'course' => 'MBA Post Graduate Programme in Management', 'verified' => 1
];
$reviews['col-iima-0009'][] = [
    'title' => 'World-Class in Every Aspect',
    'body' => 'From faculty to curriculum to placements, IIMA delivers world-class quality. The alumni network spans Fortune 500 companies and leading startups globally.',
    'pros' => 'Global recognition, alumni power, placement packages',
    'cons' => 'Overwhelming competition, WAC assignments never end',
    'rating' => 4.8, 'academics' => 5.0, 'faculty' => 5.0, 'placements' => 5.0, 'infra' => 4.5, 'hostel' => 4.0, 'social' => 4.0, 'food' => 3.5,
    'batch' => 2021, 'course' => 'MBA Post Graduate Programme in Management', 'verified' => 1
];
$reviews['col-iima-0009'][] = [
    'title' => 'Best Return on Investment',
    'body' => 'Despite high fees, IIMA offers the best ROI in management education. The average salary is among the highest globally. The brand opens doors everywhere.',
    'pros' => 'ROI, placement averages, brand prestige',
    'cons' => 'High fees for non-sponsored students, intense pressure',
    'rating' => 4.8, 'academics' => 4.8, 'faculty' => 5.0, 'placements' => 5.0, 'infra' => 4.5, 'hostel' => 4.0, 'social' => 4.0, 'food' => 3.5,
    'batch' => 2022, 'course' => 'PGPX Executive MBA', 'verified' => 1
];

$reviews['col-iimb-0010'][] = [
    'title' => 'Excellent Management Education',
    'body' => 'IIM Bangalore offers a world-class MBA experience with its unique blend of academics, industry interaction, and beautiful green campus. The location in Bangalore adds to the tech startup ecosystem exposure.',
    'pros' => 'Bangalore location, tech ecosystem, campus beauty',
    'cons' => 'Fees very high, academic pressure, limited hostels',
    'rating' => 4.8, 'academics' => 4.8, 'faculty' => 4.8, 'placements' => 4.8, 'infra' => 4.5, 'hostel' => 4.0, 'social' => 4.5, 'food' => 3.5,
    'batch' => 2023, 'course' => 'MBA Post Graduate Programme in Management', 'verified' => 1
];
$reviews['col-iimb-0010'][] = [
    'title' => 'Tech-Savvy Management School',
    'body' => 'Being in Bangalore, IIMB has strong connections with the tech industry. Many tech founders and CXOs are regular guest speakers. The startup culture here is unmatched.',
    'pros' => 'Tech industry connections, Bangalore ecosystem, diverse cohort',
    'cons' => 'Heavy coursework, expensive living, hostel distance',
    'rating' => 4.8, 'academics' => 4.8, 'faculty' => 4.8, 'placements' => 4.8, 'infra' => 4.5, 'hostel' => 4.0, 'social' => 4.5, 'food' => 3.5,
    'batch' => 2022, 'course' => 'MBA Post Graduate Programme in Management', 'verified' => 1
];
$reviews['col-iimb-0010'][] = [
    'title' => 'Solid Brand, Great Placements',
    'body' => 'IIM Bangalore delivers on its promise of quality education and excellent placements. The PE/VC and consulting cohorts are particularly strong here.',
    'pros' => 'Placement diversity, consulting/PE dominance, brand strength',
    'cons' => 'Grade deflation, competitive peer group, work overload',
    'rating' => 4.7, 'academics' => 4.8, 'faculty' => 4.7, 'placements' => 4.8, 'infra' => 4.5, 'hostel' => 4.0, 'social' => 4.5, 'food' => 3.5,
    'batch' => 2023, 'course' => 'MBA Post Graduate Programme in Management', 'verified' => 0
];

$reviews['col-aiims-0011'][] = [
    'title' => 'The Mecca of Medical Education',
    'body' => 'AIIMS Delhi is the dream of every medical aspirant in India. The quality of clinical exposure, faculty, and patient diversity is unmatched. Studying here is both a privilege and a responsibility.',
    'pros' => 'Best clinical exposure, top faculty, immense patient load',
    'cons' => 'Extreme academic pressure, limited social life, strict schedule',
    'rating' => 4.9, 'academics' => 5.0, 'faculty' => 5.0, 'placements' => 5.0, 'infra' => 4.5, 'hostel' => 4.0, 'social' => 3.5, 'food' => 3.5,
    'batch' => 2022, 'course' => 'MBBS Bachelor of Medicine and Surgery', 'verified' => 1
];
$reviews['col-aiims-0011'][] = [
    'title' => 'Unmatched Clinical Training',
    'body' => 'The clinical training at AIIMS is the best in India. You get to see cases that other medical colleges might see in years. The OPD handles thousands of patients daily, giving unparalleled hands-on experience.',
    'pros' => 'Clinical variety, hands-on training, research opportunities',
    'cons' => 'Workload is intense, hostel food poor, limited leisure time',
    'rating' => 4.9, 'academics' => 5.0, 'faculty' => 5.0, 'placements' => 5.0, 'infra' => 4.5, 'hostel' => 4.0, 'social' => 3.5, 'food' => 3.0,
    'batch' => 2021, 'course' => 'MBBS Bachelor of Medicine and Surgery', 'verified' => 1
];
$reviews['col-aiims-0011'][] = [
    'title' => 'Dream College but Demanding',
    'body' => 'Getting into AIIMS was the hardest part. Living up to its standards is equally challenging. The professors are brilliant but expect nothing less than excellence. The patient exposure is truly world-class.',
    'pros' => 'Brand value, patient diversity, academic rigor',
    'cons' => 'Mental health challenges, sleep deprivation, limited campus area',
    'rating' => 4.8, 'academics' => 5.0, 'faculty' => 5.0, 'placements' => 4.5, 'infra' => 4.0, 'hostel' => 3.5, 'social' => 3.5, 'food' => 3.0,
    'batch' => 2023, 'course' => 'MBBS Bachelor of Medicine and Surgery', 'verified' => 1
];
$reviews['col-aiims-0011'][] = [
    'title' => 'Best for Postgraduate Medical Education',
    'body' => 'AIIMS MD/MS programs are the most sought after in India. The training is rigorous and prepares you for any challenge in clinical practice. The research output is phenomenal.',
    'pros' => 'PG reputation, research output, super-specialty exposure',
    'cons' => 'Bond period, immense workload, limited stipend',
    'rating' => 4.8, 'academics' => 5.0, 'faculty' => 5.0, 'placements' => 4.5, 'infra' => 4.0, 'hostel' => 4.0, 'social' => 3.5, 'food' => 3.0,
    'batch' => 2022, 'course' => 'MD Doctor of Medicine', 'verified' => 1
];
$reviews['col-aiims-0011'][] = [
    'title' => 'Proud to Be an AIIMSite',
    'body' => 'The three letters AIIMS carry immense pride and responsibility. The alumni network is the strongest in Indian healthcare. Training here transforms you into a confident, competent physician.',
    'pros' => 'Alumni network, brand recognition, career opportunities',
    'cons' => 'Social life limited, academic stress, old hostels',
    'rating' => 4.9, 'academics' => 5.0, 'faculty' => 5.0, 'placements' => 5.0, 'infra' => 4.0, 'hostel' => 3.5, 'social' => 4.0, 'food' => 3.0,
    'batch' => 2021, 'course' => 'MD Doctor of Medicine', 'verified' => 1
];
$reviews['col-aiims-0011'][] = [
    'title' => 'World-Class Research Environment',
    'body' => 'AIIMS has excellent research facilities and encourages students to participate in cutting-edge medical research. The publications in top journals speak for themselves.',
    'pros' => 'Research infrastructure, publication opportunities, grants',
    'cons' => 'Research competes with clinical duties, long hours',
    'rating' => 4.7, 'academics' => 5.0, 'faculty' => 5.0, 'placements' => 4.5, 'infra' => 4.5, 'hostel' => 4.0, 'social' => 3.5, 'food' => 3.0,
    'batch' => 2020, 'course' => 'PhD Medical Sciences', 'verified' => 1
];

$reviews['col-nlsiu-0012'][] = [
    'title' => 'Harvard of Indian Legal Education',
    'body' => 'NLSIU Bangalore is the Harvard of India when it comes to legal education. The five-year integrated program is rigorous and transformative. Moot courts and legal aid clinics provide practical exposure.',
    'pros' => 'Legal education brand, moot court culture, Bangalore location',
    'cons' => 'Extremely competitive, limited campus size, academic pressure',
    'rating' => 4.8, 'academics' => 5.0, 'faculty' => 5.0, 'placements' => 4.8, 'infra' => 4.0, 'hostel' => 4.0, 'social' => 4.5, 'food' => 3.5,
    'batch' => 2022, 'course' => 'BA LLB (Hons)', 'verified' => 1
];
$reviews['col-nlsiu-0012'][] = [
    'title' => 'Legal Excellence Redefined',
    'body' => 'NLSIU produces the finest lawyers, judges, and legal professionals in India. The curriculum is comprehensive and the faculty includes practicing advocates and renowned legal scholars.',
    'pros' => 'Faculty quality, legal network, career diversity',
    'cons' => 'Placement in law firms is intense, high expectations',
    'rating' => 4.8, 'academics' => 5.0, 'faculty' => 5.0, 'placements' => 4.8, 'infra' => 4.0, 'hostel' => 4.0, 'social' => 4.5, 'food' => 3.5,
    'batch' => 2023, 'course' => 'BA LLB (Hons)', 'verified' => 1
];
$reviews['col-nlsiu-0012'][] = [
    'title' => 'Top Law School, Period',
    'body' => 'NLSIU consistently ranks number 1 in law school rankings. The peer group is incredibly talented and diverse. The legal aid clinic gives real-world experience from second year itself.',
    'pros' => '#1 ranking, peer quality, practical exposure, Bangalore city',
    'cons' => 'Intense academic schedule, limited sports infrastructure',
    'rating' => 4.7, 'academics' => 4.8, 'faculty' => 4.8, 'placements' => 4.7, 'infra' => 4.0, 'hostel' => 4.0, 'social' => 4.5, 'food' => 3.5,
    'batch' => 2023, 'course' => 'BA LLB (Hons)', 'verified' => 0
];

$total = 0;
$stmt = $pdo->prepare("INSERT INTO reviews (id, user_id, college_id, overall_rating, academics_rating, faculty_rating, placements_rating, infrastructure_rating, hostel_rating, social_life_rating, food_rating, review_title, review_body, pros, cons, batch_year, course_id, moderation_status, is_verified_alumnus, helpful_votes, created_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,NOW() - INTERVAL ? DAY)");

foreach ($reviews as $collegeId => $collegeReviews) {
    foreach ($collegeReviews as $i => $rev) {
        $courseId = null;

        $uid = $users[array_rand($users)];
        $id = uuid();
        $daysAgo = $i * 30 + mt_rand(1, 29);

        $stmt->execute([
            $id,
            $uid,
            $collegeId,
            $rev['rating'],
            $rev['academics'],
            $rev['faculty'],
            $rev['placements'],
            $rev['infra'],
            $rev['hostel'],
            $rev['social'],
            $rev['food'],
            $rev['title'],
            $rev['body'],
            $rev['pros'],
            $rev['cons'],
            $rev['batch'],
            $courseId,
            'approved',
            $rev['verified'],
            mt_rand(2, 45),
            $daysAgo,
        ]);
        $total++;
    }
}

echo 'Updating college stats...' . PHP_EOL;
$pdo->exec("UPDATE colleges SET total_reviews = (SELECT COUNT(*) FROM reviews WHERE reviews.college_id = colleges.id AND moderation_status = 'approved')");
$pdo->exec("UPDATE colleges SET overall_rating_avg = (SELECT ROUND(AVG(overall_rating),1) FROM reviews WHERE reviews.college_id = colleges.id AND moderation_status = 'approved')");
$pdo->exec("UPDATE colleges SET verified_reviews_count = (SELECT COUNT(*) FROM reviews WHERE reviews.college_id = colleges.id AND moderation_status = 'approved' AND is_verified_alumnus = 1)");

echo "Inserted $total reviews across " . count($reviews) . " colleges" . PHP_EOL;
echo 'Done!' . PHP_EOL;
