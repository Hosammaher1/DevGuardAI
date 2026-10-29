import React, { useState, useMemo } from 'react';
import {
  SearchIcon,
  RefreshIcon,
  LockIcon,
  CodeIcon,
  GitHubIcon,
  ChevronDownIcon,
} from './Icons';

function formatRelativeTime(dateString) {
  if (!dateString) return 'Updated recently';
  const date = new Date(dateString);
  const now = new Date();
  const diffSec = Math.floor((now - date) / 1000);

  if (diffSec < 3600) {
    const mins = Math.max(1, Math.floor(diffSec / 60));
    return `Updated ${mins} minute${mins > 1 ? 's' : ''} ago`;
  }
  if (diffSec < 86400) {
    const hours = Math.floor(diffSec / 3600);
    return `Updated ${hours} hour${hours > 1 ? 's' : ''} ago`;
  }
  if (diffSec < 604800) {
    const days = Math.floor(diffSec / 86400);
    return `Updated ${days} day${days > 1 ? 's' : ''} ago`;
  }
  if (diffSec < 2592000) {
    const weeks = Math.floor(diffSec / 604800);
    return `Updated ${weeks} week${weeks > 1 ? 's' : ''} ago`;
  }
  const months = Math.floor(diffSec / 2592000);
  return `Updated ${months} month${months > 1 ? 's' : ''} ago`;
}

