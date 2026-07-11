import { useLocation } from 'react-router-dom';
import { useAuth } from '../../hooks/useAuth';
import { Search, Bell } from 'lucide-react';

export const TopBar = () => {
  const { user } = useAuth();
  const location = useLocation();

  if (!user) return null;

  const getPageTitle = () => {
    const path = location.pathname;
    if (path.includes('/dashboard')) return 'Dashboard';
    if (path.includes('/rooms')) return 'Rooms';
    if (path.includes('/bookings') || path.includes('/reservations')) return 'Bookings';
    if (path.includes('/staff')) return 'Staff';
    if (path.includes('/payments')) return 'Payments';
    if (path.includes('/reports')) return 'Reports';
    if (path.includes('/profile')) return 'Profile';
    if (path.includes('/checkin')) return 'Check-In';
    if (path.includes('/checkout')) return 'Check-Out';
    if (path.includes('/payment-success')) return 'Payment Confirmed';
    if (path.includes('/payment-error')) return 'Payment Failed';
    return 'Overview';
  };

  const today = new Date().toLocaleDateString('en-US', {
    weekday: 'long',
    month: 'short',
    day: 'numeric'
  });

  return (
    <header className="topbar">
      <div className="topbar-left">
        <h1 className="topbar-title">{getPageTitle()}</h1>
        <span className="topbar-date">{today}</span>
      </div>

      <div className="topbar-right">
        <div className="topbar-greeting">
          Hi, <strong>{user.name.split(' ')[0]}</strong>
        </div>
      </div>

      <style>{`
        .topbar {
          height: 60px;
          display: flex;
          align-items: center;
          justify-content: space-between;
          padding: 0 32px;
          border-bottom: 1px solid var(--border-color);
          background: var(--bg-card);
          position: sticky;
          top: 0;
          z-index: 90;
        }

        .topbar-left {
          display: flex;
          align-items: baseline;
          gap: 16px;
        }

        .topbar-title {
          font-size: 1.15rem;
          font-weight: 700;
          color: var(--text-primary);
          font-family: var(--font-display);
        }

        .topbar-date {
          font-size: 0.75rem;
          color: var(--text-muted);
          font-weight: 500;
        }

        .topbar-right {
          display: flex;
          align-items: center;
          gap: 12px;
        }

        .topbar-greeting {
          font-size: 0.8rem;
          color: var(--text-secondary);
        }

        .topbar-greeting strong {
          color: var(--text-primary);
        }

        @media (max-width: 768px) {
          .topbar {
            padding: 0 16px;
          }
          .topbar-date {
            display: none;
          }
        }
      `}</style>
    </header>
  );
};
