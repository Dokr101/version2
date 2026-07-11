import { useState } from 'react';
import { Search, ChevronLeft, ChevronRight, Inbox } from 'lucide-react';

export const DataTable = ({ 
  columns, 
  data = [], 
  searchPlaceholder = 'Search...', 
  searchKey,
  loading = false,
  emptyMessage = 'No records found.'
}) => {
  const [searchTerm, setSearchTerm] = useState('');
  const [currentPage, setCurrentPage] = useState(1);
  const itemsPerPage = 10;

  const filteredData = data.filter(item => {
    if (!searchTerm || !searchKey) return true;
    const value = item[searchKey];
    if (!value) return false;
    return String(value).toLowerCase().includes(searchTerm.toLowerCase());
  });

  const totalPages = Math.ceil(filteredData.length / itemsPerPage);
  const indexOfLastItem = currentPage * itemsPerPage;
  const indexOfFirstItem = indexOfLastItem - itemsPerPage;
  const currentItems = filteredData.slice(indexOfFirstItem, indexOfLastItem);

  const handlePageChange = (pageNumber) => {
    if (pageNumber >= 1 && pageNumber <= totalPages) {
      setCurrentPage(pageNumber);
    }
  };

  return (
    <div className="dt-root">
      {searchKey && (
        <div className="dt-toolbar">
          <div className="dt-search">
            <Search size={15} className="dt-search-icon" />
            <input
              type="text"
              placeholder={searchPlaceholder}
              value={searchTerm}
              onChange={(e) => {
                setSearchTerm(e.target.value);
                setCurrentPage(1);
              }}
              className="dt-search-input"
            />
          </div>
          <span className="dt-count">{filteredData.length} records</span>
        </div>
      )}

      <div className="dt-body">
        {loading ? (
          <div className="dt-loading">
            <div className="spinner"></div>
            <span>Loading...</span>
          </div>
        ) : currentItems.length === 0 ? (
          <div className="dt-empty">
            <Inbox size={32} />
            <p>{emptyMessage}</p>
          </div>
        ) : (
          <table>
            <thead>
              <tr>
                {columns.map((col) => (
                  <th key={col.header} style={{ width: col.width }}>
                    {col.header}
                  </th>
                ))}
              </tr>
            </thead>
            <tbody>
              {currentItems.map((item, idx) => (
                <tr key={item.id || item.booking_id || item.room_id || item.payment_id || idx}>
                  {columns.map((col) => (
                    <td key={col.header}>
                      {col.cell ? col.cell(item) : item[col.accessor]}
                    </td>
                  ))}
                </tr>
              ))}
            </tbody>
          </table>
        )}
      </div>

      {!loading && totalPages > 1 && (
        <div className="dt-footer">
          <span className="dt-footer-info">
            {indexOfFirstItem + 1}–{Math.min(indexOfLastItem, filteredData.length)} of {filteredData.length}
          </span>
          <div className="dt-pagination">
            <button 
              onClick={() => handlePageChange(currentPage - 1)}
              disabled={currentPage === 1}
              className="dt-page-btn"
            >
              <ChevronLeft size={14} />
            </button>
            
            {Array.from({ length: Math.min(totalPages, 5) }, (_, i) => {
              let page;
              if (totalPages <= 5) {
                page = i + 1;
              } else if (currentPage <= 3) {
                page = i + 1;
              } else if (currentPage >= totalPages - 2) {
                page = totalPages - 4 + i;
              } else {
                page = currentPage - 2 + i;
              }
              return (
                <button
                  key={page}
                  onClick={() => handlePageChange(page)}
                  className={`dt-page-btn ${currentPage === page ? 'dt-page-btn--active' : ''}`}
                >
                  {page}
                </button>
              );
            })}

            <button 
              onClick={() => handlePageChange(currentPage + 1)}
              disabled={currentPage === totalPages}
              className="dt-page-btn"
            >
              <ChevronRight size={14} />
            </button>
          </div>
        </div>
      )}

      <style>{`
        .dt-root {
          background: var(--bg-card);
          border: 1px solid var(--border-color);
          border-radius: var(--border-radius-md);
          overflow: hidden;
        }

        .dt-toolbar {
          padding: 14px 18px;
          border-bottom: 1px solid var(--border-color);
          display: flex;
          justify-content: space-between;
          align-items: center;
        }

        .dt-search {
          position: relative;
          width: 240px;
        }

        .dt-search-icon {
          position: absolute;
          left: 12px;
          top: 50%;
          transform: translateY(-50%);
          color: var(--text-muted);
        }

        .dt-search-input {
          width: 100%;
          background: var(--bg-input);
          border: 1px solid var(--border-color);
          border-radius: var(--border-radius-sm);
          padding: 8px 12px 8px 34px;
          color: var(--text-primary);
          outline: none;
          font-size: 0.82rem;
          transition: all var(--transition-fast);
        }

        .dt-search-input:focus {
          border-color: var(--primary-light);
          background: var(--bg-input-focus);
        }

        .dt-count {
          font-size: 0.72rem;
          color: var(--text-muted);
        }

        .dt-body {
          width: 100%;
          overflow-x: auto;
          min-height: 120px;
          position: relative;
        }

        .dt-loading {
          display: flex;
          flex-direction: column;
          align-items: center;
          justify-content: center;
          padding: 48px;
          gap: 12px;
          color: var(--text-muted);
          font-size: 0.82rem;
        }

        .dt-empty {
          display: flex;
          flex-direction: column;
          align-items: center;
          padding: 40px 20px;
          text-align: center;
          color: var(--text-muted);
          gap: 8px;
          font-size: 0.85rem;
        }

        .dt-footer {
          padding: 12px 18px;
          border-top: 1px solid var(--border-color);
          display: flex;
          justify-content: space-between;
          align-items: center;
        }

        .dt-footer-info {
          font-size: 0.72rem;
          color: var(--text-muted);
        }

        .dt-pagination {
          display: flex;
          gap: 4px;
        }

        .dt-page-btn {
          width: 28px;
          height: 28px;
          display: flex;
          align-items: center;
          justify-content: center;
          border-radius: 6px;
          border: 1px solid var(--border-color);
          background: transparent;
          color: var(--text-secondary);
          cursor: pointer;
          font-size: 0.75rem;
          font-weight: 600;
          transition: all var(--transition-fast);
        }

        .dt-page-btn:hover:not(:disabled) {
          background: var(--bg-card-hover);
          color: var(--text-primary);
        }

        .dt-page-btn--active {
          background: var(--primary) !important;
          color: white !important;
          border-color: var(--primary) !important;
        }

        .dt-page-btn:disabled {
          opacity: 0.25;
          cursor: not-allowed;
        }
      `}</style>
    </div>
  );
};
export default DataTable;
