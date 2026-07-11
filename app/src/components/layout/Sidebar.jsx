import { useState } from 'react';
import { NavLink, useLocation } from 'react-router-dom';
import { useAuth } from '../../hooks/useAuth';
import { 
  LayoutDashboard, Bed, CalendarCheck, User, LogOut, Users, 
  CreditCard, BarChart3, CheckCircle, Hotel, ChevronLeft,
  Menu
} from 'lucide-react';

export const Sidebar = () => {
  const { user, logout } = useAuth();
  const [collapsed, setCollapsed] = useState(false);
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
        {!collapsed && (
          <div className="brand-text">
            <span className="brand-name">HRMS</span>
            <span className="brand-role">{roleLabel}</span>
          </div>
        )}
        <button 
          className="collapse-toggle" 
          onClick={() => setCollapsed(!collapsed)}
          aria-label={collapsed ? 'Expand sidebar' : 'Collapse sidebar'}
        >
          {collapsed ? <Menu size={16} /> : <ChevronLeft size={16} />}
        </button>
      </div>

      {/* Navigation */}
      <nav className="sidebar-nav">
        {!collapsed && (
          <span className="nav-section-label">Navigation</span>
        )}
        {menuItems.map((item) => {
          const Icon = item.icon;
          const isActive = location.pathname === item.path;
          return (
            <NavLink
              key={item.name}
              to={item.path}
              className={`nav-link ${isActive ? 'nav-link--active' : ''}`}
              title={collapsed ? item.name : undefined}
            >
              <span className="nav-link-indicator"></span>
              <Icon size={18} className="nav-link-icon" />
              {!collapsed && <span className="nav-link-label">{item.name}</span>}
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
          {!collapsed && (
            <div className="user-meta">
              <span className="user-name">{user.name}</span>
              <span className="user-role">{role}</span>
            </div>
          )}
        </div>
        <button onClick={logout} className="logout-btn" title="Sign out">
          <LogOut size={16} />
          {!collapsed && <span>Sign Out</span>}
        </button>
      </div>

      <style>{`
        .sidebar {
          width: 240px;
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
          transition: width var(--transition-normal);
          overflow: hidden;
        }

        .sidebar--collapsed {
          width: 68px;
        }

        /* Brand */
        .sidebar-brand {
          display: flex;
          align-items: center;
          gap: 10px;
          padding: 0 8px;
          margin-bottom: 28px;
          position: relative;
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
          opacity: 0;
        }

        .sidebar:hover .collapse-toggle {
          opacity: 1;
        }

        .collapse-toggle:hover {
          color: var(--text-primary);
          background: rgba(0,0,0,0.03);
        }

        /* Navigation */
        .sidebar-nav {
          display: flex;
          flex-direction: column;
          gap: 2px;
          flex-grow: 1;
        }

        .nav-section-label {
          font-size: 0.6rem;
          font-weight: 700;
          color: var(--text-muted);
          text-transform: uppercase;
          letter-spacing: 0.1em;
          padding: 0 12px;
          margin-bottom: 8px;
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
          transition: all var(--transition-fast);
          position: relative;
        }

        .nav-link-indicator {
          position: absolute;
          left: 0;
          top: 50%;
          transform: translateY(-50%) scaleY(0);
          width: 3px;
          height: 20px;
          background: var(--primary-light);
          border-radius: 0 3px 3px 0;
          transition: transform var(--transition-spring);
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
          color: var(--primary-dark);
        }

        .nav-link-icon {
          color: var(--text-muted);
          transition: color var(--transition-fast);
          flex-shrink: 0;
        }

        .nav-link-label {
          white-space: nowrap;
          overflow: hidden;
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
        }

        .user-pill--collapsed {
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
          transition: all var(--transition-fast);
        }

        .logout-btn:hover {
          color: #f87171;
          background: rgba(239, 68, 68, 0.06);
          border-color: rgba(239, 68, 68, 0.2);
        }

        @media (max-width: 768px) {
          .sidebar {
            width: 68px;
          }
          .brand-text, .nav-section-label, .nav-link-label, .user-meta, .logout-btn span {
            display: none;
          }
        }
      `}</style>
    </aside>
  );
};
