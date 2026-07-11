import { useState, useEffect } from 'react';
import axios from 'axios';
import toast from 'react-hot-toast';
import { 
  BedDouble, Plus, Edit3, Trash2, X, Sparkles, Camera, Layers
} from 'lucide-react';
import { useAuth } from '../../hooks/useAuth';
import { Modal } from '../../components/ui/Modal';

export const ManageRooms = () => {
  const { user } = useAuth();
  const isStaff = user?.role === 'staff';

  const [rooms, setRooms] = useState([]);
  const [loading, setLoading] = useState(true);
  const [showModal, setShowModal] = useState(false);
  const [modalMode, setModalMode] = useState('add');
  const [selectedRoom, setSelectedRoom] = useState(null);

  // Form states
  const [type, setType] = useState('');
  const [price, setPrice] = useState('');
  const [description, setDescription] = useState('');
  const [amenities, setAmenities] = useState('');
  const [status, setStatus] = useState('available');
  const [imageFile, setImageFile] = useState(null);
  const [imagePreview, setImagePreview] = useState('');
  const [isSubmitting, setIsSubmitting] = useState(false);

  const fetchRooms = async () => {
    try {
      setLoading(true);
      const response = await axios.get('/version2/api/rooms/list.php');
      setRooms(response.data);
    } catch (error) {
      console.error('Failed to load rooms:', error);
      toast.error('Could not load hotel suites.');
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchRooms();
  }, []);

  const openAddModal = () => {
    setModalMode('add');
    setSelectedRoom(null);
    setType('');
    setPrice('');
    setDescription('');
    setAmenities('');
    setStatus('available');
    setImageFile(null);
    setImagePreview('');
    setShowModal(true);
  };

  const openEditModal = (room) => {
    setModalMode('edit');
    setSelectedRoom(room);
    setType(room.type);
    setPrice(room.price);
    setDescription(room.description);
    setAmenities(room.amenities);
    setStatus(room.status);
    setImageFile(null);
    setImagePreview(room.image_url || '');
    setShowModal(true);
  };

  const handleImageChange = (e) => {
    const file = e.target.files[0];
    if (file) {
      setImageFile(file);
      const reader = new FileReader();
      reader.onloadend = () => {
        setImagePreview(reader.result);
      };
      reader.readAsDataURL(file);
    }
  };

  const handleDelete = async (roomId) => {
    if (!window.confirm('Delete this room listing?')) return;

    try {
      const formData = new FormData();
      formData.append('action', 'delete');
      formData.append('room_id', roomId);

      const response = await axios.post('/version2/api/rooms/manage.php', formData);
      if (response.data.success) {
        toast.success(response.data.message || 'Room deleted.');
        fetchRooms();
      }
    } catch (error) {
      toast.error(error.response?.data?.error || 'Failed to delete room.');
    }
  };

  const handleSubmit = async (e) => {
    e.preventDefault();

    if (price <= 0) {
      toast.error('Price must be greater than zero.');
      return;
    }

    try {
      setIsSubmitting(true);
      const formData = new FormData();
      formData.append('action', modalMode);
      formData.append('type', type);
      formData.append('price', price);
      formData.append('description', description);
      formData.append('amenities', amenities);
      formData.append('status', status);

      if (modalMode === 'edit') {
        formData.append('room_id', selectedRoom.room_id);
      }

      if (imageFile) {
        formData.append('room_image', imageFile);
      }

      const response = await axios.post('/version2/api/rooms/manage.php', formData, {
        headers: {
          'Content-Type': 'multipart/form-data'
        }
      });

      if (response.data.success) {
        toast.success(response.data.message || 'Room saved.');
        setShowModal(false);
        fetchRooms();
      }
    } catch (error) {
      toast.error(error.response?.data?.error || 'Failed to save room details.');
    } finally {
      setIsSubmitting(false);
    }
  };

  return (
    <div className="page animate-fade-in">
      <div className="tabbar-header" style={{ borderBottom: 'none', marginBottom: '8px' }}>
        <div className="title-section" style={{ display: 'flex', alignItems: 'center', gap: '10px' }}>
          <Layers size={18} className="icon-purple" />
          <h3 style={{ fontSize: '1rem', color: 'var(--text-primary)' }}>Suite Inventory</h3>
        </div>

        {!isStaff && (
          <button onClick={openAddModal} className="btn btn-primary">
            <Plus size={15} />
            <span>Add Suite</span>
          </button>
        )}
      </div>

      {loading ? (
        <div className="rooms-loading-state" style={{ display: 'flex', justifyContent: 'center', padding: '60px' }}>
          <div className="spinner"></div>
        </div>
      ) : rooms.length === 0 ? (
        <div className="empty-state glass-card">
          <BedDouble size={32} />
          <h4>No Rooms Found</h4>
          <p>The hotel room database is currently empty.</p>
        </div>
      ) : (
        <div className="rooms-grid-layout stagger-children">
          {rooms.map((room) => (
            <div key={room.room_id} className="room-showcase-card glass-card">
              <div className="room-image-area">
                {room.image_url ? (
                  <img src={room.image_url} alt={`${room.type} room`} className="room-card-img" />
                ) : (
                  <div className="room-no-img">
                    <BedDouble size={36} className="icon-purple" />
                    <span>No Photo</span>
                  </div>
                )}
                <div className="room-price-banner">
                  <span className="price-num">Rs. {room.price}</span>
                  <span className="price-unit" style={{ color: '#ffffff', fontWeight: 700 }}>/ night</span>
                </div>
              </div>

              <div className="room-details-area">
                <div className="room-header-row">
                  <h4>{room.type} Room</h4>
                  <div className={`room-avail-tag ${room.status === 'available' ? 'tag-avail' : room.status === 'occupied' ? 'tag-taken' : 'tag-unavail'}`}>
                    {room.status}
                  </div>
                </div>

                <p className="room-description-text">{room.description}</p>

                <div className="amenities-container">
                  {room.amenities.split(',').map((amenity, idx) => (
                    <span key={idx} className="amenity-tag">
                      <Sparkles size={11} />
                      <span>{amenity.trim()}</span>
                    </span>
                  ))}
                </div>

                {!isStaff && (
                  <div className="admin-room-actions">
                    <button 
                      onClick={() => openEditModal(room)}
                      className="btn btn-secondary flex-1"
                    >
                      <Edit3 size={13} />
                      <span>Edit</span>
                    </button>
                    <button 
                      onClick={() => handleDelete(room.room_id)}
                      className="btn btn-danger flex-1"
                    >
                      <Trash2 size={13} />
                      <span>Delete</span>
                    </button>
                  </div>
                )}
              </div>
            </div>
          ))}
        </div>
      )}

      {/* Add/Edit Suite Modal */}
      {showModal && (
        <Modal
          open={showModal}
          onClose={() => setShowModal(false)}
          title={modalMode === 'add' ? 'Add New Suite' : 'Edit Suite'}
          subtitle="Configure room rates, cover banner, and status"
        >
          <form onSubmit={handleSubmit} className="modal-booking-form">
            <div className="form-group">
              <label className="form-label">Cover Banner Photo</label>
              <div className="image-uploader-container">
                {imagePreview ? (
                  <div className="image-preview-wrapper">
                    <img src={imagePreview} alt="Preview" className="upload-preview-img" />
                    <label htmlFor="modal-image-file" className="change-image-btn">
                      <Camera size={14} />
                    </label>
                  </div>
                ) : (
                  <label htmlFor="modal-image-file" className="upload-placeholder-box">
                    <Camera size={24} className="icon-muted" />
                    <span>Select Photo Banner</span>
                  </label>
                )}
                <input 
                  type="file" 
                  id="modal-image-file" 
                  accept="image/*"
                  onChange={handleImageChange}
                  style={{ display: 'none' }}
                />
              </div>
            </div>

            <div className="dates-form-row">
              <div className="form-group">
                <label className="form-label">Suite Type Name</label>
                <input 
                  type="text"
                  value={type}
                  onChange={(e) => setType(e.target.value)}
                  placeholder="Deluxe, Suite, Standard..."
                  className="form-input"
                  required
                />
              </div>

              <div className="form-group">
                <label className="form-label">Price per Night (Rs.)</label>
                <input 
                  type="number"
                  value={price}
                  onChange={(e) => setPrice(e.target.value)}
                  placeholder="200"
                  className="form-input"
                  required
                />
              </div>
            </div>

            <div className="form-group">
              <label className="form-label">Operating Status</label>
              <select 
                value={status}
                onChange={(e) => setStatus(e.target.value)}
                className="form-input"
                required
              >
                <option value="available">Available (Open for bookings)</option>
                <option value="occupied">Occupied (In use)</option>
                <option value="unavailable">Unavailable (Maintenance)</option>
              </select>
            </div>

            <div className="form-group">
              <label className="form-label">Amenities List (comma-separated)</label>
              <input 
                type="text"
                value={amenities}
                onChange={(e) => setAmenities(e.target.value)}
                placeholder="Free Wi-Fi, AC, Breakfast"
                className="form-input"
                required
              />
            </div>

            <div className="form-group">
              <label className="form-label">Description Details</label>
              <textarea 
                value={description}
                onChange={(e) => setDescription(e.target.value)}
                placeholder="Provide brief details on this room category..."
                className="form-input"
                style={{ minHeight: '60px', resize: 'vertical' }}
                required
              />
            </div>

            <div className="modal-footer-actions">
              <button type="button" onClick={() => setShowModal(false)} className="btn btn-secondary">
                Cancel
              </button>
              <button type="submit" disabled={isSubmitting} className="btn btn-primary">
                {isSubmitting ? 'Saving...' : 'Save Configuration'}
              </button>
            </div>
          </form>
        </Modal>
      )}

      <style>{`
        .icon-purple {
          color: var(--primary-dark);
        }

        .flex-1 {
          flex: 1;
        }

        .admin-room-actions {
          display: flex;
          gap: 8px;
          margin-top: auto;
          padding-top: 12px;
          border-top: 1px solid var(--border-color);
        }

        .tag-unavail {
          background: rgba(255, 255, 255, 0.04);
          color: var(--text-muted);
          border: 1px solid var(--border-color);
        }

        .image-uploader-container {
          width: 100%;
          height: 120px;
          border: 1px dashed var(--border-color);
          border-radius: var(--border-radius-sm);
          overflow: hidden;
          background: var(--bg-input);
          position: relative;
        }

        .upload-placeholder-box {
          width: 100%;
          height: 100%;
          display: flex;
          flex-direction: column;
          align-items: center;
          justify-content: center;
          gap: 6px;
          cursor: pointer;
          color: var(--text-secondary);
          font-size: 0.8rem;
          transition: all var(--transition-fast);
        }

        .upload-placeholder-box:hover {
          background: rgba(255, 255, 255, 0.02);
        }

        .image-preview-wrapper {
          width: 100%;
          height: 100%;
          position: relative;
        }

        .upload-preview-img {
          width: 100%;
          height: 100%;
          object-fit: cover;
        }

        .change-image-btn {
          position: absolute;
          bottom: 8px;
          right: 8px;
          width: 28px;
          height: 28px;
          background: var(--primary);
          border: 1px solid var(--primary-light);
          border-radius: 50%;
          display: flex;
          align-items: center;
          justify-content: center;
          color: var(--bg-app);
          cursor: pointer;
          box-shadow: var(--shadow-sm);
          transition: all var(--transition-fast);
        }

        .change-image-btn:hover {
          background: var(--primary-light);
        }
      `}</style>
    </div>
  );
};
export default ManageRooms;
