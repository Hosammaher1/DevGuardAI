import React, { useState, useEffect } from 'react';
import LandingScreen from './components/LandingScreen';
import TopNav from './components/TopNav';
import Sidebar from './components/Sidebar';
import RepositoriesScreen from './components/RepositoriesScreen';

export default function App() {
  const [currentUser, setCurrentUser] = useState(null);
  const [sessionStatus, setSessionStatus] = useState('checking'); // checking, guest, authenticated, error
  const [isDemoSession, setIsDemoSession] = useState(false);
  const [workspaces, setWorkspaces] = useState([]);
  const [activeWorkspace, setActiveWorkspace] = useState(null);
  const [activeTab, setActiveTab] = useState('repos'); // repos, dashboard, scans, fixes, projects, usage, notifications

  // GitHub Discovered Repositories
  const [discoveredRepos, setDiscoveredRepos] = useState([]);
  const [isLoadingRepos, setIsLoadingRepos] = useState(false);
  const [hasGithubToken, setHasGithubToken] = useState(false);

  // Staging Login State
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

  // Forms / Modals
  const [showNewProjectModal, setShowNewProjectModal] = useState(false);
  const [newProjectName, setNewProjectName] = useState('');
  const [newProjectRepoId, setNewProjectRepoId] = useState('');
  const [newProjectFramework, setNewProjectFramework] = useState('react');
  const [newProjectDesc, setNewProjectDesc] = useState('');
  const [isCreatingProject, setIsCreatingProject] = useState(false);

  const [showConnectRepoModal, setShowConnectRepoModal] = useState(false);
  const [repoFullName, setRepoFullName] = useState('');
  const [repoInstallationId, setRepoInstallationId] = useState('99990001');
  const [repoDefaultBranch, setRepoDefaultBranch] = useState('main');
  const [isConnectingRepo, setIsConnectingRepo] = useState(false);

  // UI State
  const [isScanning, setIsScanning] = useState(false);
  const [scanStep, setScanStep] = useState('');
  const [filter, setFilter] = useState('all'); // all, failed, passed
  const [actionMessage, setActionMessage] = useState(null);
  const [isProcessingFix, setIsProcessingFix] = useState(false);

  // CSRF & Authenticated API Fetcher
  const getCsrfToken = async () => {
    try {
      const res = await fetch('/api/csrf-token', { credentials: 'same-origin' });
      if (res.ok) {
        const data = await res.json();
        return data.csrf_token || '';
      }
    } catch (e) {
      console.warn('Failed to fetch CSRF token:', e);
    }
    const match = document.cookie.match(/XSRF-TOKEN=([^;]+)/);
    return match ? decodeURIComponent(match[1]) : '';
  };

  const apiFetch = async (url, options = {}) => {
    const method = (options.method || 'GET').toUpperCase();
    const headers = {
      'Accept': 'application/json',
      ...(options.headers || {}),
    };

    if (method !== 'GET' && method !== 'HEAD') {
      const token = await getCsrfToken();
      if (token) {
        headers['X-CSRF-TOKEN'] = token;
      }
      if (!headers['Content-Type'] && !(options.body instanceof FormData)) {
        headers['Content-Type'] = 'application/json';
      }
    }

    return fetch(url, {
      ...options,
      headers,
      credentials: 'same-origin',
    });
  };

  // 1. Initial Authentication Check
  useEffect(() => {
    checkSession();
  }, []);

  const checkSession = async () => {
    setSessionStatus('checking');
    try {
      const res = await apiFetch('/api/session');
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
        fetchDiscoveredRepositories();
      } else {
        setCurrentUser(null);
        setWorkspaces([]);
        setActiveWorkspace(null);
        setIsDemoSession(false);
        setDiscoveredRepos([]);
        setSessionStatus(res.status === 401 ? 'guest' : 'error');
      }
    } catch (err) {
      console.error('Session check failed:', err);
      setSessionStatus('error');
    }
  };

  const fetchDiscoveredRepositories = async () => {
    setIsLoadingRepos(true);
    try {
      const res = await apiFetch('/api/github/repositories');
      if (res.ok) {
        const data = await res.json();
        setDiscoveredRepos(data.repositories || []);
        setHasGithubToken(Boolean(data.has_token));
      }
    } catch (err) {
      console.error('Failed to discover repositories:', err);
    } finally {
      setIsLoadingRepos(false);
    }
  };

  const loadWorkspaceData = async (workspaceId) => {
    if (!workspaceId) return;
    try {
      // 1. Metrics & Latest Scan
      const mRes = await apiFetch(`/workspaces/${workspaceId}/metrics`);
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
      const sRes = await apiFetch(`/workspaces/${workspaceId}/scans`);
      if (sRes.ok) {
        const sData = await sRes.json();
        setScans(sData.scans || []);
      }

      // 3. Projects & Targets
      const pRes = await apiFetch(`/workspaces/${workspaceId}/projects`);
      if (pRes.ok) {
        const pData = await pRes.json();
        setProjects(pData.projects || []);
      }

      // 4. Repositories
      const rRes = await apiFetch(`/workspaces/${workspaceId}/repositories`);
      if (rRes.ok) {
        const rData = await rRes.json();
        setRepositories(rData.repositories || []);
      }

      // 5. Fix Plans
      const fRes = await apiFetch(`/workspaces/${workspaceId}/fix-plans`);
      if (fRes.ok) {
        const fData = await fRes.json();
        const list = fData.fix_plans || [];
        setFixPlans(list);
        if (list.length > 0 && !selectedPlan) {
          setSelectedPlan(list[0]);
        }
      }

      // 6. Usage & Health
      const uRes = await apiFetch(`/workspaces/${workspaceId}/usage`);
      if (uRes.ok) {
        const uData = await uRes.json();
        setUsageData(uData);
      }

      // 7. Notifications
      const nRes = await apiFetch(`/workspaces/${workspaceId}/notifications`);
      if (nRes.ok) {
        const nData = await nRes.json();
        setNotifications(nData.notifications || []);
        setUnreadNotifications(nData.unread_count || 0);
      }
    } catch (err) {
      console.error('Error loading workspace data:', err);
    }
  };

  const handleLogin = async (eOrCode) => {
    let code = invitationCode.trim();
    if (typeof eOrCode === 'string') {
      code = eOrCode.trim();
    } else if (eOrCode && eOrCode.preventDefault) {
      eOrCode.preventDefault();
    }
    if (!code) {
      setAuthError('Please enter an invitation code.');
      return;
    }

    setAuthError(null);
    setIsSubmittingCode(true);

    try {
      const res = await apiFetch('/auth/demo', {
        method: 'POST',
        body: JSON.stringify({ invitation_code: code }),
      });

      const data = await res.json().catch(() => ({}));

      if (res.ok) {
        setCurrentUser(data.user);
        setIsDemoSession(false);
        setSessionStatus('authenticated');
        if (data.workspace) {
          setWorkspaces([data.workspace]);
          setActiveWorkspace(data.workspace);
          loadWorkspaceData(data.workspace.id);
        } else {
          checkSession();
        }
        fetchDiscoveredRepositories();
      } else {
        setAuthError(data.message || data.error || 'Invalid invitation code.');
      }
    } catch (err) {
      setAuthError('Connection error while authenticating: ' + err.message);
    } finally {
      setIsSubmittingCode(false);
    }
  };

  const handleLogout = async () => {
    try {
      await apiFetch('/logout', { method: 'POST' });
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

  const handleCreateProject = async (e) => {
    e.preventDefault();
    if (!activeWorkspace) return;
    if (!newProjectName.trim()) {
      setActionMessage({ type: 'danger', text: 'Project name is required.' });
      return;
    }
    const repoId = newProjectRepoId || repositories[0]?.id;
    if (!repoId) {
      setActionMessage({ type: 'warning', text: 'Please connect a repository first before creating a project.' });
      return;
    }

    setIsCreatingProject(true);
    setActionMessage(null);
    try {
      const res = await apiFetch(`/workspaces/${activeWorkspace.id}/projects`, {
        method: 'POST',
        body: JSON.stringify({
          repository_id: repoId,
          name: newProjectName.trim(),
          description: newProjectDesc.trim() || undefined,
          framework: newProjectFramework,
          default_branch: 'main',
        }),
      });
      const data = await res.json().catch(() => ({}));
      if (res.ok) {
        setActionMessage({ type: 'success', text: `Project "${newProjectName}" created successfully!` });
        setShowNewProjectModal(false);
        setNewProjectName('');
        setNewProjectDesc('');
        loadWorkspaceData(activeWorkspace.id);
      } else {
        setActionMessage({ type: 'danger', text: 'Project creation failed: ' + (data.message || data.reason || 'Server rejected request') });
      }
    } catch (err) {
      setActionMessage({ type: 'danger', text: 'Error creating project: ' + err.message });
    } finally {
      setIsCreatingProject(false);
    }
  };

  const handleConnectRepository = async (e) => {
    e.preventDefault();
    if (!activeWorkspace) return;
    if (!repoFullName.trim()) {
      setActionMessage({ type: 'danger', text: 'Repository name (owner/repo) is required.' });
      return;
    }

    setIsConnectingRepo(true);
    setActionMessage(null);
    try {
      const parts = repoFullName.trim().split('/');
      const repoName = parts.length > 1 ? parts[1] : parts[0];
      const ownerLogin = parts.length > 1 ? parts[0] : (currentUser?.name || 'DevGuardAI');

      const res = await apiFetch(`/workspaces/${activeWorkspace.id}/repositories`, {
        method: 'POST',
        body: JSON.stringify({
          installation_id: repoInstallationId || '99990001',
          github_repo_id: Math.floor(100000 + Math.random() * 900000),
          name: repoName,
          full_name: repoFullName.trim(),
          owner_login: ownerLogin,
          default_branch: repoDefaultBranch || 'main',
          is_private: false,
          auto_scan_enabled: true,
        }),
      });
      const data = await res.json().catch(() => ({}));
      if (res.ok) {
        setActionMessage({ type: 'success', text: `Repository ${repoFullName} connected successfully!` });
        setShowConnectRepoModal(false);
        setRepoFullName('');
        loadWorkspaceData(activeWorkspace.id);
      } else {
        setActionMessage({ type: 'danger', text: 'Repository connection failed: ' + (data.reason || data.message || 'Operation denied') });
      }
    } catch (err) {
      setActionMessage({ type: 'danger', text: 'Error connecting repository: ' + err.message });
    } finally {
      setIsConnectingRepo(false);
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
      const res = await apiFetch(`/workspaces/${activeWorkspace.id}/scans`, {
        method: 'POST',
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
          text: 'Security Containment: Untrusted customer repository code cannot be safely executed directly inside the Render Free control plane. Connect an external isolated runner (DEVGUARD_ISOLATED_RUNNER_ENABLED=true with Docker/MicroVM) to execute real Playwright scans.',
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
      const res = await apiFetch(`/workspaces/${activeWorkspace.id}/scans/${currentScan.id}/fix-plans`, {
        method: 'POST',
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
      const res = await apiFetch(`/workspaces/${activeWorkspace.id}/fix-plans/${planId}/approvals`, {
        method: 'POST',
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
      const res = await apiFetch(`/workspaces/${activeWorkspace.id}/fix-plans/${planId}/verify`, {
        method: 'POST',
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
      const res = await apiFetch(`/workspaces/${activeWorkspace.id}/fix-plans/${planId}/publish-pr`, {
        method: 'POST',
      });
      const data = await res.json().catch(() => ({}));

      if (res.ok) {
        setSelectedPlan(data.plan);
        loadWorkspaceData(activeWorkspace.id);
        setActionMessage({
          type: 'success',
          text: `Draft PR #${data.pr_number} created on GitHub! Branch: ${data.branch}`,
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

  // ==========================================
  // VIEW: GUEST / LANDING / LOGIN (SCREEN 1)
  // ==========================================
  if (sessionStatus !== 'authenticated') {
    if (sessionStatus === 'checking') {
      return (
        <div style={{
          minHeight: '100vh',
          display: 'flex',
          flexDirection: 'column',
          alignItems: 'center',
          justifyContent: 'center',
          backgroundColor: '#090D16',
          color: '#FFFFFF',
          fontFamily: "'Plus Jakarta Sans', system-ui, -apple-system, sans-serif",
        }}>
          <div className="spinner" style={{
            width: '36px',
            height: '36px',
            border: '3px solid #1E293B',
            borderTopColor: '#3B82F6',
            borderRadius: '50%',
            marginBottom: '16px',
          }} />
          <div style={{ color: '#94A3B8', fontSize: '14px' }}>Loading DevGuardAI...</div>
        </div>
      );
    }

    return (
      <LandingScreen
        onDemoLogin={(code) => handleLogin(code)}
        isSubmittingCode={isSubmittingCode}
        authError={authError}
      />
    );
  }

  // ==========================================
  // VIEW: AUTHENTICATED WORKSPACE
  // ==========================================
  return (
    <div style={{ display: 'flex', flexDirection: 'column', minHeight: '100vh', background: 'var(--bg-primary)' }}>
      {/* TOP NAVIGATION (SCREEN 3) */}
      <TopNav
        currentUser={currentUser}
        workspaces={workspaces}
        activeWorkspace={activeWorkspace}
        onSelectWorkspace={(ws) => {
          setActiveWorkspace(ws);
          fetchWorkspaceData(ws.id);
        }}
        onLogout={handleLogout}
        unreadCount={unreadNotifications}
      />

      <div style={{ display: 'flex', flex: 1, height: 'calc(100vh - 60px)', overflow: 'hidden' }}>
        {/* SIDEBAR NAVIGATION (SCREEN 3) */}
        <Sidebar activeTab={activeTab} onSelectTab={setActiveTab} />

        {/* MAIN VIEW AREA */}
        <main style={{ flex: 1, display: 'flex', flexDirection: 'column', height: '100%', overflowY: 'auto' }}>
          {/* HEADER (For non-repos tabs) */}
          {activeTab !== 'repos' && (
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
                {activeTab === 'projects' && (
                  <button
                    onClick={() => setShowNewProjectModal(true)}
                    style={{
                      background: 'var(--primary)',
                      color: '#fff',
                      padding: '0.55rem 1.1rem',
                      borderRadius: '6px',
                      fontSize: '0.88rem',
                      fontWeight: 600,
                    }}
                  >
                    + New Project
                  </button>
                )}

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
                  }}
                >
                  {isScanning ? (
                    <>
                      <span className="spinner" style={{ width: '14px', height: '14px', border: '2px solid rgba(255,255,255,0.3)', borderTopColor: '#fff', borderRadius: '50%' }}></span>
                      <span>{scanStep || 'Executing Scan...'}</span>
                    </>
                  ) : (
                    <>
                      <span>▶</span>
                      <span>Trigger Automated Scan</span>
                    </>
                  )}
                </button>
              </div>
            </header>
          )}

          {/* FEEDBACK BANNER */}
          {actionMessage && (
            <div style={{ padding: '0.85rem 2rem', background: actionMessage.type === 'danger' ? 'var(--danger-bg)' : actionMessage.type === 'warning' ? 'var(--warning-bg)' : 'var(--success-bg)', borderBottom: `1px solid ${actionMessage.type === 'danger' ? 'var(--danger)' : actionMessage.type === 'warning' ? 'var(--warning)' : 'var(--success)'}`, color: 'var(--text-main)', fontSize: '0.86rem', display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
              <div>{actionMessage.text}</div>
              <button onClick={() => setActionMessage(null)} style={{ color: 'var(--text-dim)', fontSize: '1rem', padding: '0 4px' }}>✕</button>
            </div>
          )}

          {/* CONTENT TABS */}
          <div style={{ padding: activeTab === 'repos' ? '0' : '2rem', flex: 1, display: 'flex', flexDirection: 'column' }}>
          {/* TAB 1: DASHBOARD */}
          {activeTab === 'dashboard' && (
            <div style={{ display: 'flex', flexDirection: 'column', gap: '1.5rem' }}>
              {/* METRIC CARDS */}
              <div style={{ display: 'grid', gridTemplateColumns: 'repeat(4, 1fr)', gap: '1.25rem' }}>
                <div style={{ background: 'var(--bg-card)', padding: '1.25rem', borderRadius: '8px', border: '1px solid var(--border-color)' }}>
                  <div style={{ fontSize: '0.75rem', color: 'var(--text-dim)', fontWeight: 600, textTransform: 'uppercase' }}>Total Scans</div>
                  <div style={{ fontSize: '1.75rem', fontWeight: 800, color: 'var(--text-main)', marginTop: '4px' }}>{metrics.total_scans}</div>
                  <div style={{ fontSize: '0.75rem', color: 'var(--text-muted)', marginTop: '4px' }}>Active repositories: {metrics.active_repositories}</div>
                </div>

                <div style={{ background: 'var(--bg-card)', padding: '1.25rem', borderRadius: '8px', border: '1px solid var(--border-color)' }}>
                  <div style={{ fontSize: '0.75rem', color: 'var(--text-dim)', fontWeight: 600, textTransform: 'uppercase' }}>Regression Pass Rate</div>
                  <div style={{ fontSize: '1.75rem', fontWeight: 800, color: metrics.pass_rate >= 90 ? 'var(--success)' : 'var(--warning)', marginTop: '4px' }}>
                    {metrics.pass_rate}%
                  </div>
                  <div style={{ fontSize: '0.75rem', color: 'var(--text-muted)', marginTop: '4px' }}>{metrics.total_passed} passed / {metrics.total_tests} executed</div>
                </div>

                <div style={{ background: 'var(--bg-card)', padding: '1.25rem', borderRadius: '8px', border: '1px solid var(--border-color)' }}>
                  <div style={{ fontSize: '0.75rem', color: 'var(--text-dim)', fontWeight: 600, textTransform: 'uppercase' }}>Detected Failures</div>
                  <div style={{ fontSize: '1.75rem', fontWeight: 800, color: metrics.total_failed > 0 ? 'var(--danger)' : 'var(--success)', marginTop: '4px' }}>
                    {metrics.total_failed}
                  </div>
                  <div style={{ fontSize: '0.75rem', color: 'var(--text-muted)', marginTop: '4px' }}>AI root cause diagnostics ready</div>
                </div>

                <div style={{ background: 'var(--bg-card)', padding: '1.25rem', borderRadius: '8px', border: '1px solid var(--border-color)' }}>
                  <div style={{ fontSize: '0.75rem', color: 'var(--text-dim)', fontWeight: 600, textTransform: 'uppercase' }}>Available Credits</div>
                  <div style={{ fontSize: '1.75rem', fontWeight: 800, color: 'var(--primary)', marginTop: '4px' }}>
                    {usageData?.usage?.scan_credits?.available ?? 50}
                  </div>
                  <div style={{ fontSize: '0.75rem', color: 'var(--text-muted)', marginTop: '4px' }}>{usageData?.usage?.fix_credits?.available ?? 20} Fix credits available</div>
                </div>
              </div>

              {/* LATEST SCAN EXECUTION DETAILS */}
              {currentScan ? (
                <div style={{ background: 'var(--bg-card)', borderRadius: '8px', border: '1px solid var(--border-color)', overflow: 'hidden' }}>
                  <div style={{ padding: '1.25rem', borderBottom: '1px solid var(--border-color)', display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
                    <div>
                      <div style={{ fontSize: '1.05rem', fontWeight: 700, color: 'var(--text-main)' }}>
                        Latest Scan Execution #{currentScan.id.slice(-6)}
                      </div>
                      <div style={{ fontSize: '0.8rem', color: 'var(--text-dim)', marginTop: '2px' }}>
                        Repository: <strong>{currentScan.repository?.full_name || 'Connected Repo'}</strong> • Branch: <code>{currentScan.branch}</code>
                      </div>
                    </div>
                    {currentScan.failed_tests > 0 && (
                      <button
                        onClick={handleProposeFix}
                        disabled={isProcessingFix}
                        style={{
                          background: 'linear-gradient(135deg, #8b5cf6, #3b82f6)',
                          color: '#fff',
                          padding: '0.5rem 1rem',
                          borderRadius: '6px',
                          fontSize: '0.84rem',
                          fontWeight: 600,
                          boxShadow: '0 2px 8px rgba(139, 92, 246, 0.3)',
                        }}
                      >
                        {isProcessingFix ? 'Analyzing...' : '⚡ Generate AI Fix Plan'}
                      </button>
                    )}
                  </div>

                  <div style={{ display: 'grid', gridTemplateColumns: '380px 1fr', minHeight: '400px' }}>
                    {/* TEST LIST */}
                    <div style={{ borderRight: '1px solid var(--border-color)', background: 'var(--bg-secondary)', overflowY: 'auto', maxHeight: '500px' }}>
                      <div style={{ padding: '0.75rem', borderBottom: '1px solid var(--border-color)', display: 'flex', gap: '6px' }}>
                        {['all', 'failed', 'passed'].map(f => (
                          <button
                            key={f}
                            onClick={() => setFilter(f)}
                            style={{
                              padding: '3px 8px',
                              borderRadius: '4px',
                              fontSize: '0.72rem',
                              fontWeight: 600,
                              textTransform: 'capitalize',
                              background: filter === f ? 'var(--border-color)' : 'transparent',
                              color: filter === f ? 'var(--text-main)' : 'var(--text-dim)',
                            }}
                          >
                            {f}
                          </button>
                        ))}
                      </div>

                      <div>
                        {currentScan.test_results
                          ?.filter(t => filter === 'all' || t.status === filter)
                          ?.map(t => (
                            <div
                              key={t.id}
                              onClick={() => setSelectedTest(t)}
                              style={{
                                padding: '0.85rem 1rem',
                                borderBottom: '1px solid rgba(255,255,255,0.05)',
                                background: selectedTest?.id === t.id ? 'var(--border-color)' : 'transparent',
                                cursor: 'pointer',
                                transition: 'all 0.1s ease',
                              }}
                            >
                              <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
                                <span style={{ fontSize: '0.72rem', fontWeight: 700, padding: '1px 6px', borderRadius: '3px', background: t.status === 'passed' ? 'var(--success-bg)' : 'var(--danger-bg)', color: t.status === 'passed' ? 'var(--success)' : 'var(--danger)' }}>
                                  {t.status.toUpperCase()}
                                </span>
                                <span style={{ fontSize: '0.72rem', color: 'var(--text-dim)' }}>{t.duration_ms}ms</span>
                              </div>
                              <div style={{ fontSize: '0.84rem', fontWeight: 600, color: 'var(--text-main)', marginTop: '5px' }}>
                                {t.test_name}
                              </div>
                              <div style={{ fontSize: '0.72rem', color: 'var(--text-dim)', marginTop: '2px', fontFamily: 'var(--font-mono)' }}>
                                {t.file_path}
                              </div>
                            </div>
                          ))}
                      </div>
                    </div>

                    {/* SELECTED TEST DETAILS */}
                    {selectedTest ? (
                      <div style={{ padding: '1.25rem', overflowY: 'auto', maxHeight: '500px' }}>
                        <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'flex-start', marginBottom: '1rem' }}>
                          <div>
                            <h3 style={{ fontSize: '1.05rem', fontWeight: 700, color: 'var(--text-main)' }}>{selectedTest.test_name}</h3>
                            <div style={{ fontSize: '0.78rem', color: 'var(--text-dim)' }}>Suite: {selectedTest.test_suite || 'Default Suite'}</div>
                          </div>
                          <span style={{ fontSize: '0.75rem', padding: '2px 8px', borderRadius: '4px', background: selectedTest.status === 'passed' ? 'var(--success-bg)' : 'var(--danger-bg)', color: selectedTest.status === 'passed' ? 'var(--success)' : 'var(--danger)', fontWeight: 700 }}>
                            {selectedTest.status.toUpperCase()}
                          </span>
                        </div>

                        {selectedTest.error_message && (
                          <div style={{ marginBottom: '1.25rem' }}>
                            <div style={{ fontSize: '0.72rem', color: 'var(--text-dim)', fontWeight: 700, textTransform: 'uppercase', marginBottom: '4px' }}>Error Details</div>
                            <div style={{ background: '#090d16', border: '1px solid var(--danger)', borderRadius: '6px', padding: '0.75rem', color: '#fca5a5', fontSize: '0.82rem', fontFamily: 'var(--font-mono)' }}>
                              {selectedTest.error_message}
                            </div>
                          </div>
                        )}

                        {selectedTest.ai_analysis && (
                          <div style={{ marginBottom: '1.25rem', background: '#1e1b4b', border: '1px solid #4338ca', borderRadius: '8px', padding: '1rem' }}>
                            <div style={{ display: 'flex', alignItems: 'center', gap: '6px', color: '#a5b4fc', fontSize: '0.8rem', fontWeight: 700, textTransform: 'uppercase', marginBottom: '0.5rem' }}>
                              <span>⚡</span>
                              <span>Autonomous AI Diagnostic Findings</span>
                            </div>
                            <div style={{ fontSize: '0.86rem', color: '#e0e7ff', lineHeight: 1.5 }}>
                              {selectedTest.ai_analysis.root_cause}
                            </div>
                            {selectedTest.ai_analysis.code_patch && (
                              <pre style={{ background: '#090d16', padding: '0.75rem', borderRadius: '4px', marginTop: '8px', fontSize: '0.78rem', color: '#38bdf8', overflowX: 'auto', fontFamily: 'var(--font-mono)' }}>
                                {selectedTest.ai_analysis.code_patch}
                              </pre>
                            )}
                          </div>
                        )}

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
                  <h3 style={{ fontSize: '1.2rem', color: 'var(--text-main)', marginBottom: '8px' }}>No Scans Executed Yet</h3>
                  <p style={{ color: 'var(--text-muted)', marginBottom: '1.5rem', maxWidth: '480px', margin: '0 auto 1.5rem' }}>
                    Connect a repository or trigger an automated scan to begin regression testing and security verification.
                  </p>
                  <button onClick={handleRunScan} style={{ background: 'var(--primary)', color: '#fff', padding: '0.65rem 1.3rem', borderRadius: '6px', fontWeight: 600 }}>
                    Trigger Initial Scan
                  </button>
                </div>
              )}
            </div>
          )}

          {/* TAB 2: SCANS & REPORTS */}
          {(activeTab === 'scans' || activeTab === 'reports') && (
            <div>
              <div style={{ background: 'var(--bg-card)', borderRadius: '8px', border: '1px solid var(--border-color)', padding: '1rem' }}>
                <h3 style={{ fontSize: '1rem', fontWeight: 700, color: 'var(--text-main)', marginBottom: '1rem' }}>Historical Scans</h3>
                {scans.length === 0 ? (
                  <div style={{ padding: '2rem', textAlign: 'center', color: 'var(--text-dim)' }}>
                    No scans recorded yet in this workspace. Trigger a scan from the header.
                  </div>
                ) : (
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
                )}
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
                          Required: {p.fix_credits_required} Fix credit
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
              {showNewProjectModal && (
                <div style={{ background: '#0f172a', borderRadius: '8px', border: '1px solid var(--primary)', padding: '1.5rem' }}>
                  <h3 style={{ fontSize: '1rem', fontWeight: 700, color: 'var(--text-main)', marginBottom: '1rem' }}>Create New Project</h3>
                  <form onSubmit={handleCreateProject} style={{ display: 'flex', flexDirection: 'column', gap: '1rem' }}>
                    <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: '1rem' }}>
                      <div>
                        <label style={{ display: 'block', fontSize: '0.78rem', color: 'var(--text-dim)', marginBottom: '4px' }}>Project Name</label>
                        <input
                          type="text"
                          value={newProjectName}
                          onChange={(e) => setNewProjectName(e.target.value)}
                          placeholder="e.g. Production Web App"
                          required
                          style={{ width: '100%', padding: '0.6rem 0.8rem', background: '#090d16', border: '1px solid var(--border-color)', borderRadius: '6px', color: '#fff', fontSize: '0.86rem' }}
                        />
                      </div>
                      <div>
                        <label style={{ display: 'block', fontSize: '0.78rem', color: 'var(--text-dim)', marginBottom: '4px' }}>Framework</label>
                        <select
                          value={newProjectFramework}
                          onChange={(e) => setNewProjectFramework(e.target.value)}
                          style={{ width: '100%', padding: '0.6rem 0.8rem', background: '#090d16', border: '1px solid var(--border-color)', borderRadius: '6px', color: '#fff', fontSize: '0.86rem' }}
                        >
                          <option value="react">React / Vite</option>
                          <option value="nextjs">Next.js</option>
                          <option value="vue">Vue.js</option>
                          <option value="laravel">Laravel Full-Stack</option>
                          <option value="fastapi">Python FastAPI</option>
                        </select>
                      </div>
                    </div>
                    <div>
                      <label style={{ display: 'block', fontSize: '0.78rem', color: 'var(--text-dim)', marginBottom: '4px' }}>Description</label>
                      <input
                        type="text"
                        value={newProjectDesc}
                        onChange={(e) => setNewProjectDesc(e.target.value)}
                        placeholder="Project purpose and scope"
                        style={{ width: '100%', padding: '0.6rem 0.8rem', background: '#090d16', border: '1px solid var(--border-color)', borderRadius: '6px', color: '#fff', fontSize: '0.86rem' }}
                      />
                    </div>
                    <div>
                      <label style={{ display: 'block', fontSize: '0.78rem', color: 'var(--text-dim)', marginBottom: '4px' }}>Bound Repository</label>
                      {repositories.length > 0 ? (
                        <select
                          value={newProjectRepoId || repositories[0]?.id}
                          onChange={(e) => setNewProjectRepoId(e.target.value)}
                          style={{ width: '100%', padding: '0.6rem 0.8rem', background: '#090d16', border: '1px solid var(--border-color)', borderRadius: '6px', color: '#fff', fontSize: '0.86rem' }}
                        >
                          {repositories.map(r => (
                            <option key={r.id} value={r.id}>{r.full_name}</option>
                          ))}
                        </select>
                      ) : (
                        <div style={{ fontSize: '0.82rem', color: 'var(--warning)', padding: '6px 0' }}>
                          No connected repositories found. Connect a repository first from the Repositories tab.
                        </div>
                      )}
                    </div>
                    <div style={{ display: 'flex', gap: '8px', justifyContent: 'flex-end', marginTop: '0.5rem' }}>
                      <button
                        type="button"
                        onClick={() => setShowNewProjectModal(false)}
                        style={{ background: 'var(--border-color)', color: 'var(--text-muted)', padding: '0.5rem 1rem', borderRadius: '6px', fontSize: '0.82rem' }}
                      >
                        Cancel
                      </button>
                      <button
                        type="submit"
                        disabled={isCreatingProject || repositories.length === 0}
                        style={{ background: 'var(--primary)', color: '#fff', padding: '0.5rem 1.25rem', borderRadius: '6px', fontSize: '0.82rem', fontWeight: 600 }}
                      >
                        {isCreatingProject ? 'Creating...' : 'Create Project'}
                      </button>
                    </div>
                  </form>
                </div>
              )}

              <div style={{ background: 'var(--bg-card)', borderRadius: '8px', border: '1px solid var(--border-color)', padding: '1.25rem' }}>
                <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '1rem' }}>
                  <div>
                    <h3 style={{ fontSize: '1rem', fontWeight: 700, color: 'var(--text-main)' }}>Configured Projects</h3>
                    <p style={{ fontSize: '0.82rem', color: 'var(--text-muted)' }}>
                      Target projects monitored by autonomous security and end-to-end regression suites.
                    </p>
                  </div>
                  {!showNewProjectModal && (
                    <button
                      onClick={() => setShowNewProjectModal(true)}
                      style={{ background: 'var(--primary)', color: '#fff', padding: '0.45rem 0.9rem', borderRadius: '6px', fontSize: '0.8rem', fontWeight: 600 }}
                    >
                      + Add Project
                    </button>
                  )}
                </div>

                {projects.length === 0 ? (
                  <div style={{ padding: '2.5rem', textAlign: 'center', color: 'var(--text-dim)' }}>
                    <div style={{ fontSize: '1rem', color: 'var(--text-main)', marginBottom: '6px' }}>No Projects Yet</div>
                    <p style={{ fontSize: '0.84rem', marginBottom: '1rem' }}>Create a project and link it to an authorized repository to begin testing.</p>
                    <button onClick={() => setShowNewProjectModal(true)} style={{ background: 'var(--primary)', color: '#fff', padding: '0.55rem 1.1rem', borderRadius: '6px', fontSize: '0.84rem', fontWeight: 600 }}>
                      Create Your First Project
                    </button>
                  </div>
                ) : (
                  <div style={{ display: 'flex', flexDirection: 'column', gap: '1rem' }}>
                    {projects.map(proj => (
                      <div key={proj.id} style={{ background: '#0f172a', borderRadius: '8px', border: '1px solid var(--border-color)', padding: '1.25rem' }}>
                        <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'flex-start' }}>
                          <div>
                            <div style={{ fontSize: '1.05rem', fontWeight: 700, color: 'var(--text-main)' }}>{proj.name}</div>
                            <div style={{ fontSize: '0.82rem', color: 'var(--text-muted)', marginTop: '4px' }}>{proj.description || 'No description provided'}</div>
                          </div>
                          <span style={{ fontSize: '0.72rem', padding: '2px 8px', borderRadius: '4px', background: 'var(--success-bg)', color: 'var(--success)', fontWeight: 700 }}>
                            {proj.status.toUpperCase()}
                          </span>
                        </div>

                        <div style={{ display: 'flex', gap: '1.5rem', marginTop: '1rem', fontSize: '0.78rem', color: 'var(--text-dim)', borderTop: '1px solid rgba(255,255,255,0.06)', paddingTop: '0.75rem' }}>
                          <div>Framework: <strong style={{ color: 'var(--text-main)' }}>{proj.framework || 'React'}</strong></div>
                          <div>Default Branch: <strong style={{ color: 'var(--text-main)' }}>{proj.default_branch || 'main'}</strong></div>
                          <div>Repository: <strong style={{ color: 'var(--text-main)' }}>{proj.repository?.full_name || 'Bound Repo'}</strong></div>
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

          {/* TAB 5: REPOSITORIES (SCREEN 3) */}
          {activeTab === 'repos' && (
            <RepositoriesScreen
              repositories={discoveredRepos}
              isLoading={isLoadingRepos}
              hasToken={hasGithubToken}
              onRefresh={fetchDiscoveredRepositories}
              onSelectRepo={async (repo) => {
                if (!activeWorkspace) return;
                try {
                  setActionMessage({ type: 'info', text: `Selecting repository ${repo.full_name}...` });
                  const csrf = getCsrfToken();
                  const res = await fetch(`/api/workspaces/${activeWorkspace.id}/repositories`, {
                    method: 'POST',
                    headers: {
                      'Content-Type': 'application/json',
                      'Accept': 'application/json',
                      'X-CSRF-TOKEN': csrf,
                    },
                    credentials: 'same-origin',
                    body: JSON.stringify({
                      full_name: repo.full_name,
                      default_branch: repo.default_branch || 'main',
                      github_installation_id: repo.id ? String(repo.id) : '10000',
                    }),
                  });
                  if (res.ok) {
                    setActionMessage({ type: 'success', text: `Repository ${repo.full_name} is active.` });
                    await fetchWorkspaceData(activeWorkspace.id);
                  }
                } catch (err) {
                  console.error('Failed to select repo:', err);
                }
              }}
            />
          )}

          {/* TAB 6: USAGE & CREDITS */}
          {(activeTab === 'usage' || activeTab === 'settings') && (
            <div style={{ display: 'grid', gridTemplateColumns: 'repeat(2, 1fr)', gap: '1.25rem' }}>
              <div style={{ background: 'var(--bg-card)', borderRadius: '8px', border: '1px solid var(--border-color)', padding: '1.25rem' }}>
                <h3 style={{ fontSize: '1rem', fontWeight: 700, color: 'var(--text-main)', marginBottom: '1rem' }}>Scan Credits</h3>
                <div style={{ fontSize: '2rem', fontWeight: 800, color: 'var(--primary)' }}>
                  {usageData?.usage?.scan_credits?.available ?? 50}
                </div>
                <div style={{ fontSize: '0.8rem', color: 'var(--text-dim)', marginTop: '4px' }}>
                  {usageData?.usage?.scan_credits?.consumed ?? 0} consumed this period.
                </div>
              </div>

              <div style={{ background: 'var(--bg-card)', borderRadius: '8px', border: '1px solid var(--border-color)', padding: '1.25rem' }}>
                <h3 style={{ fontSize: '1rem', fontWeight: 700, color: 'var(--text-main)', marginBottom: '1rem' }}>Fix Credits</h3>
                <div style={{ fontSize: '2rem', fontWeight: 800, color: 'var(--purple)' }}>
                  {usageData?.usage?.fix_credits?.available ?? 20}
                </div>
                <div style={{ fontSize: '0.8rem', color: 'var(--text-dim)', marginTop: '4px' }}>
                  {usageData?.usage?.fix_credits?.consumed ?? 0} consumed this period.
                </div>
              </div>

              <div style={{ gridColumn: 'span 2', background: 'var(--bg-card)', borderRadius: '8px', border: '1px solid var(--border-color)', padding: '1.25rem' }}>
                <h3 style={{ fontSize: '1rem', fontWeight: 700, color: 'var(--text-main)', marginBottom: '1rem' }}>Operational System Health</h3>
                <div style={{ display: 'grid', gridTemplateColumns: 'repeat(3, 1fr)', gap: '1rem', fontSize: '0.85rem' }}>
                  <div style={{ background: '#0f172a', padding: '0.85rem', borderRadius: '6px' }}>
                    <div style={{ color: 'var(--text-dim)', fontSize: '0.72rem' }}>CONTROL PLANE STATUS</div>
                    <div style={{ color: 'var(--success)', fontWeight: 700, marginTop: '2px' }}>Operational</div>
                  </div>
                  <div style={{ background: '#0f172a', padding: '0.85rem', borderRadius: '6px' }}>
                    <div style={{ color: 'var(--text-dim)', fontSize: '0.72rem' }}>DATABASE (NEON POSTGRES)</div>
                    <div style={{ color: 'var(--success)', fontWeight: 700, marginTop: '2px' }}>Connected & Persisted</div>
                  </div>
                  <div style={{ background: '#0f172a', padding: '0.85rem', borderRadius: '6px' }}>
                    <div style={{ color: 'var(--text-dim)', fontSize: '0.72rem' }}>ISOLATED RUNNER BOUNDARY</div>
                    <div style={{ color: 'var(--warning)', fontWeight: 700, marginTop: '2px' }}>Enforced (Fail-Closed)</div>
                  </div>
                </div>
              </div>
            </div>
          )}

          {/* TAB 7: NOTIFICATIONS */}
          {activeTab === 'notifications' && (
            <div style={{ background: 'var(--bg-card)', borderRadius: '8px', border: '1px solid var(--border-color)', padding: '1.25rem' }}>
              <h3 style={{ fontSize: '1rem', fontWeight: 700, color: 'var(--text-main)', marginBottom: '1rem' }}>Activity & Security Notifications</h3>
              {notifications.length === 0 ? (
                <div style={{ color: 'var(--text-dim)', fontSize: '0.88rem', padding: '1.5rem 0', textAlign: 'center' }}>
                  No new notifications.
                </div>
              ) : (
                <div style={{ display: 'flex', flexDirection: 'column', gap: '8px' }}>
                  {notifications.map(n => (
                    <div key={n.id} style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', padding: '0.85rem 1rem', background: '#0f172a', borderRadius: '6px', border: '1px solid var(--border-color)' }}>
                      <div>
                        <div style={{ fontSize: '0.86rem', fontWeight: 600, color: 'var(--text-main)' }}>{n.title}</div>
                        <div style={{ fontSize: '0.78rem', color: 'var(--text-muted)', marginTop: '2px' }}>{n.message}</div>
                      </div>
                      <span style={{ fontSize: '0.7rem', color: 'var(--text-dim)' }}>{n.created_at ? new Date(n.created_at).toLocaleDateString() : 'Just now'}</span>
                    </div>
                  ))}
                </div>
              )}
            </div>
          )}
        </div>
      </main>
      </div>
    </div>
  );
}
