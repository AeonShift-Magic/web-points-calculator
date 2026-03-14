@echo off
REM Delete all GitHub Actions workflow runs except the latest two, across all workflows (non-interactive)

powershell -NoProfile -Command ^
    "$runs = gh run list --limit 1000 --json databaseId,createdAt | ConvertFrom-Json; " ^
    "$oldRuns = $runs | Sort-Object createdAt -Descending | Select-Object -Skip 2; " ^
    "foreach ($run in $oldRuns) { " ^
    "  Write-Host 'Deleting workflow run:' $run.databaseId; " ^
    "  echo Y | gh run delete $run.databaseId; " ^
    "}"

ECHO Done.
pause
