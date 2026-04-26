# Thelia 3 — Playwright E2E

End-to-end tests for the Flexy front-office.

## Setup

```bash
cd tests/Playwright
npm install
npx playwright install chromium
```

## Run

```bash
# All specs (headless)
npm test

# UI mode (interactive)
npm run test:ui

# A single spec
npx playwright test specs/auth.spec.ts

# Override base URL
BASE_URL=https://thelia-3.ddev.site npm test
```

The DDEV stack must be up and the demo data installed (`bin/install --with-demo`).
A demo coupon `E2E10` is created on demand by `helpers/coupon.ts`.

## Layout

- `specs/` — test files (one per flow)
- `helpers/` — page actions and fixtures (register, login, cart, checkout)
- `fixtures/` — custom Playwright fixtures (authed customer, products, coupons)

## Reports

After a run, open `playwright-report/index.html` (`npm run report`).
Failures keep traces, screenshots, and videos under `test-results/`.
