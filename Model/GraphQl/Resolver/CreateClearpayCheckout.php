<?php declare(strict_types=1);

namespace Clearpay\Clearpay\Model\GraphQl\Resolver;

use Clearpay\Clearpay\Api\CheckoutManagementInterface;
use Clearpay\Clearpay\Api\Data\CheckoutInterface;
use Clearpay\Clearpay\Api\Data\RedirectPathInterfaceFactory;
use Clearpay\Clearpay\Model\Config;
use GraphQL\Error\Error;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\QuoteGraphQl\Model\Cart\GetCartForUser;

class CreateClearpayCheckout implements ResolverInterface
{
    private Config $config;
    private CheckoutManagementInterface $clearpayCheckoutManagement;
    private RedirectPathInterfaceFactory $redirectPathFactory;
    private GetCartForUser $getCartForUser;

    public function __construct(
        Config                       $config,
        CheckoutManagementInterface  $clearpayCheckoutManagement,
        RedirectPathInterfaceFactory $redirectPathFactory,
        GetCartForUser               $getCartForUser
    ) {
        $this->config = $config;
        $this->clearpayCheckoutManagement = $clearpayCheckoutManagement;
        $this->redirectPathFactory = $redirectPathFactory;
        $this->getCartForUser = $getCartForUser;
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function resolve(
        Field $field,
        $context,
        ResolveInfo $info,
        ?array $value = null,
        ?array $args = null
    ): array {
        try {
            /** @phpstan-ignore-next-line */
            $store = $context->getExtensionAttributes()->getStore();
            $websiteId = $store->getWebsiteId();

        if (!$this->config->getIsPaymentActive((int)$websiteId)) {
            throw new GraphQlInputException(__('Clearpay payment method is not active'));
        }

        if (!$args || !$args['input']) {
            throw new \InvalidArgumentException('Required params cart_id and redirect_path are missing');
        }

        $maskedCartId = $args['input']['cart_id'];
        $clearpayRedirectPath = $args['input']['redirect_path'];

        // Authorise the caller for this cart before any checkout state is written. This also
        // rejects a numeric quote id, which is not a valid masked cart id.
        /** @phpstan-ignore-next-line */
        $this->getCartForUser->execute((string)$maskedCartId, $context->getUserId(), (int)$store->getId());

        $redirectUrls = $this->redirectPathFactory->create()
            ->setConfirmPath($clearpayRedirectPath['confirm_path'])
            ->setCancelPath($clearpayRedirectPath['cancel_path']);

        $checkoutResult = $this->clearpayCheckoutManagement->create($maskedCartId, $redirectUrls);

        return [
            CheckoutInterface::CLEARPAY_TOKEN => $checkoutResult->getClearpayToken(),
            CheckoutInterface::CLEARPAY_AUTH_TOKEN_EXPIRES => $checkoutResult->getClearpayAuthTokenExpires(),
            CheckoutInterface::CLEARPAY_REDIRECT_CHECKOUT_URL => $checkoutResult->getClearpayRedirectCheckoutUrl()
        ];
        } catch (LocalizedException $exception) {
            throw new Error($exception->getMessage());
        }
    }
}
