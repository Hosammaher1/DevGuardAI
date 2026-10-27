# Instructions

- Following Playwright test failed.
- Explain why, be concise, respect Playwright best practices.
- Provide a snippet of code with the fix, if possible.

# Test info

- Name: demo.spec.js >> ShopFlow E2E Test Suite >> 03: Cart items and checkout workflow confirmation
- Location: tests\demo.spec.js:35:3

# Error details

```
Error: expect(locator).toBeVisible() failed

Locator: locator('#checkout-button')
Expected: visible
Timeout: 3000ms
Error: element(s) not found

Call log:
  - Expect "toBeVisible" locator('#checkout-button') with timeout 3000ms
  - waiting for locator('#checkout-button')

```

```yaml
- text: ⚡ ShopFlow Demo Store Guest
- button "Sign In"
- heading "Active Users" [level=3]
- text: 1,420
- heading "Automated Runs" [level=3]
- text: "84"
- heading "System Health" [level=3]
- text: 99.9%
- heading "Catalog Items" [level=2]
- heading "Enterprise Cloud Runner" [level=3]
- paragraph: $99/mo
- paragraph: Fast parallel isolated testing worker.
- button "Add to Cart"
- heading "AI Diagnostic Core" [level=3]
- paragraph: $149/mo
- paragraph: Gemini automated root-cause engine.
- button "Add to Cart"
- text: Shopping Cart (1 items) 1 item(s) selected
- button "Complete Purchase"
```

# Test source

```ts
  1  | const { test, expect } = require('@playwright/test');
  2  | const path = require('path');
  3  | 
  4  | const appUrl = 'file:///' + path.resolve(__dirname, '../app/index.html').replace(/\\/g, '/');
  5  | 
  6  | test.describe('ShopFlow E2E Test Suite', () => {
  7  |   test('01: Homepage and metrics render correctly', async ({ page }) => {
  8  |     await page.goto(appUrl);
  9  |     
  10 |     // Check Brand and Metrics
  11 |     await expect(page.locator('#brand-logo')).toContainText('ShopFlow Demo Store');
  12 |     await expect(page.locator('#card-users .value')).toHaveText('1,420');
  13 |     await expect(page.locator('#card-tests .value')).toHaveText('84');
  14 |     await expect(page.locator('#card-uptime .value')).toHaveText('99.9%');
  15 |     
  16 |     // Check products
  17 |     const productCards = page.locator('.product-card');
  18 |     await expect(productCards).toHaveCount(2);
  19 |   });
  20 | 
  21 |   test('02: User can sign in and profile state updates', async ({ page }) => {
  22 |     await page.goto(appUrl);
  23 |     
  24 |     // Initial guest state
  25 |     await expect(page.locator('#auth-status')).toHaveText('Guest');
  26 |     
  27 |     // Click Sign In
  28 |     await page.locator('#auth-modal-btn').click();
  29 |     
  30 |     // Verified authenticated state
  31 |     await expect(page.locator('#auth-status')).toHaveText('demo-qa-user@devguard.local');
  32 |     await expect(page.locator('#auth-modal-btn')).toHaveText('Sign Out');
  33 |   });
  34 | 
  35 |   test('03: Cart items and checkout workflow confirmation', async ({ page }) => {
  36 |     // Intentional defect fixture: marks this test as expected to fail due to known #checkout-button defect
  37 |     // This allows the test runner to execute cleanly and deterministically while generating real defect artifacts
  38 |     test.fail(true, 'Intentional defect fixture: #checkout-button refactored to #submit-checkout-btn');
  39 | 
  40 |     await page.goto(appUrl);
  41 |     
  42 |     // Add product to cart
  43 |     await page.locator('.add-to-cart-btn').first().click();
  44 |     await expect(page.locator('#cart-count')).toHaveText('1');
  45 |     
  46 |     // Intentional defect verification:
  47 |     // The test asserts #checkout-button, generating failure trace, logs, and screenshot
  48 |     const checkoutBtn = page.locator('#checkout-button');
> 49 |     await expect(checkoutBtn).toBeVisible({ timeout: 3000 });
     |                               ^ Error: expect(locator).toBeVisible() failed
  50 |     await checkoutBtn.click();
  51 |   });
  52 | });
  53 | 
```