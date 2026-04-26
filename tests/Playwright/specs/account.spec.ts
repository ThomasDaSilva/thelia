import { expect } from '@playwright/test';
import { test } from '../fixtures/customer';
import { login } from '../helpers/customer';
import {
  countAddresses,
  createAddress,
  deleteFirstNonDefaultAddress,
  gotoAddresses,
  setNonDefaultAsDefault,
} from '../helpers/account';

test.describe('Account', () => {
  test('after register, addresses list shows the main address', async ({ authedPage }) => {
    await gotoAddresses(authedPage);
    expect(await countAddresses(authedPage)).toBe(1);
  });

  test('create a second address and delete it', async ({ authedPage, freshCustomer }) => {
    await createAddress(authedPage, {
      ...freshCustomer.address,
      label: 'Office',
      address1: '5 avenue du Test',
      city: 'Lyon',
      zipcode: '69000',
    });
    expect(await countAddresses(authedPage)).toBe(2);

    await deleteFirstNonDefaultAddress(authedPage);
    expect(await countAddresses(authedPage)).toBe(1);
  });

  test('set the new address as default', async ({ authedPage, freshCustomer }) => {
    await createAddress(authedPage, {
      ...freshCustomer.address,
      label: 'Secondary',
      address1: '99 boulevard Test',
      city: 'Lyon',
      zipcode: '69001',
    });
    await setNonDefaultAsDefault(authedPage);
    // Now the formerly-non-default is the default; one Favorite (selected) remains, no other.
    await gotoAddresses(authedPage);
    const favorites = authedPage.locator('.AddressCard .Favorite.selected');
    await expect(favorites).toHaveCount(1);
  });

  test('change password and re-login with the new one', async ({ authedPage, freshCustomer }) => {
    await authedPage.goto('/account');
    const newPassword = 'NewSecret!9';
    await authedPage.fill('input[name="thelia_customer_password_update[password_old]"]', freshCustomer.password);
    await authedPage.fill('input[name="thelia_customer_password_update[password]"]', newPassword);
    await authedPage.fill('input[name="thelia_customer_password_update[password_confirm]"]', newPassword);
    await Promise.all([
      authedPage.waitForURL(/\/account/),
      authedPage.locator('form[name="thelia_customer_password_update"] button[type="submit"]').click(),
    ]);

    // Logout, then login with the new password.
    await authedPage.goto('/customer/logout');
    await login(authedPage, freshCustomer.email, newPassword);
    await expect(authedPage).toHaveURL(/\/account/);
  });
});
