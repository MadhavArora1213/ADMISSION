# =============================================
# upload-sync.ps1
# Watches uploads/ folder and auto-pushes
# new/changed files to GitHub every 60 seconds
# =============================================

$repoPath = Split-Path -Parent $MyInvocation.MyCommand.Path
$uploadsDir = Join-Path $repoPath "uploads"
$logFile = Join-Path $repoPath "upload-sync.log"

Set-Location $repoPath

function Write-Log {
    param([string]$Message)
    $ts = Get-Date -Format "yyyy-MM-dd HH:mm:ss"
    $entry = "[$ts] $Message"
    Write-Host $entry -ForegroundColor Cyan
    Add-Content -Path $logFile -Value $entry
}

Write-Log "=== Upload Sync Started ==="
Write-Log "Watching: $uploadsDir"
Write-Log "Interval: 60 seconds"

# Ensure git is configured
git config user.name  "upload-sync[bot]"  2>$null
git config user.email "upload-sync[bot]@users.noreply.github.com" 2>$null

while ($true) {
    try {
        Set-Location $repoPath

        # Check if uploads dir exists
        if (-not (Test-Path $uploadsDir)) {
            Write-Log "uploads/ folder not found, skipping..."
            Start-Sleep -Seconds 60
            continue
        }

        # Stage all changes in uploads/
        git add uploads/ 2>$null

        # Check if there are staged changes
        $staged = git diff --cached --name-only 2>$null

        if ($staged) {
            $fileCount = ($staged | Measure-Object).Count
            $files = $staged -join ", "

            # Create commit
            $timestamp = Get-Date -Format "yyyy-MM-dd HH:mm"
            git commit -m "uploads: sync $fileCount file(s) [$timestamp]" 2>$null

            # Push to remote
            $pushResult = git push origin main 2>&1

            if ($LASTEXITCODE -eq 0) {
                Write-Log "PUSHED $fileCount file(s): $files"
            } else {
                Write-Log "PUSH FAILED: $pushResult"
            }
        }
    } catch {
        Write-Log "ERROR: $_"
    }

    Start-Sleep -Seconds 60
}
