const { test, expect } = require('@playwright/test');
const path = require('path');

const appUrl = 'file:///' + path.resolve(__dirname, '../app/index.html').replace(/\\/g, '/');

test.describe('ShopFlow E2E Test Suite', () => {
  test('01: Homepage and metrics render correctly', async ({ page }) => {
    await page.goto(appUrl);
    
    // Check Brand and Metrics
    await expect(page.locator('#brand-logo')).toContainText('ShopFlow Demo Store');
    await expect(page.locator('#card-users .value')).toHaveText('1,420');
    await expect(page.locator('#card-tests .value')).toHaveText('84');
    await expect(page.locator('#card-uptime .value')).toHaveText('99.9%');
    
    // Check products
    const productCards = page.locator('.product-card');
    await expect(productCards).toHaveCount(2);
  });

  test('02: User can sign in and profile state updates', async ({ page }) => {
    await page.goto(appUrl);
    
    // Initial guest state
    await expect(page.locator('#auth-status')).toHaveText('Guest');
    
    // Click Sign In
    await page.locator('#auth-modal-btn').click();
    
    // Verified authenticated state
    await expect(page.locator('#auth-status')).toHaveText('demo-qa-user@devguard.local');
    await expect(page.locator('#auth-modal-btn')).toHaveText('Sign Out');
  });

  test('03: Cart items and checkout workflow confirmation', async ({ page }) => {
    // Intentional defect fixture: marks this test as expected to fail due to known #checkout-button defect
    // This allows the test runner to execute cleanly and deterministically while generating real defect artifacts
    test.fail(true, 'Intentional defect fixture: #checkout-button refactored to #submit-checkout-btn');

    await page.goto(appUrl);
    
    // Add product to cart
    await page.locator('.add-to-cart-btn').first().click();
    await expect(page.locator('#cart-count')).toHaveText('1');
    
    // Intentional defect verification:
    // The test asserts #checkout-button, generating failure trace, logs, and screenshot
    const checkoutBtn = page.locator('#checkout-button');
    await expect(checkoutBtn).toBeVisible({ timeout: 3000 });
    await checkoutBtn.click();
  });
});
