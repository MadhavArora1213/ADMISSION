<?php
/**
 * Run this once from browser to fix school slugs
 * Access: https://yourdomain.com/run_migration.php
 * DELETE THIS FILE AFTER RUNNING!
 */

require_once __DIR__ . '/panel_cms_2847/db.php';

header('Content-Type: text/plain');

function slugify(string $name): string {
    $slug = strtolower(trim($name));
    $slug = str_replace(['+', '%20'], ' ', $slug);
    $slug = preg_replace('/[^a-z0-9\s-]/', '', $slug);
    $slug = preg_replace('/[\s-]+/', '-', $slug);
    $slug = trim($slug, '-');
    return $slug;
}

try {
    $pdo->beginTransaction();
    
    $stmt = $pdo->query("SELECT id, name, slug FROM schools");
    $schools = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $updated = 0;
    foreach ($schools as $school) {
        $cleanSlug = slugify($school['name']);
        
        if ($school['slug'] !== $cleanSlug || strpos($school['slug'], '+') !== false || strpos($school['slug'], ' ') !== false) {
            $check = $pdo->prepare("SELECT id FROM schools WHERE slug = ? AND id != ?");
            $check->execute([$cleanSlug, $school['id']]);
            
            if ($check->rowCount() === 0) {
                $update = $pdo->prepare("UPDATE schools SET slug = ? WHERE id = ?");
                $update->execute([$cleanSlug, $school['id']]);
                echo "Updated: {$school['name']}\n";
                echo "  Old: {$school['slug']}\n";
                echo "  New: {$cleanSlug}\n\n";
                $updated++;
            } else {
                echo "SKIPPED (duplicate): {$school['name']} -> {$cleanSlug}\n";
            }
        }
    }
    
    $pdo->commit();
    echo "\nDone! Updated {$updated} school slugs.\n";
    echo "\n⚠️ DELETE this file now: run_migration.php";
    
} catch (Exception $e) {
    $pdo->rollBack();
    echo "Error: " . $e->getMessage();
}
