import { expect, type Page } from '@playwright/test';
import type { Customer } from './customer';

export type AddressInput = Customer['address'] & {
  label?: string;
  firstname?: string;
  lastname?: string;
  isDefault?: boolean;
};

export async function gotoAddresses(page: Page): Promise<void> {
  await page.goto('/account/addresses');
}

export async function countAddresses(page: Page): Promise<number> {
  return page.locator('.AddressCard').count();
}

export async function fillAddressForm(page: Page, address: AddressInput): Promise<void> {
  await selectFirst(page, 'select[name$="[title]"]');
  if (address.label) await safeFill(page, 'input[name$="[label]"]', address.label);
  await safeFill(page, 'input[name$="[firstname]"]', address.firstname ?? 'E2E');
  await safeFill(page, 'input[name$="[lastname]"]', address.lastname ?? 'Tester');
  await safeFill(page, 'input[name$="[address1]"]', address.address1);
  await safeFill(page, 'input[name$="[city]"]', address.city);
  await safeFill(page, 'input[name$="[zipcode]"]', address.zipcode);
  await selectCountryFor(page, address);
  await page.waitForTimeout(500);
  await selectFirst(page, 'select[name$="[state]"]', true);
  await safeFill(page, 'input[name$="[phone]"]', address.phone);
  await safeFill(page, 'input[name$="[cellphone]"]', address.phone);
  if (address.isDefault) {
    const cb = page.locator('label:has(input[name$="[is_default]"])');
    if (await cb.count() > 0) await cb.first().click();
  }
}

export async function createAddress(page: Page, address: AddressInput): Promise<void> {
  await page.goto('/account/address/new');
  await fillAddressForm(page, address);
  await page.locator('form button[type="submit"]').first().click();
  await expect(page).toHaveURL(/\/account\/addresses/);
}

export async function deleteFirstNonDefaultAddress(page: Page): Promise<void> {
  await gotoAddresses(page);
  const deleteButton = page.locator('.AddressCard button[data-modal="confirmDeleteAddress"]').first();
  await deleteButton.click();
  // Modal opens — confirm.
  const confirm = page.locator('[data-modal-target="confirm"][href*="/account/address/delete/"]').first();
  await Promise.all([
    page.waitForURL(/\/account\/addresses/),
    confirm.click(),
  ]);
}

export async function setNonDefaultAsDefault(page: Page): Promise<void> {
  await gotoAddresses(page);
  // The Favorite button is rendered only when the address is NOT default.
  const favoriteButton = page.locator('.AddressCard .Favorite[data-modal="confirmDefaultAdress"]').first();
  await favoriteButton.click();
  const confirm = page.locator('[data-modal-target="confirm"][href*="/account/address/default/"]').first();
  await Promise.all([
    page.waitForURL(/\/account\/addresses/),
    confirm.click(),
  ]);
}
async function selectCountryFor(page: Page, addr: { countryCode: string; countryLabel: string }): Promise<void> {
  const select = page.locator('select[name$="[country]"]');
  if (await select.count() === 0) return;
  const byCode = select.locator(`option[data-iso-code="${addr.countryCode}"]`);
  if (await byCode.count() > 0) {
    const value = await byCode.first().getAttribute('value');
    if (value) {
      await select.selectOption({ value });
      return;
    }
  }
  try {
    await select.selectOption({ label: addr.countryLabel });
    return;
  } catch {
    /* fall through */
  }
  await selectFirst(page, 'select[name$="[country]"]');
}

async function selectFirst(page: Page, selector: string, _optional = false): Promise<void> {
  const select = page.locator(selector);
  if (await select.count() === 0) return;
  const firstOption = select.locator('option:not([value=""])').first();
  if (await firstOption.count() === 0) return;
  const value = await firstOption.getAttribute('value');
  if (value !== null) await select.selectOption({ value });
}

async function safeFill(page: Page, selector: string, value: string): Promise<void> {
  const input = page.locator(selector);
  if (await input.count() === 0) return;
  await input.first().fill(value);
}
