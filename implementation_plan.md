next plan:

Bugs found — fix these

booked_dates.php Wrong payment_status value

Line 16 uses payment_status = 'unpaid' but your database ENUM only has 'pending', 'paid', 'refunded'. The cleanup query never matches anything. Change it to payment_status = 'pending' to match create.php.

config.local.php Old Khalti keys still in file

The test keys c2f3a0c0... and 84e4e3ea... are still in config.local.php — the same compromised keys from the original repo. You need to go to the Khalti portal and generate new test keys, then replace them here.

signup.php Already-logged-in redirect is stale

Line 9-10: when a user is already logged in and hits signup.php, it still redirects to the old PHP paths (admin/admin_dashboard.php, hotel_staff/staff_dashboard.php, guest/guest_dashboard.php). These pages don't exist anymore. Should redirect to /version2/app/{role}/dashboard.

Still missing from the checklist

StaffDashboard.jsx Invoice not upgraded

The invoice PDF is identical to the original — purple header, no charges breakdown, no nights count, no guest phone/email, no transaction ID, no service charge, no VAT, no hotel address, no footer note. This was the main invoice feedback and nothing changed.

fonts Hash-named files not renamed

All 14 woff2 files in assets/fonts/ still have hash names like 02384cdf43e3d24b76c57c66dfd114bf.woff2. Item 31 from the checklist not done.

StaffDashboard.jsx Skeleton loaders not added

All three dashboards (Admin, Guest, Staff) still show a plain spinner during loading. Item 33 not done.

staff/dashboard API Invoice missing guest phone/email

The staff.php API doesn't return guest_phone or guest_email in its SELECT, so the invoice can't show them even if you add those fields to the PDF. Add u.phone as guest_phone, u.email as guest_email to both checkin and checkout queries.
