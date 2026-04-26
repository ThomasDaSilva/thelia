import { ddevMysql } from './db';

/**
 * Ensures the CustomDelivery module is wired to a delivery zone with a flat-rate slice
 * that covers metropolitan France (area #1 in the demo dataset). Idempotent.
 *
 * The default `bin/install --with-demo` run creates areas and modules but never links
 * them in `area_delivery_module`, so no delivery method shows up in the checkout.
 */
export async function ensureCustomDeliveryConfigured(): Promise<void> {
  await ddevMysql(`
    INSERT IGNORE INTO area_delivery_module (area_id, delivery_module_id)
    SELECT 1, m.id FROM module m WHERE m.code = 'CustomDelivery';
    INSERT IGNORE INTO area_delivery_module (area_id, delivery_module_id)
    SELECT 2, m.id FROM module m WHERE m.code = 'CustomDelivery';
  `);
  const sliceCount = await ddevMysql('SELECT COUNT(*) FROM custom_delivery_slice');
  if (Number(sliceCount) === 0) {
    await ddevMysql(`
      INSERT INTO custom_delivery_slice (area_id, price_max, weight_max, price)
      VALUES (1, 10000, 10000, 11), (2, 10000, 10000, 10);
    `);
  }
}
