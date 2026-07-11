import { useState, useEffect } from 'react';
import { Link } from 'react-router-dom';
import axios from 'axios';
import toast from 'react-hot-toast';
import { StatCard } from '../../components/ui/StatCard';
import { StatusBadge } from '../../components/ui/StatusBadge';
import { useAuth } from '../../hooks/useAuth';
import { 
  Calendar, DollarSign, CheckCircle2, Clock, 
  ArrowRight, Bed, CreditCard
} from 'lucide-react';

export const GuestDashboard = () => {
  const { user } = useAuth();
  const [dashboardData, setDashboardData] = useState(null);
  const [loading, setLoading] = useState(true);

  const fetchDashboardData = async () => {
    try {
      setLoading(true);
      const response = await axios.get('/version2/api/dashboard/guest.php');
      setDashboardData(response.data);
    } catch (error) {
      console.error('Failed to fetch guest dashboard:', error);
      toast.error('Could not load dashboard data.');
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => { fetchDashboardData(); }, []);

  const handleCancelBooking = async (bookingId) => {
    if (!window.confirm('Cancel this booking?')) return;
    try {
      const response = await axios.post('/version2/api/bookings/cancel.php', { booking_id: bookingId });
      if (response.data.success) {
        toast.success(response.data.message || 'Booking cancelled.');
        fetchDashboardData();
      }
    } catch (error) {
      toast.error(error.response?.data?.error || 'Failed to cancel booking.');
    }
  };

  if (loading) {
    return (
      <div className="dashboard-loading">
        <div className="spinner"></div>
        <span>Loading dashboard...</span>
      </div>
    );
  }

  const { stats, recentBookings } = dashboardData || {};
  const firstName = user?.name?.split(' ')[0] || 'Guest';

  return (
    <div className="page animate-fade-in">
      {/* Welcome */}
      <div className="welcome-section">
        <div>
          <h2 className="welcome-title">Welcome back, {firstName}</h2>
          <p className="welcome-sub">Here's a summary of your hotel activity.</p>
        </div>
        <Link to="/guest/rooms" className="btn btn-primary">
          <Bed size={16} />
          <span>Book a Room</span>
        </Link>
      </div>

      {/* Stats */}
      <div className="stats-row stagger-children">
        <StatCard title="Total Bookings" value={stats?.totalBookings || 0} icon={Calendar} color="primary" />
        <StatCard title="Confirmed" value={stats?.confirmedBookings || 0} icon={CheckCircle2} color="success" />
        <StatCard title="Pending" value={stats?.pendingBookings || 0} icon={Clock} color="warning" />
        <StatCard title="Total Spent" value={`Rs. ${stats?.totalSpent?.toLocaleString('en-IN') || '0'}`} icon={DollarSign} color="info" />
      </div>

      {/* Recent Bookings */}
      <div className="section">
        <div className="section-head">
          <h3>Recent Bookings</h3>
          {recentBookings?.length > 0 && (
            <Link to="/guest/bookings" className="link-subtle">
              View all <ArrowRight size={14} />
            </Link>
          )}
        </div>

        {!recentBookings || recentBookings.length === 0 ? (
          <div className="empty-state glass-card">
            <Bed size={28} />
            <p>No bookings yet. Start by exploring our rooms!</p>
            <Link to="/guest/rooms" className="btn btn-outline" style={{ marginTop: 8 }}>Browse Rooms</Link>
          </div>
        ) : (
          <div className="booking-list stagger-children">
            {recentBookings.map((b) => (
              <div key={b.booking_id} className="booking-row glass-card">
                <div className="booking-row-main">
                  <div className="booking-row-id">#{b.booking_id}</div>
                  <div className="booking-row-info">
                    <span className="booking-room-type">{b.room_type} Room</span>
                    <span className="booking-dates">
                      {new Date(b.checkin).toLocaleDateString('en-US', { month: 'short', day: 'numeric' })}
                      {' → '}
                      {new Date(b.checkout).toLocaleDateString('en-US', { month: 'short', day: 'numeric' })}
                    </span>
                  </div>
                  <div className="booking-row-price">
                    Rs. {b.total_price.toLocaleString('en-IN')}
                  </div>
                  <StatusBadge status={b.status} />
                </div>
                <div className="booking-row-actions">
                  {b.status === 'pending' && (
                    <>
                      <a 
                        href={`/version2/guest/initiate_khalti_payment.php?booking_id=${b.booking_id}`}
                        className="btn btn-success"
                        style={{ padding: '6px 12px', fontSize: '0.78rem' }}
                      >
                        <CreditCard size={13} /> Pay Now
                      </a>
                      <button 
                        onClick={() => handleCancelBooking(b.booking_id)}
                        className="btn btn-danger"
                        style={{ padding: '6px 12px', fontSize: '0.78rem' }}
                      >
                        Cancel
                      </button>
                    </>
                  )}
                  {b.status === 'confirmed' && (
                    <button 
                      onClick={() => handleCancelBooking(b.booking_id)}
                      className="btn btn-danger"
                      style={{ padding: '6px 12px', fontSize: '0.78rem' }}
                    >
                      Cancel
                    </button>
                  )}
                </div>
              </div>
            ))}
          </div>
        )}
      </div>

      <style>{`
        .page {
          display: flex;
          flex-direction: column;
          gap: 28px;
        }

        .welcome-section {
          display: flex;
          justify-content: space-between;
          align-items: center;
        }

        .welcome-title {
          font-size: 1.5rem;
          margin-bottom: 4px;
        }

        .welcome-sub {
          color: var(--text-primary);
          font-size: 0.9rem;
          font-weight: 600;
        }

        .stats-row {
          display: grid;
          grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
          gap: 16px;
        }

        .section {
          display: flex;
          flex-direction: column;
          gap: 14px;
        }

        .section-head {
          display: flex;
          justify-content: space-between;
          align-items: center;
        }

        .section-head h3 {
          font-size: 1.05rem;
        }

        .link-subtle {
          display: flex;
          align-items: center;
          gap: 4px;
          color: var(--text-primary);
          text-decoration: none;
          font-size: 0.8rem;
          font-weight: 700;
          transition: color var(--transition-fast);
        }

        .link-subtle:hover {
          color: var(--primary-light);
        }

        .empty-state {
          display: flex;
          flex-direction: column;
          align-items: center;
          padding: 48px 20px !important;
          text-align: center;
          color: var(--text-primary);
          gap: 8px;
        }

        .booking-list {
          display: flex;
          flex-direction: column;
          gap: 8px;
        }

        .booking-row {
          display: flex;
          justify-content: space-between;
          align-items: center;
          padding: 14px 18px !important;
          gap: 16px;
        }

        .booking-row:hover {
          transform: none;
        }

        .booking-row-main {
          display: flex;
          align-items: center;
          gap: 20px;
          flex-grow: 1;
        }

        .booking-row-id {
          font-weight: 700;
          font-size: 0.82rem;
          color: var(--text-primary);
          min-width: 40px;
        }

        .booking-row-info {
          display: flex;
          flex-direction: column;
          gap: 2px;
        }

        .booking-room-type {
          font-weight: 600;
          font-size: 0.85rem;
          color: var(--text-primary);
        }

        .booking-dates {
          font-size: 0.76rem;
          color: var(--text-primary);
          font-weight: 600;
        }

        .booking-row-price {
          font-weight: 700;
          font-size: 0.9rem;
          color: var(--text-primary);
          min-width: 100px;
          text-align: right;
        }

        .booking-row-actions {
          display: flex;
          gap: 8px;
          flex-shrink: 0;
        }

        @media (max-width: 768px) {
          .welcome-section {
            flex-direction: column;
            align-items: flex-start;
            gap: 12px;
          }
          .booking-row {
            flex-direction: column;
            align-items: flex-start;
          }
          .booking-row-main {
            flex-wrap: wrap;
          }
        }
      `}</style>
    </div>
  );
};
export default GuestDashboard;
