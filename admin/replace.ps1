$files = @("courses", "placements", "cutoffs", "faqs", "faculty", "scholarships")
foreach ($f in $files) {
    $content = Get-Content "college_$f.php" -Raw
    $content = $content -creplace 'colleges', 'universities'
    $content = $content -creplace 'college', 'university'
    $content = $content -creplace 'Colleges', 'Universities'
    $content = $content -creplace 'College', 'University'
    Set-Content -Path "university_$f.php" -Value $content -Encoding UTF8
}
