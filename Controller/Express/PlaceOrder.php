<?php declare(strict_types=1);

namespace Clearpay\Clearpay\Controller\Express;

use Clearpay\Clearpay\Controller\Payment\Capture;
use Clearpay\Clearpay\Gateway\Config\Config;
use Clearpay\Clearpay\Model\Payment\Capture\PlaceOrderProcessor;
use Magento\Checkout\Model\Session;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\UrlInterface;
use Magento\Payment\Gateway\CommandInterface;

class PlaceOrder implements HttpPostActionInterface
{
    private RequestInterface $request;
    private ManagerInterface $messageManager;
    private Session $checkoutSession;
    private JsonFactory $jsonFactory;
    private UrlInterface $url;
    private PlaceOrderProcessor $placeOrderProcessor;
    private CommandInterface $syncCheckoutDataCommand;

    public function __construct(
        RequestInterface    $request,
        ManagerInterface    $messageManager,
        Session             $checkoutSession,
        JsonFactory         $jsonFactory,
        UrlInterface        $url,
        PlaceOrderProcessor $placeOrderProcessor,
        CommandInterface    $syncCheckoutDataCommand
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

        if ($status === Capture::CHECKOUT_STATUS_CANCELLED) {
            return $jsonResult;
        }

        if ($status !== Capture::CHECKOUT_STATUS_SUCCESS) {
            $errorMessage = (string)__('Clearpay payment is declined. Please select an alternative payment method.');
            $this->messageManager->addErrorMessage($errorMessage);

            return $jsonResult->setData(['redirectUrl' => $this->url->getUrl('checkout/cart')]);
        }

        try {
            $quote->getPayment()
                ->setMethod(Config::CODE)
                ->setAdditionalInformation('clearpay_express', true);
            $this->placeOrderProcessor->execute($quote, $this->syncCheckoutDataCommand, $clearpayOrderToken);
        } catch (\Throwable $e) {
            $errorMessage = $e instanceof LocalizedException
                ? $e->getMessage()
                : (string)__('Clearpay payment is declined. Please select an alternative payment method.');
            $this->messageManager->addErrorMessage($errorMessage);

            return $jsonResult->setData(['redirectUrl' => $this->url->getUrl('checkout/cart')]);
        }

        return $jsonResult->setData(['redirectUrl' => $this->url->getUrl('checkout/onepage/success')]);
    }
}
