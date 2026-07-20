# HRMS — UI/UX Animations Guide
> Where to add animations, what to add, and ready-to-use prompts.  
> All suggestions are CSS/React-based — no animation library needed.

---

## Philosophy
Less is more. Every animation here serves a purpose:
- **Entrance animations** tell the user something just loaded
- **Hover/active states** give feedback that an element is interactive  
- **Transition animations** help the user understand what just changed
- **Loading states** reduce perceived wait time

Aim for durations between **100ms – 350ms**. Anything longer feels sluggish.

---

## 1. //DONE Page Entrance — Fade + Slide Up
**Where:** Every page component on mount  
**What it does:** Makes page loads feel smooth instead of "popping in"

```css
/* Already partially in index.css — extend it */
@keyframes pageEnter {
  from {
    opacity: 0;
    transform: translateY(12px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.page {
  animation: pageEnter 0.25s ease forwards;
}
```

**Prompt:**
```
In /version2/app/src/index.css, replace the existing .animate-fade-in / .page 
animation with:

  @keyframes pageEnter {
    from { opacity: 0; transform: translateY(12px); }
    to   { opacity: 1; transform: translateY(0); }
  }
  .page { animation: pageEnter 0.25s cubic-bezier(0.22, 1, 0.36, 1) forwards; }

IMPORTANT: Do NOT use transform in the keyframe for elements that are stacking 
contexts (like .page itself if it has position). If z-index issues appear, 
switch to opacity-only: from { opacity: 0; } to { opacity: 1; }
```

---

## 2. Stat Cards — Staggered Entrance
**Where:** AdminDashboard, GuestDashboard, StaffDashboard stat card grids  
**What it does:** Cards appear one-by-one from left to right giving a polished feel

```css
@keyframes slideUpFade {
  from { opacity: 0; transform: translateY(20px); }
  to   { opacity: 1; transform: translateY(0); }
}

.stagger-children > * {
  opacity: 0;
  animation: slideUpFade 0.3s ease forwards;
}
.stagger-children > *:nth-child(1) { animation-delay: 0.05s; }
.stagger-children > *:nth-child(2) { animation-delay: 0.12s; }
.stagger-children > *:nth-child(3) { animation-delay: 0.19s; }
.stagger-children > *:nth-child(4) { animation-delay: 0.26s; }
```

**Prompt:**
```
In /version2/app/src/index.css, define the complete stagger system:

  @keyframes slideUpFade {
    from { opacity: 0; transform: translateY(20px); }
    to   { opacity: 1; transform: translateY(0); }
  }
  .stagger-children > * {
    opacity: 0;
    animation: slideUpFade 0.3s ease forwards;
  }
  .stagger-children > *:nth-child(1) { animation-delay: 0.05s; }
  .stagger-children > *:nth-child(2) { animation-delay: 0.12s; }
  .stagger-children > *:nth-child(3) { animation-delay: 0.19s; }
  .stagger-children > *:nth-child(4) { animation-delay: 0.26s; }
  .stagger-children > *:nth-child(5) { animation-delay: 0.33s; }
  .stagger-children > *:nth-child(6) { animation-delay: 0.40s; }

Ensure the .stats-row div in AdminDashboard.jsx, GuestDashboard.jsx, and 
StaffDashboard.jsx all have the class stagger-children applied.
```

---

## 3. Stat Card — Number Count-Up Animation
**Where:** StatCard.jsx — the main value display  
**What it does:** Numbers "count up" from 0 to their value on load — very satisfying for dashboards

**Prompt:**
```
In /version2/app/src/components/ui/StatCard.jsx, add a count-up animation for 
numeric values.

Add this hook inside the component:

  const [displayed, setDisplayed] = useState(0);

  useEffect(() => {
    const raw = typeof value === 'number' ? value : parseFloat(value.toString().replace(/[^0-9.]/g, ''));
    if (isNaN(raw)) return; // skip non-numeric like "Rs. 10,000"
    const duration = 700; // ms
    const steps = 40;
    const increment = raw / steps;
    let current = 0;
    const timer = setInterval(() => {
      current += increment;
      if (current >= raw) { setDisplayed(raw); clearInterval(timer); }
      else setDisplayed(Math.floor(current));
    }, duration / steps);
    return () => clearInterval(timer);
  }, [value]);

For numeric values, display `displayed` instead of `value` in the .stat-value span.
For string values (like "Rs. 5,000"), keep displaying the raw value string unchanged 
(detect by checking typeof value === 'number').
```

