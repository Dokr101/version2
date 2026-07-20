import { useState, useEffect } from 'react';
import { createPortal } from 'react-dom';
import { X } from 'lucide-react';

export const Modal = ({ open, onClose, title, subtitle, children }) => {
  const [shouldRender, setShouldRender] = useState(open);

  useEffect(() => {
    if (open) {
      setShouldRender(true);
    }
  }, [open]);

  const handleAnimationEnd = () => {
    if (!open) {
      setShouldRender(false);
    }
  };

  const isVisible = open || shouldRender;

  return createPortal(
    <div 
      className={`modal-overlay-wrapper ${isVisible ? '' : 'modal-overlay-wrapper--hidden'} ${open ? 'modal-overlay-wrapper--open' : 'modal-overlay-wrapper--closed'}`}
      role="dialog" 
      aria-modal="true" 
      onClick={onClose}
    >
      <div 
        className="modal-panel glass-card" 
        onClick={(event) => event.stopPropagation()}
        onAnimationEnd={handleAnimationEnd}
      >
        <div className="modal-header-section">
          <div>
            <h3>{title}</h3>
            {subtitle && <p>{subtitle}</p>}
          </div>
          <button type="button" onClick={onClose} className="modal-close-icon" aria-label="Close">
            <X size={16} />
          </button>
        </div>
        {children}
      </div>
    </div>,
    document.body
  );
};

export default Modal;
