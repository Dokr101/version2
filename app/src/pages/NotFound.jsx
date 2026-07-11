import { useNavigate } from 'react-router-dom';
import { Home, ArrowLeft } from 'lucide-react';

export const NotFound = () => {
  const navigate = useNavigate();

  return (
    <div className="not-found-page">
      <div className="not-found-card">
        <div className="not-found-code">404</div>
        <h2>Page Not Found</h2>
        <p>The page you're looking for doesn't exist or has been moved.</p>
        <div style={{ display: 'flex', gap: '12px', justifyContent: 'center', marginTop: '24px' }}>
          <button onClick={() => navigate(-1)} className="btn btn-secondary">
            <ArrowLeft size={15} />
            <span>Go Back</span>
          </button>
          <button onClick={() => navigate('/')} className="btn btn-primary">
            <Home size={15} />
            <span>Dashboard</span>
          </button>
        </div>
      </div>

      <style>{`
        .not-found-page {
          display: flex;
          align-items: center;
          justify-content: center;
          min-height: 80vh;
          padding: 24px;
        }

        .not-found-card {
          text-align: center;
          max-width: 420px;
        }

        .not-found-code {
          font-size: 6rem;
          font-weight: 900;
          background: linear-gradient(135deg, var(--primary), var(--primary-light));
          -webkit-background-clip: text;
          -webkit-text-fill-color: transparent;
          background-clip: text;
          line-height: 1;
          margin-bottom: 12px;
          letter-spacing: -0.04em;
        }

        .not-found-card h2 {
          font-size: 1.4rem;
          color: var(--text-primary);
          margin-bottom: 8px;
          font-weight: 700;
        }

        .not-found-card p {
          color: var(--text-secondary);
          font-size: 0.92rem;
          line-height: 1.5;
        }
      `}</style>
    </div>
  );
};

export default NotFound;