---

## //DONE 4. Sidebar Nav — Active Link Indicator Slide
**Where:** `Sidebar.jsx` — the left nav indicator bar  
**What it does:** The active indicator bar slides smoothly between nav items instead of jumping

**Prompt:**
```
In /version2/app/src/components/layout/Sidebar.jsx, enhance the nav-link-indicator 
with a smooth animated presence:

In the <style> tag, update:

  .nav-link-indicator {
    position: absolute;
    left: 0;
    top: 50%;
    transform: translateY(-50%) scaleY(0);
    width: 3px;
    height: 20px;
    background: var(--primary);
    border-radius: 0 3px 3px 0;
    transition: transform 0.25s cubic-bezier(0.34, 1.56, 0.64, 1);  /* spring */
  }

  .nav-link--active .nav-link-indicator {
    transform: translateY(-50%) scaleY(1);
  }

  /* Icon bounce on active */
  .nav-link--active .nav-link-icon {
    animation: iconBounce 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
  }

  @keyframes iconBounce {
    0%   { transform: scale(1); }
    50%  { transform: scale(1.2); }
    100% { transform: scale(1); }
  }

  /* Hover micro-lift on icon */
  .nav-link:hover .nav-link-icon {
    transform: translateX(2px);
    transition: transform 0.15s ease;
  }
```

---

## //DONE 5. Sidebar Collapse — Smooth Width Transition
**Where:** `Sidebar.jsx` — the collapse toggle  
**What it does:** Sidebar collapses/expands with a smooth width animation. Currently it transitions via `width var(--transition-normal)` which is set but text vanishes instantly.

**Prompt:**
```
In /version2/app/src/components/layout/Sidebar.jsx, fix the collapse animation 
so text fades out before the sidebar shrinks:

1. For .brand-text, .nav-link-label, .user-meta, .logout-btn span — add:
   transition: opacity 0.1s ease, transform 0.1s ease;

2. When sidebar is collapsing (collapsed = true), first apply:
   .sidebar--collapsed .brand-text,
   .sidebar--collapsed .nav-link-label,
   .sidebar--collapsed .user-meta,
   .sidebar--collapsed .logout-btn span {
     opacity: 0;
     transform: translateX(-6px);
     pointer-events: none;
   }

3. When expanding, the width CSS transition (0.22s) handles the sidebar growing, 
   and the labels fade back in with a 0.1s delay:
   .nav-link-label {
     transition: opacity 0.15s ease 0.1s; /* delay matches width transition */
   }

This creates a sequence: text fades → sidebar shrinks (or grows → text appears).
```

---

## 6. Modal — Smooth Open/Close
**Where:** `Modal.jsx` — all modals  
**What it does:** Modal fades and scales in on open, and animates out on close

**Prompt:**
```
Update /version2/app/src/components/ui/Modal.jsx to animate open and close.

Replace the current implementation with:

  import { useState, useEffect } from 'react';
  import { createPortal } from 'react-dom';
  import { X } from 'lucide-react';

  export const Modal = ({ open, onClose, title, subtitle, children }) => {
    const [visible, setVisible] = useState(false);
    const [animating, setAnimating] = useState(false);

    useEffect(() => {
      if (open) {
        setVisible(true);
        requestAnimationFrame(() => setAnimating(true));
      } else {
        setAnimating(false);
        const t = setTimeout(() => setVisible(false), 200);
        return () => clearTimeout(t);
      }
    }, [open]);

    if (!visible) return null;

    return createPortal(
      <div 
        className="modal-overlay-wrapper"
        style={{ opacity: animating ? 1 : 0, transition: 'opacity 0.2s ease' }}
        onClick={onClose}
      >
        <div
          className="modal-panel glass-card"
          style={{
            transform: animating ? 'scale(1) translateY(0)' : 'scale(0.95) translateY(10px)',
            opacity: animating ? 1 : 0,
            transition: 'transform 0.2s cubic-bezier(0.34, 1.56, 0.64, 1), opacity 0.2s ease'
          }}
          onClick={e => e.stopPropagation()}
        >
          <div className="modal-header-section">
            <div>
              <h3>{title}</h3>
              {subtitle && <p>{subtitle}</p>}
            </div>
            <button type="button" onClick={onClose} className="modal-close-icon">
              <X size={16} />
            </button>
          </div>
          {children}
        </div>
      </div>,
      document.body
    );
  };
```

