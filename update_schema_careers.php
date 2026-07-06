<?php
declare(strict_types=1);
require_once __DIR__ . '/panel_cms_2847/db.php';

try {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `careers` (
          `id` VARCHAR(36) NOT NULL,
          `name` VARCHAR(255) NOT NULL,
          `slug` VARCHAR(255) NOT NULL UNIQUE,
          `stream` ENUM('Science', 'Commerce', 'Humanities') NOT NULL,
          `sub_stream` VARCHAR(100) NOT NULL,
          `short_description` VARCHAR(500) DEFAULT NULL,
          `job_profile` TEXT DEFAULT NULL,
          `how_to_get_there` TEXT DEFAULT NULL,
          `salary_range` VARCHAR(100) DEFAULT NULL,
          `skills_required` VARCHAR(255) DEFAULT NULL,
          `is_popular` TINYINT(1) DEFAULT 0,
          `image_url` VARCHAR(255) DEFAULT NULL,
          `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP(),
          `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP() ON UPDATE CURRENT_TIMESTAMP(),
          PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");
    echo "Careers table created/verified successfully.\n";

    // Clean existing careers first to seed fresh
    $pdo->exec("DELETE FROM careers");

    $careers = [
        [
            'id' => 'car-1-uuid',
            'name' => 'Aeronautical Engineer',
            'slug' => 'aeronautical-engineer',
            'stream' => 'Science',
            'sub_stream' => 'Aviation & Aerospace',
            'short_description' => 'Aeronautical engineering focuses on the design, development, and testing of aircraft, missiles, and spacecraft operating within Earth\'s atmosphere.',
            'job_profile' => 'Aeronautical engineers are primarily responsible for creating safer, more efficient, and structurally sound commercial aircraft, military fighter jets, helicopters, and drones. The job profile includes performing aerodynamic testing in wind tunnels, designing engines, analyzing structural fatigue, and supervising manufacturing lines. Modern aeronautical engineers also work extensively with flight control electronics and digital twin simulations to predict flight behaviour.',
            'how_to_get_there' => "1. **Higher Secondary Schooling (10+2)**: Science stream with Physics, Chemistry, and Mathematics (PCM).\n2. **Entrance Exam**: Qualify for engineering entrance exams like JEE Main and JEE Advanced.\n3. **Undergraduate Degree**: Pursue a 4-year B.Tech / B.E. in Aeronautical, Aerospace, or Mechanical Engineering from a recognized college.\n4. **Postgraduate (Optional)**: Complete an M.Tech or MS for specialized research fields.",
            'salary_range' => '6 - 18 LPA',
            'skills_required' => 'Aerodynamics, Calculus, CAD/CAM, Matlab, Structural Analysis, Physics',
            'is_popular' => 1,
            'image_url' => 'https://images.unsplash.com/photo-1540962351504-03099e0a754b?w=600&h=400&fit=crop'
        ],
        [
            'id' => 'car-2-uuid',
            'name' => 'Computer Engineer',
            'slug' => 'computer-engineer',
            'stream' => 'Science',
            'sub_stream' => 'Software & IT',
            'short_description' => 'Computer engineering combines computer science and electronic engineering to design hardware, write software, and build complex system architectures.',
            'job_profile' => 'Computer engineers work at the intersection of hardware and software. They design microprocessors, create firmware for embedded systems, write compiler routines, and build complex software architectures like cloud systems, operating systems, and AI models. They ensure hardware components communicate seamlessly with software packages to produce fast, reliable, and energy-efficient systems.',
            'how_to_get_there' => "1. **10+2 Education**: Complete High School with Physics, Chemistry, and Mathematics (PCM).\n2. **Entrance Examination**: Sit for JEE Main, BITSAT, VITEEE, or state-level engineering entrance exams.\n3. **Bachelor\'s Degree**: Complete B.Tech/B.E. in Computer Science Engineering (CSE), Information Technology, or Computer Engineering.\n4. **Skills & Internships**: Build a solid programming portfolio on GitHub and intern as a developer.",
            'salary_range' => '8 - 25 LPA',
            'skills_required' => 'Data Structures, Algorithms, C++, Python, Computer Architecture, Cloud Computing',
            'is_popular' => 1,
            'image_url' => 'https://images.unsplash.com/photo-1517694712202-14dd9538aa97?w=600&h=400&fit=crop'
        ],
        [
            'id' => 'car-3-uuid',
            'name' => 'Doctor (MBBS)',
            'slug' => 'doctor',
            'stream' => 'Science',
            'sub_stream' => 'Medical & Health',
            'short_description' => 'Medical practitioners diagnose illnesses, prescribe treatments, perform surgeries, and provide general health counselling to patients.',
            'job_profile' => 'Doctors are the backbone of clinical healthcare. They examine patients, analyze medical reports and diagnostic images, formulate treatment plans, perform surgical operations, and prescribe medications. Depending on specialization, a doctor can be a cardiologist, surgeon, pediatrician, neurologist, or general physician.',
            'how_to_get_there' => "1. **10+2 Education**: Science stream with Physics, Chemistry, and Biology (PCB) as core subjects.\n2. **Entrance Exam**: Crack the National Eligibility cum Entrance Test (NEET UG) with a top rank.\n3. **Medical Degree**: Complete 4.5 years of MBBS course followed by 1 year of compulsory rotating internship.\n4. **Specialization**: Crack NEET PG to pursue MD, MS, or DNB courses.",
            'salary_range' => '9 - 30 LPA',
            'skills_required' => 'Human Anatomy, Pharmacology, Clinical Diagnosis, Patient Care, Medical Ethics',
            'is_popular' => 1,
            'image_url' => 'https://images.unsplash.com/photo-1537368910025-700350fe46c7?w=600&h=400&fit=crop'
        ],
        [
            'id' => 'car-4-uuid',
            'name' => 'Chartered Accountant',
            'slug' => 'chartered-accountant',
            'stream' => 'Commerce',
            'sub_stream' => 'Finance & Accounting',
            'short_description' => 'Chartered Accountants handle financial audits, corporate tax filing, accounting advice, and financial management for businesses.',
            'job_profile' => 'Chartered Accountants (CAs) serve as financial advisors, auditors, and tax consultants for corporations, government bodies, and individuals. They analyze ledger accounts, perform statutory financial audits, formulate business taxation strategies, manage corporate mergers/acquisitions, and advise on cost optimization and risk management.',
            'how_to_get_there' => "1. **Register with ICAI**: Register for the CA Foundation exam after completing Class 10 or 12.\n2. **CA Intermediate**: Complete CA Foundation and clear both groups of CA Intermediate.\n3. **Articleship**: Undergo 2-3 years of practical articleship training under a practicing CA.\n4. **CA Final**: Clear the CA Final examination to register as a member of the ICAI.",
            'salary_range' => '7 - 20 LPA',
            'skills_required' => 'Financial Auditing, Direct & Indirect Taxation, Corporate Law, Tally, Business Valuation',
            'is_popular' => 1,
            'image_url' => 'https://images.unsplash.com/photo-1554224155-8d04cb21cd6c?w=600&h=400&fit=crop'
        ],
        [
            'id' => 'car-5-uuid',
            'name' => 'Commercial Pilot',
            'slug' => 'pilot',
            'stream' => 'Science',
            'sub_stream' => 'Aviation & Aerospace',
            'short_description' => 'Commercial pilots fly cargo, charter flights, and commercial aircraft carrying passengers across domestic and international routes.',
            'job_profile' => 'Commercial pilots operate commercial aircraft for commercial airlines. Before flight, they run through safety checklists, check weather routes, verify cargo weight, and plan fuel requirements. During flight, they navigate the skies, communicate with Air Traffic Control, monitor instruments, and manage emergency protocols. They ensure passengers reach their destination safely and on schedule.',
            'how_to_get_there' => "1. **10+2 Education**: Physics and Mathematics are mandatory core subjects in 10+2.\n2. **Medical Evaluation**: Clear Class II and Class I medical exams conducted by DGCA-approved doctors.\n3. **Flying School**: Join a DGCA-approved Flying Training Organization (FTO).\n4. **Obtain Licenses**: Clear DGCA theory exams and complete 200 hours of flying to obtain a Commercial Pilot License (CPL).",
            'salary_range' => '12 - 36 LPA',
            'skills_required' => 'Meteorology, Navigation, Aircraft Systems, Crisis Management, Communication',
            'is_popular' => 1,
            'image_url' => 'https://images.unsplash.com/photo-1506012787146-f92b2d7d6d96?w=600&h=400&fit=crop'
        ],
        [
            'id' => 'car-6-uuid',
            'name' => 'Corporate Lawyer',
            'slug' => 'corporate-lawyer',
            'stream' => 'Humanities',
            'sub_stream' => 'Legal & Law',
            'short_description' => 'Corporate lawyers advise businesses on legal rights, obligations, transaction compliance, contracts, and dispute resolutions.',
            'job_profile' => 'Corporate lawyers draft commercial contracts, ensure regulatory compliance with corporate laws, structure mergers and acquisitions, protect intellectual property, and represent corporate entities in court or arbitration. They protect companies from legal liabilities and guide leadership on corporate governance.',
            'how_to_get_there' => "1. **10+2 Education**: Open to any stream (Humanities, Commerce, or Science).\n2. **Entrance Exam**: Clear CLAT (Common Law Admission Test) or AILET.\n3. **Integrative Law Degree**: Pursue a 5-year integrated B.A. LL.B., B.B.A. LL.B. or B.Com LL.B. course.\n4. **Bar Council Enrollment**: Register with the State Bar Council and pass the All India Bar Examination (AIBE).",
            'salary_range' => '6 - 22 LPA',
            'skills_required' => 'Contract Drafting, Corporate Law, Litigation, Critical Thinking, Negotiation',
            'is_popular' => 0,
            'image_url' => 'https://images.unsplash.com/photo-1589829545856-d10d557cf95f?w=600&h=400&fit=crop'
        ],
        [
            'id' => 'car-7-uuid',
            'name' => 'Hotel Manager',
            'slug' => 'hotel-manager',
            'stream' => 'Humanities',
            'sub_stream' => 'Management',
            'short_description' => 'Hotel managers supervise lodging establishments, front desk operations, housekeeping, catering, and event hosting services.',
            'job_profile' => 'Hotel managers manage the daily operations of hotels, luxury resorts, and cruise ships. They ensure guest satisfaction, monitor budgets, coordinate front desk check-ins, manage catering schedules, oversee housekeeping standards, and organize corporate conferences or wedding events. They balance financial management with premium hospitality service.',
            'how_to_get_there' => "1. **10+2 Education**: Stream agnostic, but English must be a core subject.\n2. **Entrance Exam**: Clear NCHMCT JEE (National Council for Hotel Management Joint Entrance Exam).\n3. **Undergraduate Degree**: Pursue a 3-4 year B.Sc in Hospitality & Hotel Administration, or Bachelor of Hotel Management (BHM).\n4. **Industrial Training**: Undergo training in leading hotel chains during the degree.",
            'salary_range' => '5 - 15 LPA',
            'skills_required' => 'Hospitality Operations, Staff Management, Customer Relations, Budgeting, Event Planning',
            'is_popular' => 1,
            'image_url' => 'https://images.unsplash.com/photo-1566073771259-6a8506099945?w=600&h=400&fit=crop'
        ],
        [
            'id' => 'car-8-uuid',
            'name' => 'Graphic Designer',
            'slug' => 'graphic-designer',
            'stream' => 'Humanities',
            'sub_stream' => 'Creative & Design',
            'short_description' => 'Graphic designers create visual content to communicate marketing messages, brand identity, layout designs, and illustrations.',
            'job_profile' => 'Graphic designers develop visual concepts for advertising campaigns, corporate logo designs, website user interfaces, packaging, and digital publications. They use professional software tools to combine typography, layout systems, photography, and illustration into compelling marketing materials.',
            'how_to_get_there' => "1. **10+2 Education**: Completed in any stream. A background in Fine Arts is a plus.\n2. **Design Entrances**: Crack entrance exams like NID DAT, UCEED, or NIFT.\n3. **Design Degree**: Complete a Bachelor of Design (B.Des) in Communication Design or Graphic Design.\n4. **Portfolio Development**: Build an active online portfolio showing packaging, brand, and UX designs.",
            'salary_range' => '4 - 12 LPA',
            'skills_required' => 'Adobe Photoshop, Adobe Illustrator, Figma, Typography, Color Theory, Brand Identity',
            'is_popular' => 0,
            'image_url' => 'https://images.unsplash.com/photo-1626785774573-4b799315345d?w=600&h=400&fit=crop'
        ],
        [
            'id' => 'car-9-uuid',
            'name' => 'Investment Banker',
            'slug' => 'investment-banker',
            'stream' => 'Commerce',
            'sub_stream' => 'Finance & Accounting',
            'short_description' => 'Investment bankers assist corporations, governments, and institutions in raising capital, restructuring debt, and structuring mergers.',
            'job_profile' => 'Investment bankers work in corporate finance teams, advising businesses on issuing equity shares or corporate bonds to raise capital. They create mathematical valuation models, conduct due diligence, structure mergers and acquisitions, prepare prospectus documents, and manage large-scale corporate restructurings.',
            'how_to_get_there' => "1. **10+2 Education**: Commerce stream with Math/Applied Mathematics is highly recommended.\n2. **Undergraduate Degree**: Pursue B.Com (Hons), Bachelor of Management Studies (BMS), or B.Sc in Economics/Finance.\n3. **Specialized Postgraduate (Optional)**: Complete an MBA in Finance or clear Chartered Financial Analyst (CFA) levels.\n4. **Networking**: Participate in internship programs in financial centers.",
            'salary_range' => '10 - 30 LPA',
            'skills_required' => 'Financial Modelling, Excel Formulas, Corporate Finance, Mergers & Acquisitions, Valuation',
            'is_popular' => 1,
            'image_url' => 'https://images.unsplash.com/photo-1454165804606-c3d57bc86b40?w=600&h=400&fit=crop'
        ]
    ];

    $insert = $pdo->prepare("
        INSERT INTO careers (
            id, name, slug, stream, sub_stream, short_description, job_profile, how_to_get_there, salary_range, skills_required, is_popular, image_url
        ) VALUES (
            :id, :name, :slug, :stream, :sub_stream, :short_description, :job_profile, :how_to_get_there, :salary_range, :skills_required, :is_popular, :image_url
        )
    ");

    foreach ($careers as $c) {
        $insert->execute($c);
    }

    echo "Successfully seeded " . count($careers) . " careers into the database.\n";
} catch (Exception $e) {
    echo "Error updating/seeding careers table: " . $e->getMessage() . "\n";
}
?>
