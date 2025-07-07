Write-Host "======================================="
Write-Host "Running Composer Autoload Dump..."
Write-Host "======================================="
composer dump-autoload

Write-Host "======================================="
Write-Host "Clearing & Caching Config..."
Write-Host "======================================="
php artisan config:clear
php artisan config:cache

Write-Host "======================================="
Write-Host "Optimizing Application..."
Write-Host "======================================="
php artisan optimize

Write-Host "======================================="
Write-Host "Listing Routes..."
Write-Host "======================================="
php artisan route:list

Write-Host "======================================="
Write-Host "Running Tests (if any)..."
Write-Host "======================================="
php artisan test

Write-Host "======================================="
Write-Host "All checks completed."
Write-Host "======================================="
