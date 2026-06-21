<?php
require_once 'admin/db.php';

try {
    $pdo->beginTransaction();

    // 1. Clean existing records
    $pdo->exec("DELETE FROM answers");
    $pdo->exec("DELETE FROM questions");
    $pdo->exec("DELETE FROM experts");

    // 2. Fetch default user IDs or fallback to Rahul Sharma's UUID
    $userId = $pdo->query("SELECT id FROM users LIMIT 1")->fetchColumn();
    if (!$userId) {
        $userId = 'user-1234-uuid';
        // Insert Rahul Sharma if not present
        $pdo->prepare("INSERT IGNORE INTO users (id, full_name, email, password_hash) VALUES (?, ?, ?, ?)")
            ->execute([$userId, 'Rahul Sharma', 'rahul.sharma@example.com', password_hash('123456', PASSWORD_DEFAULT)]);
    }

    $adminId = $pdo->query("SELECT id FROM users WHERE email LIKE '%admin%' LIMIT 1")->fetchColumn();
    if (!$adminId) {
        $adminId = $userId;
    }

    // Fetch some college and course IDs to link
    $collegeId = $pdo->query("SELECT id FROM colleges LIMIT 1")->fetchColumn() ?: null;
    $courseId = $pdo->query("SELECT id FROM courses LIMIT 1")->fetchColumn() ?: null;

    // 3. Seed Experts
    $experts = [
        [
            'id' => 'exp-1-uuid',
            'expert_name' => 'Dr. Amit Patel',
            'expert_designation' => 'Senior Admissions Director',
            'expert_college' => 'IIT Delhi',
            'verified_badge' => 1,
            'answer_count' => 124,
            'profile_url' => 'https://images.unsplash.com/photo-1537368910025-700350fe46c7?w=120&h=120&fit=crop',
            'specialization' => 'Engineering Admissions & Entrance Exams',
            'linkedin_url' => 'https://linkedin.com/in/dummy-expert-1',
            'response_rate_pct' => 98.2,
            'avg_response_hours' => 1.5
        ],
        [
            'id' => 'exp-2-uuid',
            'expert_name' => 'Prof. Sarah D\'Souza',
            'expert_designation' => 'Head of Career Counselling',
            'expert_college' => 'IIM Ahmedabad',
            'verified_badge' => 1,
            'answer_count' => 89,
            'profile_url' => 'https://images.unsplash.com/photo-1573496359142-b8d87734a5a2?w=120&h=120&fit=crop',
            'specialization' => 'Management (MBA) Careers & Placements',
            'linkedin_url' => 'https://linkedin.com/in/dummy-expert-2',
            'response_rate_pct' => 95.0,
            'avg_response_hours' => 2.4
        ],
        [
            'id' => 'exp-3-uuid',
            'expert_name' => 'Dr. Rakesh Nair',
            'expert_designation' => 'Medical Education Advisor',
            'expert_college' => 'AIIMS New Delhi',
            'verified_badge' => 1,
            'answer_count' => 45,
            'profile_url' => 'https://images.unsplash.com/photo-1622253692010-333f2da6031d?w=120&h=120&fit=crop',
            'specialization' => 'MBBS admissions, NEET Counseling',
            'linkedin_url' => 'https://linkedin.com/in/dummy-expert-3',
            'response_rate_pct' => 90.0,
            'avg_response_hours' => 4.0
        ]
    ];

    $expInsert = $pdo->prepare("
        INSERT INTO experts (
            id, expert_name, expert_designation, expert_college, verified_badge, 
            answer_count, profile_url, specialization, linkedin_url, response_rate_pct, avg_response_hours
        ) VALUES (
            :id, :expert_name, :expert_designation, :expert_college, :verified_badge, 
            :answer_count, :profile_url, :specialization, :linkedin_url, :response_rate_pct, :avg_response_hours
        )
    ");
    foreach ($experts as $e) {
        $expInsert->execute($e);
    }

    // 4. Seed Questions
    $questions = [
        [
            'id' => 'q-1-uuid',
            'question_text' => 'What is the average placement package for Computer Science (CSE) at IIT Delhi this year?',
            'question_category' => 'placements',
            'related_college_id' => $collegeId,
            'related_exam_id' => null,
            'related_course_id' => $courseId,
            'asked_by' => $userId,
            'views' => 450,
            'answer_count' => 1,
            'is_featured' => 1,
            'status' => 'answered',
            'trending_score' => 8.5
        ],
        [
            'id' => 'q-2-uuid',
            'question_text' => 'Is NEET mandatory for taking admission in B.Sc Nursing courses across India?',
            'question_category' => 'exams',
            'related_college_id' => null,
            'related_exam_id' => null,
            'related_course_id' => null,
            'asked_by' => $userId,
            'views' => 312,
            'answer_count' => 1,
            'is_featured' => 0,
            'status' => 'answered',
            'trending_score' => 6.2
        ],
        [
            'id' => 'q-3-uuid',
            'question_text' => 'What are the hostel facilities and fee structure for girls at BITS Pilani campus?',
            'question_category' => 'hostel',
            'related_college_id' => null,
            'related_exam_id' => null,
            'related_course_id' => null,
            'asked_by' => $userId,
            'views' => 189,
            'answer_count' => 0,
            'is_featured' => 0,
            'status' => 'open',
            'trending_score' => 3.0
        ],
        [
            'id' => 'q-4-uuid',
            'question_text' => 'Which specialization has better salary packages in MBA: Finance or Marketing?',
            'question_category' => 'general',
            'related_college_id' => null,
            'related_exam_id' => null,
            'related_course_id' => null,
            'asked_by' => $userId,
            'views' => 540,
            'answer_count' => 1,
            'is_featured' => 1,
            'status' => 'answered',
            'trending_score' => 9.2
        ],
        [
            'id' => 'q-5-uuid',
            'question_text' => 'When is the expected registration starting date for CUET UG 2026 examination?',
            'question_category' => 'exams',
            'related_college_id' => null,
            'related_exam_id' => null,
            'related_course_id' => null,
            'asked_by' => $userId,
            'views' => 280,
            'answer_count' => 0,
            'is_featured' => 0,
            'status' => 'open',
            'trending_score' => 4.5
        ]
    ];

    $qInsert = $pdo->prepare("
        INSERT INTO questions (
            id, question_text, question_category, related_college_id, related_exam_id, 
            related_course_id, asked_by, views, answer_count, is_featured, status, trending_score
        ) VALUES (
            :id, :question_text, :question_category, :related_college_id, :related_exam_id, 
            :related_course_id, :asked_by, :views, :answer_count, :is_featured, :status, :trending_score
        )
    ");
    foreach ($questions as $q) {
        $qInsert->execute($q);
    }

    // 5. Seed Answers
    $answers = [
        [
            'id' => 'ans-1-uuid',
            'question_id' => 'q-1-uuid',
            'answer_text' => 'According to recent reports, the average placement package for the Computer Science and Engineering (CSE) department at IIT Delhi for the latest batch was around 21.5 LPA. Top recruiters included Microsoft, Google, Goldman Sachs, and several high-frequency trading firms.',
            'answered_by' => $adminId,
            'is_expert_answer' => 1,
            'is_verified_alumnus' => 0,
            'upvotes' => 45,
            'is_accepted' => 1
        ],
        [
            'id' => 'ans-2-uuid',
            'question_id' => 'q-2-uuid',
            'answer_text' => 'NEET is not universally mandatory for B.Sc Nursing. While some top central universities (like BHU, JIPMER) and certain states use NEET scores for nursing admissions, many state boards and private universities still conduct their own entrance examinations or offer direct merit-based admissions based on Class 12 board marks.',
            'answered_by' => $adminId,
            'is_expert_answer' => 1,
            'is_verified_alumnus' => 0,
            'upvotes' => 18,
            'is_accepted' => 1
        ],
        [
            'id' => 'ans-3-uuid',
            'question_id' => 'q-4-uuid',
            'answer_text' => 'Both streams are highly lucrative, but placements vary by interest and profile. Finance usually offers high-paying roles in investment banking, equity research, and corporate finance, particularly at premier colleges (IIMs/FMS). Marketing offers excellent salaries in FMCG, digital marketing, and sales leadership, often with faster promotion tracks.',
            'answered_by' => $adminId,
            'is_expert_answer' => 0,
            'is_verified_alumnus' => 1,
            'upvotes' => 32,
            'is_accepted' => 1
        ]
    ];

    $ansInsert = $pdo->prepare("
        INSERT INTO answers (
            id, question_id, answer_text, answered_by, is_expert_answer, is_verified_alumnus, upvotes, is_accepted
        ) VALUES (
            :id, :question_id, :answer_text, :answered_by, :is_expert_answer, :is_verified_alumnus, :upvotes, :is_accepted
        )
    ");
    foreach ($answers as $a) {
        $ansInsert->execute($a);
    }

    $pdo->commit();
    echo "Community Q&A and Expert data seeded successfully!\n";
} catch (Exception $e) {
    $pdo->rollBack();
    echo "Error seeding community data: " . $e->getMessage() . "\n";
}
