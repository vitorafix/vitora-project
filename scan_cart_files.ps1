# ===============================
# scan_cart_files.ps1
# Laravel Cart/Product/Order scanner for PowerShell (English Only)
# ===============================

Write-Output "Scanning project for Product/Cart/Order/Checkout/Purchase ..."

# Root path
$Root = "."

# Output file name
$Output = "cart_product_audit_$(Get-Date -Format 'yyyyMMdd_HHmmss').txt"

# Run scan: only PHP files, exclude system folders
Get-ChildItem -Path $Root -Recurse -File -Include *.php |
    Where-Object {
        $_.FullName -notmatch '\\vendor\\|\\node_modules\\|\\.git\\|\\storage\\|\\public\\'
    } |
    Select-String -Pattern 'Product|Cart|Order|Checkout|Purchase|cart_|product_' |
    Select-Object -ExpandProperty Path -Unique |
    Out-File $Output

Write-Output "Scan complete!"
Write-Output "Output file: $Output"

# Show number of matching files
$Count = (Get-Content $Output | Measure-Object -Line).Lines
Write-Output "Total matching files: $Count"

# Show paths
Write-Output "----------------------------------------"
Get-Content $Output
Write-Output "----------------------------------------"
