import React, { useState, useEffect } from 'react';

export default function App() {
  const [currentUser, setCurrentUser] = useState(null);
  const [sessionStatus, setSessionStatus] = useState('checking'); // checking, guest, authenticated, error
  const [isDemoSession, setIsDemoSession] = useState(false);
  const [workspaces, setWorkspaces] = useState([]);
  const [activeWorkspace, setActiveWorkspace] = useState(null);
  const [activeTab, setActiveTab] = useState('dashboard'); // dashboard, scans, fixes, projects, repos, usage, notifications

  // Demo Login State
  const [invitationCode, setInvitationCode] = useState('');
  const [authError, setAuthError] = useState(null);
  const [isSubmittingCode, setIsSubmittingCode] = useState(false);

  // Workspace Data
  const [metrics, setMetrics] = useState({
    total_scans: 0,
    total_tests: 0,
    total_passed: 0,
    total_failed: 0,
    pass_rate: 100,
    active_repositories: 0,
  });
  const [scans, setScans] = useState([]);
  const [currentScan, setCurrentScan] = useState(null);
  const [selectedTest, setSelectedTest] = useState(null);
  const [projects, setProjects] = useState([]);
  const [repositories, setRepositories] = useState([]);
  const [fixPlans, setFixPlans] = useState([]);
  const [selectedPlan, setSelectedPlan] = useState(null);
  const [usageData, setUsageData] = useState(null);
  const [notifications, setNotifications] = useState([]);
  const [unreadNotifications, setUnreadNotifications] = useState(0);

  // UI State
  const [isScanning, setIsScanning] = useState(false);
  const [scanStep, setScanStep] = useState('');
  const [filter, setFilter] = useState('all'); // all, failed, passed
  const [actionMessage, setActionMessage] = useState(null);
  const [isProcessingFix, setIsProcessingFix] = useState(false);

  // 1. Initial Authentication Check
  useEffect(() => {
    checkSession();
  }, []);

  const checkSession = async () => {
    setSessionStatus('checking');
    try {
      const res = await fetch('/api/session', { credentials: 'same-origin' });
      if (res.ok) {
        const data = await res.json();
        setCurrentUser(data.user);
        setIsDemoSession(Boolean(data.is_demo));
        setSessionStatus('authenticated');
        const wsList = data.workspaces || [];
        setWorkspaces(wsList);
        if (wsList.length > 0) {
          const first = wsList[0];
          setActiveWorkspace(first);
          loadWorkspaceData(first.id);
        }
      } else {
        setCurrentUser(null);
        setWorkspaces([]);
        setActiveWorkspace(null);
        setIsDemoSession(false);
        setSessionStatus(res.status === 401 ? 'guest' : 'error');
      }
    } catch (err) {
      console.error('Session check failed:', err);
      setSessionStatus('error');
    }
  };

  const loadWorkspaceData = async (workspaceId) => {
    if (!workspaceId) return;
    try {
      // 1. Metrics & Latest Scan
      const mRes = await fetch(`/workspaces/${workspaceId}/metrics`, { credentials: 'same-origin' });
      if (mRes.ok) {
        const mData = await mRes.json();
        if (mData.metrics) setMetrics(mData.metrics);
        if (mData.latest_scan) {
          setCurrentScan(mData.latest_scan);
          const failed = mData.latest_scan.test_results?.find(t => t.status === 'failed');
          setSelectedTest(failed || mData.latest_scan.test_results?.[0] || null);
        }
      }

      // 2. Scans History
      const sRes = await fetch(`/workspaces/${workspaceId}/scans`, { credentials: 'same-origin' });
      if (sRes.ok) {
        const sData = await sRes.json();
        setScans(sData.scans || []);
      }

      // 3. Projects & Targets
      const pRes = await fetch(`/workspaces/${workspaceId}/projects`, { credentials: 'same-origin' });
      if (pRes.ok) {
        const pData = await pRes.json();
        setProjects(pData.projects || []);
      }

      // 4. Repositories
      const rRes = await fetch(`/workspaces/${workspaceId}/repositories`, { credentials: 'same-origin' });
      if (rRes.ok) {
        const rData = await rRes.json();
        setRepositories(rData.repositories || []);
      }

      // 5. Fix Plans
      const fRes = await fetch(`/workspaces/${workspaceId}/fix-plans`, { credentials: 'same-origin' });
      if (fRes.ok) {
        const fData = await fRes.json();
        const list = fData.fix_plans || [];
        setFixPlans(list);
        if (list.length > 0 && !selectedPlan) {
          setSelectedPlan(list[0]);
        }
      }

      // 6. Usage & Health
      const uRes = await fetch(`/workspaces/${workspaceId}/usage`, { credentials: 'same-origin' });
      if (uRes.ok) {
        const uData = await uRes.json();
        setUsageData(uData);
      }

      // 7. Notifications
      const nRes = await fetch(`/workspaces/${workspaceId}/notifications`, { credentials: 'same-origin' });
      if (nRes.ok) {
        const nData = await nRes.json();
        setNotifications(nData.notifications || []);
        setUnreadNotifications(nData.unread_count || 0);
      }
    } catch (err) {
      console.error('Error loading workspace data:', err);
    }
  };

  const handleDemoLogin = async (e) => {
    e.preventDefault();
    const code = invitationCode.trim();
    if (!code) {
      setAuthError('Please enter an invitation code.');
      return;
    }

    setAuthError(null);
    setIsSubmittingCode(true);

    try {
      const res = await fetch('/auth/demo', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
        },
        credentials: 'same-origin',
        body: JSON.stringify({ invitation_code: code }),
      });

      const data = await res.json().catch(() => ({}));

      if (res.ok) {
        setCurrentUser(data.user);
        setIsDemoSession(true);
        setSessionStatus('authenticated');
        if (data.workspace) {
          setWorkspaces([data.workspace]);
          setActiveWorkspace(data.workspace);
          loadWorkspaceData(data.workspace.id);
        } else {
          checkSession();
        }
      } else {
        setAuthError(data.message || 'Invalid or expired invitation code.');
      }
    } catch (err) {
      setAuthError('Connection error while authenticating. Please try again.');
    } finally {
      setIsSubmittingCode(false);
    }
  };

  const handleLogout = async () => {
    try {
      await fetch('/logout', {
        method: 'POST',
        headers: {
          'Accept': 'application/json',
        },
        credentials: 'same-origin',
      });
    } catch (err) {
      console.warn('Logout network error:', err);
    } finally {
      setCurrentUser(null);
      setWorkspaces([]);
      setActiveWorkspace(null);
      setIsDemoSession(false);
      setSessionStatus('guest');
      setInvitationCode('');
      setAuthError(null);
    }
  };

  const handleRunScan = async () => {
    if (!activeWorkspace || isScanning) return;
    setIsScanning(true);
    setActionMessage(null);

    const repoId = repositories[0]?.id;
    if (!repoId) {
      setActionMessage({ type: 'warning', text: 'Please connect a repository first before starting a scan.' });
      setIsScanning(false);
      return;
    }

    try {
      setScanStep('Dispatching scan task to execution runner...');
      const res = await fetch(`/workspaces/${activeWorkspace.id}/scans`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
        credentials: 'same-origin',
        body: JSON.stringify({ repository_id: repoId, trigger: 'manual' }),
      });

      const data = await res.json().catch(() => ({}));

      if (res.ok) {
        setCurrentScan(data.scan);
        loadWorkspaceData(activeWorkspace.id);
        const failed = data.scan.test_results?.find(t => t.status === 'failed');
        setSelectedTest(failed || data.scan.test_results?.[0] || null);
        setActionMessage({ type: 'success', text: `Scan #${data.scan.id.slice(-6)} completed successfully!` });
      } else if (res.status === 503) {
        setActionMessage({
          type: 'warning',
          text: 'Runner Isolation Policy Active: Live Playwright browser execution is disabled on Render Free to prevent untrusted execution on the control plane. Explore the persisted sample scans, test failures, and AI fix plans below.',
        });
      } else {
        setActionMessage({
          type: 'danger',
          text: `Scan request rejected (${res.status}): ${data.message || data.reason || 'Operation failed'}`,
        });
      }
    } catch (err) {
      setActionMessage({ type: 'danger', text: 'Error triggering scan: ' + err.message });
    } finally {
      setIsScanning(false);
      setScanStep('');
    }
  };

  const handleProposeFix = async () => {
    if (!activeWorkspace || !currentScan) return;
    setIsProcessingFix(true);
    setActionMessage(null);

    try {
      const res = await fetch(`/workspaces/${activeWorkspace.id}/scans/${currentScan.id}/fix-plans`, {
        method: 'POST',
        headers: { 'Accept': 'application/json' },
        credentials: 'same-origin',
      });
      const data = await res.json().catch(() => ({}));

      if (res.ok) {
        setSelectedPlan(data.fix_plan);
        setActiveTab('fixes');
        loadWorkspaceData(activeWorkspace.id);
        setActionMessage({ type: 'success', text: 'AI Fix Plan created! Ready for review and approval.' });
      } else {
        setActionMessage({ type: 'danger', text: 'Could not propose fix: ' + (data.reason || data.error || data.message) });
      }
    } catch (err) {
      setActionMessage({ type: 'danger', text: 'Error proposing fix: ' + err.message });
    } finally {
      setIsProcessingFix(false);
    }
  };

  const handleApprovePlan = async (planId) => {
    if (!activeWorkspace) return;
    setIsProcessingFix(true);
    setActionMessage(null);

    try {
      const res = await fetch(`/workspaces/${activeWorkspace.id}/fix-plans/${planId}/approvals`, {
        method: 'POST',
        headers: { 'Accept': 'application/json' },
        credentials: 'same-origin',
      });
      const data = await res.json().catch(() => ({}));

      if (res.ok) {
        setSelectedPlan(data.fix_plan);
        loadWorkspaceData(activeWorkspace.id);
        setActionMessage({ type: 'success', text: 'Fix Plan approved! Ready for isolated patch verification.' });
      } else {
        setActionMessage({ type: 'danger', text: 'Approval failed: ' + (data.reason || data.error || data.message) });
      }
    } catch (err) {
      setActionMessage({ type: 'danger', text: 'Error approving fix: ' + err.message });
    } finally {
      setIsProcessingFix(false);
    }
  };

  const handleVerifyPlan = async (planId) => {
    if (!activeWorkspace) return;
    setIsProcessingFix(true);
    setActionMessage(null);

    try {
      const res = await fetch(`/workspaces/${activeWorkspace.id}/fix-plans/${planId}/verify`, {
        method: 'POST',
        headers: { 'Accept': 'application/json' },
        credentials: 'same-origin',
      });
      const data = await res.json().catch(() => ({}));

      if (res.ok) {
        setSelectedPlan(data.fix_plan);
        loadWorkspaceData(activeWorkspace.id);
        setActionMessage({ type: 'success', text: 'Patch verified in fresh isolation! Ready to publish Draft PR.' });
      } else {
        setActionMessage({ type: 'danger', text: 'Verification check failed: ' + (data.reason || data.error || data.message) });
      }
    } catch (err) {
      setActionMessage({ type: 'danger', text: 'Error verifying patch: ' + err.message });
    } finally {
      setIsProcessingFix(false);
    }
  };

  const handlePublishPr = async (planId) => {
    if (!activeWorkspace) return;
    setIsProcessingFix(true);
    setActionMessage(null);

    try {
      const res = await fetch(`/workspaces/${activeWorkspace.id}/fix-plans/${planId}/publish-pr`, {
        method: 'POST',
        headers: { 'Accept': 'application/json' },
        credentials: 'same-origin',
      });
      const data = await res.json().catch(() => ({}));

      if (res.ok) {
        setSelectedPlan(data.plan);
        loadWorkspaceData(activeWorkspace.id);
        setActionMessage({
          type: 'success',
          text: `Draft PR #${data.pr_number} created on GitHub! Branch: ${data.branch}`,
        });
      } else if (res.status === 403 && data.error === 'demo_read_only') {
        setActionMessage({
          type: 'warning',
          text: 'Protective Policy: GitHub Draft PR creation is disabled in the shared demo environment to prevent automated changes to external repositories.',
        });
      } else {
        setActionMessage({
          type: 'danger',
          text: 'PR publication failed: ' + (data.message || data.reason || data.error || 'Operation denied'),
        });
      }
    } catch (err) {
      setActionMessage({ type: 'danger', text: 'Error publishing PR: ' + err.message });
    } finally {
      setIsProcessingFix(false);
    }
  };

  const filteredTests = currentScan?.test_results?.filter(t => {
    if (filter === 'failed') return t.status === 'failed';
    if (filter === 'passed') return t.status === 'passed';
    return true;
  }) || [];

  // ==========================================
  // VIEW: GUEST / DEMO LOGIN
  // ==========================================
  if (sessionStatus !== 'authenticated') {
    return (
      <main style={{ minHeight: '100vh', display: 'flex', alignItems: 'center', justifyContent: 'center', background: 'radial-gradient(circle at 50% 20%, #1e293b 0%, #090d16 100%)', padding: '1.5rem' }}>
        <div style={{ maxWidth: '440px', width: '100%', background: 'var(--bg-card)', border: '1px solid var(--border-color)', borderRadius: '12px', padding: '2.5rem 2rem', boxShadow: '0 20px 40px rgba(0,0,0,0.5)' }}>
          {/* BRAND HEADER */}
          <div style={{ textAlign: 'center', marginBottom: '2rem' }}>
            <div style={{ width: '56px', height: '56px', margin: '0 auto 1rem', borderRadius: '12px', background: 'linear-gradient(135deg, #3b82f6, #8b5cf6)', display: 'flex', alignItems: 'center', justifyContent: 'center', fontSize: '1.75rem', boxShadow: '0 8px 20px var(--primary-glow)' }}>
              🛡️
            </div>
            <h1 style={{ fontSize: '1.6rem', fontWeight: 800, color: 'var(--text-main)', letterSpacing: '-0.02em', marginBottom: '0.25rem' }}>
              DevGuard AI
            </h1>
            <p style={{ fontSize: '0.85rem', color: 'var(--text-muted)' }}>
              Autonomous Software Quality & Security Platform
            </p>
          </div>

          {/* CHECKING STATE */}
          {sessionStatus === 'checking' && (
            <div style={{ textAlign: 'center', color: 'var(--text-dim)', fontSize: '0.9rem', padding: '1rem' }}>
              Verifying active session...
            </div>
          )}

          {/* DEMO INVITATION FORM */}
          {sessionStatus !== 'checking' && (
            <div>
              <div style={{ background: 'rgba(59, 130, 246, 0.08)', border: '1px solid rgba(59, 130, 246, 0.25)', borderRadius: '8px', padding: '0.9rem 1rem', marginBottom: '1.5rem' }}>
                <div style={{ fontSize: '0.78rem', fontWeight: 700, color: 'var(--primary)', textTransform: 'uppercase', letterSpacing: '0.04em' }}>
                  Interactive Demo Access
                </div>
                <div style={{ fontSize: '0.82rem', color: 'var(--text-muted)', marginTop: '2px' }}>
                  Enter your invitation code to access the isolated live preview.
                </div>
              </div>

              {authError && (
                <div style={{ background: 'var(--danger-bg)', border: '1px solid var(--danger)', borderRadius: '6px', padding: '0.75rem', marginBottom: '1rem', color: 'var(--text-main)', fontSize: '0.84rem' }}>
                  {authError}
                </div>
              )}

              <form onSubmit={handleDemoLogin} style={{ display: 'flex', flexDirection: 'column', gap: '0.85rem' }}>
                <div>
                  <label htmlFor="invitation_code" style={{ display: 'block', fontSize: '0.8rem', fontWeight: 600, color: 'var(--text-dim)', marginBottom: '0.35rem' }}>
                    Invitation Code
                  </label>
                  <input
                    id="invitation_code"
                    type="password"
                    value={invitationCode}
                    onChange={(e) => setInvitationCode(e.target.value)}
                    placeholder="Enter invitation code"
                    autoFocus
                    required
                    style={{
                      width: '100%',
                      padding: '0.75rem 1rem',
                      background: '#090d16',
                      border: '1px solid var(--border-color)',
                      borderRadius: '6px',
                      color: '#fff',
                      fontSize: '0.9rem',
                      fontFamily: 'var(--font-mono)',
                    }}
                  />
                </div>

                <button
                  type="submit"
                  disabled={isSubmittingCode}
                  style={{
                    background: isSubmittingCode ? 'var(--border-color)' : 'var(--primary)',
                    color: '#fff',
                    padding: '0.75rem 1rem',
                    borderRadius: '6px',
                    fontWeight: 700,
                    fontSize: '0.92rem',
                    boxShadow: isSubmittingCode ? 'none' : '0 4px 12px var(--primary-glow)',
                    cursor: isSubmittingCode ? 'not-allowed' : 'pointer',
                    transition: 'all 0.15s ease',
                  }}
                >
                  {isSubmittingCode ? 'Authenticating...' : 'Enter Demo →'}
                </button>
              </form>

              {/* SECONDARY GITHUB OAUTH OPTION */}
              <div style={{ marginTop: '2rem', paddingTop: '1.25rem', borderTop: '1px solid var(--border-color)', textAlign: 'center' }}>
                <div style={{ fontSize: '0.75rem', color: 'var(--text-dim)', marginBottom: '0.75rem', textTransform: 'uppercase', letterSpacing: '0.05em' }}>
                  Developer Access
                </div>
                <a
                  href="/auth/github/redirect"
                  style={{
                    display: 'inline-flex',
                    alignItems: 'center',
                    gap: '8px',
                    fontSize: '0.86rem',
                    fontWeight: 600,
                    color: 'var(--text-muted)',
                    textDecoration: 'none',
                    padding: '0.5rem 1rem',
                    borderRadius: '6px',
                    background: 'var(--bg-secondary)',
                    border: '1px solid var(--border-color)',
                  }}
                >
                  <span>Sign in with GitHub ↗</span>
                </a>
              </div>
            </div>
          )}
        </div>
      </main>
    );
  }

  // ==========================================
  // VIEW: AUTHENTICATED DEMO WORKSPACE
  // ==========================================
  return (
    <div style={{ display: 'flex', minHeight: '100vh', background: 'var(--bg-primary)' }}>
      {/* SIDEBAR NAVIGATION */}
      <aside style={{ width: '270px', borderRight: '1px solid var(--border-color)', background: 'var(--bg-secondary)', display: 'flex', flexDirection: 'column' }}>
        <div style={{ padding: '1.25rem', borderBottom: '1px solid var(--border-color)' }}>
          <div style={{ display: 'flex', alignItems: 'center', gap: '0.75rem' }}>
            <div style={{ width: '38px', height: '38px', borderRadius: '8px', background: 'linear-gradient(135deg, #3b82f6, #8b5cf6)', display: 'flex', alignItems: 'center', justifyContent: 'center', fontWeight: 'bold', fontSize: '1.25rem', color: '#fff' }}>
              🛡️
            </div>
            <div>
              <div style={{ fontWeight: '800', fontSize: '1.1rem', letterSpacing: '-0.02em', color: 'var(--text-main)' }}>DevGuard AI</div>
              <div style={{ fontSize: '0.7rem', color: 'var(--text-dim)', textTransform: 'uppercase', letterSpacing: '0.05em' }}>Autonomous QA & Security</div>
            </div>
          </div>
        </div>

        {/* WORKSPACE SELECTOR & DEMO BADGE */}
        <div style={{ padding: '0.85rem 1.25rem', borderBottom: '1px solid var(--border-color)', background: 'rgba(59, 130, 246, 0.05)' }}>
          <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
            <div style={{ fontSize: '0.7rem', color: 'var(--text-dim)', textTransform: 'uppercase', fontWeight: 600 }}>Active Workspace</div>
            {isDemoSession && (
              <span style={{ fontSize: '0.65rem', padding: '1px 6px', borderRadius: '4px', background: 'var(--warning-bg)', color: 'var(--warning)', fontWeight: 800 }}>
                DEMO PREVIEW
              </span>
            )}
          </div>
          <div style={{ fontWeight: 600, fontSize: '0.88rem', color: 'var(--text-main)', marginTop: '3px', display: 'flex', alignItems: 'center', gap: '6px' }}>
            <span style={{ width: '8px', height: '8px', borderRadius: '50%', background: 'var(--success)' }}></span>
            {activeWorkspace ? activeWorkspace.name : 'Demo Workspace'}
          </div>
        </div>

        {/* NAV TABS */}
        <nav style={{ padding: '1rem 0.75rem', flex: 1, display: 'flex', flexDirection: 'column', gap: '4px' }}>
          {[
            { id: 'dashboard', label: 'Dashboard', icon: '📊' },
            { id: 'scans', label: 'Scans & Reports', icon: '🔬', badge: scans.length },
            { id: 'fixes', label: 'AI Fix Plans', icon: '⚡', badge: fixPlans.length },
            { id: 'projects', label: 'Projects & Targets', icon: '📁', badge: projects.length },
            { id: 'repos', label: 'Repositories', icon: '📦', badge: repositories.length },
            { id: 'usage', label: 'Credits & Usage', icon: '💳' },
            { id: 'notifications', label: 'Notifications', icon: '🔔', badge: unreadNotifications },
          ].map(tab => (
            <button
              key={tab.id}
              onClick={() => setActiveTab(tab.id)}
              style={{
                display: 'flex',
                alignItems: 'center',
                justifyContent: 'space-between',
                padding: '0.65rem 0.85rem',
                borderRadius: '6px',
                color: activeTab === tab.id ? 'var(--text-main)' : 'var(--text-muted)',
                background: activeTab === tab.id ? 'var(--border-color)' : 'transparent',
                fontWeight: activeTab === tab.id ? 600 : 500,
                fontSize: '0.88rem',
                textAlign: 'left',
                transition: 'all 0.15s ease',
              }}
            >
              <div style={{ display: 'flex', alignItems: 'center', gap: '10px' }}>
                <span>{tab.icon}</span>
                <span>{tab.label}</span>
              </div>
              {tab.badge !== undefined && tab.badge > 0 && (
                <span style={{ fontSize: '0.7rem', padding: '2px 7px', borderRadius: '10px', background: activeTab === tab.id ? 'var(--primary)' : 'rgba(255,255,255,0.1)', color: '#fff' }}>
                  {tab.badge}
                </span>
              )}
            </button>
          ))}
        </nav>

        {/* USER PROFILE & LOGOUT */}
        <div style={{ padding: '0.9rem 1.1rem', borderTop: '1px solid var(--border-color)', background: 'var(--bg-card)', display: 'flex', alignItems: 'center', justifyContent: 'space-between' }}>
          <div style={{ display: 'flex', alignItems: 'center', gap: '10px', overflow: 'hidden' }}>
            <div style={{ width: '32px', height: '32px', borderRadius: '50%', background: 'var(--primary)', display: 'flex', alignItems: 'center', justifyContent: 'center', fontSize: '0.85rem', fontWeight: 600, flexShrink: 0 }}>
              {currentUser ? currentUser.name?.[0]?.toUpperCase() : 'D'}
            </div>
            <div style={{ overflow: 'hidden' }}>
              <div style={{ fontSize: '0.82rem', fontWeight: 600, color: 'var(--text-main)', whiteSpace: 'nowrap', textOverflow: 'ellipsis' }}>
                {currentUser ? currentUser.name : 'Demo Visitor'}
              </div>
              <div style={{ fontSize: '0.7rem', color: 'var(--text-dim)', whiteSpace: 'nowrap' }}>
                {isDemoSession ? 'Demo Access' : 'Authenticated'}
              </div>
            </div>
          </div>
          <button
            onClick={handleLogout}
            title="Sign out"
            style={{
              padding: '4px 8px',
              borderRadius: '4px',
              background: 'var(--border-color)',
              color: 'var(--text-muted)',
              fontSize: '0.75rem',
              fontWeight: 600,
            }}
          >
            Logout
          </button>
        </div>
      </aside>

      {/* MAIN VIEW AREA */}
      <main style={{ flex: 1, display: 'flex', flexDirection: 'column', height: '100vh', overflowY: 'auto' }}>
        {/* HEADER */}
        <header style={{ height: '64px', borderBottom: '1px solid var(--border-color)', background: 'var(--bg-secondary)', display: 'flex', alignItems: 'center', justifyContent: 'space-between', padding: '0 2rem', flexShrink: 0 }}>
          <div style={{ display: 'flex', alignItems: 'center', gap: '1rem' }}>
            <h1 style={{ fontSize: '1.2rem', fontWeight: 700, color: 'var(--text-main)', textTransform: 'capitalize' }}>
              {activeTab.replace('_', ' ')}
            </h1>
            {currentScan && (
              <span style={{ fontSize: '0.75rem', padding: '3px 8px', borderRadius: '4px', background: currentScan.status === 'completed' ? 'var(--success-bg)' : 'var(--warning-bg)', color: currentScan.status === 'completed' ? 'var(--success)' : 'var(--warning)', fontWeight: 600 }}>
                {currentScan.status.toUpperCase()}
              </span>
            )}
          </div>

          <div style={{ display: 'flex', alignItems: 'center', gap: '1rem' }}>
            <button
              onClick={handleRunScan}
              disabled={isScanning}
              style={{
                display: 'flex',
                alignItems: 'center',
                gap: '8px',
                background: isScanning ? 'var(--border-color)' : 'var(--primary)',
                color: '#fff',
                padding: '0.55rem 1.1rem',
                borderRadius: '6px',
                fontSize: '0.88rem',
                fontWeight: 600,
                boxShadow: isScanning ? 'none' : '0 2px 8px var(--primary-glow)',
                cursor: isScanning ? 'not-allowed' : 'pointer',
              }}
            >
              <span>{isScanning ? '⏳' : '▶'}</span>
              <span>{isScanning ? 'Dispatching...' : 'Trigger Scan'}</span>
            </button>
          </div>
        </header>

        {/* FEEDBACK BANNER */}
        {actionMessage && (
          <div style={{
            margin: '1rem 2rem 0',
            padding: '0.85rem 1.25rem',
            borderRadius: '6px',
            background: actionMessage.type === 'success' ? 'var(--success-bg)' : actionMessage.type === 'warning' ? 'var(--warning-bg)' : 'var(--danger-bg)',
            border: `1px solid ${actionMessage.type === 'success' ? 'var(--success)' : actionMessage.type === 'warning' ? 'var(--warning)' : 'var(--danger)'}`,
            color: 'var(--text-main)',
            fontSize: '0.88rem',
            display: 'flex',
            alignItems: 'center',
            justifyContent: 'space-between',
            lineHeight: 1.5,
          }}>
            <span>{actionMessage.text}</span>
            <button onClick={() => setActionMessage(null)} style={{ color: 'var(--text-dim)', fontWeight: 'bold', marginLeft: '1rem' }}>✕</button>
          </div>
        )}

        {/* TAB CONTENTS */}
        <div style={{ padding: '1.5rem 2rem', flex: 1 }}>
          {/* TAB 1: DASHBOARD */}
          {activeTab === 'dashboard' && (
            <div>
              {/* METRIC CARDS */}
              <div style={{ display: 'grid', gridTemplateColumns: 'repeat(4, 1fr)', gap: '1rem', marginBottom: '1.5rem' }}>
                <div style={{ background: 'var(--bg-card)', padding: '1.25rem', borderRadius: '8px', border: '1px solid var(--border-color)' }}>
                  <div style={{ fontSize: '0.75rem', color: 'var(--text-dim)', fontWeight: 600, textTransform: 'uppercase' }}>Total Scans</div>
                  <div style={{ fontSize: '1.6rem', fontWeight: 800, color: 'var(--text-main)', marginTop: '4px' }}>{metrics.total_scans}</div>
                </div>
                <div style={{ background: 'var(--bg-card)', padding: '1.25rem', borderRadius: '8px', border: '1px solid var(--border-color)' }}>
                  <div style={{ fontSize: '0.75rem', color: 'var(--text-dim)', fontWeight: 600, textTransform: 'uppercase' }}>Tests Executed</div>
                  <div style={{ fontSize: '1.6rem', fontWeight: 800, color: 'var(--text-main)', marginTop: '4px' }}>{metrics.total_tests}</div>
                </div>
                <div style={{ background: 'var(--bg-card)', padding: '1.25rem', borderRadius: '8px', border: '1px solid var(--border-color)' }}>
                  <div style={{ fontSize: '0.75rem', color: 'var(--text-dim)', fontWeight: 600, textTransform: 'uppercase' }}>Pass Rate</div>
                  <div style={{ fontSize: '1.6rem', fontWeight: 800, color: metrics.pass_rate >= 80 ? 'var(--success)' : 'var(--danger)', marginTop: '4px' }}>{metrics.pass_rate}%</div>
                </div>
                <div style={{ background: 'var(--bg-card)', padding: '1.25rem', borderRadius: '8px', border: '1px solid var(--border-color)' }}>
                  <div style={{ fontSize: '0.75rem', color: 'var(--text-dim)', fontWeight: 600, textTransform: 'uppercase' }}>Connected Repos</div>
                  <div style={{ fontSize: '1.6rem', fontWeight: 800, color: 'var(--text-main)', marginTop: '4px' }}>{metrics.active_repositories}</div>
                </div>
              </div>

              {/* LATEST SCAN TEST RUNNER VIEW */}
              {currentScan ? (
                <div style={{ display: 'grid', gridTemplateColumns: '360px 1fr', gap: '1.25rem' }}>
                  {/* TESTS LIST */}
                  <div style={{ background: 'var(--bg-card)', borderRadius: '8px', border: '1px solid var(--border-color)', display: 'flex', flexDirection: 'column', height: '620px' }}>
                    <div style={{ padding: '0.85rem 1rem', borderBottom: '1px solid var(--border-color)', display: 'flex', alignItems: 'center', justifyContent: 'space-between' }}>
                      <span style={{ fontSize: '0.85rem', fontWeight: 700, color: 'var(--text-main)' }}>Tests & Findings</span>
                      <div style={{ display: 'flex', gap: '4px' }}>
                        {['all', 'failed', 'passed'].map(f => (
                          <button
                            key={f}
                            onClick={() => setFilter(f)}
                            style={{
                              padding: '2px 8px',
                              borderRadius: '4px',
                              fontSize: '0.72rem',
                              fontWeight: 600,
                              background: filter === f ? 'var(--primary)' : 'rgba(255,255,255,0.06)',
                              color: '#fff',
                            }}
                          >
                            {f.toUpperCase()}
                          </button>
                        ))}
                      </div>
                    </div>

                    <div style={{ flex: 1, overflowY: 'auto', padding: '0.5rem' }}>
                      {filteredTests.map((test, idx) => (
                        <div
                          key={test.id || idx}
                          onClick={() => setSelectedTest(test)}
                          style={{
                            padding: '0.75rem',
                            borderRadius: '6px',
                            background: selectedTest?.id === test.id ? 'var(--border-color)' : 'transparent',
                            border: `1px solid ${selectedTest?.id === test.id ? 'var(--border-active)' : 'transparent'}`,
                            cursor: 'pointer',
                            marginBottom: '4px',
                          }}
                        >
                          <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between' }}>
                            <span style={{ fontSize: '0.82rem', fontWeight: 600, color: 'var(--text-main)' }}>{test.test_name}</span>
                            <span style={{ fontSize: '0.7rem', padding: '1px 6px', borderRadius: '3px', background: test.status === 'passed' ? 'var(--success-bg)' : 'var(--danger-bg)', color: test.status === 'passed' ? 'var(--success)' : 'var(--danger)', fontWeight: 700 }}>
                              {test.status.toUpperCase()}
                            </span>
                          </div>
                          <div style={{ fontSize: '0.72rem', color: 'var(--text-dim)', marginTop: '4px' }}>
                            {test.file_path} • {test.duration_ms}ms
                          </div>
                        </div>
                      ))}
                    </div>
                  </div>

                  {/* TEST DETAIL & AI ANALYSIS */}
                  <div style={{ background: 'var(--bg-card)', borderRadius: '8px', border: '1px solid var(--border-color)', padding: '1.25rem', height: '620px', overflowY: 'auto' }}>
                    {selectedTest ? (
                      <div>
                        <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '1rem' }}>
                          <div>
                            <h2 style={{ fontSize: '1.1rem', fontWeight: 700, color: 'var(--text-main)' }}>{selectedTest.test_name}</h2>
                            <div style={{ fontSize: '0.78rem', color: 'var(--text-dim)' }}>{selectedTest.test_suite} • {selectedTest.file_path}</div>
                          </div>
                          {selectedTest.status === 'failed' && (
                            <button
                              onClick={handleProposeFix}
                              disabled={isProcessingFix}
                              style={{ background: 'linear-gradient(135deg, #8b5cf6, #3b82f6)', color: '#fff', padding: '0.45rem 0.9rem', borderRadius: '6px', fontSize: '0.82rem', fontWeight: 600 }}
                            >
                              ⚡ Propose AI Fix
                            </button>
                          )}
                        </div>

                        {/* ERROR MESSAGE IF FAILED */}
                        {selectedTest.error_message && (
                          <div style={{ background: 'rgba(239, 68, 68, 0.08)', border: '1px solid rgba(239, 68, 68, 0.3)', borderRadius: '6px', padding: '0.85rem', marginBottom: '1rem' }}>
                            <div style={{ fontSize: '0.72rem', color: 'var(--danger)', fontWeight: 700, textTransform: 'uppercase' }}>Failure Assertion</div>
                            <pre style={{ fontSize: '0.82rem', color: '#f8fafc', whiteSpace: 'pre-wrap', marginTop: '4px', fontFamily: 'var(--font-mono)' }}>
                              {selectedTest.error_message}
                            </pre>
                          </div>
                        )}

                        {/* AI DIAGNOSTIC PANEL */}
                        {selectedTest.ai_analysis && (
                          <div style={{ background: 'rgba(139, 92, 246, 0.08)', border: '1px solid rgba(139, 92, 246, 0.3)', borderRadius: '6px', padding: '1rem', marginBottom: '1rem' }}>
                            <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '8px' }}>
                              <span style={{ fontSize: '0.78rem', fontWeight: 700, color: 'var(--purple)', textTransform: 'uppercase' }}>
                                🤖 AI Diagnostic Engine ({selectedTest.ai_analysis.failure_category || 'Automated Diagnosis'})
                              </span>
                              <span style={{ fontSize: '0.72rem', color: 'var(--text-dim)' }}>
                                Confidence: {Math.round((selectedTest.ai_analysis.confidence || selectedTest.ai_analysis.confidence_score || 0.9) * 100)}%
                              </span>
                            </div>
                            <p style={{ fontSize: '0.86rem', color: 'var(--text-main)', lineHeight: 1.5, marginBottom: '8px' }}>
                              <strong>Root Cause:</strong> {selectedTest.ai_analysis.root_cause}
                            </p>
                            {selectedTest.ai_analysis.suggested_fix && (
                              <p style={{ fontSize: '0.86rem', color: 'var(--text-muted)', lineHeight: 1.5, marginBottom: '8px' }}>
                                <strong>Actionable Fix:</strong> {selectedTest.ai_analysis.suggested_fix}
                              </p>
                            )}
                            {selectedTest.ai_analysis.code_patch && (
                              <pre style={{ background: '#090d16', padding: '0.75rem', borderRadius: '4px', marginTop: '8px', fontSize: '0.78rem', color: '#38bdf8', overflowX: 'auto', fontFamily: 'var(--font-mono)' }}>
                                {selectedTest.ai_analysis.code_patch}
                              </pre>
                            )}
                          </div>
                        )}

                        {/* STACK TRACE */}
                        {selectedTest.stack_trace && (
                          <div style={{ marginBottom: '1rem' }}>
                            <div style={{ fontSize: '0.72rem', color: 'var(--text-dim)', fontWeight: 700, textTransform: 'uppercase', marginBottom: '4px' }}>Execution Stack Trace</div>
                            <pre style={{ background: '#0f172a', padding: '0.75rem', borderRadius: '6px', fontSize: '0.75rem', color: 'var(--text-muted)', maxHeight: '180px', overflowY: 'auto', fontFamily: 'var(--font-mono)' }}>
                              {selectedTest.stack_trace}
                            </pre>
                          </div>
                        )}
                      </div>
                    ) : (
                      <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'center', height: '100%', color: 'var(--text-dim)' }}>
                        Select a test result to view details and AI diagnosis.
                      </div>
                    )}
                  </div>
                </div>
              ) : (
                <div style={{ padding: '3rem', textAlign: 'center', background: 'var(--bg-card)', borderRadius: '8px', border: '1px solid var(--border-color)' }}>
                  <h3 style={{ fontSize: '1.2rem', color: 'var(--text-main)', marginBottom: '8px' }}>No Scans Yet</h3>
                  <p style={{ color: 'var(--text-muted)', marginBottom: '1.5rem' }}>Trigger your first automated Playwright E2E and Security scan.</p>
                  <button onClick={handleRunScan} style={{ background: 'var(--primary)', color: '#fff', padding: '0.65rem 1.3rem', borderRadius: '6px', fontWeight: 600 }}>
                    Trigger Initial Scan
                  </button>
                </div>
              )}
            </div>
          )}

          {/* TAB 2: SCANS & REPORTS */}
          {activeTab === 'scans' && (
            <div>
              <div style={{ background: 'var(--bg-card)', borderRadius: '8px', border: '1px solid var(--border-color)', padding: '1rem' }}>
                <h3 style={{ fontSize: '1rem', fontWeight: 700, color: 'var(--text-main)', marginBottom: '1rem' }}>Historical Scans</h3>
                <table style={{ width: '100%', borderCollapse: 'collapse', textAlign: 'left', fontSize: '0.85rem' }}>
                  <thead>
                    <tr style={{ color: 'var(--text-dim)', borderBottom: '1px solid var(--border-color)' }}>
                      <th style={{ padding: '8px' }}>Scan ID</th>
                      <th style={{ padding: '8px' }}>Branch</th>
                      <th style={{ padding: '8px' }}>Tests</th>
                      <th style={{ padding: '8px' }}>Pass Rate</th>
                      <th style={{ padding: '8px' }}>Status</th>
                      <th style={{ padding: '8px' }}>Actions</th>
                    </tr>
                  </thead>
                  <tbody>
                    {scans.map(s => (
                      <tr key={s.id} style={{ borderBottom: '1px solid rgba(255,255,255,0.05)' }}>
                        <td style={{ padding: '10px 8px', fontWeight: 600 }}>#{s.id.slice(-6)}</td>
                        <td style={{ padding: '10px 8px' }}>{s.branch}</td>
                        <td style={{ padding: '10px 8px' }}>{s.passed_tests}/{s.total_tests} passed</td>
                        <td style={{ padding: '10px 8px' }}>{s.total_tests > 0 ? Math.round((s.passed_tests / s.total_tests) * 100) : 100}%</td>
                        <td style={{ padding: '10px 8px' }}>
                          <span style={{ fontSize: '0.72rem', padding: '2px 7px', borderRadius: '4px', background: s.status === 'completed' ? 'var(--success-bg)' : 'var(--warning-bg)', color: s.status === 'completed' ? 'var(--success)' : 'var(--warning)', fontWeight: 700 }}>
                            {s.status.toUpperCase()}
                          </span>
                        </td>
                        <td style={{ padding: '10px 8px', display: 'flex', gap: '8px' }}>
                          <button
                            onClick={() => { setCurrentScan(s); setActiveTab('dashboard'); }}
                            style={{ background: 'var(--border-color)', padding: '4px 8px', borderRadius: '4px', fontSize: '0.75rem', color: '#fff' }}
                          >
                            View
                          </button>
                          <a
                            href={`/workspaces/${activeWorkspace.id}/scans/${s.id}/export?format=html`}
                            target="_blank"
                            rel="noreferrer"
                            style={{ background: 'rgba(59, 130, 246, 0.15)', color: 'var(--primary)', padding: '4px 8px', borderRadius: '4px', fontSize: '0.75rem', fontWeight: 600 }}
                          >
                            Export HTML
                          </a>
                        </td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>
            </div>
          )}

          {/* TAB 3: FIX PLANS */}
          {activeTab === 'fixes' && (
            <div>
              <div style={{ display: 'grid', gridTemplateColumns: '340px 1fr', gap: '1.25rem' }}>
                <div style={{ background: 'var(--bg-card)', borderRadius: '8px', border: '1px solid var(--border-color)', padding: '1rem', height: '600px', overflowY: 'auto' }}>
                  <h3 style={{ fontSize: '0.95rem', fontWeight: 700, color: 'var(--text-main)', marginBottom: '1rem' }}>AI Fix Plans</h3>
                  {fixPlans.length === 0 ? (
                    <div style={{ color: 'var(--text-dim)', fontSize: '0.85rem' }}>No fix plans created yet. Propose one from a failed scan.</div>
                  ) : (
                    fixPlans.map(p => (
                      <div
                        key={p.id}
                        onClick={() => setSelectedPlan(p)}
                        style={{
                          padding: '0.75rem',
                          borderRadius: '6px',
                          background: selectedPlan?.id === p.id ? 'var(--border-color)' : 'transparent',
                          border: `1px solid ${selectedPlan?.id === p.id ? 'var(--border-active)' : 'transparent'}`,
                          cursor: 'pointer',
                          marginBottom: '6px',
                        }}
                      >
                        <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
                          <span style={{ fontSize: '0.82rem', fontWeight: 600, color: 'var(--text-main)' }}>{p.title}</span>
                          <span style={{ fontSize: '0.7rem', padding: '1px 6px', borderRadius: '3px', background: p.status === 'published' ? 'var(--success-bg)' : 'var(--purple-bg)', color: p.status === 'published' ? 'var(--success)' : 'var(--purple)', fontWeight: 700 }}>
                            {p.status.toUpperCase()}
                          </span>
                        </div>
                        <div style={{ fontSize: '0.72rem', color: 'var(--text-dim)', marginTop: '4px' }}>
                          Required Credits: {p.fix_credits_required} Fix credit
                        </div>
                      </div>
                    ))
                  )}
                </div>

                <div style={{ background: 'var(--bg-card)', borderRadius: '8px', border: '1px solid var(--border-color)', padding: '1.25rem', height: '600px', overflowY: 'auto' }}>
                  {selectedPlan ? (
                    <div>
                      <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'flex-start', marginBottom: '1.25rem' }}>
                        <div>
                          <h2 style={{ fontSize: '1.15rem', fontWeight: 700, color: 'var(--text-main)' }}>{selectedPlan.title}</h2>
                          <div style={{ fontSize: '0.78rem', color: 'var(--text-dim)' }}>Plan Digest: {selectedPlan.plan_digest?.slice(0, 16)}...</div>
                        </div>

                        {/* ACTION BUTTONS BASED ON LIFECYCLE */}
                        <div style={{ display: 'flex', gap: '8px' }}>
                          {selectedPlan.status === 'proposed' && (
                            <button
                              onClick={() => handleApprovePlan(selectedPlan.id)}
                              disabled={isProcessingFix}
                              style={{ background: 'var(--primary)', color: '#fff', padding: '0.5rem 1rem', borderRadius: '6px', fontSize: '0.82rem', fontWeight: 600 }}
                            >
                              {isProcessingFix ? 'Approving...' : 'Approve Fix (1 Credit)'}
                            </button>
                          )}
                          {selectedPlan.status === 'approved' && (
                            <button
                              onClick={() => handleVerifyPlan(selectedPlan.id)}
                              disabled={isProcessingFix}
                              style={{ background: 'var(--purple)', color: '#fff', padding: '0.5rem 1rem', borderRadius: '6px', fontSize: '0.82rem', fontWeight: 600 }}
                            >
                              {isProcessingFix ? 'Verifying...' : 'Verify Patch in Fresh Isolation'}
                            </button>
                          )}
                          {selectedPlan.status === 'verified' && (
                            <button
                              onClick={() => handlePublishPr(selectedPlan.id)}
                              disabled={isProcessingFix}
                              style={{ background: 'var(--success)', color: '#fff', padding: '0.5rem 1rem', borderRadius: '6px', fontSize: '0.82rem', fontWeight: 600 }}
                            >
                              {isProcessingFix ? 'Publishing...' : 'Publish Verified Draft PR'}
                            </button>
                          )}
                          {selectedPlan.status === 'published' && selectedPlan.pr_url && (
                            <a
                              href={selectedPlan.pr_url}
                              target="_blank"
                              rel="noreferrer"
                              style={{ background: 'rgba(16, 185, 129, 0.15)', color: 'var(--success)', padding: '0.5rem 1rem', borderRadius: '6px', fontSize: '0.82rem', fontWeight: 600 }}
                            >
                              View PR #{selectedPlan.pr_number} ↗
                            </a>
                          )}
                        </div>
                      </div>

                      <div style={{ marginBottom: '1.25rem' }}>
                        <div style={{ fontSize: '0.75rem', color: 'var(--text-dim)', fontWeight: 700, textTransform: 'uppercase', marginBottom: '6px' }}>Fix Jobs Included:</div>
                        {selectedPlan.fix_jobs?.map((job, jIdx) => (
                          <div key={job.id || jIdx} style={{ background: '#0f172a', padding: '1rem', borderRadius: '6px', marginBottom: '8px', border: '1px solid var(--border-color)' }}>
                            <div style={{ fontSize: '0.82rem', fontWeight: 600, color: 'var(--text-main)', marginBottom: '4px' }}>Target: {job.target_file}</div>
                            <div style={{ fontSize: '0.78rem', color: 'var(--text-muted)', marginBottom: '6px' }}>{job.root_cause}</div>
                            <pre style={{ background: '#090d16', padding: '0.65rem', borderRadius: '4px', fontSize: '0.75rem', color: '#38bdf8', overflowX: 'auto', fontFamily: 'var(--font-mono)' }}>
                              {job.suggested_patch}
                            </pre>
                            {job.verification_output && (
                              <div style={{ marginTop: '6px', fontSize: '0.72rem', color: 'var(--success)', fontWeight: 600 }}>
                                ✓ {job.verification_output}
                              </div>
                            )}
                          </div>
                        ))}
                      </div>
                    </div>
                  ) : (
                    <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'center', height: '100%', color: 'var(--text-dim)' }}>
                      Select a Fix Plan from the list or propose one from a failed scan.
                    </div>
                  )}
                </div>
              </div>
            </div>
          )}

          {/* TAB 4: PROJECTS & TARGETS */}
          {activeTab === 'projects' && (
            <div style={{ display: 'flex', flexDirection: 'column', gap: '1.25rem' }}>
              <div style={{ background: 'var(--bg-card)', borderRadius: '8px', border: '1px solid var(--border-color)', padding: '1.25rem' }}>
                <h3 style={{ fontSize: '1rem', fontWeight: 700, color: 'var(--text-main)', marginBottom: '0.5rem' }}>Configured Projects</h3>
                <p style={{ fontSize: '0.82rem', color: 'var(--text-muted)', marginBottom: '1.25rem' }}>
                  Target projects monitored by autonomous security and end-to-end regression suites.
                </p>

                {projects.length === 0 ? (
                  <div style={{ color: 'var(--text-dim)', fontSize: '0.88rem' }}>No projects configured yet.</div>
                ) : (
                  <div style={{ display: 'flex', flexDirection: 'column', gap: '1rem' }}>
                    {projects.map(proj => (
                      <div key={proj.id} style={{ background: '#0f172a', borderRadius: '8px', border: '1px solid var(--border-color)', padding: '1.25rem' }}>
                        <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'flex-start' }}>
                          <div>
                            <div style={{ fontSize: '1.05rem', fontWeight: 700, color: 'var(--text-main)' }}>{proj.name}</div>
                            <div style={{ fontSize: '0.82rem', color: 'var(--text-muted)', marginTop: '4px' }}>{proj.description}</div>
                          </div>
                          <span style={{ fontSize: '0.72rem', padding: '2px 8px', borderRadius: '4px', background: 'var(--success-bg)', color: 'var(--success)', fontWeight: 700 }}>
                            {proj.status.toUpperCase()}
                          </span>
                        </div>

                        <div style={{ display: 'flex', gap: '1.5rem', marginTop: '1rem', fontSize: '0.78rem', color: 'var(--text-dim)', borderTop: '1px solid rgba(255,255,255,0.06)', paddingTop: '0.75rem' }}>
                          <div>Framework: <strong style={{ color: 'var(--text-main)' }}>{proj.framework || 'React'}</strong></div>
                          <div>Default Branch: <strong style={{ color: 'var(--text-main)' }}>{proj.default_branch || 'main'}</strong></div>
                          <div>Repository: <strong style={{ color: 'var(--text-main)' }}>{proj.repository?.full_name || 'trusted-demo'}</strong></div>
                        </div>

                        {/* TARGET AUTHORIZATIONS */}
                        {proj.target_authorizations && proj.target_authorizations.length > 0 && (
                          <div style={{ marginTop: '1rem', background: '#090d16', padding: '0.85rem', borderRadius: '6px', border: '1px solid var(--border-color)' }}>
                            <div style={{ fontSize: '0.72rem', color: 'var(--text-dim)', textTransform: 'uppercase', fontWeight: 700, marginBottom: '6px' }}>Authorized Deployment Target</div>
                            {proj.target_authorizations.map(tgt => (
                              <div key={tgt.id} style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
                                <div style={{ fontSize: '0.84rem', color: 'var(--text-main)', fontFamily: 'var(--font-mono)' }}>{tgt.target_url}</div>
                                <span style={{ fontSize: '0.68rem', padding: '2px 6px', borderRadius: '4px', background: 'var(--success-bg)', color: 'var(--success)', fontWeight: 700 }}>
                                  {tgt.environment.toUpperCase()} • {tgt.status.toUpperCase()}
                                </span>
                              </div>
                            ))}
                          </div>
                        )}
                      </div>
                    ))}
                  </div>
                )}
              </div>
            </div>
          )}

          {/* TAB 5: REPOSITORIES */}
          {activeTab === 'repos' && (
            <div style={{ background: 'var(--bg-card)', borderRadius: '8px', border: '1px solid var(--border-color)', padding: '1.25rem' }}>
              <h3 style={{ fontSize: '1rem', fontWeight: 700, color: 'var(--text-main)', marginBottom: '1rem' }}>Connected Repositories</h3>
              <div style={{ display: 'flex', flexDirection: 'column', gap: '8px' }}>
                {repositories.map(r => (
                  <div key={r.id} style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', padding: '1rem', background: '#0f172a', borderRadius: '6px', border: '1px solid var(--border-color)' }}>
                    <div>
                      <div style={{ fontSize: '0.9rem', fontWeight: 600, color: 'var(--text-main)' }}>{r.full_name}</div>
                      <div style={{ fontSize: '0.75rem', color: 'var(--text-dim)' }}>Default Branch: {r.default_branch} • Status: {r.status}</div>
                    </div>
                    <span style={{ fontSize: '0.72rem', padding: '2px 8px', borderRadius: '4px', background: 'var(--success-bg)', color: 'var(--success)', fontWeight: 600 }}>
                      CONNECTED
                    </span>
                  </div>
                ))}
              </div>
            </div>
          )}

          {/* TAB 6: USAGE & CREDITS */}
          {activeTab === 'usage' && (
            <div style={{ display: 'grid', gridTemplateColumns: 'repeat(2, 1fr)', gap: '1.25rem' }}>
              <div style={{ background: 'var(--bg-card)', borderRadius: '8px', border: '1px solid var(--border-color)', padding: '1.25rem' }}>
                <h3 style={{ fontSize: '1rem', fontWeight: 700, color: 'var(--text-main)', marginBottom: '1rem' }}>Scan Credits</h3>
                <div style={{ fontSize: '2rem', fontWeight: 800, color: 'var(--primary)' }}>
                  {usageData?.usage?.scan_credits?.available ?? 50} / 50
                </div>
                <div style={{ fontSize: '0.8rem', color: 'var(--text-dim)', marginTop: '4px' }}>
                  {usageData?.usage?.scan_credits?.consumed ?? 1} consumed this period.
                </div>
              </div>

              <div style={{ background: 'var(--bg-card)', borderRadius: '8px', border: '1px solid var(--border-color)', padding: '1.25rem' }}>
                <h3 style={{ fontSize: '1rem', fontWeight: 700, color: 'var(--text-main)', marginBottom: '1rem' }}>Fix Credits</h3>
                <div style={{ fontSize: '2rem', fontWeight: 800, color: 'var(--purple)' }}>
                  {usageData?.usage?.fix_credits?.available ?? 20} / 20
                </div>
                <div style={{ fontSize: '0.8rem', color: 'var(--text-dim)', marginTop: '4px' }}>
                  {usageData?.usage?.fix_credits?.consumed ?? 0} consumed this period.
                </div>
              </div>

              <div style={{ gridColumn: 'span 2', background: 'var(--bg-card)', borderRadius: '8px', border: '1px solid var(--border-color)', padding: '1.25rem' }}>
                <h3 style={{ fontSize: '1rem', fontWeight: 700, color: 'var(--text-main)', marginBottom: '0.5rem' }}>Emergency Containment Status (ND13)</h3>
                <div style={{ display: 'grid', gridTemplateColumns: 'repeat(4, 1fr)', gap: '1rem', marginTop: '1rem' }}>
                  {Object.entries(usageData?.system_health?.containment || {}).map(([key, val]) => (
                    <div key={key} style={{ background: '#0f172a', padding: '0.75rem', borderRadius: '6px', border: '1px solid var(--border-color)' }}>
                      <div style={{ fontSize: '0.7rem', color: 'var(--text-dim)', textTransform: 'uppercase' }}>{key.replace('_', ' ')}</div>
                      <div style={{ fontSize: '0.9rem', fontWeight: 700, color: val ? 'var(--success)' : 'var(--danger)', marginTop: '4px' }}>
                        {val ? 'ALLOWED' : 'CONTAINED'}
                      </div>
                    </div>
                  ))}
                </div>
              </div>
            </div>
          )}

          {/* TAB 7: NOTIFICATIONS */}
          {activeTab === 'notifications' && (
            <div style={{ background: 'var(--bg-card)', borderRadius: '8px', border: '1px solid var(--border-color)', padding: '1.25rem' }}>
              <h3 style={{ fontSize: '1rem', fontWeight: 700, color: 'var(--text-main)', marginBottom: '1rem' }}>Notifications</h3>
              {notifications.length === 0 ? (
                <div style={{ color: 'var(--text-dim)', fontSize: '0.88rem' }}>No notifications to display.</div>
              ) : (
                <div style={{ display: 'flex', flexDirection: 'column', gap: '8px' }}>
                  {notifications.map(n => (
                    <div key={n.id} style={{ padding: '0.85rem', background: '#0f172a', borderRadius: '6px', border: '1px solid var(--border-color)' }}>
                      <div style={{ fontSize: '0.85rem', fontWeight: 600, color: 'var(--text-main)' }}>{n.title}</div>
                      <div style={{ fontSize: '0.8rem', color: 'var(--text-muted)', marginTop: '2px' }}>{n.message}</div>
                    </div>
                  ))}
                </div>
              )}
            </div>
          )}
        </div>
      </main>
    </div>
  );
}
