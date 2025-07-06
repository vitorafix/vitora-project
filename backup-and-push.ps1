# Define project path
$projectPath = "C:\xampp\htdocs\myshop"

# Define backup storage path
$backupPath = "D:\business plan\website"

# Generate timestamp for filename
$date = Get-Date -Format "yyyy-MM-dd_HH-mm-ss"
$zipName = "myshop-backup-$date.zip"

# Change to project directory
Set-Location $projectPath

# Compress the project folder
Write-Host "Compressing project folder..."
Compress-Archive -Path * -DestinationPath "$backupPath\$zipName"

# Stage all changes for git
Write-Host "Staging changes in git..."
git add .

# Prompt for commit message
$commitMsg = Read-Host "Please enter commit message"

# Commit changes
git commit -m "$commitMsg"

# Push changes to remote repo
Write-Host "Pushing changes to remote repository..."
git push origin main

Write-Host "Done."
Write-Host "Backup file saved at:"
Write-Host "$backupPath\$zipName"
