# PowerShell script to add global-standards.css to all HTML files
# This script will update all HTML files in the Web App directory

$rootPath = "c:\Users\ROG\Desktop\Web App"
$htmlFiles = Get-ChildItem -Path $rootPath -Filter "*.html" -Recurse

$globalCssLink = '    <!-- Global CSS Standards -->' + "`n" + '    <link rel="stylesheet" href="CSS/global-standards.css">'
$fontAwesomePattern = '<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome'

$updatedCount = 0
$skippedCount = 0

foreach ($file in $htmlFiles) {
    $content = Get-Content $file.FullName -Raw
    
    # Skip if global-standards.css is already included
    if ($content -match 'global-standards\.css') {
        Write-Host "Skipping $($file.Name) - already has global-standards.css" -ForegroundColor Yellow
        $skippedCount++
        continue
    }
    
    # Find the first CSS link and add global-standards.css before it
    if ($content -match '(<link rel="stylesheet"[^>]*>)') {
        $firstCssLink = $matches[1]
        
        # Add global-standards.css before the first CSS link
        $newContent = $content -replace [regex]::Escape($firstCssLink), ($globalCssLink + "`n" + '    <!-- Page Specific CSS -->' + "`n" + '    ' + $firstCssLink)
        
        # Write the updated content back to the file
        Set-Content -Path $file.FullName -Value $newContent -NoNewline
        
        Write-Host "Updated $($file.Name)" -ForegroundColor Green
        $updatedCount++
    } else {
        Write-Host "Skipping $($file.Name) - no CSS links found" -ForegroundColor Red
        $skippedCount++
    }
}

Write-Host "`n========================================" -ForegroundColor Cyan
Write-Host "Summary:" -ForegroundColor Cyan
Write-Host "Updated: $updatedCount files" -ForegroundColor Green
Write-Host "Skipped: $skippedCount files" -ForegroundColor Yellow
Write-Host "========================================" -ForegroundColor Cyan