---

##  //DONE 7. Buttons — Hover Lift + Click Press
**Where:** `index.css` — `.btn` class  
**What it does:** Buttons subtly lift on hover and press on click, giving tactile feedback

**Prompt:**
```
In /version2/app/src/index.css, update the .btn base class to include micro-interactions:

  .btn {
    /* existing styles... */
    transition: all 0.15s ease;
    position: relative;
    overflow: hidden;
  }

  .btn:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.12);
  }

  .btn:active {
    transform: translateY(0px) scale(0.98);
    box-shadow: none;
    transition-duration: 0.08s;
  }

  /* Ripple effect on click */
  .btn::after {
    content: '';
    position: absolute;
    top: 50%; left: 50%;
    width: 0; height: 0;
    background: rgba(255, 255, 255, 0.3);
    border-radius: 50%;
    transform: translate(-50%, -50%);
    transition: width 0.4s ease, height 0.4s ease, opacity 0.4s ease;
    opacity: 0;
  }

  .btn:active::after {
    width: 200px;
    height: 200px;
    opacity: 0;
    transition: 0s;
  }

Apply this to .btn-primary, .btn-secondary, .btn-danger and .auth-modal-btn as well.
```

---

## 8. Activity List Rows — Hover Slide
**Where:** `AdminDashboard.jsx` — Recent Booking Activities list  
**What it does:** Each booking row slides slightly right on hover, making the list feel interactive

**Prompt:**
```
In /version2/app/src/pages/admin/AdminDashboard.jsx, update .activity-row:

  .activity-row {
    /* existing styles */
    transition: transform 0.15s ease, background 0.15s ease, border-color 0.15s ease;
    cursor: default;
  }

  .activity-row:hover {
    transform: translateX(3px);
    background: rgba(0, 0, 0, 0.015);
    border-color: var(--border-color-hover);
  }

Also add a transition to the activity icon box on hover:
  .activity-icon-box {
    transition: background 0.15s ease, transform 0.15s ease;
  }
  .activity-row:hover .activity-icon-box {
    transform: scale(1.05);
  }
```

---

## //DONE 9. Progress Bars — Animated Fill on Load
**Where:** `AdminDashboard.jsx` — Room Occupancy progress bars  
**What it does:** Progress bars fill in from 0 to their value when the dashboard loads

**Prompt:**
```
In /version2/app/src/pages/admin/AdminDashboard.jsx, add fill animation to the 
room occupancy progress bars.

Change the inline style from:
  style={{ width: `${availablePercentage}%` }}

To using a CSS animation approach. Add to the component's <style>:

  @keyframes fillBar {
    from { width: 0%; }
    to   { width: var(--fill-width); }
  }

  .progress-bar-fill {
    animation: fillBar 0.8s cubic-bezier(0.22, 1, 0.36, 1) forwards;
    animation-delay: 0.2s;
    width: 0%; /* start at 0, CSS var sets the target */
  }

Then set the CSS variable via inline style:
  style={{ '--fill-width': `${availablePercentage}%` }}

This requires adding the width variable to the keyframe target and ensuring 
the element supports CSS custom properties (all modern browsers do).
```

---

## 10. Toast Notifications — Entrance Polish
**Where:** `App.jsx` — react-hot-toast Toaster config  
**What it does:** Makes success/error toasts slide in from the right smoothly

