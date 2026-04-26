import { expect, test } from '@playwright/test';
import { newCustomer, registerCustomer, login, logout } from '../helpers/customer';
import { gotoProduct, addCurrentProductToCart, gotoCart, expectCartItemCount } from '../helpers/cart';

test.describe('Smoke', () => {
  test('home is reachable', async ({ page }) => {
    await page.goto('/');
    await expect(page).toHaveTitle(/Thelia/i);
  });

  test('product page renders an add-to-cart form', async ({ page }) => {
    await gotoProduct(page, 'horatio');
    await expect(page.locator('form[name="thelia_cart_add"] button.Button--fill').first()).toBeVisible();
  });

  test('full register + login + add-to-cart + logout', async ({ page }) => {
    const customer = newCustomer();

    await registerCustomer(page, customer);
    await expect(page).toHaveURL(/\/customer\/(login|activation)/);

    await login(page, customer.email, customer.password);
    await expect(page).toHaveURL(/\/account/);

    await gotoProduct(page, 'horatio');
    await addCurrentProductToCart(page);
    await gotoCart(page);
    await expectCartItemCount(page, 1);

    await logout(page);
  });
});
