import { Sidebar } from './Sidebar';
import { TopBar } from './TopBar';

export const DashboardLayout = ({ children }) => {
  return (
    <div className="layout-wrapper">
      <Sidebar />
      <div className="layout-main">
        <TopBar />
        <main className="layout-content">
          {children}
        </main>
      </div>

      <style>{`
        .layout-wrapper {
          display: flex;
          min-height: 100vh;
          background-color: var(--bg-app);
        }

        .layout-main {
          flex-grow: 1;
          margin-left: 240px;
          display: flex;
          flex-direction: column;
          min-width: 0;
          transition: margin-left var(--transition-normal);
        }

        .layout-content {
          padding: 28px 32px;
          flex-grow: 1;
          overflow-y: auto;
        }

        @media (max-width: 768px) {
          .layout-main {
            margin-left: 68px;
          }
          .layout-content {
            padding: 20px 16px;
          }
        }
      `}</style>
    </div>
  );
};
export default DashboardLayout;
