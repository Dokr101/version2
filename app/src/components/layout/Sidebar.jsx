import { useState } from 'react';
import { NavLink, useLocation } from 'react-router-dom';
import { useAuth } from '../../hooks/useAuth';
import { useSidebar } from '../../contexts/SidebarContext';
import { 
  LayoutDashboard, Bed, CalendarCheck, User, LogOut, Users, 
  CreditCard, BarChart3, CheckCircle, Hotel, ChevronLeft,
  Menu
} from 'lucide-react';

export const Sidebar = () => {
  const { user, logout } = useAuth();
  const { collapsed, setCollapsed } = useSidebar();
  const [pulsingLink, setPulsingLink] = useState(null);
  const location = useLocation();

  if (!user) return null;

  const role = user.role;

  const guestMenu = [
    { name: 'Dashboard', path: '/guest/dashboard', icon: LayoutDashboard },
    { name: 'Book Rooms', path: '/guest/rooms', icon: Bed },
    { name: 'My Bookings', path: '/guest/bookings', icon: CalendarCheck },
    { name: 'Profile', path: '/guest/profile', icon: User },
  ];

  const adminMenu = [
    { name: 'Dashboard', path: '/admin/dashboard', icon: LayoutDashboard },
    { name: 'Staff', path: '/admin/staff', icon: Users },
    { name: 'Rooms', path: '/admin/rooms', icon: Bed },
    { name: 'Bookings', path: '/admin/bookings', icon: CalendarCheck },
    { name: 'Payments', path: '/admin/payments', icon: CreditCard },
    { name: 'Reports', path: '/admin/reports', icon: BarChart3 },
  ];

  const staffMenu = [
    { name: 'Dashboard', path: '/staff/dashboard', icon: LayoutDashboard },
    { name: 'Rooms', path: '/staff/rooms', icon: Bed },
    { name: 'Check-In', path: '/staff/checkin', icon: CheckCircle },
    { name: 'Check-Out', path: '/staff/checkout', icon: LogOut },
    { name: 'Reservations', path: '/staff/reservations', icon: CalendarCheck },
  ];

  const menuItems = role === 'admin' ? adminMenu : role === 'staff' ? staffMenu : guestMenu;

  const roleLabel = role === 'admin' ? 'Administration' : role === 'staff' ? 'Staff Portal' : 'Guest Portal';

  return (
    <aside className={`sidebar ${collapsed ? 'sidebar--collapsed' : ''}`}>
      {/* Top: Brand */}
      <div className="sidebar-brand">
        <div className="brand-icon">
          <Hotel size={18} />
        </div>
        <div className="brand-text brand-text-wrap">
          <span className="brand-name">HRMS</span>
          <span className="brand-role">{roleLabel}</span>
        </div>
        <button 
          className="collapse-toggle" 
          onClick={() => setCollapsed(!collapsed)}
          aria-label={collapsed ? 'Expand sidebar' : 'Collapse sidebar'}
        >
          <ChevronLeft size={16} className="toggle-icon" />
        </button>
      </div>

      {/* Navigation */}
      <nav className="sidebar-nav">
        <div className="nav-section-wrapper">
          <span className="nav-section-label">Navigation</span>
        </div>
        {menuItems.map((item) => {
          const Icon = item.icon;
          const isActive = location.pathname === item.path;
          const isPulsing = pulsingLink === item.path;
          return (
            <NavLink
              key={item.name}
              to={item.path}
              className={`nav-link ${isActive ? 'nav-link--active' : ''} ${isPulsing ? 'nav-link--pulse' : ''}`}
              title={collapsed ? item.name : undefined}
              onClick={() => {
                setPulsingLink(item.path);
                setTimeout(() => setPulsingLink(null), 300);
              }}
            >
              <span className="nav-link-indicator"></span>
              <Icon size={18} className="nav-link-icon" />
              <span className="nav-link-label">{item.name}</span>
            </NavLink>
          );
        })}
      </nav>

      {/* Footer */}
      <div className="sidebar-footer">
        <div className={`user-pill ${collapsed ? 'user-pill--collapsed' : ''}`}>
          <div className="user-avatar">
            {user.name.charAt(0).toUpperCase()}
          </div>
          <div className="user-meta user-meta-wrap">
            <span className="user-name">{user.name}</span>
            <span className="user-role">{role}</span>
          </div>
        </div>
        <button onClick={logout} className="logout-btn" title="Sign out">
          <LogOut size={16} />
          <span className="logout-label">Sign Out</span>
        </button>
      </div>

      <style>{`
        .sidebar {
          width: 240px;
          min-width: 240px;
          height: 100vh;
          position: fixed;
          top: 0;
          left: 0;
          background: var(--bg-sidebar);
          border-right: 1px solid var(--border-color);
          display: flex;
          flex-direction: column;
          z-index: 100;
          padding: 20px 12px;
          transition: width 0.28s cubic-bezier(0.4, 0, 0.2, 1),
                      min-width 0.28s cubic-bezier(0.4, 0, 0.2, 1);
          overflow: hidden;
        }

        .sidebar--collapsed {
          width: 68px;
          min-width: 68px;
        }

        /* Brand */
        .sidebar-brand {
          display: flex;
          align-items: center;
          gap: 10px;
          padding: 0 8px;
          margin-bottom: 28px;
          position: relative;
          transition: gap 0.28s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .sidebar--collapsed .sidebar-brand {
          gap: 0;
        }

        .brand-icon {
          width: 34px;
          height: 34px;
          min-width: 34px;
          background: var(--primary);
          border-radius: var(--border-radius-sm);
          display: flex;
          align-items: center;
          justify-content: center;
          color: white;
        }

        .brand-text {
          display: flex;
          flex-direction: column;
          overflow: hidden;
        }

        .brand-text-wrap {
          transition: opacity 0.16s ease, transform 0.16s ease;
        }

        .brand-name {
          font-family: var(--font-display);
          font-size: 1.1rem;
          font-weight: 800;
          color: var(--text-primary);
          letter-spacing: 0.5px;
          line-height: 1.2;
        }

        .brand-role {
          font-size: 0.65rem;
          color: var(--text-muted);
          font-weight: 500;
          text-transform: uppercase;
          letter-spacing: 0.06em;
        }

        .collapse-toggle {
          position: absolute;
          right: 0;
          width: 26px;
          height: 26px;
          border: 1px solid var(--border-color);
          border-radius: 6px;
          background: transparent;
          color: var(--text-muted);
          cursor: pointer;
          display: flex;
          align-items: center;
          justify-content: center;
          transition: all var(--transition-fast);
          opacity: 1;
        }

        .collapse-toggle:hover {
          color: var(--text-primary);
          background: rgba(0,0,0,0.03);
        }

        .toggle-icon {
          transition: transform 0.28s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .sidebar--collapsed .toggle-icon {
          transform: rotate(180deg);
        }

        /* Navigation */
        .sidebar-nav {
          display: flex;
          flex-direction: column;
          gap: 2px;
          flex-grow: 1;
        }

        .nav-section-wrapper {
          max-height: 30px;
          opacity: 1;
          overflow: hidden;
          transition: max-height 0.28s cubic-bezier(0.4, 0, 0.2, 1), opacity 0.28s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .sidebar--collapsed .nav-section-wrapper {
          max-height: 0;
          opacity: 0;
          pointer-events: none;
        }

        .nav-section-label {
          font-size: 0.6rem;
          font-weight: 700;
          color: var(--text-muted);
          text-transform: uppercase;
          letter-spacing: 0.1em;
          padding: 0 12px;
          margin-bottom: 8px;
          display: block;
        }

        .nav-link {
          display: flex;
          align-items: center;
          gap: 10px;
          color: var(--text-secondary);
          text-decoration: none;
          padding: 10px 12px;
          border-radius: var(--border-radius-sm);
          font-size: 0.85rem;
          font-weight: 500;
          transition: background-color 0.2s ease, color 0.2s ease, gap 0.28s cubic-bezier(0.4, 0, 0.2, 1);
          position: relative;
          overflow: hidden;
        }

        .sidebar--collapsed .nav-link {
          gap: 0;
        }

        /* Ripple/pulse animation on click */
        .nav-link--pulse::after {
          content: '';
          position: absolute;
          top: 0;
          left: 0;
          right: 0;
          bottom: 0;
          background: rgba(34, 34, 34, 0.15);
          border-radius: inherit;
          pointer-events: none;
          animation: navRipplePulse 0.3s ease-out;
        }

        @keyframes navRipplePulse {
          0% {
            opacity: 1;
            transform: scale(0.9);
          }
          100% {
            opacity: 0;
            transform: scale(1.05);
          }
        }

        .nav-link-indicator {
          position: absolute;
          left: 0;
          top: 50%;
          transform: translateY(-50%) scaleY(0);
          width: 3px;
          height: 20px;
          background: var(--primary);
          border-radius: 0 3px 3px 0;
          transition: transform 0.25s cubic-bezier(0.34, 1.56, 0.64, 1); /* spring */
        }

        .nav-link:hover {
          color: var(--text-primary);
          background: rgba(255, 255, 255, 0.03);
        }

        .nav-link--active {
          color: var(--text-primary);
          background: var(--bg-card-hover);
        }

        .nav-link--active .nav-link-indicator {
          transform: translateY(-50%) scaleY(1);
        }

        .nav-link--active .nav-link-icon { 
          color: var(--primary);
          animation: iconBounce 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
        }

        @keyframes iconBounce {
          0%   { transform: scale(1); }
          50%  { transform: scale(1.2); }
          100% { transform: scale(1); }
        }

        .nav-link:hover .nav-link-icon {
          transform: translateX(2px);
          transition: transform 0.15s ease;
        }

        .nav-link-icon {
          color: var(--text-muted);
          transition: color var(--transition-fast), transform var(--transition-fast);
          flex-shrink: 0;
        }

        .nav-link-label {
          white-space: nowrap;
          overflow: hidden;
          /* collapse: fade out fast; expand: wait 0.1s for width to grow first */
          transition: opacity 0.1s ease, transform 0.1s ease;
        }

        /* Re-entering labels wait for the sidebar to finish widening */
        :not(.sidebar--collapsed) .nav-link-label {
          transition: opacity 0.15s ease 0.1s, transform 0.15s ease 0.1s;
        }

        /* Footer */
        .sidebar-footer {
          border-top: 1px solid var(--border-color);
          padding-top: 16px;
          display: flex;
          flex-direction: column;
          gap: 10px;
        }

        .user-pill {
          display: flex;
          align-items: center;
          gap: 10px;
          padding: 6px 8px;
          border-radius: var(--border-radius-sm);
          transition: gap 0.28s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .sidebar--collapsed .user-pill {
          gap: 0;
          justify-content: center;
        }

        .user-avatar {
          width: 32px;
          height: 32px;
          min-width: 32px;
          background: linear-gradient(135deg, var(--primary), var(--primary-light));
          border-radius: 8px;
          display: flex;
          align-items: center;
          justify-content: center;
          color: white;
          font-weight: 700;
          font-size: 0.85rem;
        }

        .user-meta {
          display: flex;
          flex-direction: column;
          overflow: hidden;
        }

        .user-meta-wrap {
          transition: opacity 0.16s ease, transform 0.16s ease;
        }

        .user-name {
          font-weight: 600;
          font-size: 0.8rem;
          color: var(--text-primary);
          white-space: nowrap;
          overflow: hidden;
          text-overflow: ellipsis;
        }

        .user-role {
          font-size: 0.65rem;
          color: var(--text-muted);
          text-transform: capitalize;
        }

        .logout-btn {
          display: flex;
          align-items: center;
          justify-content: center;
          gap: 8px;
          background: transparent;
          border: 1px solid rgba(239, 68, 68, 0.1);
          color: var(--text-muted);
          padding: 9px;
          border-radius: var(--border-radius-sm);
          cursor: pointer;
          font-size: 0.8rem;
          font-weight: 500;
          transition: all var(--transition-fast), gap 0.28s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .sidebar--collapsed .logout-btn {
          gap: 0;
        }

        .logout-btn:hover {
          color: #f87171;
          background: rgba(239, 68, 68, 0.06);
          border-color: rgba(239, 68, 68, 0.2);
        }

        .logout-label {
          transition: opacity 0.16s ease, transform 0.16s ease;
        }

        /* Collapsed Sidebar Transitions */
        .sidebar--collapsed .brand-text-wrap {
          opacity: 0;
          transform: translateX(-4px);
          width: 0;
          pointer-events: none;
          transition: opacity 0.16s ease, transform 0.16s ease;
        }

        .sidebar--collapsed .nav-link-label {
          opacity: 0;
          transform: translateX(-4px);
          width: 0;
          pointer-events: none;
          transition: opacity 0.16s ease, transform 0.16s ease;
        }

        .sidebar--collapsed .user-meta-wrap {
          opacity: 0;
          transform: translateX(-4px);
          width: 0;
          pointer-events: none;
          transition: opacity 0.16s ease, transform 0.16s ease;
        }

        .sidebar--collapsed .logout-label {
          opacity: 0;
          transform: translateX(-4px);
          width: 0;
          pointer-events: none;
          transition: opacity 0.16s ease, transform 0.16s ease;
        }

        @media (max-width: 768px) {
          .sidebar {
            width: 68px;
            min-width: 68px;
          }
          
          .brand-text-wrap {
            opacity: 0;
            transform: translateX(-4px);
            width: 0;
            pointer-events: none;
          }

          .nav-link-label {
            opacity: 0;
            transform: translateX(-4px);
            width: 0;
            pointer-events: none;
          }

          .user-meta-wrap {
            opacity: 0;
            transform: translateX(-4px);
            width: 0;
            pointer-events: none;
          }

          .logout-label {
            opacity: 0;
            transform: translateX(-4px);
            width: 0;
            pointer-events: none;
          }

          .nav-section-wrapper {
            max-height: 0;
            opacity: 0;
            pointer-events: none;
          }

          .toggle-icon {
            transform: rotate(180deg);
          }

          .sidebar-brand {
            gap: 0;
          }

          .user-pill {
            gap: 0;
            justify-content: center;
          }

          .logout-btn {
            gap: 0;
          }
        }
      `}</style>
    </aside>
  );
};
