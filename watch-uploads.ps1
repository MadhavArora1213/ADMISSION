# =============================================
# watch-uploads.ps1
# Real-time watcher for uploads/ folder
# Detects new/changed/deleted files instantly
# Auto-commits and pushes to GitHub
# =============================================

$repoPath = Split-Path -Parent $MyInvocation.MyCommand.Path
$uploadsDir = Join-Path $repoPath "uploads"
$logFile = Join-Path $repoPath "upload-sync.log"
$debounceTimer = $null
$pendingPush = $false

Set-Location $repoPath

function Write-Log {
    param([string]$Message, [string]$Color = "Cyan")
    $ts = Get-Date -Format "yyyy-MM-dd HH:mm:ss"
    $entry = "[$ts] $Message"
    Write-Host $entry -ForegroundColor $Color
    Add-Content -Path $logFile -Value $entry
}

function Push-Changes {
    Set-Location $repoPath

    # Stage everything in uploads/
    git add uploads/ 2>$null

    # Check staged changes
    $staged = git diff --cached --name-only 2>$null

    if ($staged) {
        $fileCount = ($staged | Measure-Object).Count
        $fileList = ($staged | Select-Object -First 5) -join ", "
        if ($fileCount -gt 5) { $fileList += " (+$($fileCount - 5) more)" }

        $ts = Get-Date -Format "yyyy-MM-dd HH:mm"
        git commit -m "uploads: $fileCount file(s) changed [$ts]" 2>$null

        $result = git push origin main 2>&1
        if ($LASTEXITCODE -eq 0) {
            Write-Log "PUSHED $fileCount file(s): $fileList" "Green"
        } else {
            Write-Log "PUSH FAILED: $result" "Red"
        }
    } else {
        Write-Log "No staged changes to push" "DarkGray"
    }
}

function Start-DebouncePush {
    # Cancel existing timer
    if ($global:debounceTimer) {
        $global:debounceTimer.Stop()
        $global:debounceTimer.Dispose()
    }

    # Create new 3-second debounce timer
    # Waits 3 seconds after last change before pushing (batches rapid uploads)
    $global:debounceTimer = New-Object System.Timers.Timer
    $global:debounceTimer.Interval = 3000
    $global:debounceTimer.AutoReset = $false
    Register-ObjectEvent $global:debounceTimer Elapsed -Action {
        Push-Changes
    }
    $global:debounceTimer.Start()
}

# ─── Validate uploads folder ───
if (-not (Test-Path $uploadsDir)) {
    Write-Log "Creating uploads/ folder..." "Yellow"
    New-Item -ItemType Directory -Path $uploadsDir -Force | Out-Null
}

# ─── Git config ───
git config user.name  "upload-sync[bot]" 2>$null
git config user.email "upload-sync[bot]@users.noreply.github.com" 2>$null

# ─── Create FileSystemWatcher ───
$watcher = New-Object System.IO.FileSystemWatcher
$watcher.Path = $uploadsDir
$watcher.IncludeSubdirectories = $true
$watcher.EnableRaisingEvents = $false

# Watch for all changes
$watcher.NotifyFilter = [System.IO.NotifyFilters]::FileName -bor
                         [System.IO.NotifyFilters]::LastWrite -bor
                         [System.IO.NotifyFilters]::Size -bor
                         [System.IO.NotifyFilters]::CreationTime

# ─── Event handlers ───
$onCreated = {
    $path = $Event.SourceEventArgs.FullPath
    $name = Split-Path $path -Leaf
    Write-Host "`n[NEW] $name" -ForegroundColor Green
    Start-DebouncePush
}

$onChanged = {
    $path = $Event.SourceEventArgs.FullPath
    $name = Split-Path $path -Leaf
    Write-Host "`n[MODIFIED] $name" -ForegroundColor Yellow
    Start-DebouncePush
}

$onDeleted = {
    $path = $Event.SourceEventArgs.FullPath
    $name = Split-Path $path -Leaf
    Write-Host "`n[DELETED] $name" -ForegroundColor Red
    Start-DebouncePush
}

$onRenamed = {
    $oldName = Split-Path $Event.SourceEventArgs.OldPath -Leaf
    $newName = Split-Path $Event.SourceEventArgs.FullPath -Leaf
    Write-Host "`n[RENAMED] $oldName -> $newName" -ForegroundColor Magenta
    Start-DebouncePush
}

# ─── Register events ───
Register-ObjectEvent $watcher "Created" -Action $onCreated
Register-ObjectEvent $watcher "Changed" -Action $onChanged
Register-ObjectEvent $watcher "Deleted" -Action $onDeleted
Register-ObjectEvent $watcher "Renamed" -Action $onRenamed

# ─── Start watching ───
$watcher.EnableRaisingEvents = $true

Write-Log "========================================" "White"
Write-Log " Upload Sync - REAL-TIME WATCHER" "White"
Write-Log "========================================" "White"
Write-Log "Watching: $uploadsDir" "White"
Write-Log "Events: Created, Changed, Deleted, Renamed" "White"
Write-Log "Debounce: 3 seconds (batches rapid uploads)" "White"
Write-Log "Press CTRL+C to stop" "White"
Write-Log "========================================" "White"

# ─── Keep alive ───
try {
    while ($true) {
        Start-Sleep -Seconds 1
    }
} finally {
    # Cleanup on exit
    $watcher.EnableRaisingEvents = $false
    $watcher.Dispose()
    Unregister-Event -SourceIdentifier *
    if ($global:debounceTimer) {
        $global:debounceTimer.Stop()
        $global:debounceTimer.Dispose()
    }
    Write-Log "Watcher stopped." "Yellow"
}
