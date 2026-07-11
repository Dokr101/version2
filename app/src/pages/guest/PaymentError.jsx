import { Link, useSearchParams } from 'react-router-dom';
import { AlertTriangle, Home, RefreshCw } from 'lucide-react';

export const PaymentError = () => {
  const [searchParams] = useSearchParams();
  const bookingId = searchParams.get('booking_id') || '';
  const errorMessage = searchParams.get('error') || 'The transaction was cancelled or declined by the payment provider.';

  return (
    <div className="page-center animate-fade-in">
      <div className="status-card glass-card danger-border">
        <div className="status-icon-box danger-bg">
          <AlertTriangle size={36} className="danger-color" />
        </div>

        <h2>Payment Unsuccessful</h2>
        <p className="status-desc">{errorMessage}</p>

        <div className="status-actions">
          <Link to="/guest/bookings" className="btn btn-secondary flex-1">
            <Home size={14} />
            <span>My Bookings</span>
          </Link>
          
          {bookingId && (
            <a 
              href={`/version2/guest/initiate_khalti_payment.php?booking_id=${bookingId}`} 
              className="btn btn-primary flex-1"
            >
              <RefreshCw size={14} />
              <span>Retry Payment</span>
            </a>
          )}
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

        .danger-border {
          border-color: rgba(239, 68, 68, 0.2) !important;
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

        .danger-bg {
          background: var(--danger-glow);
          border: 1px solid rgba(239, 68, 68, 0.15);
        }

        .danger-color {
          color: var(--danger);
        }

        .status-desc {
          color: var(--text-secondary);
          font-size: 0.82rem;
          margin-top: 6px;
          margin-bottom: 28px;
          line-height: 1.5;
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
export default PaymentError;
