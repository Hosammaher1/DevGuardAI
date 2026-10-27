// @ts-check
const { defineConfig, devices } = require('@playwright/test');

module.exports = defineConfig({
  testDir: './tests',
  timeout: 15000,
  expect: {
    timeout: 5000
  },
  fullyParallel: false,
  reporter: [
    ['json', { outputFile: 'test-results/results.json' }],
    ['list']
  ],
  use: {
    screenshot: 'only-on-failure',
    trace: 'retain-on-failure',
    headless: true,
  },
  projects: [
    {
      name: 'chromium',
      use: { ...devices['Desktop Chrome'] },
    },
  ],
});
