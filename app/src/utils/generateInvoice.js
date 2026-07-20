// app/src/utils/generateInvoice.js
//
// Shared invoice generator used by both StaffDashboard and GuestDashboard.
// Import and call: generateInvoice(booking)
//
// Required fields in booking object:
//   booking_id, guest_name, guest_email, guest_phone,
//   room_id, room_type, checkin, checkout, guests,
//   total_price, payment_status, transaction_id, payment_date

import { jsPDF } from 'jspdf';
import toast from 'react-hot-toast';

export const generateInvoice = (booking) => {
  try {
    const doc = new jsPDF({ orientation: 'portrait', unit: 'mm', format: 'a4' });

    const PW     = 210;   // page width
    const M      = 16;    // left/right margin
    const RIGHT  = PW - M;
    const COL2   = 115;   // x-start of right column

    // ─── helpers ────────────────────────────────────────────
    const label = (text, x, y) => {
      doc.setFont('Helvetica', 'normal');
      doc.setFontSize(7);
      doc.setTextColor(160, 160, 160);
      doc.text(text, x, y);
    };

    const value = (text, x, y, opts = {}) => {
      doc.setFont('Helvetica', opts.bold === false ? 'normal' : 'bold');
      doc.setFontSize(opts.size || 9);
      doc.setTextColor(...(opts.color || [20, 20, 20]));
      doc.text(String(text ?? '—'), x, y, opts.align ? { align: opts.align } : undefined);
    };

    const rule = (y, color = [230, 230, 230]) => {
      doc.setDrawColor(...color);
      doc.setLineWidth(0.25);
      doc.line(M, y, RIGHT, y);
    };

    const fmtDate = (d) =>
      new Date(d).toLocaleDateString('en-US', { day: 'numeric', month: 'short', year: 'numeric' });

    const fmtMoney = (n) =>
      Number(n).toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

    // ─── calculated values ───────────────────────────────────
    const checkinDate  = new Date(booking.checkin);
    const checkoutDate = new Date(booking.checkout);
    const nights       = Math.max(1, Math.round((checkoutDate - checkinDate) / 86400000));
    const basePrice    = Number(booking.total_price);
    const nightlyRate  = basePrice / nights;
    const svcCharge    = basePrice * 0.10;
    const vat          = basePrice * 0.13;
    const grandTotal   = basePrice + svcCharge + vat;
    const isPaid       = booking.payment_status === 'paid';

    const invoiceNo    = `INV-${String(booking.booking_id).padStart(5, '0')}`;
    const bookingRef   = `#BK-${String(booking.booking_id).padStart(4, '0')}`;
    const issuedDate   = new Date().toLocaleDateString('en-US', { day: 'numeric', month: 'short', year: 'numeric' });
    const paymentDate  = booking.payment_date
      ? fmtDate(booking.payment_date)
      : (isPaid ? issuedDate : '—');

    // ════════════════════════════════════════════════════════
    // 1. DARK HEADER BAND
    // ════════════════════════════════════════════════════════
    doc.setFillColor(26, 26, 26);
    doc.rect(0, 0, PW, 44, 'F');

    // Left — hotel identity
    doc.setFont('Helvetica', 'bold');
    doc.setFontSize(20);
    doc.setTextColor(255, 255, 255);
    doc.text('HRMS', M, 17);

    doc.setFont('Helvetica', 'normal');
    doc.setFontSize(7.5);
    doc.setTextColor(160, 160, 160);
    doc.text('Hotel Room Management System', M, 23);
    doc.text('Lainchaur, Kathmandu 44600, Nepal', M, 28);
    doc.text('+977 9803040024  |  info@hrms.com.np', M, 33);

    // Right — invoice number + status
    doc.setFont('Helvetica', 'normal');
    doc.setFontSize(7);
    doc.setTextColor(160, 160, 160);
    doc.text('INVOICE', RIGHT, 16, { align: 'right' });

    doc.setFont('Helvetica', 'bold');
    doc.setFontSize(12);
    doc.setTextColor(255, 255, 255);
    doc.text(`#${invoiceNo}`, RIGHT, 23, { align: 'right' });

    doc.setFont('Helvetica', 'normal');
    doc.setFontSize(7.5);
    doc.setTextColor(150, 150, 150);
    doc.text(`Issued: ${issuedDate}`, RIGHT, 29, { align: 'right' });

    // Status badge
    if (isPaid) {
      doc.setFillColor(45, 106, 79);
      doc.roundedRect(RIGHT - 22, 32, 22, 7, 1, 1, 'F');
      doc.setFont('Helvetica', 'bold');
      doc.setFontSize(6.5);
      doc.setTextColor(216, 243, 220);
      doc.text('SETTLED', RIGHT - 11, 37, { align: 'center' });
    } else {
      doc.setFillColor(120, 60, 20);
      doc.roundedRect(RIGHT - 22, 32, 22, 7, 1, 1, 'F');
      doc.setFont('Helvetica', 'bold');
      doc.setFontSize(6.5);
      doc.setTextColor(255, 220, 180);
      doc.text('PENDING', RIGHT - 11, 37, { align: 'center' });
    }

    // ════════════════════════════════════════════════════════
    // 2. BILLED TO  |  BOOKING DETAILS  (2-col grid)
    // ════════════════════════════════════════════════════════
    let y = 54;

    // Left — guest info
    label('BILLED TO', M, y);
    value(booking.guest_name || '—', M, y + 7, { size: 12 });
    doc.setFont('Helvetica', 'normal');
    doc.setFontSize(8.5);
    doc.setTextColor(100, 100, 100);
    doc.text(booking.guest_email || '—', M, y + 14);
    doc.text(booking.guest_phone || '—', M, y + 20);

    // Right — booking ref
    label('BOOKING REFERENCE', COL2, y);
    value(bookingRef, COL2, y + 7, { size: 11 });

    // Room assigned (below booking ref)
    label('ROOM ASSIGNED', COL2, y + 16);
    value(`${booking.room_type} Room — #${booking.room_id}`, COL2, y + 23, { size: 9 });
    doc.setFont('Helvetica', 'normal');
    doc.setFontSize(8);
    doc.setTextColor(130, 130, 130);
    doc.text(`${booking.guests} Guest${booking.guests > 1 ? 's' : ''}`, COL2, y + 29);

    y += 38;
    rule(y);

    // ════════════════════════════════════════════════════════
    // 3. STAY PERIOD — 3 columns
    // ════════════════════════════════════════════════════════
    y += 9;
    const stayItems = [
      { lbl: 'CHECK-IN',  val: fmtDate(booking.checkin)  },
      { lbl: 'CHECK-OUT', val: fmtDate(booking.checkout) },
      { lbl: 'DURATION',  val: `${nights} Night${nights !== 1 ? 's' : ''}` },
    ];
    stayItems.forEach(({ lbl, val }, i) => {
      const x = M + i * 60;
      label(lbl, x, y);
      value(val, x, y + 7, { size: 9 });
    });

    y += 20;
    rule(y);

    // ════════════════════════════════════════════════════════
    // 4. CHARGES TABLE
    // ════════════════════════════════════════════════════════
    y += 8;
    label('CHARGES', M, y);
    y += 6;

    // Table header row
    doc.setFont('Helvetica', 'normal');
    doc.setFontSize(7.5);
    doc.setTextColor(170, 170, 170);
    doc.text('Description',    M,       y);
    doc.text('Qty',            118,     y, { align: 'center' });
    doc.text('Rate',           152,     y, { align: 'right'  });
    doc.text('Amount',         RIGHT,   y, { align: 'right'  });

    y += 3;
    rule(y, [220, 220, 220]);

    // Row helper
    const tableRow = (desc, qty, rate, amount, isBold = false) => {
      y += 9;
      doc.setFont('Helvetica', 'normal');
      doc.setFontSize(8.5);
      doc.setTextColor(60, 60, 60);
      doc.text(desc,   M,     y);
      doc.setTextColor(100, 100, 100);
      doc.text(qty,    118,   y, { align: 'center' });
      doc.text(rate,   152,   y, { align: 'right'  });
      doc.setFont('Helvetica', isBold ? 'bold' : 'normal');
      doc.setTextColor(20, 20, 20);
      doc.text(amount, RIGHT, y, { align: 'right'  });
      doc.setDrawColor(242, 242, 242);
      doc.setLineWidth(0.2);
      doc.line(M, y + 2.5, RIGHT, y + 2.5);
    };

    tableRow(
      `${booking.room_type} Room — nightly accommodation`,
      `${nights} night${nights !== 1 ? 's' : ''}`,
      `Rs. ${fmtMoney(nightlyRate)}`,
      `Rs. ${fmtMoney(basePrice)}`
    );
    tableRow('Service charge (10%)', '—', '', `Rs. ${fmtMoney(svcCharge)}`);
    tableRow('VAT (13%)',            '—', '', `Rs. ${fmtMoney(vat)}`);

    // ════════════════════════════════════════════════════════
    // 5. TOTAL ROW
    // ════════════════════════════════════════════════════════
    y += 12;
    doc.setFillColor(250, 250, 248);
    doc.rect(M, y - 4, RIGHT - M, 14, 'F');
    doc.setFont('Helvetica', 'normal');
    doc.setFontSize(8.5);
    doc.setTextColor(130, 130, 130);
    doc.text('Total amount due', M + 3, y + 4);
    doc.setFont('Helvetica', 'bold');
    doc.setFontSize(15);
    doc.setTextColor(20, 20, 20);
    doc.text(`Rs. ${fmtMoney(grandTotal)}`, RIGHT, y + 4, { align: 'right' });

    // ════════════════════════════════════════════════════════
    // 6. PAYMENT INFORMATION
    // ════════════════════════════════════════════════════════
    y += 20;
    rule(y);
    y += 8;
    label('PAYMENT INFORMATION', M, y);
    y += 7;

    const payRows = [
      ['Method',          'Khalti (Online)'],
      ['Transaction ID',  booking.transaction_id || 'N/A'],
      ['Payment date',    paymentDate],
      ['Payment status',  isPaid ? 'Paid in full' : booking.payment_status],
    ];

    payRows.forEach(([lbl, val]) => {
      doc.setFont('Helvetica', 'normal');
      doc.setFontSize(8);
      doc.setTextColor(140, 140, 140);
      doc.text(lbl, M, y);

      const isStatus = lbl === 'Payment status';
      const isTxId   = lbl === 'Transaction ID';
      doc.setFont(isTxId ? 'Courier' : 'Helvetica', 'normal');
      doc.setFontSize(isTxId ? 7.5 : 8);
      doc.setTextColor(
        isStatus && isPaid ? 45  : 40,
        isStatus && isPaid ? 106 : 40,
        isStatus && isPaid ? 79  : 40
      );
      doc.text(val, M + 45, y);
      y += 7;
    });

    // ════════════════════════════════════════════════════════
    // 7. FOOTER NOTE
    // ════════════════════════════════════════════════════════
    y += 4;
    rule(y);
    y += 7;
    label('NOTE', M, y);
    y += 6;
    doc.setFont('Helvetica', 'normal');
    doc.setFontSize(7.5);
    doc.setTextColor(180, 180, 180);
    const note =
      'Thank you for staying at HRMS. This is a computer-generated invoice and does not require a signature. ' +
      'For queries, contact our front desk or email billing@hrms.com.np.';
    const noteLines = doc.splitTextToSize(note, RIGHT - M);
    doc.text(noteLines, M, y);

    // ── Save ─────────────────────────────────────────────────
    doc.save(`Invoice_HRMS_${booking.booking_id}.pdf`);
    toast.success('Invoice downloaded.');

  } catch (err) {
    console.error('Invoice error:', err);
    toast.error('Failed to generate invoice.');
  }
};