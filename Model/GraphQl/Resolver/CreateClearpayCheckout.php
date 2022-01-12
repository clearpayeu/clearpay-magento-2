<?php declare(strict_types=1);

namespace Clearpay\Clearpay\Model\GraphQl\Resolver;

use Clearpay\Clearpay\Api\Data\CheckoutInterface;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;

class CreateClearpayCheckout implements \Magento\Framework\GraphQl\Query\ResolverInterface
{
    private \Clearpay\Clearpay\Model\Config $config;
    private \Clearpay\Clearpay\Api\CheckoutManagementInterface $clearpayCheckoutManagement;
    private \Clearpay\Clearpay\Api\Data\RedirectPathInterfaceFactory $redirectPathFactory;

    public function __construct(
        \Clearpay\Clearpay\Model\Config $config,
        \Clearpay\Clearpay\Api\CheckoutManagementInterface $clearpayCheckoutManagement,
        \Clearpay\Clearpay\Api\Data\RedirectPathInterfaceFactory $redirectPathFactory
    ) {
        $this->config = $config;
        $this->clearpayCheckoutManagement = $clearpayCheckoutManagement;
        $this->redirectPathFactory = $redirectPathFactory;
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function resolve(Field $field, $context, ResolveInfo $info, array $value = null, array $args = null): array
    {
        /** @phpstan-ignore-next-line */
        $storeId = $context->getExtensionAttributes()->getStore()->getId();

        if (!$this->config->getIsPaymentActive((int)$storeId)) {
            throw new GraphQlInputException(__('Clearpay payment method is not active'));
        }

        if (!$args || !$args['input']) {
            throw new \InvalidArgumentException('Required params cart_id and redirect_path are missing');
        }

        $maskedCartId = $args['input']['cart_id'];
        $clearpayRedirectPath = $args['input']['redirect_path'];

        $redirectUrls = $this->redirectPathFactory->create()
            ->setConfirmPath($clearpayRedirectPath['confirm_path'])
            ->setCancelPath($clearpayRedirectPath['cancel_path']);

        $checkoutResult = $this->clearpayCheckoutManagement->create($maskedCartId, $redirectUrls);

        return [
            CheckoutInterface::CLEARPAY_TOKEN => $checkoutResult->getClearpayToken(),
            CheckoutInterface::CLEARPAY_AUTH_TOKEN_EXPIRES => $checkoutResult->getClearpayAuthTokenExpires(),
            CheckoutInterface::CLEARPAY_REDIRECT_CHECKOUT_URL => $checkoutResult->getClearpayRedirectCheckoutUrl()
        ];
    }
}
