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
use Magento\Payment\Gateway\CommandInterface;
use Magento\Store\Model\StoreManagerInterface;

class PlaceOrder implements HttpPostActionInterface
{
    private RequestInterface $request;
    private Session $checkoutSession;
    private JsonFactory $jsonFactory;
    private PlaceOrderProcessor $placeOrderProcessor;
    private CommandInterface $syncCheckoutDataCommand;
    private StoreManagerInterface $storeManager;
    private ManagerInterface $messageManager;

    public function __construct(
        RequestInterface      $request,
        Session               $checkoutSession,
        JsonFactory           $jsonFactory,
        PlaceOrderProcessor   $placeOrderProcessor,
        CommandInterface      $syncCheckoutDataCommand,
        StoreManagerInterface $storeManager,
        ManagerInterface      $messageManager
    ) {
        $this->request = $request;
        $this->checkoutSession = $checkoutSession;
        $this->jsonFactory = $jsonFactory;
        $this->placeOrderProcessor = $placeOrderProcessor;
        $this->syncCheckoutDataCommand = $syncCheckoutDataCommand;
        $this->storeManager = $storeManager;
        $this->messageManager = $messageManager;
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

            return $jsonResult->setData([
                'error'       => $errorMessage,
                'redirectUrl' => $this->storeManager->getStore()->getUrl('checkout/cart')
            ]);
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

            return $jsonResult->setData([
                'error'       => $errorMessage,
                'redirectUrl' => $this->storeManager->getStore()->getUrl('checkout/cart')
            ]);
        }

        $this->messageManager->addSuccessMessage((string)__('Clearpay Transaction Completed.'));

        return $jsonResult->setData(['redirectUrl' => $this->storeManager->getStore()->getUrl('checkout/onepage/success')]);
    }
}
