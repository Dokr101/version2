import { Sidebar } from './Sidebar';
import { TopBar } from './TopBar';
import { SidebarProvider, useSidebar } from '../../contexts/SidebarContext';

const LayoutInner = ({ children }) => {
  const { collapsed } = useSidebar();
  return (
    <div className="layout-wrapper">
      <Sidebar />
      <div className={`layout-main ${collapsed ? 'layout-main--collapsed' : ''}`}>
        <TopBar />
        <main className="layout-content">{children}</main>
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
          transition: margin-left 0.28s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .layout-main--collapsed {
          margin-left: 68px;
        }
        .layout-content {
          padding: 28px 32px;
          flex-grow: 1;
          overflow-y: auto;
        }
        @media (max-width: 768px) {
          .layout-main { margin-left: 68px; }
          .layout-content { padding: 20px 16px; }
        }
      `}</style>
    </div>
  );
};

export const DashboardLayout = ({ children }) => (
  <SidebarProvider>
    <LayoutInner>{children}</LayoutInner>
  </SidebarProvider>
);
export default DashboardLayout;
