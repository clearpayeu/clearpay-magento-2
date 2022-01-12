<?php declare(strict_types=1);

namespace Clearpay\Clearpay\Api;

/**
 * Interface for managing Clearpay Checkout
 * @api
 */
interface CheckoutManagementInterface
{
    /**
     * @param string $cartId
     * @param \Clearpay\Clearpay\Api\Data\RedirectPathInterface $redirectPath
     *
     * @return \Clearpay\Clearpay\Api\Data\CheckoutInterface
     *
     * @throws \Magento\Framework\Validation\ValidationException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Payment\Gateway\Command\CommandException
     */
    public function create(
        string $cartId,
        \Clearpay\Clearpay\Api\Data\RedirectPathInterface $redirectPath
    ): \Clearpay\Clearpay\Api\Data\CheckoutInterface;

    /**
     * @param string $cartId
     * @param string $popupOriginUrl
     *
     * @return \Clearpay\Clearpay\Api\Data\CheckoutInterface
     *
     * @throws \Magento\Framework\Validation\ValidationException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Payment\Gateway\Command\CommandException
     */
    public function createExpress(
        string $cartId,
        string $popupOriginUrl
    ): \Clearpay\Clearpay\Api\Data\CheckoutInterface;
}
