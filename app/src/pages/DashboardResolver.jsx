import { useEffect } from 'react';
import { useNavigate } from 'react-router-dom';
import { useAuth } from '../hooks/useAuth';

export const DashboardResolver = () => {
  const { user, loading } = useAuth();
  const navigate = useNavigate();

  useEffect(() => {
    if (!loading) {
      if (user) {
        if (user.role === 'admin') {
          navigate('/admin/dashboard', { replace: true });
        } else if (user.role === 'staff') {
          navigate('/staff/dashboard', { replace: true });
        } else {
          navigate('/guest/dashboard', { replace: true });
        }
      } else {
        window.location.href = '/version2/auth/login.php?redirect=/version2/app/';
      }
    }
  }, [user, loading, navigate]);

  return (
    <div style={{ display: 'flex', flexDirection: 'column', alignItems: 'center', justifyContent: 'center', height: '60vh', gap: '16px' }}>
      <div className="spinner"></div>
      <p style={{ color: 'var(--text-secondary)', fontWeight: 500 }}>Redirecting to portal...</p>
    </div>
  );
};
export default DashboardResolver;
