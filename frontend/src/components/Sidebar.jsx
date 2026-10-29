import React from 'react';
import {
  HomeIcon,
  RepoFolderIcon,
  ScansRadarIcon,
  DocumentIcon,
  SparklesIcon,
  GearIcon,
} from './Icons';

export default function Sidebar({ activeTab = 'repos', onSelectTab }) {
  const items = [
    { id: 'dashboard', label: 'Dashboard', icon: HomeIcon },
    { id: 'repos', label: 'Repositories', icon: RepoFolderIcon },
    { id: 'scans', label: 'Scans', icon: ScansRadarIcon },
    { id: 'reports', label: 'Reports', icon: DocumentIcon },
    { id: 'fixes', label: 'AI Fix Plans', icon: SparklesIcon },
    { id: 'settings', label: 'Settings', icon: GearIcon },
  ];

  return (
    <aside style={{
      width: '240px',
      backgroundColor: '#090D16',
      borderRight: '1px solid #1E293B',
      display: 'flex',
      flexDirection: 'column',
      padding: '20px 12px',
      gap: '6px',
      boxSizing: 'border-box',
      fontFamily: "'Plus Jakarta Sans', system-ui, -apple-system, sans-serif",
    }}>
      {items.map((item) => {
        const Icon = item.icon;
        const isActive = activeTab === item.id;

        return (
          <button
            key={item.id}
            onClick={() => onSelectTab && onSelectTab(item.id)}
            style={{
              display: 'flex',
              alignItems: 'center',
              gap: '12px',
              width: '100%',
              padding: '10px 14px',
              borderRadius: '8px',
              fontSize: '14px',
              fontWeight: isActive ? '600' : '500',
              color: isActive ? '#FFFFFF' : '#94A3B8',
              backgroundColor: isActive ? '#1E293B' : 'transparent',
              border: isActive ? '1px solid #3B82F6' : '1px solid transparent',
              cursor: 'pointer',
              transition: 'all 0.15s ease',
              textAlign: 'left',
            }}
            onMouseOver={(e) => {
              if (!isActive) {
                e.currentTarget.style.backgroundColor = '#0F172A';
                e.currentTarget.style.color = '#F1F5F9';
              }
            }}
            onMouseOut={(e) => {
              if (!isActive) {
                e.currentTarget.style.backgroundColor = 'transparent';
                e.currentTarget.style.color = '#94A3B8';
              }
            }}
          >
            <Icon
              size={18}
              className=""
              style={{ color: isActive ? '#3B82F6' : '#64748B' }}
            />
            <span>{item.label}</span>
          </button>
        );
      })}
    </aside>
  );
}
