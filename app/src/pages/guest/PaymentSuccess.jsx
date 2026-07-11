import { useState, useEffect } from 'react';
import { Link, useSearchParams } from 'react-router-dom';
import axios from 'axios';
import { CheckCircle2, ShieldCheck, Home, ArrowRight } from 'lucide-react';

export const PaymentSuccess = () => {
  const [searchParams] = useSearchParams();
  const bookingId = searchParams.get('booking_id') || '';
  
  const [booking, setBooking] = useState(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    const fetchBookingDetails = async () => {
      if (!bookingId) {
        setLoading(false);
        return;
      }
      try {
        const response = await axios.get(`/version2/api/bookings/list.php?booking_id=${bookingId}`);
        const match = response.data.find(b => b.booking_id === parseInt(bookingId));
        setBooking(match || null);
      } catch (error) {
        console.error('Error fetching booking details:', error);
      } finally {
        setLoading(false);
      }
    };
    fetchBookingDetails();
  }, [bookingId]);

  return (
    <div className="page-center animate-fade-in">
      <div className="status-card glass-card success-border">
        <div className="status-icon-box success-bg">
          <CheckCircle2 size={36} className="success-color" />
        </div>
        
        <h2>Payment Confirmed</h2>
        <p className="status-desc">
          Your booking has been verified and your room is successfully reserved.
        </p>

        {loading ? (
          <div className="dt-loading">
            <div className="spinner"></div>
            <span>Fetching booking invoice...</span>
          </div>
        ) : booking ? (
          <div className="summary-box">
            <div className="summary-hdr">
              <span>INVOICE DETAILS</span>
              <strong>#{booking.booking_id}</strong>
            </div>
            
            <div className="summary-rows">
              <div className="summary-row">
                <span>Room Type</span>
                <span>{booking.room_type} Room</span>
              </div>
              <div className="summary-row">
                <span>Check-In</span>
                <span>{new Date(booking.checkin).toLocaleDateString('en-US', { dateStyle: 'medium' })}</span>
              </div>
              <div className="summary-row">
                <span>Check-Out</span>
                <span>{new Date(booking.checkout).toLocaleDateString('en-US', { dateStyle: 'medium' })}</span>
              </div>
              <div className="summary-row">
                <span>Guests</span>
                <span>{booking.guests}</span>
              </div>
              <div className="summary-divider"></div>
              <div className="summary-row summary-total">
                <span>Amount Paid</span>
                <span>Rs. {booking.total_price.toLocaleString('en-IN')}</span>
              </div>
            </div>
          </div>
        ) : (
          <p className="no-invoice-note">
            Reservation details could not be retrieved, but your payment was processed successfully.
          </p>
        )}

        <div className="gateway-note">
          <ShieldCheck size={14} />
          <span>Secured via Khalti Payment Gateway</span>
        </div>

        <div className="status-actions">
          <Link to="/guest/dashboard" className="btn btn-secondary flex-1">
            <Home size={14} />
            <span>Dashboard</span>
          </Link>
          <Link to="/guest/bookings" className="btn btn-primary flex-1">
            <span>My Bookings</span>
            <ArrowRight size={14} />
          </Link>
        </div>
      </div>

      <style>{`
        .page-center {
          display: flex;
          align-items: center;
          justify-content: center;
          min-height: 60vh;
        }

        .status-card {
          width: 100%;
          max-width: 440px;
          display: flex;
          flex-direction: column;
          align-items: center;
          text-align: center;
          padding: 32px !important;
        }

        .success-border {
          border-color: rgba(16, 185, 129, 0.2) !important;
        }

        .status-icon-box {
          width: 64px;
          height: 64px;
          border-radius: var(--border-radius-md);
          display: flex;
          align-items: center;
          justify-content: center;
          margin-bottom: 20px;
        }

        .success-bg {
          background: var(--success-glow);
          border: 1px solid rgba(16, 185, 129, 0.15);
        }

        .success-color {
          color: var(--success);
        }

        .status-desc {
          color: var(--text-secondary);
          font-size: 0.82rem;
          margin-top: 6px;
          margin-bottom: 24px;
          line-height: 1.5;
        }

        .summary-box {
          width: 100%;
          background: rgba(255, 255, 255, 0.015);
          border: 1px solid var(--border-color);
          border-radius: var(--border-radius-sm);
          padding: 16px;
          margin-bottom: 20px;
          text-align: left;
        }

        .summary-hdr {
          display: flex;
          justify-content: space-between;
          font-size: 0.7rem;
          color: var(--text-muted);
          margin-bottom: 12px;
          letter-spacing: 0.05em;
        }

        .summary-rows {
          display: flex;
          flex-direction: column;
          gap: 8px;
          font-size: 0.8rem;
        }

        .summary-row {
          display: flex;
          justify-content: space-between;
          color: var(--text-secondary);
        }

        .summary-divider {
          height: 1px;
          background: var(--border-color);
          margin: 4px 0;
        }

        .summary-total {
          font-weight: 700;
          color: var(--text-primary);
        }

        .no-invoice-note {
          color: var(--text-muted);
          font-size: 0.8rem;
          margin-bottom: 20px;
        }

        .gateway-note {
          display: inline-flex;
          align-items: center;
          gap: 6px;
          font-size: 0.7rem;
          color: var(--text-muted);
          margin-bottom: 24px;
        }

        .status-actions {
          display: flex;
          gap: 10px;
          width: 100%;
        }

        .flex-1 {
          flex: 1;
        }
      `}</style>
    </div>
  );
};
export default PaymentSuccess;