export default function RepositoriesScreen({
  repositories = [],
  isLoading = false,
  hasToken = true,
  onRefresh,
  onSelectRepo,
}) {
  const [searchQuery, setSearchQuery] = useState('');
  const [filterType, setFilterType] = useState('all'); // 'all', 'private', 'public'
  const [sortBy, setSortBy] = useState('updated');

  const counts = useMemo(() => {
    let priv = 0;
    let pub = 0;
    repositories.forEach((r) => {
      if (r.is_private) priv++;
      else pub++;
    });
    return {
      all: repositories.length,
      private: priv,
      public: pub,
    };
  }, [repositories]);

  const filteredRepositories = useMemo(() => {
    return repositories
      .filter((repo) => {
        if (filterType === 'private' && !repo.is_private) return false;
        if (filterType === 'public' && repo.is_private) return false;

        if (searchQuery.trim()) {
          const q = searchQuery.toLowerCase();
          const matchName = repo.name?.toLowerCase().includes(q);
          const matchOwner = repo.owner_login?.toLowerCase().includes(q);
          const matchDesc = repo.description?.toLowerCase().includes(q);
          if (!matchName && !matchOwner && !matchDesc) return false;
        }

        return true;
      })
      .sort((a, b) => {
        if (sortBy === 'updated') {
          return new Date(b.updated_at || 0) - new Date(a.updated_at || 0);
        }
        if (sortBy === 'name') {
          return (a.name || '').localeCompare(b.name || '');
        }
        return 0;
      });
  }, [repositories, filterType, searchQuery, sortBy]);

  return (
    <div style={{
      flex: 1,
      padding: '32px 40px',
      backgroundColor: '#0B0F19',
      color: '#FFFFFF',
      overflowY: 'auto',
      fontFamily: "'Plus Jakarta Sans', system-ui, -apple-system, sans-serif",
    }}>
      {/* Page Title & Search Bar */}
      <div style={{
        display: 'flex',
        alignItems: 'flex-start',
        justifyContent: 'space-between',
        marginBottom: '28px',
        flexWrap: 'wrap',
        gap: '16px',
      }}>
        <div>
          <h1 style={{ fontSize: '24px', fontWeight: '700', marginBottom: '6px', color: '#FFFFFF' }}>
            My Repositories
          </h1>
          <p style={{ fontSize: '14px', color: '#94A3B8' }}>
            Select a repository to start a security scan
          </p>
        </div>

        {/* Search & Refresh Tools */}
        <div style={{ display: 'flex', alignItems: 'center', gap: '10px' }}>
          <div style={{
            display: 'flex',
            alignItems: 'center',
            backgroundColor: '#161E2E',
            border: '1px solid #1E293B',
            borderRadius: '8px',
            padding: '8px 14px',
            width: '260px',
            gap: '8px',
          }}>
            <SearchIcon size={16} style={{ color: '#64748B' }} />
            <input
              type="text"
              placeholder="Search repositories..."
              value={searchQuery}
              onChange={(e) => setSearchQuery(e.target.value)}
              style={{
                backgroundColor: 'transparent',
                border: 'none',
                outline: 'none',
                color: '#FFFFFF',
                fontSize: '13px',
                width: '100%',
              }}
            />
          </div>

          <button
            onClick={onRefresh}
            disabled={isLoading}
            title="Refresh repositories"
            style={{
              width: '38px',
              height: '38px',
              borderRadius: '8px',
              backgroundColor: '#161E2E',
              border: '1px solid #1E293B',
              color: '#94A3B8',
              display: 'flex',
              alignItems: 'center',
              justifyContent: 'center',
              cursor: isLoading ? 'wait' : 'pointer',
              transition: 'all 0.15s ease',
            }}
            onMouseOver={(e) => { e.currentTarget.style.color = '#FFFFFF'; }}
            onMouseOut={(e) => { e.currentTarget.style.color = '#94A3B8'; }}
          >
            <RefreshIcon size={16} className={isLoading ? 'spinner' : ''} />
          </button>
        </div>
      </div>

      {/* Filter Tabs & Sorting Header */}
      <div style={{
        display: 'flex',
        alignItems: 'center',
        justifyContent: 'space-between',
        marginBottom: '20px',
        flexWrap: 'wrap',
        gap: '12px',
      }}>
        {/* Pills: All, Private, Public */}
        <div style={{ display: 'flex', alignItems: 'center', gap: '8px' }}>
          {/* All */}
          <button
            onClick={() => setFilterType('all')}
            style={{
              display: 'inline-flex',
              alignItems: 'center',
              gap: '6px',
              padding: '6px 14px',
              borderRadius: '9999px',
              fontSize: '13px',
              fontWeight: '600',
              backgroundColor: filterType === 'all' ? '#2563EB' : '#161E2E',
              color: filterType === 'all' ? '#FFFFFF' : '#94A3B8',
              border: filterType === 'all' ? '1px solid #3B82F6' : '1px solid #1E293B',
              cursor: 'pointer',
              transition: 'all 0.15s ease',
            }}
          >
            <span>All</span>
            <span style={{
              backgroundColor: filterType === 'all' ? '#1D4ED8' : '#0F172A',
              color: filterType === 'all' ? '#FFFFFF' : '#64748B',
              padding: '1px 6px',
              borderRadius: '9999px',
              fontSize: '11px',
            }}>
              {counts.all}
            </span>
          </button>

          {/* Private */}
          <button
            onClick={() => setFilterType('private')}
            style={{
              display: 'inline-flex',
              alignItems: 'center',
              gap: '6px',
              padding: '6px 14px',
              borderRadius: '9999px',
              fontSize: '13px',
              fontWeight: '600',
              backgroundColor: filterType === 'private' ? '#2563EB' : '#161E2E',
              color: filterType === 'private' ? '#FFFFFF' : '#94A3B8',
              border: filterType === 'private' ? '1px solid #3B82F6' : '1px solid #1E293B',
              cursor: 'pointer',
              transition: 'all 0.15s ease',
            }}
          >
            <span>Private</span>
            <span style={{
              backgroundColor: filterType === 'private' ? '#1D4ED8' : '#0F172A',
              color: filterType === 'private' ? '#FFFFFF' : '#64748B',
              padding: '1px 6px',
              borderRadius: '9999px',
              fontSize: '11px',
            }}>
              {counts.private}
            </span>
          </button>

          {/* Public */}
          <button
            onClick={() => setFilterType('public')}
            style={{
              display: 'inline-flex',
              alignItems: 'center',
              gap: '6px',
              padding: '6px 14px',
              borderRadius: '9999px',
              fontSize: '13px',
              fontWeight: '600',
              backgroundColor: filterType === 'public' ? '#2563EB' : '#161E2E',
              color: filterType === 'public' ? '#FFFFFF' : '#94A3B8',
              border: filterType === 'public' ? '1px solid #3B82F6' : '1px solid #1E293B',
              cursor: 'pointer',
              transition: 'all 0.15s ease',
            }}
          >
            <span>Public</span>
            <span style={{
              backgroundColor: filterType === 'public' ? '#1D4ED8' : '#0F172A',
              color: filterType === 'public' ? '#FFFFFF' : '#64748B',
              padding: '1px 6px',
              borderRadius: '9999px',
              fontSize: '11px',
            }}>
              {counts.public}
            </span>
          </button>
        </div>

        {/* Sort Dropdown */}
        <div style={{ display: 'flex', alignItems: 'center', gap: '8px' }}>
          <select
            value={sortBy}
            onChange={(e) => setSortBy(e.target.value)}
            style={{
              backgroundColor: '#161E2E',
              border: '1px solid #1E293B',
              borderRadius: '8px',
              color: '#94A3B8',
              padding: '6px 12px',
              fontSize: '13px',
              outline: 'none',
              cursor: 'pointer',
            }}
          >
            <option value="updated">Recently updated</option>
            <option value="name">Repository name</option>
          </select>
        </div>
      </div>

      {/* Repositories List */}
      <div style={{ display: 'flex', flexDirection: 'column', gap: '12px' }}>
        {isLoading && repositories.length === 0 ? (
          <div style={{
            padding: '60px',
            textAlign: 'center',
            backgroundColor: '#0F172A',
            border: '1px solid #1E293B',
            borderRadius: '12px',
            color: '#94A3B8',
          }}>
            <RefreshIcon size={24} className="spinner" style={{ marginBottom: '12px', color: '#3B82F6' }} />
            <p style={{ fontSize: '14px' }}>Discovering your GitHub repositories...</p>
          </div>
        ) : !hasToken && repositories.length === 0 ? (
          <div style={{
            padding: '48px 32px',
            textAlign: 'center',
            backgroundColor: '#0F172A',
            border: '1px solid #1E293B',
            borderRadius: '12px',
            maxWidth: '560px',
            margin: '40px auto',
          }}>
            <GitHubIcon size={36} style={{ color: '#3B82F6', marginBottom: '16px' }} />
            <h3 style={{ fontSize: '18px', fontWeight: '700', marginBottom: '8px', color: '#FFFFFF' }}>
              Connect GitHub to discover repositories
            </h3>
            <p style={{ fontSize: '14px', color: '#94A3B8', marginBottom: '24px', lineHeight: '1.5' }}>
              Sign in with your GitHub account to automatically discover all your private and public repositories with zero manual configuration.
            </p>
            <a
              href="/auth/github/redirect"
              style={{
                display: 'inline-flex',
                alignItems: 'center',
                gap: '8px',
                backgroundColor: '#2563EB',
                color: '#FFFFFF',
                padding: '10px 24px',
                borderRadius: '8px',
                fontSize: '14px',
                fontWeight: '600',
                textDecoration: 'none',
              }}
            >
              <GitHubIcon size={18} />
              <span>Connect with GitHub</span>
            </a>
          </div>
        ) : filteredRepositories.length === 0 ? (
          <div style={{
            padding: '48px',
            textAlign: 'center',
            backgroundColor: '#0F172A',
            border: '1px solid #1E293B',
            borderRadius: '12px',
            color: '#94A3B8',
          }}>
            <p style={{ fontSize: '15px', color: '#FFFFFF', fontWeight: '600', marginBottom: '6px' }}>
              No repositories match your filter
            </p>
            <p style={{ fontSize: '13px', color: '#64748B' }}>
              Try searching with another keyword or switching filter tabs.
            </p>
          </div>
        ) : (
          filteredRepositories.map((repo) => {
            const isPrivate = repo.is_private;

            return (
              <div
                key={repo.id}
                style={{
                  display: 'flex',
                  alignItems: 'center',
                  justifyContent: 'space-between',
                  padding: '16px 20px',
                  backgroundColor: '#0F172A',
                  border: '1px solid #1E293B',
                  borderRadius: '12px',
                  transition: 'border-color 0.15s ease',
                }}
                onMouseOver={(e) => { e.currentTarget.style.borderColor = '#334155'; }}
                onMouseOut={(e) => { e.currentTarget.style.borderColor = '#1E293B'; }}
              >
                {/* Left: Icon, Name, Visibility Badge, Updated Info */}
                <div style={{ display: 'flex', alignItems: 'center', gap: '14px' }}>
                  {/* Repo Type Icon */}
                  <div style={{
                    color: isPrivate ? '#94A3B8' : '#94A3B8',
                    display: 'flex',
                    alignItems: 'center',
                    justifyContent: 'center',
                  }}>
                    {isPrivate ? <LockIcon size={18} /> : <CodeIcon size={18} />}
                  </div>

                  {/* Name & Metadata */}
                  <div style={{ display: 'flex', alignItems: 'center', gap: '12px', flexWrap: 'wrap' }}>
                    <span style={{ fontSize: '15px', fontWeight: '600', color: '#FFFFFF' }}>
                      {repo.name}
                    </span>

                    {/* Badge: Private / Public */}
                    <span style={{
                      padding: '2px 8px',
                      borderRadius: '9999px',
                      fontSize: '11px',
                      fontWeight: '600',
                      backgroundColor: isPrivate ? '#450A0A' : '#064E3B',
                      color: isPrivate ? '#F87171' : '#34D399',
                      border: isPrivate ? '1px solid #7F1D1D' : '1px solid #065F46',
                    }}>
                      {isPrivate ? 'Private' : 'Public'}
                    </span>

                    {/* Language if present */}
                    {repo.language && (
                      <span style={{ fontSize: '12px', color: '#94A3B8' }}>
                        • {repo.language}
                      </span>
                    )}

                    {/* Relative updated timestamp */}
                    <span style={{ fontSize: '12px', color: '#64748B' }}>
                      {formatRelativeTime(repo.updated_at)}
                    </span>
                  </div>
                </div>

                {/* Right Action: Select Button */}
                <button
                  onClick={() => onSelectRepo && onSelectRepo(repo)}
                  style={{
                    backgroundColor: '#2563EB',
                    color: '#FFFFFF',
                    padding: '8px 20px',
                    borderRadius: '8px',
                    fontSize: '13px',
                    fontWeight: '600',
                    cursor: 'pointer',
                    transition: 'background-color 0.15s ease',
                  }}
                  onMouseOver={(e) => { e.currentTarget.style.backgroundColor = '#1D4ED8'; }}
                  onMouseOut={(e) => { e.currentTarget.style.backgroundColor = '#2563EB'; }}
                >
                  Select
                </button>
              </div>
            );
          })
        )}
      </div>
    </div>
  );
}
