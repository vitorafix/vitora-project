# ===============================
# scan_cart_files.ps1
# Laravel Store Scanner for Cart/Product/Stock/Coupon/Order/Checkout/Discount/Inventory etc.
# ===============================

Write-Output "Scanning project for Product/Cart/Stock/Coupon/Order/Checkout/Purchase/Discount/Inventory/Shipment ..."

# Root path of project
$Root = "."

# Output file name with timestamp
$Output = "store_audit_$(Get-Date -Format 'yyyyMMdd_HHmmss').txt"

# Define search keywords (case-insensitive)
$Patterns = @(
    'Product', 'product_', 'Cart', 'cart_', 
    'Order', 'order_', 'Checkout', 'checkout_', 
    'Purchase', 'purchase_', 'Coupon', 'coupon_', 
    'Discount', 'discount_', 'Inventory', 'inventory_', 
    'Stock', 'stock_', 'Shipment', 'shipment_', 
    'Warehouse', 'warehouse_', 'Promo', 'promo_', 
    'Voucher', 'voucher_', 'GiftCard', 'giftcard_', 
    'Price', 'price_', 'Tax', 'tax_', 'Billing', 'billing_', 
    'Shipping', 'shipping_', 'Payment', 'payment_'
)

# Join patterns for Select-String as regex pattern (OR)
$RegexPattern = $Patterns -join '|'

# Run scan: only PHP files, exclude system/vendor/node_modules folders
Get-ChildItem -Path $Root -Recurse -File -Include *.php |
    Where-Object {
        $_.FullName -notmatch '\\vendor\\|\\node_modules\\|\.git\\|\\storage\\|\\public\\'
    } |
    Select-String -Pattern $RegexPattern -CaseSensitive:$false |
    Select-Object -ExpandProperty Path -Unique |
    Out-File $Output

Write-Output "Scan complete!"
Write-Output "Output file: $Output"

# Show number of matching files
$Count = (Get-Content $Output | Measure-Object -Line).Lines
Write-Output "Total matching files: $Count"

# Show file paths
Write-Output "----------------------------------------"
Get-Content $Output
Write-Output "----------------------------------------"
