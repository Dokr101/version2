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

  // ============================================================
// REPLACE the entire handleDownloadInvoice function in
// app/src/pages/staff/StaffDashboard.jsx
//
// The API already returns: guest_name, guest_email, guest_phone,
// room_id, room_type, checkin, checkout, guests, total_price,
// payment_status, booking_id
//
// Nights and per-night rate are calculated from total_price.
// Service charge (10%) and VAT (13%) are calculated from base.
// ============================================================

const handleDownloadInvoice = (booking) => {
  try {
    const doc = new jsPDF({
      orientation: 'portrait',
      unit: 'mm',
      format: 'a4'   // A4 gives more room for the charges table
    });

    const W = 210; // A4 width
    const margin = 16;
    const col2 = 120; // right column x

    // ── HEADER BAND ─────────────────────────────────────────
    doc.setFillColor(26, 26, 26);          // near-black, not purple
    doc.rect(0, 0, W, 42, 'F');

    doc.setTextColor(255, 255, 255);
    doc.setFont('Helvetica', 'bold');
    doc.setFontSize(20);
    doc.text('HRMS', margin, 18);

    doc.setFont('Helvetica', 'normal');
    doc.setFontSize(7.5);
    doc.setTextColor(170, 170, 170);
    doc.text('Hotel Room Management System', margin, 24);
    doc.text('Thamel, Kathmandu 44600, Nepal', margin, 29);
    doc.text('+977-1-4XXXXXX  |  info@hrms.com.np', margin, 34);

    // Invoice label (top-right)
    doc.setTextColor(170, 170, 170);
    doc.setFontSize(7.5);
    doc.text('INVOICE', col2, 16, { align: 'left' });
    doc.setFont('Helvetica', 'bold');
    doc.setTextColor(255, 255, 255);
    doc.setFontSize(11);
    doc.text(`#INV-${String(booking.booking_id).padStart(5, '0')}`, col2, 23, { align: 'left' });
    doc.setFont('Helvetica', 'normal');
    doc.setFontSize(7.5);
    doc.setTextColor(170, 170, 170);
    doc.text(`Issued: ${new Date().toLocaleDateString('en-US', { dateStyle: 'medium' })}`, col2, 29, { align: 'left' });

    // Payment status badge
    const isPaid = booking.payment_status === 'paid';
    doc.setFillColor(isPaid ? 45 : 180, isPaid ? 106 : 70, isPaid ? 79 : 30);
    doc.roundedRect(col2, 32, 28, 6, 1, 1, 'F');
    doc.setFont('Helvetica', 'bold');
    doc.setFontSize(6.5);
    doc.setTextColor(isPaid ? 216 : 255, isPaid ? 243 : 220, isPaid ? 220 : 180);
    doc.text(isPaid ? 'PAID' : 'PENDING', col2 + 14, 36.2, { align: 'center' });

    // ── SECTION: BILLED TO + BOOKING DETAILS ────────────────
    let y = 52;

    // Left: Guest info
    doc.setFont('Helvetica', 'normal');
    doc.setFontSize(7);
    doc.setTextColor(150, 150, 150);
    doc.text('BILLED TO', margin, y);

    doc.setFont('Helvetica', 'bold');
    doc.setFontSize(10);
    doc.setTextColor(20, 20, 20);
    doc.text(booking.guest_name, margin, y + 6);

    doc.setFont('Helvetica', 'normal');
    doc.setFontSize(8);
    doc.setTextColor(80, 80, 80);
    doc.text(booking.guest_email || '—', margin, y + 12);
    doc.text(booking.guest_phone || '—', margin, y + 17);

    // Right: Booking meta
    doc.setFont('Helvetica', 'normal');
    doc.setFontSize(7);
    doc.setTextColor(150, 150, 150);
    doc.text('BOOKING REFERENCE', col2, y);
    doc.setFont('Helvetica', 'bold');
    doc.setFontSize(10);
    doc.setTextColor(20, 20, 20);
    doc.text(`#BK-${String(booking.booking_id).padStart(4, '0')}`, col2, y + 6);

    // Room info
    doc.setFont('Helvetica', 'normal');
    doc.setFontSize(7);
    doc.setTextColor(150, 150, 150);
    doc.text('ROOM ASSIGNED', col2, y + 14);
    doc.setFont('Helvetica', 'bold');
    doc.setFontSize(8.5);
    doc.setTextColor(20, 20, 20);
    doc.text(`${booking.room_type} Room — #${booking.room_id}`, col2, y + 20);
    doc.setFont('Helvetica', 'normal');
    doc.setFontSize(8);
    doc.setTextColor(100, 100, 100);
    doc.text(`${booking.guests} Guest${booking.guests > 1 ? 's' : ''}`, col2, y + 26);

    // ── STAY PERIOD ROW ──────────────────────────────────────
    y += 34;
    doc.setDrawColor(230, 230, 230);
    doc.setLineWidth(0.3);
    doc.line(margin, y, W - margin, y);
    y += 8;

    const checkinDate  = new Date(booking.checkin);
    const checkoutDate = new Date(booking.checkout);
    const nights = Math.round((checkoutDate - checkinDate) / (1000 * 60 * 60 * 24));
    const nightlyRate = nights > 0 ? booking.total_price / nights : booking.total_price;

    const fmtDate = (d) => d.toLocaleDateString('en-US', { day: '2-digit', month: 'short', year: 'numeric' });

    const stayItems = [
      { label: 'CHECK-IN',   value: fmtDate(checkinDate)  },
      { label: 'CHECK-OUT',  value: fmtDate(checkoutDate) },
      { label: 'DURATION',   value: `${nights} Night${nights !== 1 ? 's' : ''}` },
    ];

    stayItems.forEach((item, i) => {
      const x = margin + i * 58;
      doc.setFont('Helvetica', 'normal');
      doc.setFontSize(7);
      doc.setTextColor(150, 150, 150);
      doc.text(item.label, x, y);
      doc.setFont('Helvetica', 'bold');
      doc.setFontSize(9);
      doc.setTextColor(20, 20, 20);
      doc.text(item.value, x, y + 6);
    });

    // ── CHARGES TABLE ────────────────────────────────────────
    y += 20;
    doc.setDrawColor(230, 230, 230);
    doc.line(margin, y, W - margin, y);
    y += 7;

    // Table header
    doc.setFont('Helvetica', 'normal');
    doc.setFontSize(7);
    doc.setTextColor(150, 150, 150);
    doc.text('DESCRIPTION',         margin,      y);
    doc.text('QTY',                 112,         y, { align: 'center' });
    doc.text('RATE (Rs.)',           140,         y, { align: 'right'  });
    doc.text('AMOUNT (Rs.)',         W - margin,  y, { align: 'right'  });

    y += 4;
    doc.setLineWidth(0.2);
    doc.line(margin, y, W - margin, y);

    // Calculate breakdown
    const serviceCharge = booking.total_price * 0.10;
    const vat           = booking.total_price * 0.13;
    const grandTotal    = booking.total_price + serviceCharge + vat;

    const fmt = (n) => n.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

    const rows = [
      {
        desc: `${booking.room_type} Room — nightly accommodation`,
        qty: `${nights} night${nights !== 1 ? 's' : ''}`,
        rate: fmt(nightlyRate),
        amount: fmt(booking.total_price)
      },
      {
        desc: 'Service charge (10%)',
        qty: '—',
        rate: '',
        amount: fmt(serviceCharge)
      },
      {
        desc: 'VAT (13%)',
        qty: '—',
        rate: '',
        amount: fmt(vat)
      },
    ];

    rows.forEach((row) => {
      y += 9;
      doc.setFont('Helvetica', 'normal');
      doc.setFontSize(8.5);
      doc.setTextColor(40, 40, 40);
      doc.text(row.desc,            margin,      y);
      doc.setTextColor(100, 100, 100);
      doc.text(row.qty,             112,         y, { align: 'center' });
      doc.text(row.rate,            140,         y, { align: 'right'  });
      doc.setFont('Helvetica', 'bold');
      doc.setTextColor(20, 20, 20);
      doc.text(row.amount,          W - margin,  y, { align: 'right'  });
      doc.setLineWidth(0.15);
      doc.setDrawColor(240, 240, 240);
      doc.line(margin, y + 2.5, W - margin, y + 2.5);
    });

    // ── TOTAL ROW ────────────────────────────────────────────
    y += 14;
    doc.setFillColor(248, 248, 246);
    doc.rect(margin, y - 5, W - margin * 2, 14, 'F');
    doc.setFont('Helvetica', 'normal');
    doc.setFontSize(8.5);
    doc.setTextColor(100, 100, 100);
    doc.text('TOTAL AMOUNT DUE', margin + 3, y + 3);
    doc.setFont('Helvetica', 'bold');
    doc.setFontSize(14);
    doc.setTextColor(26, 26, 26);
    doc.text(`Rs. ${fmt(grandTotal)}`, W - margin, y + 3, { align: 'right' });

    // ── PAYMENT INFO ─────────────────────────────────────────
    y += 22;
    doc.setDrawColor(230, 230, 230);
    doc.line(margin, y, W - margin, y);
    y += 7;

    doc.setFont('Helvetica', 'normal');
    doc.setFontSize(7);
    doc.setTextColor(150, 150, 150);
    doc.text('PAYMENT INFORMATION', margin, y);
    y += 6;

    const paymentRows = [
      ['Method',          'Khalti (Online Payment)'],
      ['Payment Status',  isPaid ? 'Paid in full' : booking.payment_status],
      ['Transaction ID',  booking.transaction_id || 'N/A'],
    ];

    paymentRows.forEach(([label, value]) => {
      doc.setFont('Helvetica', 'normal');
      doc.setFontSize(8);
      doc.setTextColor(120, 120, 120);
      doc.text(label, margin, y);
      doc.setFont('Helvetica', 'bold');
      doc.setTextColor(label === 'Payment Status' && isPaid ? 45 : 20,
                       label === 'Payment Status' && isPaid ? 106 : 20,
                       label === 'Payment Status' && isPaid ? 79 : 20);
      doc.text(value, margin + 40, y);
      y += 6;
    });

    // ── FOOTER ───────────────────────────────────────────────
    y += 6;
    doc.setDrawColor(230, 230, 230);
    doc.line(margin, y, W - margin, y);
    y += 6;
    doc.setFont('Helvetica', 'normal');
    doc.setFontSize(7);
    doc.setTextColor(180, 180, 180);
    const footerText =
      'Thank you for choosing HRMS. This is a computer-generated invoice and does not require a signature. ' +
      'For billing queries, contact billing@hrms.com.np or call our front desk.';
    const footerLines = doc.splitTextToSize(footerText, W - margin * 2);
    doc.text(footerLines, margin, y);

    // ── SAVE ─────────────────────────────────────────────────
    doc.save(`Invoice_HRMS_${booking.booking_id}.pdf`);
    toast.success('Invoice downloaded.');

  } catch (error) {
    console.error('Invoice PDF error:', error);
    toast.error('Failed to generate invoice.');
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
