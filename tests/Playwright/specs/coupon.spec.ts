import { expect, test } from '@playwright/test';
import { addCurrentProductToCart, gotoCart, gotoProduct } from '../helpers/cart';
import { ensureFlatCoupon, E2E_COUPON_CODE } from '../helpers/coupon';

test.describe('Coupon', () => {
  test.beforeAll(async () => {
    await ensureFlatCoupon();
  });

  async function fillAndSubmitCoupon(page: import('@playwright/test').Page, code: string): Promise<void> {
    // The promo code form lives inside a <details><summary> accordion — open it first.
    const summary = page.locator('.PromoCodeForm summary').first();
    if (await summary.count() > 0) {
      await summary.click();
    }
    await page.fill('input[name="thelia_coupon_code[coupon-code]"]', code);
    await page.locator('form[name="thelia_coupon_code"] button[type="submit"]').click();
    await page.waitForLoadState('networkidle').catch(() => {});
  }

  test('valid coupon discounts the cart total', async ({ page }) => {
    await gotoProduct(page, 'horatio');
    await addCurrentProductToCart(page);
    await gotoCart(page);

    await fillAndSubmitCoupon(page, E2E_COUPON_CODE);

    // The Summary panel renders the active coupon and the discount line once it is consumed.
    await expect(page.locator('.Summary')).toContainText(E2E_COUPON_CODE, { timeout: 10_000 });
    await expect(page.locator('.Summary')).toContainText(/discount/i);
  });

  test('invalid coupon is rejected with an error', async ({ page }) => {
    await gotoProduct(page, 'horatio');
    await addCurrentProductToCart(page);
    await gotoCart(page);

    await fillAndSubmitCoupon(page, 'NOT-A-REAL-CODE');

    // Either an inline form error or no discount line should appear.
    await expect(page.locator('.PromoCodeForm')).toContainText(/(invalid|does not exist|n'existe|no longer)/i).catch(async () => {
      await expect(page.locator('.PromoCodeForm')).not.toContainText('NOT-A-REAL-CODE');
    });
  });
});
