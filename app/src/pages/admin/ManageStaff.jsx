import { useState, useEffect } from 'react';
import axios from 'axios';
import toast from 'react-hot-toast';
import { DataTable } from '../../components/ui/DataTable';
import { 
  Users, UserPlus, Check, Trash2, Mail, Phone, ShieldCheck, Clock, X, User, Lock
} from 'lucide-react';
import { Modal } from '../../components/ui/Modal';

export const ManageStaff = () => {
  const [activeStaff, setActiveStaff] = useState([]);
  const [pendingStaff, setPendingStaff] = useState([]);
  const [loading, setLoading] = useState(true);
  const [activeTab, setActiveTab] = useState('active');
  const [showAddModal, setShowAddModal] = useState(false);
  const [confirmModal, setConfirmModal] = useState({ open: false, title: '', message: '', onConfirm: null });

  // Form states
  const [name, setName] = useState('');
  const [username, setUsername] = useState('');
  const [email, setEmail] = useState('');
  const [phone, setPhone] = useState('');
  const [password, setPassword] = useState('');
  const [isSubmitting, setIsSubmitting] = useState(false);

  const fetchStaff = async () => {
    try {
      setLoading(true);
      const response = await axios.get('/version2/api/staff/list.php');
      setActiveStaff(response.data.activeStaff || []);
      setPendingStaff(response.data.pendingStaff || []);
    } catch (error) {
      console.error('Failed to load staff list:', error);
      toast.error('Could not load staff accounts.');
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchStaff();
  }, []);

  const handleApprove = (userId) => {
    setConfirmModal({
      open: true,
      title: 'Approve Staff Member',
      message: 'Are you sure you want to approve this staff member?',
      onConfirm: async () => {
        try {
          const response = await axios.post('/version2/api/staff/manage.php', {
            action: 'approve',
            user_id: userId
          });
          if (response.data.success) {
            toast.success(response.data.message || 'Staff approved.');
            fetchStaff();
          }
        } catch (error) {
          toast.error(error.response?.data?.error || 'Failed to approve staff.');
        }
        setConfirmModal(prev => ({ ...prev, open: false }));
      }
    });
  };

  const handleDelete = (userId) => {
    setConfirmModal({
      open: true,
      title: 'Remove Staff Member',
      message: 'Are you sure you want to remove this staff member?',
      onConfirm: async () => {
        try {
          const response = await axios.post('/version2/api/staff/manage.php', {
            action: 'delete',
            user_id: userId
          });
          if (response.data.success) {
            toast.success(response.data.message || 'Staff member removed.');
            fetchStaff();
          }
        } catch (error) {
          toast.error(error.response?.data?.error || 'Failed to remove staff.');
        }
        setConfirmModal(prev => ({ ...prev, open: false }));
      }
    });
  };

  const handleAddStaff = async (e) => {
    e.preventDefault();

    if (password.length < 6) {
      toast.error('Password must be at least 6 characters.');
      return;
    }

    try {
      setIsSubmitting(true);
      const response = await axios.post('/version2/api/staff/manage.php', {
        action: 'add',
        name,
        username,
        email,
        phone,
        password
      });

      if (response.data.success) {
        toast.success(response.data.message || 'Staff member added.');
        setShowAddModal(false);
        setName('');
        setUsername('');
        setEmail('');
        setPhone('');
        setPassword('');
        fetchStaff();
      }
    } catch (error) {
      toast.error(error.response?.data?.error || 'Failed to add staff.');
    } finally {
      setIsSubmitting(false);
    }
  };

  const activeColumns = [
    {
      header: 'Staff Member',
      accessor: 'name',
      cell: (row) => (
        <div className="staff-meta">
          <strong>{row.name}</strong>
          <span className="staff-username">@{row.username}</span>
        </div>
      )
    },
    {
      header: 'Contact Email',
      accessor: 'email',
      cell: (row) => (
        <div className="contact-cell">
          <Mail size={12} />
          <span>{row.email}</span>
        </div>
      )
    },
    {
      header: 'Phone Number',
      accessor: 'phone',
      cell: (row) => (
        <div className="contact-cell">
          <Phone size={12} />
          <span>{row.phone}</span>
        </div>
      )
    },
    {
      header: 'Enrolled On',
      accessor: 'created_at',
      cell: (row) => new Date(row.created_at).toLocaleDateString('en-US', { dateStyle: 'medium' })
    },
    {
      header: 'Actions',
      cell: (row) => (
        <button 
          onClick={() => handleDelete(row.id)} 
          className="btn btn-danger"
          style={{ padding: '5px 10px', fontSize: '0.78rem' }}
        >
          <Trash2 size={13} />
          <span>Remove</span>
        </button>
      )
    }
  ];

  const pendingColumns = [
    {
      header: 'Applicant Name',
      accessor: 'name',
      cell: (row) => (
        <div className="staff-meta">
          <strong>{row.name}</strong>
          <span className="staff-username">@{row.username}</span>
        </div>
      )
    },
    {
      header: 'Contact Email',
      accessor: 'email',
      cell: (row) => (
        <div className="contact-cell">
          <Mail size={12} />
          <span>{row.email}</span>
        </div>
      )
    },
    {
      header: 'Phone Number',
      accessor: 'phone',
      cell: (row) => (
        <div className="contact-cell">
          <Phone size={12} />
          <span>{row.phone}</span>
        </div>
      )
    },
    {
      header: 'Request Date',
      accessor: 'created_at',
      cell: (row) => new Date(row.created_at).toLocaleDateString('en-US', { dateStyle: 'medium' })
    },
    {
      header: 'Actions',
      cell: (row) => (
        <div style={{ display: 'flex', gap: '6px' }}>
          <button 
            onClick={() => handleApprove(row.id)} 
            className="btn btn-success"
            style={{ padding: '5px 10px', fontSize: '0.78rem' }}
          >
            <Check size={13} />
            <span>Approve</span>
          </button>
          <button 
            onClick={() => handleDelete(row.id)} 
            className="btn btn-danger"
            style={{ padding: '5px 10px', fontSize: '0.78rem' }}
          >
            <Trash2 size={13} />
            <span>Reject</span>
          </button>
        </div>
      )
    }
  ];

  return (
    <div className="page animate-fade-in">
      <div className="tabbar-header">
        <div className="tabbar-tabs">
          <button 
            onClick={() => setActiveTab('active')}
            className={`tabbar-btn ${activeTab === 'active' ? 'tabbar-btn--active' : ''}`}
          >
            <ShieldCheck size={14} />
            <span>Active Roster ({activeStaff.length})</span>
          </button>
          
          <button 
            onClick={() => setActiveTab('pending')}
            className={`tabbar-btn ${activeTab === 'pending' ? 'tabbar-btn--active' : ''}`}
            style={{ position: 'relative' }}
          >
            <Clock size={14} />
            <span>Pending ({pendingStaff.length})</span>
            {pendingStaff.length > 0 && <span className="tabbar-dot"></span>}
          </button>
        </div>

        <button onClick={() => setShowAddModal(true)} className="btn btn-primary">
          <UserPlus size={15} />
          <span>Add Staff</span>
        </button>
      </div>

      <div className="roster-table-section">
        {activeTab === 'active' ? (
          <DataTable 
            columns={activeColumns}
            data={activeStaff}
            loading={loading}
            searchKey="name"
            searchPlaceholder="Search active roster..."
            emptyMessage="No active staff enrolled."
          />
        ) : (
          <DataTable 
            columns={pendingColumns}
            data={pendingStaff}
            loading={loading}
            searchKey="name"
            searchPlaceholder="Search applicants..."
            emptyMessage="No pending registrations."
          />
        )}
      </div>

      {showAddModal && (
        <Modal
          open={showAddModal}
          onClose={() => setShowAddModal(false)}
          title="Add Staff Member"
          subtitle="Create a verified staff profile directly"
        >
          <form onSubmit={handleAddStaff} className="modal-booking-form">
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
                placeholder="John Doe"
                required 
              />
            </div>

            <div className="form-group">
              <label className="form-label">
                <User size={13} />
                <span>Username</span>
              </label>
              <input 
                type="text" 
                value={username} 
                onChange={(e) => setUsername(e.target.value)} 
                className="form-input" 
                placeholder="johndoe"
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
                placeholder="johndoe@company.com"
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
                placeholder="9841234567"
                required 
              />
            </div>

            <div className="form-group">
              <label className="form-label">
                <Lock size={13} />
                <span>Password</span>
              </label>
              <input 
                type="password" 
                value={password} 
                onChange={(e) => setPassword(e.target.value)} 
                className="form-input" 
                placeholder="Min 6 characters"
                required 
              />
            </div>

            <div className="modal-footer-actions">
              <button type="button" onClick={() => setShowAddModal(false)} className="btn btn-secondary">
                Cancel
              </button>
              <button type="submit" disabled={isSubmitting} className="btn btn-primary">
                {isSubmitting ? 'Saving...' : 'Add Staff'}
              </button>
            </div>
          </form>
        </Modal>
      )}

      {confirmModal.open && (
        <Modal
          open={confirmModal.open}
          onClose={() => setConfirmModal(prev => ({ ...prev, open: false }))}
          title={confirmModal.title}
        >
          <div style={{ padding: '10px 0 20px 0', color: 'var(--text-secondary)' }}>
            <p>{confirmModal.message}</p>
          </div>
          <div className="modal-footer-actions">
            <button 
              type="button" 
              onClick={() => setConfirmModal(prev => ({ ...prev, open: false }))} 
              className="btn btn-secondary"
            >
              Cancel
            </button>
            <button 
              type="button" 
              onClick={confirmModal.onConfirm} 
              className="btn btn-primary"
            >
              Confirm
            </button>
          </div>
        </Modal>
      )}

      <style>{`
        .staff-meta {
          display: flex;
          flex-direction: column;
        }

        .staff-username {
          color: var(--text-muted);
          font-size: 0.75rem;
        }

        .contact-cell {
          display: flex;
          align-items: center;
          gap: 6px;
          color: var(--text-secondary);
        }

        .contact-cell svg {
          color: var(--text-muted);
        }
      `}</style>
    </div>
  );
};
export default ManageStaff;
