import { useState, useEffect } from 'react';
import axios from 'axios';
import toast from 'react-hot-toast';
import { jsPDF } from 'jspdf';
import { DataTable } from '../../components/ui/DataTable';
import { StatusBadge } from '../../components/ui/StatusBadge';
import { Download, CreditCard, Trash2 } from 'lucide-react';

export const MyBookings = () => {
  const [bookings, setBookings] = useState([]);
  const [loading, setLoading] = useState(true);

  const fetchBookings = async () => {
    try {
      setLoading(true);
      const response = await axios.get('/version2/api/bookings/list.php');
      setBookings(response.data);
    } catch (error) {
      console.error('Failed to load bookings:', error);
      toast.error('Could not load booking history.');
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchBookings();
  }, []);

  const handleCancel = async (bookingId) => {
    if (!window.confirm('Cancel this booking?')) return;

    try {
      const response = await axios.post('/version2/api/bookings/cancel.php', {
        booking_id: bookingId
      });
      if (response.data.success) {
        toast.success(response.data.message || 'Booking cancelled.');
        fetchBookings();
      }
    } catch (error) {
      toast.error(error.response?.data?.error || 'Failed to cancel booking.');
    }
  };

  const handleDownloadTicket = (booking) => {
    try {
      const doc = new jsPDF({ orientation: 'portrait', unit: 'mm', format: 'a5' });

      const W = 148, m = 12, cW = W - m * 2, c2 = m + cW / 2;
      const fmtDate = (d) => new Date(d).toLocaleDateString('en-US', { day: 'numeric', month: 'short', year: 'numeric' });
      const fmtNPR = (n) => `Rs. ${Math.round(n).toLocaleString('en-IN')}`;

      const cin = new Date(booking.checkin);
      const cout = new Date(booking.checkout);
      const nights = Math.ceil((cout - cin) / 86400000);
      const nightlyRate = booking.room_price || 0;
      const subtotal = nightlyRate * nights;
      const vat = Math.round(subtotal * 0.13);
      const grandTotal = subtotal + vat;

      // ── HEADER (dark) ──────────────────────────────────────────
      doc.setFillColor(26, 26, 26);
      doc.rect(0, 0, W, 46, 'F');

      doc.setTextColor(255, 255, 255);
      doc.setFont('Helvetica', 'normal');
      doc.setFontSize(17);
      doc.text('HRMS', m, 16);

      doc.setFontSize(7);
      doc.setTextColor(136, 136, 136);
      doc.text('Lainchaur, Kathmandu 44600, Nepal', m, 24);
      doc.text('+977 9803040024  \u00b7  info@hrms.com.np', m, 29);

      doc.setFontSize(7);
      doc.setTextColor(136, 136, 136);
      doc.text('INVOICE', W - m, 13, { align: 'right' });

      doc.setFontSize(13);
      doc.setTextColor(255, 255, 255);
      doc.text(`#INV-${String(booking.booking_id).padStart(5, '0')}`, W - m, 21, { align: 'right' });

      doc.setFontSize(7);
      doc.setTextColor(119, 119, 119);
      doc.text(`Issued: ${fmtDate(new Date())}`, W - m, 27, { align: 'right' });

      doc.setFillColor(45, 106, 79);
      doc.roundedRect(W - m - 22, 31, 22, 7, 1, 1, 'F');
      doc.setFontSize(6.5);
      doc.setTextColor(216, 243, 220);
      doc.text('SETTLED', W - m - 11, 35.5, { align: 'center' });

      let y = 48;

      // ── BILLED TO / BOOKING DETAILS ────────────────────────────
      doc.setFontSize(6.5);
      doc.setTextColor(153, 153, 153);
      doc.text('BILLED TO', m, y + 6);
      doc.text('BOOKING DETAILS', c2, y + 6);

      doc.setFontSize(12);
      doc.setTextColor(26, 26, 26);
      doc.text(booking.guest_name || '', m, y + 13);

      doc.setFontSize(8.5);
      doc.setTextColor(102, 102, 102);
      if (booking.guest_email) doc.text(booking.guest_email, m, y + 19);
      if (booking.guest_phone) doc.text(booking.guest_phone, m, y + 25);

      doc.setFontSize(8);
      doc.setTextColor(153, 153, 153);
      doc.text('Booking ID', c2, y + 13);
      doc.setFont('Helvetica', 'bold');
      doc.setFontSize(11);
      doc.setTextColor(17, 17, 17);
      doc.text(`#BK-${String(booking.booking_id).padStart(4, '0')}`, c2, y + 20);

      y += 32;
      doc.setFont('Helvetica', 'normal');
      doc.setDrawColor(238, 238, 238);
      doc.line(m, y, W - m, y);

      // ── ROOM ASSIGNED / STAY PERIOD ────────────────────────────
      y += 3;
      doc.setFontSize(6.5);
      doc.setTextColor(153, 153, 153);
      doc.text('ROOM ASSIGNED', m, y + 6);
      doc.text('STAY PERIOD', c2, y + 6);

      doc.setFontSize(11);
      doc.setTextColor(26, 26, 26);
      doc.text(`${booking.room_type} \u2014 Room ${booking.room_id}`, m, y + 13);
      doc.setFontSize(8);
      doc.setTextColor(136, 136, 136);
      doc.text(`${booking.guests} Guest${booking.guests > 1 ? 's' : ''}`, m, y + 19);

      [['Check-in', fmtDate(cin)], ['Check-out', fmtDate(cout)], ['Duration', `${nights} Night${nights !== 1 ? 's' : ''}`]].forEach(([lbl, val], i) => {
        const sy = y + 12 + i * 6;
        doc.setFontSize(8); doc.setTextColor(153, 153, 153); doc.text(lbl, c2, sy);
        doc.setFont('Helvetica', 'bold'); doc.setTextColor(17, 17, 17); doc.text(val, c2 + 24, sy);
        doc.setFont('Helvetica', 'normal');
      });

      y += 30;
      doc.setDrawColor(238, 238, 238);
      doc.line(m, y, W - m, y);

      // ── CHARGES ────────────────────────────────────────────────
      y += 5;
      doc.setFontSize(6.5); doc.setTextColor(153, 153, 153);
      doc.text('CHARGES', m, y);
      y += 7;

      doc.setFontSize(8); doc.setTextColor(170, 170, 170);
      doc.text('Description', m, y);
      doc.text('Qty', m + cW * 0.52, y, { align: 'center' });
      doc.text('Rate', m + cW * 0.76, y, { align: 'right' });
      doc.text('Amount', W - m, y, { align: 'right' });
      doc.setDrawColor(238, 238, 238);
      doc.line(m, y + 2, W - m, y + 2);
      y += 8;

      [
        [`${booking.room_type} \u2014 nightly rate`, String(nights), fmtNPR(nightlyRate), fmtNPR(subtotal)],
        ['VAT (13%)', '\u2014', '', fmtNPR(vat)],
      ].forEach(([desc, qty, rate, amt]) => {
        doc.setFontSize(8.5); doc.setTextColor(51, 51, 51); doc.text(desc, m, y);
        doc.setTextColor(85, 85, 85);
        doc.text(qty, m + cW * 0.52, y, { align: 'center' });
        doc.text(rate, m + cW * 0.76, y, { align: 'right' });
        doc.setFont('Helvetica', 'bold'); doc.setTextColor(17, 17, 17);
        doc.text(amt, W - m, y, { align: 'right' });
        doc.setFont('Helvetica', 'normal');
        doc.setDrawColor(245, 245, 245);
        doc.line(m, y + 2, W - m, y + 2);
        y += 8;
      });

      doc.setDrawColor(238, 238, 238);
      doc.line(m, y, W - m, y);

      // ── TOTAL ──────────────────────────────────────────────────
      doc.setFillColor(250, 250, 248);
      doc.rect(0, y, W, 14, 'F');
      doc.setFontSize(9); doc.setTextColor(119, 119, 119);
      doc.text('Total amount due', m, y + 9);
      doc.setFontSize(18); doc.setTextColor(17, 17, 17);
      doc.text(fmtNPR(grandTotal), W - m, y + 10, { align: 'right' });
      y += 14;
      doc.setDrawColor(238, 238, 238);
      doc.line(m, y, W - m, y);

      // ── PAYMENT INFORMATION ────────────────────────────────────
      y += 5;
      doc.setFontSize(6.5); doc.setTextColor(153, 153, 153);
      doc.text('PAYMENT INFORMATION', m, y);
      y += 7;

      [
        ['Method', 'Khalti (Online)', false],
        ['Payment date', fmtDate(cin), false],
        ['Payment status', 'Paid in full', true],
      ].forEach(([lbl, val, green]) => {
        doc.setFontSize(8.5); doc.setTextColor(136, 136, 136); doc.text(lbl, m, y);
        doc.setTextColor(green ? 45 : 51, green ? 106 : 51, green ? 79 : 51);
        if (green) doc.setFont('Helvetica', 'bold');
        doc.text(val, c2, y);
        doc.setFont('Helvetica', 'normal');
        y += 6;
      });

      doc.setDrawColor(238, 238, 238);
      doc.line(m, y, W - m, y);

      // ── NOTE ───────────────────────────────────────────────────
      y += 5;
      doc.setFontSize(6.5); doc.setTextColor(153, 153, 153);
      doc.text('NOTE', m, y);
      y += 6;
      doc.setFontSize(7.5); doc.setTextColor(170, 170, 170);
      doc.text(doc.splitTextToSize(
        'Thank you for staying at HRMS. This is a computer-generated invoice and does not require a signature. For queries, contact our front desk or email billing@hrms.com.np.',
        cW
      ), m, y);

      doc.save(`Invoice_HRMS_${booking.booking_id}.pdf`);
      toast.success('Invoice downloaded.');
    } catch (error) {
      console.error('PDF generation error:', error);
      toast.error('Failed to generate invoice PDF.');
    }
  };

  const columns = [
    {
      header: 'Booking ID',
      accessor: 'booking_id',
      cell: (row) => <strong>#{row.booking_id}</strong>
    },
    {
      header: 'Room Type',
      accessor: 'room_type',
      cell: (row) => <span>{row.room_type} Room</span>
    },
    {
      header: 'Check-In',
      accessor: 'checkin',
      cell: (row) => new Date(row.checkin).toLocaleDateString('en-US', { dateStyle: 'medium' })
    },
    {
      header: 'Check-Out',
      accessor: 'checkout',
      cell: (row) => new Date(row.checkout).toLocaleDateString('en-US', { dateStyle: 'medium' })
    },
    {
      header: 'Guests',
      accessor: 'guests'
    },
    {
      header: 'Total Price',
      accessor: 'total_price',
      cell: (row) => <span style={{ color: 'var(--success)', fontWeight: 600 }}>Rs. {row.total_price.toLocaleString('en-IN')}</span>
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
          {row.status === 'pending' && (
            <>
              <a
                href={`/version2/guest/initiate_khalti_payment.php?booking_id=${row.booking_id}`}
                className="btn btn-success"
                style={{ padding: '5px 10px', fontSize: '0.78rem' }}
              >
                <CreditCard size={13} />
                <span>Pay</span>
              </a>
              <button
                onClick={() => handleCancel(row.booking_id)}
                className="btn btn-danger"
                style={{ padding: '5px 10px', fontSize: '0.78rem' }}
              >
                <Trash2 size={13} />
                <span>Cancel</span>
              </button>
            </>
          )}

          {row.status === 'confirmed' && (
            <>
              <button
                onClick={() => handleDownloadTicket(row)}
                className="btn btn-primary"
                style={{ padding: '5px 10px', fontSize: '0.78rem' }}
              >
                <Download size={13} />
                <span>Invoice</span>
              </button>
              <button
                onClick={() => handleCancel(row.booking_id)}
                className="btn btn-danger"
                style={{ padding: '5px 10px', fontSize: '0.78rem' }}
              >
                <Trash2 size={13} />
                <span>Cancel</span>
              </button>
            </>
          )}

          {['checked_in', 'checked_out'].includes(row.status) && (
            <button
              onClick={() => handleDownloadTicket(row)}
              className="btn btn-secondary"
              style={{ padding: '5px 10px', fontSize: '0.78rem' }}
            >
              <Download size={13} />
              <span>Invoice</span>
            </button>
          )}

          {row.status === 'cancelled' && (
            <span style={{ color: 'var(--text-muted)', fontSize: '0.8rem' }}>None</span>
          )}
        </div>
      )
    }
  ];

  return (
    <div className="page animate-fade-in">
      <DataTable
        columns={columns}
        data={bookings}
        loading={loading}
        searchKey="booking_id"
        searchPlaceholder="Search Booking ID..."
        emptyMessage="No reservations found. Go ahead and book a room!"
      />
    </div>
  );
};
export default MyBookings;
