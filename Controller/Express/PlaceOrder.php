<?php declare(strict_types=1);

namespace Clearpay\Clearpay\Controller\Express;

class PlaceOrder implements \Magento\Framework\App\Action\HttpPostActionInterface
{
    const CANCELLED_STATUS = 'CANCELLED';

    private \Magento\Framework\App\RequestInterface $request;
    private \Magento\Framework\Message\ManagerInterface $messageManager;
    private \Magento\Checkout\Model\Session $checkoutSession;
    private \Magento\Framework\Controller\Result\JsonFactory $jsonFactory;
    private \Magento\Framework\UrlInterface $url;
    private \Clearpay\Clearpay\Model\Payment\Capture\PlaceOrderProcessor $placeOrderProcessor;
    private \Magento\Payment\Gateway\CommandInterface $syncCheckoutDataCommand;

    public function __construct(
        \Magento\Framework\App\RequestInterface $request,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Framework\Controller\Result\JsonFactory $jsonFactory,
        \Magento\Framework\UrlInterface $url,
        \Clearpay\Clearpay\Model\Payment\Capture\PlaceOrderProcessor $placeOrderProcessor,
        \Magento\Payment\Gateway\CommandInterface $syncCheckoutDataCommand
    ) {
        $this->request = $request;
        $this->messageManager = $messageManager;
        $this->checkoutSession = $checkoutSession;
        $this->jsonFactory = $jsonFactory;
        $this->url = $url;
        $this->placeOrderProcessor = $placeOrderProcessor;
        $this->syncCheckoutDataCommand = $syncCheckoutDataCommand;
    }

    public function execute()
    {
        $jsonResult = $this->jsonFactory->create();
        $quote = $this->checkoutSession->getQuote();

        $clearpayOrderToken = $this->request->getParam('orderToken');
        $status = $this->request->getParam('status');

        if ($status === static::CANCELLED_STATUS) {
            return $jsonResult;
        }

        try {
            $quote->getPayment()
                ->setMethod(\Clearpay\Clearpay\Gateway\Config\Config::CODE)
                ->setAdditionalInformation('clearpay_express', true);
            $this->placeOrderProcessor->execute($quote, $this->syncCheckoutDataCommand, $clearpayOrderToken);
        } catch (\Throwable $e) {
            $errorMessage = $e instanceof \Magento\Framework\Exception\LocalizedException
                ? $e->getMessage()
                : (string)__('Clearpay payment is declined. Please select an alternative payment method.');
            $this->messageManager->addErrorMessage($errorMessage);
            return $jsonResult->setData(['redirectUrl' => $this->url->getUrl('checkout/cart')]);
        }

        return $jsonResult->setData(['redirectUrl' => $this->url->getUrl('checkout/onepage/success')]);
    }
}
