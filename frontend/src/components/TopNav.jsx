import React, { useState } from 'react';
import { LogoIcon, BellIcon, ChevronDownIcon } from './Icons';

export default function TopNav({
  currentUser,
  workspaces = [],
  activeWorkspace,
  onSelectWorkspace,
  onLogout,
  unreadCount = 0,
}) {
  const [showUserMenu, setShowUserMenu] = useState(false);
  const [showWorkspaceMenu, setShowWorkspaceMenu] = useState(false);

  const displayName = currentUser?.nickname || currentUser?.name || 'User';
  const initial = (displayName[0] || 'U').toUpperCase();

  return (
    <header style={{
      height: '60px',
      backgroundColor: '#0B0F19',
      borderBottom: '1px solid #1E293B',
      display: 'flex',
      alignItems: 'center',
      justifyContent: 'space-between',
      padding: '0 24px',
      position: 'relative',
      zIndex: 50,
      fontFamily: "'Plus Jakarta Sans', system-ui, -apple-system, sans-serif",
    }}>
      {/* Left: Brand & Section / Workspace Dropdown */}
      <div style={{ display: 'flex', alignItems: 'center', gap: '20px' }}>
        <div style={{ display: 'flex', alignItems: 'center', gap: '10px', textDecoration: 'none' }}>
          <LogoIcon size={26} />
          <span style={{ fontSize: '18px', fontWeight: '700', letterSpacing: '-0.3px', color: '#FFFFFF' }}>
            DevGuard<span style={{ color: '#3B82F6' }}>AI</span>
          </span>
        </div>

        {/* Repositories / Workspace Dropdown */}
        <div style={{ position: 'relative' }}>
          <button
            onClick={() => setShowWorkspaceMenu(!showWorkspaceMenu)}
            style={{
              display: 'flex',
              alignItems: 'center',
              gap: '8px',
              backgroundColor: '#161E2E',
              border: '1px solid #1E293B',
              borderRadius: '8px',
              padding: '6px 12px',
              color: '#F1F5F9',
              fontSize: '13px',
              fontWeight: '500',
              cursor: 'pointer',
            }}
          >
            <span>{activeWorkspace?.name || 'Repositories'}</span>
            <ChevronDownIcon size={12} />
          </button>

          {showWorkspaceMenu && workspaces.length > 0 && (
            <div style={{
              position: 'absolute',
              top: '100%',
              left: 0,
              marginTop: '6px',
              width: '240px',
              backgroundColor: '#0F172A',
              border: '1px solid #1E293B',
              borderRadius: '8px',
              boxShadow: '0 10px 25px -5px rgba(0,0,0,0.5)',
              padding: '6px',
              zIndex: 100,
            }}>
              <div style={{ padding: '6px 10px', fontSize: '11px', color: '#64748B', fontWeight: '600', textTransform: 'uppercase' }}>
                Workspaces
              </div>
              {workspaces.map((ws) => (
                <button
                  key={ws.id}
                  onClick={() => {
                    if (onSelectWorkspace) onSelectWorkspace(ws);
                    setShowWorkspaceMenu(false);
                  }}
                  style={{
                    display: 'flex',
                    alignItems: 'center',
                    justifyContent: 'space-between',
                    width: '100%',
                    padding: '8px 10px',
                    borderRadius: '6px',
                    backgroundColor: activeWorkspace?.id === ws.id ? '#1E293B' : 'transparent',
                    color: '#F1F5F9',
                    fontSize: '13px',
                    textAlign: 'left',
                    cursor: 'pointer',
                  }}
                >
                  <span style={{ overflow: 'hidden', textOverflow: 'ellipsis', whiteSpace: 'nowrap' }}>{ws.name}</span>
                  {activeWorkspace?.id === ws.id && (
                    <span style={{ fontSize: '11px', color: '#3B82F6', fontWeight: '600' }}>Active</span>
                  )}
                </button>
              ))}
            </div>
          )}
        </div>
      </div>

      {/* Right: Notifications & User Avatar */}
      <div style={{ display: 'flex', alignItems: 'center', gap: '16px' }}>
        {/* Notification Bell */}
        <button
          style={{
            position: 'relative',
            width: '36px',
            height: '36px',
            borderRadius: '8px',
            display: 'flex',
            alignItems: 'center',
            justifyContent: 'center',
            color: '#94A3B8',
            backgroundColor: 'transparent',
            cursor: 'pointer',
          }}
          onMouseOver={(e) => { e.currentTarget.style.color = '#FFFFFF'; }}
          onMouseOut={(e) => { e.currentTarget.style.color = '#94A3B8'; }}
        >
          <BellIcon size={18} />
          {unreadCount > 0 && (
            <span style={{
              position: 'absolute',
              top: '8px',
              right: '8px',
              width: '6px',
              height: '6px',
              borderRadius: '9999px',
              backgroundColor: '#3B82F6',
            }} />
          )}
        </button>

        {/* User Profile Pill */}
        <div style={{ position: 'relative' }}>
          <button
            onClick={() => setShowUserMenu(!showUserMenu)}
            style={{
              display: 'flex',
              alignItems: 'center',
              gap: '10px',
              backgroundColor: 'transparent',
              border: 'none',
              cursor: 'pointer',
              padding: '4px',
            }}
          >
            {currentUser?.avatar_url ? (
              <img
                src={currentUser.avatar_url}
                alt={displayName}
                style={{ width: '32px', height: '32px', borderRadius: '9999px', border: '1px solid #2563EB' }}
              />
            ) : (
              <div style={{
                width: '32px',
                height: '32px',
                borderRadius: '9999px',
                backgroundColor: '#2563EB',
                color: '#FFFFFF',
                fontSize: '14px',
                fontWeight: '700',
                display: 'flex',
                alignItems: 'center',
                justifyContent: 'center',
              }}>
                {initial}
              </div>
            )}
            <span style={{ fontSize: '14px', fontWeight: '600', color: '#F1F5F9' }}>
              {displayName}
            </span>
            <ChevronDownIcon size={12} />
          </button>

          {showUserMenu && (
            <div style={{
              position: 'absolute',
              top: '100%',
              right: 0,
              marginTop: '6px',
              width: '180px',
              backgroundColor: '#0F172A',
              border: '1px solid #1E293B',
              borderRadius: '8px',
              boxShadow: '0 10px 25px -5px rgba(0,0,0,0.5)',
              padding: '6px',
              zIndex: 100,
            }}>
              <div style={{ padding: '8px 10px', borderBottom: '1px solid #1E293B', marginBottom: '4px' }}>
                <div style={{ fontSize: '13px', fontWeight: '600', color: '#FFFFFF' }}>{displayName}</div>
                <div style={{ fontSize: '11px', color: '#64748B', overflow: 'hidden', textOverflow: 'ellipsis' }}>
                  {currentUser?.email || 'GitHub User'}
                </div>
              </div>
              <button
                onClick={onLogout}
                style={{
                  width: '100%',
                  padding: '8px 10px',
                  borderRadius: '6px',
                  backgroundColor: 'transparent',
                  color: '#EF4444',
                  fontSize: '13px',
                  textAlign: 'left',
                  cursor: 'pointer',
                }}
                onMouseOver={(e) => { e.currentTarget.style.backgroundColor = '#1E293B'; }}
                onMouseOut={(e) => { e.currentTarget.style.backgroundColor = 'transparent'; }}
              >
                Sign out
              </button>
            </div>
          )}
        </div>
      </div>
    </header>
  );
}
