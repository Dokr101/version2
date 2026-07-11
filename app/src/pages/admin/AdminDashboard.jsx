import { useState, useEffect } from 'react';
import { Link } from 'react-router-dom';
import axios from 'axios';
import toast from 'react-hot-toast';
import { StatCard } from '../../components/ui/StatCard';
import { StatusBadge } from '../../components/ui/StatusBadge';
import { 
  BedDouble, CalendarCheck, Clock, DollarSign, Users, 
  ArrowRight, ShieldAlert
} from 'lucide-react';

export const AdminDashboard = () => {
  const [dashboardData, setDashboardData] = useState(null);
  const [loading, setLoading] = useState(true);

  const fetchDashboardData = async () => {
    try {
      setLoading(true);
      const response = await axios.get('/version2/api/dashboard/admin.php');
      setDashboardData(response.data);
    } catch (error) {
      console.error('Failed to load admin dashboard:', error);
      toast.error('Could not load administrative statistics.');
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchDashboardData();
  }, []);

  if (loading) {
    return (
      <div className="dashboard-loading">
        <div className="spinner"></div>
        <span>Loading administrator overview...</span>
      </div>
    );
  }

  const { stats, roomStats, recentBookings } = dashboardData || {};
  
  const totalRooms = roomStats ? roomStats.available + roomStats.occupied + roomStats.unavailable : 0;
  const availablePercentage = totalRooms > 0 ? Math.round((roomStats.available / totalRooms) * 100) : 0;
  const occupiedPercentage = totalRooms > 0 ? Math.round((roomStats.occupied / totalRooms) * 100) : 0;
  const unavailablePercentage = totalRooms > 0 ? Math.round((roomStats.unavailable / totalRooms) * 100) : 0;

  return (
    <div className="page animate-fade-in">
      <div className="welcome-section">
        <div>
          <h2 className="welcome-title">System Control Panel</h2>
          <p className="welcome-sub">Real-time metrics, room status, and reservation records.</p>
        </div>
        {stats?.pendingStaff > 0 && (
          <Link to="/admin/staff" className="pending-alert-badge">
            <ShieldAlert size={14} />
            <span>{stats.pendingStaff} Staff Pending Approval</span>
          </Link>
        )}
      </div>

      {/* Stats Grid */}
      <div className="stats-row stagger-children">
        <StatCard 
          title="Revenue Generated" 
          value={`Rs. ${stats?.revenue?.toLocaleString('en-IN') || '0'}`} 
          icon={DollarSign} 
          color="success" 
        />
        <StatCard 
          title="Total Bookings" 
          value={stats?.totalBookings || 0} 
          icon={CalendarCheck} 
          color="primary" 
        />
        <StatCard 
          title="Pending Bookings" 
          value={stats?.pendingBookings || 0} 
          icon={Clock} 
          color="warning" 
        />
        <StatCard 
          title="Registered Guests" 
          value={stats?.registeredGuests || 0} 
          icon={Users} 
          color="info" 
        />
      </div>

      {/* Main Grid */}
      <div className="admin-main-grid">
        {/* Room Occupancy */}
        <div className="occupancy-card glass-card">
          <h3>Room Occupancy</h3>
          <p className="card-subtitle">Real-time room occupancy and logistics status</p>
          
          <div className="occupancy-progress-list">
            <div className="progress-item">
              <div className="progress-labels">
                <span>Available Suites</span>
                <strong>{roomStats?.available || 0} ({availablePercentage}%)</strong>
              </div>
              <div className="progress-bar-bg">
                <div className="progress-bar-fill avail-fill" style={{ width: `${availablePercentage}%` }}></div>
              </div>
            </div>

            <div className="progress-item">
              <div className="progress-labels">
                <span>Occupied Suites</span>
                <strong>{roomStats?.occupied || 0} ({occupiedPercentage}%)</strong>
              </div>
              <div className="progress-bar-bg">
                <div className="progress-bar-fill occupied-fill" style={{ width: `${occupiedPercentage}%` }}></div>
              </div>
            </div>

            <div className="progress-item">
              <div className="progress-labels">
                <span>Maintenance / Out of Service</span>
                <strong>{roomStats?.unavailable || 0} ({unavailablePercentage}%)</strong>
              </div>
              <div className="progress-bar-bg">
                <div className="progress-bar-fill unavail-fill" style={{ width: `${unavailablePercentage}%` }}></div>
              </div>
            </div>
          </div>
        </div>

        {/* Recent Bookings List */}
        <div className="recent-activity-card glass-card">
          <div className="card-header-row">
            <h3>Recent Booking Activities</h3>
            <Link to="/admin/bookings" className="view-all-link">
              <span>View All</span>
              <ArrowRight size={14} />
            </Link>
          </div>
          
          {recentBookings && recentBookings.length === 0 ? (
            <p className="no-activity-text">No booking activities registered yet.</p>
          ) : (
            <div className="activity-list stagger-children">
              {recentBookings?.map((b) => (
                <div key={b.booking_id} className="activity-row">
                  <div className="activity-icon-box">
                    <BedDouble size={16} />
                  </div>
                  <div className="activity-details-main">
                    <div className="activity-primary">
                      <strong>{b.user_name}</strong>
                      <span className="activity-id">#{b.booking_id}</span>
                    </div>
                    <p className="activity-desc">
                      Booked {b.room_type} Room for <span className="price-bold">Rs. {b.total_price.toLocaleString('en-IN')}</span>
                    </p>
                    <div className="activity-footer">
                      <span className="activity-date">{new Date(b.created_at).toLocaleDateString('en-US', { dateStyle: 'medium' })}</span>
                      <StatusBadge status={b.status} />
                    </div>
                  </div>
                </div>
              ))}
            </div>
          )}
        </div>
      </div>

      <style>{`
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
          color: var(--text-muted);
          font-size: 0.85rem;
        }

        .pending-alert-badge {
          display: inline-flex;
          align-items: center;
          gap: 6px;
          background: var(--danger-glow);
          color: #f87171;
          border: 1px solid rgba(239, 68, 68, 0.2);
          padding: 6px 14px;
          border-radius: 20px;
          font-size: 0.75rem;
          font-weight: 600;
          text-decoration: none;
          transition: all var(--transition-fast);
        }

        .pending-alert-badge:hover {
          background: rgba(239, 68, 68, 0.15);
          color: var(--danger);
        }

        .stats-row {
          display: grid;
          grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
          gap: 16px;
        }

        .admin-main-grid {
          display: grid;
          grid-template-columns: 1.1fr 2.1fr;
          gap: 24px;
        }

        @media (max-width: 992px) {
          .admin-main-grid {
            grid-template-columns: 1fr;
          }
        }

        .occupancy-card h3, .recent-activity-card h3 {
          font-size: 1rem;
          color: var(--text-primary);
        }

        .card-subtitle {
          font-size: 0.75rem;
          color: var(--text-muted);
          margin-bottom: 20px;
        }

        .occupancy-progress-list {
          display: flex;
          flex-direction: column;
          gap: 16px;
          padding-top: 8px;
        }

        .progress-item {
          display: flex;
          flex-direction: column;
          gap: 6px;
        }

        .progress-labels {
          display: flex;
          justify-content: space-between;
          font-size: 0.8rem;
          color: var(--text-secondary);
        }

        .progress-bar-bg {
          height: 6px;
          background: rgba(255, 255, 255, 0.04);
          border-radius: 3px;
          overflow: hidden;
        }

        .progress-bar-fill {
          height: 100%;
          border-radius: 3px;
        }

        .avail-fill { background: var(--success); }
        .occupied-fill { background: var(--primary-light); }
        .unavail-fill { background: var(--danger); }

        .card-header-row {
          display: flex;
          justify-content: space-between;
          align-items: center;
          margin-bottom: 20px;
        }

        .view-all-link {
          display: flex;
          align-items: center;
          gap: 4px;
          color: var(--text-muted);
          text-decoration: none;
          font-size: 0.78rem;
          font-weight: 500;
          transition: color var(--transition-fast);
        }

        .view-all-link:hover {
          color: var(--primary-light);
        }

        .no-activity-text {
          color: var(--text-muted);
          padding: 40px 0;
          text-align: center;
          font-size: 0.85rem;
        }

        .activity-list {
          display: flex;
          flex-direction: column;
          gap: 10px;
        }

        .activity-row {
          display: flex;
          gap: 14px;
          background: rgba(255, 255, 255, 0.015);
          border: 1px solid var(--border-color);
          padding: 12px 16px;
          border-radius: var(--border-radius-md);
        }

        .activity-icon-box {
          width: 38px;
          height: 38px;
          background: var(--primary-glow);
          color: var(--primary-light);
          border-radius: var(--border-radius-sm);
          display: flex;
          align-items: center;
          justify-content: center;
          flex-shrink: 0;
        }

        .activity-details-main {
          display: flex;
          flex-direction: column;
          gap: 3px;
          flex-grow: 1;
        }

        .activity-primary {
          display: flex;
          justify-content: space-between;
          font-size: 0.85rem;
          color: var(--text-primary);
        }

        .activity-id {
          font-size: 0.75rem;
          color: var(--text-muted);
        }

        .activity-desc {
          font-size: 0.8rem;
          color: var(--text-secondary);
        }

        .price-bold {
          font-weight: 600;
          color: var(--success);
        }

        .activity-footer {
          display: flex;
          justify-content: space-between;
          align-items: center;
          margin-top: 6px;
          font-size: 0.75rem;
        }

        .activity-date {
          color: var(--text-muted);
        }
      `}</style>
    </div>
  );
};
export default AdminDashboard;
