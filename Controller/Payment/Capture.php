<?php declare(strict_types=1);

namespace Clearpay\Clearpay\Controller\Payment;

class Capture implements \Magento\Framework\App\Action\HttpGetActionInterface
{
    const CHECKOUT_STATUS_CANCELLED = 'CANCELLED';
    const CHECKOUT_STATUS_SUCCESS = 'SUCCESS';

    private \Magento\Framework\App\RequestInterface $request;
    private \Magento\Checkout\Model\Session $session;
    private \Magento\Framework\Controller\Result\RedirectFactory $redirectFactory;
    private \Magento\Framework\Message\ManagerInterface $messageManager;
    private \Clearpay\Clearpay\Model\Payment\Capture\PlaceOrderProcessor $placeOrderProcessor;
    private \Magento\Payment\Gateway\CommandInterface $validateCheckoutDataCommand;

    public function __construct(
        \Magento\Framework\App\RequestInterface $request,
        \Magento\Checkout\Model\Session $session,
        \Magento\Framework\Controller\Result\RedirectFactory $redirectFactory,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Clearpay\Clearpay\Model\Payment\Capture\PlaceOrderProcessor $placeOrderProcessor,
        \Magento\Payment\Gateway\CommandInterface $validateCheckoutDataCommand
    ) {
        $this->request = $request;
        $this->session = $session;
        $this->redirectFactory = $redirectFactory;
        $this->messageManager = $messageManager;
        $this->placeOrderProcessor = $placeOrderProcessor;
        $this->validateCheckoutDataCommand = $validateCheckoutDataCommand;
    }

    public function execute()
    {
        if ($this->request->getParam('status') == self::CHECKOUT_STATUS_CANCELLED) {
            $this->messageManager->addErrorMessage(
                (string)__('You have cancelled your Clearpay payment. Please select an alternative payment method.')
            );
            return $this->redirectFactory->create()->setPath('checkout/cart');
        }
        if ($this->request->getParam('status') != self::CHECKOUT_STATUS_SUCCESS) {
            $this->messageManager->addErrorMessage(
                (string)__('Clearpay payment is failed. Please select an alternative payment method.')
            );
            return $this->redirectFactory->create()->setPath('checkout/cart');
        }

        try {
            $quote = $this->session->getQuote();
            $clearpayOrderToken = $this->request->getParam('orderToken');
            $this->placeOrderProcessor->execute($quote, $this->validateCheckoutDataCommand, $clearpayOrderToken);
        } catch (\Throwable $e) {
            $errorMessage = $e instanceof \Magento\Framework\Exception\LocalizedException
                ? $e->getMessage()
                : (string)__('Payment is failed');
            $this->messageManager->addErrorMessage($errorMessage);
            return $this->redirectFactory->create()->setPath('checkout/cart');
        }

        $this->messageManager->addSuccessMessage((string)__('Clearpay Transaction Completed'));
        return $this->redirectFactory->create()->setPath('checkout/onepage/success');
    }
}
