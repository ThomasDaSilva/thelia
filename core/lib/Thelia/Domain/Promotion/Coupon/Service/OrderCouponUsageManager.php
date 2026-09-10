<?php

declare(strict_types=1);

/*
 * This file is part of the Thelia package.
 * http://www.thelia.net
 *
 * (c) OpenStudio <info@thelia.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Thelia\Domain\Promotion\Coupon\Service;

use Thelia\Model\CouponQuery;
use Thelia\Model\Order;
use Thelia\Model\OrderCouponQuery;

/**
 * Counts, on the coupons themselves, the usages an order consumes or gives back.
 *
 * A coupon remembered on an order (order_coupon) starts with its usage canceled:
 * it counts once the order is paid, and stops counting when the order stops
 * being paid. Both moves are idempotent, so calling them twice changes nothing.
 */
final readonly class OrderCouponUsageManager
{
    public function __construct(
        private CouponManager $couponManager,
    ) {
    }

    /**
     * Counts the usages of the coupons of this order that are not counted yet.
     *
     * @return int how many coupon usages were counted
     */
    public function consume(Order $order): int
    {
        $orderCoupons = OrderCouponQuery::create()
            ->filterByUsageCanceled(true)
            ->findByOrderId($order->getId());

        foreach ($orderCoupons as $orderCoupon) {
            if (null !== $coupon = CouponQuery::create()->findOneByCode($orderCoupon->getCode())) {
                $this->couponManager->decrementQuantity($coupon, $order->getCustomerId());
            }

            $orderCoupon->setUsageCanceled(false)->save();
        }

        return \count($orderCoupons);
    }

    /**
     * Gives back the usages of the coupons of this order that are counted.
     *
     * @return int how many coupon usages were given back
     */
    public function release(Order $order): int
    {
        $orderCoupons = OrderCouponQuery::create()
            ->filterByUsageCanceled(false)
            ->findByOrderId($order->getId());

        foreach ($orderCoupons as $orderCoupon) {
            if (null !== $coupon = CouponQuery::create()->findOneByCode($orderCoupon->getCode())) {
                $this->couponManager->incrementQuantity($coupon, $order->getCustomerId());
            }

            $orderCoupon->setUsageCanceled(true)->save();
        }

        return \count($orderCoupons);
    }
}
