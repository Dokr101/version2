# HRMS — Full Improvement Plan (version2)
> Generated from live code audit of your uploaded zip.  
> Each item has a **Ready-to-use prompt** you can paste directly.

---

## Table of Contents
1. [🔐 Security Fixes](#1--security-fixes)
2. [🐛 Bug Fixes](#2--bug-fixes)
3. [🏗️ Architecture & Code Quality](#3-️-architecture--code-quality)
4. [🎨 UI/UX Improvements](#4--uiux-improvements)
5. [✨ Missing Features](#5--missing-features)

---

## 1. 🔐 Security Fixes

### 1.1 — Khalti API Keys Hardcoded as Fallback in `khalti_config.php`
**Severity: CRITICAL**

`khalti_config.php` has real API keys hardcoded as default fallbacks:
```php
define('KHALTI_PUBLIC_KEY', ... ? ... : 'c2f3a0c0a3714fde883e4d57555f39c6');
define('KHALTI_SECRET_KEY', ... ? ... : '84e4e3ea15914437a2d581cf4fccb801');
```
Even though `config.local.php` is in `.gitignore`, the fallback keys are directly in committed PHP. If `config.local.php` is missing, real keys are exposed in source.

**Prompt:**
```
In /version2/includes/khalti_config.php, remove the hardcoded fallback API keys. 
Replace them so that if KHALTI_PUBLIC_KEY_LOCAL or KHALTI_SECRET_KEY_LOCAL are not 
defined (i.e. config.local.php is absent), the system must throw a clear exception 
like: throw new RuntimeException('Khalti keys not configured. Add them to config.local.php.');
Do NOT define a default value. Also add a check at the top of initiate_khalti_payment.php 
that calls this file and wraps the whole payment logic in a try/catch so the user 
gets a readable error page, not a PHP fatal.
```

---

### 1.2 — Login Rate Limiting Stored in Session (Bypassable)
**Severity: HIGH**

The brute-force lockout in `auth/login.php` stores the attempt count in `$_SESSION`. An attacker can bypass this by simply deleting their cookies (new session = reset counter).

**Prompt:**
```
In /version2/auth/login.php, replace the session-based login attempt counter with 
a database-backed one. Create a table called login_attempts with columns: ip_address 
(VARCHAR 45), attempts (INT), last_attempt (DATETIME). On each failed login, INSERT 
or UPDATE a row keyed by the user's IP (use $_SERVER['REMOTE_ADDR']). Block the IP 
for 15 minutes after 5 failed attempts. On successful login, delete the row for 
that IP. This ensures clearing cookies does not reset the lockout.
```

---

### 1.3 — CSRF Tokens Missing on All State-Changing Forms
**Severity: HIGH**

`auth/login.php`, `auth/signup.php`, and all PHP forms lack CSRF token validation. Any state-changing POST is vulnerable to cross-site request forgery.

**Prompt:**
```
Add CSRF protection to all HTML forms in the PHP layer (login.php, signup.php, 
homepage.php). In /version2/includes/config.php, add two functions:
  - generateCsrfToken(): generates a random token, stores it in $_SESSION['csrf_token'], 
    and returns it.
  - validateCsrfToken(): reads $_POST['csrf_token'], compares with session using 
    hash_equals(), and calls die() with a 403 if it doesn't match.
In each form, output: <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
At the top of each POST handler, call validateCsrfToken() before any other logic.
```

---

### 1.4 — `booking_id` in Khalti Payment URL is Not Validated as Integer
**Severity: MEDIUM**

`guest/initiate_khalti_payment.php` uses `$_GET['booking_id']` directly in a PDO query but never casts it to an integer. While PDO prepared statements protect against SQL injection, using `intval()` is an extra safety layer and should be enforced.

**Prompt:**
```
In /version2/guest/initiate_khalti_payment.php and /version2/guest/payment_verify.php,
change:
  $booking_id = $_GET['booking_id'];
to:
  $booking_id = isset($_GET['booking_id']) ? intval($_GET['booking_id']) : 0;
  if ($booking_id <= 0) { /* redirect to error */ }
Do the same for booking_id in any other guest PHP files. Add this same integer-cast 
pattern in /version2/guest/verify_pending_payment.php as well.
```

---

### 1.5 — Open Redirect on Login via `?redirect=` Parameter
**Severity: MEDIUM**

`auth/login.php` checks `strpos($redirect_val, '/version2/app/') === 0` before redirecting, which is good. But `signup.php` only checks `strpos($redirect_val, '/version2/') === 0`, which is broader and could be bypassed.

**Prompt:**
```
In /version2/auth/signup.php, tighten the redirect validation to match login.php:
Change:
  strpos($_GET['redirect'], '/version2/') === 0
To:
  strpos($_GET['redirect'], '/version2/app/') === 0

Also create a shared helper function in includes/config.php called safeRedirect($url) 
that centralizes this check and is used by both login.php and signup.php so the 
validation cannot drift again.
```

---

### 1.6 — Sensitive Error Details Leaked to Browser in Production
**Severity: MEDIUM**

`includes/config.php` runs `die("Connection failed: " . $e->getMessage())` which prints the database host, DB name, and credentials error to the browser in production.

**Prompt:**
```
In /version2/includes/config.php, replace:
  die("Connection failed: " . $e->getMessage());
with:
  error_log("DB Connection failed: " . $e->getMessage());
  die("A system error has occurred. Please try again later.");
Also add display_errors=Off and log_errors=On to a .htaccess or php.ini configuration 
note, and document this in the README.
```

---

## 2. 🐛 Bug Fixes

### 2.1 — `window.confirm()` Used for Cancellation — Blocks UX on Mobile
**Severity: HIGH**

`GuestDashboard.jsx` and `StaffDashboard.jsx` use `window.confirm()` for destructive actions (cancel booking, check-in, check-out). This is blocked in many mobile browsers and cross-origin iframes, and it's jarring UX.

**Prompt:**
```
In /version2/app/src/pages/guest/GuestDashboard.jsx and 
/version2/app/src/pages/staff/StaffDashboard.jsx, replace all window.confirm() calls 
with a React-based confirmation modal. 

Create a reusable component at /version2/app/src/components/ui/ConfirmDialog.jsx 
that accepts: isOpen, title, message, confirmLabel, onConfirm, onCancel props and 
renders a modal (using the existing Modal.jsx + createPortal) with a confirm button 
(red/danger style) and cancel button. 

Replace every window.confirm(...) in the codebase with a useState hook that controls 
this dialog. The delete/cancel action should only fire after the user clicks the 
confirm button inside the dialog.
```

---

### 2.2 — Tax (13% VAT) Is Shown in Frontend But NOT Added to Backend Total
**Severity: HIGH**

In `BrowseRooms.jsx`, the booking modal shows:
```js
const tax = rawCost * 0.13;
const totalCost = rawCost + tax;
```
But `api/bookings/create.php` calculates:
```php
$total_price = $room['price'] * $nights; // no tax
```
The amount charged to the DB and sent to Khalti is the pre-tax amount. There's a mismatch between what the user sees and what gets recorded/charged.

**Prompt:**
```
Fix the tax calculation mismatch between frontend and backend.

Option A (recommended — add tax server-side):
In /version2/api/bookings/create.php, after calculating $total_price:
  $tax_rate = 0.13;
  $tax_amount = $total_price * $tax_rate;
  $total_price_with_tax = $total_price + $tax_amount;
Store $total_price_with_tax as the booking total. Update the DB INSERT to use this value.

In /version2/app/src/pages/guest/BrowseRooms.jsx, keep the tax display as-is 
(it's already correct visually). The backend total should now match what the user sees.

Also add a 'tax_amount' column to the bookings table migration and store it separately 
so it can be shown on invoices.
```

---

### 2.3 — No Pending Booking Count Shown to Guest on Dashboard
**Severity: MEDIUM**

`GuestDashboard.jsx` shows "Total Bookings", "Confirmed", "Pending", "Total Spent" as stat cards — but the "Pending" stat includes bookings that may have expired (15-minute auto-cancel window). Guest sees stale pending count until page refresh.

**Prompt:**
```
In /version2/api/dashboard/guest.php, add the expired pending booking cleanup query 
(same one used in api/bookings/create.php) at the top before running the stats query:
  $pdo->query("UPDATE bookings SET status='cancelled' WHERE status='pending' 
    AND payment_status='pending' AND created_at < NOW() - INTERVAL 15 MINUTE 
    AND user_id = {$_SESSION['user_id']}");

This ensures the Guest Dashboard always shows accurate booking counts. 
Also, in GuestDashboard.jsx, add auto-refresh every 60 seconds using useEffect 
with setInterval when there are pending bookings (stats.pendingBookings > 0), 
so the count updates without a manual page reload.
```

---

### 2.4 — Reports Page Has No Chart Library — Bar Chart is CSS-Only
**Severity: MEDIUM**

`Reports.jsx` renders a revenue bar chart using pure CSS widths based on `maxRevenue`. This breaks for months with 0 revenue and the bars look inconsistent. There is no proper chart library being used.

**Prompt:**
```
Add a proper chart to /version2/app/src/pages/admin/Reports.jsx.

Install recharts:
  cd /version2/app && npm install recharts

In Reports.jsx, import:
  import { BarChart, Bar, XAxis, YAxis, CartesianGrid, Tooltip, ResponsiveContainer } from 'recharts';

Replace the CSS progress-bar-style chart with a ResponsiveContainer + BarChart 
using the monthlyRevenue array (field names: month, revenue). 
Use a clean monochrome style (bar color: #222222, grid lines: #e2e8f0, tooltip with 
white background and gray border). Remove all the manual maxRevenue calculation 
and the old CSS bar markup.
```

---

### 2.5 — Staff Checkin/Checkout Routes Are Duplicate of StaffDashboard
**Severity: LOW**

In `App.jsx`:
```jsx
<Route path="/staff/checkin" element={<StaffDashboard />} />
<Route path="/staff/checkout" element={<StaffDashboard />} />
```
Both routes just render `StaffDashboard` again. The sidebar links navigate to these but the page is identical to `/staff/dashboard`. There's no functional difference and no tab auto-selection on load.

**Prompt:**
```
In /version2/app/src/pages/staff/StaffDashboard.jsx, read the current URL pathname 
to auto-select the active tab on load:

  import { useLocation } from 'react-router-dom';
  const location = useLocation();
  
  useEffect(() => {
    if (location.pathname.includes('checkout')) setActiveRegister('checkout');
    else setActiveRegister('checkin');
  }, [location.pathname]);

This makes the sidebar links actually switch the visible tab without any route changes 
needed. Update the App.jsx routes to keep /staff/checkin and /staff/checkout pointing 
to StaffDashboard (they already do), and update Sidebar.jsx staff menu items to 
navigate to /staff/checkin and /staff/checkout respectively.
```

---

### 2.6 — `BrowseRooms.jsx` Is Rendered Inside DashboardLayout for Logged-In Users But Not for Public
**Severity: LOW**

When a guest is logged in, `BrowseRooms.jsx` renders inside `DashboardLayout` (sidebar visible). When not logged in, it uses `PublicHeader`. This conditional layout logic is handled inside the component itself, which means the component needs to know about both layouts. If more pages need this dual-mode, the pattern becomes hard to maintain.

**Prompt:**
```
Refactor /version2/app/src/pages/guest/BrowseRooms.jsx to remove the layout 
conditional logic from inside the component. 

In App.jsx, change the /guest/rooms route to a custom element that wraps the component 
in the correct layout based on auth state:
  - If user is logged in: wrap in <DashboardLayout>
  - If user is not logged in: wrap in a PublicLayout (just the PublicHeader + content)

Create a small wrapper component called <PublicOrDashboardRoute> in App.jsx that 
reads useAuth() and returns the appropriate layout. BrowseRooms.jsx should then be 
a pure presentational component that doesn't care about its surrounding layout.
```

---

## 3. 🏗️ Architecture & Code Quality

### 3.1 — No Global Error Boundary in React App
**Severity: MEDIUM**

If any component throws during render (network error, undefined property), the entire app goes blank with a white screen and no message to the user.

**Prompt:**
```
Create /version2/app/src/components/ErrorBoundary.jsx as a React class component 
that implements componentDidCatch and getDerivedStateFromError. When an error is 
caught, render a friendly fallback UI with the HRMS logo, the message 
"Something went wrong. Please refresh the page.", and a "Reload" button that calls 
window.location.reload(). 

Wrap the entire <Routes> in App.jsx with this ErrorBoundary so no unhandled error 
causes a white-screen crash.
```

---

### 3.2 — All API Calls Use Raw `axios.get/post` With Hardcoded Paths
**Severity: MEDIUM**

Every page calls `axios.get('/version2/api/...')` directly. If the base path ever changes (e.g. deployment to a subdirectory), 30+ places must be updated manually.

**Prompt:**
```
Create /version2/app/src/lib/api.js that exports a configured axios instance:
  import axios from 'axios';
  const api = axios.create({ baseURL: '/version2/api', withCredentials: true });
  export default api;

Then do a find-and-replace across all JSX files:
  - Replace: import axios from 'axios'; ... axios.get('/version2/api/...')
  - With: import api from '../../lib/api'; ... api.get('/...')
  
Remove the /version2/api prefix from all URL strings since it's now in baseURL.
This makes the base path a single source of truth.
```

---

### 3.3 — Inline `<style>` Tags in Every Component Cause CSS Duplication
**Severity: LOW**

`Sidebar.jsx`, `AdminDashboard.jsx`, `GuestDashboard.jsx`, `StatCard.jsx`, and others all have `<style>` tags with hundreds of lines of CSS. This means styles are injected into the DOM once per component mount and are not deduplicated. For a college project this is acceptable but creates maintenance problems.

**Prompt:**
```
Move all page-specific and component-specific CSS from inline <style> tags into 
dedicated CSS modules or separate .css files co-located with each component.

For example:
  - Create /version2/app/src/pages/admin/AdminDashboard.module.css
  - Move all the CSS from the AdminDashboard.jsx <style> tag into it
  - Import it: import styles from './AdminDashboard.module.css';
  - Replace className="stats-row" with className={styles['stats-row']}

Repeat this for StatCard.jsx, Sidebar.jsx, Modal.jsx, and all other components 
that have embedded <style> tags. Keep global tokens and shared utilities in index.css.
```

---

### 3.4 — No Loading Skeleton — Just a Spinner
**Severity: LOW**

Every page shows a plain `<div class="spinner">` during data fetching. This looks unpolished and causes layout shift when data loads.

**Prompt:**
```
Create a reusable skeleton loader component at 
/version2/app/src/components/ui/Skeleton.jsx.

It should accept: width (default '100%'), height (default '1rem'), borderRadius 
(default '6px'), and count (number of rows, default 1) props.

The animation should be a shimmer effect using a CSS keyframe:
  @keyframes shimmer {
    0% { background-position: -200% 0; }
    100% { background-position: 200% 0; }
  }
  background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
  background-size: 200% 100%;

Use this component in:
  - AdminDashboard.jsx: replace the spinner with skeleton StatCards (4 cards) 
    and a skeleton activity list (5 rows)
  - GuestDashboard.jsx: same pattern
  - BrowseRooms.jsx: skeleton room cards (grid of 6)
  - ManageRooms.jsx / ManageStaff.jsx: skeleton table rows
```

---

## 4. 🎨 UI/UX Improvements

### 4.1 — Modal Has No Entrance/Exit Animation
**Severity: MEDIUM**

`Modal.jsx` renders/unmounts instantly via `if (!open) return null`. There is no fade or scale animation for open/close transitions.

**Prompt:**
```
Update /version2/app/src/components/ui/Modal.jsx to add smooth entrance and exit 
animations without needing a third-party library.

Change the implementation so that:
1. The modal is always rendered in the DOM (don't return null) but starts invisible.
2. Use a CSS class that toggles based on the open prop.
3. Add these keyframes to index.css:
   @keyframes modalFadeIn {
     from { opacity: 0; transform: scale(0.95) translateY(8px); }
     to { opacity: 1; transform: scale(1) translateY(0); }
   }
   @keyframes modalFadeOut {
     from { opacity: 1; transform: scale(1) translateY(0); }
     to { opacity: 0; transform: scale(0.95) translateY(8px); }
   }
4. When open=true, apply modalFadeIn (0.2s ease).
5. When open switches to false, apply modalFadeOut (0.15s ease) and only unmount 
   (hide overlay) after the animation completes using onAnimationEnd.
```

---

### 4.2 — Sidebar Has No Active State Animation for Nav Links
**Severity: LOW**

The `nav-link--active` class applies a background color change, but there's no transition when switching between nav items.

**Prompt:**
```
In /version2/app/src/components/layout/Sidebar.jsx, improve the active nav link 
indicator with a smooth animated indicator bar.

The .nav-link-indicator already exists (left-side bar). Add:
1. A sliding background highlight that moves between nav items using CSS transition 
   on background-color and the scaleY transform already on .nav-link-indicator.
2. When .nav-link--active is applied, also add a subtle icon color transition:
   .nav-link--active .nav-link-icon { 
     color: var(--primary);
     transition: color 0.2s ease;
   }
3. Add a hover scale effect to the icon:
   .nav-link:hover .nav-link-icon {
     transform: scale(1.1);
     transition: transform 0.15s ease;
   }
4. Add a ripple/pulse animation on click by briefly adding a class via onClick 
   and removing it after 300ms.
```

---

### 4.3 — StatCards Have No Entrance Animation (Stagger)
**Severity: LOW**

Dashboard stat cards appear instantly. The `.stagger-children` class exists in the codebase but may not be defined/applied correctly for entrance animations.

**Prompt:**
```
In /version2/app/src/index.css, define the stagger-children animation system:

  @keyframes slideUpFade {
    from { opacity: 0; transform: translateY(16px); }
    to { opacity: 1; transform: translateY(0); }
  }

  .stagger-children > * {
    animation: slideUpFade 0.3s ease forwards;
    opacity: 0;
  }

  .stagger-children > *:nth-child(1) { animation-delay: 0.05s; }
  .stagger-children > *:nth-child(2) { animation-delay: 0.10s; }
  .stagger-children > *:nth-child(3) { animation-delay: 0.15s; }
  .stagger-children > *:nth-child(4) { animation-delay: 0.20s; }
  .stagger-children > *:nth-child(5) { animation-delay: 0.25s; }
  .stagger-children > *:nth-child(6) { animation-delay: 0.30s; }

In StatCard.jsx, add a hover effect:
  .stat-card:hover {
    transform: translateY(-3px);
    box-shadow: var(--shadow-md);
    transition: transform 0.2s ease, box-shadow 0.2s ease;
  }
```

---

### 4.4 — Homepage PHP Does Not Match React App's Visual Language
**Severity: MEDIUM**

`homepage.php` and `auth/login.php` use `style.css` (older PHP styling) while the React app uses `index.css`. The font choices, card styles, and button aesthetics are visually inconsistent.

**Prompt:**
```
Audit /version2/style.css (used by homepage.php, login.php, signup.php) and 
/version2/app/src/index.css (used by the React app).

Make these specific changes to style.css to align the PHP pages with the React UI:
1. Match the font stack: ensure both use Inter for body and Outfit for headings 
   (the local font files are already in /assets/fonts/).
2. Match button styles: .auth-modal-btn should use the same border-radius (10px), 
   font-weight (600), and padding as .btn in index.css.
3. Match form input styles: same border color (#e2e8f0), border-radius (10px), 
   focus ring (box-shadow with primary color), and padding as index.css inputs.
4. Match card styling: .auth-modal should use the same box-shadow as .glass-card.

Do NOT change the React index.css — only update style.css to match it.
```

---

### 4.5 — Booking Modal Shows No Visual Booked-Date Calendar
**Severity: MEDIUM**

The booking modal in `BrowseRooms.jsx` fetches booked dates from the API but doesn't show them visually. The guest has to manually avoid those dates using the basic `<input type="date">` which doesn't indicate unavailable dates.

**Prompt:**
```
In /version2/app/src/pages/guest/BrowseRooms.jsx, improve the date selection UX 
in the booking modal.

Since we're not using a date picker library, enhance the existing date inputs:
1. Below the check-in and check-out inputs, add a "Booked Dates" section that renders 
   a simple visual list of unavailable date ranges fetched from bookedDates state.
   Show them as pill badges: "Jun 15 → Jun 18  ●  Unavailable" in a red-tinted style.
2. Add client-side validation: when the user selects dates that overlap with any 
   booked range, show an inline error message below the checkout input in red 
   ("These dates overlap with an existing booking") and disable the Confirm button.
3. Show the number of nights prominently in the modal summary as a large badge 
   (e.g. "3 Nights") using a highlighted box, not just inline text.
```

---

### 4.6 — No Empty State Illustrations
**Severity: LOW**

When there are no bookings, no rooms, or no staff, pages show plain text like "No booking activities registered yet." with no visual treatment.

**Prompt:**
```
Create a reusable EmptyState component at 
/version2/app/src/components/ui/EmptyState.jsx that accepts:
  - icon: a Lucide icon component
  - title: main message string  
  - subtitle: optional secondary message
  - action: optional { label, onClick } for a CTA button

Style it as a centered block with the icon in a large light-gray circle 
(64x64, border-radius 50%, background #f1f5f9), title in --text-primary at 1rem, 
subtitle in --text-muted at 0.85rem, and the action button using .btn .btn-primary.

Replace the plain text fallbacks in:
  - AdminDashboard.jsx "No booking activities registered yet."
  - GuestDashboard.jsx "No bookings yet."
  - ManageRooms.jsx "No rooms found."
  - ManageStaff.jsx "No staff members found."
  - MyBookings.jsx "No bookings found."
```

---

### 4.7 — DataTable Has No Loading State or Pagination
**Severity: MEDIUM**

`DataTable.jsx` renders all rows at once. For tables like AllBookings, PaymentRecords this could be hundreds of rows. There's also no loading state — the table area is blank until data arrives.

**Prompt:**
```
Update /version2/app/src/components/ui/DataTable.jsx to add:

1. CLIENT-SIDE PAGINATION: accept a pageSize prop (default: 10). Add internal state 
   for currentPage (start at 1). Show only the rows for the current page. 
   Render pagination controls below the table:
   "Showing X–Y of Z results" and Previous / Next buttons. 
   Disable Previous on page 1, Next on last page.

2. LOADING SKELETON: accept a loading prop. When loading=true, render 5 skeleton 
   rows using the Skeleton component (full-width, height 40px, borderRadius 6px) 
   instead of real data rows.

3. SEARCH: accept a searchable prop (boolean, default false). When true, render a 
   search input above the table. Filter rows client-side across all string columns 
   (case-insensitive includes). Reset to page 1 on search input change.

Update AllBookings.jsx, PaymentRecords.jsx, ManageStaff.jsx, ManageRooms.jsx 
to pass loading={loading} and pageSize={10} to DataTable.
```

---

## 5. ✨ Missing Features

### 5.1 — No "Forgot Password" Flow
**Severity: MEDIUM**

`auth/login.php` has no password reset link. Users who forget their password have no self-service recovery path.

**Prompt:**
```
Add a Forgot Password flow to HRMS.

1. Create /version2/auth/forgot_password.php:
   - Show a form with one email input.
   - On POST, check if the email exists in the users table.
   - Generate a secure token: $token = bin2hex(random_bytes(32));
   - Store it in a new table: password_resets (email, token, expires_at = NOW() + 1 HOUR).
   - Send an email using the existing email_helper.php with a link to:
     /version2/auth/reset_password.php?token={$token}
   - Always show the same success message regardless of whether email exists 
     (prevents email enumeration).

2. Create /version2/auth/reset_password.php:
   - Validate the token exists and hasn't expired.
   - Show a form with new_password and confirm_password fields.
   - On valid POST, update the user's password with password_hash() and 
     delete the token row.

3. In /version2/auth/login.php, add a "Forgot password?" link below the submit button 
   linking to /version2/auth/forgot_password.php.
```

---

### 5.2 — No Room Image Upload — Rooms Use No Images in Admin Panel
**Severity: MEDIUM**

`ManageRooms.jsx` and the room listing API return room data but there's no image per room (BrowseRooms uses a placeholder/hero). The admin cannot upload a room photo.

**Prompt:**
```
Add room image support to HRMS.

1. Add an image_url column to the rooms table:
   ALTER TABLE rooms ADD COLUMN image_url VARCHAR(255) DEFAULT NULL;

2. In /version2/api/rooms/manage.php (POST for add/edit room), accept a file upload:
   - Use $_FILES['image'] if present.
   - Validate: type must be image/jpeg, image/png, image/webp; max 2MB.
   - Save to /version2/assets/uploads/rooms/{room_id}_{timestamp}.jpg using move_uploaded_file().
   - Store the relative URL in the rooms table.

3. In ManageRooms.jsx, add a file input to the Add/Edit Room modal:
   <input type="file" accept="image/*" onChange={handleImageChange} />
   Send the image via FormData instead of JSON:
   const formData = new FormData();
   formData.append('image', imageFile);
   formData.append('type', roomType); // etc.

4. In BrowseRooms.jsx, display room.image_url if present, otherwise show the 
   existing hero.png placeholder.
```

---

### 5.3 — No CSV/Excel Export for Admin Reports
**Severity: LOW**

`Reports.jsx` shows analytics data but has no export button. Admins cannot download a report.

**Prompt:**
```
Add a CSV export button to /version2/app/src/pages/admin/Reports.jsx.

Do NOT install a library. Use this pure-JS approach:

function exportToCSV(data, filename) {
  if (!data || data.length === 0) return;
  const headers = Object.keys(data[0]);
  const rows = data.map(row => headers.map(h => `"${row[h] ?? ''}"`).join(','));
  const csv = [headers.join(','), ...rows].join('\n');
  const blob = new Blob([csv], { type: 'text/csv' });
  const url = URL.createObjectURL(blob);
  const a = document.createElement('a');
  a.href = url;
  a.download = filename;
  a.click();
  URL.revokeObjectURL(url);
}

Add a "Export CSV" button in the top-right of the Reports page header. 
On click, call exportToCSV(monthlyRevenue, 'hrms-report.csv').
Style it with a secondary button style and a Download icon from lucide-react.
```

---

### 5.4 — No Notification Bell / In-App Notification System
**Severity: LOW**

There is no notification system. Staff don't know when a new guest checks in. Admins don't see alerts beyond the "pending staff approval" badge.

**Prompt:**
```
Add a basic in-app notification bell to the TopBar for Admin and Staff users.

1. Create /version2/api/notifications/list.php:
   Query recent notable events for the logged-in admin or staff:
   - New bookings in last 24 hours (for admin)
   - Pending check-ins today (for staff)
   Return: [{ id, type, message, created_at, read: false }]
   Hardcode these as derived queries (no separate notifications table needed).

2. In /version2/app/src/components/layout/TopBar.jsx, add a bell icon (Bell from 
   lucide-react) to the right of the header. Fetch from the notifications API on 
   mount (poll every 60 seconds with setInterval).
   Show a red badge with the count if count > 0.

3. On click, show a dropdown panel (positioned absolute below the bell) listing 
   the notifications with a message and timestamp. Style as a white card with 
   box-shadow, max 5 items, overflow-y: auto.

4. Add a "Mark all read" button that clears the badge (local state only — 
   no DB write needed for a college project).
```

---

## Quick Priority Order

| Priority | Item | Effort |
|----------|------|--------|
| 🔴 Do Now | 1.1 Hardcoded API Keys | 15 min |
| 🔴 Do Now | 2.2 VAT Tax Mismatch | 20 min |
| 🔴 Do Now | 1.3 CSRF Tokens | 30 min |
| 🟠 Soon | 2.1 Replace window.confirm | 30 min |
| 🟠 Soon | 4.2 Sidebar Animation | 20 min |
| 🟠 Soon | 3.4 Skeleton Loaders | 45 min |
| 🟡 Later | 4.7 DataTable Pagination | 60 min |
| 🟡 Later | 5.1 Forgot Password | 90 min |
| 🟡 Later | 5.2 Room Image Upload | 60 min |
| 🟢 Polish | 4.1 Modal Animation | 20 min |
| 🟢 Polish | 4.3 StatCard Stagger | 15 min |
| 🟢 Polish | 4.6 Empty State | 30 min |
