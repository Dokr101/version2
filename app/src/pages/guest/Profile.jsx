import { useState, useEffect } from 'react';
import axios from 'axios';
import toast from 'react-hot-toast';
import { useAuth } from '../../hooks/useAuth';
import { User, Mail, Phone, Lock, Save } from 'lucide-react';

export const Profile = () => {
  const { user, reloadSession } = useAuth();
  
  const [name, setName] = useState('');
  const [email, setEmail] = useState('');
  const [phone, setPhone] = useState('');
  const [isSaving, setIsSaving] = useState(false);

  // Password change state
  const [currentPassword, setCurrentPassword] = useState('');
  const [newPassword, setNewPassword] = useState('');
  const [confirmNewPassword, setConfirmNewPassword] = useState('');
  const [isChangingPassword, setIsChangingPassword] = useState(false);

  useEffect(() => {
    if (user) {
      setName(user.name || '');
      setEmail(user.email || '');
      setPhone(user.phone || '');
    }
  }, [user]);

  const handleSave = async (e) => {
    e.preventDefault();
    try {
      setIsSaving(true);
      const response = await axios.post('/version2/api/users/profile.php', {
        name,
        email,
        phone
      });
      if (response.data.success) {
        toast.success(response.data.message || 'Profile updated!');
        reloadSession();
      }
    } catch (error) {
      toast.error(error.response?.data?.error || 'Failed to update profile.');
    } finally {
      setIsSaving(false);
    }
  };

  const handleChangePassword = async (e) => {
    e.preventDefault();
    if (newPassword !== confirmNewPassword) {
      toast.error('New passwords do not match.');
      return;
    }
    try {
      setIsChangingPassword(true);
      const response = await axios.post('/version2/api/auth/change_password.php', {
        current_password: currentPassword,
        new_password: newPassword
      });
      if (response.data.success) {
        toast.success(response.data.message || 'Password changed!');
        setCurrentPassword('');
        setNewPassword('');
        setConfirmNewPassword('');
      }
    } catch (error) {
      toast.error(error.response?.data?.error || 'Failed to change password.');
    } finally {
      setIsChangingPassword(false);
    }
  };

  return (
    <div className="page animate-fade-in">
      <div className="profile-grid">
        {/* Profile Card Summary */}
        <div className="summary-card glass-card">
          <div className="profile-avatar-large">
            {user?.name?.charAt(0).toUpperCase()}
          </div>
          <h3 className="profile-name-large">{user?.name}</h3>
          <span className="profile-role-badge">{user?.role}</span>
          
          <div className="profile-meta-details">
            <div className="meta-row">
              <span className="meta-label">Username:</span>
              <span className="meta-val">@{user?.username}</span>
            </div>
            <div className="meta-row">
              <span className="meta-label">Email Address:</span>
              <span className="meta-val">{user?.email}</span>
            </div>
            <div className="meta-row">
              <span className="meta-label">Phone:</span>
              <span className="meta-val">{user?.phone || 'Not set'}</span>
            </div>
          </div>
        </div>

        {/* Profile Edit Form */}
        <div className="form-card glass-card">
          <form onSubmit={handleSave} className="profile-edit-form">
            <h3 className="form-section-title">Update Profile Info</h3>
            
            <div className="form-group">
              <label className="form-label">
                <User size={13} />
                <span>Full Name</span>
              </label>
              <input 
                type="text" 
                value={name} 
                onChange={(e) => setName(e.target.value)} 
                className="form-input" 
                required 
              />
            </div>

            <div className="form-group">
              <label className="form-label">
                <Mail size={13} />
                <span>Email Address</span>
              </label>
              <input 
                type="email" 
                value={email} 
                onChange={(e) => setEmail(e.target.value)} 
                className="form-input" 
                required 
              />
            </div>

            <div className="form-group">
              <label className="form-label">
                <Phone size={13} />
                <span>Phone Number</span>
              </label>
              <input 
                type="text" 
                value={phone} 
                onChange={(e) => setPhone(e.target.value)} 
                className="form-input" 
                required 
              />
            </div>

            <button type="submit" disabled={isSaving} className="btn btn-primary w-full">
              <Save size={15} />
              <span>{isSaving ? 'Saving...' : 'Save Profile'}</span>
            </button>
          </form>
        </div>

        {/* Change Password Form */}
        <div className="form-card glass-card" style={{ gridColumn: '1 / -1' }}>
          <form onSubmit={handleChangePassword} className="profile-edit-form">
            <h3 className="form-section-title">Change Password</h3>
            <p style={{ fontSize: '0.75rem', color: 'var(--text-muted)', marginBottom: '14px' }}>
              Enter your current password to set a new one.
            </p>

            <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(200px, 1fr))', gap: '16px' }}>
              <div className="form-group">
                <label className="form-label">
                  <Lock size={13} />
                  <span>Current Password</span>
                </label>
                <input 
                  type="password" 
                  value={currentPassword} 
                  onChange={(e) => setCurrentPassword(e.target.value)} 
                  placeholder="••••••••" 
                  className="form-input" 
                  required 
                />
              </div>

              <div className="form-group">
                <label className="form-label">
                  <Lock size={13} />
                  <span>New Password</span>
                </label>
                <input 
                  type="password" 
                  value={newPassword} 
                  onChange={(e) => setNewPassword(e.target.value)} 
                  placeholder="Min 6 chars, 1 number, 1 symbol" 
                  className="form-input" 
                  required 
                  minLength={6}
                />
              </div>

              <div className="form-group">
                <label className="form-label">
                  <Lock size={13} />
                  <span>Confirm New Password</span>
                </label>
                <input 
                  type="password" 
                  value={confirmNewPassword} 
                  onChange={(e) => setConfirmNewPassword(e.target.value)} 
                  placeholder="••••••••" 
                  className="form-input" 
                  required 
                />
              </div>
            </div>

            <button type="submit" disabled={isChangingPassword} className="btn btn-primary" style={{ marginTop: '16px' }}>
              <Lock size={15} />
              <span>{isChangingPassword ? 'Updating...' : 'Update Password'}</span>
            </button>
          </form>
        </div>
      </div>

      <style>{`
        .profile-grid {
          display: grid;
          grid-template-columns: 1fr 2fr;
          gap: 24px;
          align-items: start;
        }

        @media (max-width: 768px) {
          .profile-grid {
            grid-template-columns: 1fr;
          }
        }

        .summary-card {
          display: flex;
          flex-direction: column;
          align-items: center;
          padding: 24px !important;
          text-align: center;
        }

        .profile-avatar-large {
          width: 72px;
          height: 72px;
          background: linear-gradient(135deg, var(--primary), var(--primary-light));
          border-radius: var(--border-radius-md);
          display: flex;
          align-items: center;
          justify-content: center;
          color: white;
          font-weight: 800;
          font-size: 2rem;
          margin-bottom: 16px;
        }

        .profile-name-large {
          font-size: 1.15rem;
          color: var(--text-primary);
          margin-bottom: 4px;
        }

        .profile-role-badge {
          font-size: 0.65rem;
          font-weight: 700;
          color: var(--primary-light);
          background: var(--primary-glow);
          padding: 3px 10px;
          border-radius: 12px;
          margin-bottom: 20px;
          letter-spacing: 0.05em;
          text-transform: uppercase;
        }

        .profile-meta-details {
          width: 100%;
          border-top: 1px solid var(--border-color);
          padding-top: 16px;
          display: flex;
          flex-direction: column;
          gap: 10px;
          text-align: left;
        }

        .meta-row {
          display: flex;
          justify-content: space-between;
          font-size: 0.8rem;
        }

        .meta-label {
          color: var(--text-primary);
          font-weight: 700;
        }

        .meta-val {
          color: var(--text-primary);
          font-weight: 700;
        }

        .form-section-title {
          font-size: 1rem;
          color: var(--text-primary);
          margin-bottom: 16px;
          padding-bottom: 6px;
          border-bottom: 1px solid var(--border-color);
          font-weight: 800;
        }

        .w-full {
          width: 100%;
        }
      `}</style>
    </div>
  );
};
export default Profile;
