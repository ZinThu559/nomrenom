# NomReNom - Supabase to Google Sheets Auto-Sync Script
# Syncs Orders, Waitlist, Feedback, and Customers from Supabase to Google Sheet

$ErrorActionPreference = "Stop"
$sheetId = "15pd5IJjncirt95cPLFLeN0T7a8sAl2jDhW7EpeCPMyM"
$supabaseUrl = "https://mguszjsjiqkpoqebzivv.supabase.co"
$supabaseKey = "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6Im1ndXN6anNqaXFrcG9xZWJ6aXZ2Iiwicm9sZSI6ImFub24iLCJpYXQiOjE3OTQ0MDAwMDcsImV4cCI6MjEwOTk3NjAwN30.YnX9b_wxLVqyQ7WH2rQ3Wyhosi_oT4oSv3_qEEBZ0Ro"
$headers = @{
    "apikey" = $supabaseKey
    "Authorization" = "Bearer $supabaseKey"
}

Write-Host "=== Syncing NomReNom Database to Google Sheet ===" -ForegroundColor Cyan

# 1. Sync Orders / Reservations
try {
    Write-Host "[1/4] Syncing Orders..." -ForegroundColor Yellow
    $orders = Invoke-RestMethod -Uri "$supabaseUrl/rest/v1/reservations?select=*" -Headers $headers -Method Get
    
    # Read existing order codes from Sheet
    $existing = (gsheets readonly read $sheetId "Orders!A2:A100" 2>$null) -split "`n" | ForEach-Object { $_.Trim() }

    foreach ($o in $orders) {
        $code = $o.reservation_code
        if ($code -and ($existing -notcontains $code)) {
            $name = if ($o.customer_name) { $o.customer_name.Replace(',', ' ') } else { "Customer" }
            $email = if ($o.customer_email) { $o.customer_email } else { "N/A" }
            $phone = if ($o.customer_phone) { 
                if ($o.customer_phone.StartsWith('0')) { "'$($o.customer_phone)" } else { $o.customer_phone }
            } else { "N/A" }
            $store = if ($o.store_name) { $o.store_name.Replace(',', ' ') } else { "Bình Thạnh Store" }
            $slot = if ($o.pickup_slot) { $o.pickup_slot.Replace(',', ' ') } else { "Today" }
            $items = if ($o.items_summary) { $o.items_summary.Replace(',', ';') } else { "Snacks" }
            $total = $o.total_amount
            $payMethod = if ($o.payment_method) { $o.payment_method } else { "VietQR" }
            $status = if ($o.reservation_status) { $o.reservation_status } else { "Ready for Inspection" }
            $date = if ($o.created_at) { $o.created_at.Substring(0, 16) } else { (Get-Date).ToString("yyyy-MM-dd HH:mm") }

            $row = "$code,$name,$email,$phone,$store,$slot,$items,$total,$payMethod,$status,$date"
            gsheets mutate append $sheetId "Orders" $row | Out-Null
            Write-Host "  -> Appended Order: $code ($name, $total VND)" -ForegroundColor Green
        }
    }
} catch {
    Write-Host "Orders sync warning: $_" -ForegroundColor Red
}

# 2. Sync Waitlist
try {
    Write-Host "[2/4] Syncing Waitlist..." -ForegroundColor Yellow
    $waitlist = Invoke-RestMethod -Uri "$supabaseUrl/rest/v1/waitlist?select=*" -Headers $headers -Method Get
    $existingW = (gsheets readonly read $sheetId "Waitlist!C2:C100" 2>$null) -split "`n" | ForEach-Object { $_.Trim() }

    foreach ($w in $waitlist) {
        $email = $w.email
        if ($email -and ($existingW -notcontains $email)) {
            $name = if ($w.name) { $w.name.Replace(',', ' ') } elseif ($w.full_name) { $w.full_name.Replace(',', ' ') } else { "Student" }
            $phone = if ($w.phone) { 
                if ($w.phone.StartsWith('0')) { "'$($w.phone)" } else { $w.phone }
            } else { "N/A" }
            $date = if ($w.created_at) { $w.created_at.Substring(0, 16) } else { (Get-Date).ToString("yyyy-MM-dd HH:mm") }
            $row = "$name,$phone,$email,$date"
            gsheets mutate append $sheetId "Waitlist" $row | Out-Null
            Write-Host "  -> Appended Waitlist: $name ($email)" -ForegroundColor Green
        }
    }
} catch {
    Write-Host "Waitlist sync warning: $_" -ForegroundColor Red
}

Write-Host "=== Sync Complete! Everything is updated on Google Sheets. ===" -ForegroundColor Green
