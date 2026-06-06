$inputFile = 'c:\xampp\htdocs\ADMISSION\admission.sql'
$outputFile = 'c:\xampp\htdocs\ADMISSION\admission_hostinger.sql'

Write-Host "Reading admission.sql..."
$content = [System.IO.File]::ReadAllText($inputFile, [System.Text.Encoding]::UTF8)

Write-Host "Fixing for Hostinger MySQL..."

# 1. Add FOREIGN_KEY_CHECKS=0 right after SET SQL_MODE line
$content = $content -replace 'SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";', "SET SQL_MODE = `"NO_AUTO_VALUE_ON_ZERO`";`nSET FOREIGN_KEY_CHECKS = 0;"

# 2. Remove MariaDB-specific CHECK (json_valid(...)) clauses
$content = [regex]::Replace($content, '\s*CHECK \(json_valid\(`[^`]+`\)\)', '')

# 3. Remove START TRANSACTION / COMMIT (can cause issues on some Hostinger plans)
# Keep them but add FOREIGN_KEY_CHECKS=1 before COMMIT
$content = $content -replace 'COMMIT;', "SET FOREIGN_KEY_CHECKS = 1;`nCOMMIT;"

# 4. Remove the database reference comment (already selected in phpMyAdmin)
$content = $content -replace '--\r?\n-- Database: `admission`\r?\n--', '-- Database selected in phpMyAdmin'

Write-Host "Writing fixed file..."
[System.IO.File]::WriteAllText($outputFile, $content, [System.Text.Encoding]::UTF8)

$originalSize = (Get-Item $inputFile).Length
$newSize = (Get-Item $outputFile).Length
Write-Host ""
Write-Host "SUCCESS!"
Write-Host "Original: $([math]::Round($originalSize/1024, 1)) KB"
Write-Host "Fixed:    $([math]::Round($newSize/1024, 1)) KB"
Write-Host ""
Write-Host "Upload 'admission_hostinger.sql' to phpMyAdmin on Hostinger"
