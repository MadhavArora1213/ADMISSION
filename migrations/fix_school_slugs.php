<?php
/**
 * Migration: Fix school slugs to use hyphens instead of spaces
 * Run once: php migrations/fix_school_slugs.php
 */

require_once __DIR__ . '/../panel_cms_2847/db.php';

function slugify(string $name): string {
    $slug = strtolower(trim($name));
    $slug = preg_replace('/[^a-z0-9\s-]/', '', $slug);
    $slug = preg_replace('/[\s-]+/', '-', $slug);
    $slug = trim($slug, '-');
    return $slug;
}

try {
    $pdo->beginTransaction();
    
    // Get all schools
    $stmt = $pdo->query("SELECT id, name, slug FROM schools");
    $schools = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $updated = 0;
    foreach ($schools as $school) {
        $cleanSlug = slugify($school['name']);
        
        // Only update if slug is different or contains spaces/encoded chars
        if ($school['slug'] !== $cleanSlug) {
            // Check for duplicate slug
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
    
} catch (Exception $e) {
    $pdo->rollBack();
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
