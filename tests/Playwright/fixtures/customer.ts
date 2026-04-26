import { test as base, type Page } from '@playwright/test';
import { newCustomer, registerCustomer, login, type Customer } from '../helpers/customer';

type CustomerFixtures = {
  freshCustomer: Customer;
  authedPage: Page;
};

export const test = base.extend<CustomerFixtures>({
  freshCustomer: async ({ browser }, use) => {
    // Provision a customer once per test that asks for it.
    const customer = newCustomer();
    const ctx = await browser.newContext({ ignoreHTTPSErrors: true });
    const page = await ctx.newPage();
    await registerCustomer(page, customer);
    await ctx.close();
    await use(customer);
  },

  authedPage: async ({ page, freshCustomer }, use) => {
    await login(page, freshCustomer.email, freshCustomer.password);
    await use(page);
  },
});

export { expect } from '@playwright/test';
