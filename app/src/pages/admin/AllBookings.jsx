import { useState, useEffect } from 'react';
import axios from 'axios';
import toast from 'react-hot-toast';
import { jsPDF } from 'jspdf';
import { DataTable } from '../../components/ui/DataTable';
import { StatusBadge } from '../../components/ui/StatusBadge';
import { 
  Calendar, Check, Trash2, Download, CreditCard, LogOut, ChevronRight
} from 'lucide-react';

export const AllBookings = () => {
  const [bookings, setBookings] = useState([]);
  const [loading, setLoading] = useState(true);
  const [statusFilter, setStatusFilter] = useState('');

  const fetchBookings = async () => {
    try {
      setLoading(true);
      const response = await axios.get('/version2/api/bookings/list.php');
      setBookings(response.data);
    } catch (error) {
      console.error('Failed to fetch all bookings:', error);
      toast.error('Could not load bookings ledger.');
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchBookings();
  }, []);

  const handleAction = async (bookingId, action) => {
    let confirmMsg = '';
    if (action === 'checkin') confirmMsg = 'Confirm guest check-in?';
    else if (action === 'checkout') confirmMsg = 'Confirm guest check-out?';
    else if (action === 'cancel') confirmMsg = 'Cancel this booking?';
    else if (action === 'confirm_payment') confirmMsg = 'Confirm manual payment?';

    if (confirmMsg && !window.confirm(confirmMsg)) return;

    try {
      const response = await axios.post('/version2/api/bookings/update.php', {
        booking_id: bookingId,
        action: action
      });

      if (response.data.success) {
        toast.success(response.data.message || 'Updated successfully.');
        fetchBookings();
      }
    } catch (error) {
      toast.error(error.response?.data?.error || 'Operation failed.');
    }
  };

  const handleDownloadTicket = (booking) => {
    try {
      const doc = new jsPDF({
        orientation: 'portrait',
        unit: 'mm',
        format: 'a5'
      });

      // Header Banner
      doc.setFillColor(92, 45, 145);
      doc.rect(0, 0, 148, 35, 'F');

      doc.setTextColor(255, 255, 255);
      doc.setFont('Helvetica', 'bold');
      doc.setFontSize(16);
      doc.text('HRMS', 74, 15, { align: 'center' });
      
      doc.setFont('Helvetica', 'normal');
      doc.setFontSize(8);
      doc.text('PREMIUM RESERVATION INVOICE', 74, 22, { align: 'center' });

      // Frame
      doc.setDrawColor(229, 231, 235);
      doc.setFillColor(255, 255, 255);
      doc.roundedRect(10, 42, 128, 140, 2, 2, 'FD');

      doc.setFont('Helvetica', 'bold');
      doc.setFontSize(11);
      doc.setTextColor(92, 45, 145);
      doc.text('BOOKING STATEMENT', 16, 52);

      doc.setDrawColor(92, 45, 145);
      doc.setLineWidth(0.3);
      doc.line(16, 55, 132, 55);

      // Meta info
      doc.setFontSize(8);
      doc.setTextColor(100, 100, 100);
      doc.setFont('Helvetica', 'normal');

      // Row 1
      doc.text('GUEST NAME:', 16, 64);
      doc.setFont('Helvetica', 'bold');
      doc.setTextColor(0, 0, 0);
      doc.text(booking.guest_name.toUpperCase(), 16, 69);

      doc.setFont('Helvetica', 'normal');
      doc.setTextColor(100, 100, 100);
      doc.text('BOOKING ID:', 80, 64);
      doc.setFont('Helvetica', 'bold');
      doc.setTextColor(0, 0, 0);
      doc.text(`#${booking.booking_id}`, 80, 69);

      // Row 2
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

      // Row 3
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

      // Row 4
      doc.setFont('Helvetica', 'normal');
      doc.setTextColor(100, 100, 100);
      doc.text('GUESTS:', 16, 112);
      doc.setFont('Helvetica', 'bold');
      doc.setTextColor(0, 0, 0);
      doc.text(`${booking.guests} Guest(s)`, 16, 117);

      doc.setFont('Helvetica', 'normal');
      doc.setTextColor(100, 100, 100);
      doc.text('PAYMENT STATUS:', 80, 112);
      doc.setFont('Helvetica', 'bold');
      doc.setTextColor(16, 185, 129);
      doc.text(booking.payment_status.toUpperCase(), 80, 117);

      doc.setDrawColor(229, 231, 235);
      doc.line(16, 126, 132, 126);

      // Price
      doc.setFont('Helvetica', 'normal');
      doc.setTextColor(100, 100, 100);
      doc.text('TOTAL AMOUNT PAID:', 16, 134);
      
      doc.setFont('Helvetica', 'bold');
      doc.setFontSize(13);
      doc.setTextColor(92, 45, 145);
      doc.text(`Rs. ${booking.total_price.toLocaleString('en-IN')}`, 16, 141);

      doc.save(`Receipt_HRMS_${booking.booking_id}.pdf`);
      toast.success('Invoice downloaded!');
    } catch (error) {
      console.error('PDF Invoice generation error:', error);
      toast.error('Failed to generate invoice PDF.');
    }
  };

  const handleExportCSV = () => {
    if (filteredBookings.length === 0) {
      toast.error('No bookings to export.');
      return;
    }

    const headers = [
      'Booking ID',
      'Guest Name',
      'Guest Email',
      'Guest Phone',
      'Room ID',
      'Room Type',
      'Check-in Date',
      'Check-out Date',
      'Guests',
      'Total Price (Rs.)',
      'Payment Status',
      'Status'
    ];

    const rows = filteredBookings.map(b => [
      b.booking_id,
      `"${(b.guest_name || '').replace(/"/g, '""')}"`,
      `"${(b.guest_email || '').replace(/"/g, '""')}"`,
      `"${(b.guest_phone || '').replace(/"/g, '""')}"`,
      b.room_id,
      `"${(b.room_type || '').replace(/"/g, '""')}"`,
      b.checkin,
      b.checkout,
      b.guests,
      b.total_price,
      b.payment_status.toUpperCase(),
      b.status.toUpperCase()
    ]);

    const csvContent = [
      headers.join(','),
      ...rows.map(e => e.join(','))
    ].join('\n');

    const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
    const url = URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.setAttribute('href', url);
    link.setAttribute('download', `Reservations_Ledger_${new Date().toISOString().split('T')[0]}.csv`);
    link.style.visibility = 'hidden';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    toast.success('CSV exported successfully!');
  };

  const filteredBookings = bookings.filter(b => {
    if (statusFilter && b.status !== statusFilter) return false;
    return true;
  });

  const columns = [
    {
      header: 'ID',
      accessor: 'booking_id',
      cell: (row) => <strong>#{row.booking_id}</strong>
    },
    {
      header: 'Guest Info',
      accessor: 'guest_name',
      cell: (row) => (
        <div style={{ display: 'flex', flexDirection: 'column' }}>
          <span style={{ fontWeight: 600, color: 'var(--text-primary)' }}>{row.guest_name}</span>
          <span style={{ color: 'var(--text-muted)', fontSize: '0.75rem' }}>{row.guest_phone || 'No phone'}</span>
        </div>
      )
    },
    {
      header: 'Room',
      accessor: 'room_id',
      cell: (row) => (
        <div style={{ display: 'flex', flexDirection: 'column' }}>
          <span>Room #{row.room_id}</span>
          <span style={{ color: 'var(--text-muted)', fontSize: '0.75rem' }}>{row.room_type}</span>
        </div>
      )
    },
    {
      header: 'Dates',
      cell: (row) => (
        <div style={{ display: 'flex', alignItems: 'center', gap: '4px', fontSize: '0.8rem' }}>
          <span>{new Date(row.checkin).toLocaleDateString('en-US', { month: 'short', day: 'numeric' })}</span>
          <ChevronRight size={10} style={{ color: 'var(--text-muted)' }} />
          <span>{new Date(row.checkout).toLocaleDateString('en-US', { month: 'short', day: 'numeric' })}</span>
        </div>
      )
    },
    {
      header: 'Amount',
      accessor: 'total_price',
      cell: (row) => (
        <div style={{ display: 'flex', flexDirection: 'column' }}>
          <strong style={{ color: 'var(--success)' }}>Rs. {row.total_price.toLocaleString('en-IN')}</strong>
          <span style={{ fontSize: '0.7rem', color: row.payment_status === 'paid' ? 'var(--success)' : 'var(--warning)' }}>
            {row.payment_status.toUpperCase()}
          </span>
        </div>
      )
    },
    {
      header: 'Status',
      accessor: 'status',
      cell: (row) => <StatusBadge status={row.status} />
    },
    {
      header: 'Actions',
      cell: (row) => (
        <div style={{ display: 'flex', gap: '6px', flexWrap: 'wrap' }}>
          {row.status === 'pending' && row.payment_status === 'unpaid' && (
            <button 
              onClick={() => handleAction(row.booking_id, 'confirm_payment')}
              className="btn btn-success"
              style={{ padding: '5px 10px', fontSize: '0.78rem' }}
              title="Confirm Payment Manually"
            >
              <CreditCard size={13} />
              <span>Mark Paid</span>
            </button>
          )}

          {row.status === 'confirmed' && (
            <button 
              onClick={() => handleAction(row.booking_id, 'checkin')}
              className="btn btn-success"
              style={{ padding: '5px 10px', fontSize: '0.78rem' }}
            >
              <Check size={13} />
              <span>Check-In</span>
            </button>
          )}

          {row.status === 'checked_in' && (
            <button 
              onClick={() => handleAction(row.booking_id, 'checkout')}
              className="btn btn-primary"
              style={{ padding: '5px 10px', fontSize: '0.78rem' }}
            >
              <LogOut size={13} />
              <span>Check-Out</span>
            </button>
          )}

          {['pending', 'confirmed'].includes(row.status) && (
            <button 
              onClick={() => handleAction(row.booking_id, 'cancel')}
              className="btn btn-danger"
              style={{ padding: '5px 10px', fontSize: '0.78rem' }}
            >
              <Trash2 size={13} />
              <span>Cancel</span>
            </button>
          )}

          {['checked_in', 'checked_out', 'confirmed'].includes(row.status) && (
            <button 
              onClick={() => handleDownloadTicket(row)}
              className="btn btn-secondary"
              style={{ padding: '5px 10px', fontSize: '0.78rem' }}
              title="Download PDF"
            >
              <Download size={13} />
            </button>
          )}
        </div>
      )
    }
  ];

  return (
    <div className="page animate-fade-in">
      <div className="tabbar-header" style={{ borderBottom: 'none', marginBottom: '8px' }}>
        <div className="title-section" style={{ display: 'flex', alignItems: 'center', gap: '10px' }}>
          <Calendar size={18} className="icon-purple" />
          <h3 style={{ fontSize: '1rem', color: 'var(--text-primary)' }}>Reservations Ledger</h3>
        </div>

        <div className="bookings-filter-bar" style={{ display: 'flex', alignItems: 'center', gap: '8px' }}>
          <label style={{ fontSize: '0.75rem', color: 'var(--text-muted)' }}>Status:</label>
          <select 
            value={statusFilter} 
            onChange={(e) => setStatusFilter(e.target.value)}
            className="form-input"
            style={{ width: '130px', padding: '6px 10px', fontSize: '0.8rem' }}
          >
            <option value="">All</option>
            <option value="pending">Pending</option>
            <option value="confirmed">Confirmed</option>
            <option value="checked_in">Checked In</option>
            <option value="checked_out">Checked Out</option>
            <option value="cancelled">Cancelled</option>
          </select>

          <button
            onClick={handleExportCSV}
            className="btn btn-secondary"
            style={{ display: 'flex', alignItems: 'center', gap: '6px', padding: '6px 12px', fontSize: '0.8rem', height: 'fit-content' }}
          >
            <Download size={13} />
            <span>Export CSV</span>
          </button>
        </div>
      </div>

      <DataTable 
        columns={columns}
        data={filteredBookings}
        loading={loading}
        searchKey="guest_name"
        searchPlaceholder="Search guest name..."
        emptyMessage="No reservations matched."
      />

      <style>{`
        .icon-purple {
          color: var(--primary-dark);
        }
      `}</style>
    </div>
  );
};
export default AllBookings;
