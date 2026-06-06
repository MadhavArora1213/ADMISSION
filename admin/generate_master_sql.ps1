$out = 'c:\xampp\htdocs\ADMISSION\admin\MASTER_IMPORT.sql'

# Write header
$header = "-- =============================================`r`n"
$header += "-- MASTER IMPORT FILE for Hostinger`r`n"
$header += "-- Database: u642624414_edusearch`r`n"
$header += "-- Select this database in phpMyAdmin then import`r`n"
$header += "-- =============================================`r`n`r`n"
$header += "SET NAMES utf8mb4;`r`n"
$header += "SET FOREIGN_KEY_CHECKS = 0;`r`n"
$header += "SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';`r`n`r`n"

[System.IO.File]::WriteAllText($out, $header, [System.Text.Encoding]::UTF8)

$orderedFiles = @(
  'users_schema.sql',
  'database.sql',
  'colleges_schema.sql',
  'college_details_schema.sql',
  'universities_schema.sql',
  'university_details_schema.sql',
  'courses_schema.sql',
  'exams_schema_new_tables.sql',
  'dashboard_schema.sql',
  'leads_schema.sql',
  'reviews_schema.sql',
  'cms_schema.sql',
  'analytics_schema.sql',
  'applications_schema.sql',
  'billing_revenue_schema.sql',
  'community_schema.sql',
  'compare_engine_schema.sql',
  'emi_calculator_schema.sql',
  'enterprise_colleges_schema_fixed.sql',
  'enterprise_dashboard_schema.sql',
  'moderation_schema.sql',
  'notifications_schema.sql',
  'partner_portal_schema.sql',
  'predictor_schema.sql',
  'rankings_schema.sql',
  'scholarships_schema.sql',
  'search_schema.sql',
  'seo_schema.sql',
  'shortlist_schema.sql',
  'study_abroad_schema.sql',
  'system_settings_schema.sql',
  'ugc_and_engagement_schema.sql',
  'ai_systems_schema.sql'
)

$stream = [System.IO.StreamWriter]::new($out, $true, [System.Text.Encoding]::UTF8)

foreach ($file in $orderedFiles) {
    $path = "c:\xampp\htdocs\ADMISSION\admin\$file"
    if (Test-Path $path) {
        $stream.WriteLine("")
        $stream.WriteLine("/* === FILE: $file === */")
        $content = [System.IO.File]::ReadAllText($path, [System.Text.Encoding]::UTF8)
        $content = $content -replace 'USE admission;', ''
        $content = $content -replace 'USE `admission`;', ''
        $stream.WriteLine($content)
    } else {
        $stream.WriteLine("/* SKIPPED (not found): $file */")
    }
}

$stream.WriteLine("")
$stream.WriteLine("SET FOREIGN_KEY_CHECKS = 1;")
$stream.Close()

$size = (Get-Item $out).Length
Write-Host "SUCCESS: MASTER_IMPORT.sql created ($size bytes)"
