import { expect, test } from '@playwright/test';
import { login, logout, newCustomer, registerCustomer } from '../helpers/customer';

test.describe('Auth', () => {
  test('register a new customer and reach the login page', async ({ page }) => {
    const customer = newCustomer();
    await registerCustomer(page, customer);
    await expect(page).toHaveURL(/\/customer\/(login|activation)/);
  });

  test('register then login then logout', async ({ page }) => {
    const customer = newCustomer();
    await registerCustomer(page, customer);

    await login(page, customer.email, customer.password);
    await expect(page).toHaveURL(/\/account/);

    await logout(page);
    // After logout, /account should redirect to login.
    await page.goto('/account');
    await expect(page).toHaveURL(/\/customer\/login/);
  });

  test('login fails on bad password', async ({ page }) => {
    const customer = newCustomer();
    await registerCustomer(page, customer);

    await page.goto('/customer/login');
    await page.fill('#thelia_customer_login_email', customer.email);
    await page.fill('#thelia_customer_login_password', 'totally-wrong-password');
    await page.locator('form[name="thelia_customer_login"] button[type="submit"]').click();

    // Stays on login page with an error.
    await expect(page).toHaveURL(/\/customer\/login/);
  });

  test('register rejects an existing email', async ({ page }) => {
    const customer = newCustomer();
    await registerCustomer(page, customer);

    // Try to register again with the same email.
    await page.goto('/customer/register');
    await page.fill('#flexybundle_form_customer_register_form_firstname', 'Other');
    await page.fill('#flexybundle_form_customer_register_form_lastname', 'Person');
    await page.fill('#flexybundle_form_customer_register_form_email', customer.email);
    await page.fill('#flexybundle_form_customer_register_form_password', 'AnotherPassword!9');
    await page.locator('form[name="flexybundle_form_customer_register_form"] button[type="submit"]').click();

    // Server should keep us on the register page (or send us back) with an error.
    await expect(page).toHaveURL(/\/customer\/register/);
  });
});