**Prompt:**
```
In /version2/app/src/App.jsx, update the Toaster toastOptions to add custom 
entrance animation and better styling:

  <Toaster
    position="top-right"
    gutter={8}
    toastOptions={{
      duration: 3500,
      style: {
        background: '#ffffff',
        color: '#111111',
        border: '1px solid #e2e8f0',
        fontSize: '0.875rem',
        borderRadius: '10px',
        padding: '12px 16px',
        boxShadow: '0 8px 24px rgba(15, 23, 42, 0.10)',
        fontFamily: 'Inter, sans-serif',
        maxWidth: '360px',
      },
      success: {
        iconTheme: { primary: '#16a34a', secondary: '#ffffff' },
        style: { borderLeft: '3px solid #16a34a' },
      },
      error: {
        iconTheme: { primary: '#dc2626', secondary: '#ffffff' },
        style: { borderLeft: '3px solid #dc2626' },
        duration: 5000,
      },
    }}
  />
```

---

## 11. Room Cards — Hover Reveal Effect
**Where:** `BrowseRooms.jsx` — room listing cards  
**What it does:** On hover, room card lifts and reveals the "Book Now" button more prominently

**Prompt:**
```
In /version2/app/src/pages/guest/BrowseRooms.jsx, in the room card <style> section, 
add hover effects:

  .room-card {
    transition: transform 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease;
  }

  .room-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 16px 40px rgba(15, 23, 42, 0.12);
    border-color: var(--border-color-hover);
  }

  /* Book button slides up from hidden on card hover */
  .room-book-btn {
    transition: transform 0.2s ease, opacity 0.2s ease, background 0.15s ease;
    opacity: 0.85;
  }

  .room-card:hover .room-book-btn {
    opacity: 1;
    transform: scale(1.02);
  }

  /* Room type badge scale */
  .room-card:hover .room-type-badge {
    transform: scale(1.03);
    transition: transform 0.15s ease;
  }
```

---

## 12. Status Badges — Pulse for "Pending"
**Where:** `StatusBadge.jsx`  
**What it does:** Pending/pending-payment badges pulse softly to draw attention

**Prompt:**
```
In /version2/app/src/components/ui/StatusBadge.jsx, add a gentle pulse animation 
to pending status badges only.

Add to the component's style:

  @keyframes badgePulse {
    0%, 100% { opacity: 1; }
    50%       { opacity: 0.65; }
  }

  .badge-pending, .badge-pending-payment {
    animation: badgePulse 2s ease infinite;
  }

This draws the admin's or staff's eye to items that need action without being 
disruptive. Do NOT apply the pulse to confirmed, paid, cancelled, or checked_in 
status badges — those are resolved states.
```

---

## Summary: Where Each Animation Lives

| Component | Animation | Priority |
|-----------|-----------|----------|
| All pages | Page entrance fade+slide | 🔴 High |
| StatCard grid | Stagger entrance | 🔴 High |
| StatCard value | Count-up number | 🟠 Medium |
| Sidebar nav | Active indicator spring | 🔴 High |
| Sidebar | Collapse text fade | 🟠 Medium |
| Modal | Scale + fade in/out | 🔴 High |
| Buttons | Lift + press + ripple | 🔴 High |
| Room cards | Hover lift + button reveal | 🟠 Medium |
| Activity rows | Hover slide right | 🟡 Low |
| Progress bars | Fill from 0 on load | 🟡 Low |
| Status badges | Pending pulse | 🟡 Low |
| Toasts | Styled entrance | 🟠 Medium |

---

## Do's and Don'ts

**✅ DO:**
- Keep durations under 350ms for interactive feedback
- Use `cubic-bezier(0.34, 1.56, 0.64, 1)` for "spring" feel (sidebar, modal)
- Use `ease` for simple fades
- Use `animation-fill-mode: forwards` with `opacity: 0` start to prevent flash

**❌ DON'T:**
- Animate `width`, `height`, or `top/left` — use `transform` instead (GPU-accelerated)
- Add animation to every element — reserve it for meaningful moments
- Use `transition: all` — always specify the property (e.g. `transition: transform 0.2s ease`)
- Animate things the user didn't trigger (except loaders and badges)
