<?php declare(strict_types=1);

namespace Clearpay\Clearpay\Controller\Express;

class GetShippingOptions implements \Magento\Framework\App\Action\HttpPostActionInterface
{
    private \Magento\Checkout\Model\Session $checkoutSession;
    private \Magento\Framework\Controller\Result\JsonFactory $jsonResultFactory;
    private \Magento\Framework\App\RequestInterface $request;
    private \Clearpay\Clearpay\Model\Shipment\Express\ShippingListProvider $shippingListProvider;
    private \Clearpay\Clearpay\Model\Shipment\Express\ShippingAddressUpdater $shippingAddressUpdater;
    private \Psr\Log\LoggerInterface $logger;
    private \Magento\Framework\Message\ManagerInterface $messageManager;

    public function __construct(
        \Magento\Framework\App\RequestInterface $request,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Clearpay\Clearpay\Model\Shipment\Express\ShippingAddressUpdater $shippingAddressUpdater,
        \Clearpay\Clearpay\Model\Shipment\Express\ShippingListProvider $shippingListProvider,
        \Magento\Framework\Controller\Result\JsonFactory $jsonResultFactory,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Psr\Log\LoggerInterface $logger
    ) {
        $this->request = $request;
        $this->checkoutSession = $checkoutSession;
        $this->shippingAddressUpdater = $shippingAddressUpdater;
        $this->shippingListProvider = $shippingListProvider;
        $this->jsonResultFactory = $jsonResultFactory;
        $this->messageManager = $messageManager;
        $this->logger = $logger;
    }

    public function execute()
    {
        $shippingAddress = $this->request->getParams();
        $shippingList = [];
        try {
            $quote = $this->checkoutSession->getQuote();
            $quote = $this->shippingAddressUpdater->fillQuoteWithShippingAddress($shippingAddress, $quote);
            $shippingList = $this->shippingListProvider->provide($quote);
        } catch (\Throwable $e) {
            $this->logger->error($e->getMessage());
        }
        if (empty($shippingList)) {
            $this->messageManager->addErrorMessage(
                (string)__('Shipping is unavailable for this address, or all options exceed Clearpay order limit.')
            );
        }
        return $this->jsonResultFactory->create()
            ->setData($this->getResult($shippingList));
    }

    private function getResult(array $shippingList): array
    {
        if (!empty($shippingList)) {
            return [
                'success' => true,
                'shippingOptions' => $shippingList
            ];
        }
        return [
            'error' => true,
        ];
    }
}
