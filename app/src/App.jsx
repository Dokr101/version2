import { Routes, Route, Navigate, useLocation } from 'react-router-dom';
import { Toaster } from 'react-hot-toast';
import { AuthProvider, useAuth } from './hooks/useAuth';

// Layout
import { DashboardLayout } from './components/layout/DashboardLayout';

// Core & Resolver
import { DashboardResolver } from './pages/DashboardResolver';

// Guest Pages
import { GuestDashboard } from './pages/guest/GuestDashboard';
import { BrowseRooms } from './pages/guest/BrowseRooms';
import { MyBookings } from './pages/guest/MyBookings';
import { Profile } from './pages/guest/Profile';
import { PaymentSuccess } from './pages/guest/PaymentSuccess';
import { PaymentError } from './pages/guest/PaymentError';

// Admin Pages
import { AdminDashboard } from './pages/admin/AdminDashboard';
import { ManageStaff } from './pages/admin/ManageStaff';
import { ManageRooms } from './pages/admin/ManageRooms';
import { AllBookings } from './pages/admin/AllBookings';
import { PaymentRecords } from './pages/admin/PaymentRecords';
import { Reports } from './pages/admin/Reports';

// Staff Pages
import { StaffDashboard } from './pages/staff/StaffDashboard';

// Fallback
import { NotFound } from './pages/NotFound';

// Secure Route Guard
const ProtectedRoute = ({ allowedRoles, children }) => {
  const { user, loading } = useAuth();
  const location = useLocation();

  if (!loading && !user) {
    const redirectPath = `/version2/app${location.pathname}${location.search}`;
    window.location.href = `/version2/auth/login.php?redirect=${encodeURIComponent(redirectPath)}`;
    return null;
  }

  if (loading) {
    return (
      <div style={{ display: 'flex', flexDirection: 'column', alignItems: 'center', justifyContent: 'center', height: '80vh', gap: '16px' }}>
        <div className="spinner"></div>
        <p style={{ color: 'var(--text-secondary)', fontWeight: 500 }}>Authorizing portal access...</p>
      </div>
    );
  }

  if (allowedRoles && !allowedRoles.includes(user.role)) {
    return <Navigate to="/" replace />;
  }

  return <DashboardLayout>{children}</DashboardLayout>;
};

function App() {
  return (
    <AuthProvider>
      <Toaster 
        position="top-right"
        toastOptions={{
          style: {
            background: '#ffffff',
            color: 'var(--text-primary)',
            border: '1px solid var(--border-color)',
            fontSize: '0.9rem',
            borderRadius: '10px',
            padding: '12px 18px',
          },
        }}
      />
      <Routes>
        {/* Core redirector */}
        <Route path="/" element={<DashboardResolver />} />

        {/* Guest Protected Routes */}
        <Route path="/guest/dashboard" element={
          <ProtectedRoute allowedRoles={['guest']}>
            <GuestDashboard />
          </ProtectedRoute>
        } />
        <Route path="/guest/rooms" element={<BrowseRooms />} />
        <Route path="/guest/bookings" element={
          <ProtectedRoute allowedRoles={['guest']}>
            <MyBookings />
          </ProtectedRoute>
        } />
        <Route path="/guest/profile" element={
          <ProtectedRoute allowedRoles={['guest', 'admin', 'staff']}>
            <Profile />
          </ProtectedRoute>
        } />
        <Route path="/guest/payment-success" element={
          <ProtectedRoute allowedRoles={['guest']}>
            <PaymentSuccess />
          </ProtectedRoute>
        } />
        <Route path="/guest/payment-error" element={
          <ProtectedRoute allowedRoles={['guest']}>
            <PaymentError />
          </ProtectedRoute>
        } />

        {/* Admin Protected Routes */}
        <Route path="/admin/dashboard" element={
          <ProtectedRoute allowedRoles={['admin']}>
            <AdminDashboard />
          </ProtectedRoute>
        } />
        <Route path="/admin/staff" element={
          <ProtectedRoute allowedRoles={['admin']}>
            <ManageStaff />
          </ProtectedRoute>
        } />
        <Route path="/admin/rooms" element={
          <ProtectedRoute allowedRoles={['admin']}>
            <ManageRooms />
          </ProtectedRoute>
        } />
        <Route path="/admin/bookings" element={
          <ProtectedRoute allowedRoles={['admin']}>
            <AllBookings />
          </ProtectedRoute>
        } />
        <Route path="/admin/payments" element={
          <ProtectedRoute allowedRoles={['admin']}>
            <PaymentRecords />
          </ProtectedRoute>
        } />
        <Route path="/admin/reports" element={
          <ProtectedRoute allowedRoles={['admin']}>
            <Reports />
          </ProtectedRoute>
        } />

        {/* Staff Protected Routes */}
        <Route path="/staff/dashboard" element={
          <ProtectedRoute allowedRoles={['staff']}>
            <StaffDashboard />
          </ProtectedRoute>
        } />
        <Route path="/staff/rooms" element={
          <ProtectedRoute allowedRoles={['staff']}>
            <ManageRooms />
          </ProtectedRoute>
        } />
        <Route path="/staff/checkin" element={
          <ProtectedRoute allowedRoles={['staff']}>
            <StaffDashboard />
          </ProtectedRoute>
        } />
        <Route path="/staff/checkout" element={
          <ProtectedRoute allowedRoles={['staff']}>
            <StaffDashboard />
          </ProtectedRoute>
        } />
        <Route path="/staff/reservations" element={
          <ProtectedRoute allowedRoles={['staff']}>
            <AllBookings />
          </ProtectedRoute>
        } />

        {/* Fallback */}
        <Route path="*" element={<NotFound />} />
      </Routes>
    </AuthProvider>
  );
}

export default App;
