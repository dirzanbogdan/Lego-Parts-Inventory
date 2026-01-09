$keep = @(
"app\Config\config.php",
"app\Controllers\AuthController.php",
"app\Controllers\UpdateController.php",
"app\Core\Controller.php",
"app\Core\Migrator.php",
"app\Core\Router.php",
"app\Core\Security.php",
"app\Models\User.php",
"app\views\admin\update.php",
"app\views\auth\login.php",
"app\views\auth\register.php",
"app\bootstrap.php",
"database\schema.sql",
"database\migrations\001_init.sql",
"database\migrations\002_add_composition_to_parts.sql",
"database\migrations\003_v2_features.sql",
"public\install\index.php",
"public\.htaccess",
"public\index.php",
"public\update.php",
"scripts\update.php",
"sql\schema.sql",
".htaccess",
"LICENSE"
)

# Normalize keep list to full paths for easier comparison
$root = (Get-Location).Path
$keepFull = $keep | ForEach-Object { Join-Path $root $_ }

# Get all files excluding .git and the script itself
$files = Get-ChildItem -Recurse -File | Where-Object { 
    $_.FullName -notmatch "\\.git\\" -and 
    $_.Name -ne "cleanup_script.ps1"
}

foreach ($file in $files) {
    # Check if the file is in the keep list
    # We compare FullNames. 
    # Note: Windows paths are case-insensitive, but let's be careful.
    
    $shouldKeep = $false
    foreach ($k in $keepFull) {
        if ($file.FullName -eq $k) {
            $shouldKeep = $true
            break
        }
    }
    
    if (-not $shouldKeep) {
        Write-Host "Deleting: $($file.FullName)"
        Remove-Item $file.FullName -Force
    } else {
        Write-Host "Keeping: $($file.FullName)"
    }
}

# Clean up empty directories
# Get directories, sort by length descending to delete deepest first
$dirs = Get-ChildItem -Recurse -Directory | Where-Object { $_.FullName -notmatch "\\.git\\" } | Sort-Object -Property FullName -Descending

foreach ($dir in $dirs) {
    if ((Get-ChildItem $dir.FullName -Force).Count -eq 0) {
        Write-Host "Removing empty dir: $($dir.FullName)"
        Remove-Item $dir.FullName -Force
    }
}
