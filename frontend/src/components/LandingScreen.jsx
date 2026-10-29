import React, { useState } from 'react';
import {
  LogoIcon,
  GitHubIcon,
  KeyIcon,
  SecurityShieldIcon,
  WandIcon,
  ReportsChartIcon,
  ArrowRightIcon,
} from './Icons';

export default function LandingScreen({ onDemoLogin, isSubmittingCode, authError }) {
  const [showCodeInput, setShowCodeInput] = useState(false);
  const [code, setCode] = useState('');

  const handleDemoSubmit = (e) => {
    e.preventDefault();
    if (code.trim() && onDemoLogin) {
      onDemoLogin(code.trim());
    }
  };

  return (
    <div style={{
      minHeight: '100vh',
      backgroundColor: '#090D16',
      color: '#FFFFFF',
      display: 'flex',
      flexDirection: 'column',
      fontFamily: "'Plus Jakarta Sans', system-ui, -apple-system, sans-serif",
    }}>
      {/* Top Navigation */}
      <header style={{
        display: 'flex',
        alignItems: 'center',
        justifyContent: 'space-between',
        padding: '24px 48px',
        maxWidth: '1280px',
        margin: '0 auto',
        width: '100%',
        boxSizing: 'border-box',
      }}>
        <div style={{ display: 'flex', alignItems: 'center', gap: '10px' }}>
          <LogoIcon size={32} />
          <span style={{ fontSize: '20px', fontWeight: '700', letterSpacing: '-0.3px', color: '#FFFFFF' }}>
            DevGuard<span style={{ color: '#3B82F6' }}>AI</span>
          </span>
        </div>

        <nav style={{ display: 'flex', alignItems: 'center', gap: '32px' }}>
          <a href="#features" style={{ color: '#94A3B8', fontSize: '14px', fontWeight: '500', transition: 'color 0.2s' }}>
            Features
          </a>
          <a href="#pricing" style={{ color: '#94A3B8', fontSize: '14px', fontWeight: '500', transition: 'color 0.2s' }}>
            Pricing
          </a>
          <a href="#docs" style={{ color: '#94A3B8', fontSize: '14px', fontWeight: '500', transition: 'color 0.2s' }}>
            Docs
          </a>
        </nav>
      </header>

      {/* Main Hero Section */}
      <main style={{
        flex: 1,
        display: 'flex',
        flexDirection: 'column',
        alignItems: 'center',
        justifyContent: 'center',
        padding: '40px 24px 60px',
        textAlign: 'center',
        maxWidth: '760px',
        margin: '0 auto',
        width: '100%',
        boxSizing: 'border-box',
      }}>
        <h1 style={{
          fontSize: '48px',
          fontWeight: '800',
          lineHeight: '1.2',
          letterSpacing: '-1px',
          marginBottom: '20px',
          color: '#FFFFFF',
        }}>
          Secure Your Code<br />With AI
        </h1>

        <p style={{
          fontSize: '17px',
          lineHeight: '1.6',
          color: '#94A3B8',
          maxWidth: '560px',
          marginBottom: '36px',
        }}>
          Automated security scans, smart fix suggestions, and continuous protection for your repositories.
        </p>

        {/* Primary Action: Continue with GitHub */}
        <a
          href="/auth/github/redirect"
          style={{
            display: 'inline-flex',
            alignItems: 'center',
            justifyContent: 'center',
            gap: '12px',
            backgroundColor: '#FFFFFF',
            color: '#090D16',
            fontSize: '15px',
            fontWeight: '600',
            padding: '14px 32px',
            borderRadius: '9999px',
            textDecoration: 'none',
            boxShadow: '0 4px 14px rgba(255, 255, 255, 0.15)',
            transition: 'all 0.2s ease',
            cursor: 'pointer',
          }}
          onMouseOver={(e) => { e.currentTarget.style.backgroundColor = '#F1F5F9'; }}
          onMouseOut={(e) => { e.currentTarget.style.backgroundColor = '#FFFFFF'; }}
        >
          <GitHubIcon size={20} />
          <span>Continue with GitHub</span>
          <ArrowRightIcon size={18} />
        </a>

        {/* Divider */}
        <div style={{
          display: 'flex',
          alignItems: 'center',
          gap: '16px',
          width: '260px',
          margin: '24px 0',
        }}>
          <div style={{ flex: 1, height: '1px', backgroundColor: '#1E293B' }} />
          <span style={{ fontSize: '13px', color: '#64748B', fontWeight: '500' }}>or</span>
          <div style={{ flex: 1, height: '1px', backgroundColor: '#1E293B' }} />
        </div>

        {/* Secondary Action: Enter Invitation Code */}
        {!showCodeInput ? (
          <button
            onClick={() => setShowCodeInput(true)}
            style={{
              display: 'inline-flex',
              alignItems: 'center',
              gap: '10px',
              backgroundColor: '#0F172A',
              color: '#3B82F6',
              border: '1px solid #1E3A8A',
              padding: '10px 24px',
              borderRadius: '9999px',
              fontSize: '14px',
              fontWeight: '500',
              cursor: 'pointer',
              transition: 'all 0.2s ease',
            }}
            onMouseOver={(e) => { e.currentTarget.style.backgroundColor = '#1E293B'; }}
            onMouseOut={(e) => { e.currentTarget.style.backgroundColor = '#0F172A'; }}
          >
            <KeyIcon size={16} />
            <span>Enter Invitation Code</span>
          </button>
        ) : (
          <form
            onSubmit={handleDemoSubmit}
            style={{
              display: 'flex',
              flexDirection: 'column',
              alignItems: 'center',
              gap: '12px',
              width: '100%',
              maxWidth: '380px',
              backgroundColor: '#0F172A',
              padding: '20px',
              borderRadius: '16px',
              border: '1px solid #1E293B',
            }}
          >
            <div style={{ width: '100%', display: 'flex', gap: '8px' }}>
              <input
                type="text"
                value={code}
                onChange={(e) => setCode(e.target.value)}
                placeholder="Enter invitation code..."
                autoFocus
                style={{
                  flex: 1,
                  backgroundColor: '#090D16',
                  border: '1px solid #334155',
                  color: '#FFFFFF',
                  padding: '10px 14px',
                  borderRadius: '8px',
                  fontSize: '14px',
                  outline: 'none',
                }}
              />
              <button
                type="submit"
                disabled={isSubmittingCode || !code.trim()}
                style={{
                  backgroundColor: '#2563EB',
                  color: '#FFFFFF',
                  padding: '10px 18px',
                  borderRadius: '8px',
                  fontSize: '14px',
                  fontWeight: '600',
                  opacity: isSubmittingCode || !code.trim() ? 0.6 : 1,
                  cursor: isSubmittingCode ? 'wait' : 'pointer',
                }}
              >
                {isSubmittingCode ? 'Entering...' : 'Enter'}
              </button>
            </div>
            {authError && (
              <div style={{ color: '#EF4444', fontSize: '13px', width: '100%', textAlign: 'left' }}>
                {authError}
              </div>
            )}
            <button
              type="button"
              onClick={() => setShowCodeInput(false)}
              style={{ color: '#64748B', fontSize: '12px', cursor: 'pointer' }}
            >
              Cancel
            </button>
          </form>
        )}

        {/* Feature Highlights Grid */}
        <div style={{
          display: 'grid',
          gridTemplateColumns: 'repeat(3, 1fr)',
          gap: '20px',
          width: '100%',
          marginTop: '64px',
        }}>
          <div style={{
            display: 'flex',
            alignItems: 'center',
            gap: '12px',
            backgroundColor: '#0F172A',
            border: '1px solid #1E293B',
            borderRadius: '12px',
            padding: '16px 20px',
            boxSizing: 'border-box',
          }}>
            <SecurityShieldIcon size={22} />
            <span style={{ fontSize: '14px', fontWeight: '600', color: '#E2E8F0' }}>
              Security Scanning
            </span>
          </div>

          <div style={{
            display: 'flex',
            alignItems: 'center',
            gap: '12px',
            backgroundColor: '#0F172A',
            border: '1px solid #1E293B',
            borderRadius: '12px',
            padding: '16px 20px',
            boxSizing: 'border-box',
          }}>
            <WandIcon size={22} />
            <span style={{ fontSize: '14px', fontWeight: '600', color: '#E2E8F0' }}>
              AI Fix Suggestions
            </span>
          </div>

          <div style={{
            display: 'flex',
            alignItems: 'center',
            gap: '12px',
            backgroundColor: '#0F172A',
            border: '1px solid #1E293B',
            borderRadius: '12px',
            padding: '16px 20px',
            boxSizing: 'border-box',
          }}>
            <ReportsChartIcon size={22} />
            <span style={{ fontSize: '14px', fontWeight: '600', color: '#E2E8F0' }}>
              Real-time Reports
            </span>
          </div>
        </div>
      </main>
    </div>
  );
}
