const { execSync } = require('child_process');
const fs = require('fs');
const path = require('path');

function runDemoScan() {
  const root = __dirname;
  const testResultsDir = path.join(root, 'test-results');
  
  if (!fs.existsSync(testResultsDir)) {
    fs.mkdirSync(testResultsDir, { recursive: true });
  }

  let exitCode = 0;
  let rawOutput = '';
  try {
    rawOutput = execSync('npx playwright test', {
      cwd: root,
      encoding: 'utf-8',
      stdio: ['ignore', 'pipe', 'pipe']
    });
  } catch (err) {
    exitCode = err.status || 1;
    rawOutput = (err.stdout || '') + '\n' + (err.stderr || '');
  }

  // Parse results.json
  const resultsJsonPath = path.join(testResultsDir, 'results.json');
  let parsedResults = null;
  if (fs.existsSync(resultsJsonPath)) {
    try {
      parsedResults = JSON.parse(fs.readFileSync(resultsJsonPath, 'utf-8'));
    } catch (e) {
      console.error('Failed to parse results.json:', e.message);
    }
  }

  const summary = {
    total: 0,
    passed: 0,
    failed: 0,
    skipped: 0,
    duration_ms: 0,
    tests: [],
    raw_logs: rawOutput
  };

  if (parsedResults && parsedResults.suites) {
    function processSuite(suite) {
      if (suite.specs) {
        for (const spec of suite.specs) {
          summary.total++;
          const testCase = spec.tests ? spec.tests[0] : null;
          const result = testCase && testCase.results ? testCase.results[0] : null;
          const status = result ? (result.status === 'passed' ? 'passed' : 'failed') : 'skipped';
          if (status === 'passed') summary.passed++;
          else if (status === 'failed') summary.failed++;
          else summary.skipped++;

          const duration = result ? result.duration : 0;
          summary.duration_ms += duration;

          let screenshotPath = null;
          let screenshotBase64 = null;
          let errorMessage = null;
          let stackTrace = null;

          if (result && result.error) {
            errorMessage = result.error.message || '';
            stackTrace = result.error.stack || '';
          }

          if (result && result.attachments) {
            const ssAttachment = result.attachments.find(a => a.name === 'screenshot' || a.contentType === 'image/png');
            if (ssAttachment && fs.existsSync(ssAttachment.path)) {
              screenshotPath = ssAttachment.path;
              // Copy to canonical latest screenshot
              const canonicalPath = path.join(testResultsDir, 'latest-failure.png');
              fs.copyFileSync(screenshotPath, canonicalPath);
              screenshotBase64 = fs.readFileSync(screenshotPath).toString('base64');
            }
          }

          summary.tests.push({
            name: spec.title,
            file_path: spec.file || 'tests/demo.spec.js',
            status: status,
            duration_ms: duration,
            error_message: errorMessage,
            stack_trace: stackTrace,
            screenshot_path: screenshotPath,
            screenshot_base64: screenshotBase64
          });
        }
      }
      if (suite.suites) {
        for (const s of suite.suites) {
          processSuite(s);
        }
      }
    }

    for (const suite of parsedResults.suites) {
      processSuite(suite);
    }
  }

  // Write summary output
  const summaryPath = path.join(testResultsDir, 'scan-summary.json');
  fs.writeFileSync(summaryPath, JSON.stringify(summary, null, 2));
  console.log(JSON.stringify({ status: 'done', summary_path: summaryPath, summary: { total: summary.total, passed: summary.passed, failed: summary.failed } }));
  return summary;
}

if (require.main === module) {
  runDemoScan();
}

module.exports = { runDemoScan };
