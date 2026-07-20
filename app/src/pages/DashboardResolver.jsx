import { useEffect } from 'react';
import { useNavigate } from 'react-router-dom';
import { useAuth } from '../hooks/useAuth';

export const DashboardResolver = () => {
  const { user, loading } = useAuth();
  const navigate = useNavigate();

  useEffect(() => {
    if (loading) return;

    if (user) {
      switch (user.role) {
        case 'admin':
          navigate('/admin/dashboard', { replace: true });
          break;
        case 'staff':
          navigate('/staff/dashboard', { replace: true });
          break;
        default:
          navigate('/guest/dashboard', { replace: true });
      }
    } else {
      const redirect = encodeURIComponent('/version2/app/');
      window.location.href = `/version2/auth/login.php?redirect=${redirect}`;
    }
  }, [loading, user, navigate]);

  return (
    <div style={{ padding: 40 }}>
      Redirecting...
    </div>
  );
};

export default DashboardResolver;