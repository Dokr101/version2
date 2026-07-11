import { useState, useEffect } from 'react';
import axios from 'axios';
import toast from 'react-hot-toast';
import { StatCard } from '../../components/ui/StatCard';
import { 
  Calendar, DollarSign, TrendingUp, Percent, Layers
} from 'lucide-react';

export const Reports = () => {
  const [startDate, setStartDate] = useState(
    new Date(Date.now() - 30 * 24 * 60 * 60 * 1000).toISOString().split('T')[0]
  );
  const [endDate, setEndDate] = useState(
    new Date().toISOString().split('T')[0]
  );
  const [reportData, setReportData] = useState(null);
  const [loading, setLoading] = useState(true);

  const fetchReports = async () => {
    try {
      setLoading(true);
      const response = await axios.get(
        `/version2/api/reports/summary.php?start_date=${startDate}&end_date=${endDate}`
      );
      setReportData(response.data);
    } catch (error) {
      console.error('Failed to load reports summary:', error);
      toast.error('Could not load reports.');
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchReports();
  }, []);

  const handleFilterSubmit = (e) => {
    e.preventDefault();
    if (new Date(startDate) > new Date(endDate)) {
      toast.error('Start date must be before end date.');
      return;
    }
    fetchReports();
  };

  if (loading) {
    return (
      <div className="dashboard-loading">
        <div className="spinner"></div>
        <span>Generating analytics summary...</span>
      </div>
    );
  }

  const { stats, monthlyRevenue = [] } = reportData || {};

  const maxRevenue = monthlyRevenue.length > 0 
    ? Math.max(...monthlyRevenue.map(m => m.revenue), 1000) 
    : 1000;

  const getMonthName = (monthStr) => {
    const [year, month] = monthStr.split('-');
    const date = new Date(year, parseInt(month) - 1, 1);
    return date.toLocaleDateString('en-US', { month: 'short' });
  };

  return (
    <div className="page animate-fade-in">
      {/* Date Filter Bar */}
      <div className="filter-card glass-card">
        <form onSubmit={handleFilterSubmit} className="reports-filter-form">
          <div className="filter-form-title">
            <Calendar size={15} className="icon-purple" />
            <span>Select Date Range</span>
          </div>

          <div className="filter-inputs">
            <div className="form-group-inline">
              <label>From:</label>
              <input 
                type="date" 
                value={startDate} 
                onChange={(e) => setStartDate(e.target.value)}
                className="form-input filter-date-input"
                required
              />
            </div>
            <div className="form-group-inline">
              <label>To:</label>
              <input 
                type="date" 
                value={endDate} 
                onChange={(e) => setEndDate(e.target.value)}
                className="form-input filter-date-input"
                required
              />
            </div>
            <button type="submit" className="btn btn-primary">
              Generate
            </button>
          </div>
        </form>
      </div>

      {/* Summary Metrics Grid */}
      <div className="stats-row stagger-children">
        <StatCard 
          title="Total Revenue" 
          value={`Rs. ${stats?.totalRevenue?.toLocaleString('en-IN') || '0'}`} 
          icon={DollarSign} 
          color="success" 
          subtitle="Realized from reservations"
        />
        <StatCard 
          title="Bookings Logged" 
          value={stats?.totalBookings || 0} 
          icon={TrendingUp} 
          color="primary" 
          subtitle="Confirmed and pending"
        />
        <StatCard 
          title="Average Booking Value" 
          value={`Rs. ${Math.round(stats?.averageBookingValue || 0).toLocaleString('en-IN')}`} 
          icon={Percent} 
          color="info" 
          subtitle="Per guest stay"
        />
        <StatCard 
          title="Room Types Booked" 
          value={stats?.roomTypesBooked || 0} 
          icon={Layers} 
          color="warning" 
          subtitle="Unique categories selected"
        />
      </div>

      {/* Monthly Revenue Chart Card */}
      <div className="chart-card glass-card">
        <div className="chart-header">
          <div>
            <h3>Monthly Revenue</h3>
            <p className="card-subtitle">Showing performance metrics for current year</p>
          </div>
          <div className="chart-legend">
            <span className="legend-dot legend-revenue"></span>
            <span>Total Revenue (Rs.)</span>
          </div>
        </div>

        {monthlyRevenue.length === 0 ? (
          <div className="empty-chart-state">
            <TrendingUp size={28} />
            <p>No monthly booking revenues logged for current year.</p>
          </div>
        ) : (
          <div className="custom-bar-chart-container">
            <div className="bar-chart-viewport">
              {monthlyRevenue.map((item) => {
                const heightPercentage = Math.round((item.revenue / maxRevenue) * 75) + 5;
                return (
                  <div key={item.month} className="chart-bar-column">
                    <div className="bar-wrapper">
                      <div className="bar-tooltip">
                        <strong style={{ color: 'var(--text-primary)' }}>{getMonthName(item.month)}</strong>
                        <span>Revenue: Rs. {item.revenue.toLocaleString('en-IN')}</span>
                        <span>Bookings: {item.booking_count}</span>
                      </div>
                      <div 
                        className="bar-filled-pill" 
                        style={{ height: `${heightPercentage}%` }}
                      >
                        <div className="bar-glow-effect"></div>
                      </div>
                    </div>
                    <span className="bar-label-month">{getMonthName(item.month)}</span>
                  </div>
                );
              })}
            </div>
            
            <div className="chart-y-axis-helpers">
              <div className="helper-line"><span className="helper-label">Rs. {Math.round(maxRevenue).toLocaleString()}</span></div>
              <div className="helper-line"><span className="helper-label">Rs. {Math.round(maxRevenue / 2).toLocaleString()}</span></div>
              <div className="helper-line"><span className="helper-label">Rs. 0</span></div>
            </div>
          </div>
        )}
      </div>

      <style>{`
        .icon-purple {
          color: var(--primary-dark);
        }

        .filter-card {
          padding: 12px 18px !important;
        }

        .reports-filter-form {
          display: flex;
          justify-content: space-between;
          align-items: center;
          gap: 16px;
          flex-wrap: wrap;
        }

        .filter-form-title {
          display: flex;
          align-items: center;
          gap: 8px;
          font-weight: 600;
          color: var(--text-primary);
          font-size: 0.85rem;
        }

        .filter-inputs {
          display: flex;
          align-items: center;
          gap: 12px;
          flex-wrap: wrap;
        }

        .form-group-inline {
          display: flex;
          align-items: center;
          gap: 6px;
          font-size: 0.8rem;
          color: var(--text-secondary);
        }

        .filter-date-input {
          padding: 6px 10px;
          font-size: 0.8rem;
          width: 130px;
        }

        .chart-card {
          display: flex;
          flex-direction: column;
        }

        .chart-header {
          display: flex;
          justify-content: space-between;
          align-items: flex-start;
          margin-bottom: 20px;
          flex-wrap: wrap;
          gap: 8px;
        }

        .card-subtitle {
          font-size: 0.72rem;
          color: var(--text-muted);
          margin-top: 2px;
        }

        .chart-legend {
          display: flex;
          align-items: center;
          gap: 6px;
          font-size: 0.75rem;
          color: var(--text-secondary);
        }

        .legend-dot {
          width: 8px;
          height: 8px;
          border-radius: 50%;
        }

        .legend-revenue {
          background: linear-gradient(to top, var(--primary), var(--primary-light));
        }

        .empty-chart-state {
          display: flex;
          flex-direction: column;
          align-items: center;
          justify-content: center;
          height: 180px;
          color: var(--text-muted);
          gap: 10px;
          font-size: 0.85rem;
        }

        .custom-bar-chart-container {
          position: relative;
          height: 220px;
          margin-top: 10px;
          border-left: 1px solid var(--border-color);
          border-bottom: 1px solid var(--border-color);
          padding-left: 8px;
          padding-top: 8px;
        }

        .bar-chart-viewport {
          display: flex;
          justify-content: space-around;
          align-items: flex-end;
          height: 100%;
          width: 100%;
          position: relative;
          z-index: 2;
        }

        .chart-bar-column {
          display: flex;
          flex-direction: column;
          align-items: center;
          width: 8%;
          height: 100%;
        }

        .bar-wrapper {
          position: relative;
          width: 100%;
          height: 100%;
          display: flex;
          align-items: flex-end;
          justify-content: center;
          cursor: pointer;
        }

        .bar-filled-pill {
          width: 18px;
          min-height: 4px;
          background: linear-gradient(to top, var(--primary), var(--primary-light));
          border-radius: 4px 4px 0 0;
          position: relative;
          transition: all 0.3s ease;
        }

        .bar-wrapper:hover .bar-filled-pill {
            background: linear-gradient(to top, var(--primary-light), var(--primary-dark));
        }

        .bar-glow-effect {
          position: absolute;
          top: 0;
          left: 0;
          width: 100%;
          height: 100%;
          background: rgba(255, 255, 255, 0.1);
          border-radius: 4px 4px 0 0;
          opacity: 0;
          transition: opacity var(--transition-fast);
        }

        .bar-wrapper:hover .bar-glow-effect {
          opacity: 1;
        }

        .bar-label-month {
          font-size: 0.7rem;
          color: var(--text-secondary);
          margin-top: 8px;
          font-weight: 500;
        }

        .bar-tooltip {
          position: absolute;
          bottom: 100%;
          left: 50%;
          transform: translate(-50%, -8px) scale(0.95);
          background: #0f0b1e;
          border: 1px solid var(--border-color);
          border-radius: 6px;
          padding: 6px 10px;
          display: flex;
          flex-direction: column;
          gap: 3px;
          font-size: 0.7rem;
          width: 130px;
          opacity: 0;
          pointer-events: none;
          z-index: 10;
          transition: all 0.15s ease-out;
        }

        .bar-wrapper:hover .bar-tooltip {
          opacity: 1;
          transform: translate(-50%, -8px) scale(1);
        }

        .chart-y-axis-helpers {
          position: absolute;
          top: 0;
          left: 0;
          width: 100%;
          height: 100%;
          display: flex;
          flex-direction: column;
          justify-content: space-between;
          pointer-events: none;
          z-index: 1;
        }

        .helper-line {
          width: 100%;
          height: 1px;
          border-top: 1px dashed rgba(255, 255, 255, 0.03);
          position: relative;
        }

        .helper-label {
          position: absolute;
          right: 100%;
          top: -6px;
          margin-right: 8px;
          font-size: 0.65rem;
          color: var(--text-muted);
          white-space: nowrap;
          font-family: monospace;
        }
      `}</style>
    </div>
  );
};
export default Reports;
