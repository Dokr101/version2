import { useState, useEffect } from 'react';
import axios from 'axios';
import toast from 'react-hot-toast';
import { jsPDF } from 'jspdf';
import { StatCard } from '../../components/ui/StatCard';
import { StatusBadge } from '../../components/ui/StatusBadge';
import { 
  Calendar, Check, LogOut, Users, Clock, Download, Inbox
} from 'lucide-react';

export const StaffDashboard = () => {
  const [dashboardData, setDashboardData] = useState(null);
  const [loading, setLoading] = useState(true);
  const [activeRegister, setActiveRegister] = useState('checkin');

  const fetchDashboardData = async () => {
    try {
      setLoading(true);
      const response = await axios.get('/version2/api/dashboard/staff.php');
      setDashboardData(response.data);
    } catch (error) {
      console.error('Failed to load staff dashboard:', error);
      toast.error('Could not load staff operations panel.');
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchDashboardData();
  }, []);

  const handleAction = async (bookingId, action) => {
    const confirmMsg = action === 'checkin' 
      ? 'Confirm guest check-in?' 
      : 'Confirm guest check-out?';

    if (!window.confirm(confirmMsg)) return;

    try {
      const response = await axios.post('/version2/api/bookings/update.php', {
        booking_id: bookingId,
        action: action
      });

      if (response.data.success) {
        toast.success(response.data.message || 'Updated successfully!');
        fetchDashboardData();
      }
    } catch (error) {
      toast.error(error.response?.data?.error || 'Operation failed.');
    }
  };

  const handleDownloadInvoice = (booking) => {
    try {
      const doc = new jsPDF({
        orientation: 'portrait',
        unit: 'mm',
        format: 'a5'
      });

      doc.setFillColor(92, 45, 145);
      doc.rect(0, 0, 148, 35, 'F');

      doc.setTextColor(255, 255, 255);
      doc.setFont('Helvetica', 'bold');
      doc.setFontSize(16);
      doc.text('HRMS', 74, 15, { align: 'center' });
      
      doc.setFont('Helvetica', 'normal');
      doc.setFontSize(8);
      doc.text('GUEST INVOICE STATEMENT', 74, 22, { align: 'center' });

      doc.setDrawColor(229, 231, 235);
      doc.setFillColor(255, 255, 255);
      doc.roundedRect(10, 42, 128, 140, 2, 2, 'FD');

      doc.setFont('Helvetica', 'bold');
      doc.setFontSize(11);
      doc.setTextColor(92, 45, 145);
      doc.text('FOLIO SUMMARY', 16, 52);

      doc.setDrawColor(92, 45, 145);
      doc.setLineWidth(0.3);
      doc.line(16, 55, 132, 55);

      doc.setFontSize(8);
      doc.setTextColor(100, 100, 100);
      doc.setFont('Helvetica', 'normal');

      doc.text('GUEST NAME:', 16, 64);
      doc.setFont('Helvetica', 'bold');
      doc.setTextColor(0, 0, 0);
      doc.text(booking.guest_name.toUpperCase(), 16, 69);

      doc.setFont('Helvetica', 'normal');
      doc.setTextColor(100, 100, 100);
      doc.text('INVOICE NO:', 80, 64);
      doc.setFont('Helvetica', 'bold');
      doc.setTextColor(0, 0, 0);
      doc.text(`INV-${booking.booking_id}`, 80, 69);

      doc.setFont('Helvetica', 'normal');
      doc.setTextColor(100, 100, 100);
      doc.text('ROOM TYPE:', 16, 80);
      doc.setFont('Helvetica', 'bold');
      doc.setTextColor(0, 0, 0);
      doc.text(`${booking.room_type} Room`, 16, 85);

      doc.setFont('Helvetica', 'normal');
      doc.setTextColor(100, 100, 100);
      doc.text('ROOM NO:', 80, 80);
      doc.setFont('Helvetica', 'bold');
      doc.setTextColor(0, 0, 0);
      doc.text(`Room #${booking.room_id}`, 80, 85);

      doc.setFont('Helvetica', 'normal');
      doc.setTextColor(100, 100, 100);
      doc.text('CHECK-IN:', 16, 96);
      doc.setFont('Helvetica', 'bold');
      doc.setTextColor(0, 0, 0);
      doc.text(new Date(booking.checkin).toLocaleDateString('en-US', { dateStyle: 'medium' }), 16, 101);

      doc.setFont('Helvetica', 'normal');
      doc.setTextColor(100, 100, 100);
      doc.text('CHECK-OUT:', 80, 96);
      doc.setFont('Helvetica', 'bold');
      doc.setTextColor(0, 0, 0);
      doc.text(new Date(booking.checkout).toLocaleDateString('en-US', { dateStyle: 'medium' }), 80, 101);

      doc.setFont('Helvetica', 'normal');
      doc.setTextColor(100, 100, 100);
      doc.text('BILLING METHOD:', 16, 112);
      doc.setFont('Helvetica', 'bold');
      doc.setTextColor(0, 0, 0);
      doc.text('Khalti (Online)', 16, 117);

      doc.setFont('Helvetica', 'normal');
      doc.setTextColor(100, 100, 100);
      doc.text('STATUS:', 80, 112);
      doc.setFont('Helvetica', 'bold');
      doc.setTextColor(16, 185, 129);
      doc.text('SETTLED', 80, 117);

      doc.setDrawColor(229, 231, 235);
      doc.line(16, 126, 132, 126);

      doc.setFont('Helvetica', 'normal');
      doc.setTextColor(100, 100, 100);
      doc.text('TOTAL AMOUNT:', 16, 134);
      
      doc.setFont('Helvetica', 'bold');
      doc.setFontSize(13);
      doc.setTextColor(92, 45, 145);
      doc.text(`Rs. ${booking.total_price.toLocaleString('en-IN')}`, 16, 141);

      doc.save(`Invoice_HRMS_${booking.booking_id}.pdf`);
      toast.success('Invoice downloaded.');
    } catch (error) {
      console.error('Invoice PDF error:', error);
      toast.error('Failed to print invoice.');
    }
  };

  if (loading) {
    return (
      <div className="dashboard-loading">
        <div className="spinner"></div>
        <span>Loading operational ledger...</span>
      </div>
    );
  }

  const { stats, todayCheckins = [], todayCheckouts = [] } = dashboardData || {};

  // Filter lists based on checklist specifications
  const arrivalsList = todayCheckins.filter(b => b.status === 'confirmed');
  const departuresList = todayCheckouts.filter(b => b.status === 'checked_in');

  return (
    <div className="page animate-fade-in">
      {/* Stats Grid */}
      <div className="stats-row stagger-children">
        <StatCard 
          title="Check-ins Today" 
          value={stats?.todayCheckinsCount || 0} 
          icon={Calendar} 
          color="primary" 
        />
        <StatCard 
          title="Check-outs Today" 
          value={stats?.todayCheckoutsCount || 0} 
          icon={LogOut} 
          color="warning" 
        />
        <StatCard 
          title="Active Stays" 
          value={stats?.occupiedRooms || 0} 
          icon={Users} 
          color="success" 
        />
        <StatCard 
          title="Pending Reservations" 
          value={stats?.pendingReservationsCount || 0} 
          icon={Clock} 
          color="info" 
        />
      </div>

      {/* Grid of Operations */}
      <div className="operations-grid" style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(320px, 1fr))', gap: '24px', marginTop: '24px' }}>
        {/* Today's Arrivals */}
        <div className="glass-card" style={{ padding: '24px', borderRadius: '12px', border: '1px solid var(--border-color)', background: 'var(--card-bg)' }}>
          <h3 style={{ display: 'flex', alignItems: 'center', gap: '8px', marginBottom: '18px', fontSize: '1.1rem', fontWeight: 600, color: 'var(--text-primary)' }}>
            <Check size={18} style={{ color: 'var(--success)' }} />
            <span>Today's Arrivals</span>
            <span style={{ fontSize: '0.8rem', background: 'rgba(72, 187, 120, 0.1)', color: 'var(--success)', padding: '2px 8px', borderRadius: '12px', marginLeft: 'auto' }}>
              {arrivalsList.length}
            </span>
          </h3>

          {arrivalsList.length === 0 ? (
            <div className="empty-state" style={{ padding: '30px 10px', textAlign: 'center', color: 'var(--text-secondary)' }}>
              <Inbox size={32} style={{ marginBottom: '10px', opacity: 0.6 }} />
              <p style={{ fontSize: '0.9rem' }}>No arrivals remaining scheduled today.</p>
            </div>
          ) : (
            <div style={{ display: 'flex', flexDirection: 'column', gap: '12px' }}>
              {arrivalsList.map((booking) => (
                <div key={booking.booking_id} style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', padding: '14px', background: 'rgba(0,0,0,0.01)', borderRadius: '10px', border: '1px solid var(--border-color)' }}>
                  <div>
                    <strong style={{ display: 'block', fontSize: '0.95rem', color: 'var(--text-primary)' }}>{booking.guest_name}</strong>
                    <span style={{ fontSize: '0.8rem', color: 'var(--text-secondary)' }}>
                      Room #{booking.room_id} ({booking.room_type})
                    </span>
                  </div>
                  <button 
                    onClick={() => handleAction(booking.booking_id, 'checkin')}
                    className="btn btn-success"
                    style={{ padding: '8px 14px', fontSize: '0.8rem', display: 'flex', alignItems: 'center', gap: '6px', borderRadius: '6px' }}
                  >
                    <Check size={14} />
                    <span>Check-In</span>
                  </button>
                </div>
              ))}
            </div>
          )}
        </div>

        {/* Today's Departures */}
        <div className="glass-card" style={{ padding: '24px', borderRadius: '12px', border: '1px solid var(--border-color)', background: 'var(--card-bg)' }}>
          <h3 style={{ display: 'flex', alignItems: 'center', gap: '8px', marginBottom: '18px', fontSize: '1.1rem', fontWeight: 600, color: 'var(--text-primary)' }}>
            <LogOut size={18} style={{ color: 'var(--warning)' }} />
            <span>Today's Departures</span>
            <span style={{ fontSize: '0.8rem', background: 'rgba(237, 137, 54, 0.1)', color: 'var(--warning)', padding: '2px 8px', borderRadius: '12px', marginLeft: 'auto' }}>
              {departuresList.length}
            </span>
          </h3>

          {departuresList.length === 0 ? (
            <div className="empty-state" style={{ padding: '30px 10px', textAlign: 'center', color: 'var(--text-secondary)' }}>
              <Inbox size={32} style={{ marginBottom: '10px', opacity: 0.6 }} />
              <p style={{ fontSize: '0.9rem' }}>No active departures remaining scheduled today.</p>
            </div>
          ) : (
            <div style={{ display: 'flex', flexDirection: 'column', gap: '12px' }}>
              {departuresList.map((booking) => (
                <div key={booking.booking_id} style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', padding: '14px', background: 'rgba(0,0,0,0.01)', borderRadius: '10px', border: '1px solid var(--border-color)' }}>
                  <div>
                    <strong style={{ display: 'block', fontSize: '0.95rem', color: 'var(--text-primary)' }}>{booking.guest_name}</strong>
                    <span style={{ fontSize: '0.8rem', color: 'var(--text-secondary)' }}>
                      Room #{booking.room_id} ({booking.room_type})
                    </span>
                  </div>
                  <div style={{ display: 'flex', gap: '6px' }}>
                    <button 
                      onClick={() => handleDownloadInvoice(booking)}
                      className="btn btn-secondary"
                      style={{ padding: '8px', borderRadius: '6px' }}
                      title="Print Folio Statement"
                    >
                      <Download size={14} />
                    </button>
                    <button 
                      onClick={() => handleAction(booking.booking_id, 'checkout')}
                      className="btn btn-primary"
                      style={{ padding: '8px 14px', fontSize: '0.8rem', display: 'flex', alignItems: 'center', gap: '6px', borderRadius: '6px' }}
                    >
                      <LogOut size={14} />
                      <span>Check-Out</span>
                    </button>
                  </div>
                </div>
              ))}
            </div>
          )}
        </div>
      </div>
    </div>
  );
};
export default StaffDashboard;
