import { ddevMysql } from './db';

export const E2E_COUPON_CODE = 'E2E10';

/**
 * Ensures a flat 10 EUR off coupon `E2E10` exists in the DB.
 * Idempotent — safe to call before each test that needs the coupon.
 */
export async function ensureFlatCoupon(): Promise<void> {
  const existing = await ddevMysql(`SELECT id FROM coupon WHERE code = '${E2E_COUPON_CODE}' LIMIT 1`);
  if (existing.length > 0) return;

  const effects = JSON.stringify({ amount: 10 }).replace(/'/g, "''");
  // Conditions are persisted as base64-encoded JSON (see ConditionFactory::serializeConditionCollection).
  // A coupon must hold at least one condition — `MatchForEveryone` always applies.
  const conditions = Buffer.from(JSON.stringify([
    { conditionServiceId: 'thelia.condition.match_for_everyone', operators: {}, values: {} },
  ])).toString('base64');
  const sql = `
    INSERT INTO coupon (code, type, serialized_effects, is_enabled, expiration_date,
                         max_usage, is_cumulative, is_removing_postage, is_available_on_special_offers,
                         is_used, serialized_conditions, per_customer_usage_count, created_at, updated_at)
    VALUES ('${E2E_COUPON_CODE}',
            'thelia.coupon.type.remove_x_amount',
            '${effects}',
            1,
            DATE_ADD(NOW(), INTERVAL 1 YEAR),
            -1, 0, 0, 1,
            0, '${conditions}',
            0,
            NOW(), NOW());
  `.trim();
  await ddevMysql(sql);

  const couponId = await ddevMysql(`SELECT id FROM coupon WHERE code = '${E2E_COUPON_CODE}' LIMIT 1`);
  if (!couponId) throw new Error('Failed to insert coupon');

  await ddevMysql(`
    INSERT INTO coupon_i18n (id, locale, title, short_description, description)
    VALUES (${couponId}, 'en_US', 'E2E flat 10', '10 off for E2E', '10 off for E2E'),
           (${couponId}, 'fr_FR', 'E2E flat 10', '10 EUR pour E2E', '10 EUR pour E2E');
  `.trim());
}
