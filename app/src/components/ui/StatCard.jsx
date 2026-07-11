export const StatCard = ({ title, value, icon: IconComponent, color = 'primary', subtitle }) => {
  const accentColors = {
    primary: 'var(--primary-light)',
    success: 'var(--success)',
    warning: 'var(--warning)',
    danger: 'var(--danger)',
    info: 'var(--info)',
  };

  const accent = accentColors[color] || accentColors.primary;

  return (
    <div className="stat-card glass-card">
      <div className="stat-top">
        <span className="stat-label">{title}</span>
        {IconComponent && (
          <div className="stat-icon" style={{ color: accent }}>
            <IconComponent size={18} />
          </div>
        )}
      </div>
      <span className="stat-value">{value}</span>
      {subtitle && <span className="stat-subtitle">{subtitle}</span>}
      {/* Bottom accent line */}
      <div className="stat-accent-line" style={{ background: accent }}></div>

      <style>{`
        .stat-card {
          position: relative;
          overflow: hidden;
          padding: 20px !important;
        }

        .stat-card:hover {
          transform: translateY(-2px);
        }

        .stat-top {
          display: flex;
          justify-content: space-between;
          align-items: flex-start;
          margin-bottom: 12px;
        }

        .stat-label {
          font-size: 0.72rem;
          font-weight: 700;
          color: var(--text-primary);
          text-transform: uppercase;
          letter-spacing: 0.06em;
        }

        .stat-icon {
          width: 36px;
          height: 36px;
          border-radius: 8px;
          display: flex;
          align-items: center;
          justify-content: center;
          background: rgba(255, 255, 255, 0.03);
          border: 1px solid rgba(255, 255, 255, 0.04);
        }

        .stat-value {
          font-family: var(--font-display);
          font-size: 1.85rem;
          font-weight: 800;
          color: var(--text-primary);
          line-height: 1;
          letter-spacing: -0.02em;
        }

        .stat-subtitle {
          display: block;
          font-size: 0.72rem;
          color: var(--text-secondary);
          margin-top: 6px;
          font-weight: 600;
        }

        .stat-accent-line {
          position: absolute;
          bottom: 0;
          left: 0;
          width: 100%;
          height: 2px;
          opacity: 0.4;
          transition: opacity var(--transition-normal);
        }

        .stat-card:hover .stat-accent-line {
          opacity: 0.8;
        }
      `}</style>
    </div>
  );
};
export default StatCard;
