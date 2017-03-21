<?php
/**
 * Copyright © 2013-2017 Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Braintree\Test\TestStep;

use Magento\Checkout\Test\Constraint\AssertGrandTotalOrderReview;
use Magento\Checkout\Test\Constraint\AssertBillingAddressAbsentInPayment;
use Magento\Checkout\Test\Page\CheckoutOnepage;
use Magento\Checkout\Test\Page\CheckoutOnepageSuccess;
use Magento\Mtf\Fixture\FixtureFactory;
use Magento\Mtf\TestStep\TestStepInterface;
use Magento\Sales\Test\Fixture\OrderInjectable;

/**
 * Place order with Paypal in one page checkout.
 */
class PlaceOrderWithPaypalStep implements TestStepInterface
{
    /**
     * Onepage checkout page.
     *
     * @var CheckoutOnepage
     */
    private $checkoutOnepage;

    /**
     * Assert that Order Grand Total is correct on checkout page review block.
     *
     * @var AssertGrandTotalOrderReview
     */
    private $assertGrandTotalOrderReview;

    /**
     * Assert billing address is not present in selected payment method.
     *
     * @var AssertBillingAddressAbsentInPayment
     */
    private $assertBillingAddressAbsentInPayment;

    /**
     * One page checkout success page.
     *
     * @var CheckoutOnepageSuccess
     */
    private $checkoutOnepageSuccess;

    /**
     * Price array.
     *
     * @var array
     */
    private $prices;

    /**
     * Factory for fixtures.
     *
     * @var FixtureFactory
     */
    private $fixtureFactory;

    /**
     * Array of product entities.
     *
     * @var array
     */
    private $products;

    /**
     * Fixture OrderInjectable.
     *
     * @var OrderInjectable
     */
    private $order;

    /**
     * @param CheckoutOnepage $checkoutOnepage
     * @param AssertGrandTotalOrderReview $assertGrandTotalOrderReview
     * @param AssertBillingAddressAbsentInPayment $assertBillingAddressAbsentInPayment
     * @param CheckoutOnepageSuccess $checkoutOnepageSuccess
     * @param FixtureFactory $fixtureFactory
     * @param array $products
     * @param array $prices
     * @param OrderInjectable|null $order
     */
    public function __construct(
        CheckoutOnepage $checkoutOnepage,
        AssertGrandTotalOrderReview $assertGrandTotalOrderReview,
        AssertBillingAddressAbsentInPayment $assertBillingAddressAbsentInPayment,
        CheckoutOnepageSuccess $checkoutOnepageSuccess,
        FixtureFactory $fixtureFactory,
        array $products,
        array $prices = [],
        OrderInjectable $order = null
    ) {
        $this->checkoutOnepage = $checkoutOnepage;
        $this->assertGrandTotalOrderReview = $assertGrandTotalOrderReview;
        $this->assertBillingAddressAbsentInPayment = $assertBillingAddressAbsentInPayment;
        $this->checkoutOnepageSuccess = $checkoutOnepageSuccess;
        $this->fixtureFactory = $fixtureFactory;
        $this->products = $products;
        $this->prices = $prices;
        $this->order = $order;
    }

    /**
     * Place order after checking order totals on review step.
     *
     * @return array
     */
    public function run()
    {
        if (isset($this->prices['grandTotal'])) {
            $this->assertGrandTotalOrderReview->processAssert($this->checkoutOnepage, $this->prices['grandTotal']);
        }

        $this->assertBillingAddressAbsentInPayment->processAssert($this->checkoutOnepage);

        $parentWindow = $this->checkoutOnepage->getPaymentBlock()
            ->getSelectedPaymentMethodBlock()
            ->clickPayWithPaypal();
        $this->checkoutOnepage->getBraintreePaypalBlock()->process($parentWindow);
        $data = [
            'entity_id' => ['products' => $this->products]
        ];
        $orderData = $this->order !== null ? $this->order->getData() : [];
        $order = $this->fixtureFactory->createByCode(
            'orderInjectable',
            ['data' => array_merge($data, $orderData)]
        );

        return [
            'orderId' => $this->checkoutOnepageSuccess->getSuccessBlock()->getGuestOrderId(),
            'order' => $order
        ];
    }
}
