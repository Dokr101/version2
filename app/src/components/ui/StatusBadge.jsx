export const StatusBadge = ({ status }) => {
  const labelMap = {
    pending: 'Pending',
    confirmed: 'Confirmed',
    checked_in: 'Checked In',
    checked_out: 'Checked Out',
    cancelled: 'Cancelled',
    paid: 'Paid',
    refunded: 'Refunded',
    available: 'Available',
    occupied: 'Occupied',
    unavailable: 'Unavailable'
  };

  const cleanStatus = status ? status.toLowerCase() : 'pending';
  const label = labelMap[cleanStatus] || status;

  return (
    <span className={`badge badge-${cleanStatus}`}>
      <span className="badge-dot" />
      {label}
      <style>{`
        .badge-dot {
          width: 6px;
          height: 6px;
          border-radius: 50%;
          background-color: currentColor;
          display: inline-block;
        }
      `}</style>
    </span>
  );
};
export default StatusBadge;
