import { expect, test } from '@playwright/test';

test.describe('Navigation', () => {
  test('home renders the menu and product highlights', async ({ page }) => {
    await page.goto('/');
    await expect(page.locator('header')).toBeVisible();
    // Home shows at least one product card link to the product page (rewritten URL).
    await expect(page.locator('a[href$=".html"]').first()).toBeVisible();
  });

  test('navigate from home to a category page', async ({ page }) => {
    await page.goto('/');
    const categoryLink = page.getByRole('link', { name: /Chairs/i }).first();
    await categoryLink.click();
    await expect(page).toHaveURL(/chairs|category/i);
  });

  test('navigate from home to a product page (slug-rewriting)', async ({ page }) => {
    await page.goto('/');
    const productLink = page.locator('a[href$="/horatio.html"]').first();
    await productLink.click();
    await expect(page).toHaveURL(/horatio\.html/);
    await expect(page.locator('form[name="thelia_cart_add"]')).toBeAttached();
  });

  test('renders a 404 for a missing slug', async ({ page }) => {
    const response = await page.goto('/does-not-exist-please.html');
    expect(response?.status()).toBe(404);
  });

  test('language switch keeps you on the same page', async ({ page }) => {
    await page.goto('/');
    await page.goto('/?lang=fr_FR');
    await expect(page).toHaveURL(/lang=fr_FR/);
    await expect(page.locator('html')).toHaveAttribute('lang', /fr/);
  });

  test('contact page is reachable with a working form', async ({ page }) => {
    await page.goto('/contact');
    await expect(page.locator('form').first()).toBeVisible();
  });

  test('product page boots the LiveComponent without crashing', async ({ page }) => {
    // Smoke test the LiveComponent serializer — historically a brittle area.
    const response = await page.goto('/horatio.html');
    expect(response?.status()).toBe(200);
    await expect(page.locator('[data-live-name-value="Flexy:Pages:Product"]')).toBeAttached();
  });
});
