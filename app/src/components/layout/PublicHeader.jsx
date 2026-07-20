import { Hotel, ArrowLeft, LogIn } from 'lucide-react';

export const PublicHeader = () => {
  return (
    <header className="public-header">
      <div className="public-header__inner">
        <a href="/version2/homepage.php" className="public-header__brand">
          <Hotel size={22} />
          <span>HRMS</span>
        </a>

        <nav className="public-header__nav">
          <a href="/version2/homepage.php" className="btn btn-secondary" style={{ fontSize: '0.82rem', padding: '7px 14px' }}>
            <ArrowLeft size={14} />
            <span>Back to Home</span>
          </a>
          <a 
            href={`/version2/auth/login.php?redirect=${encodeURIComponent(window.location.pathname)}`}
            className="btn btn-primary" 
            style={{ fontSize: '0.82rem', padding: '7px 14px' }}
          >
            <LogIn size={14} />
            <span>Login</span>
          </a>
        </nav>
      </div>

      <style>{`
        .public-header {
          position: sticky;
          top: 0;
          z-index: 100;
          background: rgba(255, 255, 255, 0.85);
          backdrop-filter: blur(16px);
          -webkit-backdrop-filter: blur(16px);
          border-bottom: 1px solid var(--border-color, #e2e8f0);
          padding: 0 24px;
        }

        .public-header__inner {
          max-width: 1200px;
          margin: 0 auto;
          display: flex;
          align-items: center;
          justify-content: space-between;
          height: 60px;
        }

        .public-header__brand {
          display: flex;
          align-items: center;
          gap: 10px;
          text-decoration: none;
          color: var(--primary, #5c2d91);
          font-weight: 800;
          font-size: 1.15rem;
          letter-spacing: -0.02em;
        }

        .public-header__nav {
          display: flex;
          align-items: center;
          gap: 10px;
        }

        @media (max-width: 480px) {
          .public-header {
            padding: 0 12px;
          }
          .public-header__brand span {
            display: none;
          }
        }
      `}</style>
    </header>
  );
};

export default PublicHeader;
