import { useState, useEffect } from 'react';
import axios from 'axios';
import toast from 'react-hot-toast';
import { useAuth } from '../../hooks/useAuth';
import { 
  BedDouble, Wifi, Coffee, Tv, Wind, Calendar, Users, X, Receipt,
  Sparkles, Search, SlidersHorizontal, ArrowLeft, LogIn
} from 'lucide-react';
import { Modal } from '../../components/ui/Modal';
import { DashboardLayout } from '../../components/layout/DashboardLayout';
import { PublicHeader } from '../../components/layout/PublicHeader';

export const BrowseRooms = () => {
  const [rooms, setRooms] = useState([]);
  const [loading, setLoading] = useState(true);
  const [typeFilter, setTypeFilter] = useState('');
  const [priceFilter, setPriceFilter] = useState('');
  const [checkin, setCheckin] = useState('');
  const [checkout, setCheckout] = useState('');
  const { user } = useAuth();
  const isGuestUser = user?.role === 'guest';
  
  // Modal states
  const [selectedRoom, setSelectedRoom] = useState(null);
  const [modalGuests, setModalGuests] = useState(1);
  const [modalCheckin, setModalCheckin] = useState('');
  const [modalCheckout, setModalCheckout] = useState('');
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [bookedDates, setBookedDates] = useState([]);
  const [loadingDates, setLoadingDates] = useState(false);

  const fetchRooms = async (inDate = '', outDate = '') => {
    try {
      setLoading(true);
      let url = '/version2/api/rooms/list.php';
      if (inDate && outDate) {
        url += `?checkin=${inDate}&checkout=${outDate}`;
      }
      const response = await axios.get(url);
      setRooms(response.data);
    } catch (error) {
      console.error('Failed to load rooms:', error);
      toast.error('Could not load room listings.');
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchRooms();
  }, []);

  const handleQuickCheck = (e) => {
    e.preventDefault();
    if (checkin && checkout) {
      if (new Date(checkin) >= new Date(checkout)) {
        toast.error('Check-out date must be after check-in.');
        return;
      }
      fetchRooms(checkin, checkout);
      toast.success('Room availability updated for selected dates.');
    }
  };

  const handleResetCheck = () => {
    setCheckin('');
    setCheckout('');
    fetchRooms();
  };

  const openBookingModal = async (room) => {
    setSelectedRoom(room);
    setModalGuests(1);
    setModalCheckin(checkin || '');
    setModalCheckout(checkout || '');
    
    try {
      setLoadingDates(true);
      const response = await axios.get(`/version2/api/rooms/booked_dates.php?room_id=${room.room_id}`);
      setBookedDates(response.data || []);
    } catch (err) {
      console.error('Failed to fetch booked dates:', err);
      setBookedDates([]);
    } finally {
      setLoadingDates(false);
    }
  };

  const closeBookingModal = () => {
    setSelectedRoom(null);
  };

  const calculateNights = () => {
    if (!modalCheckin || !modalCheckout) return 0;
    const cin = new Date(modalCheckin);
    const cout = new Date(modalCheckout);
    const diffTime = cout - cin;
    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
    return diffDays > 0 ? diffDays : 0;
  };

  const nights = calculateNights();
  const rawCost = selectedRoom ? selectedRoom.price * nights : 0;
  const tax = rawCost * 0.13;
  const totalCost = rawCost + tax;

  const handleConfirmBooking = async (e) => {
    e.preventDefault();
    if (!modalCheckin || !modalCheckout) {
      toast.error('Please select check-in and check-out dates.');
      return;
    }
    if (new Date(modalCheckin) >= new Date(modalCheckout)) {
      toast.error('Check-out date must be after check-in date.');
      return;
    }

    try {
      setIsSubmitting(true);
      const response = await axios.post('/version2/api/bookings/create.php', {
        room_id: selectedRoom.room_id,
        checkin: modalCheckin,
        checkout: modalCheckout,
        guests: modalGuests
      });

      if (response.data.success) {
        toast.success(response.data.message || 'Booking created! Redirecting to payment...');
        window.location.href = response.data.redirect_url;
      }
    } catch (error) {
      const msg = error.response?.data?.error || 'Booking failed. Please try again.';
      toast.error(msg);
    } finally {
      setIsSubmitting(false);
    }
  };

  const roomTypes = Array.from(new Set(rooms.map(r => r.type)));

  const filteredRooms = rooms.filter(room => {
    let show = true;
    if (typeFilter && room.type !== typeFilter) show = false;
    
    if (priceFilter === 'low' && room.price >= 150) show = false;
    else if (priceFilter === 'medium' && (room.price < 150 || room.price > 250)) show = false;
    else if (priceFilter === 'high' && room.price <= 250) show = false;
    
    return show;
  });

  const getAmenityIcon = (name) => {
    const term = name.toLowerCase();
    if (term.includes('wifi')) return <Wifi size={13} />;
    if (term.includes('breakfast') || term.includes('coffee')) return <Coffee size={13} />;
    if (term.includes('tv') || term.includes('television')) return <Tv size={13} />;
    if (term.includes('ac') || term.includes('condition')) return <Wind size={13} />;
    return <Sparkles size={13} />;
  };

  const content = (
    <div className="page animate-fade-in">
      {/* Search and Filters Card */}
      <div className="filters-card glass-card">
        <div className="filters-card-header">
          <SlidersHorizontal size={16} className="icon-muted" />
          <h3>Filter Accommodations</h3>
        </div>
        
        <div className="filters-grid">
          <div className="form-group">
            <label className="form-label">Room Type</label>
            <select 
              value={typeFilter}
              onChange={(e) => setTypeFilter(e.target.value)}
              className="form-input"
            >
              <option value="">All Types</option>
              {roomTypes.map(t => (
                <option key={t} value={t}>{t} Room</option>
              ))}
            </select>
          </div>

          <div className="form-group">
            <label className="form-label">Price Category</label>
            <select 
              value={priceFilter}
              onChange={(e) => setPriceFilter(e.target.value)}
              className="form-input"
            >
              <option value="">All Prices</option>
              <option value="low">Budget (Under Rs. 150)</option>
              <option value="medium">Premium (Rs. 150 - Rs. 250)</option>
              <option value="high">Luxury (Over Rs. 250)</option>
            </select>
          </div>

          <div className="form-group date-filter-group">
            <label className="form-label">Check Dates Availability</label>
            <form onSubmit={handleQuickCheck} className="quick-date-form">
              <input 
                type="date" 
                value={checkin}
                onChange={(e) => setCheckin(e.target.value)}
                min={new Date().toISOString().split('T')[0]}
                className="form-input"
                required
              />
              <input 
                type="date" 
                value={checkout}
                onChange={(e) => setCheckout(e.target.value)}
                min={checkin || new Date().toISOString().split('T')[0]}
                className="form-input"
                required
              />
              <div className="quick-date-actions">
                <button type="submit" className="btn btn-primary">
                  Filter Availability
                </button>
                {(checkin || checkout) && (
                  <button type="button" onClick={handleResetCheck} className="btn btn-secondary">
                    Reset
                  </button>
                )}
              </div>
            </form>
          </div>
        </div>
      </div>

      {/* Rooms Grid */}
      {loading ? (
        <div className="rooms-loading-state">
          <div className="spinner"></div>
          <span>Loading rooms...</span>
        </div>
      ) : filteredRooms.length === 0 ? (
        <div className="empty-state glass-card">
          <Search size={32} />
          <h4>No Rooms Found</h4>
          <p>Try resetting filters to browse all room choices.</p>
          <button onClick={handleResetCheck} className="btn btn-primary" style={{ marginTop: '10px' }}>
            Show All Rooms
          </button>
        </div>
      ) : (
        <div className="rooms-grid-layout stagger-children">
          {filteredRooms.map((room) => {
            const isAvailable = room.is_available;
            return (
              <div key={room.room_id} className={`room-showcase-card glass-card ${!isAvailable ? 'room-card-opaque' : ''}`}>
                <div className="room-image-area">
                  {room.image_url ? (
                    <img src={room.image_url} alt={`${room.type} room`} className="room-card-img" />
                  ) : (
                    <div className="room-no-img">
                      <BedDouble size={36} className="icon-purple" />
                      <span>No Photo available</span>
                    </div>
                  )}
                  <div className="room-price-banner">
                    <span className="price-num">Rs. {room.price}</span>
                    <span className="price-unit">/ night</span>
                  </div>
                </div>

                <div className="room-details-area">
                  <div className="room-header-row">
                    <h4>{room.type} Room</h4>
                    <div className={`room-avail-tag ${isAvailable ? 'tag-avail' : 'tag-taken'}`}>
                      {isAvailable ? 'Available' : 'Booked'}
                    </div>
                  </div>

                  <p className="room-description-text">{room.description}</p>

                  <div className="amenities-container">
                    {room.amenities.split(',').map((amenity, idx) => (
                      <span key={idx} className="amenity-tag">
                        {getAmenityIcon(amenity)}
                        <span>{amenity.trim()}</span>
                      </span>
                    ))}
                  </div>

                  <div className="room-footer-action">
                    {isAvailable ? (
                      isGuestUser ? (
                        <button 
                          onClick={() => openBookingModal(room)}
                          className="btn btn-primary w-full"
                        >
                          Book Now
                        </button>
                      ) : user ? (
                        <button 
                          disabled 
                          className="btn btn-secondary w-full"
                          style={{ opacity: 0.85, cursor: 'not-allowed' }}
                        >
                          Guest Booking Only
                        </button>
                      ) : (
                        <button 
                          type="button"
                          onClick={() => window.location.href = '/version2/auth/login.php?redirect=/version2/app/guest/rooms'}
                          className="btn btn-secondary w-full"
                        >
                          Login to Book
                        </button>
                      )
                    ) : (
                      <button 
                        disabled 
                        className="btn btn-secondary w-full"
                        style={{ opacity: 0.5, cursor: 'not-allowed' }}
                      >
                        Occupied
                      </button>
                    )}
                  </div>
                </div>
              </div>
            );
          })}
        </div>
      )}

      {/* Booking Modal */}
      {selectedRoom && (
        <Modal
          open={selectedRoom !== null}
          onClose={closeBookingModal}
          title="Confirm Booking"
          subtitle={`${selectedRoom.type} Room — #${selectedRoom.room_id}`}
        >
          <form onSubmit={handleConfirmBooking} className="modal-booking-form">
            <div className="form-group">
                <label className="form-label">
                <Users size={14} />
                <span>Guests</span>
              </label>
              <select 
                value={modalGuests} 
                onChange={(e) => setModalGuests(parseInt(e.target.value, 10))}
                className="form-input"
                required
              >
                <option value={1}>1 Guest</option>
                <option value={2}>2 Guests</option>
                <option value={3}>3 Guests</option>
                <option value={4}>4 Guests</option>
              </select>
            </div>

            {/* Booked Dates Calendar Display */}
            <div className="form-group" style={{ marginBottom: '16px' }}>
              <label className="form-label" style={{ fontWeight: '600', marginBottom: '8px' }}>
                <Calendar size={14} />
                <span>Reserved Dates / Room Availability</span>
              </label>
              {loadingDates ? (
                <div style={{ fontSize: '0.8rem', color: 'var(--text-muted)' }}>Loading availability...</div>
              ) : bookedDates.length === 0 ? (
                <div className="no-bookings-badge" style={{
                  padding: '8px', 
                  borderRadius: '6px', 
                  background: 'rgba(16, 185, 129, 0.1)', 
                  color: 'var(--success)', 
                  fontSize: '0.8rem',
                  display: 'inline-block'
                }}>
                  Room is fully available (No active bookings)
                </div>
              ) : (
                <div className="booked-dates-list" style={{
                  display: 'flex',
                  flexWrap: 'wrap',
                  gap: '6px',
                  maxHeight: '100px',
                  overflowY: 'auto',
                  padding: '8px',
                  border: '1px solid var(--border-color)',
                  borderRadius: '6px',
                  background: 'var(--bg-card)'
                }}>
                  {bookedDates.map((range, idx) => (
                    <span key={idx} className="booked-date-badge" style={{
                      padding: '4px 8px',
                      background: 'rgba(239, 68, 68, 0.1)',
                      color: 'var(--danger)',
                      borderRadius: '4px',
                      fontSize: '0.75rem',
                      fontWeight: '500'
                    }}>
                      {new Date(range.checkin).toLocaleDateString('en-US', { month: 'short', day: 'numeric' })} – {new Date(range.checkout).toLocaleDateString('en-US', { month: 'short', day: 'numeric' })}
                    </span>
                  ))}
                </div>
              )}
            </div>

            <div className="dates-form-row">
              <div className="form-group">
                <label className="form-label">
                  <Calendar size={14} />
                  <span>Check-In</span>
                </label>
                <input 
                  type="date"
                  value={modalCheckin}
                  onChange={(e) => setModalCheckin(e.target.value)}
                  min={new Date().toISOString().split('T')[0]}
                  className="form-input"
                  required
                />
              </div>

              <div className="form-group">
                <label className="form-label">
                  <Calendar size={14} />
                  <span>Check-Out</span>
                </label>
                <input 
                  type="date"
                  value={modalCheckout}
                  onChange={(e) => setModalCheckout(e.target.value)}
                  min={modalCheckin || new Date().toISOString().split('T')[0]}
                  className="form-input"
                  required
                />
              </div>
            </div>

            {nights > 0 && (
              <div className="booking-billing-summary">
                <div className="billing-title">
                  <Receipt size={14} />
                  <span>Payment Summary</span>
                </div>

                <div className="billing-rows">
                  <div className="billing-row">
                    <span>Rate ({nights} {nights === 1 ? 'night' : 'nights'})</span>
                    <span>Rs. {rawCost.toLocaleString('en-IN')}</span>
                  </div>
                  <div className="billing-row">
                    <span>GST (13%)</span>
                    <span>Rs. {tax.toLocaleString('en-IN')}</span>
                  </div>
                  <div className="billing-divider"></div>
                  <div className="billing-row billing-total">
                    <span>Total Cost</span>
                    <span className="total-glow">Rs. {totalCost.toLocaleString('en-IN')}</span>
                  </div>
                </div>
              </div>
            )}

            <div className="modal-policy-notice">
              <span className="policy-bullet"></span>
              <span>You will be redirected to Khalti Gateway to securely pay and confirm.</span>
            </div>

            <div className="modal-footer-actions">
              <button type="button" onClick={closeBookingModal} className="btn btn-secondary">
                Close
              </button>
              <button 
                type="submit" 
                disabled={isSubmitting || nights === 0} 
                className="btn btn-primary"
              >
                {isSubmitting ? 'Confirming...' : 'Confirm & Pay'}
              </button>
            </div>
          </form>
        </Modal>
      )}

      <style>{`
        .filters-card {
          margin-bottom: 20px;
        }

        .filters-card-header {
          display: flex;
          align-items: center;
          gap: 8px;
          margin-bottom: 16px;
        }

        .filters-card-header h3 {
          font-size: 1rem;
          color: var(--text-primary);
          font-weight: 800;
        }

        .icon-purple {
          color: var(--primary-dark);
        }

        .filters-grid {
          display: grid;
          grid-template-columns: 1fr 1fr 2fr;
          gap: 16px;
        }

        .quick-date-form {
          display: flex;
          gap: 8px;
        }

        .quick-date-actions {
          display: flex;
          gap: 6px;
        }

        .room-card-opaque {
          opacity: 0.55;
        }

        .w-full {
          width: 100%;
        }

        @media (max-width: 992px) {
          .filters-grid {
            grid-template-columns: 1fr;
          }
          .quick-date-form {
            flex-direction: column;
          }
        }
      `}</style>
    </div>
  );

  // Wrap based on auth state
  if (user) {
    return <DashboardLayout>{content}</DashboardLayout>;
  }
  return (
    <>
      <PublicHeader />
      <div style={{ padding: '24px', maxWidth: '1200px', margin: '0 auto' }}>
        {content}
      </div>
    </>
  );
};
export default BrowseRooms;
