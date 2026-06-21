<?php
require_once 'admin/db.php';

try {
    $pdo->beginTransaction();

    // 1. Clean old foreign universities, visa guides, and consultants
    $pdo->exec("DELETE FROM foreign_universities");
    $pdo->exec("DELETE FROM visa_guides");
    $pdo->exec("DELETE FROM consultants");

    // 2. Insert Foreign Universities
    $unis = [
        [
            'university_name' => 'Harvard University',
            'university_slug' => 'harvard-university',
            'country' => 'United States',
            'qs_rank' => 4,
            'times_rank' => 4,
            'acceptance_rate' => 4.0,
            'tuition_usd_annual' => 54768.00,
            'living_cost_usd_monthly' => 2000.00,
            'intake_months' => json_encode(['September']),
            'official_url' => 'https://www.harvard.edu',
            'min_ielts' => 7.5,
            'min_toefl' => 100,
            'min_gre' => 320,
            'scholarship_available' => 1,
            'logo_url' => 'https://images.unsplash.com/photo-1579783900882-c0d3dad7b119?w=100&h=100&fit=crop',
            'description' => 'Harvard University is a private Ivy League research university in Cambridge, Massachusetts. Established in 1636, Harvard is the oldest institution of higher learning in the United States and among the most prestigious in the world.',
            'city' => 'Cambridge',
            'institution_type' => 'Private',
            'application_fee_usd' => 85.00,
            'min_pte' => 70.0,
            'min_gmat' => 730,
            'min_gpa' => '3.8/4.0',
            'degrees_offered' => json_encode(['Bachelor', 'Master', 'PhD', 'MBA', 'MD', 'JD'])
        ],
        [
            'university_name' => 'University of Oxford',
            'university_slug' => 'university-of-oxford',
            'country' => 'United Kingdom',
            'qs_rank' => 3,
            'times_rank' => 1,
            'acceptance_rate' => 17.5,
            'tuition_usd_annual' => 42500.00,
            'living_cost_usd_monthly' => 1500.00,
            'intake_months' => json_encode(['October']),
            'official_url' => 'https://www.ox.ac.uk',
            'min_ielts' => 7.5,
            'min_toefl' => 110,
            'min_gre' => 315,
            'scholarship_available' => 1,
            'logo_url' => 'https://images.unsplash.com/photo-1541339907198-e08756dedf3f?w=100&h=100&fit=crop',
            'description' => 'The University of Oxford is a collegiate research university in Oxford, England. There is evidence of teaching as early as 1096, making it the oldest university in the English-speaking world.',
            'city' => 'Oxford',
            'institution_type' => 'Public',
            'application_fee_usd' => 100.00,
            'min_pte' => 76.0,
            'min_gmat' => 690,
            'min_gpa' => '3.7/4.0',
            'degrees_offered' => json_encode(['Bachelor', 'Master', 'PhD', 'MBA'])
        ],
        [
            'university_name' => 'University of Toronto',
            'university_slug' => 'university-of-toronto',
            'country' => 'Canada',
            'qs_rank' => 21,
            'times_rank' => 18,
            'acceptance_rate' => 43.0,
            'tuition_usd_annual' => 38000.00,
            'living_cost_usd_monthly' => 1200.00,
            'intake_months' => json_encode(['September', 'January']),
            'official_url' => 'https://www.utoronto.ca',
            'min_ielts' => 7.0,
            'min_toefl' => 100,
            'min_gre' => 310,
            'scholarship_available' => 1,
            'logo_url' => 'https://images.unsplash.com/photo-1523050854058-8df90110c9f1?w=100&h=100&fit=crop',
            'description' => 'The University of Toronto is a public research university in Toronto, Ontario, Canada, located on the grounds that surround Queen\'s Park. It was founded by royal charter in 1827.',
            'city' => 'Toronto',
            'institution_type' => 'Public',
            'application_fee_usd' => 120.00,
            'min_pte' => 65.0,
            'min_gmat' => 650,
            'min_gpa' => '3.5/4.0',
            'degrees_offered' => json_encode(['Bachelor', 'Master', 'PhD', 'MBA'])
        ],
        [
            'university_name' => 'University of Melbourne',
            'university_slug' => 'university-of-melbourne',
            'country' => 'Australia',
            'qs_rank' => 14,
            'times_rank' => 37,
            'acceptance_rate' => 70.0,
            'tuition_usd_annual' => 32000.00,
            'living_cost_usd_monthly' => 1300.00,
            'intake_months' => json_encode(['February', 'July']),
            'official_url' => 'https://www.unimelb.edu.au',
            'min_ielts' => 6.5,
            'min_toefl' => 94,
            'min_gre' => null,
            'scholarship_available' => 1,
            'logo_url' => 'https://images.unsplash.com/photo-1548625361-155deee223d0?w=100&h=100&fit=crop',
            'description' => 'Founded in 1853, the University of Melbourne is a public research university located in Melbourne, Australia. It is Australia\'s second oldest university and the oldest in Victoria.',
            'city' => 'Melbourne',
            'institution_type' => 'Public',
            'application_fee_usd' => 100.00,
            'min_pte' => 58.0,
            'min_gmat' => 630,
            'min_gpa' => '3.2/4.0',
            'degrees_offered' => json_encode(['Bachelor', 'Master', 'PhD', 'MBA'])
        ],
        [
            'university_name' => 'Technical University of Munich',
            'university_slug' => 'technical-university-of-munich',
            'country' => 'Germany',
            'qs_rank' => 37,
            'times_rank' => 30,
            'acceptance_rate' => 8.0,
            'tuition_usd_annual' => 0.00,
            'living_cost_usd_monthly' => 950.00,
            'intake_months' => json_encode(['October', 'April']),
            'official_url' => 'https://www.tum.de',
            'min_ielts' => 6.5,
            'min_toefl' => 88,
            'min_gre' => 310,
            'scholarship_available' => 0,
            'logo_url' => 'https://images.unsplash.com/photo-1592280771190-3e2e4d571952?w=100&h=100&fit=crop',
            'description' => 'The Technical University of Munich is a public research university in Munich, Garching, and Freising-Weihenstephan, Germany. It specializes in engineering, technology, medicine, and applied sciences.',
            'city' => 'Munich',
            'institution_type' => 'Public',
            'application_fee_usd' => 0.00,
            'min_pte' => 60.0,
            'min_gmat' => 640,
            'min_gpa' => '3.0/4.0',
            'degrees_offered' => json_encode(['Bachelor', 'Master', 'PhD'])
        ]
    ];

    $uniInsert = $pdo->prepare("
        INSERT INTO foreign_universities (
            university_name, university_slug, country, qs_rank, times_rank, acceptance_rate, 
            tuition_usd_annual, living_cost_usd_monthly, intake_months, official_url, 
            min_ielts, min_toefl, min_gre, scholarship_available, logo_url, description, 
            city, institution_type, application_fee_usd, min_pte, min_gmat, min_gpa, degrees_offered
        ) VALUES (
            :university_name, :university_slug, :country, :qs_rank, :times_rank, :acceptance_rate, 
            :tuition_usd_annual, :living_cost_usd_monthly, :intake_months, :official_url, 
            :min_ielts, :min_toefl, :min_gre, :scholarship_available, :logo_url, :description, 
            :city, :institution_type, :application_fee_usd, :min_pte, :min_gmat, :min_gpa, :degrees_offered
        )
    ");

    foreach ($unis as $u) {
        $uniInsert->execute($u);
    }
    echo "Foreign Universities inserted successfully.\n";

    // 3. Insert Visa Guides
    $visas = [
        [
            'country' => 'United States',
            'visa_type' => 'F-1 Student Visa',
            'processing_time_days' => 30,
            'visa_fee_usd' => 185.00,
            'documents_required' => json_encode(['Form I-20 issued by SEVP-approved school', 'DS-160 Online Application confirmation', 'SEVIS Fee receipt (I-901)', 'Valid Passport', 'Financial evidence of funds for tuition & living costs']),
            'success_tips' => 'Be prepared to prove ties to your home country to demonstrate your intent to return after graduation. Answer interview questions honestly and concisely.',
            'pswv_duration_months' => 12, // 36 months for STEM OPT
            'proof_of_funds_usd' => 45000.00,
            'interview_required' => 1,
            'part_time_work_hours' => 20
        ],
        [
            'country' => 'United Kingdom',
            'visa_type' => 'Student Visa (Subclass of General Student)',
            'processing_time_days' => 21,
            'visa_fee_usd' => 490.00,
            'documents_required' => json_encode(['Confirmation of Acceptance for Studies (CAS) from university', 'Valid Passport', 'Tuberculosis test results (if applicable)', 'Proof of financial support (maintenance funds)']),
            'success_tips' => 'Ensure funds are held in your account for at least 28 consecutive days before applying. Apply up to 6 months before starting your course.',
            'pswv_duration_months' => 24, // Graduate Route
            'proof_of_funds_usd' => 16000.00,
            'interview_required' => 0,
            'part_time_work_hours' => 20
        ],
        [
            'country' => 'Canada',
            'visa_type' => 'Study Permit',
            'processing_time_days' => 45,
            'visa_fee_usd' => 150.00,
            'documents_required' => json_encode(['Letter of Acceptance from DLI', 'Proof of Identity (Passport)', 'Proof of Financial Support (min $10,000 + tuition)', 'Letter of Explanation / Statement of Purpose']),
            'success_tips' => 'Utilize the Student Direct Stream (SDS) if you are from eligible countries by purchasing a GIC of $10,000 CAD and paying 1st-year tuition upfront.',
            'pswv_duration_months' => 36, // PGWP up to 3 years
            'proof_of_funds_usd' => 20000.00,
            'interview_required' => 0,
            'part_time_work_hours' => 20
        ],
        [
            'country' => 'Australia',
            'visa_type' => 'Student Visa (Subclass 500)',
            'processing_time_days' => 30,
            'visa_fee_usd' => 435.00,
            'documents_required' => json_encode(['Confirmation of Enrolment (CoE)', 'Genuine Temporary Entrant (GTE) statement', 'Proof of Financial capacity', 'Overseas Student Health Cover (OSHC)']),
            'success_tips' => 'Write a strong GTE statement explaining why you chose this specific course and how it adds value to your career path back home.',
            'pswv_duration_months' => 24, // Post-study work visa up to 4 years for select degrees
            'proof_of_funds_usd' => 18000.00,
            'interview_required' => 0,
            'part_time_work_hours' => 24
        ],
        [
            'country' => 'Germany',
            'visa_type' => 'German Student Visa',
            'processing_time_days' => 60,
            'visa_fee_usd' => 80.00,
            'documents_required' => json_encode(['Certificate of admission to a German university', 'Proof of blocked bank account with €11,208', 'Valid Passport', 'Recognized school leaving certificate']),
            'success_tips' => 'Blocked account (Sperrkonto) setup takes time, start early. Make sure you book your visa appointment at the consulate months in advance.',
            'pswv_duration_months' => 18,
            'proof_of_funds_usd' => 12000.00,
            'interview_required' => 1,
            'part_time_work_hours' => 20
        ]
    ];

    $visaInsert = $pdo->prepare("
        INSERT INTO visa_guides (
            country, visa_type, processing_time_days, visa_fee_usd, documents_required, 
            success_tips, pswv_duration_months, proof_of_funds_usd, interview_required, part_time_work_hours
        ) VALUES (
            :country, :visa_type, :processing_time_days, :visa_fee_usd, :documents_required, 
            :success_tips, :pswv_duration_months, :proof_of_funds_usd, :interview_required, :part_time_work_hours
        )
    ");

    foreach ($visas as $v) {
        $visaInsert->execute($v);
    }
    echo "Visa Guides inserted successfully.\n";

    // 4. Insert Consultants
    $consultants = [
        [
            'consultant_name' => 'IDP Education',
            'consultant_rating' => 4.8,
            'verified_consultant' => 1,
            'specialization_countries' => json_encode(['United States', 'United Kingdom', 'Canada', 'Australia']),
            'fee_range' => 'Free (University Funded)',
            'logo_url' => 'https://images.unsplash.com/photo-1542744173-8e7e53415bb0?w=100&h=100&fit=crop',
            'contact_email' => 'info.india@idp.com',
            'contact_phone' => '+91 99999 99999',
            'address' => 'Suite 101, Connaught Place',
            'city' => 'New Delhi',
            'experience_years' => 25,
            'consultation_mode' => 'Both',
            'success_rate_percent' => 97.5
        ],
        [
            'consultant_name' => 'Edwise International',
            'consultant_rating' => 4.6,
            'verified_consultant' => 1,
            'specialization_countries' => json_encode(['United States', 'United Kingdom', 'Canada', 'Germany']),
            'fee_range' => 'Free Counseling',
            'logo_url' => 'https://images.unsplash.com/photo-1551836022-d5d88e9218df?w=100&h=100&fit=crop',
            'contact_email' => 'study@edwiseinternational.com',
            'contact_phone' => '+91 88888 88888',
            'address' => 'Commerce House, Fort',
            'city' => 'Mumbai',
            'experience_years' => 30,
            'consultation_mode' => 'Both',
            'success_rate_percent' => 95.0
        ],
        [
            'consultant_name' => 'Y-Axis Overseas',
            'consultant_rating' => 4.5,
            'verified_consultant' => 1,
            'specialization_countries' => json_encode(['Canada', 'Australia', 'Germany']),
            'fee_range' => '₹10,000 - ₹25,000 (Visa Processing)',
            'logo_url' => 'https://images.unsplash.com/photo-1556761175-b413da4baf72?w=100&h=100&fit=crop',
            'contact_email' => 'contact@y-axis.com',
            'contact_phone' => '+91 77777 77777',
            'address' => 'Jubilee Hills, Road No. 36',
            'city' => 'Hyderabad',
            'experience_years' => 20,
            'consultation_mode' => 'Both',
            'success_rate_percent' => 92.4
        ]
    ];

    $consInsert = $pdo->prepare("
        INSERT INTO consultants (
            consultant_name, consultant_rating, verified_consultant, specialization_countries, fee_range, 
            logo_url, contact_email, contact_phone, address, city, experience_years, consultation_mode, success_rate_percent
        ) VALUES (
            :consultant_name, :consultant_rating, :verified_consultant, :specialization_countries, :fee_range, 
            :logo_url, :contact_email, :contact_phone, :address, :city, :experience_years, :consultation_mode, :success_rate_percent
        )
    ");

    foreach ($consultants as $c) {
        $consInsert->execute($c);
    }
    echo "Consultants inserted successfully.\n";

    $pdo->commit();
    echo "Database successfully seeded with realistic Study Abroad data!\n";
} catch (Exception $e) {
    $pdo->rollBack();
    echo "Seeding failed: " . $e->getMessage() . "\n";
}
