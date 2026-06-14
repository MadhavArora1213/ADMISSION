<?php
require_once __DIR__ . '/admin/db.php';

$articles = [
    [
        'title' => 'Top 10 Engineering Colleges in India for 2026',
        'slug' => 'top-10-engineering-colleges-2026-v2',
        'type' => 'ranking',
        'excerpt' => 'Discover the top-ranked engineering institutions in India based on placement records, faculty, and research output for the year 2026.',
        'content' => '<p>Engineering remains one of the most sought-after career paths in India. In 2026, the rankings have seen a significant shift, with several new IITs and private institutions moving up the ladder...</p>',
        'img' => 'https://images.unsplash.com/photo-1541339907198-e08756dedf3f?w=800&q=80',
        'author' => 'Madhav Arora'
    ],
    [
        'title' => 'JEE Main 2026 Dates Announced: Check Registration Details',
        'slug' => 'jee-main-2026-dates-announced-v2',
        'type' => 'exam_update',
        'excerpt' => 'The National Testing Agency (NTA) has finally released the schedule for JEE Main 2026. Registrations will commence from the first week of November.',
        'content' => '<p>Attention engineering aspirants! The NTA has officially announced the exam dates for JEE Main 2026. The exam will be conducted in two sessions, as usual. Students are advised to keep their documents ready for the registration process...</p>',
        'img' => 'https://images.unsplash.com/photo-1434030216411-0b793f4b4173?w=800&q=80',
        'author' => 'Education Desk'
    ],
    [
        'title' => 'How to Choose the Right College: A Comprehensive Guide',
        'slug' => 'how-to-choose-the-right-college-v2',
        'type' => 'guide',
        'excerpt' => 'Feeling overwhelmed by college options? Here is a step-by-step guide to evaluating colleges based on your career goals, budget, and location preferences.',
        'content' => '<p>Choosing the right college is a life-changing decision. It is not just about the brand name; it is about finding a place that aligns with your personal and professional aspirations. Let us dive into the key factors you must consider...</p>',
        'img' => 'https://images.unsplash.com/photo-1523240795612-9a054b0db644?w=800&q=80',
        'author' => 'Career Counselor'
    ],
    [
        'title' => 'Delhi University Introduces New B.Tech Programs',
        'slug' => 'du-introduces-new-btech-programs-v2',
        'type' => 'news',
        'excerpt' => 'In a major academic expansion, Delhi University has announced the launch of three new B.Tech programs starting this academic session. Here is what you need to know.',
        'content' => '<p>Delhi University (DU) is expanding its technical education footprint by introducing B.Tech programs in Computer Science, Electronics, and Electrical Engineering. Admissions will be based on JEE Main scores...</p>',
        'img' => 'https://images.unsplash.com/photo-1562774053-701939374585?w=800&q=80',
        'author' => 'Campus Reporter'
    ],
    [
        'title' => 'Why Liberal Arts Education is Gaining Popularity in India',
        'slug' => 'liberal-arts-education-popularity-v2',
        'type' => 'blog',
        'excerpt' => 'More students are moving away from traditional STEM fields to explore Liberal Arts. What is driving this shift, and what are the career prospects?',
        'content' => '<p>The traditional mindset of "Engineering or Medical" is slowly changing in India. A liberal arts education offers critical thinking, adaptability, and a broad worldview, which modern employers highly value...</p>',
        'img' => 'https://images.unsplash.com/photo-1522202176988-66273c2fd55f?w=800&q=80',
        'author' => 'Guest Blogger'
    ],
    [
        'title' => 'My Opinion: Are Entrance Exams Putting Too Much Pressure on Students?',
        'slug' => 'opinion-entrance-exams-pressure-v2',
        'type' => 'opinion',
        'excerpt' => 'With rising competition and coaching culture, entrance exams are taking a toll on student mental health. It is time we rethink our evaluation methods.',
        'content' => '<p>Every year, millions of students appear for competitive exams like JEE, NEET, and CUET. While these exams are designed to be a fair metric for selection, the sheer pressure and the booming coaching industry are creating an unhealthy environment...</p>',
        'img' => 'https://images.unsplash.com/photo-1513258496099-48168024aec0?w=800&q=80',
        'author' => 'Student Voice'
    ]
];

function uuidv4() {
    $data = random_bytes(16);
    $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
    $data[8] = chr(ord($data[8]) & 0x3f | 0x80);
    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
}

// First, get a category ID to use
$stmtCat = $pdo->query("SELECT id FROM article_categories LIMIT 1");
$catId = $stmtCat->fetchColumn();

if (!$catId) {
    // Insert a dummy category if none exists
    $pdo->exec("INSERT INTO article_categories (category_name, category_slug, status) VALUES ('General Updates', 'general-updates', 'active')");
    $catId = $pdo->lastInsertId();
}

$inserted = 0;
foreach ($articles as $art) {
    // Check if slug already exists
    $stmtCheck = $pdo->prepare("SELECT id FROM articles WHERE article_slug = ?");
    $stmtCheck->execute([$art['slug']]);
    if ($stmtCheck->fetchColumn()) {
        continue;
    }

    $stmt = $pdo->prepare("INSERT INTO articles (
        id, category_id, article_title, article_slug, article_type, 
        excerpt, content_body, featured_image_url, custom_author_name, 
        status, publish_at
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'published', NOW())");
    
    $stmt->execute([
        uuidv4(),
        $catId,
        $art['title'],
        $art['slug'],
        $art['type'],
        $art['excerpt'],
        $art['content'],
        $art['img'],
        $art['author']
    ]);
    $inserted++;
}

echo "Successfully inserted $inserted articles.";
?>
