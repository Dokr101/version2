import { createContext, useContext, useState, useEffect } from 'react';
import axios from 'axios';

axios.interceptors.response.use(
  res => res,
  err => {
    const url = err.config?.url ?? '';
    if (err.response?.status === 401
        && !url.includes('session.php')) {
      window.location.href = '/version2/auth/login.php?redirect=/version2/app/';
    }
    return Promise.reject(err);
  }
);

const AuthContext = createContext(null);

export const AuthProvider = ({ children }) => {
  const [user, setUser] = useState(null);
  const [loading, setLoading] = useState(true);

  const checkSession = async () => {
    try {
      // Fetch session details from the PHP session endpoint
      const response = await axios.get('/version2/api/auth/session.php');
      if (response.data.isLoggedIn) {
        setUser(response.data.user);
      } else {
        setUser(null);
      }
    } catch (error) {
      console.error('Session check failed:', error);
      setUser(null);
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    checkSession();
  }, []);

  const logout = async () => {
    try {
      setLoading(true);
      // Redirect to the existing PHP logout script which clears the session perfectly
      window.location.href = '/version2/auth/logout.php';
    } catch (error) {
      console.error('Logout failed:', error);
    }
  };

  return (
    <AuthContext.Provider value={{ user, loading, logout, reloadSession: checkSession }}>
      {children}
    </AuthContext.Provider>
  );
};

export const useAuth = () => {
  const context = useContext(AuthContext);
  if (!context) {
    throw new Error('useAuth must be used within an AuthProvider');
  }
  return context;
};
